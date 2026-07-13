<?php

declare(strict_types=1);

namespace Simtabi\Laranail\ErrorPages\Http;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Throwable;

/**
 * Wraps rendered output into an HTTP response, propagating the exception's own
 * headers (a 503/429 `Retry-After`, a 401 `WWW-Authenticate`, …) and setting the
 * error-page defaults (`Retry-After` for transient codes, `no-store`, `noindex`,
 * `nosniff`).
 */
final class ErrorResponseFactory
{
    /**
     * @param  array<string, mixed>  $payload
     */
    public function json(array $payload, int $status, Throwable $e): JsonResponse
    {
        $response = new JsonResponse($payload, $status);
        $response->headers->set('Content-Type', 'application/problem+json');

        return $this->harden($response, $status, $e);
    }

    public function html(string $html, int $status, Throwable $e): Response
    {
        return $this->harden(new Response($html, $status, ['Content-Type' => 'text/html; charset=UTF-8']), $status, $e);
    }

    /**
     * Apply the error-response headers to any already-built response (used by the
     * Inertia/SPA stack renderers, which construct their own response first).
     *
     * @template T of SymfonyResponse
     *
     * @param  T  $response
     * @return T
     */
    public function harden(SymfonyResponse $response, int $status, Throwable $e): SymfonyResponse
    {
        if ($e instanceof HttpExceptionInterface) {
            foreach ($e->getHeaders() as $name => $value) {
                $response->headers->set($name, $value);
            }
        }

        if (in_array($status, [429, 502, 503, 504], true) && ! $response->headers->has('Retry-After')) {
            $response->headers->set('Retry-After', '15');
        }

        $response->headers->set('X-Robots-Tag', 'noindex');
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('Cache-Control', 'no-store');

        return $response;
    }
}
