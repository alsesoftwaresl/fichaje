-- Concede al usuario de runtime de la app SOLO SELECT e INSERT sobre las
-- tablas append-only del registro de jornada (RD-ley 8/2019). MySQL deniega
-- todo por defecto si no hay un GRANT explícito, así que esto es en sí mismo
-- la restricción: nunca se concede UPDATE ni DELETE sobre estas dos tablas,
-- ni siquiera saltándose la aplicación (phpMyAdmin, un script suelto, etc).
--
-- Ejecutar MANUALMENTE, una vez, DESPUÉS de create_app_user.sql. No forma
-- parte de `php artisan migrate` a propósito: es una restricción de
-- seguridad deliberada, no un paso más del despliegue automático.
--
-- La defensa de primera línea es el modelo Eloquent (Fichaje::booted() lanza
-- excepción en updating/deleting); esto es la segunda línea, a nivel de MySQL.

GRANT SELECT, INSERT ON fichaje_app.fichajes TO 'fichaje_app'@'localhost';
GRANT SELECT, INSERT ON fichaje_app.fichaje_correcciones TO 'fichaje_app'@'localhost';

FLUSH PRIVILEGES;
