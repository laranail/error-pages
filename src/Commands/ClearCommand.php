<?php

declare(strict_types=1);

namespace Simtabi\Laranail\ServerErrorPages\Commands;

use Simtabi\Laranail\Console\Tools\Commands\Command;
use Simtabi\Laranail\Console\Tools\Commands\Concerns\SupportsNamespacedNames;
use Simtabi\Laranail\ServerErrorPages\Contracts\ServerConfigWriter;
use Simtabi\Laranail\ServerErrorPages\Services\StaticSiteBuilder;

/**
 * Removes the generated static pages and the package's managed block from the
 * emitted server-config files (never deletes a shared file like Laravel's
 * `public/.htaccess` — only strips the managed block).
 */
final class ClearCommand extends Command
{
    use SupportsNamespacedNames;

    protected $name = 'laranail::server-error-pages.clear';

    protected $description = 'Delete generated static error pages and server config.';

    /** @var list<string> */
    protected array $commandAliases = ['server-error-pages:clear'];

    public function handle(StaticSiteBuilder $builder, ServerConfigWriter $server): int
    {
        $removed = $builder->clear();

        foreach ($server->remove() as $path) {
            $removed[] = $path;
        }

        if ($removed === []) {
            $this->info('Nothing to remove.');

            return self::SUCCESS;
        }

        foreach ($removed as $path) {
            $this->line('  cleaned ' . $path);
        }
        $this->info(sprintf('Cleaned %d file/location(s).', count($removed)));

        return self::SUCCESS;
    }
}
