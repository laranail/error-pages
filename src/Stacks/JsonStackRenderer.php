<?php

declare(strict_types=1);

namespace Simtabi\Laranail\ErrorPages\Stacks;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Simtabi\Laranail\ErrorPages\Contracts\StackRenderer;
use Simtabi\Laranail\ErrorPages\ErrorPages;
use Simtabi\Laranail\ErrorPages\Http\ErrorResponseFactory;
use Throwable;

/**
 * The API context renderer: RFC 7807 `application/problem+json`.
 */
final readonly class JsonStackRenderer implements StackRenderer
{
    public function __construct(
        private ErrorPages $pages,
        private ErrorResponseFactory $responses,
    ) {}

    public function render(Throwable $e, Request $request, int $status): JsonResponse
    {
        return $this->responses->json($this->pages->jsonFor($e, $request), $status, $e);
    }
}
