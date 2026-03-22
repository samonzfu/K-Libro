# Panel de Administrador - K-Libro

## Descripción
El panel de administrador es una sección especial de K-Libro que permite a los usuarios con rol `admin` ver:
- **Total de usuarios registrados** en la plataforma
- **Lista completa de usuarios** con los siguientes datos:
  - ID del usuario
  - Nombre de usuario
  - Email
  - Rol (admin/user)
  - Cantidad de **libros marcados como leídos**
  - Total de libros en su biblioteca

## Requisitos
Para acceder al panel de administrador, el usuario debe:
1. Estar registrado en K-Libro
2. Tener el rol de `admin` en la base de datos

## Cómo crear un usuario admin

### Opción 1: Usando el script SQL (Recomendado)
1. Abre tu cliente de base de datos (phpMyAdmin, MySQL Workbench, etc.)
2. Ejecuta el script: `BBDD/004-crear_admin.sql`
3. Esto creará un usuario admin con las siguientes credenciales:
   - **Usuario:** admin
   - **Email:** admin@k-libro.local
   - **Contraseña:** admin123
   - **Rol:** admin

### Opción 2: Inserción manual
Ejecuta esta consulta SQL en tu base de datos:

```sql
INSERT INTO usuarios (nombre, email, contrasena, rol) 
VALUES ('admin', 'admin@k-libro.local', '$2y$10$8aJfzMr9r9n8u7n6m5q4p3o2n1z8y7x6w5v4u3t2s1r0q9p8o7n6m5', 'admin');
```

## Acceder al panel de admin

1. Inicia sesión en K-Libro con el usuario admin
2. Serás redirigido a la página de inicio
3. En el menú de navegación, verás un enlace rojo llamado **"Panel Admin"**
4. Haz clic para acceder al panel

## Cambiar la contraseña del admin
Es altamente recomendable cambiar la contraseña predeterminada:

1. Inicia sesión como admin
2. Ve a **Mi Cuenta**
3. Cambia tu contraseña a una más segura

## Funcionalidades del panel

### Resumen General
Muestra el total de usuarios registrados en la plataforma.

### Tabla de Usuarios
Una tabla completa que muestra:
- Todos los usuarios registrados
- Cantidad de libros leídos por cada usuario
- Total de libros en la biblioteca de cada usuario
- El rol de cada usuario

La tabla está ordenada por **libros leídos** de mayor a menor.

## Notas de seguridad
- Solo los usuarios con rol `admin` pueden acceder a este panel
- Los intentos de acceso no autorizados serán rechazados
- El panel muestra información sensible (emails, nombres), así que solo debe ser accesible por administradores de confianza

## Ubicación del archivo
El panel de admin se encuentra en: `frontend/7_Admin/panel_admin.php`
