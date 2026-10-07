# Lista de pruebas antes de salir (Achrono)

Marca cada punto cambiando `[ ]` por `[x]`. Si algo falla, apunta aquí al lado qué pasó
(y pásame el mensaje o `storage/logs/laravel.log`).

Antes de empezar: entra en `https://achrono.es/achrono-pruebas-2026` (cookie de
mantenimiento) en cada navegador o dispositivo que uses para probar.

---

## A. Servidor y configuración

- [ ] Pull hecho en Plesk y el último commit coincide con GitHub.
- [ ] `cmd=migrate` ejecutado: responde `DONE` o "Nothing to migrate" (sin errores).
- [ ] `cmd=config:clear` ejecutado tras cambiar el `.env`.
- [ ] `https://achrono.es` abre con candado (certificado válido) sin avisos del navegador.
- [ ] `http://achrono.es` redirige a `https://` y `www.achrono.es` redirige a `achrono.es`.
- [ ] `cmd=correo:probar` responde "Enviado" y el correo llega a `info@achrono.es`.
- [ ] Tarea programada de Plesk creada (`cmd=schedule:run` cada minuto).
- [ ] `/robots.txt`, `/sitemap.xml` y `/llms.txt` abren bien.

## B. Registro y acceso

- [ ] Registro de una empresa con correo y contraseña: llega el correo de verificación (en español, con logo).
- [ ] El enlace de verificación funciona y deja entrar al panel.
- [ ] Sin verificar el correo no se puede usar la app.
- [ ] Registro y entrada con Google (otra cuenta distinta del super admin).
- [ ] Entrar con una cuenta de super admin que use Google muestra el mensaje de "usa email y contraseña".
- [ ] "¿Olvidaste tu contraseña?": llega el correo, el enlace funciona y se puede entrar con la nueva.
- [ ] Login con DNI (empleado sin correo) y con correo.
- [ ] Tras 5 intentos fallidos, el login se bloquea un rato.
- [ ] Un empleado con contraseña temporal es obligado a cambiarla en el primer acceso.
- [ ] El botón del ojo muestra y oculta la contraseña (login, registro, cambio).

## C. Empleados

- [ ] Alta de empleado solo con DNI (sin correo) y con PIN generado.
- [ ] Alta con correo y con horario corrido.
- [ ] Alta con horario partido (dos tramos) y días laborables.
- [ ] "Dar acceso a la web" muestra la contraseña temporal una sola vez.
- [ ] Editar un empleado (nombre, DNI, horario) y que se guarde.
- [ ] Regenerar el PIN: el antiguo deja de funcionar.
- [ ] Desactivar un empleado: no puede entrar ni fichar; reactivarlo funciona.
- [ ] El DNI duplicado y los formatos inválidos se rechazan con mensaje claro.
- [ ] La lista muestra el estado de hoy (trabajando, vacaciones, baja, fuera).

## D. Fichajes

- [ ] Fichar entrada y salida desde la web (móvil y ordenador): alternan bien.
- [ ] Kiosco: abrir su enlace en una tablet, fichar con PIN correcto e incorrecto.
- [ ] Un mismo empleado ficha entrada y salida y las horas salen bien en el historial.
- [ ] El admin ve los fichajes de toda la empresa y filtra por empleado y fechas.
- [ ] Corrección de un fichaje: queda el original, el motivo y quién la hizo; las horas usan la corregida.
- [ ] Un fichaje no se puede editar ni borrar (ni por la web ni a mano).
- [ ] Exportar a CSV, Excel y PDF: los tres abren bien y los datos coinciden.

## E. Horarios, incidencias y ausencias

- [ ] Un empleado con horario que no ficha pasada su hora aparece como "Sin fichar".
- [ ] Llegar más de 10 minutos tarde genera un "Retraso".
- [ ] Una jornada sin cerrar y las horas de más aparecen en Incidencias.
- [ ] Con turno partido, cada tramo se evalúa por separado.
- [ ] Un aviso de cita (médico) justifica no estar y marca "No ha vuelto" si no vuelve a la hora.
- [ ] El empleado pide vacaciones: el admin recibe el correo, aprueba o rechaza, y el empleado recibe el correo con el resultado.
- [ ] Un empleado de vacaciones o de baja no genera incidencias y sale así en el panel.
- [ ] El número de incidencias del menú coincide con la lista.

## F. Nóminas

- [ ] El admin sube un PDF para un empleado: se guarda y llega el correo "Tienes una nómina nueva" (si tiene correo).
- [ ] El empleado la ve en "Mis nóminas" y la descarga; aparece como vista.
- [ ] Un empleado no puede ver ni descargar las de otro (probar cambiando el número en la URL).
- [ ] Un usuario con permiso de contable puede subir y borrar, sin ser admin.
- [ ] Sin suscripción activa, el empleado sigue pudiendo ver sus nóminas.
- [ ] Un archivo que no sea PDF o de más de 5 MB se rechaza con mensaje.

## G. Stripe y facturación (modo test)

- [ ] Stripe en modo prueba: datos de empresa, portal de clientes y webhook creados con su `whsec_` en el `.env`.
- [ ] Super admin → Tarifas → Guardar: sin error y sin mensaje de "sincronizar".
- [ ] Facturación → "Empezar prueba gratis": pide tarjeta, dirección y NIF/CIF.
- [ ] Pago con la tarjeta `4242 4242 4242 4242`: vuelve a la app y sale "Prueba gratis hasta el…" (15 días).
- [ ] En Stripe, los eventos del webhook aparecen con respuesta 200.
- [ ] La app se desbloquea sola (sin esperar) tras contratar.
- [ ] Añadir empleados por encima de los incluidos actualiza el precio y la cantidad en Stripe.
- [ ] En Stripe: "Terminar prueba ahora" genera la primera factura con el IVA desglosado.
- [ ] La factura PDF se descarga desde Facturación y los datos de empresa y NIF son correctos.
- [ ] Pago fallido con `4000 0000 0000 0341`: la app lo gestiona y avisa.
- [ ] Autenticación 3D Secure con `4000 0025 0000 3155`.
- [ ] "Gestionar facturación" abre el portal; cancelar deja "Cancelada — activa hasta el…".
- [ ] Una empresa sin suscripción ni licencia queda bloqueada (el admin va a Facturación, el empleado ve la pantalla de bloqueo).
- [ ] Canjear un código de licencia (super admin → Licencias): activa la app, respeta el tope de empleados y se puede retirar.

## H. Seguridad y aislamiento

- [ ] Con dos empresas de prueba, ninguna ve datos de la otra.
- [ ] Un empleado no puede abrir páginas de admin (da 403).
- [ ] Un admin de empresa no puede abrir el super admin (403).
- [ ] Cambiar números en URLs (empleados, fichajes, nóminas) de otra empresa no da acceso.
- [ ] El kiosco de una empresa desactivada o sin suscripción no ficha.
- [ ] Las páginas privadas no aparecen en Google (cabecera noindex).
- [ ] `/deploy/ejecutar` sin token responde 404 (y al final, con el token vacío, queda cerrada).

## I. Web pública y contacto

- [ ] La portada se ve bien en móvil, tablet y ordenador, sin scroll horizontal.
- [ ] Precio con IVA incluido y desglose correcto; ejemplos de 5, 10, 20 y 50 empleados bien calculados.
- [ ] Formulario de contacto de la portada: llega a `info@achrono.es` y aparece en super admin → Mensajes.
- [ ] Un mensaje "Incidencia urgente" llega con `[URGENTE]` en el asunto.
- [ ] El formulario rechaza correos mal escritos y no deja enviar sin aceptar la privacidad.
- [ ] Aviso legal con razón social, NIF, domicilio y datos del registro correctos.
- [ ] Las dos guías y las preguntas frecuentes se leen bien y los enlaces funcionan.
- [ ] PageSpeed de Google a la portada (móvil y ordenador) sin problemas graves.
- [ ] Sin enlaces rotos ni textos en inglés.

## J. Correos

- [ ] Verificación de email, restablecer contraseña, contacto, ausencias, nóminas y resumen diario: todos llegan.
- [ ] Salen con el logo, el saludo, el nombre de la empresa y el pie con los datos.
- [ ] No caen en spam (probar con Gmail y con Outlook).
- [ ] Se ven bien en el móvil.
- [ ] El resumen diario llega a las 11:00 un día laborable con incidencias (con la web ya abierta).

## K. Justo antes de abrir

- [ ] Cambiadas las contraseñas que pasaron por el chat (buzón `info@achrono.es`, super admin, base de datos) y actualizado el `.env`.
- [ ] `DEPLOY_TOKEN` cambiado (y, al terminar la configuración, dejado vacío).
- [ ] Borrado `achrono-produccion.env` de tu equipo y `.env.copia` del servidor.
- [ ] Copias de seguridad del hosting confirmadas, con una restauración de prueba.
- [ ] Textos legales revisados por un abogado o tu gestor; completado el tomo, folio y hoja del registro.
- [ ] Cuenta de Stripe activada; claves reales, webhook, portal y datos de empresa en modo real.
- [ ] Tarifa guardada con las claves reales y cobro real pequeño probado y devuelto.
- [ ] Empresas y datos de prueba eliminados o desactivados.
- [ ] Mantenimiento quitado con `cmd=up`.
- [ ] Sitio enviado a Search Console y Bing, con el sitemap.
- [ ] Alguien lee `info@achrono.es` a diario.
- [ ] Primera semana: mirar a diario `storage/logs/laravel.log` y los mensajes de contacto.
