<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: /GitHub/K-Libro/frontend/2_Login/login.php');
    exit;
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>K-Libro | Buscador</title>
    <link rel="stylesheet" href="css/estilo.css">
</head>
<body>

    <div class="container">
        <h1>Busca un libro</h1>
        
        <div class="search-box">
            <input type="text" id="inputBusqueda" placeholder="Ej: Reino de Sombras..." onkeypress="manejarEnter(event)">
            <button onclick="buscarLibros()">Buscar</button>
        </div>

        <div id="loader"><h2>Consultando los archivos... ⏳</h2></div>

        <div id="contenedorResultados" class="results-grid">
            </div>
    </div>

    <script>
        // Permite buscar al pulsar la tecla "Enter"
        function manejarEnter(event) {
            if (event.key === "Enter") buscarLibros();
        }

        // Función asíncrona principal
        async function buscarLibros() {
            const input = document.getElementById('inputBusqueda');
            const termino = input.value.trim(); // .trim() quita espacios en blanco a los lados
            
            if (termino === '') return; // Si está vacío, no hacemos nada

            const loader = document.getElementById('loader');
            const contenedor = document.getElementById('contenedorResultados');

            // 1. Preparamos la interfaz (Mostramos "cargando" y limpiamos resultados viejos)
            loader.style.display = 'block';
            contenedor.innerHTML = '';

            // 2. Construimos la URL de Open Library (Nota: forzamos el idioma español)
            const url = `https://openlibrary.org/search.json?q=${encodeURIComponent(termino)}&language=spa`;

            try {
                // 3. HACEMOS LA LLAMADA A LA API 
                const respuesta = await fetch(url);
                
                // Si el servidor falla (ej: error 500), lanzamos un error que captura el bloque 'catch'
                if (!respuesta.ok) throw new Error("Fallo en la conexión con la biblioteca");

                // 4. Transformamos la respuesta en un objeto JavaScript (JSON)
                const datos = await respuesta.json();
                
                // 5. Enviamos la lista de libros (datos.docs) a la función que los dibuja
                renderizarLibros(datos.docs);

            } catch (error) {
                // Si algo sale mal (ej: no hay internet), entra aquí
                console.error("Error capturado:", error);
                contenedor.innerHTML = `<p style="color: red;">Error: Ha habido un error, prueba otra vez.</p>`;
            } finally {
                // Esto se ejecuta SIEMPRE, haya ido bien o mal. Ideal para ocultar el "Cargando..."
                loader.style.display = 'none';
            }
        }

        // Función para dibujar el HTML de cada libro
        function renderizarLibros(libros) {
            const contenedor = document.getElementById('contenedorResultados');

            // Open Library a veces devuelve 100 resultados, nos quedamos solo con los 12 primeros para no saturar
            const primerosResultados = libros.slice(0, 12);

            if (primerosResultados.length === 0) {
                contenedor.innerHTML = '<p>No se encontraron libros con ese nombre.</p>';
                return;
            }

            // Recorremos cada libro con un bucle
            primerosResultados.forEach(libro => {
                
                // PROGRAMACIÓN DEFENSIVA: OpenLibrary a veces no tiene autor o portada.
                // Si author_name existe, cogemos el primero [0]. Si no, texto por defecto.
                const autor = libro.author_name ? libro.author_name[0] : 'Autor desconocido';
                
                // Construimos la URL de la portada. Si no hay 'cover_i', ponemos una por defecto.
                const urlPortada = libro.cover_i 
                    ? `https://covers.openlibrary.org/b/id/${libro.cover_i}-M.jpg` 
                    : 'assets/img/default_book.png'; // <-- ¡Asegúrate de tener esta imagen en tu carpeta!

                const idSeguro = encodeURIComponent(libro.key || '');
                const tituloSeguro = encodeURIComponent(libro.title || 'Sin título');
                const autorSeguro = encodeURIComponent(autor);
                const portadaSegura = encodeURIComponent(urlPortada);

                // Creamos el HTML de la tarjeta (Usamos Template Literals con el símbolo ` `)
                const tarjetaHTML = `
                    <div class="book-card">
                        <img src="${urlPortada}" alt="Portada de ${libro.title}">
                        <h3>${libro.title}</h3>
                        <p>${autor}</p>
                        <select id="estado-${idSeguro}" style="width:100%; padding:8px; margin-top:8px; background:transparent; border:1px solid #d4af37; color:#f2ebeb; cursor:pointer; border-radius:20px;">
                            <option value="pendiente">Pendiente de leer</option>
                            <option value="leyendo">Leyendo</option>
                            <option value="leido">Leído</option>
                        </select>
                        <button onclick="guardarLibro('${idSeguro}', '${tituloSeguro}', '${autorSeguro}', '${portadaSegura}')" style="width:100%; padding:5px; margin-top:10px; background:transparent; border:1px solid #d4af37; color:#d4af37; cursor:pointer;">➕ Guardar en mi biblioteca</button>
                    </div>
                `;

                // Añadimos la tarjeta al contenedor
                contenedor.innerHTML += tarjetaHTML;
            });
        }

        async function guardarLibro(idOpenLibraryCod, tituloCod, autorCod, portadaCod) {
            const idOpenLibrary = decodeURIComponent(idOpenLibraryCod);
            const titulo = decodeURIComponent(tituloCod);
            const autor = decodeURIComponent(autorCod);
            const portada = decodeURIComponent(portadaCod);

            const selectEstado = document.getElementById(`estado-${idOpenLibraryCod}`);
            const estado = selectEstado ? selectEstado.value : 'pendiente';

            try {
                const datos = new URLSearchParams();
                datos.append('id_openlibrary', idOpenLibrary);
                datos.append('titulo', titulo);
                datos.append('autor', autor);
                datos.append('portada', portada);
                datos.append('estado', estado);

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

                alert(`"${titulo}" guardado como "${estado}".`);
            } catch (error) {
                console.error('Error al guardar libro:', error);
                alert('No se pudo guardar el libro. Inténtalo de nuevo.');
            }
        }
    </script>

</body>
</html>