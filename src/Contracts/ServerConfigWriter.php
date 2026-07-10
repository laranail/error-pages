<?php

declare(strict_types=1);

namespace Simtabi\Laranail\ServerErrorPages\Contracts;

/**
 * Generates the web-server config that points at the static error pages
 * (Apache `.htaccess` `ErrorDocument`, Nginx `error_page`) plus the security
 * headers that must live at the server layer.
 */
interface ServerConfigWriter
{
    /**
     * Render the configured server snippets. When $write is true, the managed
     * block is merged into each (app/FTP-writable) output between sentinel
     * markers — existing content (e.g. Laravel's own `.htaccess` rewrite rules)
     * is preserved. Otherwise the snippets are only rendered.
     *
     * @return array<string, array{path: string, content: string, written: bool}>
     *                                                                            keyed by server label ("apache", "nginx")
     */
    public function generate(bool $write = true): array;

    /**
     * Remove the managed block from each output file (deleting the file only if
     * nothing else remains). Returns the paths that were changed, keyed by label.
     *
     * @return array<string, string>
     */
    public function remove(): array;
}
