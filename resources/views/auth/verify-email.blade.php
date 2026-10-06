<x-guest-layout>
    <h1 class="text-lg font-semibold text-slate-900 mb-1">Verifica tu correo</h1>
    <p class="text-sm text-slate-500 mb-6">
        Te hemos enviado un enlace de verificación a tu email. Haz clic en él para
        activar tu cuenta — si no lo encuentras, revisa la carpeta de spam.
    </p>

    @if (session('status') === 'verification-link-sent')
        <div class="mb-4 rounded-lg bg-emerald-50 border border-emerald-200 px-4 py-3 text-sm text-emerald-700">
            Te hemos enviado otro enlace de verificación al correo que diste al registrarte.
        </div>
    @elseif (session('status'))
        <div class="mb-4 rounded-lg bg-emerald-50 border border-emerald-200 px-4 py-3 text-sm text-emerald-700">
            {{ session('status') }}
        </div>
    @endif

    <div class="flex items-center justify-between">
        <form method="POST" action="{{ route('verification.send') }}">
            @csrf
            <x-primary-button type="submit">
                Reenviar enlace
            </x-primary-button>
        </form>

        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="text-sm text-slate-500 hover:text-slate-700 hover:underline">
                Cerrar sesión
            </button>
        </form>
    </div>
</x-guest-layout>
