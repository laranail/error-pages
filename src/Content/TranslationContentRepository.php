<?php

declare(strict_types=1);

namespace Simtabi\Laranail\ServerErrorPages\Content;

use Illuminate\Contracts\Translation\Translator;
use Simtabi\Laranail\ServerErrorPages\Contracts\ContentRepository;

/**
 * File-managed content via Laravel translations. Titles/messages come from
 * `server-error-pages::errors.{key}.{field}`; an app-published
 * `lang/vendor/server-error-pages/{locale}/errors.php` overrides the package
 * automatically, and a missing key returns null so the caller falls back to the
 * HttpStatus enum default. No database.
 */
final readonly class TranslationContentRepository implements ContentRepository
{
    private const string NAMESPACE = 'server-error-pages::errors';

    public function __construct(private Translator $translator) {}

    public function overridesFor(string $key, ?string $locale = null): array
    {
        return [
            'title' => $this->line($key, 'title', $locale),
            'message' => $this->line($key, 'message', $locale),
        ];
    }

    private function line(string $key, string $field, ?string $locale): ?string
    {
        $id = self::NAMESPACE . '.' . $key . '.' . $field;

        if (! $this->translator->has($id, $locale)) {
            return null;
        }

        $value = $this->translator->get($id, [], $locale);

        return is_string($value) && trim($value) !== '' ? $value : null;
    }
}
