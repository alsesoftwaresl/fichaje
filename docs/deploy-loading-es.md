# Desplegar en Loading.es (hosting compartido Plesk)

Loading.es es hosting compartido con panel **Plesk**, no un VPS: no trae
terminal SSH. Pero su soporte confirmó que Plesk tiene el **Laravel Toolkit**
integrado, que hace casi todo esto sin terminal: `composer install`, editor de
`.env` y ejecución de comandos `artisan` (incluido `migrate`) desde el propio
panel. Es el camino a seguir primero. Si por lo que sea no está disponible o
falla algo, la Opción B (manual por FTP) es el respaldo, y usa la ruta
`/deploy/ejecutar` (`app/Http/Controllers/DeployController.php`) como
sustituto de `artisan` sin terminal.

## Opción A: Laravel Toolkit de Plesk (preferido)

1. **Prepara el proyecto en tu máquina**: necesitas `npm run build` hecho
   (Plesk no compila Tailwind/Vite), pero **no** subas `vendor/` ni
   `node_modules/` — el toolkit instala Composer él solo.
   ```bash
   cd fichaje-app
   npm run build
   ```
2. **Sube el proyecto**: por FTP o por el *Administrador de archivos* de
   Plesk, sube todo **excepto** `vendor/`, `node_modules/`, `tests/`, `.git/`
   a una carpeta del dominio (p. ej. `achrono/`).
3. **Activa el toolkit**: en Plesk, entra a tu dominio → busca el icono/tab
   **Laravel** (si no lo ves, pídele a soporte de Loading que te lo active
   para `achrono.es` — ya confirmaron que está disponible). Indícale la
   carpeta donde subiste el proyecto.
4. El toolkit debería:
   - Detectar `artisan` y configurar solo él el *document root* en `public/`.
   - Ofrecer un botón para **instalar dependencias de Composer**. Pulsa ahí.
   - Dar un **editor de variables de entorno** (equivalente a `.env`) — rellena
     los valores de la sección "Variables de entorno" más abajo ahí en vez de
     subir un archivo `.env` a mano.
   - Dar una opción de **ejecutar comandos artisan** — ahí lanzas `migrate
     --force`, `storage:link`, etc. en vez de usar `/deploy/ejecutar`.
5. Activa el **certificado SSL** (Let's Encrypt, gratis, ya lo incluye el
   plan) para el dominio desde Plesk si no está ya.

Si el toolkit no te deja hacer alguno de estos pasos (algunos planes
compartidos limitan ciertas acciones), sigue con la Opción B solo para esa
parte concreta — no hace falta repetir todo.

## Opción B: manual por FTP (si el toolkit falla o no está)

### 1. PHP y base de datos en Plesk

- PHP 8.2, con las extensiones: `pdo_mysql`, `mbstring`, `openssl`,
  `tokenizer`, `xml`, `ctype`, `json`, `bcmath`, `fileinfo`, `gd`, `zip`,
  `intl`. Si falta alguna, la app falla al cargar (ya nos pasó en local con
  `gd`/`zip`/`intl` — ver `docs/db-hardening.md`).
- Crea una base de datos MySQL y un usuario con todos los permisos sobre ella.
- *Document root* del dominio apuntando a `achrono/public` (si el toolkit no
  lo hizo ya).
- **Usuario de BD restringido (opcional, append-only)**: en *Bases de datos* →
  tu base → *Usuarios de la base de datos*, mira si puedes añadir un segundo
  usuario sin UPDATE/DELETE en `fichajes`/`fichaje_correcciones` (ver
  `database/sql/create_app_user.sql` y `database/sql/harden_fichajes.sql`). Si
  no se puede granular por tabla, no pasa nada grave: el bloqueo real ya está
  en el código (`app/Models/Fichaje.php` lanza excepción si algo intenta
  editar o borrar), esto es solo una segunda capa.

### 2. Build local y subida completa

```bash
cd fichaje-app
composer install --no-dev --optimize-autoloader
npm run build
```

Sube **todo** el proyecto (incluido `vendor/` esta vez) por FTP a `achrono/`,
excepto `node_modules/`, `tests/`, `.git/`, `storage/logs/*.log`. Dale permisos
de escritura (775) a `storage/` y `bootstrap/cache/` desde el Administrador de
archivos.

### 3. `.env` a mano

Crea `achrono/.env` a partir de `.env.example` (está en el repo) con los
valores reales — ver la sección "Variables de entorno" abajo.

**`APP_KEY`**: sin terminal no hay `artisan key:generate`. Genera 32 bytes
en base64 en tu máquina:
```bash
php -r "echo 'base64:'.base64_encode(random_bytes(32)).PHP_EOL;"
```

### 4. Migraciones sin terminal

Con `DEPLOY_TOKEN` puesto en el `.env` del servidor, visita desde el
navegador:
```
https://achrono.es/deploy/ejecutar?token=TU_TOKEN&cmd=migrate
```
Después crea el **super admin** inicial (con `SUPER_ADMIN_EMAIL` y
`SUPER_ADMIN_PASSWORD` del `.env` del servidor; se puede repetir sin duplicarlo):
```
https://achrono.es/deploy/ejecutar?token=TU_TOKEN&cmd=db:seed
https://achrono.es/deploy/ejecutar?token=TU_TOKEN&cmd=storage:link
```
Sin super admin nadie puede guardar las tarifas (que es lo que crea los
precios en Stripe) ni dar de alta empresas a mano.

Comandos disponibles (cambia `cmd=`): `migrate`, `db:seed`, `storage:link`,
`config:clear`, `route:clear`, `view:clear`, `cache:clear`,
`optimize:clear`, `schedule:run` (tareas programadas), `correo:probar` (correo de prueba), `down` y `up` (mantenimiento). La ruta da 404
siempre si `DEPLOY_TOKEN` está vacío, así que no pasa nada por dejarla en el
código — solo no compartas el token.

## Correo y avisos

La app envía correos reales (verificación de email, recuperar contraseña) y
avisos: solicitud de ausencia al admin, aprobación/rechazo al empleado, nómina
nueva y un **resumen diario de incidencias** (laborables a las 11:00) a los
admins. Los empleados que entran solo con DNI (sin email) no reciben correos.

1. **Crea un buzón** en Plesk → *Correo* → *Crear dirección de correo*, por
   ejemplo `info@achrono.es`. Apunta su contraseña.
2. **Edita el `.env` del servidor** (Administrador de archivos):
   ```
   MAIL_MAILER=smtp
   MAIL_HOST=<servidor SMTP que indica Plesk para el buzón>
   MAIL_PORT=465
   MAIL_SCHEME=smtps
   MAIL_USERNAME=info@achrono.es
   MAIL_PASSWORD=<contraseña del buzón>
   MAIL_FROM_ADDRESS="info@achrono.es"
   MAIL_FROM_NAME="Achrono"
   ```
   Después limpia la configuración: `cmd=config:clear`.
3. **Para que no caigan en spam**, comprueba en el DNS de `achrono.es` que
   existen los registros SPF y DKIM del dominio (Plesk los crea al activar el
   correo del dominio).
4. **Resumen diario**: crea una *Tarea programada* en Plesk (Herramientas y
   configuración → *Tareas programadas* o la del dominio), cada minuto, de tipo
   *Obtener una URL*:
   ```
   https://achrono.es/deploy/ejecutar?token=TU_TOKEN&cmd=schedule:run
   ```
   Laravel decide por sí solo qué toca ejecutar cada minuto. Con la web en
   mantenimiento no se ejecuta; ábrela al público antes.

**Comprobar que funciona:** abre `https://achrono.es/deploy/ejecutar?token=TU_TOKEN&cmd=correo:probar`.
Envía un correo de prueba a `info@achrono.es` y, si falla, te dice el motivo (usuario
incorrecto, servidor que no responde, certificado...).

Si el correo falla, no se rompe nada: el aviso se anota en
`storage/logs/laravel.log` y la acción (aprobar, subir nómina…) continúa.
## Variables de entorno (tanto si usas el toolkit como si editas `.env` a mano)

```
APP_NAME="Achrono"
APP_ENV=production
APP_KEY=                      # ver cómo generarlo arriba
APP_DEBUG=false
APP_URL=https://achrono.es
APP_TIMEZONE=Europe/Madrid

DB_CONNECTION=mysql
DB_HOST=                      # el que te dé Plesk (normalmente localhost)
DB_DATABASE=
DB_USERNAME=
DB_PASSWORD=

STRIPE_KEY=pk_live_...
STRIPE_SECRET=sk_live_...
STRIPE_WEBHOOK_SECRET=whsec_...   # lo da Stripe al crear el webhook, ver abajo

DEPLOY_TOKEN=                 # solo si usas la Opción B; una cadena larga y aleatoria

# Titular de la web (aviso legal y pie de página; lo vacío no se muestra)
LEGAL_NOMBRE="ALSE SOFTWARE, S.L."
LEGAL_NIF=
LEGAL_DOMICILIO=
LEGAL_EMAIL=
LEGAL_TELEFONO=
LEGAL_REGISTRO_MERCANTIL=
```

## Subir en modo mantenimiento para hacer pruebas

Sirve para tener la web ya en el servidor y probarla tú **sin que nadie más
entre**: los visitantes ven una página "Volvemos muy pronto" (con código 503,
así Google no la indexa) y tú entras con una clave secreta.

1. Sube y configura todo como arriba (código, `.env`, migraciones). Mientras
   pruebas, deja las **claves de Stripe en modo test** y `APP_DEBUG=false`.
2. Activa el mantenimiento desde el navegador (elige una clave secreta de 12 a
   64 caracteres: letras, números, `-` o `_`; no la compartas):
   ```
   https://achrono.es/deploy/ejecutar?token=TU_TOKEN&cmd=down&secreto=TU_CLAVE_SECRETA
   ```
   La respuesta te recuerda la dirección de acceso.
3. Para entrar **tú**, abre una sola vez en tu navegador:
   ```
   https://achrono.es/TU_CLAVE_SECRETA
   ```
   Te deja una cookie y a partir de ahí ves la web normal en ese navegador. Para
   comprobar lo que ve un visitante, abre la web en una ventana privada.
4. Para **abrir la web al público**, con la cookie ya puesta:
   ```
   https://achrono.es/deploy/ejecutar?token=TU_TOKEN&cmd=up
   ```
   Si por lo que sea no puedes, borra por FTP o desde el Administrador de
   archivos el fichero `achrono/storage/framework/down`: es lo que activa el
   mantenimiento.

Detalles:
- El webhook de Stripe **sigue funcionando** en mantenimiento (no recibe el 503),
  así puedes probar suscripciones de test completas.
- Si usas el inicio de sesión con Google, añade también la URL de producción
  (`https://achrono.es/auth/google/callback`) a los *Authorized redirect URIs*
  del cliente OAuth.
- Para probar pagos de test en producción, crea el webhook de **modo test** en
  Stripe apuntando a `https://achrono.es/stripe/webhook`.

## Stripe en real

1. En el [Dashboard de Stripe](https://dashboard.stripe.com), cambia a modo
   **Live** y copia `pk_live_...` / `sk_live_...`.
2. En *Developers → Webhooks*, añade un endpoint apuntando a
   `https://achrono.es/stripe/webhook`, con los mismos eventos que uses en
   local. Copia el `whsec_...`.
3. Sincroniza las tarifas reales desde `/super-admin/tarifas` para que cree
   los Price de Stripe en modo live — los de test no sirven en live, son
   productos distintos.

## Verificación final

- Entra a `https://achrono.es` y confirma que carga sin error 500 (si lo da,
  revisa `storage/logs/laravel.log` por FTP o desde el toolkit — con
  `APP_DEBUG=false` el navegador no muestra el detalle).
- Prueba `/registro`, date de alta con una empresa de prueba, suscríbete (o
  cancela antes de que cobre) y confirma que el kiosco y el panel funcionan.
- Borra la empresa de prueba desde `/super-admin/empresas` cuando termines.
