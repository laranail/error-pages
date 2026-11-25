<?php

declare(strict_types=1);

namespace Simtabi\Laranail\ErrorPages\Core\Support;

use InvalidArgumentException;
use Simtabi\Laranail\ErrorPages\Core\Contracts\Renderer;

/**
 * The framework-agnostic driver seam: register/override renderers by key and
 * resolve them lazily (cached after first resolution). The Laravel bridge layers
 * an Illuminate\Support\Manager on top for the idiomatic `extend()` DSL.
 */
final class RendererRegistry
{
    /** @var array<string, callable(): Renderer> */
    private array $factories = [];

    /** @var array<string, Renderer> */
    private array $resolved = [];

    /**
     * @param  callable(): Renderer  $factory
     */
    public function register(string $key, callable $factory): static
    {
        $this->factories[$key] = $factory;
        unset($this->resolved[$key]);

        return $this;
    }

    public function has(string $key): bool
    {
        return isset($this->factories[$key]);
    }

    public function resolve(string $key): Renderer
    {
        if (! isset($this->factories[$key])) {
            throw new InvalidArgumentException("No error-page renderer registered for [{$key}].");
        }

        return $this->resolved[$key] ??= ($this->factories[$key])();
    }

    /**
     * @return list<string>
     */
    public function keys(): array
    {
        return array_keys($this->factories);
    }
}
