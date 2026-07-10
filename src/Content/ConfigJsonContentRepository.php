<?php

declare(strict_types=1);

namespace Simtabi\Laranail\ServerErrorPages\Content;

use Illuminate\Contracts\Config\Repository as Config;
use Simtabi\Laranail\ServerErrorPages\Contracts\ContentRepository;
use Simtabi\Laranail\Toolkit\Services\FileService;

/**
 * File-managed content: per-locale JSON files first, then PHP config. Both are
 * edited via git (VPS) or FTP (shared hosting) — there is no database. JSON is
 * read from the app's published `content.json_path`, falling back to the
 * package's shipped defaults.
 */
final class ConfigJsonContentRepository implements ContentRepository
{
    private const string CONFIG = 'laranail.server-error-pages';

    /** @var array<string, array<array-key, mixed>> cache of decoded JSON per locale */
    private array $jsonCache = [];

    public function __construct(
        private readonly FileService $files,
        private readonly Config $config,
    ) {}

    public function overridesFor(string $key, ?string $locale = null): array
    {
        $locale = $locale ?: (string) $this->config->get(self::CONFIG . '.content.default_locale', 'en');

        $title = null;
        $message = null;

        // 1. JSON content files (unless the source is pinned to config only).
        if ($this->config->get(self::CONFIG . '.content.source', 'json') === 'json') {
            $entry = $this->json($locale)[$key] ?? null;
            if (is_array($entry)) {
                $title = $this->stringOrNull($entry['title'] ?? null);
                $message = $this->stringOrNull($entry['message'] ?? null);
            }
        }

        // 2. PHP config `messages` (fills any gaps JSON left).
        $messages = (array) $this->config->get(self::CONFIG . '.messages', []);
        $cfg = $messages[$key] ?? $messages[$this->intKey($key)] ?? null;
        if (is_array($cfg)) {
            $title ??= $this->stringOrNull($cfg['title'] ?? null);
            $message ??= $this->stringOrNull($cfg['message'] ?? null);
        }

        return ['title' => $title, 'message' => $message];
    }

    /**
     * Decoded JSON for a locale: app-published file first, then the package
     * default. Cached per locale. JSON object keys like "404" are coerced to
     * int array keys by PHP, hence array-key.
     *
     * @return array<array-key, mixed>
     */
    private function json(string $locale): array
    {
        if (isset($this->jsonCache[$locale])) {
            return $this->jsonCache[$locale];
        }

        foreach ($this->candidatePaths($locale) as $path) {
            if ($this->files->exists($path)) {
                $decoded = $this->files->fromJson($path);
                if (is_array($decoded)) {
                    return $this->jsonCache[$locale] = $decoded;
                }
            }
        }

        return $this->jsonCache[$locale] = [];
    }

    /**
     * @return list<string>
     */
    private function candidatePaths(string $locale): array
    {
        $configured = (string) $this->config->get(self::CONFIG . '.content.json_path', 'resources/error-pages');

        // Support both an absolute path and one relative to the app base.
        $base = str_starts_with($configured, '/') ? $configured : base_path($configured);

        return [
            rtrim($base, '/') . '/' . $locale . '.json',
            dirname(__DIR__, 2) . '/resources/content/' . $locale . '.json',
        ];
    }

    private function intKey(string $key): int|string
    {
        return ctype_digit($key) ? (int) $key : $key;
    }

    private function stringOrNull(mixed $value): ?string
    {
        return is_string($value) && trim($value) !== '' ? $value : null;
    }
}
