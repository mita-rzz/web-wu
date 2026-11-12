@props(['value'])

<label {{ $attributes->merge(['class' => 'block text-xs font-semibold text-gray-50']) }}>
    {{ $value ?? $slot }}
</label>
