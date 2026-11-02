{{-- Tarjeta blanca con borde: el contenedor estándar de las pantallas del panel. --}}
<div {{ $attributes->merge(['class' => 'bg-white border border-slate-200 rounded-xl shadow-sm']) }}>
    {{ $slot }}
</div>
