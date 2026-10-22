{{-- Añadir la salida que el empleado olvidó fichar (ver SalidaOlvidadaController). --}}
<div
    x-data="{
        open: false,
        accion: '',
        fecha: '',
    }"
    @abrir-salida.window="open = true; accion = $event.detail.accion; fecha = $event.detail.fecha"
    x-show="open"
    x-cloak
    x-transition.opacity
    class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 px-4"
>
    <div @click.outside="open = false" class="bg-white rounded-xl shadow-lg border border-slate-200 max-w-md w-full p-6">
        <h3 class="text-lg font-semibold text-slate-900 mb-4">Añadir salida olvidada</h3>

        <form :action="accion" method="POST" class="space-y-4">
            @csrf

            <div>
                <x-input-label value="Fecha y hora de salida" />
                <input type="datetime-local" name="fecha_hora" x-model="fecha" required
                    class="block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
            </div>

            <div>
                <x-input-label value="Motivo" />
                <textarea name="motivo" required rows="3" maxlength="500"
                    class="block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                    placeholder="Ej: olvidó fichar la salida; confirmado con el empleado"></textarea>
            </div>

            <p class="text-xs text-slate-500">
                Se añade como un fichaje nuevo, marcado como registrado por la empresa, con tu
                nombre y el motivo. La entrada no se modifica.
            </p>

            <div class="flex justify-end gap-2 pt-2">
                <x-secondary-button type="button" @click="open = false">Cancelar</x-secondary-button>
                <x-primary-button type="submit">Añadir salida</x-primary-button>
            </div>
        </form>
    </div>
</div>
