<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold text-slate-900">Dar de alta empresa</h2>
    </x-slot>

    <div class="max-w-lg">
        <x-card class="p-6">
            <form method="POST" action="{{ route('super-admin.empresas.store') }}" class="space-y-6">
                @csrf

                <div class="space-y-4">
                    <h3 class="font-medium text-slate-900">Datos de la empresa</h3>

                    <div>
                        <x-input-label for="nombre" value="Nombre de la empresa" />
                        <x-text-input id="nombre" name="nombre" type="text" class="mt-1 block w-full" :value="old('nombre')" required autofocus />
                        <x-input-error class="mt-2" :messages="$errors->get('nombre')" />
                    </div>

                    <div>
                        <x-input-label for="nif" value="NIF/CIF (opcional)" />
                        <x-text-input id="nif" name="nif" type="text" class="mt-1 block w-full" :value="old('nif')" />
                        <x-input-error class="mt-2" :messages="$errors->get('nif')" />
                    </div>

                    <div>
                        <x-input-label for="email_contacto" value="Email de contacto" />
                        <x-text-input id="email_contacto" name="email_contacto" type="email" class="mt-1 block w-full" :value="old('email_contacto')" required />
                        <x-input-error class="mt-2" :messages="$errors->get('email_contacto')" />
                    </div>
                </div>

                <div class="space-y-4 border-t border-slate-200 pt-4">
                    <h3 class="font-medium text-slate-900">Administrador de la empresa</h3>

                    <div>
                        <x-input-label for="admin_name" value="Nombre" />
                        <x-text-input id="admin_name" name="admin_name" type="text" class="mt-1 block w-full" :value="old('admin_name')" required />
                        <x-input-error class="mt-2" :messages="$errors->get('admin_name')" />
                    </div>

                    <div>
                        <x-input-label for="admin_email" value="Correo electrónico" />
                        <x-text-input id="admin_email" name="admin_email" type="email" class="mt-1 block w-full" :value="old('admin_email')" required />
                        <x-input-error class="mt-2" :messages="$errors->get('admin_email')" />
                    </div>

                    <div>
                        <x-input-label for="admin_password" value="Contraseña inicial" />
                        <x-text-input id="admin_password" name="admin_password" type="password" class="mt-1 block w-full" required />
                        <x-input-error class="mt-2" :messages="$errors->get('admin_password')" />
                    </div>

                    <div>
                        <x-input-label for="admin_password_confirmation" value="Confirmar contraseña" />
                        <x-text-input id="admin_password_confirmation" name="admin_password_confirmation" type="password" class="mt-1 block w-full" required />
                    </div>
                </div>

                <div class="space-y-3 border-t border-slate-200 pt-4">
                    <label class="flex items-start gap-2 text-sm text-slate-700">
                        <input type="checkbox" name="acepta_terminos" value="1" class="mt-1 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500" required>
                        <span>
                            La empresa acepta los
                            <a href="{{ route('legal.terminos') }}" target="_blank" class="text-indigo-600 hover:underline">términos y condiciones</a>
                            (v{{ $versionTerminos }}).
                        </span>
                    </label>
                    <x-input-error :messages="$errors->get('acepta_terminos')" />

                    <label class="flex items-start gap-2 text-sm text-slate-700">
                        <input type="checkbox" name="acepta_encargo" value="1" class="mt-1 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500" required>
                        <span>
                            La empresa acepta el
                            <a href="{{ route('legal.encargo-tratamiento') }}" target="_blank" class="text-indigo-600 hover:underline">contrato de encargo de tratamiento</a>
                            (v{{ $versionEncargo }}).
                        </span>
                    </label>
                    <x-input-error :messages="$errors->get('acepta_encargo')" />
                </div>

                <div class="flex justify-end gap-2">
                    <a href="{{ route('super-admin.empresas.index') }}">
                        <x-secondary-button type="button">Cancelar</x-secondary-button>
                    </a>
                    <x-primary-button type="submit">Dar de alta</x-primary-button>
                </div>
            </form>
        </x-card>
    </div>
</x-app-layout>
