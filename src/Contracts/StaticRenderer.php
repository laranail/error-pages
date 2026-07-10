<?php

declare(strict_types=1);

namespace Simtabi\Laranail\ServerErrorPages\Contracts;

use Simtabi\Laranail\ServerErrorPages\ValueObjects\ErrorPage;
use Simtabi\Laranail\ServerErrorPages\ValueObjects\ThemeSettings;

/**
 * Renders a resolved error page to a single self-contained HTML string
 * (inlined CSS/JS, zero external requests) suitable for writing to disk and
 * serving directly by the web server when the app is down.
 */
interface StaticRenderer
{
    public function render(ErrorPage $page, ThemeSettings $theme): string;
}
