@php
    $categories = ['Web', 'Crypto', 'Pwn', 'Forensics', 'Mobile'];
    $levels     = ['Easy', 'Medium', 'Hard', 'Insane'];
    $techTags   = ['#SQLi', '#XSS', '#RCE', '#SSRF', '#IDOR', '#ROP', '#JWT', '#RSA'];
    $toolTags   = ['BurpSuite', 'Ghidra', 'GDB', 'Wireshark', 'Volatility', 'Frida', 'pwntools', 'CyberChef'];

    $chip  = 'inline-flex h-8 cursor-pointer items-center rounded-full border border-line bg-surface px-4 text-gray-300 transition hover:border-accent hover:text-accent-soft';
    $label = 'text-xs font-semibold uppercase tracking-wider text-accent';

    $levelClass = [
        'Easy'   => 'bg-level-easy',
        'Medium' => 'bg-level-medium',
        'Hard'   => 'bg-level-hard',
        'Insane' => 'bg-level-insane',
    ];

    $samples = [
        ['Web',       'Easy',   'Contoh judul write-up pertama', ['#SQLi', 'BurpSuite']],
        ['Pwn',       'Hard',   'Contoh judul write-up kedua',   ['#ROP', 'GDB']],
        ['Crypto',    'Insane', 'Contoh judul write-up ketiga',  ['#RSA', 'pwntools']],
        ['Forensics', 'Medium', 'Contoh judul write-up keempat', ['Wireshark']],
        ['Mobile',    'Medium', 'Contoh judul write-up kelima',  ['Frida']],
        ['Web',       'Easy',   'Contoh judul write-up keenam',  ['#XSS', 'BurpSuite']],
    ];
@endphp

<x-public-layout>
  
    

    <section class="border-t border-line">
        <div class="mx-auto max-w-6xl px-6 py-5">
            <form method="GET" action="/">
                <div class="flex gap-3">
                    <input type="text" name="q" value="{{ request('q') }}"
                           placeholder="Cari write-up, mis. SQL injection..."
                           class="flex-1 rounded-lg border border-line bg-surface px-4 py-3 text-sm text-gray-50 placeholder-gray-500 focus:border-gray-200 focus:ring-1 focus:ring-gray-200">
                    <button type="submit"
                            class="rounded-full bg-accent px-6 text-sm font-semibold text-white transition hover:bg-accent-hover">
                        Cari
                    </button>
                </div>

                <div class="mt-4 flex items-center gap-4 text-sm">
                    <a href="/" class="text-gray-500 hover:text-gray-300">Reset filter</a>
                </div>
            </form>

            <div class="mt-6 grid gap-x-8 gap-y-6 md:grid-cols-2">
                <div>
                    <p class="{{ $label }}">Kategori</p>
                    <div class="mt-2 flex flex-wrap gap-2">
                        @foreach ($categories as $item)
                            <span class="{{ $chip }} text-sm">{{ $item }}</span>
                        @endforeach
                    </div>
                </div>

                <div>
                    <p class="{{ $label }}">Level Kesulitan</p>
                    <div class="mt-2 flex flex-wrap gap-2">
                        @foreach ($levels as $item)
                            <span class="{{ $chip }} text-sm">{{ $item }}</span>
                        @endforeach
                    </div>
                </div>
               
            </div>
        </div>
    </section>

    <section class="border-t border-line">
        <div class="mx-auto max-w-6xl px-6 pb-16 pt-12">
            <div class="flex items-baseline justify-between">
                <h2 class="text-xl font-semibold">Write-up</h2>
            </div>

            <div class="mt-5 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($samples as [$category, $level, $title, $tags])
                    <article class="rounded-xl border border-line bg-surface p-5 transition hover:border-gray-600">
                        <div class="flex items-start justify-between">
                            <span class="text-xs uppercase tracking-wider text-gray-400">{{ $category }}</span>
                            <span class="rounded-full {{ $levelClass[$level] }} px-2.5 py-0.5 text-xs font-semibold text-white">{{ $level }}</span>
                        </div>

                        <h3 class="mt-3 text-base font-semibold text-gray-50">{{ $title }}</h3>

                        <div class="mt-4 flex flex-wrap gap-1.5">
                            @foreach ($tags as $tag)
                                <span class="rounded-full border border-line px-2.5 py-0.5 font-mono text-xs text-gray-400">{{ $tag }}</span>
                            @endforeach
                        </div>
                    </article>
                @endforeach
            </div>
        </div>
    </section>
</x-public-layout>