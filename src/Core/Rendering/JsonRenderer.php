<?php

declare(strict_types=1);

namespace Simtabi\Laranail\ErrorPages\Core\Rendering;

use Simtabi\Laranail\ErrorPages\Core\Contracts\Renderer;
use Simtabi\Laranail\ErrorPages\Core\ValueObjects\ErrorPage;
use Simtabi\Laranail\ErrorPages\Core\ValueObjects\ThemeSettings;

/**
 * Renders an {@see ErrorPage} as an RFC 7807 `application/problem+json` payload.
 * Never leaks internals — only the end-user-safe title/message/code and an
 * optional correlation id. The bridge sets the `Content-Type` and status.
 */
final class JsonRenderer implements Renderer
{
    /**
     * @return array<string, mixed>
     */
    public function render(ErrorPage $page, ThemeSettings $theme): array
    {
        return array_filter([
            'type' => 'about:blank',
            'title' => $page->title,
            'status' => $page->code,
            'detail' => $page->message,
            'code' => $page->key,
            'request_id' => $page->requestId,
            'retry_after' => $page->retryAfter,
        ], static fn (mixed $value): bool => $value !== null);
    }
}
