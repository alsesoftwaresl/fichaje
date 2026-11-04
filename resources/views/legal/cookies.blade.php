{{-- Política de cookies (art. 22.2 LSSI). Solo hay cookies técnicas: no hace falta banner. --}}
<x-legal.layout :title="'Política de cookies'">
    <p>
        {{ config('app.name') }} solo usa cookies técnicas, necesarias para que la aplicación funcione. No usa
        cookies de analítica, publicidad ni de terceros, y por eso no te pide consentimiento (art. 22.2 de la
        Ley 34/2002, LSSI).
    </p>

    <ul class="list-disc pl-6 space-y-1">
        <li><strong>Sesión</strong> (achrono-session): mantiene tu sesión iniciada. Deja de valer al cerrar sesión o
            tras 2 horas sin actividad. Va cifrada.</li>
        <li><strong>XSRF-TOKEN</strong>: protege los formularios frente a envíos desde otras webs.</li>
        <li><strong>Recordarme</strong>: solo si marcas «Recuérdame» al entrar, para no tener que volver a
            escribir la contraseña en ese dispositivo.</li>
        <li><strong>Acceso durante el mantenimiento</strong>: solo si usas el enlace de acceso mientras la web
            está en mantenimiento.</li>
    </ul>

    <p>Además, el navegador guarda en tu dispositivo (no es una cookie) si has descartado el aviso de
    «Instalar app», para no volver a mostrártelo durante unos días.</p>
</x-legal.layout>
