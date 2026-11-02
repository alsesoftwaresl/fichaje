{{-- Segundo paso del inicio de sesión (DosPasosController): código de la app o de recuperación. --}}
<x-guest-layout>
    <h1 class="text-lg font-semibold text-slate-900 mb-1">Verificación en dos pasos</h1>
    <p class="text-sm text-slate-500 mb-6">
        Abre tu app de autenticación y escribe el código de 6 cifras de Achrono.
    </p>

    <form method="POST" action="{{ route('dos-pasos.verificar') }}" class="space-y-4" x-data="{ recuperacion: false }">
        @csrf

        <div>
            <x-input-label for="codigo" x-text="recuperacion ? 'Código de recuperación' : 'Código de 6 cifras'" value="Código de 6 cifras" />
            <input id="codigo" name="codigo" type="text" required autofocus autocomplete="one-time-code"
                :inputmode="recuperacion ? 'text' : 'numeric'" inputmode="numeric"
                :placeholder="recuperacion ? 'XXXX-XXXX' : '123456'" placeholder="123456"
                class="mt-1 block w-full rounded-lg border-slate-300 text-center text-lg tracking-widest shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
            <x-input-error :messages="$errors->get('codigo')" class="mt-2" />
        </div>

        <x-primary-button class="w-full justify-center">Entrar</x-primary-button>

        <button type="button" class="w-full text-center text-sm text-slate-500 underline hover:text-slate-700"
            @click="recuperacion = ! recuperacion">
            <span x-show="! recuperacion">¿No tienes el móvil? Usa un código de recuperación</span>
            <span x-show="recuperacion" x-cloak>Usar el código de la app</span>
        </button>
    </form>

    <p class="mt-6 text-center text-sm">
        <a href="{{ route('login') }}" class="text-slate-500 underline hover:text-slate-700">Volver a iniciar sesión</a>
    </p>
</x-guest-layout>
