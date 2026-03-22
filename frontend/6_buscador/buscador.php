<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: /GitHub/K-Libro/frontend/2_Login/login.php');
    exit;
}

// Generamos la versión del CSS para evitar problemas de caché
$cssVersion = @filemtime(__DIR__ . '/css/estilo.css') ?: time();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>K-Libro | Buscador</title>
    <link rel="stylesheet" href="css/estilo.css?v=<?= $cssVersion ?>">
</head>
<body>

    <div class="container">
        
        <nav>
            <a href="../3_Inicio/inicio.php" data-i18n="nav-inicio">Inicio</a> |
            <a href="../4_Biblioteca/biblioteca.php" data-i18n="nav-biblioteca">Biblioteca</a> |
            <a href="../5_Mi_cuenta/mi_cuenta.php" data-i18n="nav-cuenta">Mi cuenta</a>
            <button id="btn-lang" class="btn-lang">🌐 English</button>
        </nav>

        <h1 data-i18n="busca-h1">Busca un libro</h1>
        
        <div class="search-box">
            <input type="text" id="inputBusqueda" data-i18n-ph="busca-ph" placeholder="Ej: El Nombre del Viento..." onkeypress="manejarEnter(event)">
            <button data-i18n="busca-btn" onclick="buscarLibros()">Buscar</button>
        </div>

        <div id="loader" style="display: none; text-align: center; margin-top: 2rem;"><h2 data-i18n="busca-cargando">Consultando los archivos... ⏳</h2></div>

        <div id="contenedorResultados" class="results-grid">
            </div>
    </div>

    <script src="../js/i18n.js"></script>
    <script>
    // Traducciones globales accesibles desde las funciones de búsqueda
    const T = {
        es: {
            'nav-inicio':      'Inicio',
            'nav-biblioteca':  'Biblioteca',
            'nav-cuenta':      'Mi cuenta',
            'busca-h1':        'Busca un libro',
            'busca-ph':        'Ej: El Nombre del Viento...',
            'busca-btn':       'Buscar',
            'busca-cargando':  'Consultando los archivos... ⏳',
            'card-pendiente':  'Quiero leer',
            'card-leyendo':    'Leyendo',
            'card-leido':      'Leído',
            'card-fecha-lectura': 'Fecha de lectura',
            'card-fecha-ayuda': 'Solo contará para el reto mensual si la fecha pertenece a este mes.',
            'card-selecciona': 'Selecciona una opción',
            'card-guardar':    '➕ Guardar en mi biblioteca',
            'busca-vacio':     'No se encontraron libros con ese nombre en los registros antiguos.',
            'busca-error-api': 'El grimorio de OpenLibrary no responde. Prueba otra vez.',
            'busca-error-guardar': 'No se pudo guardar el libro. Los astros no están alineados.',
            'busca-error-fecha': 'Selecciona una fecha de lectura válida para marcarlo como leído.',
            'busca-error-estado': 'Selecciona un estado antes de guardar el libro.',
            'busca-guardado':  'libro guardado como',
            'busca-reto-completado': 'Has completado el objetivo.',
            'autor-desconocido': 'Autor desconocido',
        },
        en: {
            'nav-inicio':      'Home',
            'nav-biblioteca':  'Library',
            'nav-cuenta':      'My account',
            'busca-h1':        'Search a book',
            'busca-ph':        'E.g.: The Name of the Wind...',
            'busca-btn':       'Search',
            'busca-cargando':  'Consulting the archives... ⏳',
            'card-pendiente':  'Want to read',
            'card-leyendo':    'Reading',
            'card-leido':      'Read',
            'card-fecha-lectura': 'Read date',
            'card-fecha-ayuda': 'It will only count for the monthly challenge if the date belongs to this month.',
            'card-selecciona': 'Select an option',
            'card-guardar':    '➕ Save to my library',
            'busca-vacio':     'No books found with that name in the ancient records.',
            'busca-error-api': 'The OpenLibrary grimoire is not responding. Try again.',
            'busca-error-guardar': 'Could not save the book. The stars are not aligned.',
            'busca-error-fecha': 'Select a valid read date before marking it as read.',
            'busca-error-estado': 'Select a status before saving the book.',
            'busca-guardado':  'book saved as',
            'busca-reto-completado': 'You have completed the goal.',
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

        // Función para dibujar el HTML de cada libro
        function renderizarLibros(libros) {
            const contenedor = document.getElementById('contenedorResultados');
            const fechaHoy = new Date().toISOString().split('T')[0];

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
                    ? `https://covers.openlibrary.org/b/id/${libro.cover_i}-M.jpg` 
                    : '../../assets/img/default_book.png'; // <-- Ruta ajustada al estilo de tu proyecto

                const idSeguro = encodeURIComponent(libro.key || '');
                const tituloSeguro = libro.title || 'Sin título';
                const autorSeguro = autor;
                const portadaSegura = urlPortada;

                // Tarjeta HTML adaptada a las clases de tu CSS (Magia Oscura)
                htmlFinal += `
                    <div class="book-card">
                        <img src="${urlPortada}" alt="Portada de ${libro.title}" onerror="this.src='../../assets/img/default_book.png'">
                        <h3>${libro.title}</h3>
                        <p>${autor}</p>
                        
                        <select id="estado-${idSeguro}" class="select-estado">
                            <option value="" selected disabled>${t('card-selecciona')}</option>
                            <option value="pendiente">${t('card-pendiente')}</option>
                            <option value="leyendo">${t('card-leyendo')}</option>
                            <option value="leido">${t('card-leido')}</option>
                        </select>

                        <div id="fecha-lectura-wrap-${idSeguro}" class="campos-leido" hidden>
                            <label for="fecha-lectura-${idSeguro}" class="campo-label">${t('card-fecha-lectura')}</label>
                            <input type="date" id="fecha-lectura-${idSeguro}" class="input-fecha-lectura" max="${fechaHoy}" value="${fechaHoy}">
                            <p class="campo-ayuda">${t('card-fecha-ayuda')}</p>
                        </div>
                        
                        <button
                            class="btn-guardar"
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

            contenedor.querySelectorAll('.select-estado').forEach((select) => {
                select.addEventListener('change', () => {
                    const idSeguro = select.id.replace('estado-', '');
                    const campoFecha = document.getElementById(`fecha-lectura-wrap-${idSeguro}`);
                    const inputFecha = document.getElementById(`fecha-lectura-${idSeguro}`);

                    if (!campoFecha) return;

                    const mostrarFecha = select.value === 'leido';
                    campoFecha.hidden = !mostrarFecha;

                    if (!mostrarFecha && inputFecha) {
                        inputFecha.value = inputFecha.max || '';
                    }
                });
            });

            contenedor.querySelectorAll('.btn-guardar').forEach((button) => {
                button.addEventListener('click', () => {
                    guardarLibro(
                        button.dataset.idOpenlibrary || '',
                        button.dataset.titulo || '',
                        button.dataset.autor || '',
                        button.dataset.portada || ''
                    );
                });
            });
        }

        async function guardarLibro(idOpenLibraryCod, tituloCod, autorCod, portadaCod) {
            const idOpenLibrary = decodeURIComponent(idOpenLibraryCod);
            const titulo = decodeURIComponent(tituloCod);
            const autor = decodeURIComponent(autorCod);
            const portada = decodeURIComponent(portadaCod);

            const selectEstado = document.getElementById(`estado-${idOpenLibraryCod}`);
            const estado = (selectEstado && selectEstado.value) ? selectEstado.value : '';
            const inputFechaLectura = document.getElementById(`fecha-lectura-${idOpenLibraryCod}`);
            const fechaLectura = inputFechaLectura ? inputFechaLectura.value.trim() : '';

            if (estado === '') {
                alert(t('busca-error-estado'));
                return;
            }

            if (estado === 'leido' && fechaLectura === '') {
                alert(t('busca-error-fecha'));
                return;
            }

            try {
                const datos = new URLSearchParams();
                datos.append('id_openlibrary', idOpenLibrary);
                datos.append('titulo', titulo);
                datos.append('autor', autor);
                datos.append('portada', portada);
                datos.append('estado', estado);
                datos.append('fecha_lectura', fechaLectura);

                const respuesta = await fetch('/GitHub/K-Libro/backend/procesar/guardar_libro.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8'
                    },
                    body: datos.toString()
                });

                const textoRespuesta = await respuesta.text();
                let resultado;

                try {
                    resultado = JSON.parse(textoRespuesta);
                } catch (errorParseo) {
                    throw new Error(textoRespuesta || t('busca-error-guardar'));
                }

                if (!respuesta.ok || !resultado.ok) {
                    throw new Error(resultado.mensaje || 'No se pudo guardar el libro');
                }

                let mensaje = `¡Magia realizada! "${titulo}" ${t('busca-guardado')} "${estado}".`;

                if (resultado.reto_recien_completado) {
                    mensaje += ` ${resultado.reto_mensaje || t('busca-reto-completado')}`;
                }

                alert(mensaje);
            } catch (error) {
                console.error('Error al guardar libro:', error);
                alert(error.message || t('busca-error-guardar'));
            }
        }
    </script>

</body>
</html>