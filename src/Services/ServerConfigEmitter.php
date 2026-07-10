<?php

declare(strict_types=1);

namespace Simtabi\Laranail\ServerErrorPages\Services;

use Illuminate\Contracts\Config\Repository as Config;
use Illuminate\Filesystem\Filesystem;
use Simtabi\Laranail\ServerErrorPages\Contracts\ServerConfigWriter;
use Simtabi\Laranail\ServerErrorPages\Enums\HostingProfile;
use Simtabi\Laranail\ServerErrorPages\Support\SecurityHeaders;

/**
 * Renders the Apache `.htaccess` / Nginx `error_page` snippets that point the
 * web server at the generated static pages and carry the security headers.
 *
 * The snippet is written as a MANAGED BLOCK between sentinel markers and merged
 * into the target file, so existing content (notably Laravel's own
 * `public/.htaccess` front-controller rules) is preserved. Writes to
 * app/FTP-writable locations by default — never to /etc.
 */
final readonly class ServerConfigEmitter implements ServerConfigWriter
{
    private const string CONFIG = 'laranail.server-error-pages';

    private const string BEGIN = '# BEGIN laranail/server-error-pages (managed block - do not edit)';

    private const string END = '# END laranail/server-error-pages';

    /** Common extra client codes routed to the generic 4xx page. */
    private const array EXTRA_4XX = [402, 405, 406, 407, 408, 409, 410, 411, 412, 413, 414, 415, 416, 417, 418, 421, 422, 423, 424, 425, 426, 428, 431, 451];

    /** Common extra server codes routed to the generic 5xx page. */
    private const array EXTRA_5XX = [501, 505, 506, 507, 508, 510, 511];

    public function __construct(
        private Config $config,
        private Filesystem $files,
    ) {}

    public function generate(bool $write = true): array
    {
        $result = [];

        foreach ($this->targets() as $label => [$path, $content]) {
            $result[$label] = $this->emit($path, $content, $write);
        }

        return $result;
    }

    public function remove(): array
    {
        $removed = [];

        foreach ($this->targets() as $label => [$path]) {
            if ($path === '') {
                continue;
            }
            if (! $this->files->exists($path)) {
                continue;
            }
            $existing = $this->files->get($path);
            $stripped = $this->stripRegion($existing);

            if ($stripped === $existing) {
                continue; // no managed block present
            }

            if (trim($stripped) === '') {
                $this->files->delete($path);
            } else {
                $this->files->put($path, rtrim($stripped) . "\n");
            }

            $removed[$label] = $path;
        }

        return $removed;
    }

    /**
     * @return array<string, array{0: string, 1: string}> label => [path, rendered content]
     */
    private function targets(): array
    {
        $targets = [];

        if ((bool) $this->config->get(self::CONFIG . '.server.apache.enabled', true)) {
            $targets['apache'] = [
                (string) $this->config->get(self::CONFIG . '.server.apache.output', ''),
                $this->renderApache(),
            ];
        }

        if ((bool) $this->config->get(self::CONFIG . '.server.nginx.enabled', true)) {
            $path = (string) $this->config->get(self::CONFIG . '.server.nginx.output', '');
            $targets['nginx'] = [$path, $this->renderNginx($path)];
        }

        return $targets;
    }

    /**
     * @return array{path: string, content: string, written: bool}
     */
    private function emit(string $path, string $content, bool $write): array
    {
        $written = false;

        if ($write && $path !== '') {
            $existing = $this->files->exists($path) ? $this->files->get($path) : '';
            $this->files->ensureDirectoryExists(dirname($path));
            $this->files->put($path, $this->mergeInto($existing, $this->wrap($content)));
            $written = true;
        }

        return ['path' => $path, 'content' => $content, 'written' => $written];
    }

    private function wrap(string $block): string
    {
        return self::BEGIN . "\n" . rtrim($block) . "\n" . self::END;
    }

    /**
     * Replace the managed block in $existing (or append it if absent),
     * preserving all other content.
     */
    private function mergeInto(string $existing, string $wrapped): string
    {
        $pattern = $this->regionPattern();

        if (preg_match($pattern, $existing) === 1) {
            // Callback form avoids $/\ interpretation in the replacement.
            return rtrim((string) preg_replace_callback($pattern, static fn (): string => "\n" . $wrapped, $existing, 1)) . "\n";
        }

        $existing = rtrim($existing);

        return ($existing === '' ? '' : $existing . "\n\n") . $wrapped . "\n";
    }

    private function stripRegion(string $content): string
    {
        return (string) preg_replace($this->regionPattern(), '', $content);
    }

    private function regionPattern(): string
    {
        return '/\n*' . preg_quote(self::BEGIN, '/') . '.*?' . preg_quote(self::END, '/') . '/s';
    }

    private function renderApache(): string
    {
        $profile = HostingProfile::fromValue((string) $this->config->get(self::CONFIG . '.server.profile', 'vps'));
        $stub = $this->stub('apache/htaccess-' . $profile->value . '.stub');

        $lines = [];
        foreach ($this->enabledCodes() as $code) {
            $lines[] = 'ErrorDocument ' . $code . ' ' . $this->fileUrl((string) $code);
        }

        if ($this->fallbacksEnabled()) {
            foreach ($this->diff(self::EXTRA_4XX) as $code) {
                $lines[] = 'ErrorDocument ' . $code . ' ' . $this->fileUrl('4xx');
            }
            foreach ($this->diff(self::EXTRA_5XX) as $code) {
                $lines[] = 'ErrorDocument ' . $code . ' ' . $this->fileUrl('5xx');
            }
        }

        return $this->fill($stub, [
            'ERROR_DOCUMENTS' => implode("\n", $lines),
            'SECURITY_HEADERS' => SecurityHeaders::apache($this->headers(), ''),
        ]);
    }

    private function renderNginx(string $selfPath): string
    {
        $stub = $this->stub('nginx/error_page.conf.stub');

        $lines = [];
        foreach ($this->enabledCodes() as $code) {
            $lines[] = 'error_page ' . $code . ' ' . $this->fileUrl((string) $code);
        }

        if ($this->fallbacksEnabled()) {
            $extra4xx = $this->diff(self::EXTRA_4XX);
            $extra5xx = $this->diff(self::EXTRA_5XX);
            if ($extra4xx !== []) {
                $lines[] = 'error_page ' . implode(' ', $extra4xx) . ' ' . $this->fileUrl('4xx');
            }
            if ($extra5xx !== []) {
                $lines[] = 'error_page ' . implode(' ', $extra5xx) . ' ' . $this->fileUrl('5xx');
            }
        }

        return $this->fill($stub, [
            'ERROR_PAGES' => implode("\n", $lines),
            'ERRORS_URL' => $this->errorsUrl(),
            'SECURITY_HEADERS' => SecurityHeaders::nginx($this->headers()),
            'SELF_PATH' => $selfPath !== '' ? $selfPath : '/etc/nginx/snippets/errors.conf',
        ]);
    }

    /**
     * @param  array<string, string>  $replacements
     */
    private function fill(string $stub, array $replacements): string
    {
        $replacements['GENERATED_AT'] = date('c');

        foreach ($replacements as $key => $value) {
            $stub = str_replace('{{' . $key . '}}', $value, $stub);
        }

        return $stub;
    }

    private function fileUrl(string $key): string
    {
        return $this->errorsUrl() . '/' . $key . '.html';
    }

    private function errorsUrl(): string
    {
        $path = trim((string) $this->config->get(self::CONFIG . '.output.url_path', '/errors'));

        if ($path === '') {
            $path = '/errors';
        }

        return '/' . trim($path, '/');
    }

    private function fallbacksEnabled(): bool
    {
        return (bool) $this->config->get(self::CONFIG . '.codes.fallbacks', true);
    }

    /**
     * @return list<int>
     */
    private function enabledCodes(): array
    {
        return array_values(array_map(intval(...), (array) $this->config->get(self::CONFIG . '.codes.enabled', [])));
    }

    /**
     * @param  list<int>  $candidates
     * @return list<int>
     */
    private function diff(array $candidates): array
    {
        return array_values(array_diff($candidates, $this->enabledCodes()));
    }

    /**
     * @return array<string, string>
     */
    private function headers(): array
    {
        return SecurityHeaders::fromConfig($this->config);
    }

    private function stub(string $relative): string
    {
        $path = dirname(__DIR__, 2) . '/resources/server/' . $relative;

        return $this->files->exists($path) ? $this->files->get($path) : '';
    }
}
