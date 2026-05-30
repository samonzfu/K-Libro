<?php
/**
 * Conexión a MySQL usando PDO.
 * Prioriza variables de entorno; si no existen, usa las credenciales por defecto.
 */

try {
    $dbHost = getenv('DB_HOST') ?: 'localhost';
    $dbName = getenv('DB_NAME') ?: 'k_libro';

    $credenciales = [];

    $envUser = getenv('DB_USER');
    if ($envUser !== false && $envUser !== '') {
        $credenciales[] = [
            'user' => $envUser,
            'pass' => getenv('DB_PASS') !== false ? getenv('DB_PASS') : ''
        ];
    }

    $credenciales[] = ['user' => 'k_libro', 'pass' => 'KLibro_2026$Clase!'];

    $ultimoError = null;
    foreach ($credenciales as $cred) {
        try {
            $pdo = new PDO(
                "mysql:host={$dbHost};dbname={$dbName};charset=utf8mb4",
                $cred['user'],
                $cred['pass']
            );
            break;
        } catch (PDOException $e) {
            $ultimoError = $e;
        }
    }

    if (!isset($pdo)) {
        $msg = "No se pudo conectar a MySQL. "
             . "Importa database/BBDD.sql o define DB_HOST, DB_NAME, DB_USER y DB_PASS en el entorno.";
        if ($ultimoError instanceof PDOException) {
            $msg .= " Detalle: " . $ultimoError->getMessage();
        }
        throw new PDOException($msg);
    }

    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

} catch (PDOException $e) {
    die("ERROR DE CONEXIÓN: " . $e->getMessage());
}
