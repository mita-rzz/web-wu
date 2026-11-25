<x-guest-layout :glow="true">
    <x-slot name="heading">
        <h1 class="text-lg font-semibold text-gray-50">Create an account</h1>
        <p class="mt-1 text-xs text-gray-400">Enter your details below to create your account</p>
    </x-slot>

    <form method="POST" action="{{ route('register') }}">
        @csrf

        <div>
            <x-input-label for="username" :value="__('Username')" />
            <x-text-input id="username" class="mt-1.5" type="text" name="username" :value="old('username')" placeholder="Username" required autofocus autocomplete="username" />
            <x-input-error :messages="$errors->get('username')" class="mt-1.5" />
        </div>

        <div class="mt-4">
            <x-input-label for="email" :value="__('Email address')" />
            <x-text-input id="email" class="mt-1.5" type="email" name="email" :value="old('email')" placeholder="email@example.com" required autocomplete="email" />
            <x-input-error :messages="$errors->get('email')" class="mt-1.5" />
        </div>

        <div class="mt-4">
            <x-input-label for="password" :value="__('Password')" />
            <x-text-input id="password" class="mt-1.5" type="password" name="password" placeholder="Password" required autocomplete="new-password" />
            <x-input-error :messages="$errors->get('password')" class="mt-1.5" />
        </div>

        <div class="mt-4">
            <x-input-label for="password_confirmation" :value="__('Confirm password')" />
            <x-text-input id="password_confirmation" class="mt-1.5" type="password" name="password_confirmation" placeholder="Confirm password" required autocomplete="new-password" />
            <x-input-error :messages="$errors->get('password_confirmation')" class="mt-1.5" />
        </div>

        <x-primary-button class="mt-6 w-full">
            {{ __('Create account') }}
        </x-primary-button>

        <p class="mt-6 text-center text-xs text-gray-400">
            {{ __('Already have an account?') }}
            <a href="{{ route('login') }}" class="underline text-gray-300 hover:text-white">{{ __('Log in') }}</a>
        </p>
    </form>
</x-guest-layout>