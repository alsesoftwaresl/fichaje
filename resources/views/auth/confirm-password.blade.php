<x-guest-layout>
    <h1 class="text-lg font-semibold text-slate-900 mb-1">Confirma tu contraseña</h1>
    <p class="text-sm text-slate-500 mb-6">
        Esta es un área segura de la aplicación. Confirma tu contraseña antes de continuar.
    </p>

    <form method="POST" action="{{ route('password.confirm') }}" class="space-y-4">
        @csrf

        <!-- Password -->
        <div>
            <x-input-label for="password" value="Contraseña" />

            <x-text-input id="password" class="block mt-1 w-full"
                            type="password"
                            name="password"
                            required autocomplete="current-password" />

            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <x-primary-button class="w-full justify-center">
            Confirmar
        </x-primary-button>
    </form>
</x-guest-layout>
