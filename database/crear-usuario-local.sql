-- Crea el usuario de MySQL que usa K-Libro en local.
--
-- 1) Sustituye CAMBIA_ESTA_CONTRASENA por una contraseña propia
--    (la misma que pondrás en DB_PASS del archivo .env).
-- 2) Ejecuta como administrador de MySQL, por ejemplo:  sudo mysql < database/crear-usuario-local.sql
--    NO subas al repositorio este archivo con la contraseña real.

CREATE USER IF NOT EXISTS 'k_libro'@'localhost' IDENTIFIED BY 'Klibro123$';
ALTER USER 'k_libro'@'localhost' IDENTIFIED BY 'Klibro123$';

GRANT ALL PRIVILEGES ON k_libro.* TO 'k_libro'@'localhost';
FLUSH PRIVILEGES;
