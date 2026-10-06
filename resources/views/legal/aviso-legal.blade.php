<x-legal.layout :title="'Aviso legal'">
    @php($t = config('legal.titular'))

    <p>
        En cumplimiento del artículo 10 de la Ley 34/2002, de Servicios de la Sociedad de la Información y de
        Comercio Electrónico (LSSI), se facilitan los datos del titular de este sitio web.
    </p>

    <h2 class="pt-2 text-base font-semibold text-slate-900">Titular</h2>
    <ul class="list-disc space-y-1 pl-6">
        <li><strong>Denominación social:</strong> {{ $t['nombre'] }}</li>
        @if ($t['nif'])
            <li><strong>NIF/CIF:</strong> {{ $t['nif'] }}</li>
        @endif
        @if ($t['domicilio'])
            <li><strong>Domicilio:</strong> {{ $t['domicilio'] }}</li>
        @endif
        @if ($t['registro_mercantil'])
            <li><strong>Datos registrales:</strong> {{ $t['registro_mercantil'] }}</li>
        @endif
        @if ($t['email'])
            <li><strong>Correo electrónico:</strong> <a href="mailto:{{ $t['email'] }}" class="text-indigo-600 hover:underline">{{ $t['email'] }}</a></li>
        @endif
        @if ($t['telefono'])
            <li><strong>Teléfono:</strong> {{ $t['telefono'] }}</li>
        @endif
        <li><strong>Sitio web:</strong> {{ config('app.url') }}</li>
    </ul>

    <h2 class="pt-2 text-base font-semibold text-slate-900">Objeto</h2>
    <p>
        {{ config('app.name') }} es un servicio de software como servicio (SaaS) de control horario y registro de
        jornada para empresas. El uso del servicio se rige por los
        <a href="{{ route('legal.terminos') }}" class="text-indigo-600 hover:underline">términos y condiciones</a> y el
        tratamiento de datos personales por la
        <a href="{{ route('legal.privacidad') }}" class="text-indigo-600 hover:underline">política de privacidad</a>.
    </p>

    <h2 class="pt-2 text-base font-semibold text-slate-900">Propiedad intelectual</h2>
    <p>
        El software, el diseño, los textos y la marca {{ config('app.name') }} pertenecen a su titular o cuentan con licencia
        de uso. No se permite su reproducción o distribución sin autorización.
    </p>

    <h2 class="pt-2 text-base font-semibold text-slate-900">Contenido informativo</h2>
    <p>
        Las guías y textos informativos de este sitio tienen carácter orientativo y no constituyen asesoramiento
        jurídico. La normativa puede cambiar: consulta tu caso concreto con un profesional.
    </p>

    <h2 class="pt-2 text-base font-semibold text-slate-900">Legislación aplicable</h2>
    <p>Este sitio se rige por la legislación española.</p>
</x-legal.layout>
