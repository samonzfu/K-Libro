<?php

/**
 * ==================== ACTUALIZAR RESEÑA Y CALIFICACIÓN ====================
 * 
 * ENDPOINT AJAX que ACTUALIZA la reseña y calificación de un libro
 * 
 * QUÉ HACE:
 * 1. Valida los datos recibidos (calificación 1-5, reseña máx 2000 caracteres)
 * 2. Verifica que el libro existe en la biblioteca del usuario
 * 3. Actualiza reseña y calificación en la BD
 * 4. Recalcula logros y retos si es necesario
 * 5. Devuelve JSON con el resultado
 * 
 * Se llama desde: AJAX en frontend/4_Biblioteca/biblioteca.php
 * Parámetros POST:
 * - id_openlibrary: ID del libro en OpenLibrary
 * - calificacion: 1-5 (opcional)
 * - review: Texto de la reseña (opcional, máx 2000 caracteres)
 * - fecha_lectura: Fecha en formato YYYY-MM-DD (si estado es 'leido')
 */

// Iniciar sesión
session_start();
// Indicar que responderemos en JSON (no HTML)
header('Content-Type: application/json; charset=utf-8');

// PASO 1: Verificar que el usuario está autenticado
if (empty($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['ok' => false, 'mensaje' => 'Sesion no iniciada']);
    exit;
}

// PASO 2: Verificar que la solicitud es POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'mensaje' => 'Metodo no permitido']);
    exit;
}

// Incluir conexión a BD y funciones de helpers
include '../conexionBD.php';
require_once '../helpers/biblioteca_schema.php';
require_once '../helpers/logros.php';

// Asegurar que existe la columna fecha_lectura
asegurarColumnaFechaLectura($pdo);

// PASO 3: Obtener y limpiar datos del formulario POST
$idOpenLibrary = trim((string) ($_POST['id_openlibrary'] ?? ''));
$calificacionRaw = trim((string) ($_POST['calificacion'] ?? ''));
$review = trim((string) ($_POST['review'] ?? ''));
$fechaLecturaRaw = trim((string) ($_POST['fecha_lectura'] ?? ''));

// PASO 4: VALIDACIONES

// Validar que existe el ID del libro
if ($idOpenLibrary === '') {
    http_response_code(400);
    echo json_encode(['ok' => false, 'mensaje' => 'Falta el identificador del libro']);
    exit;
}

// Validar la CALIFICACIÓN (si se proporciona)
$calificacion = null;
if ($calificacionRaw !== '') {
    // Verificar que sea un número
    if (!ctype_digit($calificacionRaw)) {
        http_response_code(400);
        echo json_encode(['ok' => false, 'mensaje' => 'La calificacion debe ser un numero entero entre 1 y 5']);
        exit;
    }

    // Convertir a entero y verificar rango (1-5)
    $calificacion = (int) $calificacionRaw;
    if ($calificacion < 1 || $calificacion > 5) {
        http_response_code(400);
        echo json_encode(['ok' => false, 'mensaje' => 'La calificacion debe estar entre 1 y 5']);
        exit;
    }
}

// Validar la RESEÑA (máximo 2000 caracteres)
$reviewLength = function_exists('mb_strlen') ? mb_strlen($review, 'UTF-8') : strlen($review);
if ($reviewLength > 2000) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'mensaje' => 'La reseña es demasiado larga']);
    exit;
}

// Obtener ID del usuario autenticado
$usuarioId = (int) $_SESSION['user_id'];

try {
    // PASO 5: OBTENER DATOS ACTUALES DEL LIBRO
    // Necesitamos saber el estado actual (leido/leyendo/pendiente)
    // para saber si hay fecha_lectura anterior
    $stmtEstado = $pdo->prepare(
                'SELECT estado, fecha_lectura
         FROM biblioteca
         WHERE usuario_id = :usuario_id
           AND libro_id_openlibrary = :libro_id_openlibrary
         ORDER BY id DESC
         LIMIT 1'
    );
    $stmtEstado->execute([
        ':usuario_id' => $usuarioId,
        ':libro_id_openlibrary' => $idOpenLibrary
    ]);
    $libroActual = $stmtEstado->fetch(PDO::FETCH_ASSOC);

    // Si no encontramos el libro, error 404
    if (!libroActual) {
        http_response_code(404);
        echo json_encode(['ok' => false, 'mensaje' => 'No se encontro el libro en tu biblioteca']);
        exit;
    }

    // PASO 6: Determinar FECHA DE LECTURA
    // Solo es válida si el estado actual es 'leido'
    $fechaLectura = null;
    // Guardar la fecha anterior para sincronizar retos después
    $fechaLecturaAnterior = ($libroActual['estado'] ?? '') === 'leido'
        ? (($libroActual['fecha_lectura'] ?? null) ?: null)
        : null;
    
    if ($libroActual['estado'] === 'leido') {
        // Si está leído, DEBE tener fecha
        if ($fechaLecturaRaw === '') {
            http_response_code(400);
            echo json_encode(['ok' => false, 'mensaje' => 'Debes indicar la fecha de lectura']);
            exit;
        }

        // Validar formato de fecha (YYYY-MM-DD)
        $fechaLecturaObj = DateTime::createFromFormat('Y-m-d', $fechaLecturaRaw);
        $fechaValida = $fechaLecturaObj && $fechaLecturaObj->format('Y-m-d') === $fechaLecturaRaw;
        if (!$fechaValida) {
            http_response_code(400);
            echo json_encode(['ok' => false, 'mensaje' => 'La fecha de lectura no es valida']);
            exit;
        }

        // Validar que la fecha NO esté en el futuro
        $hoy = new DateTimeImmutable('today');
        if ($fechaLecturaObj > $hoy) {
            http_response_code(400);
            echo json_encode(['ok' => false, 'mensaje' => 'La fecha de lectura no puede estar en el futuro']);
            exit;
        }

        $fechaLectura = $fechaLecturaRaw;
    }

    // PASO 7: INICIAR TRANSACCIÓN
    // (Si algo falla, revertir todos los cambios)
    $pdo->beginTransaction();

    // Verificar si la tabla soporta las columnas calificacion y review
    $stmtColCal = $pdo->query("SHOW COLUMNS FROM biblioteca LIKE 'calificacion'");
    $stmtColReview = $pdo->query("SHOW COLUMNS FROM biblioteca LIKE 'review'");
    $soportaCalificacion = $stmtColCal && $stmtColCal->fetch(PDO::FETCH_ASSOC);
    $soportaReview = $stmtColReview && $stmtColReview->fetch(PDO::FETCH_ASSOC);

    // PASO 8: ACTUALIZAR LA BASE DE DATOS
    if ($soportaCalificacion && $soportaReview) {
        // UPDATE: Modificar calificación, reseña y fecha
        $stmt = $pdo->prepare(
            'UPDATE biblioteca
             SET calificacion = :calificacion,
                 review = :review,
                 fecha_lectura = :fecha_lectura
             WHERE usuario_id = :usuario_id
               AND libro_id_openlibrary = :libro_id_openlibrary
             ORDER BY id DESC
             LIMIT 1'
        );

        $stmt->execute([
            ':calificacion' => $calificacion,
            ':review' => $review !== '' ? $review : null,
            ':fecha_lectura' => $fechaLectura,
            ':usuario_id' => $usuarioId,
            ':libro_id_openlibrary' => $idOpenLibrary
        ]);

        // Verificar que al menos 1 fila fue actualizada
        if ($stmt->rowCount() === 0) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();  // Deshacer cambios
            }
            http_response_code(404);
            echo json_encode(['ok' => false, 'mensaje' => 'No se encontro el libro en tu biblioteca']);
            exit;
        }
    }

    // PASO 9: SINCRONIZAR LOGROS Y RETOS
    // Si cambió la fecha, recalcular retos y logros
    $sincronizacion = sincronizarRetosYLogrosPorCambioLibro(
        $pdo,
        $usuarioId,
        $fechaLecturaAnterior,
        ($libroActual['estado'] ?? '') === 'leido' ? $fechaLectura : null
    );

    // PASO 10: Confirmar transacción (aplicar todos los cambios)
    $pdo->commit();
    
} catch (Throwable $e) {
    // ERROR: Si algo falló, deshacer todo
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    http_response_code(500);
    echo json_encode(['ok' => false, 'mensaje' => 'Error al guardar los detalles']);
    exit;
}

// PASO 11: RESPONDER CON ÉXITO EN JSON
echo json_encode([
    'ok' => true,
    'mensaje' => 'Detalles guardados correctamente',
    'nuevos_logros' => $sincronizacion['nuevos_logros'] ?? []
]);
exit;
