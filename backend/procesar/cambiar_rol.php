<?php

/**
 * ==================== CAMBIAR ROL DE USUARIO (SOLO ADMIN) ====================
 *
 * Alterna el rol de un usuario entre 'user' y 'admin'.
 *
 * Seguridad:
 *  - Solo accesible para sesiones con rol 'admin'.
 *  - No permite que el admin cambie su propio rol.
 *  - Token CSRF obligatorio.
 */

session_start();

if (empty($_SESSION['user_id']) || $_SESSION['rol'] !== 'admin') {
    header('Location: ../../frontend/2_Login/login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../../frontend/7_Admin/panel_admin.php');
    exit;
}

if (empty($_POST['csrf_token']) || $_POST['csrf_token'] !== ($_SESSION['csrf_token'] ?? '')) {
    header('Location: ../../frontend/7_Admin/panel_admin.php?error=csrf');
    exit;
}

$usuario_id_target = filter_input(INPUT_POST, 'usuario_id', FILTER_VALIDATE_INT);
$nuevo_rol         = $_POST['nuevo_rol'] ?? '';

if (!$usuario_id_target || !in_array($nuevo_rol, ['user', 'admin'], true)) {
    header('Location: ../../frontend/7_Admin/panel_admin.php?error=datos_invalidos');
    exit;
}

if ($usuario_id_target === (int) $_SESSION['user_id']) {
    header('Location: ../../frontend/7_Admin/panel_admin.php?error=cambiar_propio_rol');
    exit;
}

require_once __DIR__ . '/../conexionBD.php';

$stmt = $pdo->prepare("UPDATE usuarios SET rol = ? WHERE id = ?");
$stmt->execute([$nuevo_rol, $usuario_id_target]);

header('Location: ../../frontend/7_Admin/panel_admin.php?ok=rol_actualizado');
exit;
