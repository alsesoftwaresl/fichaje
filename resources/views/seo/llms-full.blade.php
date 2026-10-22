# {!! config('app.name') !!} — información completa

> {!! config('app.name') !!} es un software web de control horario y registro de jornada para empresas en España. Los empleados fichan entrada y salida desde el navegador o en un kiosco con PIN o DNI; la empresa controla incidencias, ausencias y nóminas, y conserva un registro de jornada que no se puede editar ni borrar desde la aplicación. Producto de {!! rtrim(config('legal.titular.nombre'), '.') !!}.

Última actualización: octubre de 2026. Esta página resume todo el contenido de la web en texto plano para asistentes de IA y buscadores. La fuente principal es {!! route('home') !!}.

## Qué es y para quién

- Pensado para pymes y autónomos con empleados que necesitan llevar el registro diario de jornada que exige el artículo 34.9 del Estatuto de los Trabajadores (RD-ley 8/2019).
- Funciona desde el navegador del móvil, la tablet o el ordenador, y se puede instalar como aplicación web (PWA) sin pasar por las tiendas de aplicaciones.
- Cada empresa tiene su espacio de datos aislado del resto.

## Funciones

@foreach ($funciones as $bloque)
### {!! $bloque['titulo'] !!}

{!! $bloque['resumen'] !!}

@foreach ($bloque['puntos'] as $punto)
- {!! $punto !!}
@endforeach

@endforeach
## Lo que no hace

- No es un ERP ni un programa de nóminas: las nóminas se suben en PDF, no se calculan.
- No está en las tiendas de aplicaciones (App Store y Google Play); se instala desde el navegador.
- No tiene geolocalización ni reconocimiento facial.
- Necesita conexión a internet para registrar los fichajes.
- No gestiona cuadrantes de turnos rotativos ni el saldo de días de vacaciones.

## Precio

- {!! $tarifa->precioBaseTexto() !!} €/mes con IVA incluido, con {!! $tarifa->empleados_incluidos !!} empleados incluidos.
- {!! number_format((float) $tarifa->precio_empleado_extra, 2, ',', '.') !!} €/mes con IVA incluido por cada empleado adicional.
@if ($tarifa->nominas_modulo_activo)
- Módulo opcional de Nóminas: {!! number_format((float) $tarifa->nominas_precio_mensual, 2, ',', '.') !!} €/mes por empresa con IVA incluido; incluido en la prueba gratis. Sin el módulo, los empleados siguen viendo las nóminas ya subidas.
@endif
- 15 días de prueba gratis (se pide tarjeta, pero no se cobra nada durante la prueba). Sin permanencia; se cancela cuando se quiera.
- Ejemplos al mes, con IVA incluido:
@foreach ([$tarifa->empleados_incluidos, 10, 20, 50] as $n)
  - {!! $n !!} empleados: {!! number_format($tarifa->calcularPrecioMensual($n), 2, ',', '.') !!} €
@endforeach

## Preguntas frecuentes

@foreach ($preguntas as [$pregunta, $respuesta])
### {!! $pregunta !!}

{!! $respuesta !!}

@endforeach
## La ley, sin exageraciones

- Lo que exige hoy: registro diario de la jornada de cada persona trabajadora con la hora de inicio y de fin (artículo 34.9 del Estatuto de los Trabajadores, desde 2019), conservarlo cuatro años a disposición de los trabajadores, sus representantes y la Inspección de Trabajo. La ley no impone un formato: puede ser en papel o digital.
- Lo que está en preparación: según medios especializados, el Ministerio de Trabajo tramita un reglamento para que el registro sea digital, a prueba de manipulación y consultable a distancia por la Inspección. A principios de octubre de 2026 no constaba aprobado ni publicado en el BOE.
- Guías: {!! route('guia.registro-jornada') !!} y {!! route('guia.registro-digital') !!}.
- Este texto es informativo y no constituye asesoramiento jurídico.

## Contacto y datos del titular

- Titular: {!! config('legal.titular.nombre') !!}@if (config('legal.titular.nif')), NIF {!! config('legal.titular.nif') !!}@endif
@if (config('legal.titular.domicilio'))
- Domicilio: {!! config('legal.titular.domicilio') !!}
@endif
@if (config('legal.titular.registro_mercantil'))
- Registro: {!! config('legal.titular.registro_mercantil') !!}
@endif
- Atención al cliente en español, con atención personal: {!! config('legal.titular.email') !!}
- Formulario de contacto: {!! route('contacto.create') !!}

## Páginas

- Inicio: {!! route('home') !!}
- Funciones: {!! route('funciones') !!}
- Guía del registro de jornada: {!! route('guia.registro-jornada') !!}
- Guía del registro horario digital: {!! route('guia.registro-digital') !!}
- Plantilla gratuita de registro de jornada (Excel y PDF): {!! route('plantilla.index') !!}
- Control horario por tipo de negocio: {!! route('sectores.index') !!}
@foreach (config('sectores') as $slug => $s)
  - {!! $s['titulo'] !!}: {!! route('sector.show', $slug) !!}
@endforeach
- Crear una cuenta: {!! route('registro.create') !!}
- Aviso legal: {!! route('legal.aviso-legal') !!}
