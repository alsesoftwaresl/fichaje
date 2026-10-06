-- Crea el usuario de runtime de la aplicación con privilegios CRUD normales
-- sobre las tablas "de plataforma" (Laravel, colas, sesiones...) y de negocio
-- normales. Las tablas append-only (fichajes, fichaje_correcciones) se
-- conceden aparte, con permisos reducidos, en harden_fichajes.sql.
--
-- Importante sobre el modelo de privilegios de MySQL: un GRANT a nivel de
-- base de datos completa (p.ej. `fichaje_app.*`) se hereda en TODAS las
-- tablas y no se puede restringir después con un REVOKE a nivel de tabla
-- individual (MySQL da error "no such grant defined... on table"). Por eso
-- aquí se concede tabla a tabla en vez de con un `fichaje_app.*` genérico.
--
-- Ejecutar UNA VEZ como root/admin, después de que las migraciones hayan
-- creado las tablas. Cambia la contraseña por una propia.

CREATE USER IF NOT EXISTS 'fichaje_app'@'localhost' IDENTIFIED BY 'cambia-esta-contrasena';

GRANT SELECT, INSERT, UPDATE, DELETE ON fichaje_app.empresas TO 'fichaje_app'@'localhost';
GRANT SELECT, INSERT, UPDATE, DELETE ON fichaje_app.users TO 'fichaje_app'@'localhost';
GRANT SELECT, INSERT, UPDATE, DELETE ON fichaje_app.audit_logs TO 'fichaje_app'@'localhost';
GRANT SELECT, INSERT, UPDATE, DELETE ON fichaje_app.legal_aceptaciones TO 'fichaje_app'@'localhost';
GRANT SELECT, INSERT, UPDATE, DELETE ON fichaje_app.password_reset_tokens TO 'fichaje_app'@'localhost';
GRANT SELECT, INSERT, UPDATE, DELETE ON fichaje_app.sessions TO 'fichaje_app'@'localhost';
GRANT SELECT, INSERT, UPDATE, DELETE ON fichaje_app.cache TO 'fichaje_app'@'localhost';
GRANT SELECT, INSERT, UPDATE, DELETE ON fichaje_app.cache_locks TO 'fichaje_app'@'localhost';
GRANT SELECT, INSERT, UPDATE, DELETE ON fichaje_app.jobs TO 'fichaje_app'@'localhost';
GRANT SELECT, INSERT, UPDATE, DELETE ON fichaje_app.job_batches TO 'fichaje_app'@'localhost';
GRANT SELECT, INSERT, UPDATE, DELETE ON fichaje_app.failed_jobs TO 'fichaje_app'@'localhost';
GRANT SELECT, INSERT, UPDATE, DELETE ON fichaje_app.tarifas TO 'fichaje_app'@'localhost';
GRANT SELECT, INSERT, UPDATE, DELETE ON fichaje_app.subscriptions TO 'fichaje_app'@'localhost';
GRANT SELECT, INSERT, UPDATE, DELETE ON fichaje_app.subscription_items TO 'fichaje_app'@'localhost';
GRANT SELECT, INSERT, UPDATE, DELETE ON fichaje_app.ausencias TO 'fichaje_app'@'localhost';
GRANT SELECT, INSERT, UPDATE, DELETE ON fichaje_app.citas TO 'fichaje_app'@'localhost';
GRANT SELECT, INSERT, UPDATE, DELETE ON fichaje_app.nominas TO 'fichaje_app'@'localhost';

-- Nota: no se concede NADA aquí sobre `fichajes` ni `fichaje_correcciones` ni
-- sobre `migrations` (MySQL deniega por defecto lo que no se concede
-- explícitamente). Ver harden_fichajes.sql para las dos primeras.

FLUSH PRIVILEGES;
