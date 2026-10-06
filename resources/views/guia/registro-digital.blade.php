<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Registro horario digital obligatorio: en qué punto está la reforma — {{ config('app.name') }}</title>
    <meta name="description" content="¿Es ya obligatorio el fichaje digital? Punto de situación del reglamento del registro de jornada: qué exige la ley hoy, qué prepara el Ministerio de Trabajo y qué conviene hacer mientras tanto.">
    <meta name="robots" content="index, follow, max-image-preview:large, max-snippet:-1">
    <link rel="canonical" href="{{ route('guia.registro-digital') }}">

    <meta property="og:type" content="article">
    <meta property="og:title" content="Registro horario digital obligatorio: en qué punto está la reforma">
    <meta property="og:description" content="Qué exige la ley hoy, qué prepara el Ministerio de Trabajo y qué conviene hacer mientras tanto.">
    <meta property="og:url" content="{{ route('guia.registro-digital') }}">
    <meta property="og:image" content="{{ asset('images/og-image.png') }}">
    <meta property="og:locale" content="es_ES">
    <meta name="twitter:card" content="summary_large_image">

    <script type="application/ld+json">
    {!! json_encode([
        '@context' => 'https://schema.org',
        '@graph' => [
            [
                '@type' => 'Article',
                'headline' => 'Registro horario digital obligatorio: en qué punto está la reforma',
                'inLanguage' => 'es-ES',
                'dateModified' => '2026-10-07',
                'mainEntityOfPage' => route('guia.registro-digital'),
                'image' => asset('images/og-image.png'),
                'author' => ['@type' => 'Organization', 'name' => config('app.name')],
                'publisher' => ['@type' => 'Organization', 'name' => config('app.name'), 'logo' => ['@type' => 'ImageObject', 'url' => asset('images/logo.png')]],
            ],
            [
                '@type' => 'BreadcrumbList',
                'itemListElement' => [
                    ['@type' => 'ListItem', 'position' => 1, 'name' => 'Inicio', 'item' => route('home')],
                    ['@type' => 'ListItem', 'position' => 2, 'name' => 'Registro horario digital', 'item' => route('guia.registro-digital')],
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
    <header class="bg-indigo-950">
        <div class="max-w-4xl mx-auto w-full px-4 sm:px-6 py-5 flex justify-between items-center">
            <a href="{{ route('home') }}" class="flex items-center">
                <img src="{{ asset('images/logo-claro.png') }}" alt="{{ config('app.name') }}" class="h-7 w-auto">
            </a>
            <a href="{{ route('registro.create') }}" class="inline-flex items-center rounded-lg bg-accent-500 px-4 py-2 text-sm font-semibold text-indigo-950 hover:bg-accent-400 transition">Prueba gratis</a>
        </div>
    </header>

    <main class="max-w-3xl mx-auto w-full px-4 sm:px-6 py-12 sm:py-16">
        <nav aria-label="Migas de pan" class="text-sm text-slate-500">
            <a href="{{ route('home') }}" class="hover:underline">Inicio</a> <span aria-hidden="true">/</span> Registro horario digital
        </nav>

        <article class="mt-6 text-slate-700 leading-relaxed space-y-5">
            <h1 class="font-display text-3xl sm:text-4xl font-bold tracking-tight text-indigo-950">¿Es ya obligatorio el fichaje digital? En qué punto está la reforma</h1>
            <p class="text-sm text-slate-500">Actualizado en octubre de 2026. Este tema cambia con frecuencia: comprueba siempre el BOE.</p>

            <p class="text-lg">
                Se habla mucho de "la nueva ley del fichaje digital". Aquí va un resumen sin alarmismo de qué es
                obligatorio hoy y qué está todavía en trámite.
            </p>

            <h2 class="pt-4 font-display text-2xl font-bold text-indigo-950">Lo que ya es obligatorio</h2>
            <p>
                Desde 2019, el artículo 34.9 del Estatuto de los Trabajadores obliga a todas las empresas a llevar un
                registro diario de la jornada, con la hora de inicio y de fin de cada persona trabajadora, y a
                conservarlo cuatro años. Esa obligación está en vigor y no depende de ninguna reforma.
                Puedes verla con más detalle en la <a href="{{ route('guia.registro-jornada') }}" class="font-medium text-indigo-600 hover:underline">guía del registro de jornada</a>.
            </p>

            <h2 class="pt-4 font-display text-2xl font-bold text-indigo-950">Lo que está en preparación</h2>
            <p>
                El Ministerio de Trabajo tramita un real decreto que desarrollaría ese registro: según el texto
                que ha trascendido, tendría que ser digital, personal y no manipulable, con acceso remoto inmediato
                para los trabajadores, sus representantes y la Inspección de Trabajo, y distinguiendo las horas
                ordinarias de las extraordinarias.
            </p>
            <p>
                Según la información publicada, el Consejo de Estado emitió un dictamen desfavorable y la
                aprobación se aplazó varias veces. A principios de octubre de 2026 no estaba aprobado ni publicado
                en el BOE. Mientras no se publique, no hay una obligación nueva de usar un sistema digital concreto.
            </p>

            <h2 class="pt-4 font-display text-2xl font-bold text-indigo-950">Qué conviene hacer mientras tanto</h2>
            <ul class="list-disc pl-6 space-y-2">
                <li>Cumplir lo que ya es obligatorio: registrar cada día el inicio y el fin de jornada y guardarlo cuatro años.</li>
                <li>Si aún usas papel u hojas de cálculo, valorar pasar a un sistema digital en el que los registros no se puedan modificar sin dejar rastro, porque es la dirección que apunta la reforma.</li>
                <li>Desconfiar de quien te meta prisa con sanciones concretas o fechas límite que no estén publicadas en el BOE.</li>
            </ul>

            <div class="rounded-xl border border-slate-200 bg-slate-50 p-6">
                <p class="font-semibold text-indigo-950">Cómo lo hace {{ config('app.name') }}</p>
                <p class="mt-1 text-sm">
                    Registro digital, fichajes que no se editan ni se borran desde la aplicación y correcciones con
                    su motivo y su autor. Si el reglamento final exige algo más, lo adaptaremos.
                </p>
                <a href="{{ route('registro.create') }}" class="mt-4 inline-flex items-center rounded-lg bg-indigo-950 px-5 py-2.5 text-sm font-semibold text-white hover:bg-indigo-900 transition">Probar 15 días gratis</a>
            </div>

            <h2 class="pt-4 font-display text-xl font-bold text-indigo-950">Fuentes consultadas</h2>
            <ul class="list-disc pl-6 space-y-1 text-sm">
                <li><a href="https://www.iberley.es/noticias/aprobada-tramitacion-urgente-nuevo-reglamento-registro-horario-35393" rel="noopener nofollow" class="text-indigo-600 hover:underline">Iberley: tramitación urgente del nuevo reglamento del registro horario</a></li>
                <li><a href="https://aunte.es/blog/decreto-fichaje-digital-2026-hosteleria" rel="noopener nofollow" class="text-indigo-600 hover:underline">Aunte: fichaje digital en 2026, aún no está en el BOE</a></li>
                <li><a href="https://qworker.es/blog/registro-horario-digital-consejo-estado-reserva-ley" rel="noopener nofollow" class="text-indigo-600 hover:underline">Qworker: el dictamen del Consejo de Estado y la reserva de ley</a></li>
            </ul>

            <p class="text-sm text-slate-500">
                Este texto es informativo y no constituye asesoramiento jurídico. Para tu caso concreto consulta con tu
                asesoría laboral o con un abogado.
            </p>
        </article>
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
