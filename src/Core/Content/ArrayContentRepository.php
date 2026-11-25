<?php

declare(strict_types=1);

namespace Simtabi\Laranail\ErrorPages\Core\Content;

use Simtabi\Laranail\ErrorPages\Core\Contracts\ContentRepository;

/**
 * The default, dependency-free content source: an in-memory array keyed by
 * status key, optionally nested by locale. The Laravel bridge replaces this with
 * a translation-backed repository; plain-PHP consumers pass their own array.
 */
final readonly class ArrayContentRepository implements ContentRepository
{
    /**
     * @param  array<string, array{title?: string, message?: string}>  $content  key => copy
     * @param  array<string, array<string, array{title?: string, message?: string}>>  $localized  locale => (key => copy)
     */
    public function __construct(
        private array $content = [],
        private array $localized = [],
    ) {}

    public function overridesFor(string $key, ?string $locale = null): array
    {
        $entry = ($locale !== null ? ($this->localized[$locale][$key] ?? null) : null)
            ?? $this->content[$key]
            ?? [];

        return [
            'title' => $this->clean($entry['title'] ?? null),
            'message' => $this->clean($entry['message'] ?? null),
        ];
    }

    private function clean(?string $value): ?string
    {
        return is_string($value) && trim($value) !== '' ? $value : null;
    }
}
