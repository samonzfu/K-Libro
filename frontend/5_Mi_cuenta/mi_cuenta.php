<?php
// 1. PROTECCIÓN DE SESIÓN Y CONEXIÓN
session_start();

// 🛑 APLICADO TU CAMBIO AQUÍ 🛑
if (empty($_SESSION['user_id'])) {
    header('Location: /GitHub/K-Libro/frontend/2_Login/login.php');
    exit;
}

// Ajustamos la ruta para llegar a la carpeta backend según tu estructura
require '../../backend/conexionBD.php'; 

$user_id = $_SESSION['user_id'];

// 2. OBTENER DATOS DEL USUARIO
$stmtUsuario = $pdo->prepare("SELECT nombre, email, avatar, fecha_registro FROM usuarios WHERE id = ?");
$stmtUsuario->execute([$user_id]);
$usuario = $stmtUsuario->fetch();

// 3. OBTENER LOGROS DEL USUARIO (Usando JOIN)
$sqlLogros = "SELECT l.nombre, l.descripcion, l.icono, ul.fecha_ganado 
              FROM logros l 
              JOIN usuario_logros ul ON l.id = ul.logro_id 
              WHERE ul.usuario_id = ? 
              ORDER BY ul.fecha_ganado DESC";
$stmtLogros = $pdo->prepare($sqlLogros);
$stmtLogros->execute([$user_id]);
$mis_logros = $stmtLogros->fetchAll();

// 4. OBTENER RETO DEL MES ACTUAL
$mesActual = date('n');
$anioActual = date('Y');
$stmtReto = $pdo->prepare("SELECT meta_libros, conseguido FROM retos_mensuales WHERE usuario_id = ? AND mes = ? AND anio = ?");
$stmtReto->execute([$user_id, $mesActual, $anioActual]);
$reto = $stmtReto->fetch();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>K-Libro | Mi Cuenta</title>
    <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@400;700&family=Lato:wght@300;400&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/estilo.css">
</head>
<body>

    <div class="container">
        
        <div class="profile-card">
            <!-- CAMBIAR ICONO DEL AVATAAAAAAAAAAR -->
            <img src="../../assets/img/<?= htmlspecialchars($usuario['avatar']) ?>" alt="Avatar de <?= htmlspecialchars($usuario['nombre']) ?>" class="avatar" onerror="this.src='https://api.dicebear.com/7.x/bottts/svg?seed=<?= $usuario['nombre'] ?>'">
            <div class="user-info">
                <h1><?= htmlspecialchars($usuario['nombre']) ?></h1>
                <p>📜 Email: <?= htmlspecialchars($usuario['email']) ?></p>
                <p>⏳ Miembro desde: <?= date('d/m/Y', strtotime($usuario['fecha_registro'])) ?></p>
            </div>
        </div>

        <h2>Reto de Lectura (<?= date('F Y') ?>)</h2>
        <div class="reto-card">
            <?php if ($reto): ?>
                <p>Tu objetivo este mes es leer <strong><?= $reto['meta_libros'] ?></strong> libros.</p>
                <?php if ($reto['conseguido']): ?>
                    <p style="color: #4CAF50; font-weight: bold;">¡Misión Cumplida! Has ganado el favor de los dioses literarios.</p>
                <?php else: ?>
                    <p>Sigue leyendo, aún estás a tiempo de completarlo.</p>
                <?php endif; ?>
            <?php else: ?>
                <p>Aún no has fijado tu destino para este mes.</p>
                <button style="padding: 10px; background: var(--color-dorado); border: none; cursor: pointer;">Fijar Nuevo Reto</button>
            <?php endif; ?>
        </div>

        <h2>Logros</h2>
        <?php if (empty($mis_logros)): ?>
            <div class="empty-state">Aún no tienes ningún logro. Sigue leyendo para desbloquearlas.</div>
        <?php else: ?>
            <div class="logros-grid">
                <?php foreach ($mis_logros as $logro): ?>
                    <div class="logro-card">
                        <div class="logro-icono">🏆</div> 
                        <div class="logro-info">
                            <h3><?= htmlspecialchars($logro['nombre']) ?></h3>
                            <p><?= htmlspecialchars($logro['descripcion']) ?></p>
                            <p style="font-size: 0.7rem; margin-top: 5px; color: var(--color-dorado);">
                                Obtenido el: <?= date('d/m/Y', strtotime($logro['fecha_ganado'])) ?>
                            </p>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

    </div>

</body>
</html>