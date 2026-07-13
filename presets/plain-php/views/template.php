<?php

declare(strict_types=1);
use Simtabi\Laranail\ErrorPages\Core\ValueObjects\ErrorPage;
use Simtabi\Laranail\ErrorPages\Core\ValueObjects\ThemeSettings;

/**
 * Canonical, framework-agnostic error page (the guaranteed fallback renderer).
 *
 * PLACEHOLDER DESIGN — meets the shared DOM/CSS contract (.ep-shell / .ep-card /
 * .ep-brand / .ep-status / .ep-title / .ep-message / .ep-actions) and WCAG AA;
 * it is superseded by the user's CSS-first templates when they land in
 * presets/blade + presets/shared, from which the other stacks are ported.
 *
 * In scope: $page (ErrorPage), $theme (ThemeSettings), $criticalCss (string),
 * $themeOverrideCss (string), $e (HTML-escaper closure).
 *
 * @var ErrorPage $page
 * @var ThemeSettings $theme
 * @var string $criticalCss
 * @var string $themeOverrideCss
 * @var ?string $nonce
 * @var Closure $e
 */
$bodyClass = 'ep-body ep-theme-' . $e($theme->preset->value) . ($theme->autoDark ? ' ep-auto-dark' : '');
$nonceAttr = ($nonce ?? '') !== '' ? ' nonce="' . $e($nonce) . '"' : '';
?><!DOCTYPE html>
<html lang="<?= $e($theme->locale) ?>" dir="<?= $e($theme->dir) ?>">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex, nofollow">
<meta name="color-scheme" content="<?= $theme->autoDark ? 'light dark' : 'light' ?>">
<?php if ($page->retryable && $page->retryAfter !== null) { ?>
<meta http-equiv="refresh" content="<?= (int) $page->retryAfter ?>">
<?php } ?>
<title><?= $e($page->key) ?> &middot; <?= $e($page->title) ?></title>
<style<?= $nonceAttr ?>><?= $criticalCss ?><?= $themeOverrideCss ?></style>
</head>
<body class="<?= $bodyClass ?>">
<main class="ep-shell" role="main">
  <section class="ep-card">
    <div class="ep-brand">
      <?php if ($theme->logo !== null && $theme->logo !== '') { ?>
        <img class="ep-logo" src="<?= $e($theme->logo) ?>" alt="<?= $e($theme->brandName) ?>">
      <?php } else { ?>
        <span class="ep-brand-name"><?= $e($theme->brandName) ?></span>
      <?php } ?>
    </div>
    <p class="ep-status" aria-hidden="true"><?= $e($page->key) ?></p>
    <h1 class="ep-title"><?= $e($page->title) ?></h1>
    <p class="ep-message"><?= $e($page->message) ?></p>
    <div class="ep-actions">
      <a class="ep-btn ep-btn-primary" href="<?= $e($theme->brandUrl) ?>">Back to home</a>
    </div>
    <?php if ($page->requestId !== null && $page->requestId !== '') { ?>
      <p class="ep-ref">Reference: <code><?= $e($page->requestId) ?></code></p>
    <?php } ?>
  </section>
</main>
</body>
</html>
