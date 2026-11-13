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
  
    
    <div class="flex h-16 w-full items-center justify-between px-8">
        <a href="/" class="text-3xl font-semibold tracking-tight text-accent">SolveLog</a>

      <x-primary-button href="/test" class="inline-flex items-center gap-2 rounded-full bg-accent px-4 py-1.5 text-sm font-semibold text-gray-950 hover:opacity-90 transition">
    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
    </svg>
    <span>Create Post</span>
</x-primary-button>
    





        <nav class="flex items-center gap-6 text-base text-gray-400">
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