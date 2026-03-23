<?php

/**
 * ==================== GUARDAR LIBRO EN BIBLIOTECA ====================
 * 
 * ENDPOINT AJAX principal que GUARDA un libro en la biblioteca personal del usuario
 * 
 * FLUJO:
 * 1. Usuario busca libro en OpenLibrary (buscador)
 * 2. Usuario presiona "Agregar a mi biblioteca"
 * 3. Este archivo recibe los datos del libro y estado
 * 4. Inserta/actualiza en tablas libros y biblioteca
 * 5. Calculalogros y retos
 * 6. Devuelve JSON con resultado
 * 
 * Se llama desde: AJAX en frontend/6_Buscador/buscador.php
 * Parámetros POST necesarios:
 * - id_openlibrary: ID único del libro en OpenLibrary
 * - titulo: Título del libro
 * - autor: Autor/es del libro
 * - portada: URL de la imagen de portada
 * - estado: pendiente|leyendo|leido (estado de lectura)
 * - fecha_lectura: Si estado='leido', fecha en formato YYYY-MM-DD
 * - calificacion: Si estado='leido', calificación 1-5 (opcional)
 * - review: Reseña del libro (opcional, máximo 2000 caracteres)
 */

// Iniciar sesión
session_start();
// Responder en JSON
header('Content-Type: application/json; charset=utf-8');

// ========== PASO 1: VERIFICACIONES DE SEGURIDAD ==========

// Verificar que el usuario está autenticado
if (empty($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['ok' => false, 'mensaje' => 'Sesion no iniciada']);
    exit;
}

// Verificar que la solicitud es POST (no GET u otros métodos)
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'mensaje' => 'Metodo no permitido']);
    exit;
}

// ========== PASO 2: INCLUIR ARCHIVOS NECESARIOS ==========
include '../conexionBD.php';
require_once '../helpers/biblioteca_schema.php';
require_once '../helpers/logros.php';

// Asegurar que existe la columna fecha_lectura
asegurarColumnaFechaLectura($pdo);

// ========== PASO 3: OBTENER Y LIMPIAR DATOS DEL FORMULARIO ==========
$idOpenLibrary = trim($_POST['id_openlibrary'] ?? '');
$titulo = trim($_POST['titulo'] ?? '');
$autor = trim($_POST['autor'] ?? 'Autor desconocido');
$portada = trim($_POST['portada'] ?? '');
$estado = trim($_POST['estado'] ?? '');                          // pendiente, leyendo, leido
$fechaLecturaRaw = trim((string) ($_POST['fecha_lectura'] ?? ''));
$calificacionRaw = trim((string) ($_POST['calificacion'] ?? ''));
$review = trim((string) ($_POST['review'] ?? ''));

// ========== PASO 4: VALIDACIONES BÁSICAS ==========

// Validar que tenemos los campos OBLIGATORIOS
$estadosPermitidos = ['pendiente', 'leyendo', 'leido'];

if ($idOpenLibrary === '' || $titulo === '' || !in_array($estado, $estadosPermitidos, true)) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'mensaje' => 'Datos incompletos o estado invalido']);
    exit;
}

// Inicializar variables que dependerán del estado
$calificacion = null;
$fechaLectura = null;

// ========== PASO 5: VALIDACIONES ESPECÍFICAS POR ESTADO ==========

// Si el estado es 'leido', DEBE tener fecha
if ($estado === 'leido') {
    if ($fechaLecturaRaw === '') {
        http_response_code(400);
        echo json_encode(['ok' => false, 'mensaje' => 'Debes indicar cuando leiste el libro']);
        exit;
    }

    // Validar que la fecha sea válida (formato YYYY-MM-DD)
    $fechaLecturaObj = DateTime::createFromFormat('Y-m-d', $fechaLecturaRaw);
    $fechaValida = $fechaLecturaObj && $fechaLecturaObj->format('Y-m-d') === $fechaLecturaRaw;

    if (!$fechaValida) {
        http_response_code(400);
        echo json_encode(['ok' => false, 'mensaje' => 'La fecha de lectura no es valida']);
        exit;
    }

    // Validar que no sea una fecha futura
    $hoy = new DateTimeImmutable('today');
    if ($fechaLecturaObj > $hoy) {
        http_response_code(400);
        echo json_encode(['ok' => false, 'mensaje' => 'La fecha de lectura no puede estar en el futuro']);
        exit;
    }

    $fechaLectura = $fechaLecturaRaw;
}

// Si estado es 'leido', validar la CALIFICACIÓN (1-5, opcional)
if ($estado === 'leido' && $calificacionRaw !== '') {
    if (!ctype_digit($calificacionRaw)) {
        http_response_code(400);
        echo json_encode(['ok' => false, 'mensaje' => 'La calificacion debe ser un numero entero entre 1 y 5']);
        exit;
    }

    $calificacion = (int) $calificacionRaw;
    if ($calificacion < 1 || $calificacion > 5) {
        http_response_code(400);
        echo json_encode(['ok' => false, 'mensaje' => 'La calificacion debe estar entre 1 y 5']);
        exit;
    }
}

// Si el estado NO es 'leido', la reseña debe estar vacía
if ($estado !== 'leido') {
    $review = '';
}

// Validar que la reseña no sea muy larga (máximo 2000 caracteres)
$reviewLength = function_exists('mb_strlen') ? mb_strlen($review, 'UTF-8') : strlen($review);
if ($reviewLength > 2000) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'mensaje' => 'La reseña es demasiado larga']);
    exit;
}

// ========== PASO 6: PREPARAR VARIABLES ==========
$usuarioId = (int) $_SESSION['user_id'];

try {
    // PASO 7: INICIAR TRANSACCIÓN
    // Si algo falla, deshacer todos los cambios
    $pdo->beginTransaction();

    // Obtener el estado del reto ANTES de insertar el libro
    // Esto para detectar si se completó un reto después
    $mesActual = (int) date('n');
    $anioActual = (int) date('Y');
    $stmtRetoAntes = $pdo->prepare(
        'SELECT conseguido
         FROM retos_mensuales
         WHERE usuario_id = ? AND mes = ? AND anio = ?
         LIMIT 1'
    );
    $stmtRetoAntes->execute([$usuarioId, $mesActual, $anioActual]);
    $retoActualAntes = $stmtRetoAntes->fetch(PDO::FETCH_ASSOC);
    $conseguidoAntes = (bool) ($retoActualAntes['conseguido'] ?? false);

    // PASO 8: Verificar qué columnas soporta la tabla biblioteca
    // (algunos sistemas antiguos podrían no tener calificacion y review)
    $stmtColCal = $pdo->query("SHOW COLUMNS FROM biblioteca LIKE 'calificacion'");
    $stmtColReview = $pdo->query("SHOW COLUMNS FROM biblioteca LIKE 'review'");
    $soportaCalificacion = $stmtColCal && $stmtColCal->fetch(PDO::FETCH_ASSOC);
    $soportaReview = $stmtColReview && $stmtColReview->fetch(PDO::FETCH_ASSOC);
    $soportaExtras = (bool) ($soportaCalificacion && $soportaReview);

    // PASO 9: INSERTAR O ACTUALIZAR EL LIBRO EN LA TABLA 'libros'
    // ON DUPLICATE KEY UPDATE: Si ya existe, actualiza; si no, inserta
    $stmtLibro = $pdo->prepare(
        'INSERT INTO libros (id_openlibrary, titulo, autores, portada)
         VALUES (:id_openlibrary, :titulo, :autores, :portada)
         ON DUPLICATE KEY UPDATE
            titulo = VALUES(titulo),
            autores = VALUES(autores),
            portada = VALUES(portada)'
    );

    $stmtLibro->execute([
        ':id_openlibrary' => $idOpenLibrary,
        ':titulo' => $titulo,
        ':autores' => $autor,
        ':portada' => $portada
    ]);

    // PASO 10: VERIFICAR SI EL LIBRO YA ESTÁ EN LA BIBLIOTECA DEL USUARIO
    $stmtBuscar = $pdo->prepare(
        'SELECT id, estado, fecha_lectura
         FROM biblioteca
         WHERE usuario_id = :usuario_id AND libro_id_openlibrary = :libro_id_openlibrary
         ORDER BY id DESC
         LIMIT 1'
    );

    $stmtBuscar->execute([
        ':usuario_id' => $usuarioId,
        ':libro_id_openlibrary' => $idOpenLibrary
    ]);

    $registroExistente = $stmtBuscar->fetch(PDO::FETCH_ASSOC);
    
    // Guardar la fecha anterior (para sincronizar retos después)
    $fechaLecturaAnterior = null;
    if ($registroExistente && ($registroExistente['estado'] ?? '') === 'leido') {
        $fechaLecturaAnterior = $registroExistente['fecha_lectura'] ?: null;
    }

    // PASO 11: ACTUALIZAR O INSERTAR EN LA TABLA 'biblioteca'
    if ($registroExistente) {
        // El libro ya existe en la biblioteca del usuario -> ACTUALIZAR
        if ($soportaExtras) {
            $stmtActualizar = $pdo->prepare(
                'UPDATE biblioteca
                 SET estado = :estado,
                     fecha_lectura = :fecha_lectura,
                     calificacion = :calificacion,
                     review = :review
                 WHERE id = :id'
            );
            $stmtActualizar->execute([
                ':estado' => $estado,
                ':fecha_lectura' => $fechaLectura,
                ':calificacion' => $calificacion,
                ':review' => $review !== '' ? $review : null,
                ':id' => (int) $registroExistente['id']
            ]);
        } else {
            $stmtActualizar = $pdo->prepare('UPDATE biblioteca SET estado = :estado, fecha_lectura = :fecha_lectura WHERE id = :id');
            $stmtActualizar->execute([
                ':estado' => $estado,
                ':fecha_lectura' => $fechaLectura,
                ':id' => (int) $registroExistente['id']
            ]);
        }
    } else {
        // El libro NO existe en la biblioteca del usuario -> INSERTAR
        if ($soportaExtras) {
            $stmtInsertar = $pdo->prepare(
                'INSERT INTO biblioteca (usuario_id, libro_id_openlibrary, estado, fecha_lectura, calificacion, review)
                 VALUES (:usuario_id, :libro_id_openlibrary, :estado, :fecha_lectura, :calificacion, :review)'
            );
            $stmtInsertar->execute([
                ':usuario_id' => $usuarioId,
                ':libro_id_openlibrary' => $idOpenLibrary,
                ':estado' => $estado,
                ':fecha_lectura' => $fechaLectura,
                ':calificacion' => $calificacion,
                ':review' => $review !== '' ? $review : null
            ]);
        } else {
            $stmtInsertar = $pdo->prepare(
                'INSERT INTO biblioteca (usuario_id, libro_id_openlibrary, estado, fecha_lectura)
                 VALUES (:usuario_id, :libro_id_openlibrary, :estado, :fecha_lectura)'
            );
            $stmtInsertar->execute([
                ':usuario_id' => $usuarioId,
                ':libro_id_openlibrary' => $idOpenLibrary,
                ':estado' => $estado,
                ':fecha_lectura' => $fechaLectura
            ]);
        }
    }

    // PASO 12: SINCRONIZAR LOGROS Y RETOS MENSUALES
    // Recalcular si se ganaron nuevos logros o si se completó un reto
    $sincronizacion = sincronizarRetosYLogrosPorCambioLibro(
        $pdo,
        $usuarioId,
        $fechaLecturaAnterior,
        $estado === 'leido' ? $fechaLectura : null
    );

    $progresoReto = $sincronizacion['reto_actual'];
    // Detectar si se completó un reto (antes no estaba completado, ahora sí)
    $retoRecienCompletado = !$conseguidoAntes && (bool) ($progresoReto['conseguido'] ?? false);
    $nuevosLogros = $sincronizacion['nuevos_logros'];

    // PASO 13: CONFIRMAR TRANSACCIÓN (aplicar todos los cambios)
    $pdo->commit();
    
    // PASO 14: RESPONDER CON ÉXITO
    echo json_encode([
        'ok' => true,
        'mensaje' => 'Libro guardado correctamente',
        'reto_recien_completado' => $retoRecienCompletado,      // ¿Se completó el reto?
        'reto_mensaje' => $retoRecienCompletado ? 'Has completado el objetivo.' : null,
        'reto_progreso' => $progresoReto,                        // Progreso del reto actual
        'nuevos_logros' => $nuevosLogros                         // Logros desbloqueados
    ]);
    
} catch (Throwable $e) {
    // ERROR: Deshacer todos los cambios
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    http_response_code(500);
    echo json_encode(['ok' => false, 'mensaje' => 'Error al guardar el libro']);
}
