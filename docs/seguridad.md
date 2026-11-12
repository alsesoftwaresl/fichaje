# Seguridad

Medidas técnicas que aplica Achrono (art. 32 RGPD), qué hay que mantener en el servidor y qué
hacer si algo pasa. Complementa a [`cumplimiento-legal.md`](cumplimiento-legal.md).

## 1. Datos y cómo se protegen

| Dato | Protección |
|---|---|
| Contraseñas | Bcrypt (se re-cifra al entrar si sube el coste). Mínimo 8 caracteres con letras y números. |
| PIN del kiosco | HMAC-SHA256 con la `APP_KEY`; nunca en claro. |
| DNI/NIE | **Cifrado** (AES-256, `APP_KEY`). Se busca por su huella HMAC (`dni_hash`). |
| Nóminas (PDF) | **Cifradas** en el disco privado (`.pdf.enc`, nombre aleatorio); se descifran solo en memoria al descargar. |
| Verificación en dos pasos | Secreto y códigos de recuperación cifrados. |
| Sesiones | Cifradas, cookie `httponly`, `secure` con https, `SameSite=Lax`. |
| Registro de jornada | Inalterable desde la app y con sello HMAC encadenado (`IntegridadRegistro`). |
| Resto (nombre, correo, horarios, ausencias) | Base de datos con acceso solo desde la aplicación. |

## 2. Acceso

- Roles: super admin, admin de empresa, empleado (y permiso de contable). Datos de cada empresa
  aislados por un *global scope*; nunca se acepta `empresa_id` de un formulario.
- **Verificación en dos pasos** (TOTP) opcional para todos y recomendada a quien administra;
  **obligatoria para el super admin en producción** (`DosPasosObligatorio`):
  código caducado a los 5 min, 5 intentos por minuto, el mismo código no vale dos veces,
  8 códigos de recuperación de un uso.
- Límite de intentos: login 5; kiosco 10 fallos de PIN o DNI en 5 minutos por conexión y 100 por
  hora en toda la empresa; cambiar, confirmar y recuperar la contraseña, desactivar la
  verificación en dos pasos, registro y contacto.
- **Enlace del kiosco**: es secreto (32 caracteres) y el admin lo puede cambiar desde Empleados si
  se pierde la tablet o sale de la empresa; el viejo deja de funcionar.
- **Bajas**: quien es dado de baja (o cuya empresa se desactiva) pierde en su siguiente petición
  la sesión abierta y el «Recuérdame» (`CuentaActiva`), no solo el login.
- Al cambiar la contraseña se cierran las sesiones de los demás dispositivos (`AuthenticateSession`).
- Google solo deja entrar a admins activos de empresas activas.
- El admin no puede darse de baja a sí mismo.

## 3. Web

- Cabeceras en todas las respuestas (`CabecerasSeguridad`): `X-Content-Type-Options`,
  `X-Frame-Options` y `frame-ancestors`, `form-action` (web y Stripe), `base-uri`, `object-src`,
  `Referrer-Policy`, `Permissions-Policy` y HSTS en producción. No se limita `script-src` porque
  Alpine.js lo necesita.
- CSRF en todos los formularios (excepto el webhook de Stripe, que va firmado: sin
  `STRIPE_WEBHOOK_SECRET` Stripe no acepta ningún aviso, y `produccion:comprobar` lo marca como FALLO).
- **Exportaciones a Excel y CSV sin fórmulas**: un texto como `=HYPERLINK(...)` (p. ej. en el
  nombre, que el empleado puede cambiar) se guarda como texto y no se ejecuta al abrirlo.
- El seeder no crea en producción el super admin con la contraseña de desarrollo.
- Parámetros raros en la URL se ignoran en vez de romper la página.
- `composer audit` sin avisos; `npm audit` solo afecta a dependencias de compilación.

## 4. Mantenimiento obligatorio

| Tarea | Cada cuánto |
|---|---|
| **Copia de seguridad** de la base de datos y de `storage/app/private` (Plesk → Copias de seguridad), guardada fuera del servidor | Diaria |
| Probar a **restaurar** una copia en otro sitio | Trimestral |
| `cmd=registro:verificar` o Admin → Fichajes → Comprobar integridad | Mensual y tras cualquier incidente |
| Revisar `storage/logs/laravel.log` | Semanal |
| `composer audit` y `npm audit` en local; actualizar dependencias | Mensual |
| Versión de PHP del hosting al día (`cmd=produccion:comprobar` la revisa; mínima actual 8.3.36 por «PHP2Shell», CVE-2026-109618/109619) | Mensual y cuando se publique un fallo de PHP |
| Revisar que el certificado SSL se renueva | Plesk lo hace solo; comprobar cada trimestre |

**La `APP_KEY` no se cambia nunca en producción**: con ella se descifran los DNI, las nóminas y
los secretos de dos pasos, y se calculan los PIN, las huellas del DNI y los sellos del registro.
Si se perdiera, esos datos serían irrecuperables: **guárdala aparte**, junto a las copias.

Variables de producción relacionadas: `APP_ENV=production`, `APP_DEBUG=false`,
`APP_URL=https://…`, `SESSION_SECURE_COOKIE=true` (las sesiones van siempre cifradas), y `DEPLOY_TOKEN`
vacío cuando no se esté desplegando.

## 5. Si hay una brecha de seguridad

1. **Contener**: poner la web en mantenimiento (`cmd=down`), cambiar las contraseñas afectadas,
   el `DEPLOY_TOKEN`, la contraseña de la base de datos y las claves de Stripe si procede
   (la `APP_KEY` no, salvo que se haya filtrado: en ese caso hay que planificar el re-cifrado).
2. **Evaluar**: qué datos, de qué empresas y desde cuándo. `registro:verificar` dice si se ha
   tocado el registro; `audit_logs` y `laravel.log` ayudan a reconstruir qué pasó.
3. **Avisar a las empresas afectadas** (somos su encargado) sin dilación indebida y como máximo
   en **48 horas**, con lo que se sepa: ellas deciden si notifican a la AEPD (**72 horas**) y a
   las personas afectadas (art. 33 y 34 RGPD).
4. Si afecta a datos de los que ALSE es responsable (clientes, contacto), **notificar a la AEPD**
   en 72 horas si hay riesgo para las personas.
5. **Documentar** la brecha (qué pasó, efectos, medidas) aunque no se notifique (art. 33.5).

## 6. Límites conocidos

- El correo y el nombre no van cifrados (se usan para entrar y para mostrarse).
- En el hosting compartido la base de datos tiene un único usuario con todos los permisos; el
  sello de integridad permite **detectar** cambios, pero no los impide a quien tenga ese acceso.
- La verificación en dos pasos es opcional para empresas y empleados (lo decide cada empresa);
  solo el super admin la tiene obligatoria.
- Quien tenga el enlace del kiosco y adivine un PIN puede fichar por otro: por eso el tope de
  fallos, el enlace que se puede cambiar y el modo DNI. Un ataque al tope puede frenar el kiosco
  hasta una hora; los empleados con acceso web pueden fichar desde el móvil mientras tanto.
- Los datos del registro no se borran solos al cumplir 4 años (ver
  [cumplimiento legal](cumplimiento-legal.md#5-pendiente)).
