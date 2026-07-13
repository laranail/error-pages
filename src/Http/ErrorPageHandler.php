<?php

declare(strict_types=1);

namespace Simtabi\Laranail\ErrorPages\Http;

use Illuminate\Auth\AuthenticationException;
use Illuminate\Contracts\Config\Repository as Config;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Simtabi\Laranail\ErrorPages\Enums\Stack;
use Simtabi\Laranail\ErrorPages\ErrorPages;
use Simtabi\Laranail\ErrorPages\Exceptions\ErrorPageRenderException;
use Simtabi\Laranail\ErrorPages\Rendering\StackManager;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Throwable;

/**
 * Wires the package into Laravel's error handling by two complementary paths, so
 * it COMPLEMENTS Ignition/Sentry/Flare rather than competing:
 *
 *   Path 1 (web/livewire) — push our thin errors dir into config('view.paths') so
 *   Laravel's native `errors::{code}` resolution finds it as a fallback (the app's
 *   own views still win, Ignition still renders dev 500s). No renderable, so it
 *   can't double-report or preempt the debug page.
 *
 *   Path 2 (api/inertia/spa) — ONE gated renderable that defers (returns null) for
 *   validation/auth, the web context, non-intercepted codes, and consumer vetoes;
 *   its render is failure-safe (reports only OUR failure, then degrades).
 *
 * Registration is idempotent (Octane-safe) and dependencies resolve fresh per call.
 */
final class ErrorPageHandler
{
    private bool $registered = false;

    public function __construct(
        private readonly Application $app,
        private readonly Config $config,
    ) {}

    public function register(): void
    {
        if ($this->registered) {
            return;
        }
        $this->registered = true;

        if (! (bool) $this->config->get('error-pages.enabled', true)) {
            return;
        }

        $this->registerViewPath();
        $this->registerRenderable();
    }

    private function registerViewPath(): void
    {
        // The package's views live in resources/views (its errors/{code}.blade.php
        // are picked up as `errors::{code}` fallbacks once this dir is a view path).
        $dir = dirname(__DIR__, 2) . '/resources/views';

        /** @var list<string> $paths */
        $paths = (array) $this->config->get('view.paths', []);

        if (! in_array($dir, $paths, true)) {
            $paths[] = $dir;
            $this->config->set('view.paths', $paths);
        }
    }

    private function registerRenderable(): void
    {
        $handler = $this->app->make(ExceptionHandler::class);

        if (! method_exists($handler, 'renderable')) {
            return;
        }

        $handler->renderable(fn (Throwable $e, Request $request): ?SymfonyResponse => $this->render($e, $request));
    }

    private function render(Throwable $e, Request $request): ?SymfonyResponse
    {
        if (! (bool) $this->config->get('error-pages.enabled', true)) {
            return null;
        }

        // Preserve framework UX: validation feedback + auth challenge/redirect.
        if ($e instanceof ValidationException || $e instanceof AuthenticationException) {
            return null;
        }

        $errorPages = $this->app->make(ErrorPages::class);

        $stack = Stack::fromValue($errorPages->stackName() ?? (string) $this->config->get('error-pages.stack', 'blade'));

        $contexts = $this->app->make(ContextResolver::class);
        $contexts->using($errorPages->contextResolverOverride());
        $context = $contexts->resolve($request);

        // Path 1 (native errors:: views) owns the web context for server-HTML
        // stacks (blade/livewire). A web request under a Vue/React SPA stack is
        // Path 2 (renders the shell + payload).
        if ($context === 'web' && $stack->isServerHtml()) {
            return null;
        }

        $status = $e instanceof HttpExceptionInterface ? $e->getStatusCode() : 500;

        if (! $this->handles($status) || $errorPages->shouldSkip($e, $request)) {
            return null;
        }

        // Complement Ignition: for a genuine (non-HttpException) 500 in dev, defer
        // the HTML-ish contexts to the debug page; JSON is safe (Ignition is
        // HTML-only). render_debug_pages forces branded output in dev.
        if ($this->shouldDeferToDebug($e, $context)) {
            return null;
        }

        try {
            return $this->app->make(StackManager::class)
                ->renderer($this->rendererKeyFor($context))
                ->render($e, $request, $status);
        } catch (Throwable $rendererFailure) {
            report(new ErrorPageRenderException($rendererFailure));

            return null;
        }
    }

    /**
     * Map a resolved (context, stack) to a stack-renderer key. API is always
     * JSON; Inertia is the Inertia renderer; a web SPA stack renders the shell;
     * any custom context maps to a same-named consumer-registered driver.
     */
    private function rendererKeyFor(string $context): string
    {
        return match ($context) {
            'api' => 'json',
            'inertia' => 'inertia',
            'web' => 'spa',
            default => $context,
        };
    }

    private function shouldDeferToDebug(Throwable $e, string $context): bool
    {
        if ($context === 'api') {
            return false;
        }

        return ! ($e instanceof HttpExceptionInterface)
            && (bool) $this->config->get('app.debug', false)
            && ! (bool) $this->config->get('error-pages.render_debug_pages', false);
    }

    private function handles(int $status): bool
    {
        /** @var list<int|string> $intercept */
        $intercept = (array) $this->config->get('error-pages.codes.intercept', []);

        return in_array($status, array_map(intval(...), $intercept), true);
    }
}
