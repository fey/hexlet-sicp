<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}"
  data-mantine-color-scheme="{{ $page['props']['colorScheme'] ?? 'light' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="description" content="{{ $description ?? __('layout.meta.description') }}">
    @isset($robots)
        <meta name="robots" content="{{ $robots }}">
    @endisset

    <title inertia>{{ __('layout.title.name') }}</title>

    <x-hreflang-tags />
    @viteReactRefresh
    @vite('resources/js/app.tsx')
    @inertiaHead
</head>
<body>
    @inertia
</body>
</html>
