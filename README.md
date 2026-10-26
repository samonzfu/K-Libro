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
- Modo claro y oscuro (oscuro por defecto)
- Avisos y confirmaciones visuales (sin cuadros `alert()` del navegador)
- Botón para mostrar u ocultar la contraseña en login y registro

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
3. **Configura las credenciales.** Crea un archivo `.env` en la raíz del proyecto (está en `.gitignore`, no se sube) con tus datos. La aplicación lo carga automáticamente:
   ```
   DB_HOST=localhost
   DB_NAME=k_libro
   DB_USER=k_libro
   DB_PASS=tu_contraseña
   ```
4. **Arranca el servidor** desde la raíz del proyecto (la carpeta que contiene `backend/` y `public/`):
   ```bash
   php -S localhost:8000
   ```
   Con XAMPP, copia la carpeta del proyecto dentro de `htdocs` y usa Apache.
5. Abre la ruta relativa `public/pages/login/login.php` desde la raíz del proyecto en tu servidor local, regístrate y conviértete en administrador:
   ```sql
   UPDATE usuarios SET rol = 'admin' WHERE email = 'tu@correo.com';
   ```

**¿Ya tenías la base de datos creada?** No vuelvas a importar `BBDD.sql` (borra los datos). Ejecuta solo `database/003-renombrar-logros.sql` en phpMyAdmin para actualizar los nombres de los logros (Lector Principiante, Bibliófilo y Reto Mensual).

> Los formularios envían a `backend/actions/`, así que la raíz del servidor es la carpeta del proyecto, no `public/`. Si lo publicas, bloquea el acceso web a `.env`, `database/` y `.git` (por ejemplo con un `.htaccess`) y comprueba que `https://tu-sitio/.env` da error 403 o 404.

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
│   ├── 003-renombrar-logros.sql    # Migración: nuevos nombres de logros
│   └── crear-usuario-local.sql     # Crea el usuario de MySQL (pon tu contraseña)
├── backend/
│   ├── config/db.php       # Conexión PDO a MySQL (lee .env / variables de entorno)
│   ├── helpers/            # Logros y retos, esquema, protección CSRF, avisos (flash)
│   ├── validators/         # Validación de contraseñas
│   ├── actions/            # Procesadores de formularios (POST)
│   └── noticias.php        # Recomendaciones de la página de inicio
└── public/
    ├── css/                # Hojas de estilo por página + theme.css (modo claro/oscuro) y ui.css (avisos, confirmaciones)
    ├── js/                 # i18n.js (ES/EN), theme.js (modo claro/oscuro), notify.js (avisos), password-toggle.js
    ├── assets/img/
    └── pages/              # registro, login, inicio, biblioteca, mi_cuenta, buscador, admin
```
