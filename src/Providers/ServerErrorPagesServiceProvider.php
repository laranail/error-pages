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
use Simtabi\Laranail\ServerErrorPages\Commands\ExportCommand;
use Simtabi\Laranail\ServerErrorPages\Commands\ServerConfigCommand;
use Simtabi\Laranail\ServerErrorPages\Content\TranslationContentRepository;
use Simtabi\Laranail\ServerErrorPages\Contracts\ContentRepository;
use Simtabi\Laranail\ServerErrorPages\Contracts\ServerConfigWriter;
use Simtabi\Laranail\ServerErrorPages\Contracts\StaticRenderer;
use Simtabi\Laranail\ServerErrorPages\Services\BladeStaticRenderer;
use Simtabi\Laranail\ServerErrorPages\Services\ServerConfigEmitter;
use Simtabi\Laranail\ServerErrorPages\Services\ServerErrorPagesManager;
use Simtabi\Laranail\ServerErrorPages\Support\ErrorPageFactory;

final class ServerErrorPagesServiceProvider extends PackageServiceProvider
{
    /** Publish tag for the error-view stubs → the app's resources/views/errors. */
    private const string TAG_ERRORS = 'laranail::server-error-pages-errors';

    /** Publish tag for the built bundle → the app's public/vendor/server-error-pages. */
    private const string TAG_ASSETS = 'laranail::server-error-pages-assets';

    public function configurePackage(Package $package): void
    {
        $base = $this->packageRoot();

        $package
            ->name('laranail/server-error-pages')
            ->setPublishTagId('server-error-pages')
            ->hasConfigFile()
            ->hasViews('server-error-pages')
            ->hasAnonymousComponents('resources/views/components')
            ->hasTranslations('server-error-pages')
            ->publish(
                [$base . '/public/assets' => public_path('vendor/server-error-pages')],
                self::TAG_ASSETS,
            )
            ->publish(
                [$base . '/resources/views/errors' => resource_path('views/errors')],
                self::TAG_ERRORS,
            )
            ->hasCommands([
                BuildCommand::class,
                ExportCommand::class,
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
            ->step('Publish config', function (InstallCommand $command): void {
                $command->call('vendor:publish', ['--tag' => 'laranail::server-error-pages-config']);
            })
            ->step('Publish error views', function (InstallCommand $command): void {
                $command->call('vendor:publish', ['--tag' => self::TAG_ERRORS]);
            })
            ->step('Publish content translations', function (InstallCommand $command): void {
                $command->call('vendor:publish', ['--tag' => 'laranail::server-error-pages-translations']);
            })
            ->step('Publish assets', function (InstallCommand $command): void {
                $command->call('vendor:publish', ['--tag' => 'laranail::server-error-pages-assets']);
            })
            ->step('Build static pages and server config', function (InstallCommand $command): void {
                $command->call('server-error-pages:build');
            });
    }

    #[Override]
    public function packageRegistered(): void
    {
        $this->app->singleton(ErrorPageFactory::class);

        $this->app->bind(ContentRepository::class, TranslationContentRepository::class);
        $this->app->bind(StaticRenderer::class, BladeStaticRenderer::class);
        $this->app->bind(ServerConfigWriter::class, ServerConfigEmitter::class);

        $this->app->singleton(ServerErrorPagesManager::class);
        $this->app->alias(ServerErrorPagesManager::class, 'server-error-pages');
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
            'Output path' => (string) $config->get('laranail.server-error-pages.output.path', ''),
            'Assets URL' => (string) $config->get('laranail.server-error-pages.output.assets_url', ''),
            'Theme' => (string) $config->get('laranail.server-error-pages.theme.preset', 'default'),
            'Codes' => implode(', ', array_map(strval(...), $codes)),
            'Server profile' => (string) $config->get('laranail.server-error-pages.server.profile', 'vps'),
        ];
    }
}
