<?php

declare(strict_types=1);

namespace Simtabi\Laranail\ErrorPages;

use Closure;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Http\Request;
use Simtabi\Laranail\ErrorPages\Contracts\StackRenderer;
use Simtabi\Laranail\ErrorPages\Core\ErrorPageFactory;
use Simtabi\Laranail\ErrorPages\Core\Rendering\HtmlRenderer;
use Simtabi\Laranail\ErrorPages\Core\Rendering\JsonRenderer;
use Simtabi\Laranail\ErrorPages\Core\Support\Pipeline;
use Simtabi\Laranail\ErrorPages\Core\ValueObjects\ErrorPage;
use Simtabi\Laranail\ErrorPages\Core\ValueObjects\ThemeSettings;
use Simtabi\Laranail\ErrorPages\Rendering\StackManager;
use Simtabi\Laranail\ErrorPages\Support\ThemeResolver;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Throwable;

/**
 * The bridge's fluent entry point (facade root). Builds a resolved
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
    ) {}

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
        $status = $e instanceof HttpExceptionInterface ? $e->getStatusCode() : 500;
        $page = $this->factory->make($status);

        // A developer-intended 4xx abort message is safe to show; a 5xx never
        // uses getMessage() (it may carry internals) — always the generic copy.
        if ($status < 500 && $e instanceof HttpExceptionInterface) {
            $message = trim($e->getMessage());
            if ($message !== '') {
                $page = new ErrorPage($page->key, $page->code, $page->title, $message, $page->retryable, $page->retryAfter, $page->requestId);
            }
        }

        $requestId = $request?->headers->get('X-Request-Id');
        if (is_string($requestId) && $requestId !== '') {
            $page = $page->withRequestId($requestId);
        }

        return $this->pipeline->process($page);
    }

    public function htmlFor(Throwable $e, ?Request $request = null): string
    {
        return (new HtmlRenderer)->render($this->errorPageFor($e, $request), $this->themeSettings());
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
     * @return array<string, mixed>
     */
    public function jsonFor(Throwable $e, ?Request $request = null): array
    {
        return (new JsonRenderer)->render($this->errorPageFor($e, $request), $this->themeSettings());
    }

    /**
     * Render a page for a status code directly (preview / design QA).
     */
    public function htmlForCode(int $code): string
    {
        $page = $this->pipeline->process($this->factory->make($code));

        return (new HtmlRenderer)->render($page, $this->themeSettings());
    }

    /**
     * Render a page for a status key — a code ("404") or a generic "4xx"/"5xx".
     */
    public function htmlForKey(string $key): string
    {
        $page = $this->pipeline->process($this->factory->makeByKey($key));

        return (new HtmlRenderer)->render($page, $this->themeSettings());
    }
}
