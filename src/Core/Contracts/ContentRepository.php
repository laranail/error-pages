<?php

declare(strict_types=1);

namespace Simtabi\Laranail\ErrorPages\Core\Contracts;

/**
 * Resolves editable content overrides (title/message) for a status key. The
 * built-in enum defaults are applied later by the error-page factory, so absent
 * fields are returned as null. Implementations back this with an array, config,
 * translations, a database — anything.
 */
interface ContentRepository
{
    /**
     * Title/message overrides for a status key ('404', '4xx', …). Absent fields
     * are null so the caller can fall back to enum defaults.
     *
     * @return array{title: ?string, message: ?string}
     */
    public function overridesFor(string $key, ?string $locale = null): array;
}
