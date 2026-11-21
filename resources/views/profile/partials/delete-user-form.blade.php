{{-- Zona berbahaya: hapus akun. $punyaKonten dikirim oleh ProfileController::edit(). --}}
@php
    $bagHapus = $errors->userDeletion;
@endphp

<div x-data="{ buka: @js($bagHapus->isNotEmpty() && ! $punyaKonten) }">
    <header>
        <h2 class="text-lg font-semibold text-red-400">Zona berbahaya</h2>
        <p class="mt-1 text-sm text-textSec">Menghapus akun bersifat permanen dan tidak bisa dibatalkan.</p>
    </header>

    @if ($punyaKonten)
        <p id="alasan-hapus" class="mt-4 text-sm text-textSec">Akun yang masih memiliki write-up atau gambar tidak bisa dihapus. Hubungi admin.</p>

        <div class="mt-4">
            <button type="button" disabled aria-describedby="alasan-hapus"
                    class="h-btn-h cursor-not-allowed rounded-btn bg-red-500 px-5 text-sm font-semibold text-white opacity-40">
                Hapus akun
            </button>
        </div>
    @else
        <div class="mt-4">
            <button type="button" @click="buka = true"
                    class="h-btn-h rounded-btn bg-red-500 px-5 text-sm font-semibold text-white transition hover:bg-red-600 focus:outline-none focus:ring-2 focus:ring-red-400">
                Hapus akun
            </button>
        </div>

        <div x-show="buka" style="{{ $bagHapus->isNotEmpty() ? '' : 'display: none' }}"
             @keydown.escape.window="buka = false"
             @click.self="buka = false"
             x-effect="if (buka) $nextTick(() => $refs.password.focus())"
             role="dialog" aria-modal="true" aria-labelledby="hapus-akun-judul"
             class="fixed inset-0 z-50 flex items-center justify-center bg-black/70 p-4 backdrop-blur-sm">
            <div class="w-full max-w-md rounded-xl border border-borderMain bg-panel p-6 shadow-2xl">
                <form method="POST" action="{{ route('profile.destroy') }}">
                    @csrf
                    @method('DELETE')

                    <h2 id="hapus-akun-judul" class="text-lg font-semibold text-textMain">Hapus akun?</h2>
                    <p class="mt-2 text-sm leading-relaxed text-textSec">
                        Akunmu akan dihapus secara permanen. Masukkan password untuk melanjutkan.
                    </p>

                    <div class="mt-5">
                        <label for="hapus-akun-password" class="sr-only">Password</label>
                        <input id="hapus-akun-password" x-ref="password" type="password" name="password"
                               autocomplete="current-password" placeholder="Password"
                               class="block h-btn-h w-full rounded-btn border border-borderMain bg-bgMain px-3.5 text-sm text-textMain placeholder-textSec focus:border-accent focus:outline-none focus:ring-1 focus:ring-accent">
                        <x-input-error :messages="$bagHapus->get('password')" class="mt-2 !text-red-400" />
                    </div>

                    <div class="mt-6 flex items-center justify-end gap-3">
                        <button type="button" @click="buka = false"
                                class="h-btn-h rounded-btn border border-borderMain px-4 text-sm font-medium text-textSec transition hover:text-textMain focus:outline-none focus:ring-2 focus:ring-accent">
                            Batal
                        </button>
                        <button type="submit"
                                class="h-btn-h rounded-btn bg-red-500 px-5 text-sm font-semibold text-white transition hover:bg-red-600 focus:outline-none focus:ring-2 focus:ring-red-400">
                            Hapus akun
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</div>
