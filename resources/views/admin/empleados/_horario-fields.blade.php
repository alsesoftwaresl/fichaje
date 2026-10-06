@php
    // En el alta no hay $empleado todavía (se pasa null desde el controlador).
    $empleado ??= null;
    $dias = ['Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado', 'Domingo'];
    $diasSeleccionados = old('dias_laborables', $empleado->dias_laborables ?? [1, 2, 3, 4, 5]);

    // Tramos iniciales: lo que se envió si el formulario falló, si no los del
    // empleado, si no un tramo vacío.
    $tramosIniciales = old('tramos', $empleado?->tramos() ?? []);
    $tramosIniciales = collect($tramosIniciales)
        ->map(fn ($t) => ['entrada' => $t['entrada'] ?? '', 'salida' => $t['salida'] ?? ''])
        ->values()
        ->all() ?: [['entrada' => '', 'salida' => '']];
@endphp

<div class="space-y-4 border-t border-slate-200 pt-4">
    <div>
        <h3 class="font-medium text-slate-900">Horario esperado (opcional)</h3>
        <p class="text-xs text-slate-500 mt-1">
            Si lo rellenas, el panel avisará si este empleado llega tarde, no ficha, deja el fichaje
            sin cerrar o hace más horas de las esperadas, y lo verás en Control horario. Si lo dejas
            en blanco, no se le controla ningún horario.
        </p>
    </div>

    <div x-data="{ tramos: @js($tramosIniciales) }" class="space-y-3">
        <template x-for="(tramo, i) in tramos" :key="i">
            <div class="rounded-lg border border-slate-200 p-3">
                <div class="mb-2 flex items-center justify-between">
                    <span class="text-xs font-medium uppercase tracking-wide text-slate-500"
                          x-text="tramos.length > 1 ? 'Tramo ' + (i + 1) : 'Jornada'"></span>
                    <button type="button" x-show="tramos.length > 1" @click="tramos.splice(i, 1)"
                            class="text-xs font-medium text-rose-600 hover:text-rose-500">Quitar</button>
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-sm font-medium text-slate-700">Entrada</label>
                        <input type="time" :name="'tramos[' + i + '][entrada]'" x-model="tramo.entrada"
                               class="mt-1 block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700">Salida</label>
                        <input type="time" :name="'tramos[' + i + '][salida]'" x-model="tramo.salida"
                               class="mt-1 block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    </div>
                </div>
            </div>
        </template>

        <button type="button" x-show="tramos.length < 3" @click="tramos.push({ entrada: '', salida: '' })"
                class="inline-flex items-center gap-1 text-sm font-medium text-indigo-600 hover:text-indigo-500">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="h-4 w-4"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
            Añadir otro tramo (turno partido)
        </button>

        <x-input-error :messages="$errors->get('tramos')" />
        @foreach ($errors->get('tramos.*') as $mensajes)
            <x-input-error :messages="$mensajes" />
        @endforeach
    </div>

    <div>
        <x-input-label value="Días laborables" />
        <div class="mt-1 flex flex-wrap gap-3">
            @foreach ($dias as $numero => $nombre)
                @php $numeroIso = $numero + 1; @endphp
                <label class="inline-flex items-center gap-1.5 text-sm text-slate-700">
                    <input type="checkbox" name="dias_laborables[]" value="{{ $numeroIso }}"
                        class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500"
                        @checked(in_array($numeroIso, $diasSeleccionados))>
                    {{ $nombre }}
                </label>
            @endforeach
        </div>
        <x-input-error class="mt-2" :messages="$errors->get('dias_laborables')" />
    </div>
</div>
