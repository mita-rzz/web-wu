@php
    $label = 'text-xs font-semibold uppercase tracking-wider text-accent';
@endphp

<x-public-layout title="Edit write-up - SolveLog">
    <section class="mx-auto max-w-3xl px-6 pb-16 pt-12">
        <div class="flex items-start justify-between gap-4">
            <div>
                <p class="{{ $label }}">Edit write-up</p>
                <h1 class="mt-3 font-serif text-4xl font-normal tracking-tight">Edit write-up</h1>
            </div>
            <a href="{{ route('writeups.mine') }}"
               class="inline-flex h-btn-h shrink-0 items-center rounded-btn border border-borderMain px-4 text-sm font-medium text-textSec transition hover:border-textSec hover:text-textMain focus:outline-none focus:ring-2 focus:ring-accent">
                Batal
            </a>
        </div>

        @include('writeups.partials.form', ['action' => route('writeups.update', $writeup), 'writeup' => $writeup])
    </section>
</x-public-layout>
