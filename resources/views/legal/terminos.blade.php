{{--
    Términos y condiciones del servicio para la empresa cliente. Los acepta el admin al dar de
    alta la empresa (versión en config/legal.php). Debe revisarlos un profesional.
--}}
<x-legal.layout :title="'Términos y condiciones'">
    <p>Versión: {{ config('legal.version_terminos') }}</p>

    <p>
        Estos términos regulan el uso de {{ config('app.name') }}, servicio de
        {{ rtrim(config('legal.titular.nombre'), '.') }} ({{ config('legal.titular.nif') }}), por parte de la
        empresa cliente («el Cliente») como herramienta de registro de jornada de sus trabajadores.
    </p>

    <p><strong>1. Objeto del servicio.</strong> {{ config('app.name') }} permite registrar la entrada y la
    salida de los trabajadores del Cliente, corregir errores dejando constancia, consultar y exportar el
    registro, controlar horarios, ausencias e incidencias y, si se contrata, poner las nóminas a
    disposición de los trabajadores. El registro se lleva conforme al artículo 34.9 del Estatuto de los
    Trabajadores.</p>

    <p><strong>2. Responsabilidades del Cliente.</strong> El Cliente es el empleador y el responsable del
    tratamiento de los datos de sus trabajadores. Le corresponde: informar a sus trabajadores del sistema
    de registro y del tratamiento de sus datos (art. 13 RGPD), negociar o consultar la organización del
    registro con la representación legal de los trabajadores cuando exista, mantener actualizados los
    datos y horarios, revisar y corregir los fichajes, custodiar las credenciales de sus administradores
    (se recomienda activar la verificación en dos pasos), conservar el registro durante 4 años y ponerlo
    a disposición de los trabajadores, sus representantes y la Inspección de Trabajo.</p>

    <p><strong>3. Lo que hace {{ config('app.name') }}.</strong> Guarda el registro de forma que no se pueda
    modificar ni borrar sin dejar rastro, permite descargarlo completo (con la hora original, las
    correcciones, su motivo y su autor) y genera informes mensuales por trabajador. Las «horas por encima
    del horario» se calculan con el horario configurado; si son horas extraordinarias o complementarias
    depende del contrato y del convenio, que la aplicación no conoce.</p>

    <p><strong>4. Disponibilidad y soporte.</strong> El servicio se presta por internet y se procura que esté
    disponible de forma continua, salvo paradas por mantenimiento, que se intentarán hacer fuera del
    horario laboral habitual, o por causas ajenas al titular. El soporte se presta en español por correo
    ({{ config('legal.titular.email') }}) y por el formulario de contacto, atendido por personas, dando
    prioridad a las incidencias que impidan fichar.</p>

    <p><strong>5. Precio, prueba y baja.</strong> El precio vigente (IVA incluido) se muestra en la web y en
    Facturación. La primera suscripción incluye una prueba gratuita; se puede cancelar en cualquier momento
    desde el portal de facturación y el acceso se mantiene hasta el final del periodo pagado. Al darse de
    baja, el Cliente dispone de 30 días para descargar sus datos, según el
    <a href="{{ route('legal.encargo-tratamiento') }}" class="text-indigo-600 hover:underline">contrato de encargo de tratamiento</a>.</p>

    <p><strong>6. Uso correcto.</strong> El Cliente no usará el servicio para fines ilícitos ni intentará
    acceder a datos de otras empresas o alterar el registro. El titular puede suspender el acceso ante un
    uso indebido grave, avisando al Cliente.</p>

    <p><strong>7. Responsabilidad.</strong> El titular responde de prestar el servicio con la diligencia
    debida. No responde de las decisiones del Cliente basadas en datos introducidos incorrectamente ni de
    las obligaciones laborales propias del empleador.</p>

    <p><strong>8. Legislación aplicable.</strong> Estos términos se rigen por la legislación española.</p>
</x-legal.layout>
