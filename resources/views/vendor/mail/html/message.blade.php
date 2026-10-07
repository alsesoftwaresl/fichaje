<x-mail::layout>
{{-- Header --}}
<x-slot:header>
<x-mail::header :url="config('app.url')">
{{ config('app.name') }}
</x-mail::header>
</x-slot:header>

{{-- Body --}}
{!! $slot !!}

{{-- Subcopy --}}
@isset($subcopy)
<x-slot:subcopy>
<x-mail::subcopy>
{!! $subcopy !!}
</x-mail::subcopy>
</x-slot:subcopy>
@endisset

{{-- Footer --}}
<x-slot:footer>
<x-mail::footer>
**{{ config('app.name') }}** · un producto de {{ config('legal.titular.nombre') }}

@if (config('legal.titular.nif') || config('legal.titular.domicilio'))
{{ collect([config('legal.titular.nif'), config('legal.titular.domicilio')])->filter()->implode(' · ') }}

@endif
@if (config('legal.titular.email'))
¿Necesitas ayuda? Escríbenos a [{{ config('legal.titular.email') }}](mailto:{{ config('legal.titular.email') }})
@endif
</x-mail::footer>
</x-slot:footer>
</x-mail::layout>
