<?php

session_start();

if (empty($_SESSION['user_id'])) {
    header('Location: /GitHub/K-Libro/frontend/2_Login/login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /GitHub/K-Libro/frontend/4_Biblioteca/biblioteca.php');
    exit;
}

include '../conexionBD.php';

$idOpenLibrary = trim((string) ($_POST['id_openlibrary'] ?? ''));
if ($idOpenLibrary === '') {
    header('Location: /GitHub/K-Libro/frontend/4_Biblioteca/biblioteca.php');
    exit;
}

$usuarioId = (int) $_SESSION['user_id'];

try {
    $stmt = $pdo->prepare(
        'DELETE FROM biblioteca
         WHERE usuario_id = :usuario_id
           AND libro_id_openlibrary = :libro_id_openlibrary'
    );

    $stmt->execute([
        ':usuario_id' => $usuarioId,
        ':libro_id_openlibrary' => $idOpenLibrary
    ]);
} catch (Throwable $e) {
    header('Location: /GitHub/K-Libro/frontend/4_Biblioteca/biblioteca.php');
    exit;
}

header('Location: /GitHub/K-Libro/frontend/4_Biblioteca/biblioteca.php');
exit;
