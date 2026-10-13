{{--
    Cabecera común de las páginas públicas (portada, funciones, guías, contacto), con menú
    de escritorio y menú desplegable en el móvil.
    Props: $ancho (ancho máximo del contenido) y $fondo (clase de color de fondo; en la
    portada va vacío porque ya tiene el suyo).
--}}
@props(['ancho' => 'max-w-6xl', 'fondo' => 'bg-indigo-950'])

@php
    $enlaces = [
        ['Cómo funciona', route('home').'#como-funciona', false],
        ['Funciones', route('funciones'), request()->routeIs('funciones')],
        ['Precio', route('home').'#precio', false],
        ['Contacto', route('contacto.create'), request()->routeIs('contacto.create')],
    ];
@endphp

<header x-data="{ abierto: false }" class="relative z-30 {{ $fondo }}">
    <div class="{{ $ancho }} mx-auto flex w-full items-center justify-between gap-3 px-4 py-4 sm:px-6 sm:py-5">
        <a href="{{ route('home') }}" class="flex shrink-0 items-center" aria-label="{{ config('app.name') }}, inicio">
            <img src="{{ asset('images/logo-claro.png') }}" alt="{{ config('app.name') }}" width="150" height="26" class="h-6 w-auto sm:h-8">
        </a>

        {{-- Escritorio --}}
        <nav class="hidden items-center gap-6 text-sm md:flex" aria-label="Principal">
            @foreach ($enlaces as [$texto, $url, $activo])
                <a href="{{ $url }}" @if ($activo) aria-current="page" @endif
                    class="{{ $activo ? 'text-white' : 'text-indigo-200 hover:text-white' }}">{{ $texto }}</a>
            @endforeach
            @auth
                <a href="{{ route('dashboard') }}" class="inline-flex items-center rounded-lg bg-accent-500 px-4 py-2 font-semibold text-indigo-950 transition hover:bg-accent-400">Ir al panel</a>
            @else
                <a href="{{ route('login') }}" class="text-indigo-200 hover:text-white">Iniciar sesión</a>
                <a href="{{ route('registro.create') }}" class="inline-flex items-center rounded-lg bg-accent-500 px-4 py-2 font-semibold text-indigo-950 transition hover:bg-accent-400">Prueba gratis</a>
            @endauth
        </nav>

        {{-- Móvil: botón principal + menú --}}
        <div class="flex items-center gap-2 md:hidden">
            @auth
                <a href="{{ route('dashboard') }}" class="inline-flex items-center rounded-lg bg-accent-500 px-3 py-2 text-sm font-semibold text-indigo-950">Ir al panel</a>
            @else
                <a href="{{ route('registro.create') }}" class="inline-flex items-center rounded-lg bg-accent-500 px-3 py-2 text-sm font-semibold text-indigo-950">Prueba gratis</a>
            @endauth
            <button type="button" @click="abierto = !abierto" :aria-expanded="abierto.toString()" aria-controls="menu-movil" aria-label="Abrir el menú"
                class="inline-flex h-10 w-10 items-center justify-center rounded-lg text-indigo-100 hover:bg-white/10 focus:outline-none focus-visible:ring-2 focus-visible:ring-accent-400">
                <svg x-show="!abierto" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="h-6 w-6" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5M3.75 17.25h16.5" /></svg>
                <svg x-show="abierto" x-cloak xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="h-6 w-6" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" /></svg>
            </button>
        </div>
    </div>

    <nav id="menu-movil" x-show="abierto" x-cloak x-transition.opacity class="border-t border-indigo-800/70 bg-indigo-950 px-4 pb-5 pt-3 md:hidden" aria-label="Menú móvil">
        <ul class="space-y-1 text-base">
            @foreach ($enlaces as [$texto, $url, $activo])
                <li><a href="{{ $url }}" @click="abierto = false" class="block rounded-lg px-3 py-3 {{ $activo ? 'bg-white/10 text-white' : 'text-indigo-100 hover:bg-white/5' }}">{{ $texto }}</a></li>
            @endforeach
            @guest
                <li><a href="{{ route('login') }}" class="block rounded-lg px-3 py-3 text-indigo-100 hover:bg-white/5">Iniciar sesión</a></li>
            @endguest
        </ul>
    </nav>
</header>
