<?php

/*
 * Páginas por sector (/control-horario/{slug}). Todo lo que se cuenta aquí tiene que poder
 * hacerse de verdad con la aplicación (si no, se dice que no). En las respuestas se pueden
 * usar {precio}, {incluidos} y {extra}, que se sustituyen por la tarifa vigente.
 */
return [
    'hosteleria' => [
        'nombre' => 'Bares y restaurantes',
        'titulo' => 'Control horario para bares y restaurantes',
        'descripcion' => 'Registro de jornada para hostelería: turnos partidos, tablet de fichaje en la barra o la cocina, horas de más y vacaciones. 15 días gratis, sin permanencia.',
        'intro' => 'En un bar o un restaurante se trabaja por turnos, con plantillas que cambian y poco tiempo para papeleo. Fichar tiene que llevar dos segundos, y el registro de jornada tiene que hacerse solo.',
        'situaciones' => [
            'Turno partido: comida y cena, con un parón en medio, y hay que apuntar entradas y salidas dos veces al día.',
            'Altas y bajas frecuentes, y personal que no usa correo electrónico ni quiere instalar aplicaciones.',
            'Fines de semana y eventos que alargan la jornada, sin que nadie lleve la cuenta de las horas de más.',
        ],
        'solucion' => [
            ['Una tablet en la barra o en la cocina', 'Cada empleado ficha con su PIN de 6 números o con su DNI, en dos segundos y sin instalar nada.'],
            ['Turno partido de verdad', 'Defines uno o varios tramos al día para cada empleado. Si alguien no ficha la vuelta del descanso, el panel lo marca.'],
            ['Horas de más a la vista', 'El panel avisa cuando alguien trabaja más de lo previsto, y descargas el informe del mes en Excel o PDF.'],
            ['Altas y bajas sin perder nada', 'Das de alta a alguien con su DNI en un minuto. Si se marcha, lo desactivas y sus fichajes se conservan.'],
            ['Sin correo, sin problema', 'Quien no tiene correo entra con su DNI y elige su contraseña la primera vez.'],
        ],
        'ejemplo' => 'Un restaurante con turno de comida de 12:00 a 16:00 y de cena de 20:00 a 23:30 configura esos dos tramos para cada camarero. Si alguien ficha la entrada de la cena a las 20:25, el panel marca un retraso de 25 minutos; si no ficha la vuelta, aparece como "no ha fichado la vuelta". El dueño lo ve al abrir el panel y no tiene que revisar papeles.',
        'faqs' => [
            ['¿Sirve para turnos partidos?', 'Sí. Puedes definir varios tramos al día para cada empleado (por ejemplo comida y cena) y los días que trabaja. Cada tramo se evalúa por separado.'],
            ['¿Y si mis empleados no saben usar aplicaciones?', 'No tienen que instalar nada: fichan en una tablet con un PIN o con su DNI. Quien no tiene correo entra con su DNI.'],
            ['¿Puedo hacer los cuadrantes de turnos rotativos?', 'No. {nombre} no es un programa de cuadrantes: cada empleado tiene un horario fijo por tramos y días de la semana. Si cambias de turnos cada semana, ten en cuenta este límite antes de empezar.'],
            ['¿Cuánto cuesta para un negocio pequeño?', '{precio} € al mes con {incluidos} empleados incluidos y {extra} € al mes por cada empleado adicional, IVA incluido. 15 días de prueba gratis y sin permanencia.'],
        ],
    ],

    'comercio' => [
        'nombre' => 'Comercio y tiendas',
        'titulo' => 'Control horario para tiendas y comercios',
        'descripcion' => 'Registro de jornada para tiendas: horarios por empleado, jornadas parciales, tablet en la trastienda, vacaciones y nóminas. 15 días gratis, sin permanencia.',
        'intro' => 'Una tienda abre muchas horas, con empleados a jornada completa y parcial y horarios que no se parecen entre sí. El registro de jornada tiene que adaptarse a cada uno, no al revés.',
        'situaciones' => [
            'Horario de apertura largo, con turnos continuos o partidos según la persona.',
            'Empleados a tiempo parcial que trabajan días distintos de la semana.',
            'Vacaciones y permisos que se piden de palabra, y luego nadie recuerda qué se aprobó.',
        ],
        'solucion' => [
            ['Un horario para cada empleado', 'Defines las horas y los días de la semana de cada persona, también si trabaja solo unos días.'],
            ['Una tablet en la trastienda', 'Se ficha con PIN o con DNI al entrar y al salir. El dueño ve quién está en la tienda sin estar presente.'],
            ['Vacaciones y bajas por escrito', 'El empleado las pide desde su cuenta, tú las apruebas o rechazas y os avisamos por correo a los dos.'],
            ['Salidas dentro de la jornada', 'Si alguien tiene que ir al médico o hacer una gestión, avisa con la hora de vuelta y no cuenta como incidencia.'],
            ['Nóminas a mano', 'Subes la nómina en PDF y cada empleado la descarga desde su cuenta.'],
        ],
        'ejemplo' => 'Una empleada trabaja de lunes a jueves, de 10:00 a 14:00 y de 17:00 a 20:00. Se configura una vez con esos días y tramos. Si un martes no ficha la entrada de la tarde, el panel lo marca; si el jueves avisó de que iba al médico de 12:00 a 13:00, ese rato no cuenta como incidencia.',
        'faqs' => [
            ['¿Sirve para empleados a tiempo parcial?', 'Sí. Cada empleado tiene sus propias horas y los días de la semana en que trabaja.'],
            ['¿Puedo ver quién está en la tienda?', 'Sí. El panel muestra quién está trabajando, quién está de vacaciones o de baja y quién está fuera.'],
            ['¿Tengo que comprar una tablet?', 'Hace falta un dispositivo con navegador para el kiosco: una tablet o un ordenador fijo en la trastienda o la entrada. Los empleados no instalan nada.'],
            ['¿Cuánto cuesta?', '{precio} € al mes con {incluidos} empleados incluidos y {extra} € al mes por cada empleado adicional, IVA incluido. 15 días gratis y sin permanencia.'],
        ],
    ],

    'talleres' => [
        'nombre' => 'Talleres y pequeña industria',
        'titulo' => 'Control horario para talleres y pequeña industria',
        'descripcion' => 'Registro de jornada para talleres y naves: tablet de fichaje a la entrada, horas de más, correcciones con motivo y nóminas. 15 días gratis, sin permanencia.',
        'intro' => 'En un taller o una nave la plantilla no suele estar delante de un ordenador, y las horas extra por urgencias son habituales. Hace falta un fichaje rápido en la entrada y un registro que nadie pueda retocar.',
        'situaciones' => [
            'Plantilla que trabaja en la nave y no tiene ordenador ni usa correo a diario.',
            'Horas extra por trabajos urgentes que luego se discuten porque nadie las anotó.',
            'Un registro en papel que se pierde, se moja o se rellena de golpe a final de mes.',
        ],
        'solucion' => [
            ['Un kiosco a la entrada', 'Una tablet o un ordenador fijo junto a la puerta: cada empleado ficha con su PIN o su DNI al llegar y al irse.'],
            ['Horas de más registradas', 'Si alguien trabaja más de lo previsto, el panel lo marca y queda anotado con las horas exactas.'],
            ['Un registro que no se reescribe', 'Un fichaje guardado no se puede editar ni borrar. Si alguien se olvida, el responsable añade una corrección con su motivo, y el original se conserva.'],
            ['Informes cuando los pidan', 'Descargas los fichajes por empleado y fechas en Excel, PDF o CSV.'],
            ['Nóminas y ausencias', 'Cada empleado ve y descarga sus nóminas y pide vacaciones desde su cuenta, en el móvil si quiere.'],
        ],
        'ejemplo' => 'Un taller con ocho personas pone una tablet junto a la puerta. Un viernes entra un trabajo urgente y dos mecánicos salen a las 19:20 en vez de a las 17:00. Al cerrar la semana, el responsable ve en el panel las "horas de más" de cada uno y las descarga en el informe, sin discutir de memoria quién se quedó cuánto.',
        'faqs' => [
            ['¿Qué pasa si alguien se olvida de fichar?', 'El administrador añade una corrección con la hora correcta y un motivo. Queda anotado quién la hizo y el fichaje original se conserva.'],
            ['¿Tienen que usar el móvil?', 'No. Pueden fichar solo en la tablet de la entrada con su PIN o su DNI. Si la empresa lo permite, también desde su cuenta en el móvil.'],
            ['¿Funciona sin internet?', 'No: necesita conexión para registrar los fichajes. Si en tu nave la conexión es poco fiable, ten en cuenta este punto antes de empezar.'],
            ['¿Cuánto cuesta?', '{precio} € al mes con {incluidos} empleados incluidos y {extra} € al mes por cada empleado adicional, IVA incluido. 15 días gratis y sin permanencia.'],
        ],
    ],

    'oficinas' => [
        'nombre' => 'Oficinas y despachos',
        'titulo' => 'Control horario para oficinas y pequeñas empresas de servicios',
        'descripcion' => 'Registro de jornada para oficinas: fichaje desde el navegador o en kiosco, ausencias, nóminas y resumen diario por correo. 15 días gratis, sin permanencia.',
        'intro' => 'En una oficina el registro de jornada suele ser un Excel que nadie quiere rellenar. Fichar desde el navegador, ver las horas propias y pedir vacaciones sin papeles lo hace más fácil para todos.',
        'situaciones' => [
            'Un Excel compartido que se rellena tarde, se retoca y cuesta sumar a final de mes.',
            'Parte de la plantilla trabaja desde casa algunos días y hay que registrar también su jornada.',
            'Nóminas, vacaciones y bajas repartidas entre correos, carpetas y mensajes.',
        ],
        'solucion' => [
            ['Fichar desde el navegador', 'Un botón de entrada y salida desde el móvil o el ordenador, sin instalar nada. La empresa decide si se puede fichar así o solo en el kiosco.'],
            ['Cada persona ve sus horas', 'El empleado consulta sus fichajes, las horas de cada día y las correcciones que haya.'],
            ['Ausencias y nóminas en un sitio', 'Vacaciones y bajas con aprobación, y nóminas en PDF que cada empleado descarga cuando quiera.'],
            ['Resumen diario por correo', 'Si hay incidencias (retrasos, faltas de fichaje, jornadas abiertas), el administrador recibe cada día laborable un correo con la lista.'],
            ['Informes para la asesoría', 'Exportas los fichajes por empleado y fechas en Excel, PDF o CSV.'],
        ],
        'ejemplo' => 'Una gestoría de seis personas deja que cada empleado fiche desde su navegador. Quien un día teletrabaja ficha igual desde casa. A las 11:00, la responsable recibe un correo con las incidencias de la jornada y, a final de mes, descarga el informe de fichajes para la asesoría laboral.',
        'faqs' => [
            ['¿Pueden fichar desde casa los que teletrabajan?', 'Sí, si la empresa tiene activado el fichaje desde la cuenta. Es un ajuste que decide el administrador y puede desactivarse para que solo se fiche en el kiosco.'],
            ['¿Registra la ubicación?', 'No. No tiene geolocalización: registra la hora, la IP y el navegador del fichaje, no dónde está la persona.'],
            ['¿Se pueden subir las nóminas?', 'Sí, en PDF, por el administrador o por una persona con permiso de contable. Cada empleado ve solo las suyas.'],
            ['¿Cuánto cuesta?', '{precio} € al mes con {incluidos} empleados incluidos y {extra} € al mes por cada empleado adicional, IVA incluido. 15 días gratis y sin permanencia.'],
        ],
    ],
];
