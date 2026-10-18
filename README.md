# K-Libro

Aplicación web de gestión de biblioteca personal. Permite registrarse, buscar libros con la API de [OpenLibrary](https://openlibrary.org/developers/api), organizar tu biblioteca por estado de lectura, escribir reseñas, seguir logros y fijarte retos mensuales.

## Funcionalidades

- Registro e inicio de sesión
- Buscador de libros (OpenLibrary)
- Biblioteca personal con estados de lectura
- Reseñas de los libros
- Logros y retos mensuales
- Panel de administración (gestión de usuarios y roles: user, moderador, admin)
- Interfaz en español e inglés

## Capturas

| Login | Biblioteca | Buscador |
| ----- | ---------- | -------- |
| ![Login](docs/img/login.png) | ![Biblioteca](docs/img/biblioteca.png) | ![Buscador](docs/img/buscador.png) |

## Requisitos

- PHP 7.3 o superior con la extensión `pdo_mysql`
- MySQL o MariaDB

## Instalación en local

1. **Crea el usuario de MySQL.** Edita `database/crear-usuario-local.sql`, cambia `CAMBIA_ESTA_CONTRASENA` por tu contraseña y ejecútalo como administrador:
   ```bash
   sudo mysql < database/crear-usuario-local.sql
   ```
2. **Importa la base de datos.** Atención: `BBDD.sql` hace `DROP DATABASE IF EXISTS k_libro`, así que borra los datos que hubiera.
   ```bash
   sudo mysql < database/BBDD.sql
   ```
3. **Configura las credenciales.** Copia `.env.example` a `.env` y pon la misma contraseña en `DB_PASS`. La aplicación lo carga automáticamente.
4. **Arranca el servidor** con `public/` como raíz:
   ```bash
   php -S localhost:8000 -t public
   ```
5. Abre `http://localhost:8000/pages/login/login.php`, regístrate y conviértete en administrador:
   ```sql
   UPDATE usuarios SET rol = 'admin' WHERE email = 'tu@correo.com';
   ```

> En producción, configura Apache/Nginx con `public/` como directorio raíz, de modo que `.env`, `backend/` y `database/` no sean accesibles desde el navegador.

## Variables de entorno

| Variable  | Descripción                | Valor por defecto |
| --------- | -------------------------- | ----------------- |
| `DB_HOST` | Host de MySQL              | `localhost`       |
| `DB_NAME` | Nombre de la base de datos | `k_libro`         |
| `DB_USER` | Usuario                    | *(obligatoria)*   |
| `DB_PASS` | Contraseña                 | *(obligatoria)*   |

## Estructura del proyecto

```
k-libro/
├── database/
│   ├── BBDD.sql                    # Esquema completo + datos iniciales
│   ├── 002-add-moderador-role.sql  # Migración: rol moderador
│   └── crear-usuario-local.sql     # Crea el usuario de MySQL (pon tu contraseña)
├── backend/
│   ├── config/db.php       # Conexión PDO a MySQL (lee .env / variables de entorno)
│   ├── helpers/            # Logros y retos, esquema, protección CSRF
│   ├── validators/         # Validación de contraseñas
│   ├── actions/            # Procesadores de formularios (POST)
│   └── noticias.php        # Recomendaciones de la página de inicio
└── public/
    ├── css/                # Hojas de estilo por página
    ├── js/i18n.js          # Internacionalización (ES/EN)
    ├── assets/img/
    └── pages/              # registro, login, inicio, biblioteca, mi_cuenta, buscador, admin
```

## Licencia

[MIT](LICENSE)
