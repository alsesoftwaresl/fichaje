<x-guest-layout>
    <h1 class="text-lg font-semibold text-slate-900 mb-1">Elige tu contraseña</h1>
    <p class="text-sm text-slate-500 mb-6">
        Has entrado con una contraseña temporal. Elige la tuya para seguir; la usarás
        a partir de ahora junto con tu DNI.
    </p>

    <form method="POST" action="{{ route('password.forzar.store') }}" class="space-y-4">
        @csrf

        <div>
            <x-input-label for="password" value="Nueva contraseña" />
            <x-text-input id="password" class="block mt-1 w-full" type="password" name="password" required autofocus autocomplete="new-password" />
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <div>
            <x-input-label for="password_confirmation" value="Repite la contraseña" />
            <x-text-input id="password_confirmation" class="block mt-1 w-full" type="password" name="password_confirmation" required autocomplete="new-password" />
        </div>

        <x-primary-button class="w-full justify-center">Guardar y continuar</x-primary-button>
    </form>

    <form method="POST" action="{{ route('logout') }}" class="mt-4 text-center">
        @csrf
        <button type="submit" class="text-sm text-slate-500 hover:text-slate-700 hover:underline">Cerrar sesión</button>
    </form>
</x-guest-layout>
