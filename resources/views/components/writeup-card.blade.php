@props(['writeup', 'excerpt' => ''])

@php
    use App\Enums\WriteupDifficulty;
    use App\Enums\WriteupStatus;

    $warna = match ($writeup->difficulty) {
        WriteupDifficulty::Easy => [
            'card' => 'focus:ring-emerald-500 hover:border-emerald-500 hover:shadow-[0_0_20px_rgba(16,185,129,0.2)]',
            'title' => 'group-hover:text-emerald-400',
            'badge' => 'bg-emerald-500/10 text-emerald-400 border-emerald-500/20',
        ],
        WriteupDifficulty::Medium => [
            'card' => 'focus:ring-amber-500 hover:border-amber-500 hover:shadow-[0_0_20px_rgba(245,158,11,0.2)]',
            'title' => 'group-hover:text-amber-400',
            'badge' => 'bg-amber-500/10 text-amber-400 border-amber-500/20',
        ],
        WriteupDifficulty::Hard => [
            'card' => 'focus:ring-red-500 hover:border-red-500 hover:shadow-[0_0_20px_rgba(239,68,68,0.2)]',
            'title' => 'group-hover:text-red-400',
            'badge' => 'bg-red-500/10 text-red-400 border-red-500/20',
        ],
        WriteupDifficulty::Insane => [
            'card' => 'focus:ring-purple-500 hover:border-purple-500 hover:shadow-[0_0_20px_rgba(168,85,247,0.2)]',
            'title' => 'group-hover:text-purple-400',
            'badge' => 'bg-purple-500/10 text-purple-400 border-purple-500/20',
        ],
    };

    $tanggal = ($writeup->published_at ?? $writeup->created_at)?->locale('id')->translatedFormat('d M Y');
@endphp

<a href="{{ route('writeups.show', $writeup) }}"
   class="group z-10 flex flex-col rounded-xl border border-borderMain bg-panel p-5 outline-none transition-all duration-300 ease-[cubic-bezier(0.22,1,0.36,1)] hover:z-20 hover:scale-[1.02] active:scale-[0.98] motion-reduce:transition-none motion-reduce:hover:scale-100 motion-reduce:active:scale-100 focus:ring-2 {{ $warna['card'] }}">
    <div class="mb-3 flex items-start justify-between">
        <span class="text-xs font-semibold uppercase tracking-wider text-textSec">{{ $writeup->category->name }}</span>
        <div class="flex items-center gap-1.5">
            @if ($writeup->status === WriteupStatus::Draft)
                <span class="rounded border border-borderMain bg-slate-500/10 px-2 py-0.5 text-[10px] font-bold uppercase text-textSec">Draft</span>
            @endif
            <span class="rounded border px-2 py-0.5 text-[10px] font-bold uppercase {{ $warna['badge'] }}">{{ $writeup->difficulty->value }}</span>
        </div>
    </div>

    <h3 class="mb-2 line-clamp-2 text-lg font-semibold leading-snug text-textMain transition {{ $warna['title'] }}">
        {{ $writeup->title }}
    </h3>

    <p class="mb-4 line-clamp-2 flex-1 text-sm leading-relaxed text-textSec">
        {{ $excerpt }}
    </p>

    @if ($writeup->tags->isNotEmpty())
        <div class="mb-5 flex flex-wrap gap-2">
            @foreach ($writeup->tags as $tag)
                <span class="text-xs font-medium text-textSec">#{{ $tag->name }}</span>
            @endforeach
        </div>
    @endif

    <div class="mt-auto flex items-center justify-between border-t border-borderMain pt-4 text-xs font-medium text-textSec">
        <span class="text-textMain">{{ $writeup->author->username }}</span>
        <span>{{ $tanggal }}</span>
    </div>
</a>
