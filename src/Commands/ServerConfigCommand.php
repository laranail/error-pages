<?php

declare(strict_types=1);

namespace Simtabi\Laranail\ServerErrorPages\Commands;

use Override;
use Simtabi\Laranail\Console\Tools\Commands\Command;
use Simtabi\Laranail\Console\Tools\Commands\Concerns\SupportsNamespacedNames;
use Simtabi\Laranail\ServerErrorPages\Contracts\ServerConfigWriter;
use Symfony\Component\Console\Input\InputOption;

/**
 * Prints (or writes) the Apache/Nginx config that points the web server at the
 * static error pages — without regenerating the pages themselves.
 */
final class ServerConfigCommand extends Command
{
    use SupportsNamespacedNames;

    protected $name = 'laranail::server-error-pages.server-config';

    protected $description = 'Print or write the Apache/Nginx error-page config.';

    /** @var list<string> */
    protected array $commandAliases = ['server-error-pages:server-config'];

    public function handle(ServerConfigWriter $server): int
    {
        $write = (bool) $this->option('write');

        foreach ($server->generate($write) as $label => $result) {
            $this->newLine();
            if ($result['written']) {
                $this->info(sprintf('%s written to %s', ucfirst($label), $result['path']));
            } else {
                $this->info(sprintf('%s (preview — pass --write to save to %s):', ucfirst($label), $result['path']));
                $this->line($result['content']);
            }
        }

        return self::SUCCESS;
    }

    /**
     * @return list<InputOption>
     */
    #[Override]
    protected function getOptions(): array
    {
        return [
            new InputOption('write', null, InputOption::VALUE_NONE, 'Write the config to its configured output path instead of printing it.'),
        ];
    }
}
