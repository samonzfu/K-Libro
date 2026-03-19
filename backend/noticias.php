<?php
function obtenerNoticias(): array
{
    return [
        [
            'titulo' => '¿No sabes qué leer a continuación?',
            'titulo_en' => 'Not sure what to read next?',

            'descripcion' => 'Esta web te recomienda autores/as que podrían interesarte según el autor/a que has buscado. Cuanto más cerca estén los/as autores/as del que has buscado, más se asemejan. 
                            Esta web es una forma muy interactiva de descubrir nuevos escritores/as',
            'descripcion_en' => 'This website recommends authors who might interest you based on the author you searched for. The closer the authors are to the one you searched for, the more similar they are.
                            This website is a highly interactive way to discover new writers',

            'enlace' => 'https://www.literature-map.com/',
        ],
        [
            'titulo' => '¿Buscas consumir contenido sobre libros?',
            'titulo_en' => 'Looking for book-related content?',

            'descripcion' => 'Aquí te dejo un vídeo del canal de youtube "Plan Based Bride" donde hace una selección de sus sagas de fantasía favoritas. Te recomiendo este canal
                        encarecidamente si eres amante de la fantasía y no sabes qué leer a continuación.',
            'descripcion_en' => 'Here is a video from the YouTube channel "Plan Based Bride" where she makes a selection of her favorite fantasy sagas. 
                        I highly recommend this channel if you are a fantasy lover and dont know what to read next.',

            'enlace' => 'https://www.youtube.com/watch?v=JVVLm0bLvZM',
        ],
        [
            'titulo' => '¿No tienes dinero para comprar libros?',
            'titulo_en' => 'Dont have money to buy books?',

            'descripcion' => 'Si definitivamente no te puedes permitir comprar libros nuevos ya sean en físico o en digital, no te preocupes, tengo la solución.
                            OpenLibrary.org es una biblioteca digital gratuita que ofrece acceso a millones de libros. Puedes leer en línea o descargar algunos títulos de forma gratuita. Es una excelente opción para los amantes de la lectura con presupuesto limitado.',
            'descripcion_en' => 'If you definitely cannot afford to buy new books, whether in physical or digital format, do not worry, I have the solution.
                            OpenLibrary.org is a free digital library that offers access to millions of books. You can read',

            'enlace' => 'https://openlibrary.org/',
        ],
    ];
}
