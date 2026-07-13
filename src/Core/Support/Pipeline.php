<?php

declare(strict_types=1);

namespace Simtabi\Laranail\ErrorPages\Core\Support;

use Simtabi\Laranail\ErrorPages\Core\ValueObjects\ErrorPage;

/**
 * A tiny, ordered enrichment pipeline: each stage receives the {@see ErrorPage}
 * and returns a (possibly new) one — used to attach a correlation id, support
 * links, solutions, etc. Framework-agnostic; the bridge exposes it via the DSL's
 * `pipe()`.
 */
final class Pipeline
{
    /** @var list<callable(ErrorPage): ErrorPage> */
    private array $stages = [];

    /**
     * @param  callable(ErrorPage): ErrorPage  $stage
     */
    public function pipe(callable $stage): static
    {
        $this->stages[] = $stage;

        return $this;
    }

    public function process(ErrorPage $page): ErrorPage
    {
        foreach ($this->stages as $stage) {
            $page = $stage($page);
        }

        return $page;
    }
}
