@php
    // Una sola fuente para la página y para los datos estructurados (featureList).
    // Lenguaje claro, pensado para quien lleva un negocio, no para un técnico.
    $bloques = [
        [
            'id' => 'fichar',
            'titulo' => 'Fichar es tocar un botón',
            'resumen' => 'Cada empleado ficha como le venga mejor, sin instalar nada.',
            'puntos' => [
                'Desde el móvil o el ordenador, con un solo botón de entrada y salida.',
                'En una tablet compartida en la entrada, con un PIN de 6 números para cada empleado.',
                'Si alguien no tiene correo, entra con su DNI.',
                'La hora la pone el sistema, no el empleado, así nadie puede cambiarla.',
            ],
        ],
        [
            'id' => 'control',
            'titulo' => 'Sabes qué pasa sin tener que preguntar',
            'resumen' => 'Un panel que te cuenta cómo va el día.',
            'puntos' => [
                'Ves de un vistazo quién está trabajando, quién está de vacaciones o de baja y quién está fuera.',
                'Te avisa de quién llega tarde, quién no ha fichado y quién se ha dejado la jornada abierta.',
                'Funciona con horario normal o con turno partido (mañana y tarde).',
                'Si hubo incidencias, recibes un correo cada día laborable con la lista.',
            ],
        ],
        [
            'id' => 'ausencias',
            'titulo' => 'Vacaciones y bajas, sin líos',
            'resumen' => 'Todo por escrito, sin mensajes sueltos por WhatsApp.',
            'puntos' => [
                'El empleado las pide desde su cuenta y tú las apruebas o las rechazas.',
                'Os avisamos por correo a los dos, y todo queda guardado.',
                'Si alguien tiene que salir un rato (médico, una gestión), lo avisa con la hora a la que vuelve.',
            ],
        ],
        [
            'id' => 'nominas',
            'titulo' => 'Nóminas al alcance de cada empleado',
            'resumen' => 'La empresa las sube y cada uno las descarga cuando quiera.',
            'puntos' => [
                'Subes la nómina en PDF y el empleado la descarga desde su cuenta.',
                'Cada uno ve solo las suyas, y le avisamos por correo cuando hay una nueva.',
                'Puede subirlas tu contable sin necesidad de ser administrador.',
                'No calcula las nóminas: las guarda y las reparte.',
            ],
        ],
        [
            'id' => 'registro',
            'titulo' => 'Un registro fiable, listo para entregar',
            'resumen' => 'Lo que se ficha no se puede cambiar, y puedes descargarlo cuando lo pidan.',
            'puntos' => [
                'Un fichaje guardado no se puede borrar ni editar. Si hay un error, se corrige aparte con su motivo y quién lo hizo.',
                'Descargas los fichajes en Excel, PDF o CSV, por empleado y por fechas.',
                'Queda anotado quién ha hecho cada cosa y cuándo.',
                'Los datos de tu empresa están separados de los de las demás.',
            ],
        ],
        [
            'id' => 'empleados',
            'titulo' => 'Cada empleado tiene su espacio',
            'resumen' => 'Lo suyo, siempre a mano, sin pedírselo al jefe.',
            'puntos' => [
                'Ve todos sus fichajes y las horas de cada día.',
                'Descarga sus nóminas cuando las necesita.',
                'Pide vacaciones y ve si se las han aprobado.',
                'Avisa si tiene que salir un rato.',
            ],
        ],
        [
            'id' => 'acceso',
            'titulo' => 'Empezar es rápido',
            'resumen' => 'En un par de minutos puedes tenerlo en marcha.',
            'puntos' => [
                'Te das de alta con tu correo o con tu cuenta de Google.',
                'Das de alta a cada empleado con su DNI y le generas su PIN.',
                '15 días gratis (se pide tarjeta, pero no se cobra nada durante la prueba) y sin permanencia.',
                'Facturas descargables, y cancelas cuando quieras.',
            ],
        ],
    ];
@endphp
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Funciones del software de control horario — {{ config('app.name') }}</title>
    <meta name="description" content="Todo lo que hace {{ config('app.name') }}: fichaje web y con PIN, turno partido, incidencias y retrasos, vacaciones y bajas, nóminas para los empleados, registro inalterable e informes.">
    <meta name="robots" content="index, follow, max-image-preview:large, max-snippet:-1">
    <link rel="canonical" href="{{ route('funciones') }}">

    <meta property="og:type" content="website">
    <meta property="og:title" content="Funciones del software de control horario — {{ config('app.name') }}">
    <meta property="og:description" content="Fichaje con PIN, turno partido, incidencias, ausencias, nóminas y registro inalterable.">
    <meta property="og:url" content="{{ route('funciones') }}">
    <meta property="og:image" content="{{ asset('images/og-image.png') }}">
    <meta property="og:locale" content="es_ES">
    <meta name="twitter:card" content="summary_large_image">

    <script type="application/ld+json">
    {!! json_encode([
        '@context' => 'https://schema.org',
        '@graph' => [
            [
                '@type' => 'WebPage',
                'name' => 'Funciones de '.config('app.name'),
                'url' => route('funciones'),
                'inLanguage' => 'es-ES',
                'about' => [
                    '@type' => 'SoftwareApplication',
                    'name' => config('app.name'),
                    'applicationCategory' => 'BusinessApplication',
                    'operatingSystem' => 'Web',
                    'featureList' => collect($bloques)->flatMap(fn ($b) => $b['puntos'])->values()->all(),
                ],
            ],
            [
                '@type' => 'BreadcrumbList',
                'itemListElement' => [
                    ['@type' => 'ListItem', 'position' => 1, 'name' => 'Inicio', 'item' => route('home')],
                    ['@type' => 'ListItem', 'position' => 2, 'name' => 'Funciones', 'item' => route('funciones')],
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
        <div class="max-w-5xl mx-auto w-full px-4 sm:px-6 py-5 flex justify-between items-center">
            <a href="{{ route('home') }}" class="flex items-center">
                <img src="{{ asset('images/logo-claro.png') }}" alt="{{ config('app.name') }}" class="h-7 w-auto">
            </a>
            <a href="{{ route('registro.create') }}" class="inline-flex items-center rounded-lg bg-accent-500 px-4 py-2 text-sm font-semibold text-indigo-950 hover:bg-accent-400 transition">Prueba gratis</a>
        </div>
    </header>

    <main class="max-w-5xl mx-auto w-full px-4 sm:px-6 py-12 sm:py-16">
        <nav aria-label="Migas de pan" class="text-sm text-slate-500">
            <a href="{{ route('home') }}" class="hover:underline">Inicio</a> <span aria-hidden="true">/</span> Funciones
        </nav>

        <h1 class="mt-6 font-display text-3xl sm:text-5xl font-bold tracking-tight text-indigo-950">Todo lo que hace {{ config('app.name') }}</h1>
        <p class="mt-4 max-w-2xl text-lg text-slate-600">
            Fichar, controlar al equipo, gestionar vacaciones y repartir nóminas, en un solo sitio y pensado para
            pequeñas empresas. Esto es lo que hace, explicado sin tecnicismos.
        </p>

        {{-- Índice --}}
        <nav aria-label="Índice de funciones" class="mt-8 flex flex-wrap gap-2">
            @foreach ($bloques as $b)
                <a href="#{{ $b['id'] }}" class="rounded-full border border-slate-200 px-4 py-1.5 text-sm font-medium text-slate-700 hover:border-indigo-300 hover:text-indigo-700 transition">{{ $b['titulo'] }}</a>
            @endforeach
        </nav>

        <div class="mt-14 space-y-14">
            @foreach ($bloques as $b)
                <section id="{{ $b['id'] }}" class="scroll-mt-6 grid gap-6 border-t border-slate-200 pt-10 lg:grid-cols-3">
                    <div>
                        <h2 class="font-display text-2xl font-bold tracking-tight text-indigo-950">{{ $b['titulo'] }}</h2>
                        <p class="mt-2 text-slate-600">{{ $b['resumen'] }}</p>
                    </div>
                    <ul class="space-y-3 text-slate-700 lg:col-span-2">
                        @foreach ($b['puntos'] as $punto)
                            <li class="flex gap-3">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" class="mt-1 h-4 w-4 shrink-0 text-accent-600" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" /></svg>
                                <span>{{ $punto }}</span>
                            </li>
                        @endforeach
                    </ul>
                </section>
            @endforeach

            <section id="limites" class="scroll-mt-6 rounded-2xl border border-slate-200 bg-slate-50 p-6 sm:p-8">
                <h2 class="font-display text-2xl font-bold tracking-tight text-indigo-950">Lo que no hace (todavía)</h2>
                <p class="mt-2 text-slate-600">Para que decidas con la información completa:</p>
                <ul class="mt-4 space-y-2 text-slate-700">
                    <li>No es un ERP ni un programa de nóminas: las nóminas se suben en PDF, no se calculan.</li>
                    <li>No hay aplicación móvil para instalar: funciona desde el navegador.</li>
                    <li>No tiene geolocalización ni reconocimiento facial.</li>
                    <li>Necesita conexión a internet para registrar los fichajes.</li>
                    <li>No gestiona cuadrantes de turnos rotativos, ni el saldo de días de vacaciones.</li>
                </ul>
            </section>

            <section class="rounded-2xl bg-indigo-950 px-6 py-12 text-center text-white sm:px-12">
                <h2 class="font-display text-2xl sm:text-3xl font-bold tracking-tight">Pruébalo 15 días gratis</h2>
                <p class="mx-auto mt-3 max-w-lg text-indigo-100/90">Se pide tarjeta, pero no se cobra nada durante la prueba. Sin permanencia.</p>
                <div class="mt-6 flex flex-col items-center justify-center gap-3 sm:flex-row">
                    <a href="{{ route('registro.create') }}" class="inline-flex items-center justify-center rounded-lg bg-accent-500 px-7 py-3 font-semibold text-indigo-950 hover:bg-accent-400 transition">Crear mi cuenta</a>
                    <a href="{{ route('contacto.create') }}" class="inline-flex items-center justify-center rounded-lg border border-indigo-400/40 px-7 py-3 font-medium text-white hover:bg-white/5 transition">Hablar con el equipo</a>
                </div>
            </section>
        </div>
    </main>

    <footer class="bg-indigo-950">
        <div class="max-w-5xl mx-auto w-full px-4 sm:px-6 py-8 flex flex-wrap gap-x-6 gap-y-2 text-sm text-indigo-300">
            <a href="{{ route('home') }}" class="hover:text-white hover:underline">Inicio</a>
            <a href="{{ route('guia.registro-jornada') }}" class="hover:text-white hover:underline">Registro de jornada</a>
            <a href="{{ route('contacto.create') }}" class="hover:text-white hover:underline">Contacto</a>
            <a href="{{ route('legal.aviso-legal') }}" class="hover:text-white hover:underline">Aviso legal</a>
            <a href="{{ route('legal.terminos') }}" class="hover:text-white hover:underline">Términos</a>
            <a href="{{ route('legal.privacidad') }}" class="hover:text-white hover:underline">Privacidad</a>
        </div>
    </footer>
</body>
</html>
