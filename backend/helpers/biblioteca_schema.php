<?php

/**
 * ==================== ESQUEMA DE LA TABLA BIBLIOTECA ====================
 * 
 * Esta función VERIFICA Y ACTUALIZA la estructura de la tabla 'biblioteca'
 * 
 * QUÉ HACE:
 * 1. Verifica si existe la columna 'fecha_lectura' en la tabla
 * 2. Si NO EXISTE, la AÑADE automáticamente (ALTER TABLE)
 * 3. Solo se ejecuta UNA SOLA VEZ gracias a la variable estática
 * 
 * POR QUÉ EXISTE:
 * Para evitar errores si alguien usa una base de datos antigua sin la columna fecha_lectura.
 * Es una medida de COMPATIBILIDAD Y ACTUALIZACIÓN automática.
 * 
 * NOTA: La variable $verificada es ESTÁTICA, significa que solo se crea una vez
 * por toda la ejecución del programa (no se reinicia en cada llamada).
 */
function asegurarColumnaFechaLectura(PDO $pdo): void
{
    // Variable ESTÁTICA: solo se crea la primera vez que se ejecuta la función
    // En próximas llamadas, ya existe y evita repetir la verificación
    static $verificada = false;

    // Si ya lo verificamos antes, SALIR (no hacer nada más)
    if ($verificada) {
        return;
    }

    // VERIFICAR SI LA COLUMNA EXISTE
    // Ejecutar query SQL: SHOW COLUMNS FROM biblioteca WHERE name = 'fecha_lectura'
    $stmt = $pdo->query("SHOW COLUMNS FROM biblioteca LIKE 'fecha_lectura'");
    // Si existe, fetch() devolverá los datos de la columna
    // Si no existe, fetch() devolverá null/false
    $existe = $stmt && $stmt->fetch(PDO::FETCH_ASSOC);

    // SI NO EXISTE LA COLUMNA, AÑADIRLA
    if (!$existe) {
        // Ejecutar: ALTER TABLE biblioteca ADD COLUMN fecha_lectura DATE NULL AFTER estado
        // Esto añade una columna nueva de tipo DATE (fecha) después de la columna 'estado'
        // NULL significa que puede estar vacía (no es obligatoria)
        $pdo->exec('ALTER TABLE biblioteca ADD COLUMN fecha_lectura DATE NULL AFTER estado');
    }

    // Marcar como verificada para no hacer esto de nuevo
    $verificada = true;
}

/**
 * CÓMO SE USA:
 * 
 * En backend/procesar/guardar_libro.php:
 * 
 * require_once '../helpers/biblioteca_schema.php';
 * asegurarColumnaFechaLectura($pdo);
 * 
 * // Ahora podemos acceder a $pdo y saber que la columna existe
 */"

?>