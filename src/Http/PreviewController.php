<?php

declare(strict_types=1);

namespace Simtabi\Laranail\ErrorPages\Http;

use Illuminate\Contracts\Config\Repository as Config;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Simtabi\Laranail\ErrorPages\Core\Enums\ThemePreset;
use Simtabi\Laranail\ErrorPages\ErrorPages;

/**
 * Design-QA preview surface (dev/preview only): a gallery index over every
 * intercepted code × theme, and a single-page preview that honours a `?theme=`
 * override — so branded pages can be reviewed without triggering real errors.
 */
final readonly class PreviewController
{
    public function __construct(
        private ErrorPages $pages,
        private Config $config,
    ) {}

    /**
     * The gallery: a link grid of every code × theme.
     */
    public function index(): Response
    {
        $base = rtrim((string) $this->config->get('error-pages.preview.route', '/_error-pages'), '/');
        $codes = array_map(strval(...), (array) $this->config->get('error-pages.codes.intercept', []));
        $codes = [...$codes, '4xx', '5xx'];
        $rows = '';
        foreach ($codes as $code) {
            $links = '';
            foreach (ThemePreset::cases() as $theme) {
                $href = htmlspecialchars($base . '/' . $code . '?theme=' . $theme->value, ENT_QUOTES);
                $links .= '<a href="' . $href . '">' . htmlspecialchars($theme->value, ENT_QUOTES) . '</a>';
            }
            $rows .= '<tr><th>' . htmlspecialchars($code, ENT_QUOTES) . '</th><td>' . $links . '</td></tr>';
        }
        $html = <<<HTML
        <!DOCTYPE html><html lang="en"><head><meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="robots" content="noindex, nofollow">
        <title>Error pages · preview gallery</title>
        <style>
          :root{color-scheme:light dark}
          body{font:16px/1.5 system-ui,sans-serif;margin:2rem;max-width:60rem}
          h1{font-size:1.4rem} table{border-collapse:collapse;width:100%}
          th,td{padding:.5rem .75rem;border-bottom:1px solid #8883;text-align:left;vertical-align:top}
          td a{display:inline-block;margin:.15rem .4rem .15rem 0}
        </style></head><body>
        <h1>Error pages &middot; preview gallery</h1>
        <p>Each cell opens that status code rendered in the chosen theme.</p>
        <table><thead><tr><th>Code</th><th>Themes</th></tr></thead><tbody>{$rows}</tbody></table>
        </body></html>
        HTML;

        return new Response($html, 200, ['Content-Type' => 'text/html; charset=UTF-8']);
    }

    /**
     * A single branded page for a code (or generic `4xx`/`5xx` key), with an
     * optional `?theme=` override for side-by-side design review.
     */
    public function show(Request $request, string $code): Response
    {
        $theme = $request->query('theme');
        if (is_string($theme) && $theme !== '') {
            $this->pages->theme($theme);
        }

        $html = ctype_digit($code)
            ? $this->pages->htmlForCode((int) $code)
            : $this->pages->htmlForKey($code);

        return new Response($html, 200, ['Content-Type' => 'text/html; charset=UTF-8']);
    }
}
