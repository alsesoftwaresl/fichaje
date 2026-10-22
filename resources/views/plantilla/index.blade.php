@php
    $preguntas = [
        ['¿Esta plantilla vale como registro de jornada?', 'La ley exige registrar el horario de inicio y de fin de la jornada de cada persona trabajadora y no impone un formato, así que una hoja en papel o en Excel se puede usar. Lo importante es que los datos sean fiables y se conserven cuatro años. Si tienes dudas sobre tu caso, consúltalo con tu asesoría laboral.'],
        ['¿Hay que firmarla?', 'La ley no obliga a firmar cada día. Es una práctica habitual que el trabajador firme al cerrar el mes para dejar constancia de su conformidad; por eso la plantilla deja una columna de observaciones y firma.'],
        ['¿Cuánto tiempo hay que guardar el registro?', 'Cuatro años, a disposición de la persona trabajadora, sus representantes legales y la Inspección de Trabajo y Seguridad Social.'],
        ['¿Cómo se calculan las horas extra?', 'La plantilla suma las horas trabajadas del mes. Para saber cuáles son extra hay que compararlas con la jornada pactada. Con '.config('app.name').' el panel marca solo las "horas de más" respecto al horario de cada empleado.'],
        ['¿Qué pasa si un turno acaba pasada la medianoche?', 'La fórmula de horas del Excel no contempla turnos que cruzan la medianoche. En ese caso anota las horas a mano en la columna de observaciones.'],
    ];
@endphp
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Plantilla de registro de jornada gratis (Excel y PDF) — {{ config('app.name') }}</title>
    <meta name="description" content="Descarga gratis una plantilla de registro diario de jornada en Excel (calcula las horas solo) o en PDF para imprimir. Sin registro ni correo. Qué dice la ley y cómo usarla.">
    <meta name="robots" content="index, follow, max-image-preview:large, max-snippet:-1">
    <link rel="canonical" href="{{ route('plantilla.index') }}">

    <meta property="og:type" content="website">
    <meta property="og:title" content="Plantilla de registro de jornada gratis (Excel y PDF)">
    <meta property="og:description" content="Excel con fórmulas o PDF para imprimir. Sin registro ni correo.">
    <meta property="og:url" content="{{ route('plantilla.index') }}">
    <meta property="og:image" content="{{ asset('images/og-image.png') }}">
    <meta property="og:locale" content="es_ES">
    <meta name="twitter:card" content="summary_large_image">

    <script type="application/ld+json">
    {!! json_encode([
        '@context' => 'https://schema.org',
        '@graph' => [
            [
                '@type' => 'WebPage',
                'name' => 'Plantilla de registro de jornada gratis (Excel y PDF)',
                'url' => route('plantilla.index'),
                'inLanguage' => 'es-ES',
                'isAccessibleForFree' => true,
            ],
            [
                '@type' => 'BreadcrumbList',
                'itemListElement' => [
                    ['@type' => 'ListItem', 'position' => 1, 'name' => 'Inicio', 'item' => route('home')],
                    ['@type' => 'ListItem', 'position' => 2, 'name' => 'Plantilla de registro de jornada', 'item' => route('plantilla.index')],
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
    <x-sitio.cabecera ancho="max-w-5xl" />

    <main class="max-w-5xl mx-auto w-full px-4 sm:px-6 py-12 sm:py-16">
        <nav aria-label="Migas de pan" class="text-sm text-slate-500">
            <a href="{{ route('home') }}" class="hover:underline">Inicio</a> <span aria-hidden="true">/</span> Plantilla de registro de jornada
        </nav>

        <h1 class="mt-6 font-display text-3xl sm:text-5xl font-bold tracking-tight text-indigo-950">Plantilla gratuita de registro de jornada</h1>
        <p class="mt-4 max-w-2xl text-lg text-slate-600">
            Descárgala en <strong>Excel</strong> (calcula las horas solo) o en <strong>PDF</strong> para imprimir.
            Sin registro y sin dejar tu correo.
        </p>

        {{-- Descarga --}}
        <section class="mt-10 rounded-2xl border border-slate-200 bg-slate-50 p-6 sm:p-8" x-data="{ mes: '{{ $mesActual }}' }">
            <h2 class="font-display text-xl font-semibold text-indigo-950">1. Elige el mes y descárgala</h2>
            <div class="mt-4 flex flex-col gap-4 sm:flex-row sm:items-end">
                <div>
                    <label for="mes" class="block text-sm font-medium text-slate-700">Mes</label>
                    <input id="mes" type="month" x-model="mes" min="2020-01" max="2099-12" class="mt-1 block rounded-lg border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                </div>
                <div class="flex flex-col gap-3 sm:flex-row">
                    <a :href="'{{ route('plantilla.excel') }}?mes=' + mes" href="{{ route('plantilla.excel') }}" class="inline-flex items-center justify-center gap-2 rounded-lg bg-accent-500 px-6 py-3 font-semibold text-indigo-950 transition hover:bg-accent-400">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="h-5 w-5" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3" /></svg>
                        Descargar en Excel
                    </a>
                    <a :href="'{{ route('plantilla.pdf') }}?mes=' + mes" href="{{ route('plantilla.pdf') }}" class="inline-flex items-center justify-center gap-2 rounded-lg border border-slate-300 bg-white px-6 py-3 font-semibold text-slate-800 transition hover:border-indigo-300">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="h-5 w-5" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3" /></svg>
                        Descargar en PDF
                    </a>
                </div>
            </div>

            {{-- Vista previa --}}
            <div class="mt-8 overflow-x-auto rounded-xl border border-slate-200 bg-white" aria-label="Vista previa de la plantilla">
                <table class="min-w-full text-center text-xs sm:text-sm">
                    <thead>
                        <tr class="bg-indigo-950 text-white">
                            <th class="px-3 py-2 text-left font-semibold">Día</th>
                            <th class="px-3 py-2 font-semibold">Entrada</th>
                            <th class="px-3 py-2 font-semibold">Salida</th>
                            <th class="px-3 py-2 font-semibold">Pausa (min)</th>
                            <th class="px-3 py-2 font-semibold">Horas</th>
                            <th class="px-3 py-2 font-semibold">Observaciones / firma</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 text-slate-600">
                        <tr><td class="px-3 py-2 text-left font-semibold text-slate-800">01 lun</td><td>08:00</td><td>16:30</td><td>30</td><td class="font-semibold text-slate-800">8,00</td><td class="text-slate-400">—</td></tr>
                        <tr><td class="px-3 py-2 text-left font-semibold text-slate-800">02 mar</td><td>08:05</td><td>16:35</td><td>30</td><td class="font-semibold text-slate-800">8,00</td><td class="text-slate-400">—</td></tr>
                        <tr><td class="px-3 py-2 text-left font-semibold text-slate-800">03 mié</td><td class="text-slate-300">hh:mm</td><td class="text-slate-300">hh:mm</td><td class="text-slate-300">min</td><td class="text-slate-300">se calcula</td><td></td></tr>
                    </tbody>
                </table>
            </div>
            <p class="mt-3 text-xs text-slate-500">Ejemplo con datos inventados para ver cómo funciona. Las plantillas se descargan en blanco.</p>
        </section>

        <section class="mt-12 grid gap-8 lg:grid-cols-2">
            <div>
                <h2 class="font-display text-2xl font-bold text-indigo-950">2. Cómo se usa</h2>
                <ol class="mt-4 list-decimal space-y-3 pl-5 text-slate-700">
                    <li>Rellena la empresa, el trabajador y su DNI o NIE.</li>
                    <li>Cada día, apunta la hora de entrada y de salida (por ejemplo 08:30) y los minutos de pausa si los hay.</li>
                    <li>En el Excel, las horas del día y el total del mes se calculan solas. En el PDF se suman a mano.</li>
                    <li>Al acabar el mes, que la persona trabajadora firme y guarda el registro cuatro años.</li>
                </ol>
                <p class="mt-4 text-sm text-slate-500">La fórmula del Excel no contempla turnos que cruzan la medianoche: en ese caso, anota las horas a mano.</p>
            </div>
            <div>
                <h2 class="font-display text-2xl font-bold text-indigo-950">Qué dice la ley, en corto</h2>
                <ul class="mt-4 space-y-3 text-slate-700">
                    <li class="flex gap-3"><span class="mt-2 h-1.5 w-1.5 shrink-0 rounded-full bg-accent-500"></span>Hay que registrar cada día la hora de inicio y de fin de la jornada de cada persona trabajadora.</li>
                    <li class="flex gap-3"><span class="mt-2 h-1.5 w-1.5 shrink-0 rounded-full bg-accent-500"></span>Se conserva cuatro años, a disposición de los trabajadores, sus representantes y la Inspección.</li>
                    <li class="flex gap-3"><span class="mt-2 h-1.5 w-1.5 shrink-0 rounded-full bg-accent-500"></span>La ley no impone un formato: puede ser en papel o digital.</li>
                </ul>
                <p class="mt-4 text-sm text-slate-500">Más detalle en la <a href="{{ route('guia.registro-jornada') }}" class="font-medium text-indigo-600 hover:underline">guía del registro de jornada</a>. Es información general, no asesoramiento jurídico.</p>
            </div>
        </section>

        <section class="mt-12 rounded-2xl bg-indigo-950 p-6 text-white sm:p-10">
            <h2 class="font-display text-2xl font-bold">Y cuando una hoja se te quede corta</h2>
            <p class="mt-3 max-w-2xl text-indigo-100/90">
                Un papel o un Excel se puede corregir sin dejar rastro, hay que sumarlo a mano y se pierde fácil. Si quieres que el
                equipo fiche en una tablet, que las horas y los retrasos salgan solos y que lo fichado no se pueda cambiar,
                {{ config('app.name') }} lo hace. 15 días gratis.
            </p>
            <div class="mt-6 flex flex-col gap-3 sm:flex-row">
                <a href="{{ route('registro.create') }}" class="inline-flex items-center justify-center rounded-lg bg-accent-500 px-6 py-3 font-semibold text-indigo-950 transition hover:bg-accent-400">Probar gratis</a>
                <a href="{{ route('funciones') }}" class="inline-flex items-center justify-center rounded-lg border border-indigo-400/40 px-6 py-3 font-medium text-white transition hover:bg-white/5">Ver las funciones</a>
            </div>
        </section>

        <section class="mt-12" aria-labelledby="faq-titulo">
            <h2 id="faq-titulo" class="font-display text-2xl font-bold text-indigo-950">Preguntas frecuentes</h2>
            <div class="mt-6 divide-y divide-slate-200 border-y border-slate-200">
                @foreach ($preguntas as [$pregunta, $respuesta])
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
    </main>

    <footer class="bg-indigo-950">
        <div class="max-w-5xl mx-auto w-full px-4 sm:px-6 py-8 flex flex-wrap gap-x-6 gap-y-2 text-sm text-indigo-300">
            <a href="{{ route('home') }}" class="hover:text-white hover:underline">Inicio</a>
            <a href="{{ route('funciones') }}" class="hover:text-white hover:underline">Funciones</a>
            <a href="{{ route('guia.registro-jornada') }}" class="hover:text-white hover:underline">Registro de jornada</a>
            <a href="{{ route('contacto.create') }}" class="hover:text-white hover:underline">Contacto</a>
            <a href="{{ route('legal.aviso-legal') }}" class="hover:text-white hover:underline">Aviso legal</a>
            <a href="{{ route('legal.privacidad') }}" class="hover:text-white hover:underline">Privacidad</a>
        </div>
    </footer>
</body>
</html>
