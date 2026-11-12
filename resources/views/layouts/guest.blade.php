<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Laravel') }}</title>

        <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased bg-page text-gray-50">
    <div class="min-h-screen flex items-center justify-center px-4">
        <div class="w-full max-w-sm rounded-2xl border border-line bg-surface p-8 shadow-2xl shadow-black/40">
            <a href="/" class="flex justify-center">
                <x-application-logo class="h-9 w-9 fill-current text-gray-50" />
            </a>

            @isset($heading)
                <div class="mt-4 text-center">{{ $heading }}</div>
            @endisset

            <div class="mt-6">{{ $slot }}</div>
        </div>
    </div>
</body>
</html>
