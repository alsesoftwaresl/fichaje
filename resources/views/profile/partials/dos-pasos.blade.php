{{--
    Verificación en dos pasos en el perfil (DosPasosPerfilController): desactivada, a medio
    activar (QR + primer código) o activa (códigos de recuperación y desactivar).
--}}
@php
    $usuario = auth()->user();
    $pendiente = ! $usuario->tieneDosPasos() && $usuario->dos_pasos_secreto;
    $errores = $errors->getBag('dosPasos');
@endphp

<section id="dos-pasos">
    <header>
        <h2 class="text-lg font-medium text-slate-900">Verificación en dos pasos</h2>
        <p class="mt-1 text-sm text-slate-500">
            Además de la contraseña, al entrar se pide un código de tu móvil. Si alguien consigue tu
            contraseña, no podrá entrar sin él.
            @if ($usuario->esAdminEmpresa() || $usuario->esSuperAdmin())
                <strong class="font-medium text-slate-700">Muy recomendable para ti:</strong> desde tu cuenta se ven los datos de toda la plantilla.
            @endif
        </p>
    </header>

    @if (session('codigos_recuperacion'))
        <div class="mt-4 rounded-lg border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900">
            <p class="font-medium">Guarda estos códigos de recuperación en un lugar seguro</p>
            <p class="mt-1">Sirven para entrar si pierdes el móvil. Cada uno vale una sola vez y no se volverán a mostrar.</p>
            <ul class="mt-3 grid grid-cols-2 gap-2 font-mono text-base">
                @foreach (session('codigos_recuperacion') as $codigo)
                    <li>{{ $codigo }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @if ($usuario->tieneDosPasos())
        <div class="mt-4 flex items-center gap-2">
            <x-badge tone="success">Activada</x-badge>
            <span class="text-sm text-slate-500">
                desde el {{ $usuario->dos_pasos_confirmada_en->format('d/m/Y') }} ·
                quedan {{ count($usuario->dos_pasos_recuperacion ?? []) }} códigos de recuperación
            </span>
        </div>

        {{-- Un solo campo de contraseña; cada botón envía a su acción (formaction). --}}
        <form method="POST" action="{{ route('dos-pasos.desactivar') }}" class="mt-4 space-y-3">
            @csrf

            <div>
                <x-input-label for="password_dos_pasos" value="Tu contraseña (para confirmar)" />
                <x-text-input id="password_dos_pasos" name="password" type="password" class="mt-1 block w-full" autocomplete="current-password" />
                <x-input-error class="mt-2" :messages="$errores->get('password')" />
            </div>

            <div class="flex flex-wrap gap-3">
                <x-secondary-button type="submit" formaction="{{ route('dos-pasos.codigos') }}">Nuevos códigos de recuperación</x-secondary-button>
                <x-danger-button type="submit">Desactivar</x-danger-button>
            </div>
        </form>
    @elseif ($pendiente)
        @php $uri = App\Support\Totp::uri($usuario->dos_pasos_secreto, $usuario->email ?? $usuario->name, config('app.name')); @endphp

        <ol class="mt-4 list-decimal space-y-2 pl-5 text-sm text-slate-600">
            <li>Instala en tu móvil una app de autenticación (Google Authenticator, Microsoft Authenticator, Authy…).</li>
            <li>Escanea este código con ella. Si estás en el móvil, pulsa <a href="{{ $uri }}" class="underline">añadir a la app</a> o escribe la clave a mano.</li>
            <li>Escribe el código de 6 cifras que te muestre.</li>
        </ol>

        <div class="mt-4 flex flex-col items-start gap-4 sm:flex-row sm:items-center">
            <div class="h-44 w-44 rounded-lg border border-slate-200 bg-white p-2" x-data="codigoQr(@js($uri))"></div>
            <div class="text-sm">
                <p class="text-slate-500">Clave para escribir a mano:</p>
                <p class="mt-1 break-all font-mono text-slate-900">{{ trim(chunk_split($usuario->dos_pasos_secreto, 4, ' ')) }}</p>
            </div>
        </div>

        <form method="POST" action="{{ route('dos-pasos.confirmar') }}" class="mt-4 flex flex-wrap items-end gap-3">
            @csrf
            <div>
                <x-input-label for="codigo_confirmar" value="Código de 6 cifras" />
                <input id="codigo_confirmar" name="codigo" type="text" inputmode="numeric" autocomplete="one-time-code" required
                    class="mt-1 block w-40 rounded-lg border-slate-300 text-center tracking-widest shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
            </div>
            <x-primary-button>Activar</x-primary-button>
        </form>
        <x-input-error class="mt-2" :messages="$errores->get('codigo')" />

        <form method="POST" action="{{ route('dos-pasos.desactivar') }}" class="mt-3">
            @csrf
            <button class="text-sm text-slate-500 underline hover:text-slate-700">Cancelar</button>
        </form>
    @else
        <form method="POST" action="{{ route('dos-pasos.activar') }}" class="mt-4">
            @csrf
            <x-primary-button>Activar la verificación en dos pasos</x-primary-button>
        </form>

        @if (session('status') === 'dos-pasos-desactivada')
            <p class="mt-2 text-sm text-slate-500">Verificación en dos pasos desactivada.</p>
        @endif
    @endif
</section>
