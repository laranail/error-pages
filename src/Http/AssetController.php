<?php

declare(strict_types=1);

namespace Simtabi\Laranail\ErrorPages\Http;

use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Serves the package's static enhancement bundle (the shared stylesheet and the
 * progressive-enhancement JS) from `presets/shared`, immutably cached and
 * ETag-validated. Used when `assets.mode` is `route` — no publish step and no
 * Vite manifest. The URL carries a version segment so an upgrade busts the cache.
 */
final class AssetController
{
    /** file segment => [relative preset path, content type] */
    private const array MAP = [
        'error-pages.css' => ['shared/css/critical.css', 'text/css; charset=UTF-8'],
        'error-pages.js' => ['shared/js/enhance.js', 'application/javascript; charset=UTF-8'],
    ];

    public function __invoke(Request $request, string $file): BinaryFileResponse
    {
        if (! array_key_exists($file, self::MAP)) {
            throw new NotFoundHttpException;
        }

        [$relative, $contentType] = self::MAP[$file];
        $path = dirname(__DIR__, 2) . '/presets/' . $relative;

        if (! is_file($path)) {
            throw new NotFoundHttpException;
        }

        $response = new BinaryFileResponse($path);
        $response->headers->set('Content-Type', $contentType);
        $response->setPublic();
        $response->headers->set('Cache-Control', 'public, max-age=31536000, immutable');
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->setAutoEtag();
        $response->isNotModified($request);

        return $response;
    }
}
