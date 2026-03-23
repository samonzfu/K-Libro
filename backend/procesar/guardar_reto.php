<?php

/**
 * ==================== GUARDAR RETO MENSUAL ====================
 * 
 * Este archivo GUARDA el RETO MENSUAL que el usuario se propone
 * 
 * QUÉ HACE:
 * 1. Obtiene la META DE LIBROS que el usuario quiere leer este mes
 * 2. Valida que sea un número entre 1-50
 * 3. Inserta el reto en la tabla retos_mensuales
 * 4. Redirige de vuelta a Mi Cuenta
 * 
 * Se llama desde: frontend/5_Mi_cuenta/mi_cuenta.php
 */

// Iniciar sesión para acceder a $_SESSION
session_start();

// PASO 1: Verificar que el usuario está autenticado (tiene sesión)
if (empty($_SESSION['user_id'])) {
    // Si no está logueado, redirigir al login
    header('Location: ../../frontend/2_Login/login.php');
    exit;
}

// PASO 2: Verificar que la solicitud es POST
// (seguridad: solo aceptar POST, no GET)
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    // Si no es POST, redirigir a Mi Cuenta
    header('Location: ../../frontend/5_Mi_cuenta/mi_cuenta.php');
    exit;
}

// Incluir conexión a base de datos
require '../conexionBD.php';

// PASO 3: Obtener y limpiar el valor meta_libros del formulario
$metaLibrosRaw = trim((string) ($_POST['meta_libros'] ?? ''));

// PASO 4: VALIDAR que sea un número (solo dígitos, sin letras)
if ($metaLibrosRaw === '' || !ctype_digit($metaLibrosRaw)) {
    // Si no es un número válido, guardar mensaje de error en sesión
    $_SESSION['reto_flash'] = [
        'tipo' => 'error',
        'clave' => 'reto-error-numero'  // Clave para traducción multiidioma
    ];
    // Redirigir de vuelta a Mi Cuenta
    header('Location: ../../frontend/5_Mi_cuenta/mi_cuenta.php');
    exit;
}

// Convertir a número entero
$metaLibros = (int) $metaLibrosRaw;

// PASO 5: VALIDAR que esté dentro del rango permitido (1-50 libros)
if ($metaLibros < 1 || $metaLibros > 50) {
    // Fuera de rango - guardar error
    $_SESSION['reto_flash'] = [
        'tipo' => 'error',
        'clave' => 'reto-error-rango'
    ];
    header('Location: ../../frontend/5_Mi_cuenta/mi_cuenta.php');
    exit;
}

// PASO 6: Obtener datos del usuario y la fecha actual
$usuarioId = (int) $_SESSION['user_id'];
$mesActual = (int) date('n');      // Mes actual (1-12)
$anioActual = (int) date('Y');     // Año actual (2024)

try {
    // PASO 7: INSERTAR RETO EN LA BASE DE DATOS
    // Crear reto para este mes y año con la meta especificada
    $stmt = $pdo->prepare(
        'INSERT INTO retos_mensuales (usuario_id, mes, anio, meta_libros)
         VALUES (?, ?, ?, ?)'
    );
    // Ejecutar con los 4 valores
    $stmt->execute([$usuarioId, $mesActual, $anioActual, $metaLibros]);

    // ÉXITO: Reto guardado correctamente
    $_SESSION['reto_flash'] = [
        'tipo' => 'success',
        'clave' => 'reto-success-guardado'
    ];
} catch (PDOException $e) {
    // ERROR: Posiblemente el usuario ya tiene un reto este mes (duplicado)
    $errorDuplicado = isset($e->errorInfo[1]) && (int) $e->errorInfo[1] === 1062;

    $_SESSION['reto_flash'] = [
        'tipo' => 'error',
        'clave' => $errorDuplicado ? 'reto-error-duplicado' : 'reto-error-guardado'
    ];
}

// PASO 8: Redirigir a Mi Cuenta (con mensaje de éxito o error)
header('Location: ../../frontend/5_Mi_cuenta/mi_cuenta.php');
exit;
?>