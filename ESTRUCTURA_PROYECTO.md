# Estructura del Proyecto K-Libro

## 📁 Esquema General

```
K-Libro/
│
├── backend/                              # Lógica del servidor (PHP)
│   ├── conexionBD.php                   # Conexión a la base de datos
│   ├── procesar/
│   │   └── procesa.php                  # Procesador de formularios (registro, login)
│   ├── noticias.php                     # Función que devuelve noticias/recomendaciones
│   └── validadores/
│       └── validar_contrasena.php       # Validación de contraseñas
│
├── frontend/                             # Interfaz de usuario (HTML, CSS)
│   ├── 1_Registro/                      # Página de registro
│   │   ├── registro.php                # Formulario de registro
│   │   └── css/
│   │       └── estilo.css               # Estilos del registro
│   │
│   ├── 2_Login/                         # Página de inicio de sesión
│   │   ├── login.php                   # Formulario de login
│   │   └── css/
│   │       └── estilo.css               # Estilos del login
│   │
│   ├── 3_Inicio/                        # Página principal (dashboard)
│   │   ├── inicio.php                  # Dashboard del usuario
│   │   └── css/
│   │       └── estilo.css               # Estilos del inicio
│   │
│   ├── 4_Biblioteca/                    # Catálogo de libros
│   │   ├── biblioteca.php              # Listado de libros
│   │   └── css/
│   │       └── estilo.css               # Estilos de la biblioteca
│   │
│   ├── 5_Mi_cuenta/                     # Perfil del usuario
│   │   ├── mi_cuenta.php               # Gestión de cuenta
│   │   └── css/
│   │       └── estilo.css               # Estilos de mi cuenta
│   │
│   └── 6_Buscador/                      # Buscador de libros
│       └── buscador.php                 # Motor de búsqueda
│
├── BBDD/                                 # Base de datos
│   └── BBDD.sql                         # Script de creación y datos iniciales
│
└── ESTRUCTURA_PROYECTO.md                # Este archivo

```

## 🔄 Flujo de Autenticación

```
Usuario accede a login.php
         ↓
Completa formulario y envía POST
         ↓
backend/procesar/procesa.php recibe acción='login'
         ↓
Valida credenciales en BBDD
         ↓
✓ Correcto: Crea sesión y redirige a → frontend/3_Inicio/inicio.php
✗ Incorrecto: Muestra alerta y vuelve al login
```

## 🔄 Flujo de Registro

```
Usuario accede a registro.php
         ↓
Completa formulario y envía POST
         ↓
backend/procesar/procesa.php recibe acción='registro'
         ↓
Valida contraseña (backend/validadores/validar_contrasena.php)
         ↓
Verifica email único en BBDD
         ↓
✓ Exitoso: Inserta usuario y redirige a → frontend/2_Login/login.php
✗ Error: Muestra alerta y vuelve al registro
```

## 📊 Base de Datos

**Tabla: usuarios**
```
- id (INT, PRIMARY KEY)
- nombre (VARCHAR)
- email (VARCHAR, UNIQUE)
- contrasena (VARCHAR, hashed con PASSWORD_HASH)
```

## 🔗 Rutas de Navegación

| Página | URL | Función |
|--------|-----|---------|
| Registro | `/frontend/1_Registro/registro.php` | Crear nueva cuenta |
| Login | `/frontend/2_Login/login.php` | Acceder con credenciales |
| Inicio | `/frontend/3_Inicio/inicio.php` | Dashboard principal (requiere sesión) |
| Biblioteca | `/frontend/4_Biblioteca/biblioteca.php` | Catálogo de libros |
| Mi Cuenta | `/frontend/5_Mi_cuenta/mi_cuenta.php` | Perfil del usuario |
| Buscador | `/frontend/6_Buscador/buscador.php` | Búsqueda de libros |

## 🔐 Seguridad Implementada

- ✅ Prepared Statements en todas las consultas SQL
- ✅ Password hashing con `password_hash()` (algoritmo bcrypt)
- ✅ Validación de contraseña (mín. 8 caracteres, mayús, minús, número, especial)
- ✅ Validación de email con `filter_var()`
- ✅ Saneamiento de inputs con `trim()` y `htmlspecialchars()`
- ✅ Sesiones para autenticación

## 📝 Archivos Clave

| Archivo | Descripción | Responsabilidad |
|---------|-------------|-----------------|
| `backend/conexionBD.php` | Conexión a MySQL | Manejo de BBDD |
| `backend/procesar/procesa.php` | Servidor | Lógica de registro y login |
| `backend/noticias.php` | Contenido | Devuelve arreglo de noticias/recomendaciones |
| `backend/validadores/validar_contrasena.php` | Validación | Reglas de contraseña |
| `frontend/*/estilo.css` | Estilos | Diseño visual |
| `BBDD/BBDD.sql` | SQL script | Estructura de datos |

---

**Última actualización:** 25/02/2026
**Versión:** 1.0
