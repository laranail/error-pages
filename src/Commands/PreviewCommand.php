<?php

declare(strict_types=1);

namespace Simtabi\Laranail\LaravelErrorPages\Commands;

use Override;
use Simtabi\Laranail\Console\Tools\Commands\Command;
use Simtabi\Laranail\Console\Tools\Commands\Concerns\SupportsNamespacedNames;
use Simtabi\Laranail\LaravelErrorPages\ErrorPages;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

/**
 * Render a branded error page to an HTML file so it can be designed/reviewed in
 * dev without triggering a real error. Complements the preview route.
 */
final class PreviewCommand extends Command
{
    use SupportsNamespacedNames;

    /** @var string */
    protected $name = 'laranail::laravel-error-pages.preview';

    /** @var string */
    protected $description = 'Render a branded error page to an HTML file (design QA).';

    /** @var list<string> */
    protected array $commandAliases = ['laravel-error-pages:preview'];

    public function handle(ErrorPages $pages): int
    {
        $codeArgument = $this->argument('code');
        $code = is_string($codeArgument) ? $codeArgument : '';

        $html = ctype_digit($code)
            ? $pages->htmlForCode((int) $code)
            : $pages->htmlForKey($code);

        $outputOption = $this->option('output');
        $output = is_string($outputOption) && $outputOption !== ''
            ? $outputOption
            : getcwd() . '/error-preview-' . $code . '.html';

        file_put_contents($output, $html);

        $this->info(sprintf('Rendered %s → %s', $code, $output));

        return self::SUCCESS;
    }

    /**
     * @return array<int, array<int, mixed>>
     */
    #[Override]
    protected function getArguments(): array
    {
        return [
            ['code', InputArgument::REQUIRED, 'HTTP status code (e.g. 404) or a generic key (4xx / 5xx)'],
        ];
    }

    /**
     * @return array<int, array<int, mixed>>
     */
    #[Override]
    protected function getOptions(): array
    {
        return [
            ['output', 'o', InputOption::VALUE_OPTIONAL, 'Write the HTML to this file path'],
        ];
    }
}
