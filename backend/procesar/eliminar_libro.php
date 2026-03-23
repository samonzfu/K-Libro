<?php

session_start();

if (empty($_SESSION['user_id'])) {
    header('Location: ../../frontend/2_Login/login.php');
    exit;
}

if (empty($_POST['libro_id'])) {
    header('Location: ../../frontend/4_Biblioteca/biblioteca.php');
    exit;
}

include '../conexionBD.php';

$libro_id = $_POST['libro_id'];
$usuario_id = $_SESSION['user_id'];

// Verificar que el libro existe en la biblioteca del usuario
$stmt = $pdo->prepare("
    SELECT b.id FROM biblioteca b
    WHERE b.libro_id_openlibrary = ? AND b.usuario_id = ?
");
$stmt->execute([$libro_id, $usuario_id]);

if (!$stmt->fetch()) {
    header('Location: ../../frontend/4_Biblioteca/biblioteca.php');
    exit;
}

// Eliminar el libro de la biblioteca del usuario
$stmt = $pdo->prepare("DELETE FROM biblioteca WHERE libro_id_openlibrary = ? AND usuario_id = ?");
$stmt->execute([$libro_id, $usuario_id]);

header('Location: ../../frontend/4_Biblioteca/biblioteca.php');
exit;

?>
