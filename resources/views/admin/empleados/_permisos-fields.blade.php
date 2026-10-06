@php($empleado ??= null)

<div class="border-t border-slate-200 pt-4">
    <label class="flex items-start gap-2 text-sm text-slate-700">
        <input type="hidden" name="gestiona_nominas" value="0">
        <input type="checkbox" name="gestiona_nominas" value="1"
               class="mt-0.5 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500"
               @checked(old('gestiona_nominas', $empleado?->gestiona_nominas))>
        <span>
            <span class="font-medium text-slate-900">Puede subir y gestionar las nóminas (contable)</span>
            <span class="block text-xs text-slate-500">
                Podrá subir las nóminas de todos los empleados y borrarlas, sin ser administrador. Ve
                las nóminas de toda la empresa, así que actívalo solo para quien deba tener acceso.
            </span>
        </span>
    </label>
</div>
