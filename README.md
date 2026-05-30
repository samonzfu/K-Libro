# K-Libro

Aplicación web de gestión de biblioteca personal. Permite a los usuarios registrarse, buscar libros mediante la API de OpenLibrary, gestionar su biblioteca con estados de lectura, escribir reseñas, seguir logros y establecer retos mensuales.

## Estructura del proyecto

```
k-libro/
├── database/               # Scripts SQL
│   ├── BBDD.sql            # Esquema completo + datos iniciales
│   └── 999-fix-acceso-local.sql
├── backend/
│   ├── config/
│   │   └── db.php          # Conexión PDO a MySQL
│   ├── helpers/
│   │   ├── logros.php      # Lógica de logros y retos
│   │   └── biblioteca_schema.php
│   ├── validators/
│   │   └── validar_contrasena.php
│   ├── actions/            # Procesadores de formularios (POST handlers)
│   │   ├── auth.php        # Registro e inicio de sesión
│   │   ├── guardar_libro.php
│   │   ├── eliminar_libro.php
│   │   ├── actualizar_resena.php
│   │   ├── guardar_reto.php
│   │   ├── cambiar_rol.php
│   │   └── eliminar_usuario.php
│   └── noticias.php        # Recomendaciones de la página de inicio
└── public/
    ├── logout.php
    ├── css/                # Hojas de estilo por página
    ├── js/
    │   └── i18n.js         # Motor de internacionalización (ES/EN)
    ├── assets/
    │   └── img/
    └── pages/
        ├── registro/
        ├── login/
        ├── inicio/
        ├── biblioteca/
        ├── mi_cuenta/
        ├── buscador/
        └── admin/
```

## Instalación

1. Importa `database/BBDD.sql` en tu servidor MySQL.
2. Configura las credenciales de la base de datos mediante variables de entorno o edita `backend/config/db.php`.
3. El punto de entrada es `public/pages/login/login.php`.

## Variables de entorno (opcionales)

| Variable | Descripción           | Valor por defecto |
|----------|-----------------------|-------------------|
| DB_HOST  | Host de MySQL         | localhost         |
| DB_NAME  | Nombre de la base de datos | k_libro      |
| DB_USER  | Usuario               | k_libro           |
| DB_PASS  | Contraseña            | KLibro_2026$Clase! |
