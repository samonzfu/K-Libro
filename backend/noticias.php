<?php
/**
 * ==================== NOTICIAS Y RECOMENDACIONES ====================
 * 
 * Este archivo contiene RECOMENDACIONES DE RECURSOS ÚTILES para los lectores
 * que aparecen en la página de INICIO del proyecto
 * 
 * Las noticias tienen VERSIONES en ESPAÑOL e INGLÉS porque el proyecto
 * es MULTIIDIOMA. El JavaScript (i18n.js) se encarga de cambiar de idioma.
 */

/**
 * obtenerNoticias()
 * 
 * Función que devuelve un ARRAY con 3 RECOMENDACIONES para lectores
 * 
 * Cada recomendación incluye:
 * - titulo: Título en ESPAÑOL
 * - titulo_en: Título en INGLÉS
 * - descripcion: Descripción en ESPAÑOL
 * - descripcion_en: Descripción en INGLÉS
 * - enlace: Dirección web de la recomendación
 * 
 * RECOMENDACIONES:
 * 1. Literature-Map: Para descubrir autores similares
 * 2. YouTube: Canal con reseñas de libros
 * 3. OpenLibrary: Biblioteca digital gratuita
 */
function obtenerNoticias(): array
{
    return [
        // ========== RECOMENDACIÓN 1 ==========
        // Descubrir autores similares visualmente
        [
            'titulo' => '¿No sabes qué leer a continuación?',
            'titulo_en' => 'Not sure what to read next?',

            // Descripción en ESPAÑOL
            'descripcion' => 'Esta web te recomienda autores/as que podrían interesarte según el autor/a que has buscado. Cuanto más cerca estén los/as autores/as del que has buscado, más se asemejan. 
                            Esta web es una forma muy interactiva de descubrir nuevos escritores/as',
            // Descripción en INGLÉS
            'descripcion_en' => 'This website recommends authors who might interest you based on the author you searched for. The closer the authors are to the one you searched for, the more similar they are.
                            This website is a highly interactive way to discover new writers',

            // Enlace externo a Literature-Map
            'enlace' => 'https://www.literature-map.com/',
        ],

        // ========== RECOMENDACIÓN 2 ==========
        // Contenido en video sobre libros y géneros
        [
            'titulo' => '¿Buscas consumir contenido sobre libros?',
            'titulo_en' => 'Looking for book-related content?',

            // Descripción en ESPAÑOL
            'descripcion' => 'Aquí te dejo un vídeo del canal de youtube "Plan Based Bride" donde hace una selección de sus sagas de fantasía favoritas. Te recomiendo este canal
                        encarecidamente si eres amante de la fantasía y no sabes qué leer a continuación.',
            // Descripción en INGLÉS
            'descripcion_en' => 'Here is a video from the YouTube channel "Plan Based Bride" where she makes a selection of her favorite fantasy sagas. 
                        I highly recommend this channel if you are a fantasy lover and dont know what to read next.',

            // Enlace a video en YouTube
            'enlace' => 'https://www.youtube.com/watch?v=JVVLm0bLvZM',
        ],

        // ========== RECOMENDACIÓN 3 ==========
        // Biblioteca digital con millones de libros gratis
        [
            'titulo' => '¿No tienes dinero para comprar libros?',
            'titulo_en' => 'Dont have money to buy books?',

            // Descripción en ESPAÑOL
            'descripcion' => 'Si definitivamente no te puedes permitir comprar libros nuevos ya sean en físico o en digital, no te preocupes, tengo la solución.
                            OpenLibrary.org es una biblioteca digital gratuita que ofrece acceso a millones de libros. Puedes leer en línea o descargar algunos títulos de forma gratuita. Es una excelente opción para los amantes de la lectura con presupuesto limitado.',
            // Descripción en INGLÉS
            'descripcion_en' => 'If you definitely cannot afford to buy new books, whether in physical or digital format, do not worry, I have the solution.
                            OpenLibrary.org is a free digital library that offers access to millions of books. You can read',

            // Enlace a OpenLibrary.org
            'enlace' => 'https://openlibrary.org/',
        ],
    ];
}

/**
 * CÓMO SE USA ESTA FUNCIÓN:
 * 
 * En el archivo frontend/3_Inicio/inicio.php:
 * 
 * $noticias = obtenerNoticias();
 * foreach ($noticias as $noticia) {
 *     echo "Título: " . $noticia['titulo'];
 *     echo "Descripción: " . $noticia['descripcion'];
 *     echo "Link: " . $noticia['enlace'];
 * }
 */"
