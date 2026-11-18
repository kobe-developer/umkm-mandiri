<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="theme-color" content="#f6f2ea">
    <title inertia>{{ $brand['name'] }}</title>
    <link rel="icon" href="{{ $brand['favicon'] }}" type="image/png" data-brand-favicon>
    {{-- PWA: staf memasang varian POS, pelanggan varian menu --}}
    <link rel="manifest" href="{{ request()->is('menu', 'menu/*') ? '/manifest.webmanifest' : '/manifest-pos.webmanifest' }}">
    <link rel="apple-touch-icon" href="{{ $brand['apple_touch'] }}">
    <meta name="apple-mobile-web-app-title" content="{{ $brand['name'] }}">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @inertiaHead
</head>
<body>
    @inertia
</body>
</html>
