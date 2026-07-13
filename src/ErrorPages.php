<?php

declare(strict_types=1);

namespace Simtabi\Laranail\ErrorPages;

use Closure;
use Illuminate\Contracts\Config\Repository as Config;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Http\Request;
use Simtabi\Laranail\ErrorPages\Contracts\StackRenderer;
use Simtabi\Laranail\ErrorPages\Core\ErrorPageFactory;
use Simtabi\Laranail\ErrorPages\Core\Rendering\HtmlRenderer;
use Simtabi\Laranail\ErrorPages\Core\Rendering\JsonRenderer;
use Simtabi\Laranail\ErrorPages\Core\Support\Pipeline;
use Simtabi\Laranail\ErrorPages\Core\ValueObjects\ErrorPage;
use Simtabi\Laranail\ErrorPages\Core\ValueObjects\ThemeSettings;
use Simtabi\Laranail\ErrorPages\Events\ErrorPageRendered;
use Simtabi\Laranail\ErrorPages\Events\RenderingErrorPage;
use Simtabi\Laranail\ErrorPages\Rendering\StackManager;
use Simtabi\Laranail\ErrorPages\Support\ThemeResolver;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Throwable;

/**
 * The package's fluent entry point (facade root). Builds a resolved
 * {@see ErrorPage} from a caught throwable — status derivation, the 4xx/5xx
 * message-security policy, correlation id, and the enrichment pipeline — and
 * renders it via the core renderers. Consumers reshape it from their own
 * provider through the DSL (`stack`/`theme`/`context`/`skipWhen`/`pipe`).
 */
final class ErrorPages
{
    /** @var list<callable(Throwable, ?Request): bool> */
    private array $skipPredicates = [];

    private ?Closure $contextResolver = null;

    private ?string $stackOverride = null;

    private ?string $themeOverride = null;

    public function __construct(
        private readonly ErrorPageFactory $factory,
        private readonly Pipeline $pipeline,
        private readonly ThemeResolver $themes,
        private readonly StackManager $stacks,
        private readonly Config $config,
    ) {}

    /**
     * The HTTP status for a throwable: an HttpException's own code, else 500.
     * Centralised so the handler, page builder and web renderer agree.
     */
    public function statusFor(Throwable $e): int
    {
        return $e instanceof HttpExceptionInterface ? $e->getStatusCode() : 500;
    }

    /**
     * Register or override a stack renderer (the coexistence driver seam).
     *
     * @param  Closure(Application): StackRenderer  $factory
     */
    public function extend(string $stack, Closure $factory): static
    {
        $this->stacks->extend($stack, $factory);

        return $this;
    }

    public function stacks(): StackManager
    {
        return $this->stacks;
    }

    // ---------------------------------------------------------------- DSL

    public function stack(string $stack): static
    {
        $this->stackOverride = $stack;

        return $this;
    }

    public function theme(string $preset): static
    {
        $this->themeOverride = $preset;

        return $this;
    }

    public function context(Closure $resolver): static
    {
        $this->contextResolver = $resolver;

        return $this;
    }

    /**
     * Veto handling for matching exceptions/requests (they pass through to Laravel).
     *
     * @param  callable(Throwable, ?Request): bool  $predicate
     */
    public function skipWhen(callable $predicate): static
    {
        $this->skipPredicates[] = $predicate;

        return $this;
    }

    /**
     * @param  callable(ErrorPage): ErrorPage  $stage
     */
    public function pipe(callable $stage): static
    {
        $this->pipeline->pipe($stage);

        return $this;
    }

    public function contextResolverOverride(): ?Closure
    {
        return $this->contextResolver;
    }

    public function stackName(): ?string
    {
        return $this->stackOverride;
    }

    public function themeSettings(): ThemeSettings
    {
        return $this->themes->resolve($this->themeOverride);
    }

    public function shouldSkip(Throwable $e, ?Request $request): bool
    {
        return array_any($this->skipPredicates, fn (callable $predicate): bool => $predicate($e, $request) === true);
    }

    // -------------------------------------------------------- page building

    public function errorPageFor(Throwable $e, ?Request $request = null): ErrorPage
    {
        $status = $this->statusFor($e);
        $page = $this->factory->make($status, $this->defaultLocale());

        // A developer-intended 4xx abort message is safe to show; a 5xx never
        // uses getMessage() (it may carry internals) — always the generic copy.
        //
        // Only show it when the developer set it *directly* (`abort(403, 'msg')`
        // has no previous). Laravel rewrites internal exceptions into
        // HttpExceptions carrying leaky messages while setting `previous` to the
        // cause — e.g. ModelNotFoundException → NotFoundHttpException("No query
        // results for model [App\\Models\\User] 1") and AuthorizationException →
        // AccessDeniedHttpException(...). Those must never reach the end user.
        if ($status < 500 && $e instanceof HttpExceptionInterface && ! $e->getPrevious() instanceof Throwable) {
            $message = trim($e->getMessage());
            if ($message !== '') {
                $page = new ErrorPage($page->key, $page->code, $page->title, $message, $page->retryable, $page->retryAfter, $page->requestId);
            }
        }

        $requestId = $this->requestIdFor($request);
        if ($requestId !== null) {
            $page = $page->withRequestId($requestId);
        }

        return $this->pipeline->process($page);
    }

    /**
     * The locale for content resolution: the configured `content.default_locale`,
     * or null to follow the ambient app locale.
     */
    private function defaultLocale(): ?string
    {
        $locale = $this->config->get('error-pages.content.default_locale');

        return is_string($locale) && $locale !== '' ? $locale : null;
    }

    /**
     * The correlation id surfaced to the user: the configured request-id header
     * (default `X-Request-Id`) if present, else a generated one when
     * `request_id.generate` is on (default), else null.
     */
    private function requestIdFor(?Request $request): ?string
    {
        $header = (string) $this->config->get('error-pages.request_id.header', 'X-Request-Id');

        $id = $request?->headers->get($header);
        if (is_string($id) && trim($id) !== '') {
            // The header is attacker-controllable: strip to a safe charset and
            // clamp the length before it is reflected into the page/JSON (beyond
            // the escaping already applied at render).
            $clean = substr((string) preg_replace('/[^A-Za-z0-9._-]/', '', trim($id)), 0, 128);
            if ($clean !== '') {
                return $clean;
            }
        }

        if ((bool) $this->config->get('error-pages.request_id.generate', true)) {
            return bin2hex(random_bytes(8));
        }

        return null;
    }

    public function htmlFor(Throwable $e, ?Request $request = null): string
    {
        return $this->renderPage($this->errorPageFor($e, $request));
    }

    /**
     * Render a resolved page to HTML and layer the optional progressive
     * enhancement (per `assets.mode`) on top.
     */
    private function renderPage(ErrorPage $page): string
    {
        return $this->withEnhancement((new HtmlRenderer)->render($page, $this->themeSettings()));
    }

    /**
     * Inject the enhancement `<script>` before `</body>` according to
     * `assets.mode` (route | link | inline | off). The page is fully functional
     * without it, so a missing `</body>` or an `off`/unknown mode is a no-op.
     */
    private function withEnhancement(string $html): string
    {
        $tag = $this->enhancementTag();

        if ($tag === null || ! str_contains($html, '</body>')) {
            return $html;
        }

        return str_replace('</body>', $tag . '</body>', $html);
    }

    private function enhancementTag(): ?string
    {
        $mode = (string) $this->config->get('error-pages.assets.mode', 'route');

        return match ($mode) {
            'route' => '<script src="' . htmlspecialchars($this->assetUrl('error-pages.js'), ENT_QUOTES) . '" defer></script>',
            'link' => '<script src="' . htmlspecialchars(asset('vendor/error-pages/error-pages.js'), ENT_QUOTES) . '" defer></script>',
            'inline' => $this->inlineEnhancement(),
            default => null,
        };
    }

    /**
     * URL to a package asset served by the route (with a cache-busting version
     * derived from the file when not configured).
     *
     * Built from the trusted `app.url`, NOT the request host — an error page must
     * never reflect a `Host`/`X-Forwarded-Host` header into a `<script src>`
     * (cache-poisoning / script-source hijack). Falls back to a root-relative URL
     * when `app.url` is unset.
     */
    public function assetUrl(string $file): string
    {
        $base = rtrim((string) $this->config->get('error-pages.assets.route', '/_error-pages/assets'), '/');
        $version = $this->config->get('error-pages.assets.version');
        $version = is_string($version) && $version !== '' ? $version : $this->assetVersion();

        $root = rtrim((string) $this->config->get('app.url', ''), '/');

        return $root . $base . '/' . $file . '?v=' . $version;
    }

    private function assetVersion(): string
    {
        static $version = null;

        if ($version === null) {
            $path = dirname(__DIR__) . '/presets/shared/enhance.js';
            $version = is_file($path) ? substr((string) md5_file($path), 0, 8) : '0';
        }

        return $version;
    }

    private function inlineEnhancement(): ?string
    {
        $path = dirname(__DIR__) . '/presets/shared/enhance.js';
        $js = is_file($path) ? file_get_contents($path) : false;

        return $js === false ? null : '<script>' . $js . '</script>';
    }

    /**
     * Render for the web (blade/livewire) context and fire the lifecycle events.
     * The Path-1 `errors::{code}` views call this instead of `htmlFor()` so those
     * server-rendered pages are observable too (Path 2 fires the events in the
     * exception-handler renderable).
     */
    public function renderForWeb(Throwable $e, ?Request $request = null): string
    {
        $status = $this->statusFor($e);

        event(new RenderingErrorPage($e, 'web', $status));
        $html = $this->htmlFor($e, $request);
        event(new ErrorPageRendered($e, 'web', $status));

        return $html;
    }

    /**
     * The full error payload for the Inertia/SPA components (richer than the
     * RFC 7807 JSON): copy + brand + theme + retry hints + correlation id.
     *
     * @return array<string, mixed>
     */
    public function payloadFor(Throwable $e, ?Request $request = null): array
    {
        $page = $this->errorPageFor($e, $request);
        $theme = $this->themeSettings();

        return [
            'status' => $page->code,
            'code' => $page->key,
            'title' => $page->title,
            'message' => $page->message,
            'retryable' => $page->retryable,
            'retryAfter' => $page->retryAfter,
            'requestId' => $page->requestId,
            'homeUrl' => $theme->brandUrl,
            'brand' => [
                'name' => $theme->brandName,
                'url' => $theme->brandUrl,
                'logo' => $theme->logo,
            ],
            'theme' => [
                'preset' => $theme->preset->value,
                'autoDark' => $theme->autoDark,
            ],
        ];
    }

    /**
     * The RFC 7807 `application/problem+json` payload. The core renderer supplies
     * the standard members; the bridge adds the recommended `instance` (request
     * URI) and, when `problem_type_base` is configured, a per-status `type` URI.
     *
     * @return array<string, mixed>
     */
    public function jsonFor(Throwable $e, ?Request $request = null): array
    {
        $payload = (new JsonRenderer)->render($this->errorPageFor($e, $request), $this->themeSettings());

        $base = (string) $this->config->get('error-pages.problem_type_base', '');
        if ($base !== '') {
            $payload['type'] = rtrim($base, '/') . '/' . $payload['status'];
        }

        if ($request instanceof Request) {
            $payload['instance'] = $request->getRequestUri();
        }

        return $payload;
    }

    /**
     * Render a page for a status code directly (preview / design QA).
     */
    public function htmlForCode(int $code): string
    {
        return $this->renderPage($this->pipeline->process($this->factory->make($code, $this->defaultLocale())));
    }

    /**
     * Render a page for a status key — a code ("404") or a generic "4xx"/"5xx".
     */
    public function htmlForKey(string $key): string
    {
        return $this->renderPage($this->pipeline->process($this->factory->makeByKey($key, $this->defaultLocale())));
    }
}
