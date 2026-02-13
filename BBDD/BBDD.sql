
-- CREAR BBDD
CREATE DATABASE k_libro CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE k_libro;


-- 1. TABLA USUARIOS
CREATE TABLE usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(50) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL, -- Recuerda usar password_hash() en PHP
    rol ENUM('user', 'admin') DEFAULT 'user',
    avatar VARCHAR(255) DEFAULT 'default_avatar.png',
    fecha_registro DATETIME DEFAULT CURRENT_TIMESTAMP
);


-- 2. TABLA LIBROS (Caché Local)
-- SOLO los metadatos básicos.
CREATE TABLE libros (
    id_google VARCHAR(50) PRIMARY KEY, -- ID alfanumérico de Google (ej: "zyTCAlS7Qp4C")
    titulo VARCHAR(255) NOT NULL,
    autores VARCHAR(255),
    portada VARCHAR(255), -- URL de la imagen
    descripcion TEXT,
    fecha_publicacion VARCHAR(20)
);


-- 3. TABLA BIBLIOTECA
-- Vincula usuarios con libros y define el estado (Leído, Pendiente...)
CREATE TABLE biblioteca (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NOT NULL,
    libro_id_google VARCHAR(50) NOT NULL,
    estado ENUM('pendiente', 'leyendo', 'leido') NOT NULL,
    calificacion TINYINT CHECK (calificacion BETWEEN 1 AND 5), -- Estrellas (1-5)
    review TEXT NULL, -- Opinión personal opcional
    
    -- IMPORTANTE: fecha_accion se actualiza sola al modificar el registro.
    -- Esto es vital para saber CUÁNDO se terminó de leer el libro (para el reto mensual).
    fecha_accion DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    FOREIGN KEY (libro_id_google) REFERENCES libros(id_google)
);


-- 4. TABLA LOGROS (Gamificación)
-- Catálogo de medallas disponibles.
CREATE TABLE logros (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(50) NOT NULL,
    descripcion VARCHAR(255),
    icono VARCHAR(255) NOT NULL, -- Ruta a la imagen (ej: 'img/logros/trofeo.png')
    criterio INT DEFAULT 0 -- Cantidad de libros necesarios (0 = logro especial/manual)
);


-- 5. TABLA USUARIO_LOGROS
-- Registro de qué usuario tiene qué medalla.
CREATE TABLE usuario_logros (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NOT NULL,
    logro_id INT NOT NULL,
    fecha_ganado DATETIME DEFAULT CURRENT_TIMESTAMP,
    
    -- Un usuario no puede ganar el mismo logro dos veces
    UNIQUE(usuario_id, logro_id),
    
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    FOREIGN KEY (logro_id) REFERENCES logros(id) ON DELETE CASCADE
);


-- 6. TABLA RETOS MENSUALES (Nueva Funcionalidad)
-- Guarda la meta que el usuario se pone cada mes.
CREATE TABLE retos_mensuales (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NOT NULL,
    mes TINYINT NOT NULL CHECK (mes BETWEEN 1 AND 12),
    anio SMALLINT NOT NULL,
    meta_libros INT NOT NULL DEFAULT 1, -- Objetivo de libros a leer
    conseguido BOOLEAN DEFAULT FALSE,   -- Para saber si ya tiramos el confeti
    
    -- Restricción: Un usuario solo puede tener UN reto por mes y año.
    UNIQUE(usuario_id, mes, anio),
    
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE
);


-- 7. DATOS DE EJEMPLO (SEEDERS)
-- Para que no empieces con la base de datos vacía.

-- Insertamos Logros Base + El Logro Mensual
INSERT INTO logros (nombre, descripcion, icono, criterio) VALUES 
('Lector Iniciado', 'Has leído tu primer libro en K-Libro', 'novato.png', 1),
('Ratón de Biblioteca', 'Has leído 5 libros', 'intermedio.png', 5),
('Devorador de Mundos', 'Has leído 20 libros', 'experto.png', 20),
('Campeón Mensual', '¡Has completado tu reto de lectura del mes!', 'trofeo_mensual.png', 0); -- Criterio 0 indica especial

-- Insertamos un Usuario Admin de prueba (Password: "1234" hasheado con BCRYPT para pruebas)
-- NOTA: En producción genera el hash con PHP: password_hash('1234', PASSWORD_DEFAULT)
INSERT INTO usuarios (nombre, email, password, rol) VALUES 
('Admin K-Libro', 'admin@klibro.com', '$2y$10$tM/Z.w.k.m.u.n.d.o...HASH_DE_EJEMPLO...', 'admin');