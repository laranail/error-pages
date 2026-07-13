<?php

declare(strict_types=1);

namespace Simtabi\Laranail\ErrorPages\Providers;

use Illuminate\Contracts\Config\Repository as Config;
use Illuminate\Contracts\Translation\Translator;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Route;
use Override;
use Simtabi\Laranail\ErrorPages\Commands\PreviewCommand;
use Simtabi\Laranail\ErrorPages\Content\TranslationContentRepository;
use Simtabi\Laranail\ErrorPages\Core\Contracts\ContentRepository;
use Simtabi\Laranail\ErrorPages\Core\ErrorPageFactory;
use Simtabi\Laranail\ErrorPages\Core\Support\Pipeline;
use Simtabi\Laranail\ErrorPages\Doctor\Checks;
use Simtabi\Laranail\ErrorPages\ErrorPages;
use Simtabi\Laranail\ErrorPages\Http\ErrorPageHandler;
use Simtabi\Laranail\ErrorPages\Rendering\StackManager;
use Simtabi\Laranail\Package\Tools\Package;
use Simtabi\Laranail\Package\Tools\Providers\PackageServiceProvider;
use Simtabi\Laranail\Package\Tools\Support\Definitions\AboutSectionDefinition;

/**
 * Wires the runtime error-page renderer into Laravel: bindings for the core
 * engine (fed a translation-backed content repository), the exception-handler
 * hook (Path-1 view injection + Path-2 renderable), translations/config
 * publishing, the preview route, and the doctor/about surfaces.
 */
final class ErrorPagesServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('laranail/laravel-error-pages')
            ->setPublishTagId('error-pages')
            ->withoutConfigNamespacing()
            ->hasConfigFile('error-pages')
            ->hasTranslations('error-pages')
            ->hasCommands(PreviewCommand::class)
            ->hasDoctorChecks(Checks::all())
            ->hasAboutSection(
                AboutSectionDefinition::make('Error Pages')->fieldsUsing(fn (): array => $this->aboutRows()),
            );
    }

    #[Override]
    public function packageRegistered(): void
    {
        $this->app->singleton(
            ContentRepository::class,
            fn ($app): ContentRepository => new TranslationContentRepository($app->make(Translator::class)),
        );

        $this->app->singleton(
            ErrorPageFactory::class,
            fn ($app): ErrorPageFactory => new ErrorPageFactory($app->make(ContentRepository::class)),
        );

        $this->app->singleton(Pipeline::class);
        $this->app->singleton(StackManager::class);

        $this->app->singleton(ErrorPages::class);
        $this->app->alias(ErrorPages::class, 'laranail.error-pages');

        $this->app->singleton(ErrorPageHandler::class);
    }

    #[Override]
    public function packageBooted(): void
    {
        $this->app->make(ErrorPageHandler::class)->register();

        $this->registerPreviewRoute();
    }

    private function registerPreviewRoute(): void
    {
        /** @var Config $config */
        $config = $this->app->make(Config::class);

        $enabled = $config->get('error-pages.preview.enabled');
        $enabled ??= (bool) $config->get('app.debug', false);

        if (! (bool) $enabled) {
            return;
        }

        $base = rtrim((string) $config->get('error-pages.preview.route', '/_error-pages'), '/');

        Route::get($base . '/{code}', function (string $code): Response {
            /** @var ErrorPages $pages */
            $pages = $this->app->make(ErrorPages::class);

            $html = ctype_digit($code)
                ? $pages->htmlForCode((int) $code)
                : $pages->htmlForKey($code);

            return new Response($html, 200, ['Content-Type' => 'text/html; charset=UTF-8']);
        })->where('code', '[0-9]+|4xx|5xx')->name('error-pages.preview');
    }

    /**
     * @return array<string, mixed>
     */
    private function aboutRows(): array
    {
        /** @var Config $config */
        $config = $this->app->make(Config::class);

        return [
            'Enabled' => $config->get('error-pages.enabled') ? 'yes' : 'no',
            'Default stack' => (string) $config->get('error-pages.stack', 'blade'),
            'Theme preset' => (string) $config->get('error-pages.theme.preset', 'default'),
            'Intercepted codes' => implode(', ', array_map(strval(...), (array) $config->get('error-pages.codes.intercept', []))),
        ];
    }
}
