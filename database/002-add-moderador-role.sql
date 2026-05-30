-- Migración: añade el rol 'moderador' al ENUM de usuarios
-- Ejecutar solo si ya tienes la BBDD creada con la versión anterior

ALTER TABLE usuarios
    MODIFY COLUMN rol ENUM('user', 'moderador', 'admin') DEFAULT 'user';
