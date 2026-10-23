<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $sector['titulo'] }} — {{ config('app.name') }}</title>
    <meta name="description" content="{{ $sector['descripcion'] }}">
    <meta name="robots" content="index, follow, max-image-preview:large, max-snippet:-1">
    <link rel="canonical" href="{{ route('sector.show', $slug) }}">

    <meta property="og:type" content="website">
    <meta property="og:title" content="{{ $sector['titulo'] }}">
    <meta property="og:description" content="{{ $sector['descripcion'] }}">
    <meta property="og:url" content="{{ route('sector.show', $slug) }}">
    <meta property="og:image" content="{{ asset('images/og-image.png') }}">
    <meta property="og:locale" content="es_ES">
    <meta name="twitter:card" content="summary_large_image">

    <script type="application/ld+json">
    {!! json_encode([
        '@context' => 'https://schema.org',
        '@graph' => [
            [
                '@type' => 'WebPage',
                'name' => $sector['titulo'],
                'url' => route('sector.show', $slug),
                'inLanguage' => 'es-ES',
                'description' => $sector['descripcion'],
            ],
            [
                '@type' => 'BreadcrumbList',
                'itemListElement' => [
                    ['@type' => 'ListItem', 'position' => 1, 'name' => 'Inicio', 'item' => route('home')],
                    ['@type' => 'ListItem', 'position' => 2, 'name' => 'Control horario por sector', 'item' => route('sectores.index')],
                    ['@type' => 'ListItem', 'position' => 3, 'name' => $sector['nombre'], 'item' => route('sector.show', $slug)],
                ],
            ],
            [
                '@type' => 'FAQPage',
                'mainEntity' => collect($sector['faqs'])->map(fn ($f) => [
                    '@type' => 'Question',
                    'name' => $f[0],
                    'acceptedAnswer' => ['@type' => 'Answer', 'text' => $f[1]],
                ])->all(),
            ],
        ],
    ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) !!}
    </script>

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet" />
    @include('partials.head-brand')
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-white text-slate-900 antialiased font-sans">
    <x-sitio.cabecera ancho="max-w-5xl" />

    <main class="max-w-5xl mx-auto w-full px-4 sm:px-6 py-12 sm:py-16">
        <nav aria-label="Migas de pan" class="text-sm text-slate-500">
            <a href="{{ route('home') }}" class="hover:underline">Inicio</a> <span aria-hidden="true">/</span>
            <a href="{{ route('sectores.index') }}" class="hover:underline">Control horario por sector</a> <span aria-hidden="true">/</span>
            {{ $sector['nombre'] }}
        </nav>

        <h1 class="mt-6 font-display text-3xl sm:text-5xl font-bold tracking-tight text-indigo-950">{{ $sector['titulo'] }}</h1>
        <p class="mt-4 max-w-2xl text-lg text-slate-600">{{ $sector['intro'] }}</p>
        <div class="mt-8 flex flex-col gap-3 sm:flex-row">
            <a href="{{ route('registro.create') }}" class="inline-flex items-center justify-center rounded-lg bg-indigo-950 px-7 py-3.5 font-semibold text-white transition hover:bg-indigo-900">Probar 15 días gratis</a>
            <a href="{{ route('funciones') }}" class="inline-flex items-center justify-center rounded-lg border border-slate-300 px-7 py-3.5 font-medium text-slate-800 transition hover:border-indigo-300">Ver todas las funciones</a>
        </div>
        <p class="mt-3 text-xs text-slate-500">Se pide tarjeta, pero no se cobra nada durante la prueba. Sin permanencia.</p>

        <section class="mt-14 grid gap-8 lg:grid-cols-3">
            <div>
                <h2 class="font-display text-2xl font-bold tracking-tight text-indigo-950">Lo que suele pasar</h2>
            </div>
            <ul class="space-y-3 text-slate-700 lg:col-span-2">
                @foreach ($sector['situaciones'] as $situacion)
                    <li class="flex gap-3"><span class="mt-2 h-1.5 w-1.5 shrink-0 rounded-full bg-slate-400"></span><span>{{ $situacion }}</span></li>
                @endforeach
            </ul>
        </section>

        <section class="mt-14">
            <h2 class="font-display text-2xl font-bold tracking-tight text-indigo-950">Cómo lo resuelve {{ config('app.name') }}</h2>
            <div class="mt-6 grid gap-4 sm:grid-cols-2">
                @foreach ($sector['solucion'] as [$titulo, $texto])
                    <div class="rounded-2xl border border-slate-200 p-5">
                        <h3 class="font-display text-lg font-semibold text-indigo-950">{{ $titulo }}</h3>
                        <p class="mt-2 text-sm leading-relaxed text-slate-600">{{ $texto }}</p>
                    </div>
                @endforeach
            </div>
        </section>

        <section class="mt-14 rounded-2xl border border-accent-200 bg-accent-50 p-6 sm:p-8">
            <h2 class="font-display text-xl font-bold text-indigo-950">Un ejemplo</h2>
            <p class="mt-3 leading-relaxed text-slate-700">{{ $sector['ejemplo'] }}</p>
        </section>

        <section class="mt-14" aria-labelledby="faq-titulo">
            <h2 id="faq-titulo" class="font-display text-2xl font-bold tracking-tight text-indigo-950">Preguntas frecuentes</h2>
            <div class="mt-6 divide-y divide-slate-200 border-y border-slate-200">
                @foreach ($sector['faqs'] as [$pregunta, $respuesta])
                    <details class="group py-4">
                        <summary class="flex cursor-pointer list-none items-center justify-between gap-4 font-semibold text-indigo-950">
                            {{ $pregunta }}
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="h-5 w-5 shrink-0 text-slate-400 transition group-open:rotate-180" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" /></svg>
                        </summary>
                        <p class="mt-3 leading-relaxed text-slate-600">{{ $respuesta }}</p>
                    </details>
                @endforeach
            </div>
        </section>

        <section class="mt-14 grid gap-6 sm:grid-cols-2">
            <div class="rounded-2xl border border-slate-200 p-6">
                <h2 class="font-display text-lg font-semibold text-indigo-950">Antes de decidirte, una plantilla gratis</h2>
                <p class="mt-2 text-sm text-slate-600">Descarga un registro diario de jornada en Excel o PDF, sin registrarte.</p>
                <a href="{{ route('plantilla.index') }}" class="mt-4 inline-block text-sm font-semibold text-indigo-600 hover:underline">Ver la plantilla →</a>
            </div>
            <div class="rounded-2xl border border-slate-200 p-6">
                <h2 class="font-display text-lg font-semibold text-indigo-950">Qué exige la ley</h2>
                <p class="mt-2 text-sm text-slate-600">Qué hay que registrar, durante cuánto tiempo y en qué punto está la reforma.</p>
                <a href="{{ route('guia.registro-jornada') }}" class="mt-4 inline-block text-sm font-semibold text-indigo-600 hover:underline">Leer la guía →</a>
            </div>
        </section>

        <section class="mt-14">
            <h2 class="font-display text-lg font-semibold text-indigo-950">Otros tipos de negocio</h2>
            <div class="mt-4 flex flex-wrap gap-2">
                @foreach ($otros as $otroSlug => $otro)
                    <a href="{{ route('sector.show', $otroSlug) }}" class="rounded-full border border-slate-200 px-4 py-1.5 text-sm font-medium text-slate-700 transition hover:border-indigo-300 hover:text-indigo-700">{{ $otro['nombre'] }}</a>
                @endforeach
            </div>
        </section>
    </main>

    <footer class="bg-indigo-950">
        <div class="max-w-5xl mx-auto w-full px-4 sm:px-6 py-8 flex flex-wrap gap-x-6 gap-y-2 text-sm text-indigo-300">
            <a href="{{ route('home') }}" class="hover:text-white hover:underline">Inicio</a>
            <a href="{{ route('funciones') }}" class="hover:text-white hover:underline">Funciones</a>
            <a href="{{ route('plantilla.index') }}" class="hover:text-white hover:underline">Plantilla gratis</a>
            <a href="{{ route('contacto.create') }}" class="hover:text-white hover:underline">Contacto</a>
            <a href="{{ route('legal.aviso-legal') }}" class="hover:text-white hover:underline">Aviso legal</a>
            <a href="{{ route('legal.privacidad') }}" class="hover:text-white hover:underline">Privacidad</a>
        </div>
    </footer>
</body>
</html>
