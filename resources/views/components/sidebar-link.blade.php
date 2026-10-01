@props(['active' => false])

@php
$classes = $active
    ? 'bg-slate-800 text-white'
    : 'text-slate-300 hover:bg-slate-800/60 hover:text-white';
@endphp

<a {{ $attributes->merge(['class' => "group flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition $classes"]) }}>
    {{ $slot }}
</a>
