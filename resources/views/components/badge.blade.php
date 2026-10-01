@props(['tone' => 'neutral'])

@php
$toneClasses = [
    'success' => 'bg-emerald-50 text-emerald-700 ring-1 ring-inset ring-emerald-600/20',
    'danger' => 'bg-rose-50 text-rose-700 ring-1 ring-inset ring-rose-600/20',
    'neutral' => 'bg-slate-100 text-slate-600 ring-1 ring-inset ring-slate-500/10',
    'indigo' => 'bg-indigo-50 text-indigo-700 ring-1 ring-inset ring-indigo-600/20',
];
$classes = $toneClasses[$tone] ?? $toneClasses['neutral'];
@endphp

<span {{ $attributes->merge(['class' => "inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium $classes"]) }}>
    {{ $slot }}
</span>
