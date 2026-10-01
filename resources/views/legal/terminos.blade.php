<x-legal.layout :title="'Términos y condiciones'">
    <p>Versión: {{ config('legal.version_terminos') }}</p>

    <p>
        Estos términos regulan el uso de {{ config('app.name') }} por parte de la empresa cliente
        ("el Cliente") como herramienta de registro de jornada laboral de sus empleados.
    </p>

    <p><strong>1. Objeto del servicio.</strong> {{ config('app.name') }} proporciona una plataforma
    para el registro diario de entrada y salida de los empleados del Cliente, conforme a lo exigido
    por el RD-ley 8/2019.</p>

    <p><strong>2. Responsabilidades del Cliente.</strong> El Cliente es responsable de la veracidad
    de los datos de sus empleados, de comunicarles el sistema de registro implantado y de custodiar
    las credenciales de acceso de sus administradores.</p>

    <p><strong>3. Disponibilidad y soporte.</strong> [Pendiente de definir: SLA, horario de soporte,
    procedimiento de incidencias.]</p>

    <p><strong>4. Duración y baja.</strong> [Pendiente de definir: condiciones de alta, baja y
    exportación de datos al finalizar el servicio.]</p>

    <p><strong>5. Legislación aplicable.</strong> Estos términos se rigen por la legislación española.</p>
</x-legal.layout>
