<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name', 'SolveLog') }}</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-sans antialiased bg-page text-gray-50">
    <header class="border-b border-line">
        <div class="mx-auto flex h-12 max-w-6xl items-center justify-between px-6">
            <a href="/" class="text-lg font-semibold tracking-tight text-accent">SolveLog</a>

            <nav class="flex items-center gap-6 text-sm text-gray-400">
 
                @auth
                    <a href="{{ route('profile.edit') }}" class="hover:text-gray-50">{{ Auth::user()->name }}</a>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="hover:text-gray-50">Logout</button>
                    </form>
                @else
                    <a href="{{ route('login') }}" class="hover:text-gray-50">Login</a>
                @endauth
            </nav>
        </div>
    </header>

    <main>{{ $slot }}</main>
</body>
</html>