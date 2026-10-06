<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold text-slate-900">Editar {{ $empleado->name }}</h2>
    </x-slot>

    <div class="max-w-lg">
        <x-card class="p-6">
            <form method="POST" action="{{ route('admin.empleados.update', $empleado) }}" class="space-y-4">
                @csrf
                @method('PATCH')

                <div>
                    <x-input-label for="name" value="Nombre" />
                    <x-text-input id="name" name="name" type="text" class="mt-1 block w-full" :value="old('name', $empleado->name)" required autofocus />
                    <x-input-error class="mt-2" :messages="$errors->get('name')" />
                </div>

                <div>
                    <x-input-label for="dni_nie" value="DNI/NIE" />
                    <x-text-input id="dni_nie" name="dni_nie" type="text" class="mt-1 block w-full uppercase" :value="old('dni_nie', $empleado->dni_nie)" required />
                    <p class="mt-1 text-xs text-slate-500">Es lo que usa para entrar a la web (junto con su contraseña).</p>
                    <x-input-error class="mt-2" :messages="$errors->get('dni_nie')" />
                </div>

                <div>
                    <x-input-label for="email" value="Correo electrónico (opcional)" />
                    <x-text-input id="email" name="email" type="email" class="mt-1 block w-full" :value="old('email', $empleado->email)" />
                    <p class="mt-1 text-xs text-slate-500">Solo hace falta si quieres que pueda recuperar su contraseña por email.</p>
                    <x-input-error class="mt-2" :messages="$errors->get('email')" />
                </div>

                <div>
                    <x-input-label for="password" value="Nueva contraseña (opcional)" />
                    <x-text-input id="password" name="password" type="password" class="mt-1 block w-full" />
                    <p class="mt-1 text-xs text-slate-500">Déjalo en blanco para no tocar la contraseña actual.</p>
                    <x-input-error class="mt-2" :messages="$errors->get('password')" />
                </div>

                <div>
                    <x-input-label for="password_confirmation" value="Confirmar nueva contraseña" />
                    <x-text-input id="password_confirmation" name="password_confirmation" type="password" class="mt-1 block w-full" />
                </div>

                @include('admin.empleados._permisos-fields')

                @include('admin.empleados._horario-fields')

                <div class="flex justify-end gap-2 pt-2">
                    <a href="{{ route('admin.empleados.index') }}">
                        <x-secondary-button type="button">Cancelar</x-secondary-button>
                    </a>
                    <x-primary-button type="submit">Guardar cambios</x-primary-button>
                </div>
            </form>
        </x-card>
    </div>
</x-app-layout>
