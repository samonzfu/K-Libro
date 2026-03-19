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

$idOpenLibrary = trim($_POST['id_openlibrary'] ?? '');
$titulo = trim($_POST['titulo'] ?? '');
$autor = trim($_POST['autor'] ?? 'Autor desconocido');
$portada = trim($_POST['portada'] ?? '');
$estado = trim($_POST['estado'] ?? '');

$estadosPermitidos = ['pendiente', 'leyendo', 'leido'];

if ($idOpenLibrary === '' || $titulo === '' || !in_array($estado, $estadosPermitidos, true)) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'mensaje' => 'Datos incompletos o estado invalido']);
    exit;
}

$usuarioId = (int) $_SESSION['user_id'];

try {
    $pdo->beginTransaction();

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
        'SELECT id
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

    if ($registroExistente) {
        $stmtActualizar = $pdo->prepare('UPDATE biblioteca SET estado = :estado WHERE id = :id');
        $stmtActualizar->execute([
            ':estado' => $estado,
            ':id' => (int) $registroExistente['id']
        ]);
    } else {
        $stmtInsertar = $pdo->prepare(
            'INSERT INTO biblioteca (usuario_id, libro_id_openlibrary, estado)
             VALUES (:usuario_id, :libro_id_openlibrary, :estado)'
        );
        $stmtInsertar->execute([
            ':usuario_id' => $usuarioId,
            ':libro_id_openlibrary' => $idOpenLibrary,
            ':estado' => $estado
        ]);
    }

    $pdo->commit();
    echo json_encode(['ok' => true, 'mensaje' => 'Libro guardado correctamente']);
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    http_response_code(500);
    echo json_encode(['ok' => false, 'mensaje' => 'Error al guardar el libro']);
}
