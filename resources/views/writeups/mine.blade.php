@php
    // Modal hapus dibuka otomatis bila password salah, untuk write-up yang sama.
    $slugGagal = session('hapus_slug');
    $gagal = $errors->writeupDeletion->isNotEmpty() && is_string($slugGagal)
        ? $writeups->firstWhere('slug', $slugGagal)
        : null;

    $modalAwal = [
        'show' => $gagal !== null,
        'title' => $gagal?->title ?? '',
        'action' => $gagal !== null ? route('writeups.destroy', $gagal) : '',
    ];
@endphp

<x-public-layout title="Postingan Saya - SolveLog">
    <div class="mx-auto max-w-3xl px-2 sm:px-6"
         x-data="{
             modal: @js($modalAwal),
             errorAction: @js($modalAwal['show'] ? $modalAwal['action'] : ''),
             opener: null,
             buka(detail) {
                 this.opener = detail.opener ?? null;
                 this.modal = { show: true, title: detail.title, action: detail.action };
                 this.$nextTick(() => this.$refs.password.focus());
             },
             tutup() {
                 if (!this.modal.show) return;
                 this.modal.show = false;
                 this.$refs.password.value = '';
                 this.opener?.focus();
             },
             // Focus trap dasar: Tab berputar di dalam dialog.
             jebak(event) {
                 const fokus = [...this.$refs.dialog.querySelectorAll('input, button, a[href]')].filter((el) => !el.disabled);
                 if (fokus.length === 0) return;
                 const awal = fokus[0];
                 const akhir = fokus[fokus.length - 1];
                 if (event.shiftKey && document.activeElement === awal) {
                     event.preventDefault();
                     akhir.focus();
                 } else if (!event.shiftKey && document.activeElement === akhir) {
                     event.preventDefault();
                     awal.focus();
                 }
             },
         }"
         x-init="if (modal.show) $nextTick(() => $refs.password.focus())"
         @hapus-writeup.window="buka($event.detail)">

        <section class="mt-10 border-b border-borderMain pb-7 md:mt-14">
            <a href="{{ route('dashboard') }}"
               class="mb-5 inline-flex items-center gap-1.5 rounded text-sm text-textSec transition hover:text-accent focus:outline-none focus:ring-2 focus:ring-accent">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                </svg>
                Write-Up
            </a>

            <h1 class="text-[28px] font-bold leading-tight text-textMain md:text-[36px]">Postingan Saya</h1>
        </section>

        <div class="mt-2 pb-32">
            @if ($writeups->isNotEmpty())
                <div role="list" class="divide-y divide-borderMain border-b border-borderMain">
                    @foreach ($writeups as $writeup)
                        <x-writeup-row :writeup="$writeup" />
                    @endforeach
                </div>

                {{ $writeups->links('pagination.solvelog') }}
            @else
                {{-- Empty state --}}
                <section class="flex flex-col items-center justify-center py-24 text-center">
                    <div class="mb-4 flex h-14 w-14 items-center justify-center rounded-xl border border-borderMain bg-panel text-textSec">
                        <svg class="h-7 w-7" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                        </svg>
                    </div>
                    <p class="mb-1 text-base font-medium text-textMain">Belum ada write-up yang kamu publikasikan.</p>
                    <p class="mb-6 max-w-md text-sm text-textSec">
                        Mulai dokumentasikan penyelesaian tantangan CTF atau temuan bug bounty pertamamu sekarang.
                    </p>
                    <a href="{{ route('writeups.create') }}"
                       class="inline-flex h-btn-h items-center rounded-btn bg-accent px-6 font-semibold text-bgMain transition hover:bg-emerald-500 focus:outline-none focus:ring-2 focus:ring-accent focus:ring-offset-2 focus:ring-offset-bgMain">
                        Buat Write-Up Pertama
                    </a>
                </section>
            @endif
        </div>

        {{-- Satu modal hapus untuk seluruh halaman. Sengaja bukan x-modal Breeze (latar putih). --}}
        <div x-show="modal.show" style="display: none"
             @keydown.escape.window="tutup()"
             @keydown.tab="jebak($event)"
             @click.self="tutup()"
             role="dialog" aria-modal="true" aria-labelledby="hapus-judul"
             class="fixed inset-0 z-50 flex items-center justify-center bg-black/70 p-4 backdrop-blur-sm">
            <div x-ref="dialog" class="w-full max-w-md rounded-xl border border-borderMain bg-panel p-6 shadow-2xl">
                <form method="POST" :action="modal.action" action="{{ $modalAwal['action'] }}">
                    @csrf
                    @method('DELETE')

                    <h2 id="hapus-judul" class="text-lg font-semibold text-textMain">Hapus Write-Up?</h2>
                    <p class="mt-2 text-sm leading-relaxed text-textSec">
                        Write-up <span class="font-medium text-textMain" x-text="'&quot;' + modal.title + '&quot;'">{{ $gagal ? '"'.$gagal->title.'"' : '' }}</span>
                        akan dihapus dari daftar Anda. Masukkan password untuk melanjutkan.
                    </p>

                    <div class="mt-5">
                        <label for="hapus-password" class="sr-only">Password</label>
                        <input id="hapus-password" x-ref="password" type="password" name="password"
                               autocomplete="current-password" autofocus placeholder="Password"
                               class="block h-btn-h w-full rounded-btn border border-borderMain bg-bgMain px-3.5 text-sm text-textMain placeholder-textSec focus:border-accent focus:outline-none focus:ring-1 focus:ring-accent">
                        @if ($errors->writeupDeletion->isNotEmpty())
                            <ul x-show="modal.action === errorAction" role="alert" class="mt-2 space-y-1 text-sm text-red-400">
                                @foreach ($errors->writeupDeletion->get('password') as $pesan)
                                    <li>{{ $pesan }}</li>
                                @endforeach
                            </ul>
                        @endif
                    </div>

                    <div class="mt-6 flex items-center justify-end gap-3">
                        <button type="button" @click="tutup()"
                                class="h-btn-h rounded-btn border border-borderMain px-4 text-sm font-medium text-textSec transition hover:text-textMain focus:outline-none focus:ring-2 focus:ring-accent">
                            Batal
                        </button>
                        <button type="submit"
                                class="h-btn-h rounded-btn bg-red-500 px-5 text-sm font-semibold text-white transition hover:bg-red-600 focus:outline-none focus:ring-2 focus:ring-red-400">
                            Hapus
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-public-layout>
