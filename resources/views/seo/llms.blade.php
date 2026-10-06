# {{ config('app.name') }}

> {{ config('app.name') }} es un software web de control horario y registro de jornada para empresas en España. Permite que los empleados fichen entrada y salida desde el navegador o desde un kiosco con PIN, y que la empresa conserve ese registro tal como exige el artículo 34.9 del Estatuto de los Trabajadores (RD-ley 8/2019).

## Qué hace

- Fichaje de entrada y salida desde la web o desde un kiosco compartido (tablet u ordenador) con PIN de 6 dígitos.
- Registro de jornada inalterable: los fichajes no se editan ni se borran; los errores se corrigen con una corrección que guarda el motivo y quién la hizo.
- Horarios por empleado (horario corrido o turno partido) y avisos de retrasos, falta de fichaje, jornadas sin cerrar y horas de más.
- Vacaciones, bajas y otras ausencias con solicitud del empleado y aprobación del administrador, y avisos de salida dentro de la jornada (médico, gestiones).
- Estado del equipo en tiempo real: quién está trabajando, de vacaciones, de baja o fuera.
- Nóminas en PDF que sube la empresa y que cada empleado puede ver y descargar.
- Exportación de fichajes a CSV, Excel y PDF.
- Cada empresa tiene su espacio de datos aislado.
- Los empleados pueden acceder solo con su DNI/NIE; no necesitan correo electrónico.

## Precio

- {{ number_format((float) $tarifa->precio_base_mensual, 0) }} €/mes, con {{ $tarifa->empleados_incluidos }} empleados incluidos, y {{ number_format((float) $tarifa->precio_empleado_extra, 2) }} €/mes por cada empleado adicional.
- 15 días de prueba gratis. Sin permanencia.

## Páginas

- [Inicio]({{ route('home') }}): qué es, funciones, precio y preguntas frecuentes.
- [Guía: registro de jornada obligatorio en España]({{ route('guia.registro-jornada') }}): qué exige la ley y cómo cumplirla.
- [Crear una cuenta]({{ route('registro.create') }}): alta de empresa con prueba gratuita.
