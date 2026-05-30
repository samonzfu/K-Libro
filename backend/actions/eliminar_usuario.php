<?php

/**
 * ==================== ELIMINAR USUARIO (SOLO ADMIN) ====================
 *
 * Elimina un usuario de la BD junto con todos sus datos relacionados.
 * Las FKs con ON DELETE CASCADE se encargan de borrar en cascada:
 * biblioteca, usuario_logros y retos_mensuales.
 *
 * Seguridad:
 *  - Solo accesible si la sesión activa tiene rol 'admin'.
 *  - No permite que el admin se elimine a sí mismo.
 *  - No permite eliminar a otros admins.
 *  - Token CSRF para evitar peticiones forjadas.
 *  - Verifica que el usuario a eliminar exista y sea 'user'.
 */

session_start();

// 1. Solo admins autenticados
if (empty($_SESSION['user_id']) || $_SESSION['rol'] !== 'admin') {
    header('Location: ../../public/pages/login/login.php');
    exit;
}

// 2. Solo POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../../public/pages/admin/panel_admin.php');
    exit;
}

// 3. Validar token CSRF
if (empty($_POST['csrf_token']) || $_POST['csrf_token'] !== ($_SESSION['csrf_token'] ?? '')) {
    header('Location: ../../public/pages/admin/panel_admin.php?error=csrf');
    exit;
}

// 4. Validar ID recibido
$usuario_id_borrar = filter_input(INPUT_POST, 'usuario_id', FILTER_VALIDATE_INT);
if (!$usuario_id_borrar || $usuario_id_borrar < 1) {
    header('Location: ../../public/pages/admin/panel_admin.php?error=id_invalido');
    exit;
}

// 5. No puede eliminarse a sí mismo
if ($usuario_id_borrar === (int) $_SESSION['user_id']) {
    header('Location: ../../public/pages/admin/panel_admin.php?error=autoeliminar');
    exit;
}

require_once __DIR__ . '/../config/db.php';

// 6. Verificar que el usuario existe y no es admin
$stmt = $pdo->prepare("SELECT id, rol FROM usuarios WHERE id = ?");
$stmt->execute([$usuario_id_borrar]);
$objetivo = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$objetivo) {
    header('Location: ../../public/pages/admin/panel_admin.php?error=no_existe');
    exit;
}

if ($objetivo['rol'] === 'admin') {
    header('Location: ../../public/pages/admin/panel_admin.php?error=es_admin');
    exit;
}

// 7. Eliminar — las FKs ON DELETE CASCADE se ocupan del resto
$stmt = $pdo->prepare("DELETE FROM usuarios WHERE id = ? AND rol IN ('user', 'moderador')");
$stmt->execute([$usuario_id_borrar]);

header('Location: ../../public/pages/admin/panel_admin.php?ok=usuario_eliminado');
exit;
