@props(['disabled' => false])

@php
    $clases = 'block w-full rounded-lg border-slate-300 text-sm text-slate-900 shadow-sm placeholder:text-slate-400 focus:border-indigo-500 focus:ring-indigo-500 disabled:bg-slate-50 disabled:text-slate-500';
@endphp

@if (($attributes['type'] ?? null) === 'password')
    {{-- Todo campo de contraseña lleva el botón de mostrar/ocultar. --}}
    <div x-data="{ ver: false }" class="relative">
        <input @disabled($disabled) {{ $attributes->merge(['class' => $clases.' pr-11']) }} :type="ver ? 'text' : 'password'">
        <button type="button" @click="ver = !ver" :aria-pressed="ver.toString()" :aria-label="ver ? 'Ocultar contraseña' : 'Mostrar contraseña'"
            class="absolute inset-y-0 right-0 flex w-11 items-center justify-center rounded-r-lg text-slate-400 hover:text-slate-600 focus:outline-none focus-visible:text-indigo-600">
            <svg x-show="!ver" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" class="h-5 w-5" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z" />
                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
            </svg>
            <svg x-show="ver" x-cloak xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" class="h-5 w-5" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 0 0 1.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.451 10.451 0 0 1 12 4.5c4.756 0 8.773 3.162 10.065 7.498a10.522 10.522 0 0 1-4.293 5.774M6.228 6.228 3 3m3.228 3.228 3.65 3.65m7.894 7.894L21 21m-3.228-3.228-3.65-3.65m0 0a3 3 0 1 0-4.243-4.243m4.242 4.242L9.88 9.88" />
            </svg>
        </button>
    </div>
@else
    <input @disabled($disabled) {{ $attributes->merge(['class' => $clases]) }}>
@endif
