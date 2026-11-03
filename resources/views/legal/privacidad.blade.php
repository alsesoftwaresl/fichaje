{{--
    Política de privacidad: para los trabajadores que usan la app (la responsable es su empresa)
    y para quien escribe desde la web (la responsable es el titular de Achrono). Debe revisarla
    un profesional antes de usarla con clientes.
--}}
<x-legal.layout :title="'Política de privacidad'">
    <h2 class="text-lg font-semibold text-slate-900">Si usas {{ config('app.name') }} porque tu empresa te ha dado de alta</h2>

    <p><strong>Responsable del tratamiento:</strong> tu empresa (la que te ha dado de alta). Es quien
    decide para qué se usan tus datos y ante quien debes ejercer tus derechos.
    {{ config('legal.titular.nombre') }}, titular de {{ config('app.name') }}, trata los datos
    por cuenta de tu empresa como <strong>encargado del tratamiento</strong>, según el
    <a href="{{ route('legal.encargo-tratamiento') }}" class="text-indigo-600 hover:underline">contrato de encargo</a>
    firmado con ella (art. 28 RGPD).</p>

    <p><strong>Para qué se usan tus datos y con qué base legal:</strong></p>
    <ul class="list-disc pl-6 space-y-1">
        <li>Llevar el registro diario de tu jornada, como exige el artículo 34.9 del Estatuto de los
            Trabajadores (obligación legal, art. 6.1.c RGPD).</li>
        <li>Gestionar tu horario, ausencias, avisos de salida y, si tu empresa lo usa, ponerte tus nóminas
            a disposición (ejecución de tu contrato de trabajo, art. 6.1.b RGPD). Si una ausencia es una
            baja médica, solo se guarda ese hecho, nunca el diagnóstico (art. 9.2.b RGPD).</li>
    </ul>

    <p><strong>Qué datos:</strong> nombre, DNI/NIE, correo electrónico (si lo tienes), tu horario previsto,
    tus entradas y salidas (fecha, hora, si fue en el kiosco o desde tu cuenta, dirección IP y navegador),
    las correcciones que haga tu empresa (con motivo y autor), tus ausencias, avisos de salida y nóminas.
    <strong>No</strong> se usa tu ubicación ni tu huella dactilar o tu cara.</p>

    <p><strong>Cuánto tiempo:</strong> el registro de jornada se conserva 4 años, como obliga la ley. El
    resto de datos, mientras dure tu relación con la empresa y los plazos legales que le correspondan.</p>

    <p><strong>Quién más los ve:</strong> nadie fuera de tu empresa, salvo obligación legal (por ejemplo,
    la Inspección de Trabajo o tus representantes legales, que tienen derecho a consultar el registro). El
    proveedor de alojamiento de la aplicación los guarda por cuenta de tu empresa.</p>

    <p><strong>Cómo se protegen:</strong> conexión cifrada, DNI y nóminas cifrados en el servidor, registro
    de jornada que no se puede modificar sin dejar rastro, accesos limitados por rol y registro de las
    acciones sensibles.</p>

    <p><strong>Tus derechos:</strong> puedes pedir a tu empresa el acceso a tus datos, su rectificación,
    supresión, limitación, oposición o portabilidad. Desde tu perfil puedes descargar tú mismo todos tus
    datos, y en «Mis fichajes» el informe mensual de tu registro. Si no te atienden, puedes reclamar ante la
    <a href="https://www.aepd.es" class="text-indigo-600 hover:underline" rel="noopener">Agencia Española de Protección de Datos</a>.</p>

    <h2 class="mt-8 text-lg font-semibold text-slate-900">Si nos escribes desde la web o eres cliente</h2>

    <p><strong>Responsable:</strong> {{ config('legal.titular.nombre') }}
    ({{ config('legal.titular.nif') }}), {{ config('legal.titular.domicilio') }}. Contacto:
    {{ config('legal.titular.email') }}.</p>

    <p><strong>Formulario de contacto:</strong> tratamos tu nombre, correo, empresa y mensaje para responderte
    (tu consentimiento y nuestro interés en atender la consulta). Se conservan hasta resolverla y, como
    máximo, 12 meses; después se borran automáticamente.</p>

    <p><strong>Clientes:</strong> los datos del administrador y de facturación de la empresa se usan para
    prestar el servicio y cobrarlo (contrato) y se conservan los plazos que exigen las normas fiscales y
    mercantiles. El pago lo procesa Stripe; el inicio de sesión con Google, si lo eliges, lo gestiona Google.
    Ambos pueden tratar datos en Estados Unidos con las garantías del Marco de Privacidad de Datos UE-EE. UU.</p>

    <p><strong>Tus derechos:</strong> escribe a {{ config('legal.titular.email') }} para ejercerlos. También
    puedes reclamar ante la Agencia Española de Protección de Datos.</p>
</x-legal.layout>
