<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Registro de jornada obligatorio en España: qué exige la ley — {{ config('app.name') }}</title>
    <meta name="description" content="Qué es el registro de jornada obligatorio, qué dice el artículo 34.9 del Estatuto de los Trabajadores (RD-ley 8/2019), qué datos hay que guardar, durante cuánto tiempo y cómo cumplirlo en tu empresa.">
    <meta name="robots" content="index, follow, max-image-preview:large, max-snippet:-1">
    <link rel="canonical" href="{{ route('guia.registro-jornada') }}">

    <meta property="og:type" content="article">
    <meta property="og:title" content="Registro de jornada obligatorio en España: qué exige la ley">
    <meta property="og:description" content="Qué dice la ley sobre el registro de jornada, qué datos guardar, cuánto tiempo conservarlos y cómo cumplirlo.">
    <meta property="og:url" content="{{ route('guia.registro-jornada') }}">
    <meta property="og:image" content="{{ asset('images/og-image.png') }}">
    <meta property="og:locale" content="es_ES">
    <meta name="twitter:card" content="summary_large_image">

    <script type="application/ld+json">
    {!! json_encode([
        '@context' => 'https://schema.org',
        '@graph' => [
            [
                '@type' => 'Article',
                'headline' => 'Registro de jornada obligatorio en España: qué exige la ley',
                'inLanguage' => 'es-ES',
                'mainEntityOfPage' => route('guia.registro-jornada'),
                'image' => asset('images/og-image.png'),
                'author' => ['@type' => 'Organization', 'name' => config('app.name')],
                'publisher' => ['@type' => 'Organization', 'name' => config('app.name'), 'logo' => ['@type' => 'ImageObject', 'url' => asset('images/logo.png')]],
            ],
            [
                '@type' => 'BreadcrumbList',
                'itemListElement' => [
                    ['@type' => 'ListItem', 'position' => 1, 'name' => 'Inicio', 'item' => route('home')],
                    ['@type' => 'ListItem', 'position' => 2, 'name' => 'Registro de jornada', 'item' => route('guia.registro-jornada')],
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
            <a href="{{ route('home') }}" class="hover:underline">Inicio</a> <span aria-hidden="true">/</span> Registro de jornada
        </nav>

        <article class="mt-6 text-slate-700 leading-relaxed space-y-5">
            <h1 class="font-display text-3xl sm:text-4xl font-bold tracking-tight text-indigo-950">Registro de jornada obligatorio en España: qué exige la ley y cómo cumplirla</h1>

            <p class="text-lg">
                Desde 2019, todas las empresas en España tienen que llevar un registro de la jornada de sus trabajadores.
                Esta guía resume qué dice la norma, qué hay que guardar y cómo se puede cumplir sin hojas de cálculo.
            </p>

            <h2 class="pt-4 font-display text-2xl font-bold text-indigo-950">¿Qué dice la ley?</h2>
            <p>
                El Real Decreto-ley 8/2019, de 8 de marzo, añadió el apartado 9 al artículo 34 del Estatuto de los
                Trabajadores. Establece que la empresa debe garantizar el registro diario de la jornada, que ha de
                incluir el horario concreto de inicio y de finalización de la jornada de cada persona trabajadora.
                La obligación se aplica desde el 12 de mayo de 2019.
            </p>
            <p>
                Poco después, el Tribunal de Justicia de la Unión Europea (sentencia de 14 de mayo de 2019,
                asunto C-55/18) confirmó que los Estados deben exigir a las empresas un sistema objetivo, fiable
                y accesible para medir el tiempo de trabajo diario.
            </p>

            <h2 class="pt-4 font-display text-2xl font-bold text-indigo-950">¿Qué datos hay que registrar?</h2>
            <ul class="list-disc pl-6 space-y-2">
                <li>La hora de inicio y la hora de fin de la jornada de cada persona trabajadora, cada día.</li>
                <li>Los registros tienen que ser fiables: si algo se corrige, debe quedar constancia de qué se cambió, por qué y quién lo hizo.</li>
            </ul>
            <p>
                La ley no impone un formato concreto. Se puede organizar por negociación colectiva, por acuerdo de
                empresa o por decisión del empresario previa consulta con los representantes de los trabajadores,
                y puede ser en papel o digital.
            </p>

            <h2 class="pt-4 font-display text-2xl font-bold text-indigo-950">¿Cuánto tiempo hay que conservarlo?</h2>
            <p>
                Cuatro años. Durante ese tiempo el registro debe estar a disposición de las personas trabajadoras,
                de sus representantes legales y de la Inspección de Trabajo y Seguridad Social.
            </p>

            <h2 class="pt-4 font-display text-2xl font-bold text-indigo-950">¿Qué pasa si no se cumple?</h2>
            <p>
                No llevar el registro puede sancionarse como infracción grave según la Ley sobre Infracciones y
                Sanciones en el Orden Social (LISOS). Consulta los importes vigentes con tu asesoría laboral, porque
                se actualizan.
            </p>

            <h2 class="pt-4 font-display text-2xl font-bold text-indigo-950">Cómo cumplirlo con {{ config('app.name') }}</h2>
            <ul class="list-disc pl-6 space-y-2">
                <li><strong>Fichar es un toque.</strong> Cada empleado ficha desde el navegador o en un kiosco compartido con PIN, sin instalar nada.</li>
                <li><strong>Registro inalterable.</strong> Los fichajes no se editan ni se borran. Un error se arregla con una corrección que guarda el motivo y quién la hizo, y el fichaje original se conserva.</li>
                <li><strong>Listo para pedirlo.</strong> Exportas los fichajes a CSV, Excel o PDF filtrando por empleado y fechas.</li>
                <li><strong>Horarios e incidencias.</strong> Defines el horario (corrido o partido) y la app avisa de retrasos, faltas de fichaje y horas de más.</li>
                <li><strong>Ausencias.</strong> Vacaciones y bajas con solicitud del empleado y aprobación del administrador.</li>
            </ul>

            <div class="rounded-xl border border-slate-200 bg-slate-50 p-6">
                <p class="font-semibold text-indigo-950">Prueba {{ config('app.name') }} 15 días gratis</p>
                <p class="mt-1 text-sm">Da de alta tu empresa en un par de minutos. Sin permanencia.</p>
                <a href="{{ route('registro.create') }}" class="mt-4 inline-flex items-center rounded-lg bg-indigo-950 px-5 py-2.5 text-sm font-semibold text-white hover:bg-indigo-900 transition">Crear mi cuenta</a>
            </div>

            <p class="text-sm text-slate-500">
                Esta guía es informativa y no constituye asesoramiento jurídico. La normativa puede cambiar: para tu
                caso concreto consulta con tu asesoría laboral o con un abogado.
            </p>
        </article>
    </main>

    <footer class="bg-indigo-950">
        <div class="max-w-4xl mx-auto w-full px-4 sm:px-6 py-8 flex flex-wrap gap-x-6 gap-y-2 text-sm text-indigo-300">
            <a href="{{ route('home') }}" class="hover:text-white hover:underline">Inicio</a>
            <a href="{{ route('legal.terminos') }}" class="hover:text-white hover:underline">Términos</a>
            <a href="{{ route('legal.privacidad') }}" class="hover:text-white hover:underline">Privacidad</a>
            <a href="{{ route('legal.cookies') }}" class="hover:text-white hover:underline">Cookies</a>
        </div>
    </footer>
</body>
</html>
