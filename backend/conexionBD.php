<?php
/**
 * ==================== CONEXIÓN A BASE DE DATOS ====================
 * 
 * Este archivo ABRE LA CONEXIÓN a MySQL usando PDO (PHP Data Objects)
 * 
 * QUÉ HACE:
 * 1. Intenta conectar con variables de ENTORNO (si están configuradas)
 * 2. Si no existen, usa las credenciales por DEFECTO del proyecto
 * 3. Crea un objeto PDO que se guarda en la variable $pdo
 * 4. Otros archivos pueden usar $pdo para hacer queries a la BBDD
 * 
 * CREDENCIALES POR DEFECTO:
 * - Usuario: k_libro
 * - Contraseña: KLibro_2026$Clase!
 * - Base de datos: k_libro
 * - Host: localhost
 */

try {
    // Obtener el HOST donde está la base de datos (si no existe, usar 'localhost')
    $dbHost = getenv('DB_HOST') ?: 'localhost';
    // Obtener el NOMBRE de la base de datos (si no existe, usar 'k_libro')
    $dbName = getenv('DB_NAME') ?: 'k_libro';

    // Array de CREDENCIALES para intentar conectar
    // Se intentarán en orden, y la primera que funcione será usada
    // Opción 1: Variables de entorno (si existen)
    // Opción 2: Credenciales por defecto
    $credenciales = [];

    // OPCIÓN 1: Intentar obtener credenciales del ENTORNO
    $envUser = getenv('DB_USER');
    if ($envUser !== false && $envUser !== '') {
        $credenciales[] = [
            'user' => $envUser,
            'pass' => getenv('DB_PASS') !== false ? getenv('DB_PASS') : ''
        ];
    }

    // OPCIÓN 2: Credenciales POR DEFECTO del proyecto
    $credenciales[] = ['user' => 'k_libro', 'pass' => 'KLibro_2026$Clase!'];

    // ========== INTENTAR CONECTAR ==========
    $ultimoError = null;
    // Recorrer cada conjunto de credenciales
    foreach ($credenciales as $cred) {
        try {
            // Crear conexión PDO
            // Formato: mysql:host=servidor;dbname=base_de_datos;charset=utf8mb4
            // Las variables ? se reemplazan con usuario y contraseña
            $pdo = new PDO(
                "mysql:host={$dbHost};dbname={$dbName};charset=utf8mb4",
                $cred['user'],
                $cred['pass']
            );
            // Si la conexión fue exitosa, salir del bucle for
            break;
        } catch (PDOException $e) {
            // Si falla la conexión, guardar el error e intentar siguiente credencial
            $ultimoError = $e;
        }
    }

    // ========== VERIFICAR SI CONECTAMOS ==========
    if (!isset($pdo)) {
        // Si NO conectamos con NINGUNA credencial, lanzar error
        $msg = "No se pudo conectar a MySQL con el usuario de la app. "
             . "Importa BBDD/BBDD.sql o define DB_HOST, DB_NAME, DB_USER y DB_PASS en el entorno.";

        // Añadir detalles técnicos del último error
        if ($ultimoError instanceof PDOException) {
            $msg .= " Detalle técnico: " . $ultimoError->getMessage();
        }

        throw new PDOException($msg);
    }

    // Configurar PDO para LANZAR EXCEPCIONES cuando hay errores en SQL
    // Esto nos ayuda a encontrar errores en las queries rápidamente
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
} catch (PDOException $e) {
    // Si hay ERROR de conexión, mostrar mensaje y parar el programa
    die("❌ ERROR DE CONEXIÓN A BASE DE DATOS: " . $e->getMessage());
}

/**
 * VARIABLE GLOBAL: $pdo
 * 
 * Ahora la variable $pdo está disponible para todos los archivos que incluyan
 * este archivo con require_once.
 * 
 * EJEMPLOS DE CÓMO USARLA:
 * 
 * 1. INSERT (Insertar datos):
 *    $stmt = $pdo->prepare("INSERT INTO usuarios (nombre, email) VALUES (?, ?)");
 *    $stmt->execute([$nombre, $email]);
 * 
 * 2. SELECT (Obtener datos):
 *    $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE id = ?");
 *    $stmt->execute([$usuarioId]);
 *    $usuario = $stmt->fetch(PDO::FETCH_ASSOC);
 * 
 * 3. UPDATE (Actualizar datos):
 *    $stmt = $pdo->prepare("UPDATE usuarios SET nombre = ? WHERE id = ?");
 *    $stmt->execute([$nuevoNombre, $usuarioId]);
 * 
 * 4. DELETE (Eliminar datos):
 *    $stmt = $pdo->prepare("DELETE FROM usuarios WHERE id = ?");
 *    $stmt->execute([$usuarioId]);
 */"
?>