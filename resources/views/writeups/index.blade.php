@php
    $punyaFilter = $filters['q'] !== '' || $filters['kategori'] !== null || $filters['level'] !== null;

    // URL dashboard dengan filter saat ini; $ubah menimpa sebagian (halaman selalu kembali ke 1).
    $url = fn (array $ubah = []) => route('dashboard', array_filter(
        array_merge([
            'q' => $filters['q'],
            'kategori' => $filters['kategori'],
            'level' => $filters['level'],
        ], $ubah),
        fn ($v) => $v !== null && $v !== '',
    ));

    $chip = 'rounded-full border px-3 py-1.5 text-sm font-medium focus:outline-none focus:ring-2 focus:ring-accent';
    $chipAktif = 'border-accent bg-accent/10 text-accent';
    $chipBiasa = 'border-borderMain bg-transparent text-textSec transition hover:border-textSec';
@endphp

<x-public-layout title="Arsip Write-Up - SolveLog">

  
    {{-- Panel pencarian + filter --}}
    <section class="mt-6 rounded-xl border border-borderMain bg-panel p-5">
        <form method="GET" action="{{ route('dashboard') }}" class="flex flex-col gap-3 sm:flex-row">
            @if ($filters['kategori'] !== null)<input type="hidden" name="kategori" value="{{ $filters['kategori'] }}">@endif
            @if ($filters['level'] !== null)<input type="hidden" name="level" value="{{ $filters['level'] }}">@endif

            <label for="search-input" class="sr-only">Cari judul atau tag write-up</label>
            <div class="relative flex-1">
                <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                    <svg class="h-5 w-5 text-textSec" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                </div>
                <input type="text" id="search-input" name="q" value="{{ $filters['q'] }}" maxlength="100"
                       placeholder="Cari judul atau tag write-up..."
                       class="w-full rounded-lg border border-borderMain bg-bgMain py-2.5 pl-10 pr-4 text-textMain placeholder-textSec transition focus:border-accent focus:outline-none focus:ring-1 focus:ring-accent">
            </div>
            <button type="submit"
                    class="rounded-lg bg-accent px-6 py-2.5 font-medium text-bgMain transition hover:bg-emerald-500 focus:outline-none focus:ring-2 focus:ring-accent focus:ring-offset-2 focus:ring-offset-panel">
                Cari
            </button>
        </form>

        <div class="mt-5 flex flex-col gap-6 md:flex-row md:gap-12">
            <div>
                <h3 class="mb-3 text-sm font-medium text-textSec">Kategori</h3>
                <div class="flex flex-wrap gap-2">
                    <a href="{{ $url(['kategori' => null]) }}" @if ($filters['kategori'] === null) aria-current="true" @endif
                       class="{{ $chip }} {{ $filters['kategori'] === null ? $chipAktif : $chipBiasa }}">Semua</a>
                    @foreach ($categories as $category)
                        <a href="{{ $url(['kategori' => $category->slug]) }}" @if ($filters['kategori'] === $category->slug) aria-current="true" @endif
                           class="{{ $chip }} {{ $filters['kategori'] === $category->slug ? $chipAktif : $chipBiasa }}">{{ $category->name }}</a>
                    @endforeach
                </div>
            </div>

            <div>
                <h3 class="mb-3 text-sm font-medium text-textSec">Level kesulitan</h3>
                <div class="flex flex-wrap gap-2">
                    <a href="{{ $url(['level' => null]) }}" @if ($filters['level'] === null) aria-current="true" @endif
                       class="{{ $chip }} {{ $filters['level'] === null ? $chipAktif : $chipBiasa }}">Semua</a>
                    @foreach ($levels as $level)
                        <a href="{{ $url(['level' => $level->value]) }}" @if ($filters['level'] === $level->value) aria-current="true" @endif
                           class="{{ $chip }} {{ $filters['level'] === $level->value ? $chipAktif : $chipBiasa }}">{{ ucfirst($level->value) }}</a>
                    @endforeach
                </div>
            </div>
        </div>

        <div class="mt-5 border-t border-borderMain pt-4">
            <a href="{{ route('dashboard') }}" class="rounded px-1 text-sm font-medium text-accent hover:underline focus:outline-none focus:ring-2 focus:ring-accent">
                Reset filter
            </a>
        </div>
    </section>

  

    <div class="pb-24">
        @if ($writeups->isNotEmpty())
            <div class="mt-6 grid grid-cols-1 gap-4 md:grid-cols-2 md:gap-5 lg:grid-cols-3">
                @foreach ($writeups as $writeup)
                    <x-writeup-card :writeup="$writeup" :excerpt="$excerpts[$writeup->id]" />
                @endforeach
            </div>

            {{ $writeups->links('pagination.solvelog') }}
        @else
            {{-- Empty state --}}
            <div class="flex flex-col items-center justify-center py-20 text-center">
                <svg class="mb-4 h-16 w-16 text-borderMain" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                </svg>

                @if ($punyaFilter)
                    <p class="mb-6 text-base text-textSec">Tidak ada write-up yang cocok dengan filter.</p>
                    <a href="{{ route('dashboard') }}" class="inline-flex h-btn-h items-center rounded-btn border border-borderMain bg-panel px-6 font-medium text-textMain transition hover:border-accent hover:text-accent focus:outline-none focus:ring-2 focus:ring-accent">
                        Reset filter
                    </a>
                @else
                    <p class="mb-6 text-base text-textSec">Belum ada write-up yang dipublikasikan.</p>
                    <a href="{{ route('writeups.create') }}" class="inline-flex h-btn-h items-center rounded-btn border border-borderMain bg-panel px-6 font-medium text-textMain transition hover:border-accent hover:text-accent focus:outline-none focus:ring-2 focus:ring-accent">
                        Buat Write-Up Pertama
                    </a>
                @endif
            </div>
        @endif
    </div>
</x-public-layout>
