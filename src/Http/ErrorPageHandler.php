<?php

declare(strict_types=1);

namespace Simtabi\Laranail\LaravelErrorPages\Http;

use Illuminate\Auth\AuthenticationException;
use Illuminate\Contracts\Config\Repository as Config;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Simtabi\Laranail\LaravelErrorPages\ErrorPages;
use Simtabi\Laranail\LaravelErrorPages\Exceptions\ErrorPageRenderException;
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
        $dir = dirname(__DIR__, 2) . '/resources/error-pages';

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

        $contexts = $this->app->make(ContextResolver::class);
        $contexts->using($errorPages->contextResolverOverride());
        $context = $contexts->resolve($request);

        // Path 1 owns the web/livewire context (native errors:: views).
        if ($context === 'web') {
            return null;
        }

        $status = $e instanceof HttpExceptionInterface ? $e->getStatusCode() : 500;

        if (! $this->handles($status) || $errorPages->shouldSkip($e, $request)) {
            return null;
        }

        try {
            return $this->renderContext($context, $e, $request, $status, $errorPages);
        } catch (Throwable $rendererFailure) {
            report(new ErrorPageRenderException($rendererFailure));

            return null;
        }
    }

    private function renderContext(string $context, Throwable $e, Request $request, int $status, ErrorPages $errorPages): ?SymfonyResponse
    {
        $responses = $this->app->make(ErrorResponseFactory::class);

        // Phase A ships the API/JSON context; Inertia + SPA arrive in Phase B.
        if ($context === 'api') {
            return $responses->json($errorPages->jsonFor($e, $request), $status, $e);
        }

        return null;
    }

    private function handles(int $status): bool
    {
        /** @var list<int|string> $intercept */
        $intercept = (array) $this->config->get('error-pages.codes.intercept', []);

        return in_array($status, array_map(intval(...), $intercept), true);
    }
}
