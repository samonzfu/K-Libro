<?php
// Mantenemos la protección de la sesión
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>K-Libro | Buscador Mágico</title>
    <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@400;700&family=Lato:wght@300;400&display=swap" rel="stylesheet">
    
    <style>
        /* Estilos básicos heredados de tu proyecto */
        body {
            font-family: 'Lato', sans-serif;
            background: linear-gradient(135deg, #0f0c29, #302b63);
            color: #e0e0e0;
            margin: 0; min-height: 100vh;
        }
        .container { max-width: 1000px; margin: 2rem auto; padding: 1rem; text-align: center; }
        h1 { font-family: 'Cinzel', serif; color: #d4af37; }
        
        /* Estilos del buscador */
        .search-box {
            margin-bottom: 2rem;
            display: flex;
            justify-content: center;
            gap: 10px;
        }
        .search-box input {
            padding: 10px 20px;
            font-size: 1.1rem;
            width: 60%;
            border-radius: 25px;
            border: 1px solid #d4af37;
            background: rgba(0,0,0,0.5);
            color: white;
        }
        .search-box button {
            padding: 10px 20px;
            background: #d4af37;
            color: #000;
            border: none;
            border-radius: 25px;
            font-weight: bold;
            cursor: pointer;
        }
        
        /* Grid de resultados */
        .results-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 20px;
        }
        .book-card {
            background: rgba(0,0,0,0.6);
            border: 1px solid rgba(212, 175, 55, 0.3);
            padding: 15px;
            border-radius: 10px;
        }
        .book-card img { width: 100%; height: 250px; object-fit: cover; border-radius: 5px; }
        .book-card h3 { font-size: 1.1rem; margin: 10px 0 5px; color: white; }
        .book-card p { font-size: 0.9rem; color: #aaa; margin-bottom: 15px;}
        
        /* Indicador de carga */
        #loader { display: none; color: #d4af37; font-family: 'Cinzel', serif; }
    </style>
</head>
<body>

    <div class="container">
        <h1>Buscar en los Archivos</h1>
        
        <div class="search-box">
            <input type="text" id="inputBusqueda" placeholder="Ej: El nombre del viento..." onkeypress="manejarEnter(event)">
            <button onclick="buscarLibros()">Buscar</button>
        </div>

        <div id="loader"><h2>Consultando los pergaminos... ⏳</h2></div>

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
                // 3. HACEMOS LA LLAMADA A LA API (Aquí ocurre la magia)
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
                contenedor.innerHTML = `<p style="color: red;">Error: Los pergaminos son ilegibles ahora mismo.</p>`;
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
                contenedor.innerHTML = '<p>No se encontraron tomos con ese nombre.</p>';
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

                // Creamos el HTML de la tarjeta (Usamos Template Literals con el símbolo ` `)
                const tarjetaHTML = `
                    <div class="book-card">
                        <img src="${urlPortada}" alt="Portada de ${libro.title}">
                        <h3>${libro.title}</h3>
                        <p>${autor}</p>
                        <button onclick="guardarLibro('${libro.key}', '${libro.title}', 'leyendo')" style="width:100%; padding:5px; margin-top:5px; background:transparent; border:1px solid #d4af37; color:#d4af37; cursor:pointer;">📖 Leer ahora</button>
                        <button onclick="guardarLibro('${libro.key}', '${libro.title}', 'pendiente')" style="width:100%; padding:5px; margin-top:5px; background:transparent; border:1px solid #aaa; color:#aaa; cursor:pointer;">⏳ Guardar para luego</button>
                    </div>
                `;

                // Añadimos la tarjeta al contenedor
                contenedor.innerHTML += tarjetaHTML;
            });
        }

        // Función "Placeholder" (La programaremos en el siguiente paso)
        function guardarLibro(idOpenLibrary, titulo, estado) {
            console.log(`Petición para guardar el libro ID: ${idOpenLibrary} (${titulo}) en estado: ${estado}`);
            alert(`Imagina que aquí hemos guardado "${titulo}" en tu base de datos. ¡Ese será el siguiente paso!`);
        }
    </script>

</body>
</html>