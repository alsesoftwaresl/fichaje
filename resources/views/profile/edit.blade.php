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
    </div>
</x-app-layout>
