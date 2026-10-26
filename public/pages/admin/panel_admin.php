<?php
session_start();
require_once __DIR__ . '/../../../backend/helpers/flash.php';

// Verificar que el usuario esté registrado
if (empty($_SESSION['user_id'])) {
    header('Location: ../login/login.php');
    exit;
}

// Verificar que el usuario sea admin
if (!in_array($_SESSION['rol'] ?? '', ['admin', 'moderador'], true)) {
    flash_set('error', 'No tienes permisos para acceder a esta página.', 'You do not have permission to access this page.');
    header('Location: ../inicio/inicio.php');
    exit;
}

// Generar token CSRF si no existe
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf = $_SESSION['csrf_token'];

// Conexión a la base de datos
require_once __DIR__ . '/../../../backend/config/db.php';

// ── Estadísticas generales ──────────────────────────────────────────────────

$stmtTotalUsuarios = $pdo->query("SELECT COUNT(*) FROM usuarios WHERE rol = 'user'");
$totalUsuarios = (int) $stmtTotalUsuarios->fetchColumn();

// NUEVO: Contador de moderadores
$stmtTotalModeradores = $pdo->query("SELECT COUNT(*) FROM usuarios WHERE rol = 'moderador'");
$totalModeradores = (int) $stmtTotalModeradores->fetchColumn();

$stmtTotalAdmins = $pdo->query("SELECT COUNT(*) FROM usuarios WHERE rol = 'admin'");
$totalAdmins = (int) $stmtTotalAdmins->fetchColumn();

$stmtTotalLibros = $pdo->query("SELECT COUNT(*) FROM biblioteca");
$totalLibros = (int) $stmtTotalLibros->fetchColumn();

$stmtLibrosLeidos = $pdo->query("SELECT COUNT(*) FROM biblioteca WHERE estado = 'leido'");
$totalLeidos = (int) $stmtLibrosLeidos->fetchColumn();

// ── Lista de usuarios con datos de actividad ────────────────────────────────

$stmtUsuarios = $pdo->prepare("
    SELECT
        u.id,
        u.nombre,
        u.email,
        u.rol,
        DATE_FORMAT(u.fecha_registro, '%d/%m/%Y') AS fecha_registro,
        COUNT(CASE WHEN b.estado = 'leido'     THEN 1 END) AS libros_leidos,
        COUNT(CASE WHEN b.estado = 'leyendo'   THEN 1 END) AS libros_leyendo,
        COUNT(CASE WHEN b.estado = 'pendiente' THEN 1 END) AS libros_pendientes,
        COUNT(b.id)                                         AS libros_totales
    FROM usuarios u
    LEFT JOIN biblioteca b ON u.id = b.usuario_id
    GROUP BY u.id, u.nombre, u.email, u.rol, u.fecha_registro
    ORDER BY FIELD(u.rol, 'admin', 'moderador', 'user'), libros_leidos DESC
"); // Se ha mejorado el ORDER BY para que ordene jerárquicamente: Admin -> Moderador -> User
$stmtUsuarios->execute();
$usuarios = $stmtUsuarios->fetchAll(PDO::FETCH_ASSOC);

// ── Mensajes de feedback ────────────────────────────────────────────────────
$mensajes = [
    'usuario_eliminado'  => ['tipo' => 'ok',    'texto' => 'Usuario eliminado correctamente.'],
    'rol_actualizado'    => ['tipo' => 'ok',    'texto' => 'Rol actualizado correctamente.'],
    'csrf'               => ['tipo' => 'error', 'texto' => 'Acción no válida (token de seguridad incorrecto).'],
    'autoeliminar'       => ['tipo' => 'error', 'texto' => 'No puedes eliminarte a ti mismo.'],
    'es_admin'           => ['tipo' => 'error', 'texto' => 'No puedes eliminar a un administrador.'],
    'no_existe'          => ['tipo' => 'error', 'texto' => 'El usuario no existe.'],
    'cambiar_propio_rol' => ['tipo' => 'error', 'texto' => 'No puedes cambiar tu propio rol.'],
    'sin_permiso'        => ['tipo' => 'error', 'texto' => 'No tienes permiso para realizar esa acción.'],
    'datos_invalidos'    => ['tipo' => 'error', 'texto' => 'Datos no válidos.'],
];
$feedbackKey = $_GET['ok'] ?? $_GET['error'] ?? null;
$feedback    = $feedbackKey ? ($mensajes[$feedbackKey] ?? null) : null;
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel Admin | K-Libro</title>
    <link rel="icon" type="image/png" href="../../assets/img/icono.png">
    <link rel="stylesheet" href="../../css/theme.css?v=<?= @filemtime(__DIR__ . '/../../css/theme.css') ?: time() ?>">
    <link rel="stylesheet" href="../../css/ui.css?v=<?= @filemtime(__DIR__ . '/../../css/ui.css') ?: time() ?>">
    <script src="../../js/theme.js?v=<?= @filemtime(__DIR__ . '/../../js/theme.js') ?: time() ?>"></script>
    <script src="../../js/notify.js?v=<?= @filemtime(__DIR__ . '/../../js/notify.js') ?: time() ?>"></script>
    <link rel="stylesheet" href="../../css/panel_admin.css?v=<?= @filemtime(__DIR__ . '/../../css/panel_admin.css') ?: time() ?>">
</head>
<body>

<header>
    <h1>Panel de Administrador</h1>
    <nav>
        <a href="../inicio/inicio.php">← Inicio</a>
        <a href="../../logout.php">Cerrar sesión</a>
        <button id="btn-theme" class="btn-theme" type="button">☀️ Claro</button>
    </nav>
</header>

<main>

    <?php if ($feedback): ?>
    <div class="alerta alerta-<?php echo $feedback['tipo']; ?>">
        <?php echo htmlspecialchars($feedback['texto']); ?>
    </div>
    <?php endif; ?>

    <section class="resumen">
        <h2>Resumen General</h2>
        <div class="estadisticas">
            <div class="estadistica-card">
                <h3>Usuarios registrados</h3>
                <p class="numero"><?php echo $totalUsuarios; ?></p>
            </div>
            <div class="estadistica-card">
                <h3>Moderadores</h3>
                <p class="numero"><?php echo $totalModeradores; ?></p>
            </div>
            <div class="estadistica-card">
                <h3>Administradores</h3>
                <p class="numero"><?php echo $totalAdmins; ?></p>
            </div>
            <div class="estadistica-card">
                <h3>Libros en bibliotecas</h3>
                <p class="numero"><?php echo $totalLibros; ?></p>
            </div>
            <div class="estadistica-card">
                <h3>Libros leídos (total)</h3>
                <p class="numero"><?php echo $totalLeidos; ?></p>
            </div>
        </div>
    </section>

    <section class="usuarios-listado">
        <h2>Usuarios Registrados</h2>
        <table class="tabla-usuarios">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Nombre</th>
                    <th>Email</th>
                    <th>Rol</th>
                    <th>Registro</th>
                    <th>✓ Leídos</th>
                    <th>↻ Leyendo</th>
                    <th>⏳ Pendientes</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($usuarios)): ?>
                    <?php foreach ($usuarios as $u): ?>
                        <?php $esSelf = ((int)$u['id'] === (int)$_SESSION['user_id']); ?>
                        <tr class="<?php echo $esSelf ? 'fila-self' : ''; ?>">
                            <td><?php echo (int)$u['id']; ?></td>
                            <td>
                                <?php echo htmlspecialchars($u['nombre']); ?>
                                <?php if ($esSelf): ?>
                                    <span class="badge-yo">Tú</span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo htmlspecialchars($u['email']); ?></td>
                            <td>
                                <span class="rol-badge <?php echo $u['rol']; ?>">
                                    <?php 
                                        if ($u['rol'] === 'admin') {
                                            echo '♛ Admin';
                                        } elseif ($u['rol'] === 'moderador') {
                                            echo '♘ Mod';
                                        } else {
                                            echo 'Usuario';
                                        }
                                    ?>
                                </span>
                            </td>
                            <td><?php echo htmlspecialchars($u['fecha_registro']); ?></td>
                            <td class="libros-leidos"><?php echo (int)$u['libros_leidos']; ?></td>
                            <td><?php echo (int)$u['libros_leyendo']; ?></td>
                            <td><?php echo (int)$u['libros_pendientes']; ?></td>
                            <td class="acciones">
                                <?php if (!$esSelf): ?>

                                    <?php if ($_SESSION['rol'] === 'admin'): ?>
                                        <form method="POST" action="../../../backend/actions/cambiar_rol.php" class="form-cambiar-rol"
                                              data-confirm-es="¿Cambiar el rol de «<?php echo htmlspecialchars($u['nombre'], ENT_QUOTES, 'UTF-8'); ?>» a «{rol}»?"
                                              data-confirm-en="Change the role of “<?php echo htmlspecialchars($u['nombre'], ENT_QUOTES, 'UTF-8'); ?>” to “{rol}”?"
                                              data-confirm-ok-es="Cambiar rol" data-confirm-ok-en="Change role">
                                            <input type="hidden" name="csrf_token" value="<?php echo $csrf; ?>">
                                            <input type="hidden" name="usuario_id" value="<?php echo (int)$u['id']; ?>">
                                            <select name="nuevo_rol" class="select-rol">
                                                <option value="user" <?php echo $u['rol'] === 'user' ? 'selected' : ''; ?>>Usuario</option>
                                                <option value="moderador" <?php echo $u['rol'] === 'moderador' ? 'selected' : ''; ?>>Moderador</option>
                                                <option value="admin" <?php echo $u['rol'] === 'admin' ? 'selected' : ''; ?>>Admin</option>
                                            </select>
                                            <button type="submit" class="btn btn-rol-actualizar">Actualizar</button>
                                        </form>
                                        <?php if ($u['rol'] !== 'admin'): ?>
                                        <form method="POST"
                                              action="../../../backend/actions/eliminar_usuario.php"
                                              data-confirm-es="¿Eliminar a «<?php echo htmlspecialchars($u['nombre'], ENT_QUOTES, 'UTF-8'); ?>»? Esta acción no se puede deshacer.<?php echo (int)$u['libros_totales'] > 0 ? ' Este usuario tiene ' . (int)$u['libros_totales'] . ' libro(s) en su biblioteca que también se eliminarán.' : ''; ?>"
                                              data-confirm-en="Delete “<?php echo htmlspecialchars($u['nombre'], ENT_QUOTES, 'UTF-8'); ?>”? This action cannot be undone.<?php echo (int)$u['libros_totales'] > 0 ? ' This user has ' . (int)$u['libros_totales'] . ' book(s) in their library that will also be deleted.' : ''; ?>"
                                              data-confirm-ok-es="Eliminar" data-confirm-ok-en="Delete" data-confirm-danger>
                                            <input type="hidden" name="csrf_token" value="<?php echo $csrf; ?>">
                                            <input type="hidden" name="usuario_id" value="<?php echo (int)$u['id']; ?>">
                                            <button type="submit" class="btn btn-eliminar" title="Eliminar usuario">
                                                🗑 Eliminar
                                            </button>
                                        </form>
                                        <?php endif; ?>
                                    <?php elseif ($_SESSION['rol'] === 'moderador' && $u['rol'] === 'user'): ?>
                                        <form method="POST" action="../../../backend/actions/cambiar_rol.php" class="form-cambiar-rol"
                                              data-confirm-es="¿Promover a «<?php echo htmlspecialchars($u['nombre'], ENT_QUOTES, 'UTF-8'); ?>» a Moderador?"
                                              data-confirm-en="Promote “<?php echo htmlspecialchars($u['nombre'], ENT_QUOTES, 'UTF-8'); ?>” to Moderator?"
                                              data-confirm-ok-es="Promover" data-confirm-ok-en="Promote">
                                            <input type="hidden" name="csrf_token" value="<?php echo $csrf; ?>">
                                            <input type="hidden" name="usuario_id" value="<?php echo (int)$u['id']; ?>">
                                            <input type="hidden" name="nuevo_rol" value="moderador">
                                            <button type="submit" class="btn btn-rol-actualizar">
                                                ♘ Hacer Moderador
                                            </button>
                                        </form>
                                    <?php else: ?>
                                        <span class="sin-acciones">—</span>
                                    <?php endif; ?>

                                <?php else: ?>
                                    <span class="sin-acciones">—</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="9" class="sin-datos">No hay usuarios registrados</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </section>

</main>

<script src="../../js/i18n.js?v=<?= @filemtime(__DIR__ . '/../../js/i18n.js') ?: time() ?>"></script>
    <?= flash_render() ?>
</body>
</html>