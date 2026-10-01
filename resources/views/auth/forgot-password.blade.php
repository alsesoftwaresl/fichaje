<x-guest-layout>
    <h1 class="text-lg font-semibold text-slate-900 mb-1">¿Olvidaste tu contraseña?</h1>
    <p class="text-sm text-slate-500 mb-6">
        Indícanos tu correo electrónico y te enviaremos un enlace para restablecerla.
    </p>

    <!-- Session Status -->
    <x-auth-session-status class="mb-4" :status="session('status')" />

    <form method="POST" action="{{ route('password.email') }}" class="space-y-4">
        @csrf

        <!-- Email Address -->
        <div>
            <x-input-label for="email" value="Correo electrónico" />
            <x-text-input id="email" class="block mt-1 w-full" type="email" name="email" :value="old('email')" required autofocus />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <x-primary-button class="w-full justify-center">
            Enviar enlace de restablecimiento
        </x-primary-button>
    </form>
</x-guest-layout>
