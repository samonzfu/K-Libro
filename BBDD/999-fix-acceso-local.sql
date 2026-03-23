-- Fix rápido para entorno Linux de clase (MySQL/MariaDB)
-- Ejecutar como usuario administrador de MySQL (por ejemplo: sudo mysql)

CREATE DATABASE IF NOT EXISTS k_libro CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

CREATE USER IF NOT EXISTS 'k_libro'@'localhost' IDENTIFIED BY 'KLibro_2026$Clase!';
ALTER USER 'k_libro'@'localhost' IDENTIFIED BY 'KLibro_2026$Clase!';

GRANT ALL PRIVILEGES ON k_libro.* TO 'k_libro'@'localhost';
FLUSH PRIVILEGES;
