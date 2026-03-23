<?php

/**
 * ==================== ELIMINAR LIBRO DE BIBLIOTECA ====================
 * 
 * Este archivo ELIMINA un libro de la biblioteca personal del usuario
 * 
 * QUÉ HACE:
 * 1. Verifica que el usuario está autenticado
 * 2. Verifica que el libro existe en SU biblioteca
 * 3. Elimina el libro de forma segura (solo del usuario actual)
 * 4. Redirige de vuelta a la biblioteca
 * 
 * Se llama desde: frontend/4_Biblioteca/biblioteca.php
 */

// Iniciar sesión
session_start();

// PASO 1: Verificar que el usuario está autenticado
if (empty($_SESSION['user_id'])) {
    // Si no está logueado, redirigir al login
    header('Location: ../../frontend/2_Login/login.php');
    exit;
}

// PASO 2: Verificar que se recibió el ID del libro a eliminar
if (empty($_POST['libro_id'])) {
    // Si no hay libro_id, redirigir a la biblioteca
    header('Location: ../../frontend/4_Biblioteca/biblioteca.php');
    exit;
}

// Incluir conexión a base de datos
include '../conexionBD.php';

// PASO 3: Obtener datos del POST
$libro_id = $_POST['libro_id'];           // ID de OpenLibrary del libro
$usuario_id = $_SESSION['user_id'];       // ID del usuario autenticado

// PASO 4: VERIFICAR QUE EL LIBRO EXISTE EN LA BIBLIOTECA DEL USUARIO
// Seguridad: Solo el dueño puede eliminar su libro
// No queremos que alguien pueda eliminar libros de otros usuarios
$stmt = $pdo->prepare("
    SELECT b.id FROM biblioteca b
    WHERE b.libro_id_openlibrary = ? AND b.usuario_id = ?
");
$stmt->execute([$libro_id, $usuario_id]);

// Si no encontramos el libro en su biblioteca, salir (no hacer nada)
if (!$stmt->fetch()) {
    // El libro no pertenece a este usuario
    header('Location: ../../frontend/4_Biblioteca/biblioteca.php');
    exit;
}

// PASO 5: ELIMINAR EL LIBRO
// Ahora sabemos que el libro le pertenece, así que lo eliminamos
$stmt = $pdo->prepare("DELETE FROM biblioteca WHERE libro_id_openlibrary = ? AND usuario_id = ?");
$stmt->execute([$libro_id, $usuario_id]);

// PASO 6: Redirigir a la biblioteca (actualizada sin el libro)
header('Location: ../../frontend/4_Biblioteca/biblioteca.php');
exit;

?>
