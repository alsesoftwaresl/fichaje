@props(['active' => false])

@php
$classes = $active
    ? 'bg-indigo-800 text-white ring-1 ring-inset ring-accent-500/40 [&_svg]:text-accent-400'
    : 'text-indigo-200 hover:bg-indigo-900/60 hover:text-white';
@endphp

<a {{ $attributes->merge(['class' => "group flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition $classes"]) }}>
    {{ $slot }}
</a>
