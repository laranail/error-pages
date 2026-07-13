<?php

declare(strict_types=1);

namespace Simtabi\Laranail\ErrorPages\Core\Contracts;

use Simtabi\Laranail\ErrorPages\Core\ValueObjects\ErrorPage;
use Simtabi\Laranail\ErrorPages\Core\ValueObjects\ThemeSettings;

/**
 * Turns a resolved {@see ErrorPage} + {@see ThemeSettings} into a concrete
 * representation: an HTML string (the page renderers) or a structured array
 * (the JSON renderer). The Laravel bridge wraps the result into an HTTP
 * response; a PSR-15 app can use it directly.
 */
interface Renderer
{
    /**
     * @return string|array<string, mixed>
     */
    public function render(ErrorPage $page, ThemeSettings $theme): string|array;
}
