@php
    // Tab awal: "Pengaturan" bila ada error/status flash; selain itu "Ringkasan".
    // location.hash === '#pengaturan' juga membuka Pengaturan (dicek di init Alpine).
    $tabAwal = ($errors->any() || session('status')) ? 'pengaturan' : 'ringkasan';

    $inisial = mb_strtoupper(mb_substr($user->display_name, 0, 1));
    $bergabung = $user->created_at?->locale('id')->translatedFormat('d M Y');
    $admin = $user->role === \App\Enums\UserRole::Admin;
    $banned = $user->status === \App\Enums\UserStatus::Banned;

    $tab = 'relative -mb-px rounded-t-lg border-b-2 px-4 py-2.5 text-sm font-medium transition focus:outline-none focus-visible:ring-2 focus-visible:ring-accent';
@endphp

<x-public-layout title="Profil - SolveLog">
    <div class="mx-auto max-w-3xl px-2 pb-28 pt-10 sm:px-6 md:pt-14"
         x-data="{
             tab: @js($tabAwal),
             init() { if (window.location.hash === '#pengaturan') this.tab = 'pengaturan'; },
             // Panah kiri/kanan, Home, End memindah tab (pola WAI-ARIA tabs).
             geser(event) {
                 const urutan = ['ringkasan', 'pengaturan'];
                 let i = urutan.indexOf(this.tab);
                 if (event.key === 'ArrowRight') i = (i + 1) % urutan.length;
                 else if (event.key === 'ArrowLeft') i = (i - 1 + urutan.length) % urutan.length;
                 else if (event.key === 'Home') i = 0;
                 else if (event.key === 'End') i = urutan.length - 1;
                 else return;
                 event.preventDefault();
                 this.tab = urutan[i];
                 this.$nextTick(() => this.$refs['tab-' + this.tab].focus());
             },
         }">

        {{-- Tautan kembali --}}
        <div class="mb-7">
            <a href="{{ route('dashboard') }}"
               class="inline-flex items-center gap-1.5 rounded text-sm text-textSec transition hover:text-accent focus:outline-none focus:ring-2 focus:ring-accent">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                </svg>
                Write-Up
            </a>
        </div>

        {{-- Kartu header profil --}}
        <section class="flex items-start gap-4 rounded-xl border border-borderMain bg-panel p-5 sm:gap-5 sm:p-6" aria-label="Profil">
            <div class="flex h-16 w-16 shrink-0 items-center justify-center rounded-full border border-accent/30 bg-accent/10 text-2xl font-bold text-accent sm:h-20 sm:w-20 sm:text-3xl" aria-hidden="true">{{ $inisial }}</div>

            <div class="min-w-0 flex-1">
                <h1 class="break-words text-[22px] font-bold leading-tight tracking-tight text-textMain sm:text-[28px]">{{ $user->display_name }}</h1>

                <div class="mt-1.5 flex flex-wrap items-center gap-2.5">
                    <span class="break-all text-sm text-textSec">&#64;{{ $user->username }}</span>
                    <span class="rounded border px-2 py-0.5 text-[11px] font-bold uppercase {{ $admin ? 'border-accent/25 bg-accent/10 text-accent' : 'border-borderMain bg-bgMain text-textSec' }}">{{ $user->role->value }}</span>
                    @if ($banned)
                        <span class="rounded border border-red-500/25 bg-red-500/10 px-2 py-0.5 text-[11px] font-bold uppercase text-red-400">Banned</span>
                    @endif
                </div>

                @if (filled($user->bio))
                    <p class="mt-4 whitespace-pre-line break-words text-sm leading-6 text-textSec">{{ $user->bio }}</p>
                @else
                    <p class="mt-4 text-sm text-textSec">Belum ada bio.</p>
                @endif

                <p class="mt-4 text-xs text-textSec">Bergabung sejak {{ $bergabung }}</p>
            </div>
        </section>

        {{-- Tab --}}
        <div class="mt-8 flex gap-1 border-b border-borderMain" role="tablist" aria-label="Bagian profil" @keydown="geser($event)">
            <button type="button" role="tab" id="tab-ringkasan" x-ref="tab-ringkasan"
                    aria-controls="panel-ringkasan"
                    aria-selected="{{ $tabAwal === 'ringkasan' ? 'true' : 'false' }}"
                    :aria-selected="(tab === 'ringkasan').toString()"
                    :tabindex="tab === 'ringkasan' ? 0 : -1"
                    @click="tab = 'ringkasan'"
                    :class="tab === 'ringkasan' ? 'border-accent text-accent' : 'border-transparent text-textSec hover:text-textMain'"
                    class="{{ $tab }} {{ $tabAwal === 'ringkasan' ? 'border-accent text-accent' : 'border-transparent text-textSec hover:text-textMain' }}">
                Ringkasan
            </button>
            <button type="button" role="tab" id="tab-pengaturan" x-ref="tab-pengaturan"
                    aria-controls="panel-pengaturan"
                    aria-selected="{{ $tabAwal === 'pengaturan' ? 'true' : 'false' }}"
                    :aria-selected="(tab === 'pengaturan').toString()"
                    :tabindex="tab === 'pengaturan' ? 0 : -1"
                    @click="tab = 'pengaturan'"
                    :class="tab === 'pengaturan' ? 'border-accent text-accent' : 'border-transparent text-textSec hover:text-textMain'"
                    class="{{ $tab }} {{ $tabAwal === 'pengaturan' ? 'border-accent text-accent' : 'border-transparent text-textSec hover:text-textMain' }}">
                Pengaturan
            </button>
        </div>

        <div id="panel-ringkasan" role="tabpanel" aria-labelledby="tab-ringkasan" tabindex="0"
             x-show="tab === 'ringkasan'" @if ($tabAwal !== 'ringkasan') style="display: none" @endif
             class="mt-6 rounded focus:outline-none focus-visible:ring-2 focus-visible:ring-accent">
            @include('profile.partials.ringkasan')
        </div>

        <div id="panel-pengaturan" role="tabpanel" aria-labelledby="tab-pengaturan" tabindex="0"
             x-show="tab === 'pengaturan'" @if ($tabAwal !== 'pengaturan') style="display: none" @endif
             class="mt-6 space-y-6 rounded focus:outline-none focus-visible:ring-2 focus-visible:ring-accent">
            <section class="rounded-xl border border-borderMain bg-panel p-5 sm:p-6">
                @include('profile.partials.update-profile-information-form')
            </section>

            <section class="rounded-xl border border-borderMain bg-panel p-5 sm:p-6">
                @include('profile.partials.update-password-form')
            </section>

            <section class="rounded-xl border border-red-500/30 bg-panel p-5 sm:p-6">
                @include('profile.partials.delete-user-form')
            </section>
        </div>
    </div>
</x-public-layout>
