<p align="center">
  <img src="public/images/logo.png" alt="Achrono" width="320">
</p>

<h3 align="center">Control horario y fichaje de empleados para empresas españolas</h3>

<p align="center">
  SaaS multiempresa · Registro de jornada conforme al RD-ley 8/2019 y al RGPD · Kiosco con PIN · Nóminas · Facturación con Stripe
</p>

<p align="center">
  <img alt="PHP" src="https://img.shields.io/badge/PHP-8.2%2B-777BB4?logo=php&logoColor=white">
  <img alt="Laravel" src="https://img.shields.io/badge/Laravel-12-FF2D20?logo=laravel&logoColor=white">
  <img alt="Tailwind" src="https://img.shields.io/badge/Tailwind-3-06B6D4?logo=tailwindcss&logoColor=white">
  <img alt="Alpine.js" src="https://img.shields.io/badge/Alpine.js-3-8BC0D0?logo=alpinedotjs&logoColor=white">
  <img alt="Stripe" src="https://img.shields.io/badge/Stripe-Cashier-635BFF?logo=stripe&logoColor=white">
  <img alt="MySQL / MariaDB" src="https://img.shields.io/badge/MySQL%20%2F%20MariaDB-4479A1?logo=mysql&logoColor=white">
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
11. [Facturación con Stripe](#facturación-con-stripe)
12. [Acceso con Google](#acceso-con-google)
13. [Correo electrónico](#correo-electrónico)
14. [Pruebas](#pruebas)
15. [Despliegue](#despliegue)
16. [Estructura del proyecto](#estructura-del-proyecto)
17. [Convenciones de trabajo](#convenciones-de-trabajo)
18. [Pendiente / hoja de ruta](#pendiente--hoja-de-ruta)
19. [Licencia](#licencia)

---

## Qué es Achrono

Achrono es una aplicación web **SaaS multiempresa** para llevar el registro de jornada de los empleados de una empresa. Cada empresa cliente se registra por su cuenta, tiene su propio espacio (con los datos aislados del resto), da de alta a su plantilla y ficha desde una **tablet en la entrada** con un PIN, desde el móvil o desde el ordenador.

Está pensada para el mercado español: el registro de jornada es **inalterable** (las correcciones se guardan aparte, con motivo y autor), se conserva el histórico y se puede **exportar** para nóminas o para una inspección de trabajo. Además incluye gestión de ausencias, horarios con avisos de retrasos y horas extra, nóminas descargables por cada empleado y cobro por suscripción.

El proyecto lo desarrolla **ALSE SOFTWARE, S.R.L.**

## Funcionalidades

### Fichaje

- **Kiosco con PIN**: una tablet u ordenador fijo con una URL única por empresa (`/kiosko/{token}`). Cada empleado ficha con su PIN de 6 dígitos, sin iniciar sesión. Alterna entrada/salida automáticamente.
- **Fichaje web**: los empleados también pueden fichar desde su cuenta (`/mis-fichajes`).
- **Jornadas**: entrada y salida se muestran en una sola fila con las **horas trabajadas** ya calculadas (teniendo en cuenta las correcciones).
- **Correcciones trazables**: el admin puede corregir la hora de un fichaje; el original **nunca se modifica**. La corrección queda en otra tabla con su motivo, su autor y la fecha.
- **Exportación** de fichajes por empleado y rango de fechas en **CSV, Excel y PDF**.

### Horarios, incidencias y control horario

- **Horario esperado opcional** por empleado: días laborables y hora de entrada/salida.
- **Turno partido**: hasta 3 tramos al día (por ejemplo 09:00–14:00 y 16:00–19:00). Las horas esperadas son la suma de los tramos y los avisos se evalúan tramo a tramo.
- **Avisos del panel** (con margen de 10 minutos): *retraso*, *sin fichar*, *fichaje sin cerrar*, *no ha vuelto* y *horas de más*.
- **Control horario**: pantalla con filtro por empleado y fechas (hasta 93 días) que resume, por empleado, retrasos, minutos de retraso, días sin fichar, fichajes sin cerrar y horas extra, con el detalle día a día.
- **Contador en el menú** con las incidencias de hoy.
- **Salidas avisadas** (médico, una gestión…): el empleado avisa de que sale dentro de su horario, explica el motivo y dice a qué hora vuelve. Surte efecto al momento, justifica la ausencia (no sale como retraso ni como fichaje sin cerrar), el admin lo ve en el panel y puede anularlo, y se avisa si no se vuelve a la hora prevista.
- Los avisos son **solo dentro de la aplicación** (sin correo).

### Equipo y ausencias

- **Estado del equipo** en tiempo real: *trabajando*, *fuera*, *vacaciones*, *baja*, *salida avisada*. También visible en la lista de empleados.
- **Vacaciones y bajas** con flujo de solicitud: el empleado solicita y el admin aprueba o rechaza (con motivo).
- Alta de empleados **por DNI/NIE** (el email es opcional). Acceso a la web con **contraseña temporal** que se muestra una sola vez y que el empleado debe cambiar al primer acceso.
- Los empleados **no se borran**: se desactivan, conservando su historial.

### Nóminas

- La empresa (admin o un empleado con permiso de **contable**) sube la nómina en PDF (máx. 5 MB) eligiendo empleado y mes, con descripción opcional (por ejemplo, paga extra).
- Cada empleado ve y descarga **sus** nóminas desde "Mis nóminas", con aviso de las nuevas.
- Los archivos se guardan en un **disco privado** con nombre aleatorio y solo se sirven a su dueño o a quien las gestiona.
- El empleado conserva el acceso a sus nóminas **aunque la empresa tenga la suscripción caducada**.

### Cuenta, planes y facturación

- **Registro público** de empresa (`/registro`) con aceptación de términos y encargo de tratamiento, o con **Google**.
- **Verificación de email** obligatoria para los administradores que se registran por su cuenta.
- **Prueba gratuita de 15 días** al suscribirse (con tarjeta, sin cobro hasta que termina).
- **Suscripción por Stripe**: cuota base mensual + precio por empleado extra sobre los incluidos (configurable por el super admin). La cantidad de empleados extra se sincroniza con Stripe al dar de alta o desactivar empleados.
- **Facturación**: portal de Stripe para gestionar tarjeta o cancelar, historial de facturas y descarga de PDF dentro de la app.
- **Bloqueo por suscripción**: sin suscripción activa, la empresa solo puede acceder a Facturación. El kiosco también se bloquea. Los empleados siguen accediendo a sus nóminas.

### Administración de la plataforma (super admin)

- Alta, activación y desactivación de empresas clientes.
- Edición de la **tarifa** global (cuota base, empleados incluidos, precio por empleado extra) con sincronización de precios en Stripe.

### Web pública

- **Landing** optimizada para SEO (metadatos, Open Graph, JSON-LD), con precio dinámico leído de la base de datos.
- Páginas legales: términos, privacidad, encargo de tratamiento y cookies.
- Diseño **responsive** (móvil, tablet y escritorio): las tablas se convierten en tarjetas apiladas en móvil.

## Cumplimiento legal y seguridad

| Aspecto | Cómo se resuelve |
|---|---|
| **Registro de jornada fiable (RD-ley 8/2019)** | La tabla `fichajes` es *append-only*. Una segunda capa lo refuerza en la base de datos (ver más abajo). Las correcciones van en `fichaje_correcciones`. |
| **Conservación 4 años** | Los fichajes no se borran. Empleados y empresas se desactivan en lugar de eliminarse. |
| **RGPD** | Aceptación versionada de términos y encargo de tratamiento (`legal_aceptaciones`, con fecha e IP), registro de auditoría de acciones sensibles (`audit_logs`), datos de cada empresa aislados. |
| **Aislamiento entre empresas** | Una sola base de datos con `empresa_id` y un *global scope* de Eloquent en todos los modelos de empresa. El contexto de tenant se limpia al terminar cada petición. Hay pruebas específicas de aislamiento. |
| **PIN del kiosco** | 6 dígitos guardados como HMAC-SHA256 (no en claro), único por empresa, con URL del kiosco no adivinable y límite de intentos. |
| **Contraseñas** | Hash con bcrypt. Las contraseñas temporales se muestran una sola vez y obligan a cambiarlas. |
| **Nóminas** | Disco privado, nombre de archivo aleatorio, descarga solo para el dueño o quien gestiona nóminas de esa empresa. Los PDF **no van cifrados en el servidor**; se protegen con acceso privado y copias de seguridad. |
| **Acceso** | Inicio de sesión limitado por intentos, CSRF, verificación de email, bloqueo de empleados y empresas inactivos. |
| **Ruta de despliegue** | `/deploy/ejecutar` está inerte (404) salvo que se configure `DEPLOY_TOKEN`, usa una lista cerrada de comandos y solo es necesaria en hosting sin SSH. |

> Los textos legales incluidos en `resources/views/legal/` son una **plantilla de partida**: deben ser revisados por un profesional antes de usarse con clientes reales.

## Roles y permisos

| Rol | Cómo entra | Qué puede hacer |
|---|---|---|
| **super_admin** | Email + contraseña | Gestionar empresas y tarifas. No entra en rutas de empresa. |
| **admin_empresa** | Email + contraseña, o Google | Todo lo de su empresa: empleados, fichajes y correcciones, ausencias, control horario, nóminas, facturación. |
| **empleado** | **DNI/NIE** + contraseña (o email si lo tiene) | Fichar, ver su historial, pedir ausencias, avisar de salidas, ver sus nóminas. En el kiosco ficha solo con PIN. |
| **contable** (permiso) | Como un empleado | Un empleado con el permiso `gestiona_nominas` puede subir y gestionar las nóminas de toda la empresa sin ser admin. |

El campo de inicio de sesión acepta **email o DNI/NIE**: si contiene `@` se busca por email, si no por DNI/NIE.

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

### Servicios de cálculo (solo presentación, no persisten datos)

| Servicio | Responsabilidad |
|---|---|
| `JornadasAgrupador` | Empareja entradas y salidas en jornadas y calcula horas (usando la hora corregida si existe). |
| `IncidenciasCalculador` | Retrasos, sin fichar, sin cerrar, no ha vuelto y horas de más, por día o periodo, con turnos partidos y citas. |
| `EstadoEquipoCalculador` | Estado actual de cada empleado (trabajando, fuera, vacaciones, baja, salida avisada). |
| `AltaEmpresaService` | Crea empresa + administrador + aceptaciones legales; lo usan el alta manual del super admin y el registro público. |

### Middleware propios

| Alias | Función |
|---|---|
| `tenant` | Identifica y fija la empresa del usuario. |
| `role:` | Restringe por rol. |
| `verificado` | Exige email verificado solo a `admin_empresa` del registro público. |
| `suscripcion` | Bloquea las funciones si la empresa no tiene suscripción activa (consultando a Stripe antes de bloquear). |
| `gestor.nominas` | Admin o empleado con permiso de contable. |
| *(global web)* `ForzarCambioPassword` | Obliga a elegir contraseña propia tras una temporal. |

### Rutas por área

- Públicas: `/`, `/registro`, `/legal/*`, `/kiosko/{token}`, `/stripe/webhook`.
- Autenticadas (empleado y admin): `/mis-fichajes`, `/mis-ausencias`, `/mis-nominas`, `/profile`.
- Administración de empresa: `/admin/*` (panel, fichajes, empleados, ausencias, incidencias, facturación).
- Gestión de nóminas: `/gestion-nominas`.
- Super admin: `/super-admin/*`.

## Modelo de datos

| Tabla | Descripción |
|---|---|
| `empresas` | Clientes (tenants). Entidad facturable de Cashier; guarda el token del kiosco. |
| `users` | Usuarios de todos los roles (DNI/NIE único, PIN hasheado, horario y tramos, permiso de contable…). |
| `fichajes` | Eventos de entrada/salida. **Append-only.** |
| `fichaje_correcciones` | Correcciones de la hora de un fichaje, con motivo y autor. **Append-only.** |
| `ausencias` | Vacaciones, bajas y otras, con estado pendiente/aprobada/rechazada. |
| `citas` | Salidas avisadas dentro de la jornada (motivo libre y franja horaria). |
| `nominas` | Metadatos de las nóminas en PDF (el archivo vive en disco privado). |
| `legal_aceptaciones` | Qué versión de qué documento aceptó cada empresa, cuándo y desde qué IP. |
| `audit_logs` | Registro de acciones sensibles. |
| `tarifas` | Fila única con la tarifa global y los IDs de precios en Stripe. |
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
| `APP_NAME`, `APP_URL`, `APP_TIMEZONE` | Nombre de la marca, URL pública y zona horaria (`Europe/Madrid`). |
| `DB_*` | Conexión de la aplicación. |
| `DB_MIGRATIONS_USERNAME` / `DB_MIGRATIONS_PASSWORD` | Usuario con privilegios DDL, solo para migrar (conexión `mysql_migrations`). |
| `SUPER_ADMIN_EMAIL`, `SUPER_ADMIN_PASSWORD` | Credenciales del super admin que crea el *seeder*. |
| `STRIPE_KEY`, `STRIPE_SECRET`, `STRIPE_WEBHOOK_SECRET`, `CASHIER_CURRENCY` | Stripe. Usa claves `sk_test_…` fuera de producción. |
| `GOOGLE_CLIENT_ID`, `GOOGLE_CLIENT_SECRET`, `GOOGLE_REDIRECT_URI` | Acceso con Google. |
| `DEPLOY_TOKEN` | Solo en hosting sin SSH; vacío en cualquier otro caso. |
| `MAIL_*` | Correo. En local queda en `log`. |

> **Nunca subas `.env` al repositorio** (ya está en `.gitignore`).

## Base de datos endurecida (registro append-only)

Además de que el modelo `Fichaje` lanza una excepción si algo intenta editarlo o borrarlo, la base de datos puede reforzarlo con **dos usuarios MySQL**:

- `fichaje_app` (runtime): permisos normales en las tablas, pero solo `SELECT, INSERT` en `fichajes` y `fichaje_correcciones`. Ni siquiera saltándose la aplicación (phpMyAdmin, un script) se pueden editar ni borrar.
- Un usuario con privilegios DDL (`mysql_migrations`), usado **solo** para migrar.

Los scripts están en [`database/sql/`](database/sql) y el procedimiento completo en [`docs/db-hardening.md`](docs/db-hardening.md). Cada tabla nueva necesita su `GRANT` en `create_app_user.sql`.

## Facturación con Stripe

1. Crea las claves de **test** en el panel de Stripe y ponlas en `.env`.
2. Entra como super admin en **Tarifas** y guarda la tarifa: se crean el producto y los dos precios (cuota base y empleado extra) en Stripe.
3. Para recibir eventos en local:

   ```bash
   stripe listen --all-snapshot --forward-to localhost:8000/stripe/webhook
   ```

   Copia el `whsec_…` que imprime a `STRIPE_WEBHOOK_SECRET`.
4. Tarjeta de prueba: `4242 4242 4242 4242`.

La app **no depende solo del webhook**: antes de bloquear a una empresa consulta a Stripe y copia la suscripción en local, porque el usuario vuelve del pago antes de que llegue el aviso. En producción hay que configurar el webhook en el panel de Stripe (modo *live*) apuntando a `https://tu-dominio/stripe/webhook`.

## Acceso con Google

Solo para administradores de empresa (login y alta). Se necesita un cliente OAuth de tipo *Web application* en Google Cloud Console:

- **Authorized redirect URI**: `http://localhost:8000/auth/google/callback` (y la de producción).
- **Authorized JavaScript origins**: `http://localhost:8000` (sin ruta).
- Copia el Client ID y el Client Secret a `.env`.

## Correo electrónico

En local `MAIL_MAILER=log`: los correos (verificación de email, etc.) se escriben en `storage/logs/laravel.log`. Para producción hay que configurar un proveedor real (está pensado Resend o SMTP) y un dominio verificado.

## Pruebas

```bash
php artisan test
```

La suite usa SQLite en memoria y cubre, entre otras cosas, el aislamiento entre empresas, el registro append-only, el flujo de fichaje y correcciones, el kiosco, los roles, el bloqueo por suscripción, el registro público y la verificación de email, el acceso con Google (simulado), las incidencias con turnos partidos y citas, las nóminas y el acceso web de empleados.

Las llamadas reales a Stripe no se hacen en las pruebas.

## Despliegue

La guía paso a paso para un **hosting compartido con Plesk y sin SSH** (Loading.es) está en [`docs/deploy-loading-es.md`](docs/deploy-loading-es.md): configuración de PHP, base de datos, subida por FTP o con el Laravel Toolkit de Plesk, migraciones sin terminal y paso a Stripe en modo *live*.

Recordatorios para producción: `APP_ENV=production`, `APP_DEBUG=false`, HTTPS, claves *live* de Stripe, webhook configurado, correo real y copias de seguridad de la base de datos y de `storage/app/private` (nóminas).

## Estructura del proyecto

```
app/
  Http/Controllers/      # Controladores por área (Admin/, SuperAdmin/, Auth/…)
  Http/Middleware/       # tenant, suscripción, verificación, nóminas, cambio de contraseña…
  Models/                # Modelos Eloquent (Concerns/BelongsToTenant, Scopes/EmpresaScope)
  Services/              # Cálculo de jornadas, incidencias y estado del equipo; alta de empresa
  Support/Tenant.php     # Contexto de empresa actual
config/                  # legal.php (versiones de documentos), deploy.php…
database/
  migrations/            # Esquema
  sql/                   # Usuario MySQL restringido y endurecimiento de fichajes
docs/                    # Endurecimiento de la BD y guía de despliegue
resources/views/         # Blade: landing, panel, kiosco, nóminas, legal…
routes/                  # web.php y auth.php
tests/                   # Pruebas de feature y unitarias
public/images/           # Logo y favicon
```

## Convenciones de trabajo

- Interfaz, mensajes y documentación en **español**.
- **Un commit por cada cambio terminado**, con mensaje descriptivo y las pruebas en verde.
- Cada tabla nueva se añade al `GRANT` de `database/sql/create_app_user.sql`.
- Las migraciones se ejecutan con la conexión `mysql_migrations` cuando se usa la base de datos endurecida.
- Después de cambiar vistas con clases nuevas de Tailwind hay que volver a ejecutar `npm run build`.

## Pendiente / hoja de ruta

- Configurar el **correo real** (Resend o SMTP) y verificar el dominio.
- Publicar en producción con el dominio propio y el webhook de Stripe en modo *live*.
- Exportar a CSV/Excel/PDF las incidencias del Control horario.
- Histórico de horarios (hoy el control usa el horario actual de cada empleado).
- Cifrado en reposo de las nóminas.
- Revisión de los textos legales por un profesional.

## Licencia

Código de **ALSE SOFTWARE, S.R.L.** Licencia pendiente de definir; hasta entonces, todos los derechos reservados.
