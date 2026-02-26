<?php
// Devuelve un array de noticias/recomendaciones de libros.
// En un proyecto real estas podrían sacarse de una API externa o de la base de datos.

function obtenerNoticias(): array
{
    return [
        [
            'titulo' => 'Los 10 clásicos que debes leer antes de los 30',
            'descripcion' => 'Una selección imprescindible de novelas que han marcado generaciones.',
            'enlace' => 'https://es.wikipedia.org/wiki/Lista_de_literatura_cl%C3%A1sica',
        ],
        [
            'titulo' => 'Nuevos lanzamientos de fantasía 2026',
            'descripcion' => 'Explora los mundos imaginarios que acaban de llegar a las librerías.',
            'enlace' => 'https://www.example.com/novedades-fantasia-2026',
        ],
        [
            'titulo' => 'Cómo organizar tu club de lectura',
            'descripcion' => 'Consejos prácticos para que tu grupo disfrute cada encuentro.',
            'enlace' => 'https://www.example.com/club-de-lectura',
        ],
    ];
}
