<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Crea tu cuenta — {{ config('app.name') }}</title>
    <meta name="description" content="Da de alta tu empresa en {{ config('app.name') }} y empieza a fichar hoy mismo. Sin permanencia, cumple con el RD-ley 8/2019.">
    <link rel="canonical" href="{{ route('registro.create') }}">
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
                <a href="{{ route('login') }}" class="text-sm text-slate-600 hover:text-slate-900 hover:underline">
                    ¿Ya tienes cuenta? Inicia sesión
                </a>
            </div>
        </header>

        <main class="flex-1 px-6 py-12">
            <div class="max-w-3xl mx-auto w-full">
                <div class="text-center mb-8">
                    <h1 class="text-2xl sm:text-3xl font-bold tracking-tight">Crea tu cuenta en {{ config('app.name') }}</h1>
                    <p class="mt-2 text-slate-600">
                        Dos minutos y tu equipo ya puede empezar a fichar. Después de registrarte eliges
                        tu plan — 15 días de prueba gratis, luego desde {{ $tarifa->precioBaseTexto() }}€/mes, incluye {{ $tarifa->empleados_incluidos }} empleados.
                    </p>
                </div>

                <div class="bg-white border border-slate-200 rounded-xl shadow-sm p-6 sm:p-8">
                    <a href="{{ route('auth.google.redirect') }}" class="w-full inline-flex items-center justify-center gap-2 rounded-md border border-slate-300 px-4 py-2.5 text-sm font-medium text-slate-700 hover:bg-slate-50 transition">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 48 48" class="w-5 h-5">
                            <path fill="#FFC107" d="M43.611 20.083H42V20H24v8h11.303c-1.649 4.657-6.08 8-11.303 8-6.627 0-12-5.373-12-12s5.373-12 12-12c3.059 0 5.842 1.154 7.961 3.039l5.657-5.657C34.046 6.053 29.268 4 24 4 12.955 4 4 12.955 4 24s8.955 20 20 20 20-8.955 20-20c0-1.341-.138-2.65-.389-3.917z"/>
                            <path fill="#FF3D00" d="M6.306 14.691l6.571 4.819C14.655 15.108 18.961 12 24 12c3.059 0 5.842 1.154 7.961 3.039l5.657-5.657C34.046 6.053 29.268 4 24 4 16.318 4 9.656 8.337 6.306 14.691z"/>
                            <path fill="#4CAF50" d="M24 44c5.166 0 9.86-1.977 13.409-5.192l-6.19-5.238C29.211 35.091 26.715 36 24 36c-5.202 0-9.619-3.317-11.283-7.946l-6.522 5.025C9.505 39.556 16.227 44 24 44z"/>
                            <path fill="#1976D2" d="M43.611 20.083H42V20H24v8h11.303a12.04 12.04 0 0 1-4.087 5.571l.003-.002 6.19 5.238C36.971 39.205 44 34 44 24c0-1.341-.138-2.65-.389-3.917z"/>
                        </svg>
                        Continuar con Google
                    </a>

                    <div class="relative my-6">
                        <div class="absolute inset-0 flex items-center"><div class="w-full border-t border-slate-200"></div></div>
                        <div class="relative flex justify-center text-xs"><span class="bg-white px-2 text-slate-400">o regístrate con tu email</span></div>
                    </div>

                    <form method="POST" action="{{ route('registro.store') }}" class="space-y-6">
                        @csrf

                        @if ($errors->any())
                            <div class="rounded-lg bg-rose-50 border border-rose-200 p-4 text-sm text-rose-700">
                                Revisa los datos marcados abajo.
                            </div>
                        @endif

                        <div class="space-y-4">
                            <h2 class="font-medium text-slate-900">Datos de la empresa</h2>

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
                                    <x-text-input id="email_contacto" name="email_contacto" type="email" class="mt-1 block w-full" :value="old('email_contacto')" required />
                                    <x-input-error class="mt-2" :messages="$errors->get('email_contacto')" />
                                </div>
                            </div>
                        </div>

                        <div class="space-y-4 border-t border-slate-200 pt-6">
                            <h2 class="font-medium text-slate-900">Tu cuenta de administrador</h2>

                            <div>
                                <x-input-label for="admin_name" value="Tu nombre" />
                                <x-text-input id="admin_name" name="admin_name" type="text" class="mt-1 block w-full" :value="old('admin_name')" required />
                                <x-input-error class="mt-2" :messages="$errors->get('admin_name')" />
                            </div>

                            <div>
                                <x-input-label for="admin_email" value="Correo electrónico" />
                                <x-text-input id="admin_email" name="admin_email" type="email" class="mt-1 block w-full" :value="old('admin_email')" required autocomplete="username" />
                                <x-input-error class="mt-2" :messages="$errors->get('admin_email')" />
                            </div>

                            <div class="grid sm:grid-cols-2 gap-4">
                                <div>
                                    <x-input-label for="admin_password" value="Contraseña" />
                                    <x-text-input id="admin_password" name="admin_password" type="password" class="mt-1 block w-full" required autocomplete="new-password" />
                                    <x-input-error class="mt-2" :messages="$errors->get('admin_password')" />
                                </div>

                                <div>
                                    <x-input-label for="admin_password_confirmation" value="Confirmar contraseña" />
                                    <x-text-input id="admin_password_confirmation" name="admin_password_confirmation" type="password" class="mt-1 block w-full" required autocomplete="new-password" />
                                </div>
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

                <p class="text-center text-xs text-slate-500 mt-6">
                    15 días de prueba gratis al suscribirte. Sin permanencia — podrás cancelar cuando
                    quieras desde Facturación antes de que acabe la prueba sin que se te cobre nada.
                </p>
            </div>
        </main>

        <footer class="border-t border-slate-200 bg-white">
            <div class="max-w-3xl mx-auto w-full px-6 py-6 text-sm text-slate-500 flex flex-wrap gap-x-6 gap-y-2">
                <a href="{{ route('legal.terminos') }}" class="hover:text-slate-700 hover:underline">Términos</a>
                <a href="{{ route('legal.privacidad') }}" class="hover:text-slate-700 hover:underline">Privacidad</a>
                <a href="{{ route('legal.encargo-tratamiento') }}" class="hover:text-slate-700 hover:underline">Encargo de tratamiento</a>
                <a href="{{ route('legal.cookies') }}" class="hover:text-slate-700 hover:underline">Cookies</a>
            </div>
        </footer>
    </div>
</body>
</html>
