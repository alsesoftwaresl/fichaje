<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <meta name="theme-color" content="#1b1b54">
    <title>{{ config('app.name') }} — Volvemos enseguida</title>
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('images/favicon-32.png') }}">
    {{-- Estilos en línea a propósito: en mantenimiento no debe depender de nada compilado. --}}
    <style>
        *{box-sizing:border-box}
        body{margin:0;min-height:100vh;display:flex;align-items:center;justify-content:center;padding:24px;
             font-family:system-ui,-apple-system,"Segoe UI",Roboto,sans-serif;color:#fff;
             background:radial-gradient(60rem 30rem at 85% -10%,rgba(47,198,165,.22),transparent 60%),
                        radial-gradient(50rem 30rem at -10% 110%,rgba(59,59,134,.45),transparent 60%),#070719}
        main{max-width:34rem;text-align:center}
        img{display:block;height:2.25rem;width:auto;margin:0 auto 2.5rem}
        .pill{display:inline-block;border:1px solid rgba(47,198,165,.4);background:rgba(47,198,165,.1);
              color:#6fe4c8;border-radius:999px;padding:.25rem .85rem;font-size:.8rem;font-weight:600}
        h1{font-size:clamp(1.9rem,6vw,2.8rem);line-height:1.1;margin:1.25rem 0 1rem;letter-spacing:-.02em}
        h1 span{color:#47d3b3}
        p{margin:0;color:rgba(255,255,255,.75);line-height:1.6}
    </style>
</head>
<body>
    <main>
        <img src="{{ asset('images/logo-claro.png') }}" alt="{{ config('app.name') }}">
        <span class="pill">Estamos preparando todo</span>
        <h1>Volvemos <span>muy pronto</span></h1>
        <p>{{ config('app.name') }} está en mantenimiento mientras terminamos los últimos ajustes.
           Vuelve a intentarlo dentro de un rato.</p>
    </main>
</body>
</html>
