@php
    // En el alta no hay $empleado todavía (se pasa null desde el controlador).
    $empleado ??= null;
    $dias = ['Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado', 'Domingo'];
    $diasSeleccionados = old('dias_laborables', $empleado->dias_laborables ?? [1, 2, 3, 4, 5]);
    $horaEntrada = old('hora_entrada_esperada', $empleado?->hora_entrada_esperada ? substr($empleado->hora_entrada_esperada, 0, 5) : '');
    $horaSalida = old('hora_salida_esperada', $empleado?->hora_salida_esperada ? substr($empleado->hora_salida_esperada, 0, 5) : '');
@endphp

<div class="space-y-4 border-t border-slate-200 pt-4">
    <div>
        <h3 class="font-medium text-slate-900">Horario esperado (opcional)</h3>
        <p class="text-xs text-slate-500 mt-1">
            Si lo rellenas, el panel avisará si este empleado llega tarde o hace más horas de las esperadas.
        </p>
    </div>

    <div class="grid sm:grid-cols-2 gap-4">
        <div>
            <x-input-label for="hora_entrada_esperada" value="Hora de entrada" />
            <x-text-input id="hora_entrada_esperada" name="hora_entrada_esperada" type="time" class="mt-1 block w-full" :value="$horaEntrada" />
            <x-input-error class="mt-2" :messages="$errors->get('hora_entrada_esperada')" />
        </div>
        <div>
            <x-input-label for="hora_salida_esperada" value="Hora de salida" />
            <x-text-input id="hora_salida_esperada" name="hora_salida_esperada" type="time" class="mt-1 block w-full" :value="$horaSalida" />
            <x-input-error class="mt-2" :messages="$errors->get('hora_salida_esperada')" />
        </div>
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
