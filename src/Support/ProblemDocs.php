<?php

declare(strict_types=1);

namespace Simtabi\Laranail\ErrorPages\Support;

use Illuminate\Contracts\Config\Repository as Config;
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
        private Config $config,
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
        $locale = $this->defaultLocale();

        if (! $this->translator->has($id, $locale)) {
            return null;
        }

        $value = $this->translator->get($id, [], $locale);

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
     * The configured `content.default_locale`, or null to follow the ambient
     * app locale — matching how the branded card on the same page resolves.
     */
    private function defaultLocale(): ?string
    {
        $locale = $this->config->get('error-pages.content.default_locale');

        return is_string($locale) && $locale !== '' ? $locale : null;
    }

    /**
     * @return list<string>
     */
    private function strings(mixed $value): array
    {
        return array_values(array_map(strval(...), array_filter((array) $value, is_scalar(...))));
    }
}
