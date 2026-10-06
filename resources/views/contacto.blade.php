<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Contacto y atención al cliente — {{ config('app.name') }}</title>
    <meta name="description" content="Atención al cliente en español y con atención personal. Escríbenos a {{ config('legal.titular.email') }} o usa el formulario: dudas antes de empezar, soporte técnico, facturación e incidencias urgentes.">
    <meta name="robots" content="index, follow">
    <link rel="canonical" href="{{ route('contacto.create') }}">

    <meta property="og:type" content="website">
    <meta property="og:title" content="Contacto y atención al cliente — {{ config('app.name') }}">
    <meta property="og:description" content="Atención al cliente en español, con atención personal.">
    <meta property="og:url" content="{{ route('contacto.create') }}">
    <meta property="og:image" content="{{ asset('images/og-image.png') }}">
    <meta property="og:locale" content="es_ES">

    <script type="application/ld+json">
    {!! json_encode([
        '@context' => 'https://schema.org',
        '@type' => 'ContactPage',
        'name' => 'Contacto de '.config('app.name'),
        'url' => route('contacto.create'),
        'inLanguage' => 'es-ES',
        'about' => [
            '@type' => 'Organization',
            'name' => config('app.name'),
            'legalName' => config('legal.titular.nombre'),
            'contactPoint' => [
                '@type' => 'ContactPoint',
                'contactType' => 'customer support',
                'email' => config('legal.titular.email'),
                'availableLanguage' => 'Spanish',
            ],
        ],
    ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}
    </script>

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet" />
    @include('partials.head-brand')
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-white text-slate-900 antialiased font-sans">
    <header class="bg-indigo-950">
        <div class="max-w-4xl mx-auto w-full px-4 sm:px-6 py-5 flex justify-between items-center">
            <a href="{{ route('home') }}" class="flex items-center">
                <img src="{{ asset('images/logo-claro.png') }}" alt="{{ config('app.name') }}" class="h-7 w-auto">
            </a>
            <a href="{{ route('registro.create') }}" class="inline-flex items-center rounded-lg bg-accent-500 px-4 py-2 text-sm font-semibold text-indigo-950 hover:bg-accent-400 transition">Prueba gratis</a>
        </div>
    </header>

    <main class="max-w-4xl mx-auto w-full px-4 sm:px-6 py-12 sm:py-16">
        <h1 class="font-display text-3xl sm:text-4xl font-bold tracking-tight text-indigo-950">Contacto y atención al cliente</h1>
        <p class="mt-4 max-w-2xl text-lg text-slate-600">
            Servicio de atención al cliente en español, con atención personal: te responde una persona del equipo.
            Cuéntanos qué necesitas.
        </p>

        <div class="mt-10 grid gap-10 lg:grid-cols-5">
            <div class="lg:col-span-3">
                @include('partials.formulario-contacto', ['origen' => 'contacto', 'asuntoInicial' => $asuntoInicial ?? null])
            </div>

            <aside class="space-y-5 lg:col-span-2">
                <div class="rounded-xl border border-slate-200 p-6">
                    <h2 class="font-display text-lg font-semibold text-indigo-950">Escríbenos directamente</h2>
                    <p class="mt-2 text-sm text-slate-600">Atención al cliente en español:</p>
                    <a href="mailto:{{ config('legal.titular.email') }}" class="mt-1 block break-all font-medium text-indigo-600 hover:underline">{{ config('legal.titular.email') }}</a>
                </div>

                <div class="rounded-xl border border-amber-200 bg-amber-50 p-6">
                    <h2 class="font-display text-lg font-semibold text-amber-900">¿Es una emergencia?</h2>
                    <p class="mt-2 text-sm text-amber-900/90">
                        Si tu equipo no puede fichar o has perdido el acceso a tu cuenta, elige
                        «Incidencia urgente» en el formulario o escríbenos a
                        <a href="mailto:{{ config('legal.titular.email') }}?subject=URGENTE" class="font-medium underline">{{ config('legal.titular.email') }}</a>
                        con «URGENTE» en el asunto. Las incidencias críticas se atienden con prioridad.
                    </p>
                </div>
            </aside>
        </div>
    </main>

    <footer class="bg-indigo-950">
        <div class="max-w-4xl mx-auto w-full px-4 sm:px-6 py-8 flex flex-wrap gap-x-6 gap-y-2 text-sm text-indigo-300">
            <a href="{{ route('home') }}" class="hover:text-white hover:underline">Inicio</a>
            <a href="{{ route('guia.registro-jornada') }}" class="hover:text-white hover:underline">Registro de jornada</a>
            <a href="{{ route('legal.aviso-legal') }}" class="hover:text-white hover:underline">Aviso legal</a>
            <a href="{{ route('legal.terminos') }}" class="hover:text-white hover:underline">Términos</a>
            <a href="{{ route('legal.privacidad') }}" class="hover:text-white hover:underline">Privacidad</a>
        </div>
    </footer>
</body>
</html>
