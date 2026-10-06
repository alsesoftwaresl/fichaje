<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold text-slate-900">Dar de alta empleado</h2>
    </x-slot>

    <div class="max-w-lg">
        <x-card class="p-6">
            <form method="POST" action="{{ route('admin.empleados.store') }}" class="space-y-4">
                @csrf

                <div>
                    <x-input-label for="name" value="Nombre" />
                    <x-text-input id="name" name="name" type="text" class="mt-1 block w-full" :value="old('name')" required autofocus />
                    <x-input-error class="mt-2" :messages="$errors->get('name')" />
                </div>

                <div>
                    <x-input-label for="dni_nie" value="DNI/NIE" />
                    <x-text-input id="dni_nie" name="dni_nie" type="text" class="mt-1 block w-full uppercase" :value="old('dni_nie')" required autofocus />
                    <p class="mt-1 text-xs text-slate-500">Es lo que usará para entrar a la web, junto con una contraseña. Para fichar en el kiosco usa su PIN, no hace falta nada de esto.</p>
                    <x-input-error class="mt-2" :messages="$errors->get('dni_nie')" />
                </div>

                <div>
                    <x-input-label for="email" value="Correo electrónico (opcional)" />
                    <x-text-input id="email" name="email" type="email" class="mt-1 block w-full" :value="old('email')" />
                    <p class="mt-1 text-xs text-slate-500">Solo hace falta si quieres que pueda recuperar su contraseña por email.</p>
                    <x-input-error class="mt-2" :messages="$errors->get('email')" />
                </div>

                <div>
                    <x-input-label for="password" value="Contraseña inicial (opcional)" />
                    <x-text-input id="password" name="password" type="password" class="mt-1 block w-full" />
                    <x-input-error class="mt-2" :messages="$errors->get('password')" />
                </div>

                <div>
                    <x-input-label for="password_confirmation" value="Confirmar contraseña" />
                    <x-text-input id="password_confirmation" name="password_confirmation" type="password" class="mt-1 block w-full" />
                </div>

                <label class="flex items-start gap-2 text-sm text-slate-700">
                    <input type="hidden" name="dar_acceso" value="0">
                    <input type="checkbox" name="dar_acceso" value="1"
                           class="mt-0.5 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500"
                           @checked(old('dar_acceso', '1') == '1')>
                    <span>
                        <span class="font-medium text-slate-900">Dar acceso a la web (si no escribes contraseña)</span>
                        <span class="block text-xs text-slate-500">
                            Genera una contraseña temporal que verás una sola vez, junto al PIN. El empleado entra con
                            su DNI y esa contraseña, y elige la suya la primera vez. Desde la web pide vacaciones, ve
                            sus fichajes y sus nóminas. Si lo desmarcas, solo podrá fichar por PIN en el kiosco.
                        </span>
                    </span>
                </label>

                @include('admin.empleados._permisos-fields')

                @include('admin.empleados._horario-fields')

                <div class="flex justify-end gap-2 pt-2">
                    <a href="{{ route('admin.empleados.index') }}">
                        <x-secondary-button type="button">Cancelar</x-secondary-button>
                    </a>
                    <x-primary-button type="submit">Dar de alta</x-primary-button>
                </div>
            </form>
        </x-card>
    </div>
</x-app-layout>
