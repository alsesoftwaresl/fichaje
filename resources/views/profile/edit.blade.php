{{-- Perfil: datos, contraseña, verificación en dos pasos y descarga de los datos personales. --}}
<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold text-slate-900">Perfil</h2>
    </x-slot>

    <div class="max-w-xl space-y-6">
        <x-card class="p-6">
            @include('profile.partials.update-profile-information-form')
        </x-card>

        <x-card class="p-6">
            @include('profile.partials.update-password-form')
        </x-card>

        <x-card class="p-6">
            @include('profile.partials.dos-pasos')
        </x-card>

        {{-- Derecho de acceso y portabilidad (arts. 15 y 20 RGPD). --}}
        <x-card class="p-6">
            <h2 class="text-lg font-medium text-slate-900">Tus datos</h2>
            <p class="mt-1 text-sm text-slate-500">
                Descarga todo lo que la aplicación guarda sobre ti (perfil, fichajes con sus correcciones, ausencias
                y la lista de tus nóminas) en un archivo que puedes abrir o llevarte a otro sitio. Para corregir o
                borrar algo, habla con tu empresa: es la responsable de tus datos.
            </p>
            <a href="{{ route('profile.datos') }}" class="mt-4 inline-block text-sm font-medium text-indigo-600 underline hover:text-indigo-500">Descargar mis datos</a>
        </x-card>
    </div>
</x-app-layout>
