<?php

/**
 * ==================== LOGOUT (CERRAR SESIÓN) ====================
 * 
 * Este archivo CIERRA LA SESIÓN del usuario de forma segura
 * 
 * QUÉ HACE:
 * 1. Borra todas las variables de sesión
 * 2. Invalida la cookie de sesión
 * 3. Destruye la sesión en el servidor
 * 4. Redirige al login
 * 
 * Se llama desde: Cualquier página puede incluir un botón/enlace a este archivo
 */

// Iniciar sesión (necesario para poder destruirla después)
session_start();

// PASO 1: Borrar TODAS las variables de sesión
// $_SESSION = [] o unset($_SESSION['variable'])
// Esto elimina: user_id, nombre, rol, etc.
$_SESSION = [];

// PASO 2: Invalidar la COOKIE de sesión
// Algunas configuraciones guardan el ID de sesión en una cookie
// Necesitamos borrar esa cookie también
if (ini_get('session.use_cookies')) {
    // Obtener parámetros actuales de la cookie
    $params = session_get_cookie_params();
    
    // Crear una cookie vacía con fecha de expiración en el pasado
    // Esto hace que el navegador la borre
    setcookie(
        session_name(),              // Nombre de la cookie (PHPSESSID por defecto)
        '',                          // Cookie vacía
        time() - 42000,              // Fecha de expiración en el pasado (42000 segundos atrás)
        $params['path'],             // Path
        $params['domain'],           // Domain
        $params['secure'],           // Secure
        $params['httponly']          // HttpOnly
    );
}

// PASO 3: Destruir completamente la sesión en el servidor
// Elimina el archivo de sesión del servidor
session_destroy();

// PASO 4: Redirigir al login
header('Location: 2_Login/login.php');
exit;
