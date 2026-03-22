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
require_once '../../backend/helpers/biblioteca_schema.php';
require_once '../../backend/helpers/logros.php';

asegurarColumnaFechaLectura($pdo);

$user_id = $_SESSION['user_id'];
$retoFlash = $_SESSION['reto_flash'] ?? null;
unset($_SESSION['reto_flash']);

// 2. OBTENER DATOS DEL USUARIO
$stmtUsuario = $pdo->prepare("SELECT nombre, email, avatar, fecha_registro FROM usuarios WHERE id = ?");
$stmtUsuario->execute([$user_id]);
$usuario = $stmtUsuario->fetch();

// 3. OBTENER RETO DEL MES ACTUAL
$mesActual = date('n');
$anioActual = date('Y');
$stmtReto = $pdo->prepare("SELECT meta_libros, conseguido FROM retos_mensuales WHERE usuario_id = ? AND mes = ? AND anio = ?");
$stmtReto->execute([$user_id, $mesActual, $anioActual]);
$reto = $stmtReto->fetch();

$librosLeidosMes = 0;
$progresoReto = 0;

if ($reto) {
    $resumenReto = recalcularRetoMensual($pdo, (int) $user_id, (int) $mesActual, (int) $anioActual);
    if ($resumenReto) {
        $librosLeidosMes = (int) $resumenReto['libros_leidos'];
        $progresoReto = (int) $resumenReto['porcentaje'];
        $reto['meta_libros'] = (int) $resumenReto['meta_libros'];
        $reto['conseguido'] = (int) $resumenReto['conseguido'];
    }
}

sincronizarLogrosUsuario($pdo, (int) $user_id);

// 4. OBTENER LOGROS DEL USUARIO (Catálogo completo + desbloqueados)
$totalLibrosLeidos = contarLibrosLeidosTotales($pdo, (int) $user_id);
$sqlLogros = "SELECT l.nombre, l.descripcion, l.icono, l.criterio, ul.fecha_ganado
                            FROM logros l
                            INNER JOIN (
                                SELECT MIN(id) AS id
                                FROM logros
                                GROUP BY nombre
                            ) canon ON canon.id = l.id
                            LEFT JOIN usuario_logros ul
                                ON l.id = ul.logro_id AND ul.usuario_id = ?
                            ORDER BY ul.fecha_ganado IS NULL ASC, ul.fecha_ganado DESC, l.criterio ASC, l.id ASC";
$stmtLogros = $pdo->prepare($sqlLogros);
$stmtLogros->execute([$user_id]);
$mis_logros = $stmtLogros->fetchAll();
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
            <a href="../3_Inicio/inicio.php" data-i18n="nav-inicio">IInicio</a> |
            <a href="../4_Biblioteca/biblioteca.php" data-i18n="nav-biblioteca">Biblioteca</a> |
            <a href="../6_buscador/buscador.php" data-i18n="nav-buscador">Buscador</a>
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

        <?php if ($retoFlash): ?>
            <div class="flash-message flash-<?= htmlspecialchars($retoFlash['tipo']) ?>">
                <?= htmlspecialchars($retoFlash['mensaje']) ?>
            </div>
        <?php endif; ?>

        <h2 id="reto-titulo" data-mes="<?= date('n') ?>" data-anio="<?= date('Y') ?>">Reto de Lectura (<?= date('F Y') ?>)</h2>
        <div class="reto-card">
            <?php if ($reto): ?>
                <p><span data-i18n="reto-objetivo">Tu objetivo este mes es leer</span> <strong><?= $reto['meta_libros'] ?></strong> <span data-i18n="reto-libros">libros.</span></p>
                <p class="reto-progress-text"><span data-i18n="reto-progreso">Progreso actual:</span> <strong><?= $librosLeidosMes ?>/<?= (int) $reto['meta_libros'] ?></strong> <span data-i18n="reto-progreso-libros">libros leídos este mes.</span></p>
                <div class="reto-progress" aria-label="Progreso del reto mensual">
                    <div class="reto-progress-bar" style="width: <?= $progresoReto ?>%;"></div>
                </div>
                <p class="reto-progress-percent"><?= $progresoReto ?>%</p>
                <?php if ($reto['conseguido']): ?>
                    <p class="reto-completado" data-i18n="reto-conseguido">Has completado el objetivo.</p>
                <?php else: ?>
                    <p data-i18n="reto-pendiente">Sigue leyendo, aún estás a tiempo de completarlo.</p>
                <?php endif; ?>
            <?php else: ?>
                <p data-i18n="reto-vacio">Aún no has fijado tu destino para este mes.</p>
                <form action="../../backend/procesar/guardar_reto.php" method="POST" class="reto-form">
                    <label for="meta_libros" class="reto-form-label" data-i18n="label-meta-libros">¿Cuántos libros quieres leer este mes?</label>
                    <div class="reto-input-group">
                        <input type="number" id="meta_libros" name="meta_libros" class="reto-input" min="1" max="50" step="1" value="1" required>
                        <button type="submit" data-i18n="btn-nuevo-reto">Fijar Nuevo Reto</button>
                    </div>
                    <p class="reto-hint" data-i18n="reto-hint">Elige una meta entre 1 y 50 libros para este mes.</p>
                </form>
            <?php endif; ?>
        </div>

        <h2 data-i18n="section-logros">Logros</h2>
        <div class="logros-grid">
            <?php foreach ($mis_logros as $logro): ?>
                <?php
                $desbloqueado = !empty($logro['fecha_ganado']);
                $esLogroLectura = (int) $logro['criterio'] > 0;
                $progresoLogro = $esLogroLectura
                    ? min($totalLibrosLeidos, (int) $logro['criterio']) . '/' . (int) $logro['criterio']
                    : null;
                ?>
                <div class="logro-card<?= $desbloqueado ? '' : ' logro-card-lock' ?>">
                    <div class="logro-icono"><?= $desbloqueado ? '🏆' : '🔒' ?></div>
                    <div class="logro-info">
                        <h3><?= htmlspecialchars($logro['nombre']) ?></h3>
                        <p><?= htmlspecialchars($logro['descripcion']) ?></p>
                        <?php if ($desbloqueado): ?>
                            <p class="logro-meta">
                                <span data-i18n="label-obtenido">Obtenido el:</span> <?= date('d/m/Y', strtotime($logro['fecha_ganado'])) ?>
                            </p>
                        <?php elseif ($esLogroLectura): ?>
                            <p class="logro-meta">
                                <span data-i18n="label-progreso-logro">Progreso:</span> <?= htmlspecialchars($progresoLogro) ?>
                            </p>
                        <?php else: ?>
                            <p class="logro-meta" data-i18n="label-bloqueado">Aún bloqueado</p>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

    </div>

    <script src="../js/i18n.js"></script>
    <script>
    const translations = {
        es: {
            'nav-inicio':     'Inicio',
            'nav-biblioteca': 'Biblioteca',
            'nav-buscador':   'Buscador',
            'nav-logout':     'Cerrar sesión',
            'label-email':    '📜 Email:',
            'label-miembro':  '⏳ Miembro desde:',
            'reto-objetivo':  'Tu objetivo este mes es leer',
            'reto-libros':    'libros.',
            'reto-progreso':  'Progreso actual:',
            'reto-progreso-libros': 'libros leídos este mes.',
            'reto-conseguido':'Has completado el objetivo.',
            'reto-pendiente': 'Sigue leyendo, aún estás a tiempo de completarlo.',
            'reto-vacio':     'Aún no has fijado tu destino para este mes.',
            'btn-nuevo-reto': 'Fijar Nuevo Reto',
            'label-meta-libros': '¿Cuántos libros quieres leer este mes?',
            'reto-hint':      'Elige una meta entre 1 y 50 libros para este mes.',
            'section-logros': 'Logros',
            'label-obtenido': 'Obtenido el:',
            'label-progreso-logro': 'Progreso:',
            'label-bloqueado': 'Aún bloqueado',
        },
        en: {
            'nav-inicio':     'Home',
            'nav-biblioteca': 'Library',
            'nav-buscador':   'Search',
            'nav-logout':     'Log out',
            'label-email':    '📜 Email:',
            'label-miembro':  '⏳ Member since:',
            'reto-objetivo':  'Your goal this month is to read',
            'reto-libros':    'books.',
            'reto-progreso':  'Current progress:',
            'reto-progreso-libros': 'books read this month.',
            'reto-conseguido':'You have completed the goal.',
            'reto-pendiente': 'Keep reading, you still have time to complete it.',
            'reto-vacio':     "You haven't set your goal for this month yet.",
            'btn-nuevo-reto': 'Set New Goal',
            'label-meta-libros': 'How many books do you want to read this month?',
            'reto-hint':      'Choose a goal between 1 and 50 books for this month.',
            'section-logros': 'Achievements',
            'label-obtenido': 'Earned on:',
            'label-progreso-logro': 'Progress:',
            'label-bloqueado': 'Still locked',
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