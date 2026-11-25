@props(['writeup'])

@php
    use App\Enums\WriteupDifficulty;
    use App\Enums\WriteupStatus;

    $badge = match ($writeup->difficulty) {
        WriteupDifficulty::Easy => 'bg-emerald-500/10 text-emerald-400 border-emerald-500/25',
        WriteupDifficulty::Medium => 'bg-amber-500/10 text-amber-400 border-amber-500/25',
        WriteupDifficulty::Hard => 'bg-red-500/10 text-red-400 border-red-500/25',
        WriteupDifficulty::Insane => 'bg-purple-500/10 text-purple-400 border-purple-500/25',
    };

    $tanggal = ($writeup->published_at ?? $writeup->created_at)?->locale('id')->translatedFormat('d M Y');

    $itemMenu = 'flex min-h-[44px] w-full items-center gap-2.5 px-3.5 py-2.5 text-left text-sm font-medium transition focus:outline-none focus:ring-2 focus:ring-inset focus:ring-accent';
@endphp

<article role="listitem"
         x-data="{ open: false }"
         :class="open ? 'z-30' : ''"
         @keydown.escape.window="if (open) { open = false; $refs.tombol.focus() }"
         class="relative -mx-4 flex items-center justify-between gap-4 rounded-xl px-4 py-6 transition-colors hover:bg-panel/60">
    <div class="min-w-0 flex-1">
        <div class="mb-2 flex flex-wrap items-center gap-2.5">
            <span class="text-xs font-semibold uppercase tracking-wider text-textSec">{{ $writeup->category->name }}</span>
            <span class="rounded border px-2 py-0.5 text-[11px] font-bold uppercase {{ $badge }}">{{ $writeup->difficulty->value }}</span>
            @if ($writeup->status === WriteupStatus::Draft)
                <span class="rounded border border-borderMain bg-slate-500/10 px-2 py-0.5 text-[11px] font-bold uppercase text-textSec">Draft</span>
            @endif
            <span class="text-xs text-textSec">· {{ $tanggal }}</span>
        </div>

        <h2 class="break-words text-base font-semibold leading-snug text-textMain md:text-lg">
            <a href="{{ route('writeups.show', $writeup) }}"
               class="rounded transition hover:text-accent focus:outline-none focus:ring-2 focus:ring-accent">{{ $writeup->title }}</a>
        </h2>

        @if ($writeup->tags->isNotEmpty())
            <div class="mt-2.5 flex flex-wrap items-center gap-2.5">
                @foreach ($writeup->tags as $tag)
                    <span class="text-xs text-textSec">#{{ $tag->name }}</span>
                @endforeach
            </div>
        @endif
    </div>

    <div class="relative shrink-0 self-center" @click.outside="open = false">
        <button type="button" x-ref="tombol"
                @click="open = !open; if (open) $nextTick(() => $refs.menu.querySelector('[role=menuitem]')?.focus())"
                aria-label="Opsi untuk {{ $writeup->title }}"
                aria-haspopup="true"
                aria-expanded="false"
                :aria-expanded="open.toString()"
                class="flex h-11 w-11 items-center justify-center rounded-lg border border-transparent text-textSec transition hover:border-borderMain hover:bg-panel hover:text-textMain focus:outline-none focus:ring-2 focus:ring-accent">
            <svg class="pointer-events-none h-5 w-5" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true">
                <path d="M10 6a2 2 0 110-4 2 2 0 010 4zM10 12a2 2 0 110-4 2 2 0 010 4zM10 18a2 2 0 110-4 2 2 0 010 4z" />
            </svg>
        </button>

        <div x-ref="menu" x-show="open" style="display: none" role="menu"
             class="absolute right-0 z-40 mt-1.5 w-44 overflow-hidden rounded-xl border border-borderMain bg-panel py-1.5 shadow-xl">
            <a href="{{ route('writeups.edit', $writeup) }}" role="menuitem"
               class="{{ $itemMenu }} text-textMain hover:bg-bgMain hover:text-accent">
                <svg class="h-4 w-4 text-textSec" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                </svg>
                Update
            </a>
            <button type="button" role="menuitem"
                    @click="open = false; $dispatch('hapus-writeup', { title: @js($writeup->title), action: @js(route('writeups.destroy', $writeup)), opener: $refs.tombol })"
                    class="{{ $itemMenu }} text-red-400 hover:bg-red-950/30">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                </svg>
                Delete
            </button>
        </div>
    </div>
</article>
