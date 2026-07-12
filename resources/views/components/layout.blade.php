@props(['page', 'theme'])
@php($urlBase = config('laranail.server-error-pages.output.url_base', '/'))
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
@include('server-error-pages::partials.head', ['page' => $page, 'theme' => $theme])
</head>
<body class="sep-body sep-theme-{{ $theme->preset->value }}{{ $theme->autoDark ? ' sep-auto-dark' : '' }}" data-sep-url-base="{{ $urlBase }}" data-sep-retryable="{{ $page->retryable ? '1' : '0' }}">
<main class="sep-shell">
<section class="sep-card">
<x-server-error-pages::brand :theme="$theme" />
<x-server-error-pages::status :page="$page" />
<x-server-error-pages::message :page="$page" />
<x-server-error-pages::actions :page="$page" :theme="$theme" />
</section>
</main>
@include('server-error-pages::partials.scripts', ['page' => $page, 'theme' => $theme])
</body>
</html>
