<?php

declare(strict_types=1);

namespace Simtabi\Laranail\ServerErrorPages\Contracts;

/**
 * Resolves editable, file-managed content overrides for a status key.
 *
 * Overrides come from JSON content files then PHP config (in that precedence);
 * the built-in enum defaults are applied later by the error-page factory.
 */
interface ContentRepository
{
    /**
     * Title/message overrides for a status key ('404', '4xx', …). Absent fields
     * are returned as null so the caller can fall back to enum defaults.
     *
     * @return array{title: ?string, message: ?string}
     */
    public function overridesFor(string $key, ?string $locale = null): array;
}
