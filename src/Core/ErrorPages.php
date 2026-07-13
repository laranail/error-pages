<?php

declare(strict_types=1);

namespace Simtabi\Laranail\ErrorPages\Core;

use Simtabi\Laranail\ErrorPages\Core\Content\ArrayContentRepository;
use Simtabi\Laranail\ErrorPages\Core\Contracts\ContentRepository;
use Simtabi\Laranail\ErrorPages\Core\Contracts\Renderer;
use Simtabi\Laranail\ErrorPages\Core\Rendering\HtmlRenderer;
use Simtabi\Laranail\ErrorPages\Core\Rendering\JsonRenderer;
use Simtabi\Laranail\ErrorPages\Core\Support\Pipeline;
use Simtabi\Laranail\ErrorPages\Core\Support\RendererRegistry;
use Simtabi\Laranail\ErrorPages\Core\ValueObjects\ErrorPage;
use Simtabi\Laranail\ErrorPages\Core\ValueObjects\ThemeSettings;

/**
 * The framework-agnostic entry point: resolves an {@see ErrorPage} through the
 * content chain + enrichment pipeline and renders it via a keyed renderer
 * ('html' | 'json' by default, extendable). The Laravel bridge wraps this behind
 * a facade + fluent DSL; plain-PHP / PSR-15 consumers use it directly.
 */
final readonly class ErrorPages
{
    public function __construct(
        private ErrorPageFactory $factory,
        private RendererRegistry $renderers,
        private Pipeline $pipeline,
    ) {}

    /**
     * Build an engine with the default renderers ('html', 'json') and an
     * optional content source (defaults to an empty array repository → enum copy).
     */
    public static function make(?ContentRepository $content = null): self
    {
        $renderers = new RendererRegistry;
        $renderers->register('html', static fn (): Renderer => new HtmlRenderer);
        $renderers->register('json', static fn (): Renderer => new JsonRenderer);

        return new self(
            new ErrorPageFactory($content ?? new ArrayContentRepository),
            $renderers,
            new Pipeline,
        );
    }

    /**
     * Register or override a renderer by key.
     *
     * @param  callable(): Renderer  $factory
     */
    public function extend(string $key, callable $factory): static
    {
        $this->renderers->register($key, $factory);

        return $this;
    }

    /**
     * Add an ErrorPage enrichment stage.
     *
     * @param  callable(ErrorPage): ErrorPage  $stage
     */
    public function pipe(callable $stage): static
    {
        $this->pipeline->pipe($stage);

        return $this;
    }

    public function page(int $code, ?string $locale = null): ErrorPage
    {
        return $this->pipeline->process($this->factory->make($code, $locale));
    }

    public function pageByKey(string $key, ?string $locale = null): ErrorPage
    {
        return $this->pipeline->process($this->factory->makeByKey($key, $locale));
    }

    /**
     * Render a status code with the given theme via a keyed renderer.
     *
     * @return string|array<string, mixed>
     */
    public function render(int $code, ThemeSettings $theme, string $renderer = 'html', ?string $locale = null): string|array
    {
        return $this->renderers->resolve($renderer)->render($this->page($code, $locale), $theme);
    }

    public function renderers(): RendererRegistry
    {
        return $this->renderers;
    }
}
