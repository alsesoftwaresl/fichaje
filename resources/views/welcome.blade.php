@php
    $preguntas = [
        ['¿Es obligatorio el registro de jornada en España?', 'Sí. Desde el Real Decreto-ley 8/2019 (artículo 34.9 del Estatuto de los Trabajadores) las empresas deben garantizar el registro diario de la jornada de cada persona trabajadora, con la hora de inicio y de fin, y conservarlo cuatro años a disposición de los trabajadores, sus representantes y la Inspección de Trabajo.'],
        ['¿Cuánto cuesta '.config('app.name').'?', $tarifa->precioBaseTexto().' € al mes con '.$tarifa->empleados_incluidos.' empleados incluidos, más '.number_format((float) $tarifa->precio_empleado_extra, 2).' € al mes por cada empleado adicional. Tiene 15 días de prueba gratis y no hay permanencia.'],
        ['¿Mis empleados tienen que instalar una aplicación?', 'No. Pueden fichar desde el navegador del móvil o del ordenador, o en un kiosco compartido (una tablet o un PC en la entrada) tecleando su PIN de 6 dígitos.'],
        ['¿Se pueden modificar o borrar los fichajes?', 'No. Los fichajes no se editan ni se borran. Si hay un error, el administrador registra una corrección con su motivo; queda guardado quién la hizo y el fichaje original se conserva.'],
        ['¿Pueden fichar empleados que no tienen correo electrónico?', 'Sí. Cada empleado se identifica con su DNI o NIE; el correo es opcional.'],
        ['¿Se pueden gestionar vacaciones, bajas y nóminas?', 'Sí. Los empleados solicitan vacaciones y bajas y el administrador las aprueba o rechaza. La empresa puede subir las nóminas en PDF y cada empleado las ve y descarga cuando quiera.'],
        ['¿Puedo exportar los fichajes?', 'Sí, a CSV, Excel y PDF, filtrando por empleado y por fechas.'],
        ['¿Necesito tarjeta para la prueba gratis?', 'Sí. Se pide una tarjeta para activar la prueba de 15 días, pero no se cobra nada durante ese tiempo. Si cancelas antes de que acabe, no pagas.'],
        ['¿Funciona sin conexión a internet?', 'No. '.config('app.name').' es una aplicación web y necesita conexión para registrar los fichajes. Si en tu centro la conexión es poco fiable, ten en cuenta este punto antes de empezar.'],
        ['¿Tiene aplicación móvil?', 'No hay aplicación para instalar. Funciona desde el navegador del móvil, la tablet o el ordenador.'],
        ['¿Hay atención al cliente y cómo puedo contactar?', 'Sí. Ofrecemos atención al cliente en español, con atención personal. Puedes escribirnos a '.config('legal.titular.email').' o usar el formulario de contacto; si es una incidencia urgente, indícalo y la atendemos con prioridad.'],
        ['¿Quién está detrás de '.config('app.name').'?', config('app.name').' es un producto de '.config('legal.titular.nombre').'. Los datos del titular están en el aviso legal.'],
    ];
@endphp
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Control horario y registro de jornada online — {{ config('app.name') }}</title>
    <meta name="description" content="{{ config('app.name') }} es un software de control horario para pymes: tu equipo ficha desde el navegador o con un PIN en una tablet, y tú ves quién trabaja y exportas el registro de jornada. 15 días gratis, sin permanencia.">
    <meta name="robots" content="index, follow, max-image-preview:large, max-snippet:-1">
    <link rel="canonical" href="{{ route('home') }}">

    <meta property="og:type" content="website">
    <meta property="og:title" content="Control horario y registro de jornada online — {{ config('app.name') }}">
    <meta property="og:description" content="Fichaje con PIN o desde el navegador, panel de quién trabaja, avisos de retrasos y exportación del registro. 15 días gratis, sin permanencia.">
    <meta property="og:url" content="{{ route('home') }}">
    <meta property="og:image" content="{{ asset('images/og-image.png') }}">
    <meta property="og:image:width" content="1200">
    <meta property="og:image:height" content="630">
    <meta property="og:image:alt" content="{{ config('app.name') }} — control horario y fichaje de empleados">
    <meta property="og:site_name" content="{{ config('app.name') }}">
    <meta property="og:locale" content="es_ES">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="Control horario y registro de jornada online — {{ config('app.name') }}">
    <meta name="twitter:image" content="{{ asset('images/og-image.png') }}">

    <script type="application/ld+json">
    {!! json_encode([
        '@context' => 'https://schema.org',
        '@graph' => [
            [
                '@type' => 'Organization',
                '@id' => route('home').'#organizacion',
                'name' => config('app.name'),
                'legalName' => config('legal.titular.nombre'),
                'url' => route('home'),
                'logo' => asset('images/logo.png'),
            ] + (config('legal.titular.email') ? [
                'email' => config('legal.titular.email'),
                'contactPoint' => [
                    '@type' => 'ContactPoint',
                    'contactType' => 'customer support',
                    'email' => config('legal.titular.email'),
                    'availableLanguage' => 'Spanish',
                    'url' => route('contacto.create'),
                ],
            ] : []),
            [
                '@type' => 'WebSite',
                '@id' => route('home').'#web',
                'url' => route('home'),
                'name' => config('app.name'),
                'inLanguage' => 'es-ES',
                'publisher' => ['@id' => route('home').'#organizacion'],
            ],
            [
                '@type' => 'SoftwareApplication',
                'name' => config('app.name'),
                'url' => route('home'),
                'applicationCategory' => 'BusinessApplication',
                'operatingSystem' => 'Web',
                'inLanguage' => 'es-ES',
                'description' => 'Software de fichaje y control horario para empresas españolas, conforme al RD-ley 8/2019.',
                'offers' => [
                    '@type' => 'Offer',
                    'price' => number_format((float) $tarifa->precio_base_mensual, 2, '.', ''),
                    'priceCurrency' => 'EUR',
                    'availability' => 'https://schema.org/InStock',
                    'url' => route('registro.create'),
                ],
            ],
            [
                '@type' => 'FAQPage',
                'mainEntity' => collect($preguntas)->map(fn ($p) => [
                    '@type' => 'Question',
                    'name' => $p[0],
                    'acceptedAnswer' => ['@type' => 'Answer', 'text' => $p[1]],
                ])->all(),
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

    {{-- ============ HERO ============ --}}
    <div class="relative overflow-hidden bg-indigo-950 text-white">
        <div class="pointer-events-none absolute -right-40 -top-40 h-[34rem] w-[34rem] rounded-full bg-accent-500/20 blur-3xl"></div>
        <div class="pointer-events-none absolute -bottom-52 -left-32 h-[30rem] w-[30rem] rounded-full bg-indigo-500/30 blur-3xl"></div>

        <header class="relative">
            <div class="max-w-6xl mx-auto w-full px-4 sm:px-6 py-5 flex justify-between items-center">
                <a href="{{ route('home') }}" class="flex items-center">
                    <img src="{{ asset('images/logo-claro.png') }}" alt="{{ config('app.name') }}" class="h-7 sm:h-8 w-auto">
                </a>
                <nav class="flex items-center gap-3 sm:gap-5 text-sm">
                    <a href="#como-funciona" class="hidden md:inline text-indigo-200 hover:text-white">Cómo funciona</a>
                    <a href="#precio" class="hidden md:inline text-indigo-200 hover:text-white">Precio</a>
                    <a href="{{ route('contacto.create') }}" class="hidden md:inline text-indigo-200 hover:text-white">Contacto</a>
                    <a href="{{ route('guia.registro-jornada') }}" class="hidden lg:inline text-indigo-200 hover:text-white">Guía de registro de jornada</a>
                    @auth
                        <a href="{{ route('dashboard') }}" class="inline-flex items-center rounded-lg bg-accent-500 px-4 py-2 font-semibold text-indigo-950 hover:bg-accent-400 transition">Ir al panel</a>
                    @else
                        <a href="{{ route('login') }}" class="text-indigo-200 hover:text-white">Iniciar sesión</a>
                        <a href="{{ route('registro.create') }}" class="inline-flex items-center rounded-lg bg-accent-500 px-4 py-2 font-semibold text-indigo-950 hover:bg-accent-400 transition">Prueba gratis</a>
                    @endauth
                </nav>
            </div>
        </header>

        <section class="relative max-w-6xl mx-auto w-full px-4 sm:px-6 pt-10 sm:pt-16 pb-20 sm:pb-28 grid lg:grid-cols-2 gap-14 items-center">
            <div>
                <span class="inline-flex items-center gap-2 rounded-full border border-accent-500/40 bg-accent-500/10 px-3 py-1 text-xs font-medium text-accent-300">
                    <span class="h-1.5 w-1.5 rounded-full bg-accent-400"></span>
                    Pensado para el registro de jornada del RD-ley 8/2019
                </span>
                <h1 class="mt-6 font-display text-4xl sm:text-6xl font-bold leading-[1.05] tracking-tight">
                    Control horario sencillo<br>
                    <span class="text-accent-400">para tu empresa.</span>
                </h1>
                <p class="mt-6 text-base sm:text-lg text-indigo-100/90 max-w-lg">
                    Tu equipo ficha desde el navegador o con un PIN en una tablet. Tú ves quién
                    está trabajando, quién llega tarde y exportas el registro de jornada cuando
                    lo necesites. Sin papeles ni hojas de cálculo.
                </p>
                <div class="mt-9 flex flex-col sm:flex-row gap-3">
                    <a href="{{ route('registro.create') }}" class="inline-flex items-center justify-center rounded-lg bg-accent-500 px-7 py-3.5 font-semibold text-indigo-950 hover:bg-accent-400 transition">
                        Empieza gratis ahora
                    </a>
                    <a href="#como-funciona" class="inline-flex items-center justify-center rounded-lg border border-indigo-400/40 px-7 py-3.5 font-medium text-white hover:bg-white/5 transition">
                        Ver cómo funciona
                    </a>
                </div>
                <p class="mt-4 text-xs text-indigo-300">15 días gratis · Se pide tarjeta, pero no se cobra nada hasta que acaben · Sin permanencia</p>
            </div>

            {{-- Mockup del kiosco --}}
            <div class="relative mx-auto w-full max-w-sm lg:max-w-md" aria-hidden="true">
                <div class="rounded-[2rem] border border-indigo-700/70 bg-indigo-900 p-3 shadow-2xl shadow-black/40">
                    <div class="rounded-[1.5rem] bg-indigo-950 px-6 pb-7 pt-8 text-center">
                        <p class="text-xs text-indigo-300">Taller Ejemplo, S.L.</p>
                        <p class="mt-1 font-display text-lg font-semibold">Introduce tu PIN para fichar</p>
                        <div class="mt-5 flex justify-center gap-2.5">
                            @foreach (range(1, 6) as $i)
                                <span class="h-3 w-3 rounded-full border-2 {{ $i <= 4 ? 'border-accent-500 bg-accent-500' : 'border-indigo-400' }}"></span>
                            @endforeach
                        </div>
                        <div class="mx-auto mt-6 grid max-w-[15rem] grid-cols-3 gap-2.5">
                            @foreach ([1,2,3,4,5,6,7,8,9] as $n)
                                <span class="flex h-11 items-center justify-center rounded-xl bg-indigo-900 font-display text-lg font-semibold">{{ $n }}</span>
                            @endforeach
                            <span class="flex h-11 items-center justify-center rounded-xl bg-indigo-900 text-xs text-indigo-300">Borrar</span>
                            <span class="flex h-11 items-center justify-center rounded-xl bg-indigo-900 font-display text-lg font-semibold">0</span>
                            <span class="flex h-11 items-center justify-center rounded-xl bg-indigo-900 text-indigo-300">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="h-5 w-5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 19l-7-7 7-7m-7 7h18" /></svg>
                            </span>
                        </div>
                    </div>
                </div>

                <div class="absolute -right-3 -top-5 sm:-right-8 flex items-center gap-3 rounded-2xl bg-white px-4 py-3 text-slate-900 shadow-xl">
                    <span class="flex h-9 w-9 items-center justify-center rounded-full bg-accent-500 text-white">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" class="h-5 w-5"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" /></svg>
                    </span>
                    <span class="text-left">
                        <span class="block text-sm font-semibold">Hola, Ana</span>
                        <span class="block text-xs text-slate-500">Entrada registrada · 08:02</span>
                    </span>
                </div>

                <div class="absolute -bottom-6 -left-3 sm:-left-10 w-48 rounded-2xl bg-white p-3 text-slate-900 shadow-xl">
                    <p class="text-[0.65rem] font-semibold uppercase tracking-wide text-slate-400">Equipo ahora mismo</p>
                    <ul class="mt-2 space-y-1.5 text-xs">
                        <li class="flex items-center justify-between"><span>Ana</span><span class="rounded-full bg-accent-100 px-2 py-0.5 font-medium text-accent-800">Trabajando</span></li>
                        <li class="flex items-center justify-between"><span>Luis</span><span class="rounded-full bg-indigo-100 px-2 py-0.5 font-medium text-indigo-700">Vacaciones</span></li>
                        <li class="flex items-center justify-between"><span>Marta</span><span class="rounded-full bg-slate-100 px-2 py-0.5 font-medium text-slate-600">Fuera</span></li>
                    </ul>
                </div>
            </div>
        </section>
    </div>

    <main>
        {{-- ============ DATOS CONCRETOS (nada de cifras de relleno) ============ --}}
        <section class="border-b border-slate-100 bg-white" aria-label="Datos clave">
            <dl class="max-w-6xl mx-auto w-full px-4 sm:px-6 py-8 grid gap-6 sm:grid-cols-2 lg:grid-cols-4 text-sm">
                @foreach ([
                    ['Fichajes que no se editan', 'Ni se borran desde la aplicación. Los errores se corrigen aparte, con motivo y autor.'],
                    ['Datos aislados por empresa', 'Cada empresa solo ve lo suyo, y las contraseñas y los PIN no se guardan en claro.'],
                    ['Precio publicado', 'La tarifa completa está en esta página. Sin permanencia: cancelas cuando quieras.'],
                ] + (array_filter([config('legal.titular.nif'), config('legal.titular.domicilio'), config('legal.titular.email')])
                    ? [3 => ['Titular identificado', config('legal.titular.nombre').', con sus datos en el aviso legal.']]
                    : []) as [$titulo, $texto])
                    <div>
                        <dt class="font-semibold text-indigo-950">{{ $titulo }}</dt>
                        <dd class="mt-1 text-slate-600">{{ $texto }}</dd>
                    </div>
                @endforeach
            </dl>
        </section>

        {{-- ============ CÓMO FUNCIONA ============ --}}
        <section id="como-funciona" class="scroll-mt-4 max-w-6xl mx-auto w-full px-4 sm:px-6 py-20 sm:py-28">
            <div class="max-w-xl">
                <p class="text-sm font-semibold uppercase tracking-wider text-accent-600">Cómo funciona</p>
                <h2 class="mt-3 font-display text-3xl sm:text-4xl font-bold tracking-tight text-indigo-950">Empezar son tres pasos</h2>
            </div>

            <ol class="mt-14 grid gap-10 md:grid-cols-3">
                @foreach ([
                    ['Date de alta', 'Crea tu empresa con tu correo o con Google. Pruebas 15 días gratis y decides después.'],
                    ['Dale un PIN a cada empleado', 'Les das de alta con su DNI. Cada uno recibe un PIN de 6 dígitos para fichar, sin contraseñas que recordar.'],
                    ['Pon la tablet en la entrada', 'Abre el enlace del kiosco en cualquier tablet u ordenador fijo. Fichan con el PIN y tú lo ves en tiempo real.'],
                ] as $i => [$titulo, $texto])
                    <li class="relative">
                        <span class="font-display text-7xl font-extrabold leading-none text-indigo-100">{{ $i + 1 }}</span>
                        <h3 class="-mt-3 font-display text-xl font-semibold text-indigo-950">{{ $titulo }}</h3>
                        <p class="mt-2 text-slate-600">{{ $texto }}</p>
                    </li>
                @endforeach
            </ol>
        </section>

        {{-- ============ REGISTRO INMUTABLE ============ --}}
        <section class="relative overflow-hidden bg-indigo-950 text-white">
            <div class="pointer-events-none absolute -left-40 top-0 h-96 w-96 rounded-full bg-accent-500/15 blur-3xl"></div>
            <div class="relative max-w-6xl mx-auto w-full px-4 sm:px-6 py-20 sm:py-28 grid lg:grid-cols-2 gap-14 items-center">
                <div>
                    <p class="text-sm font-semibold uppercase tracking-wider text-accent-400">Registro inalterable</p>
                    <h2 class="mt-3 font-display text-3xl sm:text-4xl font-bold tracking-tight">Un registro que no se puede reescribir</h2>
                    <p class="mt-5 text-indigo-100/90 max-w-lg">
                        Un fichaje, una vez guardado, no se puede editar ni borrar desde la aplicación,
                        tampoco por el administrador. Si hay un error, se añade una <strong class="text-white">corrección</strong>
                        al lado, con su motivo y su autor. El original siempre se conserva.
                    </p>
                    <p class="mt-4 text-indigo-100/90 max-w-lg">
                        La ley exige conservar el registro cuatro años: aquí los fichajes no se borran. Y los datos de cada empresa están aislados del resto.
                    </p>
                </div>

                <div class="rounded-2xl bg-white p-5 sm:p-6 text-slate-900 shadow-2xl shadow-black/30" aria-hidden="true">
                    <div class="flex items-center gap-3 rounded-xl border border-slate-200 p-4">
                        <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-slate-100 text-slate-500">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" class="h-5 w-5"><path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z" /></svg>
                        </span>
                        <div class="min-w-0 flex-1">
                            <p class="text-sm font-semibold">Entrada · 08:12</p>
                            <p class="text-xs text-slate-500">Registro original — bloqueado</p>
                        </div>
                        <span class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-medium text-slate-600">Original</span>
                    </div>

                    <div class="ml-5 h-6 border-l-2 border-dashed border-accent-500"></div>

                    <div class="flex items-center gap-3 rounded-xl border border-accent-500/50 bg-accent-50 p-4">
                        <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-accent-500 text-white">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="h-5 w-5"><path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931z" /></svg>
                        </span>
                        <div class="min-w-0 flex-1">
                            <p class="text-sm font-semibold">Corrección → 08:00</p>
                            <p class="text-xs text-slate-600">Motivo: olvidó fichar al llegar · por Marta (admin)</p>
                        </div>
                        <span class="rounded-full bg-accent-100 px-2.5 py-1 text-xs font-medium text-accent-800">Vigente</span>
                    </div>

                    <p class="mt-4 text-center text-xs text-slate-500">Las horas trabajadas usan la corrección. El original queda en el historial.</p>
                </div>
            </div>
        </section>

        {{-- ============ FUNCIONES (bento) ============ --}}
        <section class="max-w-6xl mx-auto w-full px-4 sm:px-6 py-20 sm:py-28">
            <div class="max-w-xl">
                <p class="text-sm font-semibold uppercase tracking-wider text-accent-600">Todo en uno</p>
                <h2 class="mt-3 font-display text-3xl sm:text-4xl font-bold tracking-tight text-indigo-950">Lo que necesitas para llevar el control, sin hojas de cálculo</h2>
            </div>

            <div class="mt-12 grid gap-5 lg:grid-cols-3">
                <div class="lg:col-span-2 rounded-2xl border border-slate-200 bg-slate-50 p-7">
                    <h3 class="font-display text-xl font-semibold text-indigo-950">Quién está trabajando, ahora mismo</h3>
                    <p class="mt-2 max-w-md text-slate-600">Un panel que te dice quién ha fichado, quién está de vacaciones o de baja y quién aún no ha llegado.</p>
                    <div class="mt-6 grid gap-2 sm:grid-cols-2" aria-hidden="true">
                        <div class="flex items-center justify-between rounded-lg bg-white px-4 py-3 text-sm shadow-sm"><span>Ana García</span><span class="rounded-full bg-accent-100 px-2.5 py-0.5 text-xs font-medium text-accent-800">Trabajando · 08:02</span></div>
                        <div class="flex items-center justify-between rounded-lg bg-white px-4 py-3 text-sm shadow-sm"><span>Luis Pérez</span><span class="rounded-full bg-indigo-100 px-2.5 py-0.5 text-xs font-medium text-indigo-700">Vacaciones</span></div>
                        <div class="flex items-center justify-between rounded-lg bg-white px-4 py-3 text-sm shadow-sm"><span>Marta Ruiz</span><span class="rounded-full bg-amber-100 px-2.5 py-0.5 text-xs font-medium text-amber-800">Baja médica</span></div>
                        <div class="flex items-center justify-between rounded-lg bg-white px-4 py-3 text-sm shadow-sm"><span>Pablo Soto</span><span class="rounded-full bg-slate-100 px-2.5 py-0.5 text-xs font-medium text-slate-600">Fuera</span></div>
                    </div>
                </div>

                <div class="rounded-2xl bg-indigo-950 p-7 text-white">
                    <h3 class="font-display text-xl font-semibold">Avisos de retrasos y horas de más</h3>
                    <p class="mt-2 text-indigo-100/90">Asigna un horario a cada empleado y el panel te marca solo quién llega tarde o se pasa de hora.</p>
                    <div class="mt-6 space-y-2 text-sm" aria-hidden="true">
                        <div class="flex items-center justify-between rounded-lg bg-indigo-900 px-4 py-2.5"><span>Entrada esperada</span><span class="font-semibold">09:00</span></div>
                        <div class="flex items-center justify-between rounded-lg bg-rose-500/15 px-4 py-2.5 text-rose-200"><span>Entró a las 09:25</span><span class="font-semibold">Retraso</span></div>
                    </div>
                </div>

                <div class="rounded-2xl border border-slate-200 p-7">
                    <h3 class="font-display text-xl font-semibold text-indigo-950">Vacaciones y bajas</h3>
                    <p class="mt-2 text-slate-600">El empleado lo pide desde su cuenta, tú lo apruebas o rechazas. Todo queda registrado.</p>
                </div>

                <div class="rounded-2xl border border-slate-200 p-7">
                    <h3 class="font-display text-xl font-semibold text-indigo-950">Informes cuando los pidas</h3>
                    <p class="mt-2 text-slate-600">Descarga los fichajes en el formato que te hagan falta.</p>
                    <div class="mt-5 flex flex-wrap gap-2" aria-hidden="true">
                        <span class="rounded-md bg-accent-100 px-3 py-1 text-xs font-semibold text-accent-800">CSV</span>
                        <span class="rounded-md bg-accent-100 px-3 py-1 text-xs font-semibold text-accent-800">Excel</span>
                        <span class="rounded-md bg-accent-100 px-3 py-1 text-xs font-semibold text-accent-800">PDF</span>
                    </div>
                </div>

                <div class="rounded-2xl border border-slate-200 p-7">
                    <h3 class="font-display text-xl font-semibold text-indigo-950">Cada empresa, su espacio</h3>
                    <p class="mt-2 text-slate-600">Los datos de tu empresa son solo tuyos: aislados del resto, siempre.</p>
                </div>
            </div>
        </section>

        {{-- ============ LA LEY, SIN EXAGERACIONES ============ --}}
        <section id="ley" class="scroll-mt-4 max-w-6xl mx-auto w-full px-4 sm:px-6 py-20 sm:py-28">
            <div class="max-w-2xl">
                <p class="text-sm font-semibold uppercase tracking-wider text-accent-600">La ley, sin exageraciones</p>
                <h2 class="mt-3 font-display text-3xl sm:text-4xl font-bold tracking-tight text-indigo-950">Qué exige hoy y qué está por venir</h2>
                <p class="mt-4 text-slate-600">
                    Hay muchas webs que meten prisa con "la nueva ley". Esto es lo que hay, explicado sin
                    alarmismo. Está actualizado a octubre de 2026.
                </p>
            </div>

            <div class="mt-12 grid gap-6 lg:grid-cols-2">
                <div class="rounded-2xl border border-slate-200 p-6 sm:p-8">
                    <h3 class="font-display text-xl font-semibold text-indigo-950">Lo que exige la ley hoy</h3>
                    <ul class="mt-4 space-y-3 text-sm text-slate-600">
                        <li class="flex gap-3"><span class="mt-1 h-1.5 w-1.5 shrink-0 rounded-full bg-accent-500"></span>Registro diario de la jornada de cada persona trabajadora, con la hora de inicio y de fin (artículo 34.9 del Estatuto de los Trabajadores, desde 2019).</li>
                        <li class="flex gap-3"><span class="mt-1 h-1.5 w-1.5 shrink-0 rounded-full bg-accent-500"></span>Conservarlo cuatro años, a disposición de los trabajadores, sus representantes y la Inspección de Trabajo.</li>
                        <li class="flex gap-3"><span class="mt-1 h-1.5 w-1.5 shrink-0 rounded-full bg-accent-500"></span>La ley no impone un formato: puede ser en papel o digital.</li>
                    </ul>
                </div>
                <div class="rounded-2xl border border-slate-200 bg-slate-50 p-6 sm:p-8">
                    <h3 class="font-display text-xl font-semibold text-indigo-950">Lo que está en preparación</h3>
                    <p class="mt-4 text-sm text-slate-600">
                        Según medios especializados, el Ministerio de Trabajo tramita un reglamento para que el registro
                        sea digital, a prueba de manipulación y consultable a distancia por la Inspección. A principios de
                        octubre de 2026 todavía no constaba aprobado ni publicado en el BOE (comprueba el BOE por tu cuenta).
                    </p>
                    <p class="mt-3 text-sm text-slate-600">
                        {{ config('app.name') }} ya funciona así: registro digital, fichajes que no se editan y correcciones con
                        su rastro. Si el reglamento cambia algo, lo adaptaremos.
                    </p>
                </div>
            </div>

            <p class="mt-6 text-sm text-slate-500">
                Más detalle en la <a href="{{ route('guia.registro-jornada') }}" class="font-medium text-indigo-600 hover:underline">guía del registro de jornada</a>
                y en <a href="{{ route('guia.registro-digital') }}" class="font-medium text-indigo-600 hover:underline">el punto de situación de la reforma</a>.
            </p>
        </section>

        {{-- ============ PARA QUIÉN ES (Y PARA QUIÉN NO) ============ --}}
        <section class="bg-slate-50 border-y border-slate-100">
            <div class="max-w-6xl mx-auto w-full px-4 sm:px-6 py-20 sm:py-24 grid gap-10 lg:grid-cols-2">
                <div>
                    <p class="text-sm font-semibold uppercase tracking-wider text-accent-600">Para quién es</p>
                    <h2 class="mt-3 font-display text-3xl font-bold tracking-tight text-indigo-950">Lo que sí hace bien</h2>
                    <ul class="mt-5 space-y-3 text-sm text-slate-600">
                        <li class="flex gap-3"><span class="mt-1 h-1.5 w-1.5 shrink-0 rounded-full bg-accent-500"></span>Pymes y autónomos con empleados que necesitan un registro de jornada claro, sin complicarse.</li>
                        <li class="flex gap-3"><span class="mt-1 h-1.5 w-1.5 shrink-0 rounded-full bg-accent-500"></span>Centros donde se ficha en una tablet compartida (taller, tienda, oficina, almacén).</li>
                        <li class="flex gap-3"><span class="mt-1 h-1.5 w-1.5 shrink-0 rounded-full bg-accent-500"></span>Empleados sin correo: entran con su DNI o NIE.</li>
                    </ul>
                </div>
                <div>
                    <p class="text-sm font-semibold uppercase tracking-wider text-slate-500">Para quién no (todavía)</p>
                    <h2 class="mt-3 font-display text-3xl font-bold tracking-tight text-indigo-950">Lo que no hace</h2>
                    <ul class="mt-5 space-y-3 text-sm text-slate-600">
                        <li class="flex gap-3"><span class="mt-1 h-1.5 w-1.5 shrink-0 rounded-full bg-slate-400"></span>No es un ERP ni un programa de nóminas: las nóminas se suben en PDF, no se calculan.</li>
                        <li class="flex gap-3"><span class="mt-1 h-1.5 w-1.5 shrink-0 rounded-full bg-slate-400"></span>No tiene geolocalización, reconocimiento facial ni app nativa para el móvil.</li>
                        <li class="flex gap-3"><span class="mt-1 h-1.5 w-1.5 shrink-0 rounded-full bg-slate-400"></span>No funciona sin internet, y no gestiona cuadrantes de turnos rotativos.</li>
                    </ul>
                </div>
            </div>
        </section>

        {{-- ============ PRECIO ============ --}}
        <section id="precio" class="scroll-mt-4 bg-slate-50 border-t border-slate-100">
            <div class="max-w-5xl mx-auto w-full px-4 sm:px-6 py-20 sm:py-28 grid lg:grid-cols-2 gap-12 items-center">
                <div>
                    <p class="text-sm font-semibold uppercase tracking-wider text-accent-600">Precio</p>
                    <h2 class="mt-3 font-display text-3xl sm:text-4xl font-bold tracking-tight text-indigo-950">Un único plan, sin sorpresas</h2>
                    <p class="mt-4 text-slate-600 max-w-md">
                        Pruebas 15 días gratis. Después pagas la cuota base y solo cobramos de
                        más si tu plantilla crece. Sin permanencia ni letra pequeña.
                    </p>

                    <table class="mt-8 w-full max-w-md text-sm">
                        <caption class="mb-2 text-left font-semibold text-indigo-950">Cuánto pagarías al mes</caption>
                        <tbody class="divide-y divide-slate-200 border-y border-slate-200">
                            @foreach ([$tarifa->empleados_incluidos, 10, 20, 50] as $n)
                                <tr>
                                    <td class="py-2.5 text-slate-600">{{ $n }} empleados</td>
                                    <td class="py-2.5 text-right font-semibold text-indigo-950">{{ number_format($tarifa->calcularPrecioMensual($n), 2, ',', '.') }} €</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="relative overflow-hidden rounded-3xl bg-indigo-950 p-8 sm:p-10 text-white shadow-xl">
                    <div class="pointer-events-none absolute -right-20 -top-20 h-56 w-56 rounded-full bg-accent-500/25 blur-3xl"></div>
                    <div class="relative">
                        <span class="inline-flex rounded-full bg-accent-500 px-3 py-1 text-xs font-bold text-indigo-950">15 días gratis</span>
                        <p class="mt-5 flex items-baseline gap-1">
                            <span class="font-display text-6xl font-extrabold tracking-tight">{{ $tarifa->precioBaseTexto() }}€</span>
                            <span class="text-indigo-300">/mes</span>
                        </p>
                        <p class="mt-1 text-sm text-indigo-300">+ {{ number_format((float) $tarifa->precio_empleado_extra, 2) }}€/mes por cada empleado adicional</p>

                        <ul class="mt-7 space-y-3 text-sm text-indigo-50">
                            @foreach ([
                                'Incluye '.$tarifa->empleados_incluidos.' empleados',
                                'Kiosco con PIN y fichaje web ilimitados',
                                'Ausencias, horarios e incidencias incluidos',
                                'Exportación CSV, Excel y PDF',
                                'Cancela cuando quieras',
                            ] as $punto)
                                <li class="flex gap-3">
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" class="mt-0.5 h-5 w-5 shrink-0 text-accent-400"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" /></svg>
                                    {{ $punto }}
                                </li>
                            @endforeach
                        </ul>

                        <a href="{{ route('registro.create') }}" class="mt-9 flex w-full items-center justify-center rounded-lg bg-accent-500 px-6 py-3.5 font-semibold text-indigo-950 hover:bg-accent-400 transition">
                            Empezar prueba gratis
                        </a>
                        <p class="mt-3 text-center text-xs text-indigo-300">Se pide tarjeta; no se cobra nada durante los 15 días.</p>
                    </div>
                </div>
            </div>
        </section>

        {{-- ============ ATENCIÓN AL CLIENTE ============ --}}
        <section id="atencion" class="scroll-mt-4 bg-indigo-950 text-white">
            <div class="max-w-6xl mx-auto w-full px-4 sm:px-6 py-16 sm:py-20 grid gap-8 lg:grid-cols-5 lg:items-center">
                <div class="lg:col-span-3">
                    <p class="text-sm font-semibold uppercase tracking-wider text-accent-400">Atención al cliente</p>
                    <h2 class="mt-3 font-display text-3xl sm:text-4xl font-bold tracking-tight">Atención personal, en español</h2>
                    <p class="mt-4 max-w-xl text-indigo-100/90">
                        Detrás de {{ config('app.name') }} hay un equipo que te atiende en persona y en español. Si tienes
                        una duda antes de empezar o una incidencia con tu cuenta, escríbenos. Si es una emergencia
                        (por ejemplo, tu equipo no puede fichar), indícalo y la atendemos con prioridad.
                    </p>
                </div>
                <div class="lg:col-span-2 flex flex-col gap-3">
                    <a href="{{ route('contacto.create') }}" class="inline-flex items-center justify-center rounded-lg bg-accent-500 px-6 py-3.5 font-semibold text-indigo-950 hover:bg-accent-400 transition">
                        Escribir al equipo
                    </a>
                    <a href="mailto:{{ config('legal.titular.email') }}" class="inline-flex items-center justify-center rounded-lg border border-indigo-400/40 px-6 py-3.5 font-medium text-white hover:bg-white/5 transition break-all">
                        {{ config('legal.titular.email') }}
                    </a>
                </div>
            </div>
        </section>

        {{-- ============ PREGUNTAS FRECUENTES ============ --}}
        <section id="preguntas" class="scroll-mt-4 max-w-3xl mx-auto w-full px-4 sm:px-6 py-20 sm:py-24">
            <p class="text-sm font-semibold uppercase tracking-wider text-accent-600">Preguntas frecuentes</p>
            <h2 class="mt-3 font-display text-3xl sm:text-4xl font-bold tracking-tight text-indigo-950">Lo que suelen preguntar antes de empezar</h2>

            <div class="mt-8 divide-y divide-slate-200 border-y border-slate-200">
                @foreach ($preguntas as [$pregunta, $respuesta])
                    <details class="group py-4">
                        <summary class="flex cursor-pointer list-none items-center justify-between gap-4 font-semibold text-indigo-950">
                            {{ $pregunta }}
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="h-5 w-5 shrink-0 text-slate-400 transition group-open:rotate-180"><path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" /></svg>
                        </summary>
                        <p class="mt-3 text-slate-600 leading-relaxed">{{ $respuesta }}</p>
                    </details>
                @endforeach
            </div>

            <p class="mt-6 text-sm text-slate-500">
                ¿Quieres saber qué exige exactamente la ley?
                <a href="{{ route('guia.registro-jornada') }}" class="font-medium text-indigo-600 hover:underline">Lee la guía del registro de jornada</a>.
            </p>
        </section>

        {{-- ============ CTA FINAL ============ --}}
        <section class="max-w-6xl mx-auto w-full px-4 sm:px-6 py-20">
            <div class="relative overflow-hidden rounded-3xl bg-indigo-950 px-6 py-16 text-center text-white sm:px-12">
                <div class="pointer-events-none absolute left-1/2 top-0 h-64 w-96 -translate-x-1/2 -translate-y-1/2 rounded-full bg-accent-500/30 blur-3xl"></div>
                <h2 class="relative font-display text-3xl sm:text-4xl font-bold tracking-tight">¿Listo para dejar de fichar en papel?</h2>
                <p class="relative mx-auto mt-4 max-w-lg text-indigo-100/90">Da de alta tu empresa en menos de dos minutos y prueba gratis 15 días.</p>
                <a href="{{ route('registro.create') }}" class="relative mt-8 inline-flex items-center justify-center rounded-lg bg-accent-500 px-8 py-3.5 font-semibold text-indigo-950 hover:bg-accent-400 transition">
                    Empieza gratis ahora
                </a>
            </div>
        </section>
    </main>

    <footer class="bg-indigo-950">
        <div class="max-w-6xl mx-auto w-full px-4 sm:px-6 py-10 flex flex-col gap-6 sm:flex-row sm:items-center sm:justify-between">
            <div class="self-start">
                <img src="{{ asset('images/logo-claro.png') }}" alt="{{ config('app.name') }}" class="h-6 w-auto">
                <p class="mt-3 text-xs text-indigo-300">
                    {{ config('app.name') }} es un producto de {{ config('legal.titular.nombre') }}
                    @if (config('legal.titular.email'))
                        · <a href="mailto:{{ config('legal.titular.email') }}" class="hover:text-white hover:underline">{{ config('legal.titular.email') }}</a>
                    @endif
                </p>
            </div>
            <div class="flex flex-wrap gap-x-6 gap-y-2 text-sm text-indigo-300">
                <a href="{{ route('guia.registro-jornada') }}" class="hover:text-white hover:underline">Registro de jornada</a>
                <a href="{{ route('contacto.create') }}" class="hover:text-white hover:underline">Contacto</a>
                <a href="{{ route('legal.aviso-legal') }}" class="hover:text-white hover:underline">Aviso legal</a>
                <a href="{{ route('legal.terminos') }}" class="hover:text-white hover:underline">Términos</a>
                <a href="{{ route('legal.privacidad') }}" class="hover:text-white hover:underline">Privacidad</a>
                <a href="{{ route('legal.encargo-tratamiento') }}" class="hover:text-white hover:underline">Encargo de tratamiento</a>
                <a href="{{ route('legal.cookies') }}" class="hover:text-white hover:underline">Cookies</a>
            </div>
        </div>
    </footer>
</body>
</html>
