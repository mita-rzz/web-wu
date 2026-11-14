@props(['title' => null])

@php
    // Gaya tombol "Postingan Saya": aktif bila sedang di halamannya.
    $mineAktif = request()->routeIs('writeups.mine');
@endphp
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? config('app.name', 'SolveLog') }}</title>

    {{-- Font Inter (sama seperti HTML acuan) --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-bgMain font-sans text-textMain antialiased">

    {{-- Efek grid latar + ambient glow (lihat app.css / app.js) --}}
    <div class="ambient-glow" aria-hidden="true"></div>
    <div class="glow-grid" aria-hidden="true"></div>

    <div class="mx-auto max-w-container px-4 md:px-6">

        {{-- Header --}}
        <header class="pb-2 pt-6">
            <div class="flex items-center justify-between">
                <a href="{{ route('dashboard') }}" class="rounded text-2xl font-bold tracking-tight text-accent focus:outline-none focus:ring-2 focus:ring-accent">
                    SolveLog
                </a>

                {{-- Navigasi desktop --}}
                <div class="hidden items-center gap-3 md:flex">
                    <a href="{{ route('writeups.mine') }}" @if ($mineAktif) aria-current="page" @endif
                       class="inline-flex h-btn-h items-center rounded-btn border px-4 font-medium transition focus:outline-none focus:ring-2 focus:ring-accent {{ $mineAktif ? 'border-accent bg-accent/10 text-accent' : 'border-borderMain text-textSec hover:border-textSec hover:text-textMain' }}">
                        Postingan Saya
                    </a>
                    <a href="{{ route('writeups.create') }}"
                       class="inline-flex h-btn-h items-center rounded-btn bg-accent px-4 font-medium text-bgMain transition hover:bg-emerald-500 focus:outline-none focus:ring-2 focus:ring-accent focus:ring-offset-2 focus:ring-offset-bgMain">
                        + Buat Write-Up
                    </a>

                    <div class="ml-4 flex items-center gap-3 border-l border-borderMain pl-4">
                        @auth
                            <span class="inline-flex items-center gap-2 text-sm font-medium text-textMain" title="{{ Auth::user()->display_name }}">
                                <span class="h-2 w-2 rounded-full bg-accent blink-green-light" aria-hidden="true"></span>
                                {{ Auth::user()->username }}
                            </span>
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button type="submit" class="rounded text-sm text-textSec hover:text-textMain focus:outline-none focus:ring-2 focus:ring-accent">Logout</button>
                            </form>
                        @else
                            <a href="{{ route('login') }}" class="rounded text-sm text-textSec hover:text-textMain focus:outline-none focus:ring-2 focus:ring-accent">Login</a>
                        @endauth
                    </div>
                </div>

                {{-- Akun mobile (baris 1) --}}
                <div class="flex items-center gap-3 md:hidden">
                    @auth
                        <span class="inline-flex items-center gap-1.5 text-sm font-medium text-textMain" title="{{ Auth::user()->display_name }}">
                            <span class="h-2 w-2 rounded-full bg-accent blink-green-light" aria-hidden="true"></span>
                            {{ Auth::user()->username }}
                        </span>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="text-sm text-textSec hover:text-textMain">Logout</button>
                        </form>
                    @else
                        <a href="{{ route('login') }}" class="text-sm text-textSec hover:text-textMain">Login</a>
                    @endauth
                </div>
            </div>

            {{-- Tombol mobile (baris 2) --}}
            <div class="mt-4 flex gap-3 md:hidden">
                <a href="{{ route('writeups.mine') }}" @if ($mineAktif) aria-current="page" @endif
                   class="inline-flex h-btn-h flex-1 items-center justify-center rounded-btn border px-4 text-sm font-medium transition focus:outline-none focus:ring-2 focus:ring-accent {{ $mineAktif ? 'border-accent bg-accent/10 text-accent' : 'border-borderMain text-textSec hover:text-textMain' }}">
                    Postingan Saya
                </a>
                <a href="{{ route('writeups.create') }}"
                   class="inline-flex h-btn-h flex-1 items-center justify-center rounded-btn bg-accent px-4 text-sm font-medium text-bgMain transition hover:bg-emerald-500">
                    + Buat Write-Up
                </a>
            </div>
        </header>

        {{-- Banner flash --}}
        @if (session('status'))
            <div role="status" class="mt-8 flex flex-col justify-between gap-3 rounded-lg border border-accent/40 bg-emerald-950/40 p-4 text-sm sm:flex-row sm:items-center">
                <div class="flex items-center gap-2 font-medium text-accent">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                    </svg>
                    {{ session('status') }}
                </div>
                @auth
                    <a href="{{ route('writeups.mine') }}" class="rounded px-1 text-textMain underline hover:text-accent focus:outline-none focus:ring-2 focus:ring-accent">
                        Lihat postingan saya
                    </a>
                @endauth
            </div>
        @endif

        <main>{{ $slot }}</main>
    </div>
</body>
</html>
