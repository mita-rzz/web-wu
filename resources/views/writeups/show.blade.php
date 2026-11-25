@php
    use App\Enums\WriteupDifficulty;
    use App\Enums\WriteupStatus;

    $badge = match ($writeup->difficulty) {
        WriteupDifficulty::Easy => 'bg-emerald-500/10 text-emerald-400 border-emerald-500/25',
        WriteupDifficulty::Medium => 'bg-amber-500/10 text-amber-400 border-amber-500/25',
        WriteupDifficulty::Hard => 'bg-red-500/10 text-red-400 border-red-500/25',
        WriteupDifficulty::Insane => 'bg-purple-500/10 text-purple-400 border-purple-500/25',
    };

    $tanggal = $writeup->published_at?->locale('id')->translatedFormat('d M Y');
@endphp

<x-public-layout :title="$writeup->title.' - SolveLog'">
    <div class="mx-auto max-w-3xl px-2 pb-28 pt-10 sm:px-6 md:pt-14">

        <div class="mb-7">
            <a href="{{ route('dashboard') }}"
               class="inline-flex items-center gap-1.5 rounded text-sm text-textSec transition hover:text-accent focus:outline-none focus:ring-2 focus:ring-accent">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                </svg>
                Write-Up
            </a>
        </div>

        <article>
            <header class="border-b border-borderMain pb-7">
                <div class="mb-4 flex flex-wrap items-center gap-2.5">
                    <span class="text-xs font-semibold uppercase tracking-wider text-textSec">{{ $writeup->category->name }}</span>
                    <span class="rounded border px-2.5 py-0.5 text-[11px] font-bold uppercase {{ $badge }}">{{ $writeup->difficulty->value }}</span>
                    @if ($writeup->status === WriteupStatus::Draft)
                        <span class="rounded border border-borderMain bg-panel px-2.5 py-0.5 text-[11px] font-semibold text-textSec">Draft</span>
                    @endif
                </div>

                <h1 class="text-[26px] font-bold leading-tight tracking-tight text-textMain sm:text-[34px]">{{ $writeup->title }}</h1>

                <div class="mt-6">
                    <p class="text-sm font-semibold text-textMain">{{ $writeup->author->username }}</p>
                    <p class="mt-0.5 text-xs text-textSec">
                        {{ $tanggal !== null ? 'Dipublikasikan pada '.$tanggal : 'Belum dipublikasikan' }}
                    </p>
                </div>

                @if ($writeup->tools->isNotEmpty() || $writeup->tags->isNotEmpty())
                    <div class="mt-6 flex flex-col gap-5 border-t border-borderMain/60 pt-5 sm:flex-row sm:items-start sm:gap-10">
                        @if ($writeup->tools->isNotEmpty())
                            <div>
                                <span class="mb-2 block text-xs font-semibold uppercase tracking-wider text-textSec">Tools</span>
                                <div class="flex flex-wrap items-center gap-2">
                                    @foreach ($writeup->tools as $tool)
                                        <span class="rounded-lg border border-borderMain bg-panel px-3 py-1 text-xs font-medium text-textMain">{{ $tool->name }}</span>
                                    @endforeach
                                </div>
                            </div>
                        @endif

                        @if ($writeup->tags->isNotEmpty())
                            <div>
                                <span class="mb-2 block text-xs font-semibold uppercase tracking-wider text-textSec">Tags</span>
                                <div class="flex flex-wrap items-center gap-2">
                                    @foreach ($writeup->tags as $tag)
                                        <span class="rounded-full border border-borderMain bg-panel px-3 py-1 text-xs font-medium text-textSec">#{{ $tag->name }}</span>
                                    @endforeach
                                </div>
                            </div>
                        @endif
                    </div>
                @endif
            </header>

            @if ($gambar !== [])
                <div class="mt-8 space-y-4">
                    @foreach ($gambar as $item)
                        <figure class="rounded-xl border border-borderMain bg-panel p-3 sm:p-4">
                            <img src="{{ $item['url'] }}" alt="{{ $item['caption'] !== '' ? $item['caption'] : $item['nama_file'] }}" loading="lazy"
                                 class="h-auto w-full rounded-lg border border-borderMain/70">
                            @php
                                $tampilCaption = $item['caption'] !== '';
                                $tampilNama = $item['nama_file'] !== '' && $item['nama_file'] !== $item['caption'];
                            @endphp
                            @if ($tampilCaption || $tampilNama)
                                <figcaption class="mt-2.5 flex flex-wrap items-center justify-between gap-2 text-xs text-textSec">
                                    @if ($tampilCaption)
                                        <span>{{ $item['caption'] }}</span>
                                    @endif
                                    @if ($tampilNama)
                                        <span class="font-medium text-textMain/80 {{ $tampilCaption ? '' : 'ml-auto' }}">{{ $item['nama_file'] }}</span>
                                    @endif
                                </figcaption>
                            @endif
                        </figure>
                    @endforeach
                </div>
            @endif

            <div class="prose-solvelog mt-8">{!! $bodyHtml !!}</div>
        </article>
    </div>
</x-public-layout>
