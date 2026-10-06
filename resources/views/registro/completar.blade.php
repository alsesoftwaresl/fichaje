<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Completa tu registro — {{ config('app.name') }}</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet" />
    @include('partials.head-brand')
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-slate-50 text-slate-900 antialiased font-sans">
    <div class="min-h-screen flex flex-col">
        <header class="border-b border-slate-200 bg-white">
            <div class="max-w-3xl mx-auto w-full px-6 py-5 flex justify-between items-center">
                <a href="{{ route('home') }}" class="flex items-center">
                    <img src="{{ asset('images/logo.png') }}" alt="{{ config('app.name') }}" class="h-7 sm:h-8 w-auto">
                </a>
            </div>
        </header>

        <main class="flex-1 px-6 py-12">
            <div class="max-w-lg mx-auto w-full">
                <div class="text-center mb-8">
                    <h1 class="text-2xl sm:text-3xl font-bold tracking-tight">Ya casi está</h1>
                    <p class="mt-2 text-slate-600">
                        Conectado como <strong>{{ $nombreAdmin }}</strong> ({{ $emailAdmin }}). Solo
                        nos falta el nombre de tu empresa.
                    </p>
                </div>

                <div class="bg-white border border-slate-200 rounded-xl shadow-sm p-6 sm:p-8">
                    <form method="POST" action="{{ route('registro.completar.store') }}" class="space-y-6">
                        @csrf

                        <div>
                            <x-input-label for="nombre" value="Nombre de la empresa" />
                            <x-text-input id="nombre" name="nombre" type="text" class="mt-1 block w-full" :value="old('nombre')" required autofocus />
                            <x-input-error class="mt-2" :messages="$errors->get('nombre')" />
                        </div>

                        <div class="grid sm:grid-cols-2 gap-4">
                            <div>
                                <x-input-label for="nif" value="NIF/CIF (opcional)" />
                                <x-text-input id="nif" name="nif" type="text" class="mt-1 block w-full" :value="old('nif')" />
                                <x-input-error class="mt-2" :messages="$errors->get('nif')" />
                            </div>

                            <div>
                                <x-input-label for="email_contacto" value="Email de contacto" />
                                <x-text-input id="email_contacto" name="email_contacto" type="email" class="mt-1 block w-full" :value="old('email_contacto', $emailAdmin)" required />
                                <x-input-error class="mt-2" :messages="$errors->get('email_contacto')" />
                            </div>
                        </div>

                        <div class="space-y-3 border-t border-slate-200 pt-6">
                            <label class="flex items-start gap-2 text-sm text-slate-700">
                                <input type="checkbox" name="acepta_terminos" value="1" class="mt-1 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500" required>
                                <span>
                                    La empresa acepta los
                                    <a href="{{ route('legal.terminos') }}" target="_blank" class="text-indigo-600 hover:underline">términos y condiciones</a>
                                    (v{{ $versionTerminos }}).
                                </span>
                            </label>
                            <x-input-error :messages="$errors->get('acepta_terminos')" />

                            <label class="flex items-start gap-2 text-sm text-slate-700">
                                <input type="checkbox" name="acepta_encargo" value="1" class="mt-1 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500" required>
                                <span>
                                    La empresa acepta el
                                    <a href="{{ route('legal.encargo-tratamiento') }}" target="_blank" class="text-indigo-600 hover:underline">contrato de encargo de tratamiento</a>
                                    (v{{ $versionEncargo }}).
                                </span>
                            </label>
                            <x-input-error :messages="$errors->get('acepta_encargo')" />
                        </div>

                        <x-primary-button type="submit" class="w-full justify-center py-3">
                            Crear cuenta y empezar
                        </x-primary-button>
                    </form>
                </div>
            </div>
        </main>
    </div>
</body>
</html>
