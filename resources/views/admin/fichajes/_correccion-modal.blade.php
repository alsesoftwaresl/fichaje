<div
    x-data="{
        open: false,
        fichajeId: null,
        fecha: '',
    }"
    @abrir-correccion.window="open = true; fichajeId = $event.detail.id; fecha = $event.detail.fecha"
    x-show="open"
    x-cloak
    x-transition.opacity
    class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 px-4"
>
    <div @click.outside="open = false" class="bg-white rounded-xl shadow-lg border border-slate-200 max-w-md w-full p-6">
        <h3 class="text-lg font-semibold text-slate-900 mb-4">Corregir fichaje</h3>

        <form :action="`/admin/fichajes/${fichajeId}/correcciones`" method="POST" class="space-y-4">
            @csrf

            <div>
                <x-input-label value="Fecha y hora correcta" />
                <input type="datetime-local" name="fecha_hora_corregida" x-model="fecha" required
                    class="block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
            </div>

            <div>
                <x-input-label value="Motivo de la corrección" />
                <textarea name="motivo" required rows="3"
                    class="block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                    placeholder="Ej: el empleado olvidó fichar la salida"></textarea>
            </div>

            <p class="text-xs text-slate-500">
                El fichaje original no se modifica: se guardará esta corrección como un registro
                aparte, trazable, tal y como exige el registro de jornada.
            </p>

            <div class="flex justify-end gap-2 pt-2">
                <x-secondary-button type="button" @click="open = false">Cancelar</x-secondary-button>
                <x-primary-button type="submit">Guardar corrección</x-primary-button>
            </div>
        </form>
    </div>
</div>
