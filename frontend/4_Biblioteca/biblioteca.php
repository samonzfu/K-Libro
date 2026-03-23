<?php
session_start();
if (empty($_SESSION['user_id'])) {
    header('Location: ../2_Login/login.php');
    exit;
}

include '../../backend/conexionBD.php';
require_once '../../backend/helpers/biblioteca_schema.php';

asegurarColumnaFechaLectura($pdo);

$cssVersion = @filemtime(__DIR__ . '/css/estilo.css') ?: time();

$usuarioId = (int) $_SESSION['user_id'];
$librosPorEstado = [
    'pendiente' => [],
    'leyendo' => [],
    'leido' => []
];

try {
    $stmt = $pdo->prepare(
        'SELECT b.estado, b.libro_id_openlibrary, b.fecha_lectura, b.calificacion, b.review, l.titulo, l.autores, l.portada
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

function renderizarSeccion(string $titulo, string $key, string $estadoActual, string $sectionId, array $libros): void
{
    echo '<section id="' . htmlspecialchars($sectionId, ENT_QUOTES, 'UTF-8') . '" class="seccion-estado">';
    echo '<h2 data-i18n="' . $key . '">' . htmlspecialchars($titulo, ENT_QUOTES, 'UTF-8') . '</h2>';

    if (count($libros) === 0) {
        echo '<p class="vacio" data-i18n="biblio-vacio">Todavía no tienes libros en este apartado.</p>';
        echo '</section>';
        return;
    }

    echo '<div class="grid-libros">';

    foreach ($libros as $index => $libro) {
        $tituloLibro = $libro['titulo'] ?: 'Sin título';
        $autorLibro = $libro['autores'] ?: 'Autor desconocido';
        $portadaLibro = $libro['portada'] ?: 'https://via.placeholder.com/130x190?text=Sin+portada';
        $idOpenLibrary = (string) ($libro['libro_id_openlibrary'] ?? '');
        $estadoLibro = (string) ($libro['estado'] ?? '');
        $fechaLectura = trim((string) ($libro['fecha_lectura'] ?? ''));
        $calificacion = isset($libro['calificacion']) ? (int) $libro['calificacion'] : 0;
        $review = trim((string) ($libro['review'] ?? ''));

        $esExtra = $index >= 10;
        $clasesCard = 'book-card' . ($esExtra ? ' book-card-extra' : '');
        $hiddenAttr = $esExtra ? ' hidden' : '';

        echo '<article class="' . $clasesCard . '"' . $hiddenAttr
            . ' data-libro-id="' . htmlspecialchars($idOpenLibrary, ENT_QUOTES, 'UTF-8') . '"'
            . ' data-libro-titulo="' . htmlspecialchars($tituloLibro, ENT_QUOTES, 'UTF-8') . '"'
            . ' data-libro-autor="' . htmlspecialchars($autorLibro, ENT_QUOTES, 'UTF-8') . '"'
            . ' data-libro-portada="' . htmlspecialchars($portadaLibro, ENT_QUOTES, 'UTF-8') . '"'
            . ' data-libro-estado="' . htmlspecialchars($estadoLibro, ENT_QUOTES, 'UTF-8') . '"'
            . ' data-libro-fecha-lectura="' . htmlspecialchars($fechaLectura, ENT_QUOTES, 'UTF-8') . '"'
            . ' data-libro-calificacion="' . htmlspecialchars((string) $calificacion, ENT_QUOTES, 'UTF-8') . '"'
            . ' data-libro-review="' . htmlspecialchars($review, ENT_QUOTES, 'UTF-8') . '"'
            . '>';
        echo '<img src="' . htmlspecialchars($portadaLibro, ENT_QUOTES, 'UTF-8') . '" alt="Portada de ' . htmlspecialchars($tituloLibro, ENT_QUOTES, 'UTF-8') . '">';
        echo '<h3>' . htmlspecialchars($tituloLibro, ENT_QUOTES, 'UTF-8') . '</h3>';
        echo '<p>' . htmlspecialchars($autorLibro, ENT_QUOTES, 'UTF-8') . '</p>';

        if ($idOpenLibrary !== '') {
            echo '<div class="book-acciones">';
            echo '<button type="button" class="btn-detalles" onclick="abrirDetallesLibro(this)" data-i18n="biblio-detalles">Detalles</button>';
            echo '<form method="POST" action="../../backend/procesar/eliminar_libro.php" class="form-eliminar" onsubmit="return window.confirm(\'¿Seguro que quieres eliminar este libro de tu biblioteca?\');">';
            echo '<input type="hidden" name="libro_id" value="' . htmlspecialchars($idOpenLibrary, ENT_QUOTES, 'UTF-8') . '">';
            echo '<button type="submit" class="btn-eliminar">';
            echo '<span data-i18n="biblio-eliminar">Eliminar</span>';
            echo '</button>';
            echo '</form>';
            echo '</div>';
        }

        echo '</article>';
    }

    echo '</div>';

    if (count($libros) > 10) {
        echo '<div class="acciones-seccion">';
        echo '<button type="button" class="btn-ver-mas-seccion" aria-expanded="false">';
        echo '<span class="label-more" data-i18n="biblio-ver-mas">Ver más</span>';
        echo '<span class="label-less" data-i18n="biblio-ver-menos" hidden>Ver menos</span>';
        echo '</button>';
        echo '</div>';
    }

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
            <a href="../3_Inicio/inicio.php" data-i18n="nav-inicio">Inicio</a> |
            <a href="../5_Mi_cuenta/mi_cuenta.php" data-i18n="nav-cuenta">Mi cuenta</a> |
            <a href="../6_Buscador/buscador.php" data-i18n="nav-buscador">Buscador</a>
            <button id="btn-lang" class="btn-lang">🌐 English</button>
        </nav>

        <h1 data-i18n="biblio-h1">Mi biblioteca</h1>
        <p class="subtitulo" data-i18n="biblio-subtitulo">Tus libros guardados se organizan automáticamente por estado.</p>

        <nav class="nav-secciones" aria-label="Estados de lectura">
            <a href="#seccion-pendiente" data-i18n="biblio-nav-pendiente">Pendientes de leer</a>
            <a href="#seccion-leyendo" data-i18n="biblio-nav-leyendo">Leyendo</a>
            <a href="#seccion-leido" data-i18n="biblio-nav-leido">Leídos</a>
        </nav>

        <?php if (!empty($errorCarga)): ?>
            <p class="error"><?= htmlspecialchars($errorCarga, ENT_QUOTES, 'UTF-8') ?></p>
        <?php else: ?>
            <?php renderizarSeccion('Pendientes de leer:', 'biblio-pendiente', 'pendiente', 'seccion-pendiente', $librosPorEstado['pendiente']); ?>
            <?php renderizarSeccion('Leyendo:', 'biblio-leyendo', 'leyendo', 'seccion-leyendo', $librosPorEstado['leyendo']); ?>
            <?php renderizarSeccion('Leídos:', 'biblio-leido', 'leido', 'seccion-leido', $librosPorEstado['leido']); ?>
        <?php endif; ?>
    </main>
    <script src="../js/i18n.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', () => {
    I18n.init({
        es: {
            'nav-inicio':        'Inicio',
            'nav-cuenta':        'Mi cuenta',
            'nav-buscador':      'Buscador',
            'biblio-h1':         'Biblioteca',
            'biblio-subtitulo':  'Tus libros guardados se organizan automáticamente por estado.',
            'biblio-nav-pendiente': 'Pendientes de leer',
            'biblio-nav-leyendo': 'Leyendo',
            'biblio-nav-leido': 'Leídos',
            'biblio-detalles': 'Detalles',
            'biblio-eliminar': 'Eliminar',
            'biblio-ver-mas': 'Ver más',
            'biblio-ver-menos': 'Ver menos',
            'biblio-modal-rating': 'Puntuación',
            'biblio-modal-review': 'Reseña',
            'biblio-modal-read-date': 'Fecha de lectura',
            'biblio-modal-read-date-empty': 'Sin fecha registrada',
            'biblio-sin-calificacion': 'Sin puntuar',
            'biblio-sin-resena': 'Sin reseña',
            'biblio-modal-rating-empty': 'Sin puntuar',
            'biblio-modal-review-ph': 'Escribe una reseña o déjala vacía para eliminarla',
            'biblio-modal-read-date-ph': 'Elige la fecha en que terminaste el libro',
            'biblio-modal-save': 'Guardar cambios',
            'biblio-modal-guardado': 'Detalles guardados correctamente.',
            'biblio-modal-error': 'No se pudieron guardar los detalles.',
            'biblio-modal-date-error': 'Indica una fecha de lectura válida.',
            'biblio-cerrar': 'Cerrar',
            'biblio-pendiente':  'Pendientes de leer:',
            'biblio-leyendo':    'Leyendo:',
            'biblio-leido':      'Leídos:',
            'biblio-vacio':      'Todavía no tienes libros en este apartado.',
            'biblio-modal-editar': '📝 Editar',
        },
        en: {
            'nav-inicio':        'Home',
            'nav-cuenta':        'My account',
            'nav-buscador':      'Search',
            'biblio-h1':         'My library',
            'biblio-subtitulo':  'Your saved books are automatically organised by status.',
            'biblio-nav-pendiente': 'To read',
            'biblio-nav-leyendo': 'Reading',
            'biblio-nav-leido': 'Read',
            'biblio-detalles': 'Details',
            'biblio-eliminar': 'Delete',
            'biblio-ver-mas': 'Show more',
            'biblio-ver-menos': 'Show less',
            'biblio-modal-rating': 'Rating',
            'biblio-modal-review': 'Review',
            'biblio-modal-read-date': 'Read date',
            'biblio-modal-read-date-empty': 'No saved date',
            'biblio-sin-calificacion': 'No rating',
            'biblio-sin-resena': 'No review',
            'biblio-modal-rating-empty': 'No rating',
            'biblio-modal-review-ph': 'Write a review or leave it empty to remove it',
            'biblio-modal-read-date-ph': 'Choose the date when you finished the book',
            'biblio-modal-save': 'Save changes',
            'biblio-modal-guardado': 'Details saved successfully.',
            'biblio-modal-error': 'Could not save details.',
            'biblio-modal-date-error': 'Enter a valid read date.',
            'biblio-cerrar': 'Close',
            'biblio-pendiente':  'To read:',
            'biblio-leyendo':    'Reading:',
            'biblio-leido':      'Read:',
            'biblio-modal-editar': '📝 Edit',
            'biblio-vacio':      'You have no books in this section yet.',
        }
    }, 'Mi biblioteca | K-Libro', 'My library | K-Libro');

    const modal = document.getElementById('modal-detalles-libro');
    const modalCerrar = document.getElementById('modal-detalles-cerrar');
    const modalOverlay = document.getElementById('modal-detalles-overlay');
    const modalPortada = document.getElementById('modal-detalles-portada');
    const modalTitulo = document.getElementById('modal-detalles-titulo');
    const modalAutor = document.getElementById('modal-detalles-autor');
    const modalCalificacion = document.getElementById('modal-detalles-calificacion');
    const modalResena = document.getElementById('modal-detalles-resena');
    const modalFechaLectura = document.getElementById('modal-detalles-fecha-lectura');
    const modalFechaLecturaBloque = document.getElementById('modal-detalles-fecha-bloque');
    const modalInputId = document.getElementById('modal-detalles-id');
    const modalInputCalificacion = document.getElementById('modal-detalles-input-calificacion');
    const modalInputResena = document.getElementById('modal-detalles-input-resena');
    const modalInputFechaLectura = document.getElementById('modal-detalles-input-fecha-lectura');
    const modalInputFechaGrupo = document.getElementById('modal-detalles-input-fecha-grupo');
    const modalForm = document.getElementById('modal-detalles-form');

    const abrirModalDetalles = (card) => {
        if (!modal || !card) return;

        const id = card.getAttribute('data-libro-id') || '';
        const titulo = card.getAttribute('data-libro-titulo') || '';
        const autor = card.getAttribute('data-libro-autor') || '';
        const portada = card.getAttribute('data-libro-portada') || '';
        const estado = card.getAttribute('data-libro-estado') || '';
        const fechaLectura = card.getAttribute('data-libro-fecha-lectura') || '';
        const calificacionRaw = card.getAttribute('data-libro-calificacion') || '';
        const review = (card.getAttribute('data-libro-review') || '').trim();

        const calificacion = Number.parseInt(calificacionRaw, 10);

        modalTitulo.textContent = titulo;
        modalAutor.textContent = autor;
        modalPortada.src = portada;
        modalPortada.alt = `Portada de ${titulo}`;
        modalCalificacion.textContent = calificacion >= 1 && calificacion <= 5 ? `${calificacion}/5` : I18n.t('biblio-sin-calificacion');
        modalResena.textContent = review !== '' ? review : I18n.t('biblio-sin-resena');
        if (modalFechaLectura) {
            modalFechaLectura.textContent = fechaLectura !== '' ? fechaLectura.split('-').reverse().join('/') : I18n.t('biblio-modal-read-date-empty');
        }
        if (modalFechaLecturaBloque) {
            modalFechaLecturaBloque.hidden = estado !== 'leido';
        }

        if (modalInputId) modalInputId.value = id;
        if (modalInputCalificacion) modalInputCalificacion.value = calificacion >= 1 && calificacion <= 5 ? String(calificacion) : '';
        if (modalInputResena) modalInputResena.value = review;
        if (modalInputFechaLectura) modalInputFechaLectura.value = fechaLectura;
        if (modalInputFechaGrupo) modalInputFechaGrupo.hidden = estado !== 'leido';

        cerrarEdicion();
        modal.hidden = false;
    };

    const cerrarModalDetalles = () => {
        if (!modal) return;
        modal.hidden = true;
    };

    const cerrarEdicion = () => {
        const btnEditar = document.getElementById('modal-detalles-btn-editar');
        if (btnEditar && modalForm) {
            btnEditar.style.display = 'block';
            modalForm.style.display = 'none';
        }
    };

    const abrirEdicion = () => {
        const btnEditar = document.getElementById('modal-detalles-btn-editar');
        if (btnEditar && modalForm) {
            btnEditar.style.display = 'none';
            modalForm.style.display = 'flex';
        }
    };

    window.abrirDetallesLibro = (btn) => {
        if (!btn) return;
        const card = btn.closest('.book-card');
        abrirModalDetalles(card);
    };

    if (modalCerrar) {
        modalCerrar.addEventListener('click', cerrarModalDetalles);
    }

    if (modalOverlay) {
        modalOverlay.addEventListener('click', cerrarModalDetalles);
    }

    const btnEditarModal = document.getElementById('modal-detalles-btn-editar');
    if (btnEditarModal) {
        btnEditarModal.addEventListener('click', abrirEdicion);
    }

    if (modalForm) {
        modalForm.addEventListener('submit', async (event) => {
            event.preventDefault();

            const idLibro = modalInputId ? modalInputId.value.trim() : '';
            if (idLibro === '') return;

            const calificacion = modalInputCalificacion ? modalInputCalificacion.value.trim() : '';
            const review = modalInputResena ? modalInputResena.value.trim() : '';
            const fechaLectura = modalInputFechaLectura ? modalInputFechaLectura.value.trim() : '';

            if (modalInputFechaGrupo && !modalInputFechaGrupo.hidden && fechaLectura === '') {
                alert(I18n.t('biblio-modal-date-error'));
                return;
            }

            try {
                const datos = new URLSearchParams();
                datos.append('id_openlibrary', idLibro);
                datos.append('calificacion', calificacion);
                datos.append('review', review);
                datos.append('fecha_lectura', fechaLectura);

                const respuesta = await fetch('../../backend/procesar/actualizar_resena.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8',
                        'Accept': 'application/json'
                    },
                    body: datos.toString()
                });

                const resultado = await respuesta.json();
                if (!respuesta.ok || !resultado.ok) {
                    throw new Error(resultado.mensaje || 'No se pudieron guardar los detalles');
                }

                const card = document.querySelector(`.book-card[data-libro-id="${CSS.escape(idLibro)}"]`);
                if (card) {
                    card.setAttribute('data-libro-calificacion', calificacion);
                    card.setAttribute('data-libro-review', review);
                    card.setAttribute('data-libro-fecha-lectura', fechaLectura);
                }

                const calificacionNum = Number.parseInt(calificacion, 10);
                modalCalificacion.textContent = calificacionNum >= 1 && calificacionNum <= 5 ? `${calificacionNum}/5` : I18n.t('biblio-sin-calificacion');
                modalResena.textContent = review !== '' ? review : I18n.t('biblio-sin-resena');
                if (modalFechaLectura && modalInputFechaGrupo && !modalInputFechaGrupo.hidden) {
                    modalFechaLectura.textContent = fechaLectura !== '' ? fechaLectura.split('-').reverse().join('/') : I18n.t('biblio-modal-read-date-empty');
                }

                alert(I18n.t('biblio-modal-guardado'));
                cerrarEdicion();
            } catch (error) {
                console.error('Error al guardar detalles del libro:', error);
                alert(I18n.t('biblio-modal-error'));
            }
        });
    }

    document.querySelectorAll('.btn-ver-mas-seccion').forEach((btn) => {
        btn.addEventListener('click', () => {
            const seccion = btn.closest('.seccion-estado');
            if (!seccion) return;

            const extras = seccion.querySelectorAll('.book-card-extra');
            const expandido = btn.getAttribute('aria-expanded') === 'true';
            const nuevoEstado = !expandido;

            extras.forEach((card) => {
                card.hidden = !nuevoEstado;
            });

            btn.setAttribute('aria-expanded', nuevoEstado ? 'true' : 'false');

            const labelMore = btn.querySelector('.label-more');
            const labelLess = btn.querySelector('.label-less');
            if (labelMore && labelLess) {
                labelMore.hidden = nuevoEstado;
                labelLess.hidden = !nuevoEstado;
            }
        });
    });
    });

    </script>

    <div id="modal-detalles-libro" class="modal-detalles-libro" hidden>
        <div id="modal-detalles-overlay" class="modal-detalles-overlay"></div>
        <div class="modal-detalles-contenido" role="dialog" aria-modal="true" aria-labelledby="modal-detalles-titulo">
            <button id="modal-detalles-cerrar" type="button" class="modal-detalles-cerrar" data-i18n="biblio-cerrar">Cerrar</button>

            <div class="modal-detalles-grid">
                <img id="modal-detalles-portada" src="" alt="Portada del libro">
                <div class="modal-detalles-info">
                    <h2 id="modal-detalles-titulo"></h2>
                    <p id="modal-detalles-autor" class="modal-detalles-autor"></p>
                    <p><strong data-i18n="biblio-modal-rating">Puntuación</strong>: <span id="modal-detalles-calificacion"></span></p>
                    <p id="modal-detalles-fecha-bloque"><strong data-i18n="biblio-modal-read-date">Fecha de lectura</strong>: <span id="modal-detalles-fecha-lectura"></span></p>
                    <p><strong data-i18n="biblio-modal-review">Reseña</strong>:</p>
                    <p id="modal-detalles-resena" class="modal-detalles-resena"></p>

                    <button id="modal-detalles-btn-editar" type="button" class="btn-modal-editar" data-i18n="biblio-modal-editar">📝 Editar</button>

                    <form id="modal-detalles-form" class="modal-detalles-form" style="display: none;">
                        <input id="modal-detalles-id" type="hidden" name="id_openlibrary" value="">

                        <label for="modal-detalles-input-calificacion" class="modal-detalles-label" data-i18n="biblio-modal-rating">Puntuación</label>
                        <select id="modal-detalles-input-calificacion" class="modal-detalles-input" name="calificacion">
                            <option value="" data-i18n="biblio-modal-rating-empty">Sin puntuar</option>
                            <option value="1">1/5</option>
                            <option value="2">2/5</option>
                            <option value="3">3/5</option>
                            <option value="4">4/5</option>
                            <option value="5">5/5</option>
                        </select>

                        <label for="modal-detalles-input-resena" class="modal-detalles-label" data-i18n="biblio-modal-review">Reseña</label>
                        <textarea id="modal-detalles-input-resena" class="modal-detalles-input" name="review" rows="4" maxlength="2000" data-i18n-ph="biblio-modal-review-ph" placeholder="Escribe una reseña o déjala vacía para eliminarla"></textarea>

                        <div id="modal-detalles-input-fecha-grupo">
                            <label for="modal-detalles-input-fecha-lectura" class="modal-detalles-label" data-i18n="biblio-modal-read-date">Fecha de lectura</label>
                            <input id="modal-detalles-input-fecha-lectura" type="date" class="modal-detalles-input" name="fecha_lectura" max="<?= date('Y-m-d') ?>" data-i18n-ph="biblio-modal-read-date-ph">
                        </div>

                        <button type="submit" class="btn-modal-guardar" data-i18n="biblio-modal-save">Guardar cambios</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</body>
</html>