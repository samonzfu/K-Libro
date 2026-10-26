-- 0. CREAR BD
DROP DATABASE IF EXISTS k_libro;
CREATE DATABASE k_libro CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE k_libro;


-- 1. TABLA USUARIOS
CREATE TABLE usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(50) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    contrasena VARCHAR(255) NOT NULL,
    avatar VARCHAR(255) DEFAULT 'default.png',
    rol ENUM('user', 'moderador', 'admin') DEFAULT 'user',
    fecha_registro DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;


-- 2. TABLA LIBROS (Adaptada a Open Library)
-- SOLO los metadatos básicos.
CREATE TABLE libros (
    id_openlibrary VARCHAR(100) PRIMARY KEY, 
    titulo VARCHAR(255) NOT NULL,
    autores VARCHAR(255),
    portada VARCHAR(255), 
    descripcion TEXT,
    fecha_publicacion VARCHAR(50)
) ENGINE=InnoDB;


-- 3. TABLA LOGROS
-- Catálogo de medallas disponibles.
CREATE TABLE logros (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(50) NOT NULL,
    descripcion VARCHAR(255),
    icono VARCHAR(255) NOT NULL,
    criterio INT DEFAULT 0,

    UNIQUE KEY uk_logros_nombre (nombre)
) ENGINE=InnoDB;



-- 4. TABLA BIBLIOTECA
-- Vincula usuarios con libros y define el estado (Leído, Pendiente...)
CREATE TABLE biblioteca (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NOT NULL,
    
    -- La FK debe coincidir en tipo y nombre con la tabla libros
    libro_id_openlibrary VARCHAR(100) NOT NULL, 
    
    estado ENUM('pendiente', 'leyendo', 'leido') NOT NULL,
    fecha_lectura DATE NULL,
    calificacion TINYINT NULL,
    review TEXT NULL,
    
    -- Fecha automática al registrar o editar el elemento en biblioteca
    fecha_accion DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    CHECK (calificacion IS NULL OR calificacion BETWEEN 1 AND 5),
    CHECK ((estado <> 'leido' AND fecha_lectura IS NULL) OR (estado = 'leido' AND fecha_lectura IS NOT NULL)),

    INDEX idx_biblioteca_usuario_libro_id (usuario_id, libro_id_openlibrary, id),
    INDEX idx_biblioteca_usuario_estado (usuario_id, estado),
    INDEX idx_biblioteca_usuario_fecha_lectura (usuario_id, fecha_lectura),
    
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    FOREIGN KEY (libro_id_openlibrary) REFERENCES libros(id_openlibrary) ON DELETE CASCADE
) ENGINE=InnoDB;


-- 5. TABLA USUARIO_LOGROS
-- Registro de qué usuario tiene qué medalla.
CREATE TABLE usuario_logros (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NOT NULL,
    logro_id INT NOT NULL,
    fecha_ganado DATETIME DEFAULT CURRENT_TIMESTAMP,
    
    UNIQUE(usuario_id, logro_id),
    INDEX idx_usuario_logros_usuario_fecha (usuario_id, fecha_ganado),
    
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    FOREIGN KEY (logro_id) REFERENCES logros(id) ON DELETE CASCADE
) ENGINE=InnoDB;


-- 6. TABLA RETOS MENSUALES
-- Guarda la meta que el usuario se pone cada mes.
CREATE TABLE retos_mensuales (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NOT NULL,
    mes TINYINT NOT NULL CHECK (mes BETWEEN 1 AND 12),
    anio SMALLINT NOT NULL,
    meta_libros INT NOT NULL DEFAULT 1,
    conseguido BOOLEAN DEFAULT FALSE,

    CHECK (meta_libros BETWEEN 1 AND 50),
    
    UNIQUE(usuario_id, mes, anio),
    INDEX idx_retos_usuario_conseguido (usuario_id, conseguido),
    
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE
) ENGINE=InnoDB;


-- 7. SEEDERS (Datos de prueba)
-- Para no empezar con la base de datos vacía.
INSERT INTO logros (nombre, descripcion, icono, criterio) VALUES 
('Lector Principiante', 'Has leído tu primer libro en K-Libro', 'novato.png', 1),
('Ratón de Biblioteca', 'Has leído 5 libros', 'intermedio.png', 5),
('Bibliófilo', 'Has leído 20 libros', 'experto.png', 20),
('Reto Mensual', 'Has completado tu reto de lectura del mes.', 'trofeo_mensual.png', 0)
ON DUPLICATE KEY UPDATE
    descripcion = VALUES(descripcion),
    icono = VALUES(icono),
    criterio = VALUES(criterio);

-- NOTA: este script ya no crea el usuario de MySQL ni un administrador de prueba
-- (no deben publicarse contraseñas en el repositorio).
--  - Usuario de MySQL: ver database/crear-usuario-local.sql
--  - Primer administrador: regístrate en la aplicación y ejecuta:
--      UPDATE usuarios SET rol = 'admin' WHERE email = 'tu@correo.com';
