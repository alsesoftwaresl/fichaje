# Arquitectura y cómo hacer crecer Achrono

Guía para quien vaya a tocar el código: cómo está organizado, qué reglas no hay que romper y
por dónde crecer cuando haya más empresas, más empleados o más módulos.

## 1. Capas

| Capa | Dónde | Regla |
|---|---|---|
| Rutas | `routes/web.php`, `routes/auth.php`, `routes/console.php` | Agrupadas por área y con su middleware. Sin lógica. |
| Middleware | `app/Http/Middleware` | Permisos y contexto (empresa, rol, suscripción, módulos). Una responsabilidad cada uno. |
| Controladores | `app/Http/Controllers/{Admin,SuperAdmin,Auth}` | Validan, llaman al modelo o servicio y devuelven vista o redirección. Finos. |
| Modelos | `app/Models` | Relaciones, *casts* y reglas propias del dato (p. ej. `Fichaje` no se edita, `Empresa::tieneAcceso()`). |
| Servicios | `app/Services` | Cálculos que cruzan varios modelos (jornadas, incidencias, estado del equipo, alta de empresa, registro de fichajes). Sin efectos secundarios salvo `AltaEmpresaService` y `RegistroFichajes`. |
| Utilidades | `app/Support` | `Tenant` (empresa actual), `Avisos` (envío de correos sin romper la acción), `ContenidoWeb` (textos compartidos). |
| Notificaciones | `app/Notifications` | Correos. Las de eventos implementan `ShouldQueue`. |
| Comandos | `app/Console/Commands` | Tareas programadas y de mantenimiento. |
| Vistas | `resources/views` | Blade + Tailwind + Alpine. Componentes en `components/`. |

## 2. Reglas que no se rompen

1. **Multiempresa.** Todo modelo con datos de una empresa usa `BelongsToTenant`: filtra solo por
   `empresa_id` y lo rellena al crear. Nunca se acepta `empresa_id` desde un formulario. Fuera de
   una petición (comandos, kiosco) se fija con `Tenant::set()` y se limpia con `Tenant::clear()`.
2. **Registro de jornada inalterable.** `fichajes` y `fichaje_correcciones` solo admiten `INSERT`.
   Un error se arregla con una corrección (hora nueva, motivo y autor), nunca editando; una salida
   que falta se añade como fichaje nuevo con origen `empresa`. Todo fichaje de empleado pasa por
   `RegistroFichajes` (entradas olvidadas, doble toque).
3. **Nada de consultas dentro de bucles.** Se carga lo necesario de una vez (`with()`, `whereIn`,
   rangos de fechas) y se reparte en memoria. `tests/Feature/RendimientoTest.php` vigila el
   cálculo de incidencias; añade una prueba igual para cualquier cálculo pesado nuevo.
4. **Un cambio, un commit, pruebas en verde.** `php artisan test` y `vendor/bin/pint --test`
   tienen que pasar (lo comprueba GitHub Actions en cada push).
5. **Nada de `env()` fuera de `config/`.** Con `php artisan optimize` (configuración en caché),
   `env()` devuelve `null` en el resto del código. Se añade una clave a un fichero de `config/`
   y se lee con `config()`.
6. **La web pública no promete lo que la app no hace**, ni lleva cifras, opiniones o logos inventados.

## 3. Cómo añadir una funcionalidad

1. **Migración** con los índices de las consultas que se van a hacer (normalmente
   `empresa_id` + fecha, o `user_id` + fecha). Si se usa la base de datos endurecida, añadir el
   `GRANT` de la tabla nueva a `database/sql/create_app_user.sql`.
2. **Modelo** con `BelongsToTenant` si son datos de una empresa, `$fillable` y `casts()`.
3. **Controlador** en su área y **rutas** dentro del grupo de middleware que corresponda
   (`tenant`, `role:admin_empresa`, `suscripcion`…).
4. **Vista** reutilizando los componentes (`x-card`, `x-badge`, `x-text-input`…). Si añades
   clases nuevas de Tailwind: `npm run build` y commitear `public/build`.
5. **Pruebas** del caso normal, de permisos (otra empresa, otro rol) y de los límites.
6. **Auditoría**: si la acción es sensible, `AuditLog::registrar()`.

## 4. Cómo añadir un módulo de pago

El módulo de Nóminas es la plantilla a seguir:

- Interruptor global y precio en `tarifas` (editables por el super admin, IVA incluido).
- Bandera por empresa (`empresas.modulo_nominas`) y método `Empresa::tieneModuloNominas()` que
  decide si está disponible (incluido, por licencia, en prueba o contratado).
- Middleware (`modulo.nominas`) que protege las rutas de gestión.
- Precio propio en Stripe creado en `Tarifa::sincronizarConStripe()` y añadido o quitado de la
  suscripción con `addPrice()` / `removePrice()`.
- Campo en `licencias` para decidir si un código lo incluye.
- Los datos que ya pertenecen a los empleados (sus nóminas) **no** se bloquean nunca.

## 5. Por dónde crecer

| Cuando… | Qué hacer |
|---|---|
| La web va lenta en el hosting | `cmd=optimize` (cachés de configuración, rutas y vistas). |
| Hay muchos correos o tardan | `QUEUE_CONNECTION=database`, `php artisan queue:table` + migrar, y un worker (en Plesk: tarea programada con `queue:work --stop-when-empty` cada minuto). Las notificaciones ya están preparadas. |
| Muchas empresas o mucho tráfico | Pasar de hosting compartido a un VPS; `CACHE_STORE` y `SESSION_DRIVER` en Redis; usuario de base de datos restringido (base endurecida). |
| Muchas nóminas en disco | Cambiar el disco de `Nomina::DISCO` a uno S3 compatible (el código ya usa `Storage`). |
| Informes de periodos largos | Pasar la exportación a un *job* en cola que genere el fichero y avise por correo. |
| Varios centros de trabajo | Nueva tabla `centros` con `BelongsToTenant` y `centro_id` en empleados y kioscos; el kiosco ya va por token. |

## 6. Medir antes de optimizar

Para ver cuántas consultas hace una página, activa `DB::enableQueryLog()` en una prueba o en
`tinker` y revisa `DB::getQueryLog()`. Referencias actuales con datos de prueba: portada 2–4
consultas, panel ~25, incidencias del mes 12 (fijo, no depende de los días), fichajes 9.
