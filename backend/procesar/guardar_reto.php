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
        'mensaje' => 'Introduce un numero entero valido para tu reto mensual.'
    ];
    header('Location: /GitHub/K-Libro/frontend/5_Mi_cuenta/mi_cuenta.php');
    exit;
}

$metaLibros = (int) $metaLibrosRaw;

if ($metaLibros < 1 || $metaLibros > 50) {
    $_SESSION['reto_flash'] = [
        'tipo' => 'error',
        'mensaje' => 'La meta mensual debe estar entre 1 y 50 libros.'
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
        'mensaje' => 'Reto mensual guardado correctamente.'
    ];
} catch (PDOException $e) {
    $errorDuplicado = isset($e->errorInfo[1]) && (int) $e->errorInfo[1] === 1062;

    $_SESSION['reto_flash'] = [
        'tipo' => 'error',
        'mensaje' => $errorDuplicado
            ? 'Ya tienes un reto fijado para este mes.'
            : 'No se pudo guardar el reto mensual. Intentalo de nuevo.'
    ];
}

header('Location: /GitHub/K-Libro/frontend/5_Mi_cuenta/mi_cuenta.php');
exit;
?>