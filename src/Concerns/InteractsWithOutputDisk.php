<?php

declare(strict_types=1);

namespace Simtabi\Laranail\ServerErrorPages\Concerns;

use Illuminate\Contracts\Config\Repository as Config;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\Storage;

/**
 * Resolves where generated static pages are written and performs the write.
 * Uses a Laravel filesystem disk when `output.disk` is set, otherwise writes to
 * the local `output.path` directly. Requires the using class to expose a
 * `Config $config` and `Filesystem $files`.
 */
trait InteractsWithOutputDisk
{
    abstract protected function config(): Config;

    abstract protected function files(): Filesystem;

    /**
     * Write one page and return the absolute (or disk-relative) path written.
     */
    protected function writePage(string $key, string $html): string
    {
        $filename = str_replace('{code}', $key, (string) $this->config()->get('laranail.server-error-pages.output.filename', '{code}.html'));
        $disk = $this->config()->get('laranail.server-error-pages.output.disk');
        $path = (string) $this->config()->get('laranail.server-error-pages.output.path', '');

        if (is_string($disk) && $disk !== '') {
            $target = trim($path, '/') . '/' . $filename;
            Storage::disk($disk)->put($target, $html);

            return $disk . '::' . $target;
        }

        $this->files()->ensureDirectoryExists($path);
        $target = rtrim($path, '/') . '/' . $filename;
        $this->files()->put($target, $html);

        return $target;
    }

    /**
     * Delete all generated pages for the given keys. Returns the paths removed.
     *
     * @param  list<string>  $keys
     * @return list<string>
     */
    protected function deletePages(array $keys): array
    {
        $removed = [];
        $template = (string) $this->config()->get('laranail.server-error-pages.output.filename', '{code}.html');
        $disk = $this->config()->get('laranail.server-error-pages.output.disk');
        $path = (string) $this->config()->get('laranail.server-error-pages.output.path', '');

        foreach ($keys as $key) {
            $filename = str_replace('{code}', $key, $template);

            if (is_string($disk) && $disk !== '') {
                $target = trim($path, '/') . '/' . $filename;
                if (Storage::disk($disk)->exists($target)) {
                    Storage::disk($disk)->delete($target);
                    $removed[] = $disk . '::' . $target;
                }

                continue;
            }

            $target = rtrim($path, '/') . '/' . $filename;
            if ($this->files()->exists($target)) {
                $this->files()->delete($target);
                $removed[] = $target;
            }
        }

        return $removed;
    }
}
