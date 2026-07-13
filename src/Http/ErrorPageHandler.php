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
use Simtabi\Laranail\ErrorPages\Events\ErrorPageRendered;
use Simtabi\Laranail\ErrorPages\Events\RenderingErrorPage;
use Simtabi\Laranail\ErrorPages\Rendering\StackManager;
use Simtabi\Laranail\ErrorPages\Support\FailureReporter;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;
use Throwable;

/**
 * Wires the package into Laravel's error handling by two complementary paths, so
 * it COMPLEMENTS Ignition/Sentry/Flare rather than competing:
 *
 *   Path 1 (server-HTML web: blade) — push our thin errors dir into
 *   config('view.paths') so Laravel's native `errors::{code}` resolution finds it
 *   as a fallback (the app's own views still win, Ignition still renders dev
 *   500s). No renderable, so it can't double-report or preempt the debug page.
 *   Because this path is pure view precedence, `codes.intercept` and `skipWhen()`
 *   govern Path 2 only (see the docs' coexistence page).
 *
 *   Path 2 (livewire/inertia/spa/api/panel) — ONE gated renderable that defers
 *   (returns null) for validation/auth, the server-HTML (blade) web context,
 *   non-intercepted codes, and consumer vetoes; its render is failure-safe
 *   (reports only OUR failure, then degrades down the fallback ladder).
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

        // Auth always preserves the framework challenge/redirect.
        if ($e instanceof AuthenticationException) {
            return null;
        }

        $errorPages = $this->app->make(ErrorPages::class);

        $stack = Stack::fromValue($errorPages->stackName() ?? (string) $this->config->get('error-pages.stack', 'blade'));

        $contexts = $this->app->make(ContextResolver::class);
        $contexts->using($errorPages->contextResolverOverride());
        $render = RenderContext::make($e, $request, $contexts->resolve($request), $stack);

        // Validation preserves Laravel's default 422 UX, unless opted in to render
        // RFC 9457 problem+json (with a field-level errors[]) for the API context.
        if ($e instanceof ValidationException) {
            if ($render->context === 'api' && (bool) $this->config->get('error-pages.problem.validation', false)) {
                return $this->app->make(ErrorResponseFactory::class)
                    ->json($errorPages->validationJsonFor($e, $request), $e->status, $e);
            }

            return null;
        }

        // Path 1 (native errors:: views) owns the web context for the server-HTML
        // `blade` stack only. A web request under a livewire/Inertia/Vue/React
        // stack is Path 2 (renders the full-page Livewire component, Inertia page,
        // or SPA shell + payload).
        if ($render->context === 'web' && $stack->isServerHtml()) {
            return null;
        }

        if (! $this->handles($render->status) || $errorPages->shouldSkip($e, $request)) {
            return null;
        }

        // Complement Ignition: for a genuine (non-HttpException) 500 in dev, defer
        // the HTML-ish contexts to the debug page; JSON is safe (Ignition is
        // HTML-only). render_debug_pages forces branded output in dev.
        if ($this->shouldDeferToDebug($render)) {
            return null;
        }

        event(new RenderingErrorPage($e, $render->context, $render->status));

        try {
            $response = $this->app->make(StackManager::class)
                ->renderer($render->rendererKey())
                ->render($e, $request, $render->status);

            // Fallback ladder: a stack that cannot render (e.g. its packages are
            // not installed, or a custom driver opts out) degrades to the
            // guaranteed core HTML rather than Laravel's default — except API,
            // which stays JSON (an HTML body would be wrong for a JSON client).
            if ($response === null && $render->context !== 'api') {
                $response = $this->app->make(ErrorResponseFactory::class)
                    ->html($errorPages->htmlFor($e, $request), $render->status, $e);
            }

            if ($response !== null) {
                event(new ErrorPageRendered($e, $render->context, $render->status));
                $errorPages->recordRender($render->status, $render->context);
            }

            return $response;
        } catch (Throwable $rendererFailure) {
            $this->app->make(FailureReporter::class)->report($rendererFailure);

            return null;
        }
    }

    private function shouldDeferToDebug(RenderContext $render): bool
    {
        // API is always branded — Ignition is HTML-only, so there is no debug
        // page to defer to for a JSON client.
        if ($render->context === 'api') {
            return false;
        }

        return ! $render->isHttpException
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
