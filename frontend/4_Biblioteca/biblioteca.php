<?php
session_start();
if (empty($_SESSION['user_id'])) {
    header('Location: /GitHub/K-Libro/frontend/2_Login/login.php');
    exit;
}

include '../../backend/conexionBD.php';

$cssVersion = @filemtime(__DIR__ . '/css/estilo.css') ?: time();

$usuarioId = (int) $_SESSION['user_id'];
$librosPorEstado = [
    'pendiente' => [],
    'leyendo' => [],
    'leido' => []
];

try {
    $stmt = $pdo->prepare(
        'SELECT b.estado, l.titulo, l.autores, l.portada
         FROM biblioteca b
         INNER JOIN (
            SELECT MAX(id) AS id
            FROM biblioteca
            WHERE usuario_id = :usuario_id
            GROUP BY libro_id_openlibrary
         ) ult ON ult.id = b.id
         INNER JOIN libros l ON l.id_openlibrary = b.libro_id_openlibrary
         WHERE b.usuario_id = :usuario_id
         ORDER BY b.fecha_accion DESC'
    );

    $stmt->execute([':usuario_id' => $usuarioId]);
    $filas = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($filas as $fila) {
        if (isset($librosPorEstado[$fila['estado']])) {
            $librosPorEstado[$fila['estado']][] = $fila;
        }
    }
} catch (Throwable $e) {
    $errorCarga = 'No se pudo cargar tu biblioteca en este momento.';
}

function renderizarSeccion(string $titulo, string $key, array $libros): void
{
    echo '<section class="seccion-estado">';
    echo '<h2 data-i18n="' . $key . '">' . htmlspecialchars($titulo, ENT_QUOTES, 'UTF-8') . '</h2>';

    if (count($libros) === 0) {
        echo '<p class="vacio" data-i18n="biblio-vacio">Todavía no tienes libros en este apartado.</p>';
        echo '</section>';
        return;
    }

    echo '<div class="grid-libros">';

    foreach ($libros as $libro) {
        $tituloLibro = $libro['titulo'] ?: 'Sin título';
        $autorLibro = $libro['autores'] ?: 'Autor desconocido';
        $portadaLibro = $libro['portada'] ?: 'https://via.placeholder.com/130x190?text=Sin+portada';

        echo '<article class="book-card">';
        echo '<img src="' . htmlspecialchars($portadaLibro, ENT_QUOTES, 'UTF-8') . '" alt="Portada de ' . htmlspecialchars($tituloLibro, ENT_QUOTES, 'UTF-8') . '">';
        echo '<h3>' . htmlspecialchars($tituloLibro, ENT_QUOTES, 'UTF-8') . '</h3>';
        echo '<p>' . htmlspecialchars($autorLibro, ENT_QUOTES, 'UTF-8') . '</p>';
        echo '</article>';
    }

    echo '</div>';
    echo '</section>';
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mi biblioteca | K-Libro</title>
    <link rel="stylesheet" href="css/estilo.css?v=<?= $cssVersion ?>">
</head>
<body>
    <main class="container">
        <nav>
            <a href="../3_Inicio/inicio.php" data-i18n="nav-inicio">Volver al inicio</a> |
            <a href="../5_Mi_cuenta/mi_cuenta.php" data-i18n="nav-cuenta">Ir a mi cuenta</a> |
            <a href="../6_buscador/buscador.php" data-i18n="nav-buscador">Ir al buscador</a>
            <button id="btn-lang" class="btn-lang">🌐 English</button>
        </nav>

        <h1 data-i18n="biblio-h1">Mi biblioteca</h1>
        <p class="subtitulo" data-i18n="biblio-subtitulo">Tus libros guardados se organizan automáticamente por estado.</p>

        <?php if (!empty($errorCarga)): ?>
            <p class="error"><?= htmlspecialchars($errorCarga, ENT_QUOTES, 'UTF-8') ?></p>
        <?php else: ?>
            <?php renderizarSeccion('Pendientes de leer:', 'biblio-pendiente', $librosPorEstado['pendiente']); ?>
            <?php renderizarSeccion('Leyendo:', 'biblio-leyendo', $librosPorEstado['leyendo']); ?>
            <?php renderizarSeccion('Leídos:', 'biblio-leido', $librosPorEstado['leido']); ?>
        <?php endif; ?>
    </main>
    <script src="../js/i18n.js"></script>
    <script>
    I18n.init({
        es: {
            'nav-inicio':        'Volver al inicio',
            'nav-cuenta':        'Ir a mi cuenta',
            'nav-buscador':      'Ir al buscador',
            'biblio-h1':         'Mi biblioteca',
            'biblio-subtitulo':  'Tus libros guardados se organizan automáticamente por estado.',
            'biblio-pendiente':  'Pendientes de leer:',
            'biblio-leyendo':    'Leyendo:',
            'biblio-leido':      'Leídos:',
            'biblio-vacio':      'Todavía no tienes libros en este apartado.',
        },
        en: {
            'nav-inicio':        'Back to home',
            'nav-cuenta':        'Go to my account',
            'nav-buscador':      'Go to search',
            'biblio-h1':         'My library',
            'biblio-subtitulo':  'Your saved books are automatically organised by status.',
            'biblio-pendiente':  'To read:',
            'biblio-leyendo':    'Reading:',
            'biblio-leido':      'Read:',
            'biblio-vacio':      'You have no books in this section yet.',
        }
    }, 'Mi biblioteca | K-Libro', 'My library | K-Libro');
    </script>
</body>
</html>