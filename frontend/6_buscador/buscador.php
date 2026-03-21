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
            'card-selecciona': 'Selecciona una opción',
            'card-rating':     'Puntuación',
            'card-rating-ph':  'Sin puntuar',
            'card-review':     'Reseña personal',
            'card-review-ph':  '¿Qué te pareció este libro?',
            'card-guardar':    '➕ Guardar en mi biblioteca',
            'busca-vacio':     'No se encontraron libros con ese nombre en los registros antiguos.',
            'busca-error-api': 'El grimorio de OpenLibrary no responde. Prueba otra vez.',
            'busca-error-guardar': 'No se pudo guardar el libro. Los astros no están alineados.',
            'busca-guardado':  'libro guardado como',
            'busca-con-rating': 'con puntuación',
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
            'card-selecciona': 'Select an option',
            'card-rating':     'Rating',
            'card-rating-ph':  'No rating',
            'card-review':     'Personal review',
            'card-review-ph':  'What did you think about this book?',
            'card-guardar':    '➕ Save to my library',
            'busca-vacio':     'No books found with that name in the ancient records.',
            'busca-error-api': 'The OpenLibrary grimoire is not responding. Try again.',
            'busca-error-guardar': 'Could not save the book. The stars are not aligned.',
            'busca-guardado':  'book saved as',
            'busca-con-rating': 'with rating',
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
                const tituloSeguro = encodeURIComponent(libro.title || 'Sin título');
                const autorSeguro = encodeURIComponent(autor);
                const portadaSegura = encodeURIComponent(urlPortada);

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

                        <div id="extra-leido-${idSeguro}" class="campos-leido" style="display: none;">
                            <label for="calificacion-${idSeguro}" class="campo-label">${t('card-rating')}</label>
                            <select id="calificacion-${idSeguro}" class="select-estado select-calificacion">
                                <option value="">${t('card-rating-ph')}</option>
                                <option value="1">1/5</option>
                                <option value="2">2/5</option>
                                <option value="3">3/5</option>
                                <option value="4">4/5</option>
                                <option value="5">5/5</option>
                            </select>

                            <label for="review-${idSeguro}" class="campo-label">${t('card-review')}</label>
                            <textarea id="review-${idSeguro}" class="input-review" rows="3" maxlength="2000" placeholder="${t('card-review-ph')}"></textarea>
                        </div>
                        
                        <button class="btn-guardar" onclick="guardarLibro('${idSeguro}', '${tituloSeguro}', '${autorSeguro}', '${portadaSegura}')">
                            ${t('card-guardar')}
                        </button>
                    </div>
                `;
            });

            contenedor.innerHTML = htmlFinal;

            primerosResultados.forEach(libro => {
                const idSeguro = encodeURIComponent(libro.key || '');
                const selectEstado = document.getElementById(`estado-${idSeguro}`);
                if (selectEstado) {
                    selectEstado.addEventListener('change', () => toggleCamposLeido(idSeguro));
                }
            });
        }

        function toggleCamposLeido(idSeguro) {
            const selectEstado = document.getElementById(`estado-${idSeguro}`);
            const extraLeido = document.getElementById(`extra-leido-${idSeguro}`);
            if (!selectEstado || !extraLeido) return;

            if (selectEstado.value === 'leido') {
                extraLeido.style.display = 'block';
            } else {
                extraLeido.style.display = 'none';
            }
        }

        async function guardarLibro(idOpenLibraryCod, tituloCod, autorCod, portadaCod) {
            const idOpenLibrary = decodeURIComponent(idOpenLibraryCod);
            const titulo = decodeURIComponent(tituloCod);
            const autor = decodeURIComponent(autorCod);
            const portada = decodeURIComponent(portadaCod);

            const selectEstado = document.getElementById(`estado-${idOpenLibraryCod}`);
            const estado = (selectEstado && selectEstado.value) ? selectEstado.value : 'pendiente';

            const selectCalificacion = document.getElementById(`calificacion-${idOpenLibraryCod}`);
            const textareaReview = document.getElementById(`review-${idOpenLibraryCod}`);

            const calificacion = estado === 'leido' && selectCalificacion ? selectCalificacion.value.trim() : '';
            const review = estado === 'leido' && textareaReview ? textareaReview.value.trim() : '';

            try {
                const datos = new URLSearchParams();
                datos.append('id_openlibrary', idOpenLibrary);
                datos.append('titulo', titulo);
                datos.append('autor', autor);
                datos.append('portada', portada);
                datos.append('estado', estado);
                datos.append('calificacion', calificacion);
                datos.append('review', review);

                const respuesta = await fetch('/GitHub/K-Libro/backend/procesar/guardar_libro.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8'
                    },
                    body: datos.toString()
                });

                const resultado = await respuesta.json();

                if (!respuesta.ok || !resultado.ok) {
                    throw new Error(resultado.mensaje || 'No se pudo guardar el libro');
                }

                const mensajeCalificacion = calificacion !== '' ? ` ${t('busca-con-rating')} ${calificacion}/5.` : '.';
                alert(`¡Magia realizada! "${titulo}" ${t('busca-guardado')} "${estado}"${mensajeCalificacion}`);
            } catch (error) {
                console.error('Error al guardar libro:', error);
                alert(t('busca-error-guardar'));
            }
        }
    </script>

</body>
</html>