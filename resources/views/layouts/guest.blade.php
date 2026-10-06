<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Laravel') }}</title>

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet" />

        @include('partials.head-brand')
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans text-slate-900 antialiased">
        <div class="min-h-screen lg:grid lg:grid-cols-2">
            <!-- Panel de marca (solo escritorio) -->
            <aside class="relative hidden lg:flex flex-col justify-between overflow-hidden bg-indigo-950 p-12 text-white">
                <div class="pointer-events-none absolute -right-32 -top-32 h-96 w-96 rounded-full bg-accent-500/20 blur-3xl"></div>
                <div class="pointer-events-none absolute -bottom-40 -left-24 h-96 w-96 rounded-full bg-indigo-500/30 blur-3xl"></div>

                <a href="/" class="relative flex items-center">
                    <img src="{{ asset('images/logo-claro.png') }}" alt="{{ config('app.name') }}" class="h-9 w-auto">
                </a>

                <div class="relative max-w-md">
                    <h2 class="font-display text-4xl font-bold leading-tight tracking-tight">
                        Tu equipo ficha.<br>
                        <span class="text-accent-400">La ley, cumplida.</span>
                    </h2>
                    <ul class="mt-8 space-y-4 text-indigo-100">
                        <li class="flex gap-3">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" class="mt-0.5 h-5 w-5 shrink-0 text-accent-400"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" /></svg>
                            Registro que no se puede manipular: las correcciones se guardan aparte.
                        </li>
                        <li class="flex gap-3">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" class="mt-0.5 h-5 w-5 shrink-0 text-accent-400"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" /></svg>
                            Kiosco con PIN: una tablet en la entrada y a fichar.
                        </li>
                        <li class="flex gap-3">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" class="mt-0.5 h-5 w-5 shrink-0 text-accent-400"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" /></svg>
                            Informes listos para nóminas o para una inspección.
                        </li>
                    </ul>
                </div>

                <p class="relative text-sm text-indigo-300">Conforme al RD-ley 8/2019 y al RGPD</p>
            </aside>

            <!-- Formulario -->
            <main class="flex min-h-screen flex-col items-center justify-center bg-slate-50 px-4 py-10 lg:min-h-0">
                <a href="/" class="mb-8 flex items-center lg:hidden">
                    <img src="{{ asset('images/logo.png') }}" alt="{{ config('app.name') }}" class="h-9 w-auto">
                </a>

                <div class="w-full max-w-md border border-slate-200 bg-white px-6 py-8 shadow-sm sm:rounded-xl sm:px-8">
                    {{ $slot }}
                </div>
            </main>
        </div>
    </body>
</html>
