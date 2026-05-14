<?php
session_start();

// Verificar que el usuario esté registrado
if (empty($_SESSION['user_id'])) {
    header('Location: ../2_Login/login.php');
    exit;
}

// Verificar que el usuario sea admin
if ($_SESSION['rol'] !== 'admin') {
    echo "<script>alert('No tienes permisos para acceder a esta página.'); window.location.href = '../3_Inicio/inicio.php';</script>";
    exit;
}

// Generar token CSRF si no existe
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf = $_SESSION['csrf_token'];

// Conexión a la base de datos
require_once __DIR__ . '/../../backend/conexionBD.php';

// ── Estadísticas generales ──────────────────────────────────────────────────

$stmtTotalUsuarios = $pdo->query("SELECT COUNT(*) FROM usuarios WHERE rol = 'user'");
$totalUsuarios = (int) $stmtTotalUsuarios->fetchColumn();

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
    ORDER BY u.rol DESC, libros_leidos DESC
");
$stmtUsuarios->execute();
$usuarios = $stmtUsuarios->fetchAll(PDO::FETCH_ASSOC);

// ── Mensajes de feedback ────────────────────────────────────────────────────
$mensajes = [
    'usuario_eliminado'  => ['tipo' => 'ok',    'texto' => 'Usuario eliminado correctamente.'],
    'rol_actualizado'    => ['tipo' => 'ok',    'texto' => 'Rol actualizado correctamente.'],
    'csrf'               => ['tipo' => 'error', 'texto' => 'Acción no válida (token de seguridad incorrecto).'],
    'autoeliminar'       => ['tipo' => 'error', 'texto' => 'No puedes eliminarte a ti mismo.'],
    'es_admin'           => ['tipo' => 'error', 'texto' => 'No puedes eliminar a otro administrador.'],
    'no_existe'          => ['tipo' => 'error', 'texto' => 'El usuario no existe.'],
    'cambiar_propio_rol' => ['tipo' => 'error', 'texto' => 'No puedes cambiar tu propio rol.'],
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
    <link rel="stylesheet" href="css/panel_admin.css">
</head>
<body>

<header>
    <h1>Panel de Administrador</h1>
    <nav>
        <a href="../3_Inicio/inicio.php">← Inicio</a>
        <a href="../logout.php">Cerrar sesión</a>
    </nav>
</header>

<main>

    <?php if ($feedback): ?>
    <div class="alerta alerta-<?php echo $feedback['tipo']; ?>">
        <?php echo htmlspecialchars($feedback['texto']); ?>
    </div>
    <?php endif; ?>

    <!-- ── Resumen ────────────────────────────────────────────── -->
    <section class="resumen">
        <h2>Resumen General</h2>
        <div class="estadisticas">
            <div class="estadistica-card">
                <h3>Usuarios registrados</h3>
                <p class="numero"><?php echo $totalUsuarios; ?></p>
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

    <!-- ── Tabla de usuarios ──────────────────────────────────── -->
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
                                    <?php echo $u['rol'] === 'admin' ? '★ Admin' : 'Usuario'; ?>
                                </span>
                            </td>
                            <td><?php echo htmlspecialchars($u['fecha_registro']); ?></td>
                            <td class="libros-leidos"><?php echo (int)$u['libros_leidos']; ?></td>
                            <td><?php echo (int)$u['libros_leyendo']; ?></td>
                            <td><?php echo (int)$u['libros_pendientes']; ?></td>
                            <td class="acciones">
                                <?php if (!$esSelf): ?>

                                    <!-- Cambiar rol -->
                                    <?php $nuevoRol = ($u['rol'] === 'admin') ? 'user' : 'admin'; ?>
                                    <form method="POST"
                                          action="../../backend/procesar/cambiar_rol.php"
                                          onsubmit="return confirm('¿Cambiar rol de <?php echo htmlspecialchars(addslashes($u['nombre'])); ?> a <?php echo $nuevoRol; ?>?')">
                                        <input type="hidden" name="csrf_token" value="<?php echo $csrf; ?>">
                                        <input type="hidden" name="usuario_id" value="<?php echo (int)$u['id']; ?>">
                                        <input type="hidden" name="nuevo_rol"  value="<?php echo $nuevoRol; ?>">
                                        <button type="submit" class="btn btn-rol"
                                                title="<?php echo $u['rol'] === 'admin' ? 'Quitar admin' : 'Hacer admin'; ?>">
                                            <?php echo $u['rol'] === 'admin' ? '↓ Quitar admin' : '↑ Hacer admin'; ?>
                                        </button>
                                    </form>

                                    <!-- Eliminar (solo usuarios, no admins) -->
                                    <?php if ($u['rol'] !== 'admin'): ?>
                                    <form method="POST"
                                          action="../../backend/procesar/eliminar_usuario.php"
                                          onsubmit="return confirmarEliminar('<?php echo htmlspecialchars(addslashes($u['nombre'])); ?>', <?php echo (int)$u['libros_totales']; ?>)">
                                        <input type="hidden" name="csrf_token" value="<?php echo $csrf; ?>">
                                        <input type="hidden" name="usuario_id" value="<?php echo (int)$u['id']; ?>">
                                        <button type="submit" class="btn btn-eliminar" title="Eliminar usuario">
                                            🗑 Eliminar
                                        </button>
                                    </form>
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

<script>
function confirmarEliminar(nombre, totalLibros) {
    const aviso = totalLibros > 0
        ? '\n\u26A0\uFE0F Este usuario tiene ' + totalLibros + ' libro(s) en su biblioteca que también se eliminarán.'
        : '';
    return confirm('¿Eliminar a "' + nombre + '"? Esta acción no se puede deshacer.' + aviso);
}
</script>

<script src="../js/i18n.js"></script>
</body>
</html>
