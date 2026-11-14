@php
    $label = 'text-xs font-semibold uppercase tracking-wider text-accent';
@endphp

<x-public-layout>
    <section class="mx-auto max-w-3xl px-6 pb-16 pt-12">
        <p class="{{ $label }}">Write-up baru</p>
        <h1 class="mt-3 font-serif text-4xl font-normal tracking-tight">Tulis write-up</h1>
        <p class="mt-3 max-w-xl text-sm leading-relaxed text-gray-400">
            Isi dengan Markdown. Simpan sebagai draft dulu bila belum selesai, publikasikan kapan siap.
        </p>

        @include('writeups.partials.form', ['action' => route('writeups.store'), 'writeup' => null])
    </section>
</x-public-layout>
