<aside class="hidden lg:flex lg:w-64 lg:flex-col lg:fixed lg:inset-y-0 bg-indigo-950">
    <div class="flex items-center gap-2 px-5 h-16 shrink-0 border-b border-indigo-900">
        <a href="{{ route('dashboard') }}" class="flex items-center">
            <img src="{{ asset('images/logo-claro.png') }}" alt="{{ config('app.name') }}" class="h-6 w-auto">
        </a>
    </div>

    @php
        $ausenciasPendientesNav = Auth::user()->esAdminEmpresa()
            ? \App\Models\Ausencia::where('estado', 'pendiente')->count()
            : 0;

        // Aviso numérico de incidencias de hoy (retrasos, sin fichar, sin
        // cerrar, horas de más). Cacheado 60 s: se muestra en todas las
        // páginas y no hace falta recalcularlo en cada una.
        // Nóminas nuevas (aún sin abrir) del propio usuario.
        $nominasNuevasNav = Auth::user()->empresa_id
            ? \App\Models\Nomina::where('user_id', Auth::id())->whereNull('descargada_en')->count()
            : 0;

        $incidenciasHoyNav = Auth::user()->esAdminEmpresa()
            ? \Illuminate\Support\Facades\Cache::remember(
                'incidencias-hoy-'.Auth::user()->empresa_id,
                60,
                fn () => \App\Services\IncidenciasCalculador::delDia(Auth::user()->empresa_id)->count()
            )
            : 0;
    @endphp

    <nav class="flex-1 px-3 py-4 space-y-1">
        @if (Auth::user()->esAdminEmpresa())
            <x-sidebar-link :href="route('admin.panel.index')" :active="request()->routeIs('admin.panel.*')">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" class="w-5 h-5 shrink-0">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 12l2-2m0 0 7-7 7 7M5 10v10a1 1 0 0 0 1 1h3m10-11 2 2m-2-2v10a1 1 0 0 1-1 1h-3m-6 0a1 1 0 0 0 1-1v-4a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1v4a1 1 0 0 0 1 1m-6 0h6" />
                </svg>
                Panel
            </x-sidebar-link>
        @endif

        @if (Auth::user()->esEmpleado() || Auth::user()->esAdminEmpresa())
            <x-sidebar-link :href="route('fichajes.mis')" :active="request()->routeIs('fichajes.mis')">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" class="w-5 h-5 shrink-0">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6l4 2" />
                    <circle cx="12" cy="12" r="9" stroke-linecap="round" stroke-linejoin="round" />
                </svg>
                Mis fichajes
            </x-sidebar-link>
            <x-sidebar-link :href="route('ausencias.mis')" :active="request()->routeIs('ausencias.mis')">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" class="w-5 h-5 shrink-0">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5" />
                </svg>
                Mis ausencias
            </x-sidebar-link>
            <x-sidebar-link :href="route('nominas.mis')" :active="request()->routeIs('nominas.mis')">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" class="w-5 h-5 shrink-0">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" />
                </svg>
                <span class="flex-1">Mis nóminas</span>
                @if ($nominasNuevasNav > 0)
                    <span class="flex h-5 min-w-5 items-center justify-center rounded-full bg-accent-500 px-1 text-xs font-semibold text-indigo-950">{{ $nominasNuevasNav }}</span>
                @endif
            </x-sidebar-link>
        @endif

        @if (Auth::user()->puedeGestionarNominas())
            <x-sidebar-link :href="route('nominas.gestion.index')" :active="request()->routeIs('nominas.gestion.*')">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" class="w-5 h-5 shrink-0">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5m-13.5-9L12 3m0 0 4.5 4.5M12 3v13.5" />
                </svg>
                Nóminas empresa
            </x-sidebar-link>
        @endif

        @if (Auth::user()->esAdminEmpresa())
            <x-sidebar-link :href="route('admin.fichajes.index')" :active="request()->routeIs('admin.fichajes.*')">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" class="w-5 h-5 shrink-0">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M8 6h10M8 12h10M8 18h10M4 6h.01M4 12h.01M4 18h.01" />
                </svg>
                Fichajes empresa
            </x-sidebar-link>
            <x-sidebar-link :href="route('admin.incidencias.index')" :active="request()->routeIs('admin.incidencias.*')">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" class="w-5 h-5 shrink-0">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z" />
                </svg>
                <span class="flex-1">Control horario</span>
                @if ($incidenciasHoyNav > 0)
                    <span class="flex h-5 min-w-5 items-center justify-center rounded-full bg-amber-400 px-1 text-xs font-semibold text-indigo-950">{{ $incidenciasHoyNav }}</span>
                @endif
            </x-sidebar-link>
            <x-sidebar-link :href="route('admin.empleados.index')" :active="request()->routeIs('admin.empleados.*')">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" class="w-5 h-5 shrink-0">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z" />
                </svg>
                Empleados
            </x-sidebar-link>
            <x-sidebar-link :href="route('admin.ausencias.index')" :active="request()->routeIs('admin.ausencias.*')">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" class="w-5 h-5 shrink-0">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5" />
                </svg>
                <span class="flex-1">Ausencias</span>
                @if ($ausenciasPendientesNav > 0)
                    <span class="flex h-5 min-w-5 items-center justify-center rounded-full bg-rose-500 px-1 text-xs font-semibold text-white">{{ $ausenciasPendientesNav }}</span>
                @endif
            </x-sidebar-link>
            <x-sidebar-link :href="route('admin.facturacion.index')" :active="request()->routeIs('admin.facturacion.*')">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" class="w-5 h-5 shrink-0">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 8.25h19.5M2.25 9h19.5m-16.5 5.25h6m-6 2.25h3m-3.75 3h15a2.25 2.25 0 0 0 2.25-2.25V6.75A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25v10.5A2.25 2.25 0 0 0 4.5 19.5Z" />
                </svg>
                Facturación
            </x-sidebar-link>
        @endif

        @if (Auth::user()->esSuperAdmin())
            <x-sidebar-link :href="route('super-admin.empresas.index')" :active="request()->routeIs('super-admin.empresas.*')">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" class="w-5 h-5 shrink-0">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 21h16.5M4.5 3h15M5.25 3v18m13.5-18v18M9 6.75h1.5m-1.5 3h1.5m-1.5 3h1.5m3-6H15m-1.5 3H15m-1.5 3H15M9 21v-3.375c0-.621.504-1.125 1.125-1.125h3.75c.621 0 1.125.504 1.125 1.125V21" />
                </svg>
                Empresas
            </x-sidebar-link>
            <x-sidebar-link :href="route('super-admin.tarifas.edit')" :active="request()->routeIs('super-admin.tarifas.*')">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" class="w-5 h-5 shrink-0">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v12m-3-2.818.879.659c1.171.879 3.07.879 4.242 0 1.172-.879 1.172-2.303 0-3.182C13.536 12.219 12.768 12 12 12c-.725 0-1.45-.22-2.003-.659-1.106-.879-1.106-2.303 0-3.182.553-.44 1.278-.659 2.003-.659.725 0 1.45.22 2.003.659l.621.502" />
                </svg>
                Tarifas
            </x-sidebar-link>
        @endif
    </nav>

    <div class="border-t border-indigo-900 p-3">
        <div class="flex items-center gap-3 rounded-lg px-3 py-2">
            <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-accent-500 text-xs font-bold text-indigo-950">
                {{ Str::of(Auth::user()->name)->substr(0, 1)->upper() }}
            </span>
            <div class="min-w-0 flex-1">
                <p class="truncate text-sm font-medium text-white">{{ Auth::user()->name }}</p>
                <p class="truncate text-xs text-indigo-300">{{ Auth::user()->email }}</p>
            </div>
        </div>
        <div class="mt-1 space-y-1">
            <a href="{{ route('profile.edit') }}" class="flex items-center gap-3 rounded-lg px-3 py-2 text-sm text-slate-300 hover:bg-indigo-900/60 hover:text-white transition">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" class="w-5 h-5 shrink-0">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M17.982 18.725A7.488 7.488 0 0 0 12 15.75a7.488 7.488 0 0 0-5.982 2.975m11.963 0a9 9 0 1 0-11.963 0m11.963 0A8.966 8.966 0 0 1 12 21a8.966 8.966 0 0 1-5.982-2.275M15 9.75a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                </svg>
                Perfil
            </a>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="flex w-full items-center gap-3 rounded-lg px-3 py-2 text-sm text-slate-300 hover:bg-indigo-900/60 hover:text-white transition">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" class="w-5 h-5 shrink-0">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0 0 13.5 3h-6a2.25 2.25 0 0 0-2.25 2.25v13.5A2.25 2.25 0 0 0 7.5 21h6a2.25 2.25 0 0 0 2.25-2.25V15M12 9l-3 3m0 0 3 3m-3-3h12.75" />
                    </svg>
                    Cerrar sesión
                </button>
            </form>
        </div>
    </div>
</aside>

<!-- Navegación móvil -->
<header x-data="{ open: false }" class="lg:hidden sticky top-0 z-40 flex items-center justify-between h-16 px-4 bg-indigo-950 border-b border-indigo-900">
    <a href="{{ route('dashboard') }}" class="flex items-center">
        <img src="{{ asset('images/logo-claro.png') }}" alt="{{ config('app.name') }}" class="h-6 w-auto">
    </a>
    <button @click="open = ! open" class="text-slate-300 hover:text-white">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="w-6 h-6">
            <path :class="{ hidden: open }" stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16" />
            <path :class="{ hidden: !open }" class="hidden" stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
        </svg>
    </button>

    <div x-show="open" x-cloak class="absolute inset-x-0 top-16 bg-indigo-950 border-b border-indigo-900 px-3 py-3 space-y-1">
        @if (Auth::user()->esAdminEmpresa())
            <x-sidebar-link :href="route('admin.panel.index')" :active="request()->routeIs('admin.panel.*')">Panel</x-sidebar-link>
        @endif
        @if (Auth::user()->esEmpleado() || Auth::user()->esAdminEmpresa())
            <x-sidebar-link :href="route('fichajes.mis')" :active="request()->routeIs('fichajes.mis')">Mis fichajes</x-sidebar-link>
            <x-sidebar-link :href="route('ausencias.mis')" :active="request()->routeIs('ausencias.mis')">Mis ausencias</x-sidebar-link>
            <x-sidebar-link :href="route('nominas.mis')" :active="request()->routeIs('nominas.mis')">
                Mis nóminas
                @if ($nominasNuevasNav > 0)
                    <span class="ml-1 inline-flex h-5 min-w-5 items-center justify-center rounded-full bg-accent-500 px-1 text-xs font-semibold text-indigo-950">{{ $nominasNuevasNav }}</span>
                @endif
            </x-sidebar-link>
        @endif
        @if (Auth::user()->puedeGestionarNominas())
            <x-sidebar-link :href="route('nominas.gestion.index')" :active="request()->routeIs('nominas.gestion.*')">Nóminas empresa</x-sidebar-link>
        @endif
        @if (Auth::user()->esAdminEmpresa())
            <x-sidebar-link :href="route('admin.fichajes.index')" :active="request()->routeIs('admin.fichajes.*')">Fichajes empresa</x-sidebar-link>
            <x-sidebar-link :href="route('admin.incidencias.index')" :active="request()->routeIs('admin.incidencias.*')">
                Control horario
                @if ($incidenciasHoyNav > 0)
                    <span class="ml-1 inline-flex h-5 min-w-5 items-center justify-center rounded-full bg-amber-400 px-1 text-xs font-semibold text-indigo-950">{{ $incidenciasHoyNav }}</span>
                @endif
            </x-sidebar-link>
            <x-sidebar-link :href="route('admin.empleados.index')" :active="request()->routeIs('admin.empleados.*')">Empleados</x-sidebar-link>
            <x-sidebar-link :href="route('admin.ausencias.index')" :active="request()->routeIs('admin.ausencias.*')">
                Ausencias
                @if ($ausenciasPendientesNav > 0)
                    <span class="ml-1 inline-flex h-5 min-w-5 items-center justify-center rounded-full bg-rose-500 px-1 text-xs font-semibold text-white">{{ $ausenciasPendientesNav }}</span>
                @endif
            </x-sidebar-link>
            <x-sidebar-link :href="route('admin.facturacion.index')" :active="request()->routeIs('admin.facturacion.*')">Facturación</x-sidebar-link>
        @endif
        @if (Auth::user()->esSuperAdmin())
            <x-sidebar-link :href="route('super-admin.empresas.index')" :active="request()->routeIs('super-admin.empresas.*')">Empresas</x-sidebar-link>
            <x-sidebar-link :href="route('super-admin.tarifas.edit')" :active="request()->routeIs('super-admin.tarifas.*')">Tarifas</x-sidebar-link>
        @endif
        <x-sidebar-link :href="route('profile.edit')" :active="request()->routeIs('profile.edit')">Perfil</x-sidebar-link>
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="flex w-full items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium text-slate-300 hover:bg-indigo-900/60 hover:text-white transition">
                Cerrar sesión
            </button>
        </form>
    </div>
</header>
