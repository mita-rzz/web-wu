@php
    use App\Enums\WriteupDifficulty;
    use Illuminate\Support\Facades\Route;

    $warnaLevel = [
        'easy' => 'bg-emerald-500/10 text-emerald-400 border-emerald-500/25',
        'medium' => 'bg-amber-500/10 text-amber-400 border-amber-500/25',
        'hard' => 'bg-red-500/10 text-red-400 border-red-500/25',
        'insane' => 'bg-purple-500/10 text-purple-400 border-purple-500/25',
    ];

    $punyaHalamanDetail = Route::has('writeups.show');
@endphp

<div class="space-y-6">
    <div class="grid grid-cols-2 gap-4">
        <div class="rounded-xl border border-borderMain bg-panel p-5">
            <p class="text-xs font-semibold uppercase tracking-wider text-textSec">Terbit</p>
            <p class="mt-2 text-3xl font-bold text-textMain">{{ $jumlahTerbit }}</p>
        </div>
        <div class="rounded-xl border border-borderMain bg-panel p-5">
            <p class="text-xs font-semibold uppercase tracking-wider text-textSec">Draft</p>
            <p class="mt-2 text-3xl font-bold text-textMain">{{ $jumlahDraft }}</p>
        </div>
    </div>

    <div class="flex flex-wrap gap-2.5" aria-label="Jumlah write-up terbit per level">
        @foreach (WriteupDifficulty::cases() as $level)
            <span class="inline-flex items-center gap-2 rounded border px-2.5 py-1 text-[11px] font-bold uppercase {{ $warnaLevel[$level->value] }}">
                {{ $level->value }}
                <span class="text-sm">{{ $perLevel[$level->value] }}</span>
            </span>
        @endforeach
    </div>

    <section>
        <div class="mb-3 flex items-center justify-between">
            <h2 class="text-lg font-semibold text-textMain sm:text-xl">Write-up terbaru</h2>
            <a href="{{ route('writeups.mine') }}"
               class="rounded px-1 text-sm font-medium text-accent hover:underline focus:outline-none focus:ring-2 focus:ring-accent">Lihat semua</a>
        </div>

        @if ($terbaru->isNotEmpty())
            <ul class="divide-y divide-borderMain rounded-xl border border-borderMain bg-panel">
                @foreach ($terbaru as $w)
                    <li class="px-4 py-4 sm:px-5">
                        <div class="mb-1.5 flex flex-wrap items-center gap-2.5">
                            <span class="text-xs font-semibold uppercase tracking-wider text-textSec">{{ $w->category->name }}</span>
                            <span class="rounded border px-2 py-0.5 text-[11px] font-bold uppercase {{ $warnaLevel[$w->difficulty->value] }}">{{ $w->difficulty->value }}</span>
                            <span class="text-xs text-textSec">· {{ $w->published_at?->locale('id')->translatedFormat('d M Y') }}</span>
                        </div>

                        <p class="break-words text-base font-semibold leading-snug text-textMain">
                            @if ($punyaHalamanDetail)
                                <a href="{{ route('writeups.show', $w) }}"
                                   class="rounded transition hover:text-accent focus:outline-none focus:ring-2 focus:ring-accent">{{ $w->title }}</a>
                            @else
                                {{ $w->title }}
                            @endif
                        </p>
                    </li>
                @endforeach
            </ul>
        @else
            <div class="flex flex-col items-center rounded-xl border border-borderMain bg-panel px-6 py-12 text-center">
                <p class="mb-1 text-base font-medium text-textMain">Belum ada write-up yang kamu publikasikan.</p>
                <p class="mb-5 max-w-md text-sm text-textSec">Mulai dokumentasikan penyelesaian tantangan CTF atau temuan bug bounty pertamamu.</p>
                <a href="{{ route('writeups.create') }}"
                   class="inline-flex h-btn-h items-center rounded-btn bg-accent px-6 font-semibold text-bgMain transition hover:bg-emerald-500 focus:outline-none focus:ring-2 focus:ring-accent focus:ring-offset-2 focus:ring-offset-bgMain">
                    Buat Write-Up
                </a>
            </div>
        @endif
    </section>
</div>
