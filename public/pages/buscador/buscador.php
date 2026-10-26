<?php
session_start();
require_once __DIR__ . '/../../../backend/helpers/flash.php';
if (!isset($_SESSION['user_id'])) {
    header('Location: ../login/login.php');
    exit;
}

require_once __DIR__ . '/../../../backend/helpers/csrf.php';

// Generamos la versión del CSS para evitar problemas de caché
$cssVersion = @filemtime(__DIR__ . '/../../css/buscador.css') ?: time();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>K-Libro | Buscador</title>
    <link rel="icon" type="image/png" href="../../assets/img/icono.png">
    <link rel="stylesheet" href="../../css/theme.css?v=<?= @filemtime(__DIR__ . '/../../css/theme.css') ?: time() ?>">
    <link rel="stylesheet" href="../../css/ui.css?v=<?= @filemtime(__DIR__ . '/../../css/ui.css') ?: time() ?>">
    <script src="../../js/theme.js?v=<?= @filemtime(__DIR__ . '/../../js/theme.js') ?: time() ?>"></script>
    <script src="../../js/notify.js?v=<?= @filemtime(__DIR__ . '/../../js/notify.js') ?: time() ?>"></script>
    <link rel="stylesheet" href="../../css/buscador.css?v=<?= $cssVersion ?>">
</head>
<body>
    <h1 data-i18n="inicio-h1">K-Libro</h1>
    <div class="container">
        
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

        <h1 data-i18n="busca-h1">Busca un libro</h1>
        
        <div class="search-box">
            <input type="text" id="inputBusqueda" data-i18n-ph="busca-ph" placeholder="Ej: El Nombre del Viento..." onkeypress="manejarEnter(event)">
            <button data-i18n="busca-btn" onclick="buscarLibros()">Buscar</button>
        </div>

        <div id="loader" style="display: none; text-align: center; margin-top: 2rem;"><h2 data-i18n="busca-cargando">Buscando…</h2></div>

        <div id="contenedorResultados" class="results-grid">
            </div>
    </div>

    <script src="../../js/i18n.js?v=<?= @filemtime(__DIR__ . '/../../js/i18n.js') ?: time() ?>"></script>
    <script>
    // Traducciones globales accesibles desde las funciones de búsqueda
    const T = {
        es: {
            'inicio-h1':       'K-Libro',
            'nav-inicio':        'Inicio',
            'nav-biblioteca':  'Biblioteca',
            'nav-cuenta':      'Mi cuenta',
            'nav-buscador':    'Buscador',
            'busca-h1':        'Busca un libro',
            'busca-ph':        'Ej: El Nombre del Viento...',
            'busca-btn':       'Buscar',
            'busca-cargando':  'Buscando…',
            'card-pendiente':  'Quiero leer',
            'card-leyendo':    'Leyendo',
            'card-leido':      'Leído',
            'card-fecha-lectura': 'Fecha de lectura',
            'card-fecha-ayuda': 'Solo contará para el reto mensual si la fecha pertenece a este mes.',
            'card-estado':     'Estado de lectura',
            'card-selecciona': 'Selecciona una opción',
            'card-guardar':    'Guardar en mi biblioteca',
            'busca-vacio':     'No se han encontrado libros con ese nombre.',
            'busca-error-api': 'No se ha podido conectar con OpenLibrary. Inténtalo de nuevo en unos minutos.',
            'busca-error-guardar': 'No se ha podido guardar el libro. Inténtalo de nuevo.',
            'busca-error-fecha': 'Indica una fecha de lectura válida para marcarlo como leído.',
            'busca-error-estado': 'Selecciona un estado antes de guardar el libro.',
            'busca-guardado':  'Se ha añadido «{titulo}» a tu biblioteca como «{estado}».',
            'busca-reto-completado': 'Has completado tu reto de lectura de este mes.',
            'autor-desconocido': 'Autor desconocido',
        },
        en: {
            'inicio-h1':       'K-Libro',
            'nav-inicio':      'Home',
            'nav-biblioteca':  'Library',
            'nav-cuenta':      'My account',
            'nav-buscador':   'Search',
            'busca-h1':        'Search a book',
            'busca-ph':        'E.g.: The Name of the Wind...',
            'busca-btn':       'Search',
            'busca-cargando':  'Searching…',
            'card-pendiente':  'Want to read',
            'card-leyendo':    'Reading',
            'card-leido':      'Read',
            'card-fecha-lectura': 'Read date',
            'card-fecha-ayuda': 'It will only count for the monthly challenge if the date belongs to this month.',
            'card-estado':     'Reading status',
            'card-selecciona': 'Select an option',
            'card-guardar':    'Save to my library',
            'busca-vacio':     'No books were found with that name.',
            'busca-error-api': 'Could not connect to OpenLibrary. Please try again in a few minutes.',
            'busca-error-guardar': 'The book could not be saved. Please try again.',
            'busca-error-fecha': 'Enter a valid read date to mark the book as read.',
            'busca-error-estado': 'Select a status before saving the book.',
            'busca-guardado':  '“{titulo}” has been added to your library as “{estado}”.',
            'busca-reto-completado': 'You have completed this month’s reading challenge.',
            'autor-desconocido': 'Unknown author',
        }
    };

    // Atajo para obtener texto en el idioma activo
    const t = (key) => I18n.t(key, T);

    I18n.init(T, 'K-Libro | Buscador', 'K-Libro | Search');

        // Permite buscar al pulsar la tecla "Enter"
        function manejarEnter(event) {
            if (event.key === "Enter") buscarLibros();
        }

        // Función asíncrona principal
        async function buscarLibros() {
            const input = document.getElementById('inputBusqueda');
            const termino = input.value.trim(); 
            
            if (termino === '') return; 

            const loader = document.getElementById('loader');
            const contenedor = document.getElementById('contenedorResultados');

            // 1. Preparamos la interfaz
            loader.style.display = 'block';
            contenedor.innerHTML = '';

            // 2. Construimos la URL de Open Library
            const url = `https://openlibrary.org/search.json?q=${encodeURIComponent(termino)}&language=spa`;

            try {
                // 3. HACEMOS LA LLAMADA A LA API 
                const respuesta = await fetch(url);
                
                if (!respuesta.ok) throw new Error("Fallo en la conexión con la biblioteca");

                // 4. Transformamos la respuesta en JSON
                const datos = await respuesta.json();
                
                // 5. Enviamos la lista de libros a renderizar
                renderizarLibros(datos.docs);

            } catch (error) {
                console.error("Error capturado:", error);
                contenedor.innerHTML = `<p class="error">${t('busca-error-api')}</p>`;
            } finally {
                loader.style.display = 'none';
            }
        }

        // Escapa texto antes de meterlo en innerHTML (los datos de OpenLibrary son editables por cualquiera)
        function escapeHtml(texto) {
            return String(texto).replace(/[&<>"']/g, (c) => ({
                '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;'
            }[c]));
        }

        // Fecha de hoy en la zona horaria del usuario (YYYY-MM-DD).
        // toISOString() usa UTC y entre las 00:00 y las 02:00 devolvía el día anterior.
        function fechaLocalHoy() {
            const d = new Date();
            const mes = String(d.getMonth() + 1).padStart(2, '0');
            const dia = String(d.getDate()).padStart(2, '0');
            return `${d.getFullYear()}-${mes}-${dia}`;
        }

        // Función para dibujar el HTML de cada libro
        function renderizarLibros(libros) {
            const contenedor = document.getElementById('contenedorResultados');
            const fechaHoy = fechaLocalHoy();

            // Nos quedamos solo con los 12 primeros
            const primerosResultados = libros.slice(0, 12);

            if (primerosResultados.length === 0) {
                contenedor.innerHTML = `<p class="vacio">${t('busca-vacio')}</p>`;
                return;
            }

            // Variable para almacenar todo el HTML antes de inyectarlo de golpe (mejor rendimiento)
            let htmlFinal = '';

            primerosResultados.forEach(libro => {
                
                const autor = libro.author_name ? libro.author_name[0] : t('autor-desconocido');
                
                const urlPortada = libro.cover_i 
                    ? `https://covers.openlibrary.org/b/id/${encodeURIComponent(libro.cover_i)}-M.jpg` 
                    : '../../assets/img/default_book.png'; // <-- Ruta ajustada al estilo de tu proyecto

                const idSeguro = encodeURIComponent(libro.key || '');
                const tituloSeguro = libro.title || 'Sin título';
                const autorSeguro = autor;
                const portadaSegura = urlPortada;
                const tituloHtml = escapeHtml(tituloSeguro);
                const autorHtml = escapeHtml(autorSeguro);

                // Tarjeta HTML adaptada a las clases de tu CSS (Magia Oscura)
                htmlFinal += `
                    <div class="book-card">
                        <img src="${urlPortada}" alt="Portada de ${tituloHtml}" onerror="this.src='../../assets/img/default_book.png'">
                        <h3>${tituloHtml}</h3>
                        <p>${autorHtml}</p>
                        
                        <div class="estado-bloque">
                            <span class="campo-label">${t('card-estado')}</span>
                            <div class="estado-opciones" role="radiogroup" aria-label="${t('card-selecciona')}">
                                <label class="estado-opcion">
                                    <input type="radio" name="estado-${idSeguro}" value="pendiente">
                                    <span class="estado-etiqueta">${t('card-pendiente')}</span>
                                </label>
                                <label class="estado-opcion">
                                    <input type="radio" name="estado-${idSeguro}" value="leyendo">
                                    <span class="estado-etiqueta">${t('card-leyendo')}</span>
                                </label>
                                <label class="estado-opcion">
                                    <input type="radio" name="estado-${idSeguro}" value="leido">
                                    <span class="estado-etiqueta">${t('card-leido')}</span>
                                </label>
                            </div>
                        </div>

                        <div id="fecha-lectura-wrap-${idSeguro}" class="campos-leido" hidden>
                            <label for="fecha-lectura-${idSeguro}" class="campo-label">${t('card-fecha-lectura')}</label>
                            <input type="date" id="fecha-lectura-${idSeguro}" class="input-fecha-lectura" max="${fechaHoy}" value="${fechaHoy}">
                            <p class="campo-ayuda">${t('card-fecha-ayuda')}</p>
                        </div>
                        
                        <button
                            class="btn-guardar"
                            disabled
                            data-id-openlibrary="${idSeguro}"
                            data-titulo="${encodeURIComponent(tituloSeguro)}"
                            data-autor="${encodeURIComponent(autorSeguro)}"
                            data-portada="${encodeURIComponent(portadaSegura)}"
                        >
                            ${t('card-guardar')}
                        </button>
                    </div>
                `;
            });

            contenedor.innerHTML = htmlFinal;

            // Al elegir un estado: se activa el botón de guardar y, si es "leído", aparece la fecha
            contenedor.querySelectorAll('.estado-opciones').forEach((grupo) => {
                grupo.addEventListener('change', () => {
                    const card = grupo.closest('.book-card');
                    const seleccion = grupo.querySelector('input:checked');
                    const campoFecha = card.querySelector('.campos-leido');
                    const inputFecha = card.querySelector('.input-fecha-lectura');
                    const boton = card.querySelector('.btn-guardar');

                    const mostrarFecha = !!seleccion && seleccion.value === 'leido';
                    if (campoFecha) campoFecha.hidden = !mostrarFecha;
                    if (!mostrarFecha && inputFecha) {
                        inputFecha.value = inputFecha.max || '';
                    }
                    if (boton) boton.disabled = !seleccion;
                });
            });

            contenedor.querySelectorAll('.btn-guardar').forEach((button) => {
                button.addEventListener('click', () => {
                    guardarLibro(
                        button.closest('.book-card'),
                        button.dataset.idOpenlibrary || '',
                        button.dataset.titulo || '',
                        button.dataset.autor || '',
                        button.dataset.portada || ''
                    );
                });
            });
        }

        async function guardarLibro(card, idOpenLibraryCod, tituloCod, autorCod, portadaCod) {
            const idOpenLibrary = decodeURIComponent(idOpenLibraryCod);
            const titulo = decodeURIComponent(tituloCod);
            const autor = decodeURIComponent(autorCod);
            const portada = decodeURIComponent(portadaCod);

            const seleccion = card.querySelector('.estado-opciones input:checked');
            const estado = seleccion ? seleccion.value : '';
            const inputFechaLectura = card.querySelector('.input-fecha-lectura');
            const fechaLectura = inputFechaLectura ? inputFechaLectura.value.trim() : '';
            const boton = card.querySelector('.btn-guardar');

            if (estado === '') {
                Notify.warning(t('busca-error-estado'));
                return;
            }

            if (estado === 'leido' && fechaLectura === '') {
                Notify.warning(t('busca-error-fecha'));
                return;
            }

            if (boton) boton.disabled = true;

            try {
                const datos = new URLSearchParams();
                datos.append('id_openlibrary', idOpenLibrary);
                datos.append('titulo', titulo);
                datos.append('autor', autor);
                datos.append('portada', portada);
                datos.append('estado', estado);
                datos.append('fecha_lectura', fechaLectura);

                const respuesta = await fetch('../../../backend/actions/guardar_libro.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8',
                        'X-CSRF-Token': <?= json_encode(csrf_token()) ?>
                    },
                    body: datos.toString()
                });

                const textoRespuesta = await respuesta.text();
                let resultado;

                try {
                    resultado = JSON.parse(textoRespuesta);
                } catch (errorParseo) {
                    throw new Error(t('busca-error-guardar'));
                }

                if (!respuesta.ok || !resultado.ok) {
                    throw new Error(resultado.mensaje || t('busca-error-guardar'));
                }

                const mensaje = t('busca-guardado')
                    .replace('{titulo}', titulo)
                    .replace('{estado}', t('card-' + estado));
                Notify.success(mensaje);

                if (resultado.reto_recien_completado) {
                    Notify.success(t('busca-reto-completado'));
                }
            } catch (error) {
                console.error('Error al guardar libro:', error);
                Notify.error(error.message || t('busca-error-guardar'));
            } finally {
                if (boton) boton.disabled = !card.querySelector('.estado-opciones input:checked');
            }
        }
    </script>

    <?= flash_render() ?>
</body>
</html>