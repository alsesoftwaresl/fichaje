<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title }} — {{ config('app.name') }}</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-slate-50 text-slate-900 antialiased font-sans">
    <div class="max-w-2xl mx-auto px-6 py-10">
        <a href="{{ url('/') }}" class="text-sm text-indigo-600 hover:underline">&larr; Volver</a>

        <div class="mt-4 mb-6 rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
            <strong>Documento plantilla.</strong> Este texto es un borrador de partida y
            <strong>no constituye asesoramiento legal</strong>. Debe ser revisado y adaptado por un
            abogado antes de usarse en producción.
        </div>

        <div class="bg-white border border-slate-200 rounded-xl shadow-sm p-8">
            <h1 class="text-2xl font-bold mb-6 text-slate-900">{{ $title }}</h1>

            <div class="text-sm leading-relaxed text-slate-700 space-y-4">
                {{ $slot }}
            </div>
        </div>
    </div>
</body>
</html>
