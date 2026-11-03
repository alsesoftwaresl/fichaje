# Informe de la prueba beta

Recorrido completo de Achrono como lo haría un cliente nuevo, en el navegador (escritorio y
móvil), con datos de prueba en local, más las pruebas automáticas. Noviembre de 2026.

## 1. Qué se ha probado

| Recorrido | Resultado |
|---|---|
| Registro de una empresa en la web (con un error de contraseña a propósito) | Correcto tras arreglar las casillas legales |
| Verificación del correo del admin (enlace del correo) | Correcto |
| Facturación sin suscripción y canje de un código de licencia | Correcto tras arreglar el formato del código y la oferta de pago con licencia |
| Elección del modo del kiosco (pregunta inicial del panel) | Correcto |
| Alta de un empleado con DNI escrito con puntos, acceso web y horario | Correcto tras arreglar el formato del DNI |
| Fichar en el kiosco con PIN y doble toque | Correcto (el segundo toque se rechaza con su mensaje) |
| Login del empleado con DNI con puntos y contraseña temporal; cambio obligatorio | Correcto |
| Empleado: fichar salida en la web, descargar su informe del mes, pedir vacaciones (con fechas al revés a propósito) | Correcto |
| Aviso por correo al admin de la solicitud de vacaciones | Correcto tras arreglar la cola de correos |
| Admin: aprobar vacaciones, informes mensuales, comprobar integridad, control horario | Correcto tras arreglar el falso «retraso» del día del alta |
| Verificación en dos pasos: activar, códigos de recuperación, salir y entrar con código | Correcto |
| Pantallas nuevas en móvil (informes, integridad, facturación, perfil, empleados, legales) | Correcto tras apilar la tabla de informes |
| Pruebas automáticas | 330 en verde; recorrido de las 58 rutas por rol sin errores 500, también con valores raros en la URL |

## 2. Fallos encontrados y corregidos

1. **Código de licencia en minúsculas o con espacios** («ach 5gh2 p3r6») daba «no es válido».
2. **Con licencia gratuita activa, Facturación ofrecía «Empezar prueba gratis»**: se podía acabar
   pagando sin necesidad. Ya no se ofrece y el servidor lo rechaza.
3. **El alta de empleados no aceptaba el DNI con puntos o guion**, aunque el kiosco y el login sí.
4. **Las casillas de aceptar términos y encargo se desmarcaban** si fallaba otro campo del registro.
5. **Correos en cola que no salían nunca**: con `QUEUE_CONNECTION=database` y sin worker, los
   avisos (vacaciones, nóminas y el formulario de contacto) se quedaban en la cola. Ahora la
   tarea programada los envía cada minuto y `.env.example` usa `sync`.
6. **Falso «retraso» el día del alta**: a quien se da de alta a media mañana se le marcaba retraso
   por no haber entrado a primera hora.
7. **PDF de casi 1 MB** por llevar el tipo de letra entero; ahora unos 26 KB.
8. **«ALSE SOFTWARE, S.L» sin el punto** en los textos legales.
9. **Tabla de informes cortada en el móvil**; ahora se apila como las demás.
10. **Aviso «Documento plantilla» en las páginas legales**: se mantiene hasta que los revise un
    profesional, pero ya se quita con `LEGAL_BORRADOR=false`.

## 3. Lo que no se ha podido probar desde aquí

- **Pago real con Stripe** (Checkout, webhook, portal, facturas): solo con la cuenta de Stripe en
  modo prueba y la web publicada. Está en la [lista de pruebas de salida](lista-pruebas-salida.md#g-stripe-y-facturación-modo-test).
- **«Continuar con Google» de verdad**: necesita la URL de redirección configurada en Google.
- **Correo real por SMTP** desde el servidor (en local los correos se guardan en el log).
- **Subir una nómina desde el navegador** (el navegador de pruebas no sube archivos); sí lo cubren
  las pruebas automáticas, incluido el cifrado.

## 4. ¿Está listo para vender?

**El código, sí**: no queda ningún fallo conocido abierto, todo está comentado y documentado, y las
pruebas cubren los flujos principales. **Antes de cobrar al primer cliente** faltan pasos que no son
de código:

1. Desplegar lo último en el servidor **con copia de seguridad previa** y la `APP_KEY` guardada aparte
   (ver [despliegue](deploy-loading-es.md#4-migraciones-sin-terminal)).
2. En el `.env` del servidor: `QUEUE_CONNECTION=sync`, `SESSION_ENCRYPT=true`,
   `SESSION_SECURE_COOKIE=true`, `APP_DEBUG=false` y la tarea programada de Plesk llamando a
   `cmd=schedule:run` cada minuto.
3. Probar Stripe en modo prueba de principio a fin y luego pasar a modo real.
4. Que un abogado o graduado social revise los textos legales; después, `LEGAL_BORRADOR=false`.
5. Activar la verificación en dos pasos en la cuenta de super admin.
6. Cambiar las claves que han pasado por el chat (`DEPLOY_TOKEN`, contraseña de la base de datos,
   contraseña del super admin y del buzón).
