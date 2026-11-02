{{--
    Contrato de encargo de tratamiento (art. 28 RGPD) entre la empresa cliente y el titular de
    Achrono. Lo acepta el admin al dar de alta la empresa (versión en config/legal.php). Las
    medidas de seguridad descritas son las que implementa el código; si cambian, actualizar
    aquí y subir la versión. Debe revisarlo un profesional antes de usarlo con clientes.
--}}
<x-legal.layout :title="'Contrato de encargo de tratamiento'">
    <p>Versión: {{ config('legal.version_encargo') }}</p>

    <p>
        Conforme al artículo 28 del Reglamento (UE) 2016/679 (RGPD) y a la Ley Orgánica 3/2018 (LOPDGDD),
        este contrato regula el tratamiento que {{ rtrim(config('legal.titular.nombre'), '.') }}
        ({{ config('legal.titular.nif') }}, en adelante «el Encargado»), titular de {{ config('app.name') }},
        hace por cuenta de la empresa cliente (en adelante «el Responsable») de los datos personales de
        sus trabajadores al usar la plataforma.
    </p>

    <p><strong>1. Objeto, duración y naturaleza.</strong> El Encargado trata los datos únicamente para
    prestar el servicio contratado: registro de jornada (art. 34.9 del Estatuto de los Trabajadores),
    control de horarios e incidencias, ausencias y, si se contrata, puesta a disposición de las
    nóminas. El tratamiento dura lo que dure el contrato de servicio y el plazo de devolución del
    apartado 9.</p>

    <p><strong>2. Datos y personas afectadas.</strong> Trabajadores y administradores del Responsable:
    nombre, DNI/NIE, correo electrónico (opcional en trabajadores), horario previsto, registros de
    entrada y salida (fecha, hora, origen —web, kiosco o añadido por la empresa—, dirección IP y
    navegador), correcciones con su motivo y autor, ausencias y su tipo (puede incluir que una
    ausencia es una baja médica, sin diagnóstico), avisos de salida durante la jornada y nóminas en
    PDF. No se tratan datos biométricos ni de geolocalización.</p>

    <p><strong>3. Instrucciones.</strong> El Encargado trata los datos siguiendo las instrucciones
    documentadas del Responsable —este contrato y lo que el Responsable configura en la plataforma— y
    no los utiliza para fines propios ni los comunica a terceros, salvo obligación legal. Si una
    instrucción le parece contraria a la normativa, lo comunicará al Responsable.</p>

    <p><strong>4. Confidencialidad.</strong> Las personas autorizadas para acceder a los datos por parte
    del Encargado están sujetas a un deber de confidencialidad y solo acceden cuando es necesario para
    prestar el servicio o atender una solicitud de soporte del Responsable.</p>

    <p><strong>5. Medidas de seguridad (art. 32 RGPD).</strong> El Encargado aplica, entre otras:</p>
    <ul class="list-disc pl-6 space-y-1">
        <li>Cifrado de las comunicaciones (https) y de las sesiones.</li>
        <li>Cifrado en reposo del DNI/NIE, de las nóminas en PDF y de los secretos de la verificación en
            dos pasos. Las contraseñas y los PIN se guardan como huellas irreversibles.</li>
        <li>Registro de jornada inalterable: los fichajes y sus correcciones no se pueden modificar ni
            borrar desde la aplicación, y cada uno lleva un sello criptográfico encadenado que permite
            detectar cualquier cambio hecho fuera de ella.</li>
        <li>Aislamiento de los datos de cada empresa, control de acceso por roles, verificación en dos
            pasos disponible, bloqueo tras intentos fallidos de acceso y cierre de las demás sesiones al
            cambiar la contraseña.</li>
        <li>Registro de auditoría de las acciones sensibles (correcciones, exportaciones, descargas de
            datos y nóminas por terceros, cambios de permisos).</li>
        <li>Copias de seguridad de la base de datos y de los archivos.</li>
    </ul>

    <p><strong>6. Subencargados.</strong> El Responsable autoriza al Encargado a recurrir a los
    siguientes proveedores, que ofrecen garantías conforme al RGPD:</p>
    <ul class="list-disc pl-6 space-y-1">
        <li><strong>Loading.es</strong>: alojamiento de la aplicación, la base de datos, los archivos y el
            correo electrónico. Es el único que almacena los datos de los trabajadores.</li>
        <li><strong>Stripe</strong>: cobro de la suscripción. Solo recibe datos de facturación de la
            empresa cliente, no de sus trabajadores.</li>
        <li><strong>Google</strong>: solo si el administrador elige «Continuar con Google», para
            identificarle con su cuenta. No recibe datos de los trabajadores.</li>
    </ul>
    <p>Stripe y Google pueden tratar datos en Estados Unidos, amparados por el Marco de Privacidad de
    Datos UE-EE. UU. y cláusulas contractuales tipo. El Encargado informará al Responsable de cualquier
    cambio de subencargados con antelación, para que pueda oponerse.</p>

    <p><strong>7. Derechos de las personas.</strong> El Encargado ayuda al Responsable a atender las
    solicitudes de acceso, rectificación, supresión, oposición, limitación y portabilidad: la plataforma
    permite descargar todos los datos de un trabajador (y él mismo puede descargar los suyos). Si un
    trabajador se dirige directamente al Encargado, este trasladará la solicitud al Responsable.</p>

    <p><strong>8. Violaciones de seguridad.</strong> El Encargado notificará al Responsable, sin dilación
    indebida y en un plazo máximo de 48 horas desde que tenga conocimiento, cualquier violación de la
    seguridad de los datos, con la información disponible para que el Responsable pueda notificarla a
    la Agencia Española de Protección de Datos en el plazo de 72 horas cuando proceda (art. 33 RGPD).</p>

    <p><strong>9. Fin del contrato: devolución y supresión.</strong> Al terminar el servicio, el
    Responsable dispone de 30 días para descargar sus datos (exportación completa del registro con sus
    correcciones y sellos, informes mensuales y datos de cada trabajador). Pasado ese plazo, el Encargado
    suprimirá los datos en los 3 meses siguientes, salvo que una ley le obligue a conservarlos, en cuyo
    caso quedarán bloqueados (art. 32 LOPDGDD). El Responsable debe conservar el registro de jornada
    durante 4 años (art. 34.9 ET), por lo que es importante que lo descargue antes de darse de baja.</p>

    <p><strong>10. Auditorías.</strong> El Encargado pondrá a disposición del Responsable la información
    necesaria para demostrar el cumplimiento de este contrato y permitirá las auditorías razonables que
    el Responsable solicite con antelación suficiente.</p>
</x-legal.layout>
