<?php

declare(strict_types=1);

namespace Simtabi\Laranail\ServerErrorPages\Commands;

use Override;
use RuntimeException;
use Simtabi\Laranail\Console\Tools\Commands\Command;
use Simtabi\Laranail\Console\Tools\Commands\Concerns\SupportsNamespacedNames;
use Simtabi\Laranail\ServerErrorPages\Contracts\ServerConfigWriter;
use Simtabi\Laranail\ServerErrorPages\Exceptions\NotSelfContainedException;
use Simtabi\Laranail\ServerErrorPages\Services\StaticSiteBuilder;
use Symfony\Component\Console\Input\InputOption;

/**
 * Generates the static error pages (linked assets by default) and, unless
 * disabled, the matching web-server config. `--standalone` produces fully
 * inlined, portable single-file pages (see {@see ExportCommand}).
 */
class BuildCommand extends Command
{
    use SupportsNamespacedNames;

    protected $name = 'laranail::server-error-pages.build';

    protected $description = 'Generate the static error pages and web-server config.';

    /** @var list<string> */
    protected array $commandAliases = ['server-error-pages:build'];

    public function handle(StaticSiteBuilder $builder, ServerConfigWriter $server): int
    {
        $standalone = $this->standalone();

        try {
            $report = $builder->build($standalone, $this->requestedKeys());
        } catch (NotSelfContainedException|RuntimeException $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        if ($report === []) {
            $this->warn('No pages matched the requested codes.');

            return self::FAILURE;
        }

        $rows = [];
        foreach ($report as $key => $info) {
            $rows[] = [$key, number_format($info['bytes']) . ' B', $info['path']];
        }
        $this->table(['Code', 'Size', 'File'], $rows);
        $this->info(sprintf(
            'Generated %d %s error page(s).',
            count($report),
            $standalone ? 'standalone (inlined)' : 'static',
        ));

        if (! (bool) $this->option('no-server')) {
            foreach ($server->generate(true) as $label => $result) {
                $this->line(sprintf('  <info>%s</info> → %s', $label, $result['path']));
            }
            $this->comment('Wire the generated config into your web server (see docs), then reload it.');
        }

        return self::SUCCESS;
    }

    /**
     * Whether to produce inlined, portable pages. Overridden by ExportCommand.
     */
    protected function standalone(): bool
    {
        return (bool) $this->option('standalone');
    }

    /**
     * @return list<string>|null
     */
    private function requestedKeys(): ?array
    {
        $codes = $this->option('codes');

        if (! is_string($codes) || trim($codes) === '') {
            return null;
        }

        $keys = array_values(array_filter(array_map(trim(...), explode(',', $codes))));

        return $keys === [] ? null : $keys;
    }

    /**
     * @return list<InputOption>
     */
    #[Override]
    protected function getOptions(): array
    {
        return [
            new InputOption('codes', null, InputOption::VALUE_OPTIONAL, 'Comma-separated status keys to build (e.g. "404,503,5xx"). Default: all configured.'),
            new InputOption('no-server', null, InputOption::VALUE_NONE, 'Only build the HTML pages; skip the Apache/Nginx config.'),
            new InputOption('standalone', null, InputOption::VALUE_NONE, 'Inline CSS/JS/logo into fully self-contained, portable pages (no separate assets).'),
        ];
    }
}
