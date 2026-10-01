<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
    <title>Kiosco — {{ $empresa->nombre }}</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-sans antialiased bg-slate-900 text-white h-screen overflow-hidden select-none">
    <div class="h-full flex flex-col items-center justify-center px-4 text-center">
        <div class="flex h-16 w-16 items-center justify-center rounded-full bg-rose-500/20 mb-6">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="w-8 h-8 text-rose-400">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z" />
            </svg>
        </div>
        <p class="text-sm text-slate-400 mb-1">{{ $empresa->nombre }}</p>
        <h1 class="text-xl font-semibold">Kiosco no disponible</h1>
        <p class="mt-2 text-sm text-slate-400 max-w-xs">
            Esta empresa no tiene una suscripción activa en este momento. Contacta con el
            administrador para reactivarla.
        </p>
    </div>
</body>
</html>
