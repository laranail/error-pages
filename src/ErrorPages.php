<?php

declare(strict_types=1);

namespace Simtabi\Laranail\ErrorPages;

use Closure;
use Illuminate\Contracts\Config\Repository as Config;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use PHPUnit\Framework\Assert;
use Simtabi\Laranail\ErrorPages\Contracts\StackRenderer;
use Simtabi\Laranail\ErrorPages\Core\ErrorPageFactory;
use Simtabi\Laranail\ErrorPages\Core\Rendering\HtmlRenderer;
use Simtabi\Laranail\ErrorPages\Core\Rendering\JsonRenderer;
use Simtabi\Laranail\ErrorPages\Core\Support\Pipeline;
use Simtabi\Laranail\ErrorPages\Core\ValueObjects\ErrorPage;
use Simtabi\Laranail\ErrorPages\Core\ValueObjects\ThemeSettings;
use Simtabi\Laranail\ErrorPages\Enums\Stack;
use Simtabi\Laranail\ErrorPages\Events\ErrorPageRendered;
use Simtabi\Laranail\ErrorPages\Events\RenderingErrorPage;
use Simtabi\Laranail\ErrorPages\Rendering\StackManager;
use Simtabi\Laranail\ErrorPages\Support\FailureReporter;
use Simtabi\Laranail\ErrorPages\Support\ThemeResolver;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Throwable;

/**
 * The package's fluent entry point (facade root). Builds a resolved
 * {@see ErrorPage} from a caught throwable — status derivation, the 4xx/5xx
 * message-security policy, correlation id, and the enrichment pipeline — and
 * renders it via the core renderers. Consumers reshape it from their own
 * provider through the DSL (`stack`/`theme`/`context`/`skipWhen`/`pipe`).
 *
 * @phpstan-type DslState array{skip: list<callable(Throwable, ?Request): bool>, context: ?Closure, stack: ?string, theme: ?string, nonce: Closure|string|null, pipeline: list<callable(ErrorPage): ErrorPage>}
 */
final class ErrorPages
{
    /** @var list<callable(Throwable, ?Request): bool> */
    private array $skipPredicates = [];

    private ?Closure $contextResolver = null;

    private ?string $stackOverride = null;

    private ?string $themeOverride = null;

    private Closure|string|null $nonce = null;

    private bool $recording = false;

    /** @var list<array{status: int, context: string, stack: string, theme: string}> */
    private array $rendered = [];

    /**
     * Boot-time DSL snapshot, captured on the first Octane request for reset.
     *
     * @var DslState|null
     */
    private ?array $baseline = null;

    public function __construct(
        private readonly ErrorPageFactory $factory,
        private readonly Pipeline $pipeline,
        private readonly ThemeResolver $themes,
        private readonly StackManager $stacks,
        private readonly Config $config,
        private readonly FailureReporter $failures,
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
     * A Content-Security-Policy nonce (a value, or a per-request resolver) applied
     * to the inline `<style>` and the enhancement `<script>` — for strict-CSP apps.
     *
     * @param  (Closure(): ?string)|string  $nonce
     */
    public function nonce(Closure|string $nonce): static
    {
        $this->nonce = $nonce;

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

    // -------------------------------------------------------------- octane

    /**
     * Keep this singleton isolated across Octane requests. The intended usage is
     * boot-time DSL config (which must persist), but a per-request `stack()`/
     * `skipWhen()`/`pipe()`/… would otherwise leak into the next request on a
     * persistent worker. On the FIRST request we snapshot the boot baseline; on
     * every request after, we restore it — so boot config survives while
     * per-request mutations are discarded. Wired to Octane's `RequestReceived`.
     */
    public function isolateOctaneRequest(): void
    {
        if ($this->baseline === null) {
            $this->baseline = [
                'skip' => $this->skipPredicates,
                'context' => $this->contextResolver,
                'stack' => $this->stackOverride,
                'theme' => $this->themeOverride,
                'nonce' => $this->nonce,
                'pipeline' => $this->pipeline->snapshot(),
            ];

            return;
        }

        $state = $this->baseline;
        $this->skipPredicates = $state['skip'];
        $this->contextResolver = $state['context'];
        $this->stackOverride = $state['stack'];
        $this->themeOverride = $state['theme'];
        $this->nonce = $state['nonce'];
        $this->pipeline->restore($state['pipeline']);
    }

    // ------------------------------------------------------------- testing

    /**
     * Start recording rendered pages so consumers can assert on them in tests.
     * Rendering still happens as normal; call the assertions below afterwards.
     */
    public function fake(): static
    {
        $this->recording = true;
        $this->rendered = [];

        return $this;
    }

    /**
     * Record a rendered page (called by the handler and the web renderer). A no-op
     * unless {@see fake()} enabled recording.
     */
    public function recordRender(int $status, string $context): void
    {
        if (! $this->recording) {
            return;
        }

        $this->rendered[] = [
            'status' => $status,
            'context' => $context,
            'stack' => Stack::fromValue($this->stackOverride ?? (string) $this->config->get('error-pages.stack', 'blade'))->value,
            'theme' => $this->themeSettings()->preset->value,
        ];
    }

    /**
     * Assert an error page was rendered for a status code (optionally narrowed to
     * a stack and/or theme). Requires {@see fake()}.
     */
    public function assertRendered(int $code, ?string $stack = null, ?string $theme = null): void
    {
        $match = array_any(
            $this->rendered,
            fn (array $r): bool => $r['status'] === $code
                && ($stack === null || $r['stack'] === $stack)
                && ($theme === null || $r['theme'] === $theme),
        );

        Assert::assertTrue($match, sprintf(
            'Failed asserting that an error page was rendered for status %d%s%s.',
            $code,
            $stack === null ? '' : " stack=[{$stack}]",
            $theme === null ? '' : " theme=[{$theme}]",
        ));
    }

    public function assertNothingRendered(): void
    {
        Assert::assertCount(0, $this->rendered, 'Failed asserting that no error page was rendered.');
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
            // Skip the framework's default reason phrase (e.g. `abort(404)` sets
            // "Not Found") — it would replace the nicer localized copy with a bare
            // status phrase. Only a *custom* developer message wins.
            $reasonPhrase = SymfonyResponse::$statusTexts[$status] ?? '';
            if ($message !== '' && $message !== $reasonPhrase) {
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
        return $this->withEnhancement((new HtmlRenderer)->render($page, $this->themeSettings(), $this->nonceValue()));
    }

    /**
     * The resolved CSP nonce for this render (from the `nonce()` DSL), or null.
     */
    public function nonceValue(): ?string
    {
        $nonce = $this->nonce instanceof Closure ? ($this->nonce)() : $this->nonce;

        return is_string($nonce) && $nonce !== '' ? $nonce : null;
    }

    private function nonceAttr(): string
    {
        $nonce = $this->nonceValue();

        return $nonce === null ? '' : ' nonce="' . htmlspecialchars($nonce, ENT_QUOTES) . '"';
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
        $nonce = $this->nonceAttr();

        return match ($mode) {
            'route' => '<script src="' . htmlspecialchars($this->assetUrl('error-pages.js'), ENT_QUOTES) . '"' . $nonce . ' defer></script>',
            'link' => '<script src="' . htmlspecialchars(asset('vendor/error-pages/error-pages.js'), ENT_QUOTES) . '"' . $nonce . ' defer></script>',
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
            $path = dirname(__DIR__) . '/presets/shared/js/enhance.js';
            $version = is_file($path) ? substr((string) md5_file($path), 0, 8) : '0';
        }

        return $version;
    }

    private function inlineEnhancement(): ?string
    {
        $path = dirname(__DIR__) . '/presets/shared/js/enhance.js';
        $js = is_file($path) ? file_get_contents($path) : false;

        return $js === false ? null : '<script' . $this->nonceAttr() . '>' . $js . '</script>';
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

        try {
            $html = $this->htmlFor($e, $request);
        } catch (Throwable $rendererFailure) {
            // Parity with the Path-2 degrade: never let a failing pipe stage /
            // translation / theme throw out of the `errors::{code}` view. Report
            // only OUR failure (throttled + fail-silent, same as Path 2) and
            // return a guaranteed static shell.
            $this->failures->report($rendererFailure);
            $html = $this->minimalShell($status);
        }

        event(new ErrorPageRendered($e, 'web', $status));
        $this->recordRender($status, 'web');

        return $html;
    }

    /**
     * A dependency-free static shell, used only if the branded web render itself
     * throws — so Path 1 still returns branded-ish HTML and the lifecycle events
     * and recording still fire.
     */
    private function minimalShell(int $status): string
    {
        $code = (string) $status;

        return '<!DOCTYPE html><html lang="en"><head><meta charset="utf-8">'
            . '<meta name="viewport" content="width=device-width, initial-scale=1">'
            . '<meta name="robots" content="noindex, nofollow">'
            . '<title>' . $code . '</title></head>'
            . '<body style="font-family:system-ui,sans-serif;text-align:center;padding:4rem 1rem;color:#0f172a">'
            . '<h1 style="font-size:3rem;margin:0">' . $code . '</h1>'
            . '<p style="color:#64748b">An error occurred.</p></body></html>';
    }

    /**
     * The full error payload for the Inertia/SPA components (richer than the
     * RFC 7807 JSON): copy + brand + theme + retry hints + correlation id.
     *
     * @return array<string, mixed>
     */
    public function payloadFor(Throwable $e, ?Request $request = null): array
    {
        return $this->payloadFromPage($this->errorPageFor($e, $request));
    }

    /**
     * The payload for a status code directly — for embedding the Inertia/Vue/React/
     * Livewire component in your own view without a caught exception.
     *
     * @return array<string, mixed>
     */
    public function payloadForCode(int $code): array
    {
        return $this->payloadFromPage($this->pipeline->process($this->factory->make($code, $this->defaultLocale())));
    }

    /**
     * The payload for a status key — a code ("404") or a generic "4xx"/"5xx".
     *
     * @return array<string, mixed>
     */
    public function payloadForKey(string $key): array
    {
        return $this->payloadFromPage($this->pipeline->process($this->factory->makeByKey($key, $this->defaultLocale())));
    }

    /**
     * Shape a resolved page + current theme into the shared component payload.
     *
     * @return array<string, mixed>
     */
    private function payloadFromPage(ErrorPage $page): array
    {
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
                'locale' => $theme->locale,
                'dir' => $theme->dir,
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

        return $this->decorateProblem($payload, $request);
    }

    /**
     * The RFC 9457 problem+json for a validation failure: the standard members
     * plus a field-level `errors[]` array (`pointer`/`field`/`detail`). Used by
     * the handler when `problem.validation` is on for the API context.
     *
     * @return array<string, mixed>
     */
    public function validationJsonFor(ValidationException $e, ?Request $request = null): array
    {
        $errors = [];
        foreach ($e->errors() as $field => $messages) {
            foreach ($messages as $message) {
                $errors[] = ['pointer' => '/' . $field, 'field' => (string) $field, 'detail' => (string) $message];
            }
        }

        $payload = [
            'type' => 'about:blank',
            'title' => 'Validation failed',
            'status' => $e->status,
            'detail' => 'The given data failed validation.',
            'errors' => $errors,
        ];

        $requestId = $this->requestIdFor($request);
        if ($requestId !== null) {
            $payload['request_id'] = $requestId;
        }

        return $this->decorateProblem($payload, $request);
    }

    /**
     * Add the resolved `type` URI (problem-docs route, else `problem_type_base`)
     * and the recommended `instance` (request URI) to a problem payload.
     *
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    private function decorateProblem(array $payload, ?Request $request): array
    {
        $type = $this->problemTypeUri((int) $payload['status']);
        if ($type !== null) {
            $payload['type'] = $type;
        }

        if ($request instanceof Request) {
            $payload['instance'] = $request->getRequestUri();
        }

        return $payload;
    }

    /**
     * The RFC 7807/9457 `type` URI for a status: the served problem-docs page when
     * enabled, else `{problem_type_base}/{status}`, else null (`about:blank` stays).
     */
    private function problemTypeUri(int $status): ?string
    {
        if ((bool) $this->config->get('error-pages.problem.docs.enabled', false)) {
            $root = rtrim((string) $this->config->get('app.url', ''), '/');
            $route = trim((string) $this->config->get('error-pages.problem.docs.route', '/errors/problems'), '/');

            return $root . '/' . $route . '/' . $status;
        }

        $base = (string) $this->config->get('error-pages.problem_type_base', '');

        return $base !== '' ? rtrim($base, '/') . '/' . $status : null;
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
