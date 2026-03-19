<?php
session_start();
if (empty($_SESSION['user_id'])) {
    header('Location: /GitHub/K-Libro/frontend/2_Login/login.php');
    exit;
}

include '../../backend/conexionBD.php';

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

function renderizarSeccion(string $titulo, array $libros): void
{
    echo '<section class="seccion-estado">';
    echo '<h2>' . htmlspecialchars($titulo, ENT_QUOTES, 'UTF-8') . '</h2>';

    if (count($libros) === 0) {
        echo '<p class="vacio">Todavía no tienes libros en este apartado.</p>';
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
    <link rel="stylesheet" href="css/estilo.css">
</head>
<body>
    <main class="container">
        <h1>Mi biblioteca</h1>
        <p class="subtitulo">Tus libros guardados se organizan automáticamente por estado.</p>

        <?php if (!empty($errorCarga)): ?>
            <p class="error"><?= htmlspecialchars($errorCarga, ENT_QUOTES, 'UTF-8') ?></p>
        <?php else: ?>
            <?php renderizarSeccion('Pendientes de leer', $librosPorEstado['pendiente']); ?>
            <?php renderizarSeccion('Leyendo', $librosPorEstado['leyendo']); ?>
            <?php renderizarSeccion('Leídos', $librosPorEstado['leido']); ?>
        <?php endif; ?>
    </main>
</body>
</html>