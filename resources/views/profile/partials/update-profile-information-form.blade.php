@php
    $errorClass = 'mt-1.5 !text-red-400';
    $emailAwal = $user->email;
    $emailSekarang = old('email', $user->email);
@endphp

<form method="POST" action="{{ route('profile.update') }}" class="space-y-6"
      x-data="{
          emailAwal: @js($emailAwal),
          email: @js($emailSekarang),
          bio: @js(old('bio', $user->bio ?? '')),
          // Password saat ini hanya diminta bila email diganti.
          get emailBerubah() { return this.email.trim().toLowerCase() !== this.emailAwal; },
      }">
    @csrf
    @method('PATCH')

    <header>
        <h2 class="text-lg font-semibold text-textMain">Informasi profil</h2>
        <p class="mt-1 text-sm text-textSec">Ubah username, nama tampilan, bio, dan email akunmu.</p>
    </header>

    <div>
        <x-input-label for="username" value="Username" />
        <x-text-input id="username" class="mt-1.5" type="text" name="username" :value="old('username', $user->username)"
                      minlength="3" maxlength="50" pattern="[A-Za-z0-9_.\-]+" required autocomplete="username" />
        <p class="mt-1.5 text-xs text-textSec">
            Huruf, angka, _ . - ; 3-50 karakter. Mengubah username mengubah nama penulis di semua write-up.
        </p>
        <x-input-error :messages="$errors->get('username')" :class="$errorClass" />
    </div>

    <div>
        <x-input-label for="display_name" value="Nama tampilan" />
        <x-text-input id="display_name" class="mt-1.5" type="text" name="display_name" :value="old('display_name', $user->display_name)"
                      maxlength="100" required autocomplete="name" />
        <x-input-error :messages="$errors->get('display_name')" :class="$errorClass" />
    </div>

    <div>
        <div class="flex items-center justify-between">
            <x-input-label for="bio" value="Bio" />
            <span class="text-xs text-textSec" x-text="bio.length + '/500'">0/500</span>
        </div>
        <textarea id="bio" name="bio" rows="4" maxlength="500" x-model="bio"
                  class="mt-1.5 block w-full rounded-lg border-line bg-page text-sm text-gray-50 placeholder-gray-500 shadow-none focus:border-gray-200 focus:ring-1 focus:ring-gray-200"
                  placeholder="Ceritakan singkat tentang dirimu">{{ old('bio', $user->bio) }}</textarea>
        <x-input-error :messages="$errors->get('bio')" :class="$errorClass" />
    </div>

    <div>
        <x-input-label for="email" value="Email" />
        <x-text-input id="email" class="mt-1.5" type="email" name="email" x-model="email" :value="$emailSekarang"
                      maxlength="255" required autocomplete="email" />
        <x-input-error :messages="$errors->get('email')" :class="$errorClass" />
    </div>

    {{-- Muncul hanya bila email diubah; dinonaktifkan (tidak ikut terkirim) saat tersembunyi. --}}
    <div x-show="emailBerubah" @if ($emailSekarang === $emailAwal) style="display: none" @endif>
        <x-input-label for="current_password" value="Password saat ini" />
        <x-text-input id="current_password" class="mt-1.5" type="password" name="current_password"
                      x-bind:disabled="!emailBerubah" autocomplete="current-password" />
        <p class="mt-1.5 text-xs text-textSec">Diperlukan untuk mengganti email. Email yang berubah perlu diverifikasi ulang.</p>
        <x-input-error :messages="$errors->get('current_password')" :class="$errorClass" />
    </div>

    <div>
        <button type="submit"
                class="h-btn-h rounded-btn bg-accent px-5 text-sm font-semibold text-bgMain transition hover:bg-emerald-500 focus:outline-none focus:ring-2 focus:ring-accent focus:ring-offset-2 focus:ring-offset-bgMain">
            Simpan perubahan
        </button>
    </div>
</form>
