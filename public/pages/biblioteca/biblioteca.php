<?php
session_start();
require_once __DIR__ . '/../../../backend/helpers/flash.php';
if (empty($_SESSION['user_id'])) {
    header('Location: ../login/login.php');
    exit;
}

require_once __DIR__ . '/../../../backend/config/db.php';
require_once __DIR__ . '/../../../backend/helpers/biblioteca_schema.php';
require_once __DIR__ . '/../../../backend/helpers/csrf.php';

asegurarColumnaFechaLectura($pdo);

$cssVersion = @filemtime(__DIR__ . '/../../css/biblioteca.css') ?: time();

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
            echo '<button type="button" class="btn-actualizar" onclick="abrirActualizarEstado(this)" data-i18n="biblio-actualizar">Actualizar estado</button>';
            echo '<form method="POST" action="../../../backend/actions/eliminar_libro.php" class="form-eliminar" data-confirm-es="¿Quieres eliminar este libro de tu biblioteca?" data-confirm-en="Do you want to remove this book from your library?" data-confirm-ok-es="Eliminar" data-confirm-ok-en="Remove" data-confirm-danger>';
            echo '<input type="hidden" name="libro_id" value="' . htmlspecialchars($idOpenLibrary, ENT_QUOTES, 'UTF-8') . '">';
            echo '<input type="hidden" name="csrf_token" value="' . htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8') . '">';
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
    <link rel="icon" type="image/png" href="../../assets/img/icono.png">
    <link rel="stylesheet" href="../../css/theme.css?v=<?= @filemtime(__DIR__ . '/../../css/theme.css') ?: time() ?>">
    <link rel="stylesheet" href="../../css/ui.css?v=<?= @filemtime(__DIR__ . '/../../css/ui.css') ?: time() ?>">
    <script src="../../js/theme.js?v=<?= @filemtime(__DIR__ . '/../../js/theme.js') ?: time() ?>"></script>
    <script src="../../js/notify.js?v=<?= @filemtime(__DIR__ . '/../../js/notify.js') ?: time() ?>"></script>
    <link rel="stylesheet" href="../../css/biblioteca.css?v=<?= $cssVersion ?>">
</head>
<body>
    <h1 data-i18n="inicio-h1">K-Libro</h1>
    <main class="container">
        <nav>
            <a href="../inicio/inicio.php" data-i18n="nav-inicio">Inicio</a>
            <a href="../biblioteca/biblioteca.php" data-i18n="nav-biblioteca">Biblioteca</a> 
            <a href="../mi_cuenta/mi_cuenta.php" data-i18n="nav-cuenta">Mi cuenta</a> 
            <a href="../buscador/buscador.php" data-i18n="nav-buscador">Buscador</a>
        <?php if (!empty($_SESSION['rol']) && in_array($_SESSION['rol'], ['admin', 'moderador'], true)): ?>
            | <a href="../admin/panel_admin.php" style="color: var(--link-admin); font-weight: bold;">Panel Admin</a>
        <?php endif; ?>
            <div class="barra-ajustes">
                <button id="btn-theme" class="btn-lang btn-theme" type="button">☀️ Claro</button>
                <button id="btn-lang" class="btn-lang">🌐 English</button>
            </div>
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
    <script src="../../js/i18n.js?v=<?= @filemtime(__DIR__ . '/../../js/i18n.js') ?: time() ?>"></script>
    <script>
    document.addEventListener('DOMContentLoaded', () => {
    I18n.init({
        es: {
            'nav-inicio':        'Inicio',
            'nav-biblioteca':  'Biblioteca',
            'nav-cuenta':        'Mi cuenta',
            'nav-buscador':      'Buscador',
            'biblio-h1':         'Biblioteca',
            'biblio-subtitulo':  'Tus libros guardados se organizan automáticamente por estado.',
            'biblio-nav-pendiente': 'Pendientes de leer',
            'biblio-nav-leyendo': 'Leyendo',
            'biblio-nav-leido': 'Leídos',
            'biblio-detalles': 'Detalles',
            'biblio-eliminar': 'Eliminar',
            'biblio-actualizar': 'Actualizar estado',
            'biblio-estado-titulo': 'Actualizar estado',
            'biblio-estado-nuevo': 'Nuevo estado',
            'biblio-estado-leyendo': 'Leyendo',
            'biblio-estado-leido': 'Leído',
            'biblio-estado-actual': 'Estado actual',
            'biblio-estado-guardar': 'Guardar',
            'biblio-estado-aviso': 'Al volver a «Leyendo» se eliminarán la fecha de lectura, la puntuación y la reseña de este libro.',
            'biblio-estado-error': 'No se ha podido actualizar el estado del libro.',
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
            'biblio-modal-editar': 'Editar',
            'biblio-modal-cancel': 'Cancelar',
        },
        en: {
            'inicio-h1':       'K-Libro',
            'nav-inicio':        'Home',
            'nav-biblioteca': 'Library',
            'nav-cuenta':        'My account',
            'nav-buscador':      'Search',
            'biblio-h1':         'My library',
            'biblio-subtitulo':  'Your saved books are automatically organised by status.',
            'biblio-nav-pendiente': 'To read',
            'biblio-nav-leyendo': 'Reading',
            'biblio-nav-leido': 'Read',
            'biblio-detalles': 'Details',
            'biblio-eliminar': 'Delete',
            'biblio-actualizar': 'Update status',
            'biblio-estado-titulo': 'Update status',
            'biblio-estado-nuevo': 'New status',
            'biblio-estado-leyendo': 'Reading',
            'biblio-estado-leido': 'Read',
            'biblio-estado-actual': 'Current status',
            'biblio-estado-guardar': 'Save',
            'biblio-estado-aviso': 'Going back to “Reading” will remove the read date, the rating and the review of this book.',
            'biblio-estado-error': 'The book status could not be updated.',
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
            'biblio-modal-editar': 'Edit',
            'biblio-modal-cancel': 'Cancel',
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
    const modalEstrellas = document.getElementById('modal-detalles-estrellas');
    const modalResena = document.getElementById('modal-detalles-resena');
    const modalFechaLectura = document.getElementById('modal-detalles-fecha-lectura');
    const modalFechaLecturaBloque = document.getElementById('modal-detalles-fecha-bloque');
    const modalInputId = document.getElementById('modal-detalles-id');
    const modalInputCalificacion = document.getElementById('modal-detalles-input-calificacion');
    const modalInputResena = document.getElementById('modal-detalles-input-resena');
    const modalInputFechaLectura = document.getElementById('modal-detalles-input-fecha-lectura');
    const modalInputFechaGrupo = document.getElementById('modal-detalles-input-fecha-grupo');
    const modalForm = document.getElementById('modal-detalles-form');
    let cardActual = null;

    // Puntuación: estrellas + texto (o "Sin puntuar")
    const pintarCalificacion = (valor) => {
        const n = Number.parseInt(valor, 10);
        const tiene = n >= 1 && n <= 5;
        if (modalEstrellas) {
            modalEstrellas.innerHTML = tiene ? '★'.repeat(n) + '<span class="vacia">' + '★'.repeat(5 - n) + '</span>' : '';
            modalEstrellas.hidden = !tiene;
        }
        modalCalificacion.textContent = tiene ? `${n}/5` : I18n.t('biblio-sin-calificacion');
        modalCalificacion.classList.toggle('vacia', !tiene);
    };

    // Reseña (o "Sin reseña" con aspecto atenuado)
    const pintarResena = (review) => {
        modalResena.textContent = review !== '' ? review : I18n.t('biblio-sin-resena');
        modalResena.classList.toggle('vacia', review === '');
    };

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
        cardActual = card;
        pintarCalificacion(calificacion);
        pintarResena(review);
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
            btnEditar.hidden = false;
            modalForm.hidden = true;
        }
    };

    const abrirEdicion = () => {
        const btnEditar = document.getElementById('modal-detalles-btn-editar');
        if (btnEditar && modalForm) {
            btnEditar.hidden = true;
            modalForm.hidden = false;
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

    // Cancelar: descarta los cambios y vuelve a mostrar los datos guardados
    const btnCancelarModal = document.getElementById('modal-detalles-btn-cancelar');
    if (btnCancelarModal) {
        btnCancelarModal.addEventListener('click', () => {
            if (cardActual) { abrirModalDetalles(cardActual); } else { cerrarEdicion(); }
        });
    }

    // Esc cierra el detalle (si no hay una ventana de confirmación encima)
    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && modal && !modal.hidden && !document.querySelector('.ui-modal-overlay')) {
            cerrarModalDetalles();
        }
    });

    if (modalForm) {
        modalForm.addEventListener('submit', async (event) => {
            event.preventDefault();

            const idLibro = modalInputId ? modalInputId.value.trim() : '';
            if (idLibro === '') return;

            const calificacion = modalInputCalificacion ? modalInputCalificacion.value.trim() : '';
            const review = modalInputResena ? modalInputResena.value.trim() : '';
            const fechaLectura = modalInputFechaLectura ? modalInputFechaLectura.value.trim() : '';

            if (modalInputFechaGrupo && !modalInputFechaGrupo.hidden && fechaLectura === '') {
                Notify.warning(I18n.t('biblio-modal-date-error'));
                return;
            }

            try {
                const datos = new URLSearchParams();
                datos.append('id_openlibrary', idLibro);
                datos.append('calificacion', calificacion);
                datos.append('review', review);
                datos.append('fecha_lectura', fechaLectura);

                const respuesta = await fetch('../../../backend/actions/actualizar_resena.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8',
                        'Accept': 'application/json',
                        'X-CSRF-Token': <?= json_encode(csrf_token()) ?>
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

                pintarCalificacion(calificacion);
                pintarResena(review);
                if (modalFechaLectura && modalInputFechaGrupo && !modalInputFechaGrupo.hidden) {
                    modalFechaLectura.textContent = fechaLectura !== '' ? fechaLectura.split('-').reverse().join('/') : I18n.t('biblio-modal-read-date-empty');
                }

                Notify.success(I18n.t('biblio-modal-guardado'));
                cerrarEdicion();
            } catch (error) {
                console.error('Error al guardar detalles del libro:', error);
                Notify.error(I18n.t('biblio-modal-error'));
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
            <button id="modal-detalles-cerrar" type="button" class="modal-detalles-cerrar">
                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M6 6l12 12M18 6L6 18"/></svg>
                <span class="sr-only" data-i18n="biblio-cerrar">Cerrar</span>
            </button>

            <div class="modal-detalles-grid">
                <img id="modal-detalles-portada" src="" alt="Portada del libro">
                <div class="modal-detalles-info">
                    <h2 id="modal-detalles-titulo"></h2>
                    <p id="modal-detalles-autor" class="modal-detalles-autor"></p>

                    <dl class="modal-detalles-datos">
                        <div>
                            <dt data-i18n="biblio-modal-rating">Puntuación</dt>
                            <dd>
                                <span id="modal-detalles-estrellas" class="estrellas" aria-hidden="true"></span>
                                <span id="modal-detalles-calificacion"></span>
                            </dd>
                        </div>
                        <div id="modal-detalles-fecha-bloque">
                            <dt data-i18n="biblio-modal-read-date">Fecha de lectura</dt>
                            <dd id="modal-detalles-fecha-lectura"></dd>
                        </div>
                    </dl>

                    <h3 class="modal-detalles-subtitulo" data-i18n="biblio-modal-review">Reseña</h3>
                    <p id="modal-detalles-resena" class="modal-detalles-resena"></p>

                    <button id="modal-detalles-btn-editar" type="button" class="btn-modal-editar">
                        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 20h9"/><path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4 12.5-12.5z"/></svg>
                        <span data-i18n="biblio-modal-editar">Editar</span>
                    </button>

                    <form id="modal-detalles-form" class="modal-detalles-form" hidden>
                        <input id="modal-detalles-id" type="hidden" name="id_openlibrary" value="">

                        <div class="campo-modal">
                            <label for="modal-detalles-input-calificacion" class="modal-detalles-label" data-i18n="biblio-modal-rating">Puntuación</label>
                            <select id="modal-detalles-input-calificacion" class="modal-detalles-input" name="calificacion">
                                <option value="" data-i18n="biblio-modal-rating-empty">Sin puntuar</option>
                                <option value="1">1/5</option>
                                <option value="2">2/5</option>
                                <option value="3">3/5</option>
                                <option value="4">4/5</option>
                                <option value="5">5/5</option>
                            </select>
                        </div>

                        <div id="modal-detalles-input-fecha-grupo" class="campo-modal">
                            <label for="modal-detalles-input-fecha-lectura" class="modal-detalles-label" data-i18n="biblio-modal-read-date">Fecha de lectura</label>
                            <input id="modal-detalles-input-fecha-lectura" type="date" class="modal-detalles-input" name="fecha_lectura" max="<?= date('Y-m-d') ?>">
                        </div>

                        <div class="campo-modal campo-modal-ancho">
                            <label for="modal-detalles-input-resena" class="modal-detalles-label" data-i18n="biblio-modal-review">Reseña</label>
                            <textarea id="modal-detalles-input-resena" class="modal-detalles-input" name="review" rows="4" maxlength="2000" data-i18n-ph="biblio-modal-review-ph" placeholder="Escribe una reseña o déjala vacía para eliminarla"></textarea>
                        </div>

                        <div class="modal-detalles-acciones">
                            <button type="button" id="modal-detalles-btn-cancelar" class="btn-modal-cancelar" data-i18n="biblio-modal-cancel">Cancelar</button>
                            <button type="submit" class="btn-modal-guardar" data-i18n="biblio-modal-save">Guardar cambios</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <!-- Ventana para actualizar el estado de un libro (Leyendo / Leído) -->
    <div id="modal-estado" class="ui-modal-overlay" hidden>
        <div class="ui-modal modal-estado" role="dialog" aria-modal="true" aria-labelledby="modal-estado-titulo">
            <h2 id="modal-estado-titulo" class="ui-modal-titulo" data-i18n="biblio-estado-titulo">Actualizar estado</h2>
            <p id="modal-estado-libro" class="modal-estado-libro"></p>

            <form id="modal-estado-form" novalidate>
                <span class="modal-detalles-label" data-i18n="biblio-estado-nuevo">Nuevo estado</span>
                <div class="estado-opciones" role="radiogroup">
                    <label class="estado-opcion">
                        <input type="radio" name="estado-nuevo" value="leyendo">
                        <span class="estado-etiqueta">
                            <span data-i18n="biblio-estado-leyendo">Leyendo</span>
                            <span class="estado-actual" data-i18n="biblio-estado-actual" hidden>Estado actual</span>
                        </span>
                    </label>
                    <label class="estado-opcion">
                        <input type="radio" name="estado-nuevo" value="leido">
                        <span class="estado-etiqueta">
                            <span data-i18n="biblio-estado-leido">Leído</span>
                            <span class="estado-actual" data-i18n="biblio-estado-actual" hidden>Estado actual</span>
                        </span>
                    </label>
                </div>

                <div id="modal-estado-fecha" class="modal-estado-fecha" hidden>
                    <label for="modal-estado-input-fecha" class="modal-detalles-label" data-i18n="biblio-modal-read-date">Fecha de lectura</label>
                    <input id="modal-estado-input-fecha" type="date" class="modal-detalles-input" max="<?= date('Y-m-d') ?>">
                </div>

                <p id="modal-estado-aviso" class="modal-estado-aviso" data-i18n="biblio-estado-aviso" hidden>Al volver a «Leyendo» se eliminarán la fecha de lectura, la puntuación y la reseña de este libro.</p>

                <div class="ui-modal-acciones">
                    <button type="button" id="modal-estado-cancelar" class="ui-btn ui-btn-secundario" data-i18n="biblio-modal-cancel">Cancelar</button>
                    <button type="submit" id="modal-estado-guardar" class="ui-btn ui-btn-primario" data-i18n="biblio-estado-guardar">Guardar</button>
                </div>
            </form>
        </div>
    </div>

    <script>
    // Actualizar estado de un libro: Leyendo <-> Leído (reutiliza guardar_libro.php, que también recalcula retos y logros)
    (function () {
        const AVISOS_KEY = 'klibro-avisos';
        const CSRF = <?= json_encode(csrf_token()) ?>;
        const ETIQUETA = {
            leyendo: { es: 'Leyendo', en: 'Reading' },
            leido: { es: 'Leídos', en: 'Read' }
        };
        const overlay = document.getElementById('modal-estado');
        const form = document.getElementById('modal-estado-form');
        const tituloEl = document.getElementById('modal-estado-libro');
        const fechaWrap = document.getElementById('modal-estado-fecha');
        const fechaInput = document.getElementById('modal-estado-input-fecha');
        const aviso = document.getElementById('modal-estado-aviso');
        const btnGuardar = document.getElementById('modal-estado-guardar');
        const radios = Array.from(form.querySelectorAll('input[name="estado-nuevo"]'));
        let cardActual = null;
        let estadoActual = '';
        let previo = null;

        // Fecha de hoy en la zona horaria del usuario (YYYY-MM-DD)
        const hoyLocal = () => {
            const d = new Date();
            return `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-${String(d.getDate()).padStart(2, '0')}`;
        };
        const seleccionado = () => {
            const r = radios.find((x) => x.checked);
            return r ? r.value : '';
        };

        const actualizarCampos = () => {
            const sel = seleccionado();
            fechaWrap.hidden = sel !== 'leido';
            aviso.hidden = !(estadoActual === 'leido' && sel === 'leyendo');
            btnGuardar.disabled = sel === '';
        };

        const cerrar = () => {
            overlay.hidden = true;
            document.body.classList.remove('ui-modal-abierto');
            if (previo && previo.focus) { previo.focus(); }
        };

        window.abrirActualizarEstado = (btn) => {
            const card = btn.closest('.book-card');
            if (!card) { return; }
            previo = btn;
            cardActual = card;
            estadoActual = card.dataset.libroEstado || '';
            tituloEl.textContent = card.dataset.libroTitulo || '';

            // Por defecto se propone el paso lógico: pendiente -> leyendo, leyendo -> leído, leído -> leyendo
            const propuesto = estadoActual === 'leyendo' ? 'leido' : 'leyendo';
            radios.forEach((r) => {
                r.disabled = r.value === estadoActual;
                r.checked = r.value === propuesto;
                const actual = r.closest('.estado-opcion').querySelector('.estado-actual');
                if (actual) { actual.hidden = r.value !== estadoActual; }
            });

            fechaInput.max = hoyLocal();
            fechaInput.value = hoyLocal();
            actualizarCampos();

            overlay.hidden = false;
            document.body.classList.add('ui-modal-abierto');
            const marcado = radios.find((r) => r.checked);
            if (marcado) { marcado.focus(); }
        };

        radios.forEach((r) => r.addEventListener('change', actualizarCampos));
        document.getElementById('modal-estado-cancelar').addEventListener('click', cerrar);
        overlay.addEventListener('mousedown', (e) => { if (e.target === overlay) { cerrar(); } });
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && !overlay.hidden) { e.preventDefault(); cerrar(); }
        });

        form.addEventListener('submit', async (event) => {
            event.preventDefault();
            const estado = seleccionado();
            if (!cardActual || estado === '') { return; }

            const fecha = estado === 'leido' ? fechaInput.value.trim() : '';
            if (estado === 'leido' && fecha === '') {
                Notify.warning(I18n.t('biblio-modal-date-error'));
                return;
            }

            const titulo = cardActual.dataset.libroTitulo || '';
            let portada = cardActual.dataset.libroPortada || '';
            if (portada.indexOf('via.placeholder.com') !== -1) { portada = ''; } // no guardar la imagen de relleno

            btnGuardar.disabled = true;
            try {
                const datos = new URLSearchParams();
                datos.append('id_openlibrary', cardActual.dataset.libroId || '');
                datos.append('titulo', titulo);
                datos.append('autor', cardActual.dataset.libroAutor || '');
                datos.append('portada', portada);
                datos.append('estado', estado);
                datos.append('fecha_lectura', fecha);

                const respuesta = await fetch('../../../backend/actions/guardar_libro.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8',
                        'X-CSRF-Token': CSRF
                    },
                    body: datos.toString()
                });
                let resultado = null;
                try { resultado = JSON.parse(await respuesta.text()); } catch (e) { resultado = null; }
                if (!respuesta.ok || !resultado || !resultado.ok) {
                    throw new Error((resultado && resultado.mensaje) || I18n.t('biblio-estado-error'));
                }

                // La página se recarga para que el libro aparezca en su nuevo apartado; el aviso se muestra después
                const avisos = [{
                    tipo: 'success',
                    mensaje: {
                        es: `«${titulo}» ahora está en «${ETIQUETA[estado].es}».`,
                        en: `“${titulo}” is now in “${ETIQUETA[estado].en}”.`
                    }
                }];
                if (resultado.reto_recien_completado) {
                    avisos.push({
                        tipo: 'success',
                        mensaje: { es: 'Has completado tu reto de lectura de este mes.', en: 'You have completed this month’s reading challenge.' }
                    });
                }
                try { sessionStorage.setItem(AVISOS_KEY, JSON.stringify(avisos)); } catch (e) { /* sin sessionStorage: no se muestra el aviso */ }
                window.location.reload();
            } catch (error) {
                console.error('Error al actualizar el estado:', error);
                Notify.error(error.message || I18n.t('biblio-estado-error'));
                btnGuardar.disabled = false;
            }
        });

        // Avisos pendientes de la acción anterior (tras recargar)
        document.addEventListener('DOMContentLoaded', () => {
            try {
                const pendientes = sessionStorage.getItem(AVISOS_KEY);
                if (pendientes) {
                    sessionStorage.removeItem(AVISOS_KEY);
                    JSON.parse(pendientes).forEach((a) => Notify.show(a.mensaje, { type: a.tipo }));
                }
            } catch (e) { /* ignorar */ }
        });
    })();
    </script>

    <?= flash_render() ?>
</body>
</html>