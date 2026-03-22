<?php

session_start();
header('Content-Type: application/json; charset=utf-8');

if (empty($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['ok' => false, 'mensaje' => 'Sesion no iniciada']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'mensaje' => 'Metodo no permitido']);
    exit;
}

include '../conexionBD.php';
require_once '../helpers/biblioteca_schema.php';
require_once '../helpers/logros.php';

asegurarColumnaFechaLectura($pdo);

$idOpenLibrary = trim($_POST['id_openlibrary'] ?? '');
$titulo = trim($_POST['titulo'] ?? '');
$autor = trim($_POST['autor'] ?? 'Autor desconocido');
$portada = trim($_POST['portada'] ?? '');
$estado = trim($_POST['estado'] ?? '');
$fechaLecturaRaw = trim((string) ($_POST['fecha_lectura'] ?? ''));
$calificacionRaw = trim((string) ($_POST['calificacion'] ?? ''));
$review = trim((string) ($_POST['review'] ?? ''));

$estadosPermitidos = ['pendiente', 'leyendo', 'leido'];

if ($idOpenLibrary === '' || $titulo === '' || !in_array($estado, $estadosPermitidos, true)) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'mensaje' => 'Datos incompletos o estado invalido']);
    exit;
}

$calificacion = null;
$fechaLectura = null;

if ($estado === 'leido') {
    if ($fechaLecturaRaw === '') {
        http_response_code(400);
        echo json_encode(['ok' => false, 'mensaje' => 'Debes indicar cuando leiste el libro']);
        exit;
    }

    $fechaLecturaObj = DateTime::createFromFormat('Y-m-d', $fechaLecturaRaw);
    $fechaValida = $fechaLecturaObj && $fechaLecturaObj->format('Y-m-d') === $fechaLecturaRaw;

    if (!$fechaValida) {
        http_response_code(400);
        echo json_encode(['ok' => false, 'mensaje' => 'La fecha de lectura no es valida']);
        exit;
    }

    $hoy = new DateTimeImmutable('today');
    if ($fechaLecturaObj > $hoy) {
        http_response_code(400);
        echo json_encode(['ok' => false, 'mensaje' => 'La fecha de lectura no puede estar en el futuro']);
        exit;
    }

    $fechaLectura = $fechaLecturaRaw;
}

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

if ($estado !== 'leido') {
    $review = '';
}

$reviewLength = function_exists('mb_strlen') ? mb_strlen($review, 'UTF-8') : strlen($review);
if ($reviewLength > 2000) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'mensaje' => 'La reseña es demasiado larga']);
    exit;
}

$usuarioId = (int) $_SESSION['user_id'];

try {
    $pdo->beginTransaction();

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

    $stmtColCal = $pdo->query("SHOW COLUMNS FROM biblioteca LIKE 'calificacion'");
    $stmtColReview = $pdo->query("SHOW COLUMNS FROM biblioteca LIKE 'review'");
    $soportaCalificacion = $stmtColCal && $stmtColCal->fetch(PDO::FETCH_ASSOC);
    $soportaReview = $stmtColReview && $stmtColReview->fetch(PDO::FETCH_ASSOC);
    $soportaExtras = (bool) ($soportaCalificacion && $soportaReview);

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
    $fechaLecturaAnterior = null;
    if ($registroExistente && ($registroExistente['estado'] ?? '') === 'leido') {
        $fechaLecturaAnterior = $registroExistente['fecha_lectura'] ?: null;
    }

    if ($registroExistente) {
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

    $sincronizacion = sincronizarRetosYLogrosPorCambioLibro(
        $pdo,
        $usuarioId,
        $fechaLecturaAnterior,
        $estado === 'leido' ? $fechaLectura : null
    );

    $progresoReto = $sincronizacion['reto_actual'];
    $retoRecienCompletado = !$conseguidoAntes && (bool) ($progresoReto['conseguido'] ?? false);
    $nuevosLogros = $sincronizacion['nuevos_logros'];

    $pdo->commit();
    echo json_encode([
        'ok' => true,
        'mensaje' => 'Libro guardado correctamente',
        'reto_recien_completado' => $retoRecienCompletado,
        'reto_mensaje' => $retoRecienCompletado ? 'Has completado el objetivo.' : null,
        'reto_progreso' => $progresoReto,
        'nuevos_logros' => $nuevosLogros
    ]);
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    http_response_code(500);
    echo json_encode(['ok' => false, 'mensaje' => 'Error al guardar el libro']);
}
