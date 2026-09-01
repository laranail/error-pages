<?php

declare(strict_types=1);

namespace Simtabi\Laranail\ErrorPages\Http;

use Illuminate\Http\Request;
use Simtabi\Laranail\ErrorPages\Enums\Stack;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Throwable;

/**
 * The resolved facts for one error render, built once by {@see ErrorPageHandler}
 * and threaded through renderer selection, the debug-deferral decision, and the
 * lifecycle events — so those choices read from one typed value instead of loose
 * `$context`/`$stack`/`$status` locals.
 */
final readonly class RenderContext
{
    public function __construct(
        public Throwable $exception,
        public Request $request,
        /** `web` | `api` | `inertia` | a custom consumer context. */
        public string $context,
        public Stack $stack,
        public int $status,
        public bool $isHttpException,
    ) {}

    public static function make(Throwable $e, Request $request, string $context, Stack $stack): self
    {
        $isHttp = $e instanceof HttpExceptionInterface;

        return new self(
            exception: $e,
            request: $request,
            context: $context,
            stack: $stack,
            status: $isHttp ? $e->getStatusCode() : 500,
            isHttpException: $isHttp,
        );
    }

    /**
     * Map this context (and, for the ambiguous `web` context, its stack) to a
     * stack-renderer key. API is always JSON; an Inertia request/stack uses the
     * Inertia renderer; an SPA stack renders the client shell; any custom context
     * maps to a same-named consumer-registered driver.
     */
    public function rendererKey(): string
    {
        return match ($this->context) {
            'api' => 'json',
            'inertia' => 'inertia',
            'web' => match (true) {
                $this->stack->isInertia() => 'inertia',
                $this->stack->isLivewire() => 'livewire',
                default => 'spa',
            },
            default => $this->context,
        };
    }
}
