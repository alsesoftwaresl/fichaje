<x-legal.layout :title="'Política de privacidad'">
    <p>
        Los datos de fichaje (entrada, salida, fecha y hora) se tratan con la única finalidad de
        cumplir con la obligación legal de registro de jornada (RD-ley 8/2019).
    </p>

    <p><strong>Responsable del tratamiento:</strong> la empresa a la que perteneces (el Cliente
    que te ha dado de alta), no {{ config('app.name') }}.</p>

    <p><strong>Encargado del tratamiento:</strong> {{ config('app.name') }} trata los datos por
    cuenta de tu empresa, conforme al contrato de encargo de tratamiento firmado con ella
    (art. 28 RGPD). Ver <a href="{{ route('legal.encargo-tratamiento') }}" class="text-indigo-600 hover:underline">Encargo de tratamiento</a>.</p>

    <p><strong>Datos tratados:</strong> nombre, correo electrónico, DNI/NIE (opcional), y los
    registros de fichaje (fecha, hora, tipo, dirección IP).</p>

    <p><strong>Conservación:</strong> los registros de fichaje se conservan durante 4 años, conforme
    a la obligación legal aplicable.</p>

    <p><strong>Tus derechos</strong> (acceso, rectificación, oposición, etc.) debes ejercerlos ante
    tu empresa como responsable del tratamiento.</p>

    <p><strong>Formulario de contacto:</strong> si nos escribes desde la web, tratamos tu nombre, correo,
    empresa y mensaje únicamente para responderte. {{ config('legal.titular.nombre') }} es la responsable de
    estos datos y puedes ejercer tus derechos escribiendo a {{ config('legal.titular.email') }}.</p>

    <p>[Pendiente de completar con los datos de contacto reales del Encargado y, en su caso, del
    Delegado de Protección de Datos.]</p>
</x-legal.layout>
