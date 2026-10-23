<p align="center">
  <img src="public/images/logo.png" alt="Achrono" width="320">
</p>

<h3 align="center">Control horario y fichaje de empleados para empresas españolas</h3>

<p align="center">
  SaaS multiempresa · Registro de jornada pensado para el RD-ley 8/2019 y el RGPD · Kiosco con PIN o DNI · Nóminas · Facturación con Stripe · App instalable
</p>

<p align="center">
  <img alt="PHP" src="https://img.shields.io/badge/PHP-8.2%2B-777BB4?logo=php&logoColor=white">
  <img alt="Laravel" src="https://img.shields.io/badge/Laravel-12-FF2D20?logo=laravel&logoColor=white">
  <img alt="Tailwind" src="https://img.shields.io/badge/Tailwind-3-06B6D4?logo=tailwindcss&logoColor=white">
  <img alt="Alpine.js" src="https://img.shields.io/badge/Alpine.js-3-8BC0D0?logo=alpinedotjs&logoColor=white">
  <img alt="Stripe" src="https://img.shields.io/badge/Stripe-Cashier-635BFF?logo=stripe&logoColor=white">
  <img alt="MySQL / MariaDB" src="https://img.shields.io/badge/MySQL%20%2F%20MariaDB-4479A1?logo=mysql&logoColor=white">
  <img alt="PWA" src="https://img.shields.io/badge/PWA-instalable-5A0FC8?logo=pwa&logoColor=white">
</p>

---

## Índice

1. [Qué es Achrono](#qué-es-achrono)
2. [Funcionalidades](#funcionalidades)
3. [Cumplimiento legal y seguridad](#cumplimiento-legal-y-seguridad)
4. [Roles y permisos](#roles-y-permisos)
5. [Stack tecnológico](#stack-tecnológico)
6. [Arquitectura](#arquitectura)
7. [Modelo de datos](#modelo-de-datos)
8. [Instalación en local](#instalación-en-local)
9. [Variables de entorno](#variables-de-entorno)
10. [Base de datos endurecida (registro append-only)](#base-de-datos-endurecida-registro-append-only)
11. [Facturación con Stripe, IVA y licencias](#facturación-con-stripe-iva-y-licencias)
12. [Acceso con Google](#acceso-con-google)
13. [Correo electrónico y avisos](#correo-electrónico-y-avisos)
14. [Web pública, SEO y contenido](#web-pública-seo-y-contenido)
15. [App instalable (PWA)](#app-instalable-pwa)
16. [Comandos y tareas programadas](#comandos-y-tareas-programadas)
17. [Pruebas](#pruebas)
18. [Despliegue](#despliegue)
19. [Estructura del proyecto](#estructura-del-proyecto)
20. [Convenciones de trabajo](#convenciones-de-trabajo)
21. [Pendiente / hoja de ruta](#pendiente--hoja-de-ruta)
22. [Licencia](#licencia)

---

## Qué es Achrono

Achrono es una aplicación web **SaaS multiempresa** para llevar el registro de jornada de los empleados de una empresa. Cada empresa cliente se registra por su cuenta, tiene su propio espacio (con los datos aislados del resto), da de alta a su plantilla y ficha desde una **tablet en la entrada** con un PIN o con el DNI, o desde el móvil y el ordenador si la empresa lo permite.

Está pensada para el mercado español: el registro de jornada es **inalterable** (las correcciones se guardan aparte, con motivo y autor), se conserva el histórico y se puede **exportar** para nóminas o para una inspección de trabajo. Además incluye gestión de ausencias, horarios con avisos de retrasos y horas extra, nóminas descargables por cada empleado, avisos por correo y cobro por suscripción. Se puede instalar en el móvil como una aplicación web (PWA).

El proyecto lo desarrolla **ALSE SOFTWARE, S.L.** (contacto: `info@achrono.es`).

## Funcionalidades

### Fichaje

- **Kiosco**: una tablet u ordenador fijo con una URL única por empresa (`/kiosko/{token}`), sin iniciar sesión. Alterna entrada/salida automáticamente. **Cada empresa elige cómo se identifican los empleados**: solo **PIN** de 6 dígitos (por defecto y lo más seguro), solo **DNI/NIE**, o **ambos** (con un enlace "¿No recuerdas tu PIN? Ficha con tu DNI"). El DNI se acepta sin importar mayúsculas, espacios ni puntos, solo para empleados activos de esa empresa, con mensajes genéricos. Tanto el PIN como el DNI se frenan tras 10 fallos en 5 minutos desde el mismo sitio. La primera vez que entra el admin, el panel le pregunta qué prefiere.
- **Fichaje desde la cuenta**: los empleados pueden fichar desde `/mis-fichajes`. La empresa puede **desactivarlo** (ajuste en Empleados) para que solo se fiche en el kiosco; entonces esa pantalla es solo de consulta y el servidor rechaza el fichaje.
- **Salidas olvidadas y doble toque**: si una entrada lleva más de 16 h abierta se da por olvidada y el siguiente fichaje vuelve a ser una entrada (el empleado ve un aviso para que su empresa la complete). Un segundo fichaje en menos de un minuto se ignora. Toda la lógica está en `RegistroFichajes`, común a web y kiosco.
- **Jornadas**: entrada y salida se muestran en una sola fila con las **horas trabajadas** ya calculadas (teniendo en cuenta las correcciones). Un **turno de noche** (22:00 a 06:00) es una sola jornada del día en que empieza, también en los informes por fechas.
- **Correcciones trazables**: el admin puede corregir la hora de un fichaje; el original **nunca se modifica**. La corrección queda en otra tabla con su motivo, su autor y la fecha.
- **Añadir la salida olvidada**: si falta la salida entera, el admin la añade como fichaje nuevo con origen `empresa`, su nombre y el motivo (y queda en auditoría y en las notas de los informes).
- **Exportación** de fichajes por empleado y rango de fechas en **CSV, Excel y PDF**.

### Horarios, incidencias y control horario

- **Horario esperado opcional** por empleado: días laborables y hora de entrada/salida. El último tramo puede acabar pasada la medianoche (turnos de noche).
- **Turno partido**: hasta 3 tramos al día (por ejemplo 09:00–14:00 y 16:00–19:00). Las horas esperadas son la suma de los tramos y los avisos se evalúan tramo a tramo.
- **Avisos del panel** (con margen de 10 minutos): *retraso*, *sin fichar*, *fichaje sin cerrar* (pasados 10 minutos de la hora de salida), *no ha vuelto* y *horas de más*. No hay aviso de salida anticipada.
- **Control horario**: pantalla con filtro por empleado y fechas (hasta 93 días) que resume, por empleado, retrasos, minutos de retraso, días sin fichar, fichajes sin cerrar y horas extra, con el detalle día a día.
- **Contador en el menú** con las incidencias de hoy y **resumen diario por correo** al administrador (laborables a las 11:00, solo si hay incidencias).
- **Salidas avisadas** (médico, una gestión…): el empleado avisa de que sale dentro de su horario, explica el motivo y dice a qué hora vuelve. Justifica la ausencia (no sale como retraso ni como fichaje sin cerrar), el admin lo ve en el panel y puede anularlo, y se avisa si no se vuelve a la hora prevista.

### Equipo y ausencias

- **Estado del equipo** en tiempo real: *trabajando*, *fuera*, *vacaciones*, *baja*, *salida avisada*. También visible en la lista de empleados.
- **Vacaciones y bajas** con flujo de solicitud: el empleado solicita y el admin aprueba o rechaza (con motivo). Ambos reciben un correo.
- Alta de empleados **por DNI/NIE** (el email es opcional). Acceso a la web con **contraseña temporal** que se muestra una sola vez y que el empleado debe cambiar al primer acceso.
- Los empleados **no se borran**: se desactivan, conservando su historial.
- Cada empleado tiene **su espacio**: todos sus fichajes agrupados por jornada, sus ausencias, sus avisos de salida y sus nóminas.

### Nóminas

- La empresa (admin o un empleado con permiso de **contable**) sube la nómina en PDF (máx. 5 MB) eligiendo empleado y mes, con descripción opcional (por ejemplo, paga extra). El empleado recibe un correo si tiene email.
- Cada empleado ve y descarga **sus** nóminas desde "Mis nóminas", con aviso de las nuevas.
- Los archivos se guardan en un **disco privado** con nombre aleatorio y solo se sirven a su dueño o a quien las gestiona.
- El empleado conserva el acceso a sus nóminas **aunque la empresa tenga la suscripción caducada**.

### Cuenta, planes y facturación

- **Registro público** de empresa (`/registro`) con aceptación de términos y encargo de tratamiento, o con **Google**. **Verificación de email** obligatoria para los administradores que se registran por su cuenta.
- **Prueba gratuita de 15 días** al suscribirse por primera vez (con tarjeta, sin cobro hasta que termina). Quien ya fue cliente se vuelve a suscribir sin prueba. No se puede abrir una segunda suscripción ni un segundo pago mientras hay uno abierto.
- **Suscripción por Stripe**: cuota base mensual + precio por empleado extra sobre los incluidos, todo **con IVA incluido** (porcentaje configurable). Checkout con dirección y NIF/CIF para la factura.
- **Facturación**: portal de Stripe para gestionar tarjeta o cancelar, historial de facturas y descarga de PDF dentro de la app.
- **Licencias gratuitas por código**: el super admin crea códigos (duración, usos, tope de empleados) y la empresa los canjea en Facturación para usar la app sin Stripe.
- **Bloqueo por suscripción o licencia**: sin ninguna de las dos, la empresa solo puede acceder a Facturación. El kiosco también se bloquea. Los empleados siguen accediendo a sus nóminas.

### Administración de la plataforma (super admin)

- Alta, activación y desactivación de empresas clientes.
- **Tarifas** editables: plan directo (cuota base, empleados incluidos, precio por empleado extra, IVA) y **plan para asesorías** (precio por licencia, empleados por licencia, empleado extra, mínimo de licencias y precio recomendado al cliente final, con simulación de margen). Se sincronizan con Stripe.
- **Licencias**: crear, activar/desactivar y ver quién canjeó cada código; retirar una licencia a una empresa.
- **Mensajes de contacto** recibidos desde la web, con marcado de leído y los urgentes primero.

### Web pública

- **Portada** con recorrido por pestañas del producto, sección para empleados, límites del producto, precio con ejemplos, formulario de contacto, preguntas frecuentes y datos estructurados.
- Páginas de **funciones**, **guías** del registro de jornada, **plantilla gratuita** (Excel y PDF), **control horario por sector**, **contacto**, aviso legal y textos legales.
- Diseño **responsive** (móvil, tablet y escritorio): las tablas se convierten en tarjetas apiladas en móvil, y la cabecera pública tiene menú desplegable.

## Cumplimiento legal y seguridad

| Aspecto | Cómo se resuelve |
|---|---|
| **Registro de jornada fiable (RD-ley 8/2019)** | La tabla `fichajes` es *append-only*. Una segunda capa lo refuerza en la base de datos (ver más abajo). Las correcciones van en `fichaje_correcciones`. |
| **Conservación 4 años** | Los fichajes no se borran. Empleados y empresas se desactivan en lugar de eliminarse. |
| **RGPD** | Aceptación versionada de términos y encargo de tratamiento (`legal_aceptaciones`, con fecha e IP), registro de auditoría de acciones sensibles (`audit_logs`), datos de cada empresa aislados, consentimiento en el formulario de contacto. |
| **Aislamiento entre empresas** | Una sola base de datos con `empresa_id` y un *global scope* de Eloquent en todos los modelos de empresa. El contexto de tenant se limpia al terminar cada petición. Hay pruebas específicas de aislamiento. |
| **PIN y DNI del kiosco** | PIN de 6 dígitos guardado como HMAC-SHA256 (no en claro). URL del kiosco no adivinable y freno tras 10 fallos de PIN o DNI en 5 minutos desde el mismo sitio. **Aviso:** con el modo DNI cualquiera que conozca el DNI de un compañero podría fichar por él; el panel lo explica al elegir. El enlace del kiosco sigue siendo usable desde fuera del local. |
| **Contraseñas** | Hash con bcrypt (se re-cifra al entrar si sube el coste). Mínimo 8 caracteres con letras y números en todo el sistema. Las contraseñas temporales se muestran una sola vez y obligan a cambiarlas. |
| **Nóminas** | Disco privado, nombre de archivo aleatorio, descarga solo para el dueño o quien gestiona nóminas de esa empresa. Los PDF **no van cifrados en el servidor**; se protegen con acceso privado y copias de seguridad. |
| **Acceso** | Inicio de sesión limitado por intentos, CSRF, verificación de email (también al cambiar de correo en el perfil), bloqueo de empleados y empresas inactivos, cookie de sesión `secure` y `httponly` cuando la web es https, `https` forzado en todas las URLs cuando `APP_URL` es https. El admin no puede darse de baja a sí mismo. |
| **Cabeceras de seguridad** | En todas las respuestas (también errores y mantenimiento): `X-Content-Type-Options`, anti-iframe (`X-Frame-Options` y `frame-ancestors`), `form-action` limitado a la web y Stripe, `base-uri`, `object-src`, `Referrer-Policy`, `Permissions-Policy` y HSTS en producción. Ver `CabecerasSeguridad`. |
| **Entradas raras** | Filtros y parámetros de la URL con valores imposibles (fechas, listas en lugar de texto) se ignoran en vez de dar error; hay pruebas que lo comprueban. |
| **Ruta de despliegue** | `/deploy/ejecutar` está inerte (404) salvo que se configure `DEPLOY_TOKEN`, usa una lista cerrada de comandos y solo es necesaria en hosting sin SSH. |
| **Indexación** | Solo las páginas públicas listadas se pueden indexar; el resto lleva `X-Robots-Tag: noindex` y `Disallow` en `robots.txt`. |

**Límites conocidos:** los datos personales (nombre, DNI, correo, fichajes) están en la base de datos sin cifrado a nivel de campo; no hay verificación en dos pasos; en producción el usuario de la base de datos suele ser único y con todos los permisos, así que el bloqueo de borrado de fichajes depende del código salvo que se monte la base de datos endurecida. No se limita `script-src` en la CSP porque Alpine.js lo necesita. **`APP_KEY` no se puede cambiar en producción**: los PIN del kiosco se calculan con ella y dejarían de funcionar todos.

> Los textos legales incluidos en `resources/views/legal/` y las guías son una **plantilla de partida**: deben ser revisados por un profesional antes de usarse con clientes reales.

## Roles y permisos

| Rol | Cómo entra | Qué puede hacer |
|---|---|---|
| **super_admin** | Email + contraseña | Gestionar empresas, tarifas, códigos de licencia y mensajes de contacto. No entra en rutas de empresa. |
| **admin_empresa** | Email + contraseña, o Google | Todo lo de su empresa: empleados, fichajes y correcciones, ausencias, control horario, nóminas, ajustes del kiosco, facturación. |
| **empleado** | **DNI/NIE** + contraseña (o email si lo tiene) | Consultar y, si la empresa lo permite, fichar desde su cuenta; pedir ausencias, avisar de salidas, ver sus nóminas. En el kiosco ficha con PIN o DNI según el modo de su empresa. |
| **contable** (permiso) | Como un empleado | Un empleado con el permiso `gestiona_nominas` puede subir y gestionar las nóminas de toda la empresa sin ser admin. |

El campo de inicio de sesión acepta **email o DNI/NIE**: si contiene `@` se busca por email, si no por DNI/NIE. El DNI es único dentro de cada empresa, pero una persona puede estar en dos: entra en la cuenta cuya contraseña coincide (si coincide en varias, se le pide que use otra contraseña en una de ellas o su email).

## Stack tecnológico

- **Backend**: PHP 8.2+, [Laravel 12](https://laravel.com), Eloquent, MySQL (SQLite en memoria para las pruebas).
- **Frontend**: Blade + [Tailwind CSS 3](https://tailwindcss.com) + [Alpine.js](https://alpinejs.dev), compilado con [Vite](https://vite.dev). Sin SPA a propósito.
- **Autenticación**: Laravel Breeze (pila Blade, sin registro propio) adaptado, y [Laravel Socialite](https://laravel.com/docs/socialite) para Google.
- **Pagos**: [Laravel Cashier (Stripe)](https://laravel.com/docs/billing) con la **empresa** como entidad facturable.
- **Exportación**: [maatwebsite/excel](https://laravel-excel.com) (CSV/Excel) y [barryvdh/laravel-dompdf](https://github.com/barryvdh/laravel-dompdf) (PDF).
- **Zona horaria**: `Europe/Madrid` (el registro de jornada debe guardar la hora local real).

## Arquitectura

### Multiempresa (tenancy)

- Una **única base de datos**; las tablas de empresa llevan `empresa_id`.
- El trait `BelongsToTenant` aplica el *global scope* `EmpresaScope` y asigna `empresa_id` al crear.
- `App\Support\Tenant` guarda el contexto. Si no se ha fijado explícitamente, **cae al `empresa_id` del usuario autenticado**: es necesario porque el *route-model binding* se ejecuta antes que el middleware de tenant.
- El middleware `tenant` fija el contexto y lo **limpia en `terminate()`** para que no se filtre entre peticiones.

### Servicios y utilidades

| Pieza | Responsabilidad |
|---|---|
| `RegistroFichajes` | Registra un fichaje (web y kiosco): decide entrada o salida, da por olvidada una entrada de más de 16 h e ignora el doble toque. Es el único servicio de esta tabla que escribe. |
| `JornadasAgrupador` | Empareja entradas y salidas en jornadas y calcula horas (usando la hora corregida si existe). No empareja una entrada y una salida separadas más de 16 h. |
| `IncidenciasCalculador` | Retrasos, sin fichar, sin cerrar, no ha vuelto y horas de más, por día o periodo, con turnos partidos, turnos de noche y citas. |
| `EstadoEquipoCalculador` | Estado actual de cada empleado (trabajando, fuera, vacaciones, baja, salida avisada). |
| `AltaEmpresaService` | Crea empresa + administrador + aceptaciones legales; lo usan el alta manual del super admin y el registro público. |
| `Support\Avisos` | Envía notificaciones por correo sin romper la acción si el SMTP falla, y construye el mensaje base con saludo y nombre de la empresa. |
| `Support\ContenidoWeb` | Preguntas frecuentes y funciones escritas una sola vez, para la portada, `/funciones` y `llms-full.txt`. |

### Middleware propios

| Alias | Función |
|---|---|
| `tenant` | Identifica y fija la empresa del usuario. |
| `role:` | Restringe por rol. |
| `verificado` | Exige email verificado solo a `admin_empresa` del registro público. |
| `suscripcion` | Bloquea las funciones si la empresa no tiene suscripción ni licencia activa (consultando a Stripe antes de bloquear). |
| `gestor.nominas` | Admin o empleado con permiso de contable. |
| *(global, la primera)* `CabecerasSeguridad` | Cabeceras de seguridad en todas las respuestas. |
| *(global web)* `ForzarCambioPassword` | Obliga a elegir contraseña propia tras una temporal. |
| *(global web)* `SeoRobots` | Añade `noindex` a todo lo que no sea una página pública indexable. |

### Rutas por área

- Públicas: `/`, `/funciones`, `/guia/*`, `/plantilla-registro-jornada`, `/control-horario/*`, `/contacto`, `/registro`, `/legal/*`, `/kiosko/{token}`, `/stripe/webhook`, `/robots.txt`, `/sitemap.xml`, `/llms.txt`, `/llms-full.txt`.
- Autenticadas (empleado y admin): `/mis-fichajes`, `/mis-ausencias`, `/mis-nominas`, `/profile`.
- Administración de empresa: `/admin/*` (panel, fichajes, empleados y kiosco, ausencias, incidencias, facturación).
- Gestión de nóminas: `/gestion-nominas`.
- Super admin: `/super-admin/*` (empresas, tarifas, licencias, mensajes).

## Modelo de datos

| Tabla | Descripción |
|---|---|
| `empresas` | Clientes (tenants). Entidad facturable de Cashier; guarda el token del kiosco, su modo (`kiosko_modo`), si se puede fichar desde la cuenta (`fichaje_web`) y su licencia. |
| `users` | Usuarios de todos los roles (DNI/NIE único dentro de cada empresa, PIN hasheado, horario y tramos, permiso de contable…). |
| `fichajes` | Eventos de entrada/salida (origen web, kiosco o empresa; los que añade la empresa llevan autor y motivo). **Append-only.** |
| `fichaje_correcciones` | Correcciones de la hora de un fichaje, con motivo y autor. **Append-only.** |
| `ausencias` | Vacaciones, bajas y otras, con estado pendiente/aprobada/rechazada. |
| `citas` | Salidas avisadas dentro de la jornada (motivo libre y franja horaria). |
| `nominas` | Metadatos de las nóminas en PDF (el archivo vive en disco privado). |
| `legal_aceptaciones` | Qué versión de qué documento aceptó cada empresa, cuándo y desde qué IP. |
| `audit_logs` | Registro de acciones sensibles. |
| `tarifas` | Fila única con la tarifa directa, la de asesorías, el IVA y los IDs de Stripe (producto, precios, IVA y modo test/live). |
| `licencias` | Códigos de licencia gratuita (duración, usos, tope de empleados, caducidad del código). |
| `contactos` | Mensajes del formulario público de contacto. |
| `subscriptions`, `subscription_items` | Suscripciones de Cashier (la clave foránea es `empresa_id`). |

## Instalación en local

Probado en Windows con XAMPP; también vale cualquier entorno con PHP 8.2+ y MySQL.

### Requisitos

- PHP **8.2+** con las extensiones `pdo_mysql`, `mbstring`, `openssl`, `tokenizer`, `xml`, `ctype`, `fileinfo`, `bcmath` y además **`gd`**, **`zip`** e **`intl`** (necesarias para Excel/PDF y para formatear moneda; en XAMPP se activan en `php.ini`).
- Composer 2, Node.js 20+ y npm.
- MySQL / MariaDB.
- Opcional: [Stripe CLI](https://stripe.com/docs/stripe-cli) para probar los webhooks en local.

### Pasos

```bash
git clone https://github.com/alsesoftwaresl/fichaje.git
cd fichaje

composer install
npm install

cp .env.example .env
php artisan key:generate
```

1. Crea una base de datos MySQL vacía y rellena `DB_*` en `.env`.
2. Ejecuta las migraciones y el *seeder* (crea el super admin con `SUPER_ADMIN_EMAIL` / `SUPER_ADMIN_PASSWORD` de `.env`):

   ```bash
   php artisan migrate
   php artisan db:seed
   ```

   > Si montas la **base de datos endurecida** (siguiente apartado), las migraciones se ejecutan con `php artisan migrate --database=mysql_migrations`.

3. Compila los estilos y arranca:

   ```bash
   npm run build        # o `npm run dev` mientras desarrollas
   php artisan serve    # http://localhost:8000
   ```

Accede con el super admin, o crea una empresa desde `/registro`.

## Variables de entorno

Todas están documentadas en [`.env.example`](.env.example). Las principales:

| Variable | Para qué sirve |
|---|---|
| `APP_NAME`, `APP_URL`, `APP_TIMEZONE` | Nombre de la marca, URL pública (con `https://` en producción) y zona horaria (`Europe/Madrid`). |
| `APP_KEY` | Clave de cifrado. Se genera una vez y **no se cambia**: los PIN del kiosco, las sesiones y la clave de IndexNow dependen de ella. |
| `APP_ENV`, `APP_DEBUG` | En producción, `production` y `false` (con `true` se verían detalles internos en los errores). |
| `DB_*` | Conexión de la aplicación. |
| `DB_MIGRATIONS_USERNAME` / `DB_MIGRATIONS_PASSWORD` | Usuario con privilegios DDL, solo para migrar (conexión `mysql_migrations`). |
| `SUPER_ADMIN_EMAIL`, `SUPER_ADMIN_PASSWORD` | Credenciales del super admin que crea el *seeder*. |
| `STRIPE_KEY`, `STRIPE_SECRET`, `STRIPE_WEBHOOK_SECRET`, `CASHIER_CURRENCY` | Stripe. Usa claves `sk_test_…` fuera de producción. |
| `GOOGLE_CLIENT_ID`, `GOOGLE_CLIENT_SECRET`, `GOOGLE_REDIRECT_URI` | Acceso con Google. |
| `MAIL_*` | Correo. En local queda en `log`; en producción, SMTP del buzón (ver más abajo). |
| `LEGAL_NOMBRE`, `LEGAL_NIF`, `LEGAL_DOMICILIO`, `LEGAL_EMAIL`, `LEGAL_TELEFONO`, `LEGAL_REGISTRO_MERCANTIL` | Datos del titular de la web (aviso legal, pie, correos y datos estructurados). Lo vacío no se muestra. |
| `INDEXNOW_KEY` | Opcional: clave de IndexNow; si no se define se deriva de `APP_KEY`. |
| `DEPLOY_TOKEN` | Solo en hosting sin SSH; vacío en cualquier otro caso. |

> **Nunca subas `.env` al repositorio** (ya está en `.gitignore`).

## Base de datos endurecida (registro append-only)

Además de que el modelo `Fichaje` lanza una excepción si algo intenta editarlo o borrarlo, la base de datos puede reforzarlo con **dos usuarios MySQL**:

- `fichaje_app` (runtime): permisos normales en las tablas, pero solo `SELECT, INSERT` en `fichajes` y `fichaje_correcciones`. Ni siquiera saltándose la aplicación (phpMyAdmin, un script) se pueden editar ni borrar.
- Un usuario con privilegios DDL (`mysql_migrations`), usado **solo** para migrar.

Los scripts están en [`database/sql/`](database/sql) y el procedimiento completo en [`docs/db-hardening.md`](docs/db-hardening.md). Cada tabla nueva necesita su `GRANT` en `create_app_user.sql`.

## Facturación con Stripe, IVA y licencias

1. Crea las claves de **test** en el panel de Stripe y ponlas en `.env`. Activa el **portal de clientes** y los datos de empresa en Stripe.
2. Entra como super admin en **Tarifas** y guarda la tarifa: se crean el producto, los dos precios (cuota base y empleado extra) y el **IVA incluido** (tipo inclusivo) en Stripe.
3. Para recibir eventos en local:

   ```bash
   stripe listen --all-snapshot --forward-to localhost:8000/stripe/webhook
   ```

   Copia el `whsec_…` que imprime a `STRIPE_WEBHOOK_SECRET`.
4. Tarjeta de prueba: `4242 4242 4242 4242`.

- **Todos los precios incluyen IVA** (`tarifas.iva_porcentaje`, 21 % por defecto). La web, Facturación y la factura de Stripe desglosan base e IVA. Si el IVA aún no está creado en Stripe, no se deja suscribir.
- **Modo test y real**: la app detecta el modo por la clave (`sk_test_` / `sk_live_`). Al pasar de uno a otro **olvida los productos, precios, clientes y suscripciones del modo anterior** y los recrea al guardar la tarifa.
- La app **no depende solo del webhook**: antes de bloquear a una empresa consulta a Stripe y copia la suscripción en local, porque el usuario vuelve del pago antes de que llegue el aviso. En producción hay que configurar el webhook en el panel de Stripe apuntando a `https://tu-dominio/stripe/webhook`.
- **Licencias y asesorías**: los códigos de licencia (`ACH-XXXX-XXXX`) dan acceso sin Stripe, con duración y tope de empleados. El plan para asesorías está **configurado pero aún no se cobra automáticamente**: hoy se vende con factura y se reparten códigos.

## Acceso con Google

Solo para administradores de empresa (login y alta). Se necesita un cliente OAuth de tipo *Web application* en Google Cloud Console:

- **Authorized redirect URI**: `http://localhost:8000/auth/google/callback` (y la de producción).
- **Authorized JavaScript origins**: `http://localhost:8000` (sin ruta).
- Copia el Client ID y el Client Secret a `.env`.

Si el correo de Google ya pertenece a otra cuenta (super admin o empleado), se rechaza con un mensaje en vez de intentar crear otra empresa.

## Correo electrónico y avisos

En local `MAIL_MAILER=log`. En producción se usa el SMTP del buzón de la empresa (por ejemplo `info@achrono.es`, puerto 465 con `smtps`). Los correos salen en español, con la marca de Achrono (cabecera con logo, botón en el color de la marca y pie con los datos del titular), saludo personal y el nombre de la empresa del destinatario.

| Correo | Quién lo recibe |
|---|---|
| Verificación de email y restablecer contraseña | Quien se registra o la pide |
| Solicitud de ausencia | Administradores de la empresa |
| Aprobación o rechazo de ausencia | El empleado |
| Nómina nueva (solo aviso, sin importes) | El empleado |
| Resumen diario de incidencias (laborables, 11:00) | Administradores |
| Mensaje del formulario de contacto | `LEGAL_EMAIL` |

Si el servidor de correo falla, la acción sigue adelante y el fallo queda en el log; los mensajes de contacto además se guardan en la base de datos. Los empleados sin correo no reciben nada. Para comprobar el SMTP: `php artisan correo:probar` (o `cmd=correo:probar` en `/deploy/ejecutar`).

## Web pública, SEO y contenido

- **Datos estructurados** (JSON-LD): Organization, WebSite, SoftwareApplication con oferta (IVA incluido), FAQPage, Article, BreadcrumbList y ContactPage según la página.
- **`robots.txt`, `sitemap.xml` (con `lastmod`), `llms.txt` y `llms-full.txt`** se generan desde `SeoController`. `robots.txt` permite expresamente a los rastreadores de IA (GPTBot, ClaudeBot, PerplexityBot, Google-Extended…) y bloquea paneles, login, kiosco y despliegue.
- **IndexNow**: `php artisan seo:indexnow` avisa a Bing y otros buscadores de las páginas públicas; el fichero de verificación `/{clave}.txt` lo sirve la propia app.
- **Contenido**: la portada, `/funciones` y `llms-full.txt` comparten texto (`Support\ContenidoWeb`); las páginas por sector se definen en `config/sectores.php`; la **plantilla gratuita** genera Excel (con fórmulas) y PDF al vuelo.
- **Reglas de contenido**: no se publican cifras, opiniones ni logos inventados; todo lo que se afirma debe poder hacerse con la aplicación, y los límites (sin cuadrantes, sin geolocalización, necesita internet) se dicen.
- Página `404` de marca y página `503` de mantenimiento con estilos en línea.

## App instalable (PWA)

La web se puede instalar en el móvil: aparece un banner "Instala Achrono en tu móvil" en las pantallas con sesión (botón nativo en Android/Chrome; instrucciones de *Compartir → Añadir a pantalla de inicio* en iPhone). Incluye `manifest.json` (iconos 192/512 y *maskable*, atajos a fichajes, nóminas y ausencias) y un *service worker* que **no guarda datos privados**: solo muestra `/offline.html` si no hay conexión. La lógica del botón está en `resources/js/app.js`.

## Comandos y tareas programadas

| Comando | Qué hace |
|---|---|
| `incidencias:resumen` | Envía a los admins el resumen de incidencias de hoy. Programado en `routes/console.php` (laborables, 11:00). |
| `correo:probar` | Envía un correo de prueba a `LEGAL_EMAIL` y muestra la configuración SMTP (sin contraseña). |
| `seo:indexnow` | Avisa a los buscadores IndexNow de las páginas públicas. |
| `schedule:run` | Ejecuta lo programado; hay que llamarlo cada minuto (cron o tarea de Plesk). |

En hosting sin SSH, estos comandos (y `migrate`, `db:seed`, `storage:link`, las limpiezas de caché, `down` y `up`) se lanzan desde `/deploy/ejecutar?token=…&cmd=…`; la tarea programada de Plesk llama a `cmd=schedule:run`.

## Pruebas

```bash
php artisan test
```

La suite (unas 290 pruebas) usa SQLite en memoria y cubre, entre otras cosas: el aislamiento entre empresas, el registro append-only, el flujo de fichaje y correcciones, el kiosco con PIN y DNI, los roles, el bloqueo por suscripción y las licencias, el registro público y la verificación de email, el acceso con Google (simulado), las incidencias con turnos partidos y citas, las nóminas, el IVA y el cambio de modo de Stripe, los correos (español, marca y envío), el formulario de contacto, la PWA, el SEO (robots, sitemap, llms, IndexNow, noindex, datos estructurados) y las páginas de plantilla y sectores, los turnos de noche, las salidas olvidadas y el doble toque, el cobro (una sola prueba gratis, sin suscripciones dobles), las cabeceras de seguridad, la política de contraseñas y los parámetros raros en la URL.

Las llamadas reales a Stripe, al SMTP y a IndexNow no se hacen en las pruebas. La lista de comprobaciones manuales antes de salir está en [`docs/lista-pruebas-salida.md`](docs/lista-pruebas-salida.md).

La guía para ampliar el código sin romper nada (capas, reglas, cómo añadir funciones y módulos de pago, y por dónde escalar) está en [`docs/arquitectura.md`](docs/arquitectura.md). GitHub Actions ejecuta en cada push Pint, las pruebas y una compilación de los estilos que falla si `public/build` no está al día.

## Despliegue

La guía paso a paso para un **hosting compartido con Plesk y sin SSH** (Loading.es) está en [`docs/deploy-loading-es.md`](docs/deploy-loading-es.md): configuración de PHP, base de datos, despliegue desde Git, migraciones sin terminal, **modo mantenimiento** para pruebas, correo SMTP, tarea programada y paso a Stripe en modo *live*.

Flujo habitual de una actualización: `git push` → *Pull* en Plesk (Git) → `cmd=migrate` si hay migraciones nuevas → `cmd=config:clear` si cambió el `.env`.

Recordatorios para producción: `APP_ENV=production`, `APP_DEBUG=false`, HTTPS, claves *live* de Stripe, webhook y portal configurados, correo real, copias de seguridad de la base de datos y de `storage/app/private` (nóminas), y `DEPLOY_TOKEN` vacío cuando ya no se necesite la ruta de despliegue.

## Estructura del proyecto

```
app/
  Console/Commands/      # incidencias:resumen, correo:probar, seo:indexnow
  Exports/               # Excel de fichajes y plantilla de registro de jornada
  Http/Controllers/      # Controladores por área (Admin/, SuperAdmin/, Auth/…)
  Http/Middleware/       # tenant, suscripción, verificación, nóminas, SEO, cambio de contraseña…
  Models/                # Modelos Eloquent (Concerns/BelongsToTenant, Scopes/EmpresaScope)
  Notifications/         # Correos de ausencias, nóminas, resumen y contacto
  Services/              # Cálculo de jornadas, incidencias y estado del equipo; alta de empresa
  Support/               # Tenant, Avisos, ContenidoWeb
config/                  # legal.php (titular), seo.php, sectores.php, deploy.php…
database/
  migrations/            # Esquema
  sql/                   # Usuario MySQL restringido y endurecimiento de fichajes
docs/                    # Endurecimiento de la BD, guía de despliegue y lista de pruebas
lang/                    # Traducciones al español (incluye lang/es.json de los correos)
resources/views/         # Blade: portada, funciones, sectores, plantilla, panel, kiosco, legal, correos…
routes/                  # web.php, auth.php y console.php
tests/                   # Pruebas de feature y unitarias
public/                  # Logo, iconos, manifest.json, sw.js, offline.html y build de Vite
```

## Convenciones de trabajo

- Interfaz, mensajes y documentación en **español**.
- **Un commit por cada cambio terminado**, con mensaje descriptivo y las pruebas en verde.
- Cada tabla nueva se añade al `GRANT` de `database/sql/create_app_user.sql`.
- Las migraciones se ejecutan con la conexión `mysql_migrations` cuando se usa la base de datos endurecida.
- Después de cambiar vistas con clases nuevas de Tailwind hay que ejecutar `npm run build` y **commitear `public/build`**: el hosting no tiene Node, así que los estilos compilados viajan en el repositorio.
- En la web pública, nada de estadísticas, opiniones ni afirmaciones que la aplicación no cumpla.

## Pendiente / hoja de ruta

- **Antes de abrir al público**: Stripe en modo *live* (cuenta activada, webhook y portal), revisión de los textos legales por un profesional, copias de seguridad comprobadas, cambiar las credenciales que se hayan compartido y cerrar `/deploy/ejecutar`.
- Informe mensual por empleado y pausas en el fichaje; saldo de vacaciones y festivos.
- Aviso por correo al empleado si se le olvida fichar la salida (hoy se le avisa al fichar la siguiente vez y la empresa la completa), y aviso de salida anticipada.
- Pasar a Tailwind 4 (queda un aviso moderado de `npm audit` en una dependencia de compilación de Tailwind 3; no llega al servidor).
- Verificación en dos pasos, cifrado de campos sensibles y de las nóminas, y exportación/anonimización de datos (RGPD).
- Plan para asesorías completo: cobro mensual automático por licencia, panel de la asesoría y cobro del empleado extra (hoy solo están configurados los precios y los códigos).
- Limitar el kiosco a la IP de la empresa, si algún cliente lo pide.
- Exportar a CSV/Excel/PDF las incidencias del Control horario; histórico de horarios.

## Licencia

Código de **ALSE SOFTWARE, S.L.** Licencia pendiente de definir; hasta entonces, todos los derechos reservados.
