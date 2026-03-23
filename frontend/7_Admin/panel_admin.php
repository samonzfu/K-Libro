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

// Conexión a la base de datos
require_once __DIR__ . '/../../backend/conexionBD.php';

// Obtener total de usuarios
$stmtTotalUsuarios = $pdo->prepare("SELECT COUNT(*) as total FROM usuarios");
$stmtTotalUsuarios->execute();
$totalUsuarios = $stmtTotalUsuarios->fetch(PDO::FETCH_ASSOC)['total'];

// Obtener usuarios con cantidad de libros leídos
$stmtUsuarios = $pdo->prepare("
    SELECT 
        u.id,
        u.nombre,
        u.email,
        u.rol,
        COUNT(CASE WHEN b.estado = 'leido' THEN 1 END) as libros_leidos,
        COUNT(b.id) as libros_totales
    FROM usuarios u
    LEFT JOIN biblioteca b ON u.id = b.usuario_id
    GROUP BY u.id, u.nombre, u.email, u.rol
    ORDER BY libros_leidos DESC
");
$stmtUsuarios->execute();
$usuarios = $stmtUsuarios->fetchAll(PDO::FETCH_ASSOC);
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
            <a href="../logout.php">Cerrar sesión</a>
        </nav>
    </header>

    <main>
        <section class="resumen">
            <h2>Resumen General</h2>
            <div class="estadisticas">
                <div class="estadistica-card">
                    <h3>Total de Usuarios</h3>
                    <p class="numero"><?php echo $totalUsuarios; ?></p>
                </div>
            </div>
        </section>

        <section class="usuarios-listado">
            <h2>Usuarios Registrados y Libros Leídos</h2>
            <table class="tabla-usuarios">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nombre</th>
                        <th>Email</th>
                        <th>Rol</th>
                        <th>Libros Leídos</th>
                        <th>Total Libros en Biblioteca</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($usuarios)): ?>
                        <?php foreach ($usuarios as $usuario): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($usuario['id']); ?></td>
                                <td><?php echo htmlspecialchars($usuario['nombre']); ?></td>
                                <td><?php echo htmlspecialchars($usuario['email']); ?></td>
                                <td>
                                    <span class="rol-badge <?php echo $usuario['rol']; ?>">
                                        <?php echo htmlspecialchars($usuario['rol']); ?>
                                    </span>
                                </td>
                                <td class="libros-leidos"><?php echo $usuario['libros_leidos']; ?></td>
                                <td><?php echo $usuario['libros_totales']; ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" class="sin-datos">No hay usuarios registrados</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </section>
    </main>

    <script src="../js/i18n.js"></script>
</body>
</html>
