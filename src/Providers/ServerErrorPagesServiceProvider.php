<?php

declare(strict_types=1);

namespace Simtabi\Laranail\ServerErrorPages\Providers;

use Illuminate\Contracts\Config\Repository as Config;
use Override;
use Simtabi\Laranail\Package\Tools\Commands\InstallCommand;
use Simtabi\Laranail\Package\Tools\Package;
use Simtabi\Laranail\Package\Tools\Providers\PackageServiceProvider;
use Simtabi\Laranail\Package\Tools\Support\Definitions\InstallCommandDefinition;
use Simtabi\Laranail\ServerErrorPages\Commands\BuildCommand;
use Simtabi\Laranail\ServerErrorPages\Commands\ClearCommand;
use Simtabi\Laranail\ServerErrorPages\Commands\ServerConfigCommand;
use Simtabi\Laranail\ServerErrorPages\Content\ConfigJsonContentRepository;
use Simtabi\Laranail\ServerErrorPages\Contracts\ContentRepository;
use Simtabi\Laranail\ServerErrorPages\Contracts\ServerConfigWriter;
use Simtabi\Laranail\ServerErrorPages\Contracts\StaticRenderer;
use Simtabi\Laranail\ServerErrorPages\Services\AssetInliner;
use Simtabi\Laranail\ServerErrorPages\Services\BladeStaticRenderer;
use Simtabi\Laranail\ServerErrorPages\Services\ServerConfigEmitter;
use Simtabi\Laranail\ServerErrorPages\Services\ServerErrorPagesManager;
use Simtabi\Laranail\ServerErrorPages\Support\ErrorPageFactory;

final class ServerErrorPagesServiceProvider extends PackageServiceProvider
{
    /**
     * Publish tags (literal so they never depend on internal tag-builder
     * visibility). `--tag=server-error-pages::content` / `::assets`.
     */
    private const string TAG_CONTENT = 'server-error-pages::content';

    private const string TAG_ASSETS = 'server-error-pages::assets';

    public function configurePackage(Package $package): void
    {
        $base = $this->packageRoot();

        $package
            ->name('laranail/server-error-pages')
            ->setPublishTagId('server-error-pages')
            ->hasConfigFile()
            ->hasViews('server-error-pages')
            ->hasAnonymousComponents('resources/views/components')
            ->publish(
                [$base . '/resources/content' => base_path('resources/error-pages')],
                self::TAG_CONTENT,
            )
            ->publish(
                [$base . '/resources/dist' => public_path('vendor/server-error-pages')],
                self::TAG_ASSETS,
            )
            ->hasCommands([
                BuildCommand::class,
                ServerConfigCommand::class,
                ClearCommand::class,
            ])
            ->hasInstallCommand($this->installDefinition())
            ->hasAboutSection('Server Error Pages', fn (): array => $this->aboutRows());
    }

    private function installDefinition(): InstallCommandDefinition
    {
        return InstallCommandDefinition::make()
            ->named('server-error-pages:install')
            ->publishes('config')
            ->step('Publish editable error content', function (InstallCommand $command): void {
                $command->call('vendor:publish', ['--tag' => self::TAG_CONTENT]);
            })
            ->step('Build static pages and server config', function (InstallCommand $command): void {
                $command->call('server-error-pages:build');
            });
    }

    #[Override]
    public function packageRegistered(): void
    {
        $this->app->singleton(ErrorPageFactory::class);
        $this->app->singleton(AssetInliner::class);

        $this->app->bind(ContentRepository::class, ConfigJsonContentRepository::class);
        $this->app->bind(StaticRenderer::class, BladeStaticRenderer::class);
        $this->app->bind(ServerConfigWriter::class, ServerConfigEmitter::class);

        $this->app->singleton(ServerErrorPagesManager::class);
        $this->app->alias(ServerErrorPagesManager::class, 'server-error-pages');
    }

    #[Override]
    public function packageBooted(): void
    {
        // Make `errors::{code}` resolve to the package's error views without
        // publishing, while an app-published `resources/views/errors/{code}`
        // still wins. Laravel's RegisterErrorViewPaths re-reads
        // config('view.paths') and maps each entry to `{path}/errors` on every
        // renderHttpException(), so pushing here survives its replaceNamespace().
        /** @var Config $config */
        $config = $this->app->make(Config::class);
        $errorViews = $this->packageRoot() . '/resources/error-views';
        $paths = (array) $config->get('view.paths', []);
        if (! in_array($errorViews, $paths, true)) {
            $config->push('view.paths', $errorViews);
        }
    }

    /**
     * Absolute package root (…/server-error-pages), from src/Providers/.
     */
    private function packageRoot(): string
    {
        return dirname(__DIR__, 2);
    }

    /**
     * @return array<string, string>
     */
    private function aboutRows(): array
    {
        /** @var Config $config */
        $config = $this->app->make(Config::class);
        $codes = (array) $config->get('laranail.server-error-pages.codes.enabled', []);

        return [
            'Content source' => (string) $config->get('laranail.server-error-pages.content.source', 'json'),
            'Output path' => (string) $config->get('laranail.server-error-pages.output.path', ''),
            'Theme' => (string) $config->get('laranail.server-error-pages.theme.preset', 'default'),
            'Codes' => implode(', ', array_map(strval(...), $codes)),
            'Server profile' => (string) $config->get('laranail.server-error-pages.server.profile', 'vps'),
        ];
    }
}
