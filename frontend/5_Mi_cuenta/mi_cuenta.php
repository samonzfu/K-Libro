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
        
        <nav>
            <a href="../3_Inicio/inicio.php" data-i18n="nav-inicio">Volver al inicio</a> |
            <a href="../4_Biblioteca/biblioteca.php" data-i18n="nav-biblioteca">Ir a mi biblioteca</a> |
            <a href="../6_buscador/buscador.php" data-i18n="nav-buscador">Ir al buscador</a>
            <button id="btn-lang" class="btn-lang">🌐 English</button>
        </nav>

        <div class="profile-card">
            <img src="../../assets/img/<?= htmlspecialchars($usuario['avatar']) ?>" alt="Avatar de <?= htmlspecialchars($usuario['nombre']) ?>" class="avatar" onerror="this.src='https://api.dicebear.com/7.x/bottts/svg?seed=<?= $usuario['nombre'] ?>'">
            <div class="user-info">
                <h1><?= htmlspecialchars($usuario['nombre']) ?></h1>
                <p><span data-i18n="label-email">📜 Email:</span> <?= htmlspecialchars($usuario['email']) ?></p>
                <p><span data-i18n="label-miembro">⏳ Miembro desde:</span> <?= date('d/m/Y', strtotime($usuario['fecha_registro'])) ?></p>
                <a href="../logout.php" class="btn-logout" data-i18n="nav-logout">Cerrar sesión</a>
            </div>
        </div>

        <h2 id="reto-titulo" data-mes="<?= date('n') ?>" data-anio="<?= date('Y') ?>">Reto de Lectura (<?= date('F Y') ?>)</h2>
        <div class="reto-card">
            <?php if ($reto): ?>
                <p><span data-i18n="reto-objetivo">Tu objetivo este mes es leer</span> <strong><?= $reto['meta_libros'] ?></strong> <span data-i18n="reto-libros">libros.</span></p>
                <?php if ($reto['conseguido']): ?>
                    <p style="color: #4CAF50; font-weight: bold;" data-i18n="reto-conseguido">¡Misión Cumplida! Has ganado el favor de los dioses literarios.</p>
                <?php else: ?>
                    <p data-i18n="reto-pendiente">Sigue leyendo, aún estás a tiempo de completarlo.</p>
                <?php endif; ?>
            <?php else: ?>
                <p data-i18n="reto-vacio">Aún no has fijado tu destino para este mes.</p>
                <button style="padding: 10px; background: var(--color-dorado); border: none; cursor: pointer;" data-i18n="btn-nuevo-reto">Fijar Nuevo Reto</button>
            <?php endif; ?>
        </div>

        <h2 data-i18n="section-logros">Logros</h2>
        <?php if (empty($mis_logros)): ?>
            <div class="empty-state" data-i18n="logros-vacio">Aún no tienes ningún logro. Sigue leyendo para desbloquearlas.</div>
        <?php else: ?>
            <div class="logros-grid">
                <?php foreach ($mis_logros as $logro): ?>
                    <div class="logro-card">
                        <div class="logro-icono">🏆</div> 
                        <div class="logro-info">
                            <h3><?= htmlspecialchars($logro['nombre']) ?></h3>
                            <p><?= htmlspecialchars($logro['descripcion']) ?></p>
                            <p style="font-size: 0.7rem; margin-top: 5px; color: var(--color-dorado);">
                                <span data-i18n="label-obtenido">Obtenido el:</span> <?= date('d/m/Y', strtotime($logro['fecha_ganado'])) ?>
                            </p>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

    </div>

    <script src="../js/i18n.js"></script>
    <script>
    const translations = {
        es: {
            'nav-inicio':     'Volver al inicio',
            'nav-biblioteca': 'Ir a mi biblioteca',
            'nav-buscador':   'Ir al buscador',
            'nav-logout':     'Cerrar sesión',
            'label-email':    '📜 Email:',
            'label-miembro':  '⏳ Miembro desde:',
            'reto-objetivo':  'Tu objetivo este mes es leer',
            'reto-libros':    'libros.',
            'reto-conseguido':'¡Misión Cumplida! Has ganado el favor de los dioses literarios.',
            'reto-pendiente': 'Sigue leyendo, aún estás a tiempo de completarlo.',
            'reto-vacio':     'Aún no has fijado tu destino para este mes.',
            'btn-nuevo-reto': 'Fijar Nuevo Reto',
            'section-logros': 'Logros',
            'logros-vacio':   'Aún no tienes ningún logro. Sigue leyendo para desbloquearlas.',
            'label-obtenido': 'Obtenido el:',
        },
        en: {
            'nav-inicio':     'Back to Home',
            'nav-biblioteca': 'Go to my library',
            'nav-buscador':   'Go to search',
            'nav-logout':     'Log out',
            'label-email':    '📜 Email:',
            'label-miembro':  '⏳ Member since:',
            'reto-objetivo':  'Your goal this month is to read',
            'reto-libros':    'books.',
            'reto-conseguido':'Mission Complete! You have earned the favor of the literary gods.',
            'reto-pendiente': 'Keep reading, you still have time to complete it.',
            'reto-vacio':     "You haven't set your goal for this month yet.",
            'btn-nuevo-reto': 'Set New Goal',
            'section-logros': 'Achievements',
            'logros-vacio':   "You don't have any achievements yet. Keep reading to unlock them.",
            'label-obtenido': 'Earned on:',
        }
    };

    const MONTHS_ES = ['Enero','Febrero','Marzo','Abril','Mayo','Junio','Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre'];
    const MONTHS_EN = ['January','February','March','April','May','June','July','August','September','October','November','December'];

    // Extendemos I18n.init con un hook para el título del reto de mes
    const _origInit = I18n.init.bind(I18n);
    I18n.init = function(t, titleEs, titleEn) {
        // setLang original + actualización del título del reto
        const _origSetLang = I18n.setLang.bind(I18n);
        I18n.setLang = function(lang, t2) {
            _origSetLang(lang, t2);
            const retoTitulo = document.getElementById('reto-titulo');
            if (retoTitulo) {
                const mes  = parseInt(retoTitulo.dataset.mes) - 1;
                const anio = retoTitulo.dataset.anio;
                retoTitulo.textContent = lang === 'en'
                    ? `Reading Challenge (${MONTHS_EN[mes]} ${anio})`
                    : `Reto de Lectura (${MONTHS_ES[mes]} ${anio})`;
            }
        };
        _origInit(t, titleEs, titleEn);
    };

    I18n.init(translations, 'K-Libro | Mi Cuenta', 'K-Libro | My Account');
    </script>

</body>
</html>