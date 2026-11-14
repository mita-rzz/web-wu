@if ($paginator->hasPages())
    @php
        $item = 'inline-flex h-9 min-w-9 items-center justify-center rounded-btn border px-3 text-sm font-medium transition';
    @endphp

    <nav role="navigation" aria-label="Navigasi halaman" class="mt-8 flex items-center justify-center gap-2">
        @if ($paginator->onFirstPage())
            <span class="{{ $item }} cursor-not-allowed border-borderMain text-textSec/50" aria-disabled="true">Sebelumnya</span>
        @else
            <a href="{{ $paginator->previousPageUrl() }}" rel="prev" class="{{ $item }} border-borderMain bg-panel text-textSec hover:border-accent hover:text-accent focus:outline-none focus:ring-2 focus:ring-accent">Sebelumnya</a>
        @endif

        <div class="hidden items-center gap-2 sm:flex">
            @foreach ($elements as $element)
                @if (is_string($element))
                    <span class="{{ $item }} border-transparent text-textSec">{{ $element }}</span>
                @endif

                @if (is_array($element))
                    @foreach ($element as $page => $url)
                        @if ($page == $paginator->currentPage())
                            <span aria-current="page" class="{{ $item }} border-accent bg-accent/10 text-accent">{{ $page }}</span>
                        @else
                            <a href="{{ $url }}" aria-label="Ke halaman {{ $page }}" class="{{ $item }} border-borderMain bg-panel text-textSec hover:border-accent hover:text-accent focus:outline-none focus:ring-2 focus:ring-accent">{{ $page }}</a>
                        @endif
                    @endforeach
                @endif
            @endforeach
        </div>

        <span class="text-sm text-textSec sm:hidden">{{ $paginator->currentPage() }} / {{ $paginator->lastPage() }}</span>

        @if ($paginator->hasMorePages())
            <a href="{{ $paginator->nextPageUrl() }}" rel="next" class="{{ $item }} border-borderMain bg-panel text-textSec hover:border-accent hover:text-accent focus:outline-none focus:ring-2 focus:ring-accent">Berikutnya</a>
        @else
            <span class="{{ $item }} cursor-not-allowed border-borderMain text-textSec/50" aria-disabled="true">Berikutnya</span>
        @endif
    </nav>
@endif
