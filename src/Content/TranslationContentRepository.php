<?php

declare(strict_types=1);

namespace Simtabi\Laranail\ErrorPages\Content;

use Illuminate\Contracts\Translation\Translator;
use Simtabi\Laranail\ErrorPages\Core\Contracts\ContentRepository;

/**
 * Content overrides backed by Laravel translations
 * (`error-pages::errors.{key}.{title|message}`). App-published
 * `lang/vendor/error-pages/{locale}/errors.php` overrides the package copy; a
 * missing key returns null so the core factory falls back to the enum default.
 */
final readonly class TranslationContentRepository implements ContentRepository
{
    private const string NAMESPACE = 'error-pages::errors';

    public function __construct(
        private Translator $translator,
    ) {}

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
