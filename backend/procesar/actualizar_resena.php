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

$idOpenLibrary = trim((string) ($_POST['id_openlibrary'] ?? ''));
$calificacionRaw = trim((string) ($_POST['calificacion'] ?? ''));
$review = trim((string) ($_POST['review'] ?? ''));

if ($idOpenLibrary === '') {
    http_response_code(400);
    echo json_encode(['ok' => false, 'mensaje' => 'Falta el identificador del libro']);
    exit;
}

$calificacion = null;
if ($calificacionRaw !== '') {
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

$reviewLength = function_exists('mb_strlen') ? mb_strlen($review, 'UTF-8') : strlen($review);
if ($reviewLength > 2000) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'mensaje' => 'La reseña es demasiado larga']);
    exit;
}

$usuarioId = (int) $_SESSION['user_id'];

try {
    $stmtColCal = $pdo->query("SHOW COLUMNS FROM biblioteca LIKE 'calificacion'");
    $stmtColReview = $pdo->query("SHOW COLUMNS FROM biblioteca LIKE 'review'");
    $soportaCalificacion = $stmtColCal && $stmtColCal->fetch(PDO::FETCH_ASSOC);
    $soportaReview = $stmtColReview && $stmtColReview->fetch(PDO::FETCH_ASSOC);

    if ($soportaCalificacion && $soportaReview) {
        $stmt = $pdo->prepare(
            'UPDATE biblioteca
             SET calificacion = :calificacion,
                 review = :review
             WHERE usuario_id = :usuario_id
               AND libro_id_openlibrary = :libro_id_openlibrary
             ORDER BY id DESC
             LIMIT 1'
        );

        $stmt->execute([
            ':calificacion' => $calificacion,
            ':review' => $review !== '' ? $review : null,
            ':usuario_id' => $usuarioId,
            ':libro_id_openlibrary' => $idOpenLibrary
        ]);

        if ($stmt->rowCount() === 0) {
            http_response_code(404);
            echo json_encode(['ok' => false, 'mensaje' => 'No se encontro el libro en tu biblioteca']);
            exit;
        }
    }
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'mensaje' => 'Error al guardar los detalles']);
    exit;
}

echo json_encode(['ok' => true, 'mensaje' => 'Detalles guardados correctamente']);
exit;
