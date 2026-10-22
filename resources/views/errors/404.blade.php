<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <meta name="theme-color" content="#1b1b54">
    <title>Página no encontrada — {{ config('app.name') }}</title>
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('images/favicon-32.png') }}">
    {{-- Estilos en línea: la página de error no debe depender de nada compilado. --}}
    <style>
        *{box-sizing:border-box}
        body{margin:0;min-height:100vh;display:flex;align-items:center;justify-content:center;padding:24px;
             font-family:system-ui,-apple-system,"Segoe UI",Roboto,sans-serif;color:#fff;
             background:radial-gradient(60rem 30rem at 85% -10%,rgba(47,198,165,.2),transparent 60%),#070719}
        main{max-width:32rem;text-align:center}
        img{display:block;height:2rem;width:auto;margin:0 auto 2rem}
        .codigo{color:#47d3b3;font-weight:700;letter-spacing:.08em;font-size:.9rem}
        h1{font-size:clamp(1.8rem,6vw,2.6rem);margin:.5rem 0 1rem;line-height:1.1}
        p{margin:0 0 1.75rem;color:rgba(255,255,255,.75);line-height:1.6}
        .enlaces{display:flex;flex-wrap:wrap;gap:.6rem;justify-content:center}
        a.boton{display:inline-block;padding:.75rem 1.4rem;border-radius:10px;font-weight:600;text-decoration:none;background:#2fc6a5;color:#1b1b54}
        a.sec{display:inline-block;padding:.75rem 1.2rem;border-radius:10px;text-decoration:none;color:#fff;border:1px solid rgba(255,255,255,.25)}
    </style>
</head>
<body>
    <main>
        <img src="{{ asset('images/logo-claro.png') }}" alt="{{ config('app.name') }}">
        <p class="codigo">ERROR 404</p>
        <h1>No encontramos esa página</h1>
        <p>Puede que el enlace esté mal escrito o que la página ya no exista. Estas son algunas direcciones útiles:</p>
        <div class="enlaces">
            <a class="boton" href="{{ route('home') }}">Ir al inicio</a>
            <a class="sec" href="{{ route('funciones') }}">Funciones</a>
            <a class="sec" href="{{ route('guia.registro-jornada') }}">Guía del registro de jornada</a>
            <a class="sec" href="{{ route('contacto.create') }}">Contacto</a>
        </div>
    </main>
</body>
</html>
