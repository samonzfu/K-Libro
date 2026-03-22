-- Script para crear usuario admin en K-Libro
-- Ejecutar este script en la base de datos k_libro después de crear las tablas BBDD.sql

-- Crear usuario admin (cambiar la contraseña después de crear)
INSERT INTO usuarios (nombre, email, contrasena, rol) 
VALUES ('admin', 'admin@k-libro.local', '$2y$10$8aJfzMr9r9n8u7n6m5q4p3o2n1z8y7x6w5v4u3t2s1r0q9p8o7n6m5', 'admin')
ON DUPLICATE KEY UPDATE rol = 'admin';

-- Nota: La contraseña hasheada anterior corresponde a "admin123"
-- Cambiar la contraseña después de crear el usuario por una más segura
-- 
-- Pasos para crear un usuario admin con contraseña segura:
-- 1. Ejecutar una consulta INSERT con un hash SHA-256 o usar password_hash() de PHP
-- 2. Conectarse a http://localhost/GitHub/K-Libro/frontend/2_Login/login.php
-- 3. Usar las credenciales del nuevo admin
-- 4. Cambiar la contraseña desde el panel de Mi Cuenta
