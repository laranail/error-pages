<?php

declare(strict_types=1);

namespace Simtabi\Laranail\ErrorPages\Support;

use Illuminate\Contracts\Translation\Translator;

/**
 * Resolves the human-readable documentation (meaning / common causes / how to
 * fix) shown on a problem-type page, from the `error-pages::problems.{key}`
 * translations. A specific code falls back to its class (`4xx`/`5xx`) entry.
 */
final readonly class ProblemDocs
{
    public function __construct(
        private Translator $translator,
    ) {}

    /**
     * @return array{meaning: string, causes: list<string>, resolution: list<string>}
     */
    public function for(string $code): array
    {
        $classKey = $code === '5xx' || (ctype_digit($code) && (int) $code >= 500) ? '5xx' : '4xx';

        return $this->line($code)
            ?? $this->line($classKey)
            ?? ['meaning' => '', 'causes' => [], 'resolution' => []];
    }

    /**
     * @return array{meaning: string, causes: list<string>, resolution: list<string>}|null
     */
    private function line(string $key): ?array
    {
        $id = 'error-pages::problems.' . $key;

        if (! $this->translator->has($id)) {
            return null;
        }

        $value = $this->translator->get($id);

        if (! is_array($value)) {
            return null;
        }

        return [
            'meaning' => is_string($value['meaning'] ?? null) ? $value['meaning'] : '',
            'causes' => $this->strings($value['causes'] ?? []),
            'resolution' => $this->strings($value['resolution'] ?? []),
        ];
    }

    /**
     * @return list<string>
     */
    private function strings(mixed $value): array
    {
        return array_values(array_map(strval(...), array_filter((array) $value, is_scalar(...))));
    }
}
