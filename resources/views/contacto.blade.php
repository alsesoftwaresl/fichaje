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
                @if (session('enviado'))
                    <div role="status" class="rounded-xl border border-emerald-200 bg-emerald-50 p-5 text-emerald-900">
                        <p class="font-semibold">Mensaje enviado.</p>
                        <p class="mt-1 text-sm">Gracias por escribirnos. Te responderemos por correo electrónico.</p>
                    </div>
                @else
                    <form method="POST" action="{{ route('contacto.store') }}" class="space-y-5" novalidate>
                        @csrf

                        {{-- Trampa para bots: las personas no ven este campo. --}}
                        <div class="absolute -left-[9999px] h-0 w-0 overflow-hidden" aria-hidden="true">
                            <label for="web">No rellenar</label>
                            <input id="web" name="web" type="text" tabindex="-1" autocomplete="off">
                        </div>

                        <div class="grid gap-5 sm:grid-cols-2">
                            <div>
                                <label for="nombre" class="block text-sm font-medium text-slate-700">Nombre</label>
                                <input id="nombre" name="nombre" type="text" value="{{ old('nombre') }}" required maxlength="120" autocomplete="name"
                                    class="mt-1 block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                @error('nombre')<p class="mt-1 text-sm text-rose-600">{{ $message }}</p>@enderror
                            </div>
                            <div>
                                <label for="email" class="block text-sm font-medium text-slate-700">Correo electrónico</label>
                                <input id="email" name="email" type="email" value="{{ old('email') }}" required maxlength="255" autocomplete="email"
                                    class="mt-1 block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                @error('email')<p class="mt-1 text-sm text-rose-600">{{ $message }}</p>@enderror
                            </div>
                        </div>

                        <div class="grid gap-5 sm:grid-cols-2">
                            <div>
                                <label for="empresa" class="block text-sm font-medium text-slate-700">Empresa <span class="font-normal text-slate-400">(opcional)</span></label>
                                <input id="empresa" name="empresa" type="text" value="{{ old('empresa') }}" maxlength="160" autocomplete="organization"
                                    class="mt-1 block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                @error('empresa')<p class="mt-1 text-sm text-rose-600">{{ $message }}</p>@enderror
                            </div>
                            <div>
                                <label for="asunto" class="block text-sm font-medium text-slate-700">Motivo</label>
                                <select id="asunto" name="asunto" required
                                    class="mt-1 block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                    @foreach ($asuntos as $clave => $texto)
                                        <option value="{{ $clave }}" @selected(old('asunto', $asuntoInicial ?? 'informacion') === $clave)>{{ $texto }}</option>
                                    @endforeach
                                </select>
                                @error('asunto')<p class="mt-1 text-sm text-rose-600">{{ $message }}</p>@enderror
                            </div>
                        </div>

                        <div>
                            <label for="mensaje" class="block text-sm font-medium text-slate-700">Mensaje</label>
                            <textarea id="mensaje" name="mensaje" rows="6" required maxlength="4000"
                                class="mt-1 block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">{{ old('mensaje') }}</textarea>
                            @error('mensaje')<p class="mt-1 text-sm text-rose-600">{{ $message }}</p>@enderror
                        </div>

                        <div>
                            <label class="flex items-start gap-3 text-sm text-slate-600">
                                <input type="checkbox" name="acepta_privacidad" value="1" @checked(old('acepta_privacidad')) class="mt-0.5 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
                                <span>
                                    Acepto que {{ config('legal.titular.nombre') }} trate mis datos para responder a este mensaje, según la
                                    <a href="{{ route('legal.privacidad') }}" class="text-indigo-600 hover:underline">política de privacidad</a>.
                                </span>
                            </label>
                            @error('acepta_privacidad')<p class="mt-1 text-sm text-rose-600">{{ $message }}</p>@enderror
                        </div>

                        <button type="submit" class="inline-flex items-center justify-center rounded-lg bg-indigo-950 px-6 py-3 font-semibold text-white hover:bg-indigo-900 transition">
                            Enviar mensaje
                        </button>
                    </form>
                @endif
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
