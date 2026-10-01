<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Laravel') }}</title>

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet" />

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased bg-slate-50 text-slate-900">
        @include('layouts.navigation')

        <div class="lg:pl-64">
            @isset($header)
                <header class="bg-white border-b border-slate-200">
                    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
                        {{ $header }}
                    </div>
                </header>
            @endisset

            <main class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
                @if (session('status'))
                    <div class="mb-6 rounded-lg bg-emerald-50 border border-emerald-200 px-4 py-3 text-sm text-emerald-800">
                        {{ session('status') }}
                    </div>
                @endif

                {{ $slot }}
            </main>
        </div>
    </body>
</html>
