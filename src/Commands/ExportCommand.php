<?php

declare(strict_types=1);

namespace Simtabi\Laranail\ServerErrorPages\Commands;

use Override;

/**
 * Publishes fully rendered, self-contained error pages (CSS/JS/logo inlined) for
 * users who want to drop the pages onto any host without deploying the Laravel
 * app. Equivalent to `server-error-pages:build --standalone`.
 */
final class ExportCommand extends BuildCommand
{
    protected $name = 'laranail::server-error-pages.export';

    protected $description = 'Publish fully self-contained, portable HTML error pages (assets inlined).';

    /** @var list<string> */
    protected array $commandAliases = ['server-error-pages:export'];

    #[Override]
    protected function standalone(): bool
    {
        return true;
    }
}
