<?php

declare(strict_types=1);

use Simtabi\Laranail\ServerErrorPages\Contracts\ServerConfigWriter;

beforeEach(function (): void {
    $this->dir = sys_get_temp_dir() . '/sep-cfg-' . bin2hex(random_bytes(4));
    @mkdir($this->dir, 0777, true);
    $this->htaccess = $this->dir . '/.htaccess';
    $this->nginx = $this->dir . '/errors.conf';

    // Simulate Laravel's own front-controller .htaccess.
    file_put_contents($this->htaccess, "<IfModule mod_rewrite.c>\n    RewriteEngine On\n    RewriteRule ^ index.php [L]\n</IfModule>\n");

    config()->set('laranail.server-error-pages.server.apache.output', $this->htaccess);
    config()->set('laranail.server-error-pages.server.nginx.output', $this->nginx);
});

afterEach(function (): void {
    @unlink($this->htaccess);
    @unlink($this->nginx);
    array_map(unlink(...), glob($this->dir . '/*') ?: []);
    @rmdir($this->dir);
});

it('merges into an existing .htaccess without destroying its rules', function (): void {
    app(ServerConfigWriter::class)->generate(true);

    $content = file_get_contents($this->htaccess);
    expect($content)
        ->toContain('RewriteEngine On')                    // Laravel's rules preserved
        ->toContain('ErrorDocument 404 /errors/404.html')  // our block added
        ->toContain('BEGIN laranail/server-error-pages');
});

it('is idempotent — re-running does not duplicate the block', function (): void {
    $writer = app(ServerConfigWriter::class);
    $writer->generate(true);
    $writer->generate(true);

    $content = file_get_contents($this->htaccess);
    expect(substr_count($content, 'BEGIN laranail/server-error-pages'))->toBe(1)
        ->and(substr_count($content, 'RewriteEngine On'))->toBe(1);
});

it('remove() strips only the managed block and keeps the file', function (): void {
    $writer = app(ServerConfigWriter::class);
    $writer->generate(true);
    $writer->remove();

    $content = file_get_contents($this->htaccess);
    expect(file_exists($this->htaccess))->toBeTrue()
        ->and($content)->toContain('RewriteEngine On')
        ->and($content)->not->toContain('ErrorDocument')
        ->and($content)->not->toContain('BEGIN laranail/server-error-pages');
});

it('deletes a dedicated nginx file that only held the managed block', function (): void {
    $writer = app(ServerConfigWriter::class);
    $writer->generate(true);
    expect(file_exists($this->nginx))->toBeTrue();

    $writer->remove();
    expect(file_exists($this->nginx))->toBeFalse();
});

it('emits apache fallback ErrorDocuments for common extra codes', function (): void {
    $result = app(ServerConfigWriter::class)->generate(false);

    expect($result['apache']['content'])
        ->toContain('ErrorDocument 410 /errors/4xx.html')
        ->toContain('ErrorDocument 501 /errors/5xx.html');
});
