<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Control horario por tipo de negocio — {{ config('app.name') }}</title>
    <meta name="description" content="Cómo funciona el control horario y el registro de jornada en bares y restaurantes, tiendas, talleres y oficinas. Elige tu tipo de negocio.">
    <meta name="robots" content="index, follow, max-image-preview:large, max-snippet:-1">
    <link rel="canonical" href="{{ route('sectores.index') }}">
    <meta property="og:type" content="website">
    <meta property="og:title" content="Control horario por tipo de negocio — {{ config('app.name') }}">
    <meta property="og:description" content="Bares y restaurantes, tiendas, talleres y oficinas: cómo te ayuda en cada caso.">
    <meta property="og:url" content="{{ route('sectores.index') }}">
    <meta property="og:image" content="{{ asset('images/og-image.png') }}">
    <meta property="og:locale" content="es_ES">
    <meta name="twitter:card" content="summary_large_image">

    <script type="application/ld+json">
    {!! json_encode([
        '@context' => 'https://schema.org',
        '@graph' => [
            [
                '@type' => 'CollectionPage',
                'name' => 'Control horario por tipo de negocio',
                'url' => route('sectores.index'),
                'inLanguage' => 'es-ES',
                'hasPart' => collect($sectores)->map(fn ($s, $slug) => [
                    '@type' => 'WebPage', 'name' => $s['titulo'], 'url' => route('sector.show', $slug),
                ])->values()->all(),
            ],
            [
                '@type' => 'BreadcrumbList',
                'itemListElement' => [
                    ['@type' => 'ListItem', 'position' => 1, 'name' => 'Inicio', 'item' => route('home')],
                    ['@type' => 'ListItem', 'position' => 2, 'name' => 'Control horario por sector', 'item' => route('sectores.index')],
                ],
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
    <x-sitio.cabecera ancho="max-w-5xl" />

    <main class="max-w-5xl mx-auto w-full px-4 sm:px-6 py-12 sm:py-16">
        <nav aria-label="Migas de pan" class="text-sm text-slate-500">
            <a href="{{ route('home') }}" class="hover:underline">Inicio</a> <span aria-hidden="true">/</span> Control horario por sector
        </nav>

        <h1 class="mt-6 font-display text-3xl sm:text-5xl font-bold tracking-tight text-indigo-950">Control horario para cada tipo de negocio</h1>
        <p class="mt-4 max-w-2xl text-lg text-slate-600">No es lo mismo fichar en un restaurante que en una oficina. Elige el tuyo y mira cómo te ayuda.</p>

        <div class="mt-10 grid gap-5 sm:grid-cols-2">
            @foreach ($sectores as $slug => $s)
                <a href="{{ route('sector.show', $slug) }}" class="group rounded-2xl border border-slate-200 p-6 transition hover:border-indigo-300 hover:shadow-md">
                    <h2 class="font-display text-xl font-semibold text-indigo-950">{{ $s['nombre'] }}</h2>
                    <p class="mt-2 text-sm leading-relaxed text-slate-600">{{ $s['intro'] }}</p>
                    <span class="mt-4 inline-block text-sm font-semibold text-indigo-600 group-hover:underline">Ver cómo te ayuda →</span>
                </a>
            @endforeach
        </div>
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
