@props([
    'title' => null,
    'metaDescription' => null,
    'canonicalUrl' => null,
])
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
<head>
    @include('partials.head', ['title' => $title])
    @if ($metaDescription)
        <meta name="description" content="{{ $metaDescription }}">
        <meta property="og:title" content="{{ $title ?? config('app.name') }}">
        <meta property="og:description" content="{{ $metaDescription }}">
        <meta property="og:type" content="website">
    @endif
    @if ($canonicalUrl)
        <link rel="canonical" href="{{ $canonicalUrl }}">
    @endif
</head>
<body class="min-h-svh bg-zinc-50 text-zinc-900 antialiased dark:bg-zinc-950 dark:text-zinc-100">
    {{ $slot }}
    @stack('scripts')
    @fluxScripts
</body>
</html>
