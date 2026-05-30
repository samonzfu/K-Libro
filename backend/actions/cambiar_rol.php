<?php
/**
 * Cambia el rol de un usuario.
 *
 * Permisos:
 *  - Admin: puede asignar cualquier rol ('user', 'moderador', 'admin') a cualquier usuario
 *    que no sea él mismo.
 *  - Moderador: solo puede promover usuarios con rol 'user' a 'moderador'.
 *    No puede tocar a otros moderadores ni a admins.
 */

session_start();

$rolActor = $_SESSION['rol'] ?? '';

if (empty($_SESSION['user_id']) || !in_array($rolActor, ['admin', 'moderador'], true)) {
    header('Location: ../../public/pages/login/login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../../public/pages/admin/panel_admin.php');
    exit;
}

if (empty($_POST['csrf_token']) || $_POST['csrf_token'] !== ($_SESSION['csrf_token'] ?? '')) {
    header('Location: ../../public/pages/admin/panel_admin.php?error=csrf');
    exit;
}

$usuario_id_target = filter_input(INPUT_POST, 'usuario_id', FILTER_VALIDATE_INT);
$nuevo_rol         = $_POST['nuevo_rol'] ?? '';

if (!$usuario_id_target) {
    header('Location: ../../public/pages/admin/panel_admin.php?error=datos_invalidos');
    exit;
}

if ($usuario_id_target === (int) $_SESSION['user_id']) {
    header('Location: ../../public/pages/admin/panel_admin.php?error=cambiar_propio_rol');
    exit;
}

require_once __DIR__ . '/../config/db.php';

// Obtener el rol actual del objetivo
$stmtCheck = $pdo->prepare('SELECT rol FROM usuarios WHERE id = ?');
$stmtCheck->execute([$usuario_id_target]);
$objetivo = $stmtCheck->fetch(PDO::FETCH_ASSOC);

if (!$objetivo) {
    header('Location: ../../public/pages/admin/panel_admin.php?error=datos_invalidos');
    exit;
}

$rolObjetivo = $objetivo['rol'];

if ($rolActor === 'admin') {
    // Admin: puede asignar cualquier rol válido
    if (!in_array($nuevo_rol, ['user', 'moderador', 'admin'], true)) {
        header('Location: ../../public/pages/admin/panel_admin.php?error=datos_invalidos');
        exit;
    }
} elseif ($rolActor === 'moderador') {
    // Moderador: solo puede promover 'user' -> 'moderador', nada más
    if ($nuevo_rol !== 'moderador' || $rolObjetivo !== 'user') {
        header('Location: ../../public/pages/admin/panel_admin.php?error=sin_permiso');
        exit;
    }
}

$stmt = $pdo->prepare('UPDATE usuarios SET rol = ? WHERE id = ?');
$stmt->execute([$nuevo_rol, $usuario_id_target]);

header('Location: ../../public/pages/admin/panel_admin.php?ok=rol_actualizado');
exit;
