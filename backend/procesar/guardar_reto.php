<?php

session_start();

if (empty($_SESSION['user_id'])) {
    header('Location: /GitHub/K-Libro/frontend/2_Login/login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /GitHub/K-Libro/frontend/5_Mi_cuenta/mi_cuenta.php');
    exit;
}

require '../conexionBD.php';

$metaLibrosRaw = trim((string) ($_POST['meta_libros'] ?? ''));

if ($metaLibrosRaw === '' || !ctype_digit($metaLibrosRaw)) {
    $_SESSION['reto_flash'] = [
        'tipo' => 'error',
        'clave' => 'reto-error-numero'
    ];
    header('Location: /GitHub/K-Libro/frontend/5_Mi_cuenta/mi_cuenta.php');
    exit;
}

$metaLibros = (int) $metaLibrosRaw;

if ($metaLibros < 1 || $metaLibros > 50) {
    $_SESSION['reto_flash'] = [
        'tipo' => 'error',
        'clave' => 'reto-error-rango'
    ];
    header('Location: /GitHub/K-Libro/frontend/5_Mi_cuenta/mi_cuenta.php');
    exit;
}

$usuarioId = (int) $_SESSION['user_id'];
$mesActual = (int) date('n');
$anioActual = (int) date('Y');

try {
    $stmt = $pdo->prepare(
        'INSERT INTO retos_mensuales (usuario_id, mes, anio, meta_libros)
         VALUES (?, ?, ?, ?)'
    );
    $stmt->execute([$usuarioId, $mesActual, $anioActual, $metaLibros]);

    $_SESSION['reto_flash'] = [
        'tipo' => 'success',
        'clave' => 'reto-success-guardado'
    ];
} catch (PDOException $e) {
    $errorDuplicado = isset($e->errorInfo[1]) && (int) $e->errorInfo[1] === 1062;

    $_SESSION['reto_flash'] = [
        'tipo' => 'error',
        'clave' => $errorDuplicado ? 'reto-error-duplicado' : 'reto-error-guardado'
    ];
}

header('Location: /GitHub/K-Libro/frontend/5_Mi_cuenta/mi_cuenta.php');
exit;
?>