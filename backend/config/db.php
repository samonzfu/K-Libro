<?php
/**
 * Conexión a MySQL usando PDO.
 *
 * Las credenciales se leen de variables de entorno (DB_HOST, DB_NAME, DB_USER, DB_PASS).
 * Si existe un archivo .env en la raíz del proyecto, se cargan desde ahí
 * (ver .env.example). No hay credenciales por defecto en el código.
 */

// Carga sencilla del archivo .env (solo define variables que no existan ya en el entorno)
$envFile = __DIR__ . '/../../.env';
if (is_readable($envFile)) {
    foreach (file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $linea) {
        $linea = trim($linea);
        if ($linea === '' || $linea[0] === '#' || strpos($linea, '=') === false) {
            continue;
        }
        [$clave, $valor] = explode('=', $linea, 2);
        $clave = trim($clave);
        $valor = trim($valor, " \t\"'");
        if ($clave !== '' && getenv($clave) === false) {
            putenv("$clave=$valor");
        }
    }
}

// Zona horaria de la aplicación: así "hoy" coincide con el día del usuario (importante para las fechas de lectura)
date_default_timezone_set('Europe/Madrid');

$dbHost = getenv('DB_HOST') ?: 'localhost';
$dbName = getenv('DB_NAME') ?: 'k_libro';
$dbUser = getenv('DB_USER');
$dbPass = getenv('DB_PASS');

if ($dbUser === false || $dbUser === '' || $dbPass === false) {
    error_log('K-Libro: faltan DB_USER o DB_PASS (define las variables de entorno o crea el archivo .env).');
    http_response_code(500);
    die('Error de configuración del servidor.');
}

try {
    $pdo = new PDO(
        "mysql:host={$dbHost};dbname={$dbName};charset=utf8mb4",
        $dbUser,
        $dbPass
    );
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    // El detalle del error va al log del servidor, no al navegador
    error_log('K-Libro: error de conexión a MySQL: ' . $e->getMessage());
    http_response_code(500);
    die('No se pudo conectar con la base de datos.');
}
