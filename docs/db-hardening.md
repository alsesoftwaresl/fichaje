# Endurecimiento de la base de datos (registro de jornada append-only)

El RD-ley 8/2019 exige que el registro de jornada sea **fiable y no manipulable**.
La app ya lo garantiza a nivel de código (el modelo `Fichaje` lanza una excepción
si algo intenta editarlo o borrarlo), pero como segunda capa de defensa el
usuario de MySQL que usa la aplicación en producción **no tiene permisos
UPDATE ni DELETE** sobre `fichajes` ni `fichaje_correcciones`, ni siquiera
saltándose la aplicación (phpMyAdmin, un script suelto, acceso directo a la BD).

## Cómo está montado

Dos conexiones en `config/database.php` / `.env`:

- `mysql` (por defecto, la que usa la app en runtime): usuario `fichaje_app`,
  con privilegios normales en la mayoría de tablas, pero solo `SELECT, INSERT`
  en `fichajes` y `fichaje_correcciones`.
- `mysql_migrations`: usuario con privilegios DDL (por defecto `root` en
  local), usada **solo** para `php artisan migrate --database=mysql_migrations`.

## Pasos para montarlo en un entorno nuevo

1. Ejecutar las migraciones con el usuario privilegiado:
   ```
   php artisan migrate --database=mysql_migrations
   ```
2. Crear el usuario de runtime y conceder sus privilegios normales:
   ```
   mysql -u root -p < database/sql/create_app_user.sql
   ```
   (Edita antes la contraseña dentro del archivo.)
3. Aplicar la restricción sobre las tablas append-only:
   ```
   mysql -u root -p < database/sql/harden_fichajes.sql
   ```
4. Configurar `.env` con `DB_USERNAME`/`DB_PASSWORD` del usuario `fichaje_app`
   recién creado, y `DB_MIGRATIONS_USERNAME`/`DB_MIGRATIONS_PASSWORD` con el
   usuario privilegiado.

Este proceso es **manual y deliberado**: no está automatizado dentro de
`php artisan migrate` porque mezclar gestión de privilegios de otro usuario
en el pipeline normal de migraciones sería frágil. Si en el futuro se añaden
más tablas append-only, hay que añadir sus GRANT correspondientes a
`harden_fichajes.sql` a mano.

## Verificar que funciona

```
mysql -u fichaje_app -p fichaje_app -e "UPDATE fichajes SET tipo='salida' WHERE id=1;"
```

Debe devolver `ERROR 1142 (42000): UPDATE command denied...`. Lo mismo con
`DELETE FROM fichajes WHERE id=1;`.
