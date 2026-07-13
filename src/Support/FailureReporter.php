<?php

declare(strict_types=1);

namespace Simtabi\Laranail\ErrorPages\Support;

use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Contracts\Config\Repository as Config;
use Illuminate\Contracts\Container\Container;
use Simtabi\Laranail\ErrorPages\Exceptions\ErrorPageRenderException;
use Throwable;

/**
 * Reports OUR renderer failure — never the original exception (the framework
 * already reported it). Used by BOTH render paths (the Path-2 handler and the
 * Path-1 `renderForWeb`) so `report.throttle` and fail-silent behaviour apply
 * uniformly: a persistently-broken branded page can't flood the log, and a
 * broken log channel can't break the fallback render.
 */
final readonly class FailureReporter
{
    public function __construct(
        private Container $app,
        private Config $config,
    ) {}

    public function report(Throwable $rendererFailure): void
    {
        $throttle = (int) $this->config->get('error-pages.report.throttle', 0);

        if ($throttle > 0 && $this->throttled($rendererFailure, $throttle)) {
            return;
        }

        try {
            report(new ErrorPageRenderException($rendererFailure));
        } catch (Throwable) {
            // Never let reporting itself break the fallback render.
        }
    }

    /**
     * True when a report of this failure signature was already sent within the
     * window. Fails open (a cache error still reports).
     */
    private function throttled(Throwable $failure, int $seconds): bool
    {
        try {
            $cache = $this->app->make(CacheRepository::class);
            $key = 'error-pages:report:' . sha1($failure::class . '|' . $failure->getMessage());

            if ($cache->get($key) !== null) {
                return true;
            }

            $cache->put($key, true, $seconds);
        } catch (Throwable) {
            // Fail open: never let the throttle bookkeeping suppress a report.
        }

        return false;
    }
}
