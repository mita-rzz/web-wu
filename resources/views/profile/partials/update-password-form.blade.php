@php
    $errorClass = 'mt-1.5 !text-red-400';
    $bag = $errors->updatePassword;
@endphp

<form method="POST" action="{{ route('password.update') }}" class="space-y-6">
    @csrf
    @method('PUT')

    <header>
        <h2 class="text-lg font-semibold text-textMain">Ganti password</h2>
        <p class="mt-1 text-sm text-textSec">Gunakan password yang panjang dan acak agar akunmu tetap aman.</p>
    </header>

    <div>
        <x-input-label for="update_password_current_password" value="Password saat ini" />
        <x-text-input id="update_password_current_password" class="mt-1.5" type="password" name="current_password" autocomplete="current-password" />
        <x-input-error :messages="$bag->get('current_password')" :class="$errorClass" />
    </div>

    <div>
        <x-input-label for="update_password_password" value="Password baru" />
        <x-text-input id="update_password_password" class="mt-1.5" type="password" name="password" autocomplete="new-password" />
        <x-input-error :messages="$bag->get('password')" :class="$errorClass" />
    </div>

    <div>
        <x-input-label for="update_password_password_confirmation" value="Konfirmasi password baru" />
        <x-text-input id="update_password_password_confirmation" class="mt-1.5" type="password" name="password_confirmation" autocomplete="new-password" />
        <x-input-error :messages="$bag->get('password_confirmation')" :class="$errorClass" />
    </div>

    <div>
        <button type="submit"
                class="h-btn-h rounded-btn bg-accent px-5 text-sm font-semibold text-bgMain transition hover:bg-emerald-500 focus:outline-none focus:ring-2 focus:ring-accent focus:ring-offset-2 focus:ring-offset-bgMain">
            Ganti password
        </button>
    </div>
</form>
