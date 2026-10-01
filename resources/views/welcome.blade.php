<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name') }}</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-white text-slate-900 antialiased font-sans">
    <div class="min-h-screen flex flex-col">
        <header class="border-b border-slate-100">
            <div class="max-w-5xl mx-auto w-full px-6 py-5 flex justify-between items-center">
                <span class="flex items-center gap-2">
                    <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-indigo-600 text-white">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="w-5 h-5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6l4 2" />
                            <circle cx="12" cy="12" r="9" stroke-linecap="round" stroke-linejoin="round" />
                        </svg>
                    </span>
                    <span class="font-semibold">{{ config('app.name') }}</span>
                </span>
                <nav class="text-sm">
                    @auth
                        <a href="{{ route('dashboard') }}" class="inline-flex items-center rounded-lg bg-indigo-600 px-4 py-2 text-white font-medium hover:bg-indigo-500 transition">Ir al panel</a>
                    @else
                        <a href="{{ route('login') }}" class="inline-flex items-center rounded-lg bg-indigo-600 px-4 py-2 text-white font-medium hover:bg-indigo-500 transition">Iniciar sesión</a>
                    @endauth
                </nav>
            </div>
        </header>

        <main class="flex-1">
            <section class="max-w-5xl mx-auto w-full px-6 pt-20 pb-16 text-center">
                <span class="inline-flex items-center rounded-full bg-indigo-50 px-3 py-1 text-xs font-medium text-indigo-700 ring-1 ring-inset ring-indigo-600/20 mb-6">
                    Conforme al RD-ley 8/2019
                </span>
                <h1 class="text-4xl sm:text-5xl font-bold tracking-tight text-slate-900 max-w-2xl mx-auto">
                    Registro de jornada laboral, sin complicaciones
                </h1>
                <p class="mt-6 text-lg text-slate-600 max-w-xl mx-auto">
                    Fichaje de entrada y salida para tu equipo, con un registro no manipulable y
                    correcciones siempre trazables.
                </p>
            </section>

            <section class="max-w-5xl mx-auto w-full px-6 pb-24">
                <div class="grid sm:grid-cols-3 gap-6">
                    <div class="rounded-xl border border-slate-200 p-6">
                        <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-indigo-50 text-indigo-600 mb-4">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" class="w-5 h-5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                            </svg>
                        </div>
                        <h3 class="font-semibold text-slate-900">Registro no manipulable</h3>
                        <p class="mt-2 text-sm text-slate-600">Cada fichaje queda guardado de forma permanente. Las correcciones se registran aparte, con motivo y autor.</p>
                    </div>
                    <div class="rounded-xl border border-slate-200 p-6">
                        <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-indigo-50 text-indigo-600 mb-4">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" class="w-5 h-5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 21h16.5M4.5 3h15M5.25 3v18m13.5-18v18M9 6.75h1.5m-1.5 3h1.5m-1.5 3h1.5m3-6H15m-1.5 3H15m-1.5 3H15" />
                            </svg>
                        </div>
                        <h3 class="font-semibold text-slate-900">Pensado para varias empresas</h3>
                        <p class="mt-2 text-sm text-slate-600">Cada empresa gestiona a sus propios empleados, con los datos siempre aislados del resto.</p>
                    </div>
                    <div class="rounded-xl border border-slate-200 p-6">
                        <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-indigo-50 text-indigo-600 mb-4">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" class="w-5 h-5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3" />
                            </svg>
                        </div>
                        <h3 class="font-semibold text-slate-900">Exportable en cualquier momento</h3>
                        <p class="mt-2 text-sm text-slate-600">Descarga el histórico de fichajes en CSV cuando lo necesites, listo para nóminas o una inspección.</p>
                    </div>
                </div>
            </section>

            <section class="border-t border-slate-100 bg-slate-50">
                <div class="max-w-5xl mx-auto w-full px-6 py-12 text-center">
                    <p class="text-sm text-slate-600">
                        El acceso lo gestiona tu empresa. Si todavía no tienes cuenta, contacta con
                        quien administra tu organización en {{ config('app.name') }}.
                    </p>
                </div>
            </section>
        </main>

        <footer class="border-t border-slate-100">
            <div class="max-w-5xl mx-auto w-full px-6 py-6 text-sm text-slate-500 flex flex-wrap gap-x-6 gap-y-2">
                <a href="{{ route('legal.terminos') }}" class="hover:text-slate-700 hover:underline">Términos</a>
                <a href="{{ route('legal.privacidad') }}" class="hover:text-slate-700 hover:underline">Privacidad</a>
                <a href="{{ route('legal.encargo-tratamiento') }}" class="hover:text-slate-700 hover:underline">Encargo de tratamiento</a>
                <a href="{{ route('legal.cookies') }}" class="hover:text-slate-700 hover:underline">Cookies</a>
            </div>
        </footer>
    </div>
</body>
</html>
