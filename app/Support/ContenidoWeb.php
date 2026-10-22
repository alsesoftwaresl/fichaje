<?php

namespace App\Support;

use App\Models\Tarifa;

/**
 * Contenido de la web que se usa en varios sitios (portada, página de funciones, llms-full.txt
 * y datos estructurados), escrito una sola vez para que nunca se contradigan.
 */
class ContenidoWeb
{
    /**
     * Preguntas frecuentes: [pregunta, respuesta]. Algunas llevan el precio real de la tarifa.
     *
     * @return list<array{0: string, 1: string}>
     */
    public static function preguntas(Tarifa $tarifa): array
    {
        return [
        ['¿Es obligatorio el registro de jornada en España?', 'Sí. Desde el Real Decreto-ley 8/2019 (artículo 34.9 del Estatuto de los Trabajadores) las empresas deben garantizar el registro diario de la jornada de cada persona trabajadora, con la hora de inicio y de fin, y conservarlo cuatro años a disposición de los trabajadores, sus representantes y la Inspección de Trabajo.'],
        ['¿Cuánto cuesta '.config('app.name').'?', $tarifa->precioBaseTexto().' € al mes, IVA incluido, con '.$tarifa->empleados_incluidos.' empleados incluidos, más '.number_format((float) $tarifa->precio_empleado_extra, 2).' € al mes por cada empleado adicional (también con IVA incluido). Tiene 15 días de prueba gratis y no hay permanencia.'],
        ['¿Mis empleados tienen que instalar una aplicación?', 'No. Pueden fichar desde el navegador del móvil o del ordenador, o en un kiosco compartido (una tablet o un PC en la entrada) tecleando su PIN de 6 dígitos o su DNI, según lo que elija la empresa. Si prefieres que solo se fiche en el kiosco, puedes desactivar el fichaje desde la cuenta.'],
        ['¿Se pueden modificar o borrar los fichajes?', 'No. Los fichajes no se editan ni se borran. Si hay un error, el administrador registra una corrección con su motivo; queda guardado quién la hizo y el fichaje original se conserva.'],
        ['¿Pueden fichar empleados que no tienen correo electrónico?', 'Sí. Cada empleado se identifica con su DNI o NIE; el correo es opcional.'],
        ['¿Se pueden gestionar vacaciones, bajas y nóminas?', 'Sí. Los empleados solicitan vacaciones y bajas y el administrador las aprueba o rechaza. La empresa puede subir las nóminas en PDF y cada empleado las ve y descarga cuando quiera.'],
        ['¿Puedo exportar los fichajes?', 'Sí, a CSV, Excel y PDF, filtrando por empleado y por fechas.'],
        ['¿Qué puede ver y hacer cada empleado?', 'Cada empleado entra con su cuenta (con su DNI si no tiene correo) y ve todos sus fichajes agrupados por jornada, con las horas trabajadas y las correcciones que haya. También ve y descarga sus nóminas en PDF, pide vacaciones y bajas, y avisa de una salida dentro de la jornada, por ejemplo para ir al médico.'],
        ['¿Necesito tarjeta para la prueba gratis?', 'Sí. Se pide una tarjeta para activar la prueba de 15 días, pero no se cobra nada durante ese tiempo. Si cancelas antes de que acabe, no pagas.'],
        ['¿Funciona sin conexión a internet?', 'No. '.config('app.name').' es una aplicación web y necesita conexión para registrar los fichajes. Si en tu centro la conexión es poco fiable, ten en cuenta este punto antes de empezar.'],
        ['¿Tiene aplicación móvil?', 'No está en las tiendas de aplicaciones (App Store o Google Play), pero se puede instalar desde el navegador con un toque: aparece como un icono en la pantalla de inicio y se abre a pantalla completa. También funciona directamente desde el navegador del móvil, la tablet o el ordenador.'],
        ['¿Hay atención al cliente y cómo puedo contactar?', 'Sí. Ofrecemos atención al cliente en español, con atención personal. Puedes escribirnos a '.config('legal.titular.email').' o usar el formulario de contacto; si es una incidencia urgente, indícalo y la atendemos con prioridad.'],
        ['¿Quién está detrás de '.config('app.name').'?', config('app.name').' es un producto de '.rtrim(config('legal.titular.nombre'), '.').'. Los datos del titular están en el aviso legal.'],
    ];
    }

    /**
     * Funciones del producto agrupadas por bloques, en lenguaje claro para quien lleva un negocio.
     *
     * @return list<array{id: string, titulo: string, resumen: string, puntos: list<string>}>
     */
    public static function funciones(): array
    {
        return [
        [
            'id' => 'fichar',
            'titulo' => 'Fichar es tocar un botón',
            'resumen' => 'Cada empleado ficha como le venga mejor, sin instalar nada.',
            'puntos' => [
                'Desde el móvil o el ordenador, con un solo botón de entrada y salida. Si prefieres que solo se fiche en el centro, lo puedes desactivar.',
                'En una tablet compartida en la entrada, con un PIN de 6 números o con el DNI, como prefiera la empresa.',
                'Si alguien no tiene correo, entra con su DNI.',
                'La hora la pone el sistema, no el empleado, así nadie puede cambiarla.',
            ],
        ],
        [
            'id' => 'control',
            'titulo' => 'Sabes qué pasa sin tener que preguntar',
            'resumen' => 'Un panel que te cuenta cómo va el día.',
            'puntos' => [
                'Ves de un vistazo quién está trabajando, quién está de vacaciones o de baja y quién está fuera.',
                'Te avisa de quién llega tarde, quién no ha fichado y quién se ha dejado la jornada abierta.',
                'Funciona con horario normal o con turno partido (mañana y tarde).',
                'Si hubo incidencias, recibes un correo cada día laborable con la lista.',
            ],
        ],
        [
            'id' => 'ausencias',
            'titulo' => 'Vacaciones y bajas, sin líos',
            'resumen' => 'Todo por escrito, sin mensajes sueltos por WhatsApp.',
            'puntos' => [
                'El empleado las pide desde su cuenta y tú las apruebas o las rechazas.',
                'Os avisamos por correo a los dos, y todo queda guardado.',
                'Si alguien tiene que salir un rato (médico, una gestión), lo avisa con la hora a la que vuelve.',
            ],
        ],
        [
            'id' => 'nominas',
            'titulo' => 'Nóminas al alcance de cada empleado',
            'resumen' => 'La empresa las sube y cada uno las descarga cuando quiera.',
            'puntos' => [
                'Subes la nómina en PDF y el empleado la descarga desde su cuenta.',
                'Cada uno ve solo las suyas, y le avisamos por correo cuando hay una nueva.',
                'Puede subirlas tu contable sin necesidad de ser administrador.',
                'No calcula las nóminas: las guarda y las reparte.',
            ],
        ],
        [
            'id' => 'registro',
            'titulo' => 'Un registro fiable, listo para entregar',
            'resumen' => 'Lo que se ficha no se puede cambiar, y puedes descargarlo cuando lo pidan.',
            'puntos' => [
                'Un fichaje guardado no se puede borrar ni editar. Si hay un error, se corrige aparte con su motivo y quién lo hizo.',
                'Descargas los fichajes en Excel, PDF o CSV, por empleado y por fechas.',
                'Queda anotado quién ha hecho cada cosa y cuándo.',
                'Los datos de tu empresa están separados de los de las demás.',
            ],
        ],
        [
            'id' => 'empleados',
            'titulo' => 'Cada empleado tiene su espacio',
            'resumen' => 'Lo suyo, siempre a mano, sin pedírselo al jefe.',
            'puntos' => [
                'Ve todos sus fichajes y las horas de cada día.',
                'Descarga sus nóminas cuando las necesita.',
                'Pide vacaciones y ve si se las han aprobado.',
                'Avisa si tiene que salir un rato.',
            ],
        ],
        [
            'id' => 'acceso',
            'titulo' => 'Empezar es rápido',
            'resumen' => 'En un par de minutos puedes tenerlo en marcha.',
            'puntos' => [
                'Te das de alta con tu correo o con tu cuenta de Google.',
                'Instalas Achrono en el móvil como una app, con un icono en la pantalla de inicio.',
                'Das de alta a cada empleado con su DNI y le generas su PIN.',
                '15 días gratis (se pide tarjeta, pero no se cobra nada durante la prueba) y sin permanencia.',
                'Facturas descargables, y cancelas cuando quieras.',
            ],
        ],
    ];
    }
}
