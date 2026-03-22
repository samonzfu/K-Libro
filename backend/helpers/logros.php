<?php

function obtenerMesAnioDeFecha(?string $fecha): ?array
{
    if (!$fecha) {
        return null;
    }

    $fechaObj = DateTime::createFromFormat('Y-m-d', $fecha);
    if (!$fechaObj || $fechaObj->format('Y-m-d') !== $fecha) {
        return null;
    }

    return [
        'mes' => (int) $fechaObj->format('n'),
        'anio' => (int) $fechaObj->format('Y')
    ];
}

function contarLibrosLeidosTotales(PDO $pdo, int $usuarioId): int
{
    $stmt = $pdo->prepare(
        "SELECT COUNT(*)
         FROM biblioteca b
         INNER JOIN (
            SELECT MAX(id) AS id
            FROM biblioteca
            WHERE usuario_id = ?
            GROUP BY libro_id_openlibrary
         ) ult ON ult.id = b.id
         WHERE b.usuario_id = ?
           AND b.estado = 'leido'"
    );
    $stmt->execute([$usuarioId, $usuarioId]);

    return (int) $stmt->fetchColumn();
}

function contarLibrosLeidosMes(PDO $pdo, int $usuarioId, int $mes, int $anio): int
{
    $stmt = $pdo->prepare(
        "SELECT COUNT(*)
         FROM biblioteca b
         INNER JOIN (
            SELECT MAX(id) AS id
            FROM biblioteca
            WHERE usuario_id = ?
            GROUP BY libro_id_openlibrary
         ) ult ON ult.id = b.id
         WHERE b.usuario_id = ?
           AND b.estado = 'leido'
           AND b.fecha_lectura IS NOT NULL
           AND MONTH(b.fecha_lectura) = ?
           AND YEAR(b.fecha_lectura) = ?"
    );
    $stmt->execute([$usuarioId, $usuarioId, $mes, $anio]);

    return (int) $stmt->fetchColumn();
}

function recalcularRetoMensual(PDO $pdo, int $usuarioId, int $mes, int $anio): ?array
{
    $stmtReto = $pdo->prepare(
        'SELECT id, meta_libros, conseguido
         FROM retos_mensuales
         WHERE usuario_id = ? AND mes = ? AND anio = ?
         LIMIT 1'
    );
    $stmtReto->execute([$usuarioId, $mes, $anio]);
    $reto = $stmtReto->fetch(PDO::FETCH_ASSOC);

    if (!$reto) {
        return null;
    }

    $librosLeidos = contarLibrosLeidosMes($pdo, $usuarioId, $mes, $anio);
    $metaLibros = max(1, (int) $reto['meta_libros']);
    $conseguido = $librosLeidos >= $metaLibros;

    if ((int) $reto['conseguido'] !== (int) $conseguido) {
        $stmtActualizar = $pdo->prepare(
            'UPDATE retos_mensuales
             SET conseguido = ?
             WHERE id = ?'
        );
        $stmtActualizar->execute([(int) $conseguido, (int) $reto['id']]);
    }

    return [
        'meta_libros' => $metaLibros,
        'conseguido' => (int) $conseguido,
        'libros_leidos' => $librosLeidos,
        'porcentaje' => (int) min(100, round(($librosLeidos / $metaLibros) * 100))
    ];
}

function sincronizarLogrosUsuario(PDO $pdo, int $usuarioId): array
{
    $nuevosLogros = [];
    $totalLeidos = contarLibrosLeidosTotales($pdo, $usuarioId);

    $stmtLogrosLectura = $pdo->prepare(
          'SELECT l.id, l.nombre
            FROM logros l
            INNER JOIN (
                SELECT MIN(id) AS id
                FROM logros
                GROUP BY nombre
            ) canon ON canon.id = l.id
            WHERE l.criterio > 0 AND l.criterio <= ?'
    );
    $stmtLogrosLectura->execute([$totalLeidos]);

    foreach ($stmtLogrosLectura->fetchAll(PDO::FETCH_ASSOC) as $logro) {
        $stmtInsertar = $pdo->prepare(
            'INSERT IGNORE INTO usuario_logros (usuario_id, logro_id)
             VALUES (?, ?)'
        );
        $stmtInsertar->execute([$usuarioId, (int) $logro['id']]);

        if ($stmtInsertar->rowCount() > 0) {
            $nuevosLogros[] = $logro['nombre'];
        }
    }

    $stmtReto = $pdo->prepare(
        'SELECT COUNT(*)
         FROM retos_mensuales
         WHERE usuario_id = ? AND conseguido = 1'
    );
    $stmtReto->execute([$usuarioId]);
    $retosConseguidos = (int) $stmtReto->fetchColumn();

    if ($retosConseguidos > 0) {
        $stmtCampeon = $pdo->prepare(
            "SELECT MIN(id) AS id, nombre
             FROM logros
             WHERE nombre = 'Campeón Mensual'
             GROUP BY nombre
             LIMIT 1"
        );
        $stmtCampeon->execute();
        $logroCampeon = $stmtCampeon->fetch(PDO::FETCH_ASSOC);

        if ($logroCampeon) {
            $stmtInsertar = $pdo->prepare(
                'INSERT IGNORE INTO usuario_logros (usuario_id, logro_id)
                 VALUES (?, ?)'
            );
            $stmtInsertar->execute([$usuarioId, (int) $logroCampeon['id']]);

            if ($stmtInsertar->rowCount() > 0) {
                $nuevosLogros[] = $logroCampeon['nombre'];
            }
        }
    }

    return $nuevosLogros;
}

function sincronizarRetosYLogrosPorCambioLibro(PDO $pdo, int $usuarioId, ?string $fechaAnterior, ?string $fechaNueva): array
{
    $mesesAfectados = [];

    foreach ([$fechaAnterior, $fechaNueva] as $fecha) {
        $mesAnio = obtenerMesAnioDeFecha($fecha);
        if (!$mesAnio) {
            continue;
        }

        $clave = $mesAnio['anio'] . '-' . $mesAnio['mes'];
        $mesesAfectados[$clave] = $mesAnio;
    }

    $resumenReto = null;
    foreach ($mesesAfectados as $mesAnio) {
        $resumen = recalcularRetoMensual($pdo, $usuarioId, $mesAnio['mes'], $mesAnio['anio']);

        $mesActual = (int) date('n');
        $anioActual = (int) date('Y');
        if ($mesAnio['mes'] === $mesActual && $mesAnio['anio'] === $anioActual) {
            $resumenReto = $resumen;
        }
    }

    $nuevosLogros = sincronizarLogrosUsuario($pdo, $usuarioId);

    return [
        'reto_actual' => $resumenReto,
        'nuevos_logros' => $nuevosLogros
    ];
}

?>