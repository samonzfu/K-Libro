-- CREAR BD
CREATE DATABASE k_libro CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE k_libro;


-- 1. TABLA USUARIOS
CREATE TABLE usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(50) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    contrasena VARCHAR(255) NOT NULL,
    rol ENUM('user', 'admin') DEFAULT 'user',
    avatar VARCHAR(255) DEFAULT 'default_avatar.png',
    fecha_registro DATETIME DEFAULT CURRENT_TIMESTAMP
);


-- 2. TABLA LIBROS (Adaptada a Open Library)
-- SOLO los metadatos básicos.
CREATE TABLE libros (
    id_openlibrary VARCHAR(100) PRIMARY KEY, 
    titulo VARCHAR(255) NOT NULL,
    autores VARCHAR(255),
    portada VARCHAR(255), 
    descripcion TEXT,
    fecha_publicacion VARCHAR(50)
);


-- 3. TABLA BIBLIOTECA
-- Vincula usuarios con libros y define el estado (Leído, Pendiente...)
CREATE TABLE biblioteca (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NOT NULL,
    
    -- La FK debe coincidir en tipo y nombre con la tabla libros
    libro_id_openlibrary VARCHAR(100) NOT NULL, 
    
    estado ENUM('pendiente', 'leyendo', 'leido') NOT NULL,
    calificacion TINYINT CHECK (calificacion BETWEEN 1 AND 5),
    review TEXT NULL,
    
    -- Fecha automática al actualizar (clave para los retos mensuales)
    fecha_accion DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    FOREIGN KEY (libro_id_openlibrary) REFERENCES libros(id_openlibrary)
);


-- 4. TABLA LOGROS
-- Catálogo de medallas disponibles.
CREATE TABLE logros (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(50) NOT NULL,
    descripcion VARCHAR(255),
    icono VARCHAR(255) NOT NULL,
    criterio INT DEFAULT 0
);


-- 5. TABLA USUARIO_LOGROS
-- Registro de qué usuario tiene qué medalla.
CREATE TABLE usuario_logros (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NOT NULL,
    logro_id INT NOT NULL,
    fecha_ganado DATETIME DEFAULT CURRENT_TIMESTAMP,
    
    UNIQUE(usuario_id, logro_id),
    
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    FOREIGN KEY (logro_id) REFERENCES logros(id) ON DELETE CASCADE
);


-- 6. TABLA RETOS MENSUALES
-- Guarda la meta que el usuario se pone cada mes.
CREATE TABLE retos_mensuales (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NOT NULL,
    mes TINYINT NOT NULL CHECK (mes BETWEEN 1 AND 12),
    anio SMALLINT NOT NULL,
    meta_libros INT NOT NULL DEFAULT 1,
    conseguido BOOLEAN DEFAULT FALSE,
    
    UNIQUE(usuario_id, mes, anio),
    
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE
);


-- 7. SEEDERS (Datos de prueba)
-- Para no empezar con la base de datos vacía.
INSERT INTO logros (nombre, descripcion, icono, criterio) VALUES 
('Lector Iniciado', 'Has leído tu primer libro en K-Libro', 'novato.png', 1),
('Ratón de Biblioteca', 'Has leído 5 libros', 'intermedio.png', 5),
('Devorador de Mundos', 'Has leído 20 libros', 'experto.png', 20),
('Campeón Mensual', '¡Has completado tu reto de lectura del mes!', 'trofeo_mensual.png', 0);

-- Usuario Admin de prueba
-- Contraseña '1234' hasheada para que puedas entrar directo a probar
-- El hash es: $2y$10$buM/.k/Nl5u8.gG8E.j/..wN.M/n.M/n.M/n.M/n.M/n.M/n.M/n
-- (Nota: Para este ejemplo, asegúrate de crear tu propio hash en PHP o usar un registro nuevo)
INSERT INTO usuarios (nombre, email, contrasena, rol) VALUES 
('Admin K-Libro', 'admin@klibro.com', '$2y$10$eE.Y.Z.P.H.P.H.P.H.P.H.P.H.P.H.P.H.P.H.P.H.P.H.P.H.P.H.', 'admin');