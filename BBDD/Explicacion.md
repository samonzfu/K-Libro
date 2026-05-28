Las 3 tablas principales
usuarios — Guarda las cuentas de los usuarios. Cada usuario tiene un id, nombre, email, contraseña hasheada y rol (user/admin).
libros — Guarda los metadatos de los libros que algún usuario ha añadido alguna vez: título, autor, portada y descripción. La clave primaria no es un número autonumérico sino el id de OpenLibrary (por ejemplo /works/OL27516W), porque los libros vienen de esa API externa.
logros — El catálogo de medallas disponibles: nombre, descripción, icono y el criterio numérico para desbloquearla (1 libro, 5 libros, 20 libros...).

Las 3 tablas de relación
Estas conectan las anteriores entre sí:
biblioteca — Conecta un usuario con un libro. Es la tabla más importante. Además de la relación, guarda el estado (pendiente/leyendo/leido), la fecha de lectura, la calificación (1-5) y la reseña. Tiene dos CHECK importantes: la calificación solo puede ser entre 1 y 5, y la fecha de lectura solo puede existir si el estado es leido.
usuario_logros — Conecta un usuario con un logro. Registra qué medallas ha desbloqueado cada usuario y cuándo. Tiene una restricción UNIQUE(usuario_id, logro_id) para que no se pueda ganar el mismo logro dos veces.
retos_mensuales — Conecta un usuario con su meta de ese mes. Guarda cuántos libros se propuso leer, en qué mes y año, y si lo consiguió o no. También tiene UNIQUE(usuario_id, mes, anio) para que solo pueda haber un reto por usuario por mes.

El diagrama
usuarios ──────────── biblioteca ──────────── libros
    │                                           
    ├──────────── usuario_logros ──────────── logros
    │                                           
    └──────────── retos_mensuales

El detalle clave: ON DELETE CASCADE
Todas las tablas de relación tienen esta opción en sus claves foráneas. Significa que si borras un usuario, se borran automáticamente todos sus datos: sus libros de la biblioteca, sus logros y sus retos. No quedan huérfanos en la base de datos. Esto es lo que aprovecha eliminar_usuario.php cuando un admin elimina una cuenta.

Cómo contarlo en la expo en 30 segundos
"La base de datos tiene 6 tablas. Tres guardan los datos principales: usuarios, libros y el catálogo de logros. Las otras tres son tablas de relación que los conectan: biblioteca une usuario con libro y guarda el estado de lectura; usuario_logros registra qué medallas tiene cada usuario; y retos_mensuales guarda el objetivo mensual de cada uno. Todas usan ON DELETE CASCADE, así que si se elimina un usuario, todos sus datos desaparecen solos."
