# K-Libro

Aplicación web de gestión de biblioteca personal. Permite registrarse, buscar libros con la API de [OpenLibrary](https://openlibrary.org/developers/api), organizar tu biblioteca por estado de lectura, escribir reseñas, seguir logros y fijarte retos mensuales.

## Funcionalidades

- Registro e inicio de sesión
- Buscador de libros (OpenLibrary)
- Biblioteca personal con estados de lectura
- Reseñas de los libros
- Logros y retos mensuales
- Panel de administración (gestión de usuarios y roles)
- Interfaz en español e inglés

## Requisitos

- PHP con la extensión `pdo_mysql`
- MySQL o MariaDB

## Instalación en local

1. Crea la base de datos e importa el esquema:
   ```bash
   mysql -u root -p -e "CREATE DATABASE k_libro CHARACTER SET utf8mb4;"
   mysql -u root -p k_libro < database/BBDD.sql
   ```
2. Copia `.env.example` a `.env` y rellena tus credenciales.
3. Arranca el servidor apuntando a `public/` como raíz:
   ```bash
   set -a; source .env; set +a
   php -S localhost:8000 -t public
   ```
4. Abre `http://localhost:8000/pages/login/login.php`.

> En producción, configura el servidor web (Apache/Nginx) con `public/` como directorio raíz, de modo que `backend/` y `database/` no sean accesibles desde el navegador.

## Variables de entorno

| Variable  | Descripción                | Valor por defecto |
| --------- | -------------------------- | ----------------- |
| `DB_HOST` | Host de MySQL              | `localhost`       |
| `DB_NAME` | Nombre de la base de datos | `k_libro`         |
| `DB_USER` | Usuario                    | `k_libro`         |
| `DB_PASS` | Contraseña                 | *(obligatoria)*   |

## Estructura del proyecto

```
k-libro/
├── database/               # Scripts SQL
│   └── BBDD.sql            # Esquema completo + datos iniciales
├── backend/
│   ├── config/db.php       # Conexión PDO a MySQL
│   ├── helpers/            # Lógica de logros y retos
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