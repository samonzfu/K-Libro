<?php

/**
 * ==================== GESTIÓN DE LOGROS Y RETOS ====================
 * 
 * Este archivo contiene todas las funciones que calculan:
 * - Cuántos libros ha leído un usuario
 * - Cuántos logros/medallas ha conseguido
 * - Progreso de los retos mensuales
 */

/**
 * obtenerMesAnioDeFecha()
 * 
 * Extrae el MES y AÑO de una fecha en formato "YYYY-MM-DD"
 * 
 * @param string|null $fecha Fecha en formato "2024-03-15"
 * @return array|null Array con ['mes' => 3, 'anio' => 2024] o null si es inválida
 * 
 * EJEMPLO:
 * $resultado = obtenerMesAnioDeFecha("2024-03-15");
 * // Devuelve: ['mes' => 3, 'anio' => 2024]
 */
function obtenerMesAnioDeFecha(?string $fecha): ?array
{
    // Si no hay fecha, devolver null
    if (!$fecha) {
        return null;
    }

    // Crear objeto DateTime a partir de la fecha en formato "Y-m-d"
    $fechaObj = DateTime::createFromFormat('Y-m-d', $fecha);
    // Verificar que el formato sea correcto
    if (!$fechaObj || $fechaObj->format('Y-m-d') !== $fecha) {
        return null;
    }

    // Devolver mes (1-12) y año
    // 'n' = mes sin ceros al inicio (1-12)
    // 'Y' = año de 4 dígitos
    return [
        'mes' => (int) $fechaObj->format('n'),
        'anio' => (int) $fechaObj->format('Y')
    ];
}

/**
 * contarLibrosLeidosTotales()
 * 
 * Cuenta CUÁNTOS LIBROS HA LEÍDO UN USUARIO en toda su vida
 * (no solo este mes, sino TOTAL de libros con estado 'leido')
 * 
 * @param PDO $pdo Conexión a base de datos
 * @param int $usuarioId ID del usuario
 * @return int Número total de libros leídos
 * 
 * NOTA: Solo cuenta UN registro por libro
 * (si el usuario agregó el mismo libro varias veces, solo cuenta una)
 */
function contarLibrosLeidosTotales(PDO $pdo, int $usuarioId): int
{
    // Query SQL que:
    // 1. SELECT COUNT(*) => Contar cuántos hay
    // 2. FROM biblioteca b => De la tabla biblioteca
    // 3. INNER JOIN ... GROUP BY libro_id => Asegúrate de que solo cuentas una vez por libro
    // 4. WHERE estado = 'leido' => Solo libros que están TERMINADOS
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

    // Devolver el resultado como número entero
    return (int) $stmt->fetchColumn();
}

/**
 * contarLibrosLeidosMes()
 * 
 * Cuenta cuántos LIBROS HA LEÍDO UN USUARIO EN UN MES ESPECÍFICO
 * 
 * @param PDO $pdo Conexión a base de datos
 * @param int $usuarioId ID del usuario
 * @param int $mes Mes (1-12)
 * @param int $anio Año (ej: 2024)
 * @return int Número de libros leídos en ese mes
 * 
 * EJEMPLO:
 * $librosMarzo = contarLibrosLeidosMes($pdo, 5, 3, 2024);
 * // Libros leídos en marzo 2024 por el usuario 5
 */
function contarLibrosLeidosMes(PDO $pdo, int $usuarioId, int $mes, int $anio): int
{
    // Query SQL que:
    // 1. Cuenta libros con estado 'leido'
    // 2. Y que MONTH(fecha_lectura) = $mes -> Libros de ese mes
    // 3. Y que YEAR(fecha_lectura) = $anio -> Libros de ese año
    // 4. Verifica que fecha_lectura NO sea null (tiene fecha)
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

/**
 * recalcularRetoMensual()
 * 
 * Actualiza el PROGRESO DEL RETO MENSUAL de un usuario
 * 
 * QUÉ HACE:
 * 1. Obtiene el reto del mes especificado
 * 2. Cuenta cuántos libros ha leído ese mes
 * 3. Verifica si la meta fue CONSEGUIDA (libros leídos >= meta)
 * 4. Actualiza la base de datos si cambió el estado
 * 
 * @param PDO $pdo Conexión a base de datos
 * @param int $usuarioId ID del usuario
 * @param int $mes Mes (1-12)
 * @param int $anio Año (2024)
 * @return array|null Array con progreso del reto o null si no existe
 *                     ['meta_libros' => 5, 'conseguido' => true, 'libros_leidos' => 5, 'porcentaje' => 100]
 */
function recalcularRetoMensual(PDO $pdo, int $usuarioId, int $mes, int $anio): ?array
{
    // PASO 1: Obtener el reto del mes especificado
    $stmtReto = $pdo->prepare(
        'SELECT id, meta_libros, conseguido
         FROM retos_mensuales
         WHERE usuario_id = ? AND mes = ? AND anio = ?
         LIMIT 1'
    );
    $stmtReto->execute([$usuarioId, $mes, $anio]);
    $reto = $stmtReto->fetch(PDO::FETCH_ASSOC);

    // Si no existe reto para este mes, devolver null
    if (!$reto) {
        return null;
    }

    // PASO 2: Contar cuántos libros leyó este mes
    $librosLeidos = contarLibrosLeidosMes($pdo, $usuarioId, $mes, $anio);
    
    // PASO 3: Saber cuál era la meta (mínimo 1)
    $metaLibros = max(1, (int) $reto['meta_libros']);
    
    // PASO 4: Verificar si CONSIGUIÓ LA META
    // Conseguido = true si libros_leidos >= meta
    $conseguido = $librosLeidos >= $metaLibros;

    // PASO 5: Actualizar la BD si cambió el estado conseguido
    if ((int) $reto['conseguido'] !== (int) $conseguido) {
        $stmtActualizar = $pdo->prepare(
            'UPDATE retos_mensuales
             SET conseguido = ?
             WHERE id = ?'
        );
        $stmtActualizar->execute([(int) $conseguido, (int) $reto['id']]);
    }

    // PASO 6: Devolver el progreso del reto
    return [
        'meta_libros' => $metaLibros,                                          // Meta: 5 libros
        'conseguido' => (int) $conseguido,                                    // ¿Consiguió? 0 o 1
        'libros_leidos' => $librosLeidos,                                      // Libros reales leídos
        'porcentaje' => (int) min(100, round(($librosLeidos / $metaLibros) * 100))  // % completado (máx 100%)
    ];
}

/**
 * sincronizarLogrosUsuario()
 * 
 * Calcula y desbloquea nuevos LOGROS/MEDALLAS para el usuario
 * según sus logros de lectura
 * 
 * LOGROS INCLUYEN:
 * - Medallas de lectura por número de libros leídos (1, 5, 10, etc.)
 * - Medalla "Campeón Mensual" si completó retos mensuales
 * 
 * @param PDO $pdo Conexión a base de datos
 * @param int $usuarioId ID del usuario
 * @return array Lista de nombres de nuevos logros que se desbloquearon
 */
function sincronizarLogrosUsuario(PDO $pdo, int $usuarioId): array
{
    // Array para guardar los nuevos logros que se desbloquearon
    $nuevosLogros = [];
    
    // PASO 1: Contar total de libros leídos
    $totalLeidos = contarLibrosLeidosTotales($pdo, $usuarioId);

    // PASO 2: Obtener logros de lectura que el usuario no tiene aún
    // Por ejemplo: si leyó 10 libros, debería tener logro "10 Libros" si existe
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

    // Recorrer cada logro de lectura elegible
    foreach ($stmtLogrosLectura->fetchAll(PDO::FETCH_ASSOC) as $logro) {
        // Intentar insertar el logro en usuario_logros (INSERT IGNORE ignora si ya existe)
        $stmtInsertar = $pdo->prepare(
            'INSERT IGNORE INTO usuario_logros (usuario_id, logro_id)
             VALUES (?, ?)'
        );
        $stmtInsertar->execute([$usuarioId, (int) $logro['id']]);

        // Si se insertó (rowCount() > 0), es un nuevo logro
        if ($stmtInsertar->rowCount() > 0) {
            $nuevosLogros[] = $logro['nombre'];  // Guardar el nombre del nuevo logro
        }
    }

    // PASO 3: Verificar si tiene derecho a "Campeón Mensual"
    // (por completar retos mensuales)
    $stmtReto = $pdo->prepare(
        'SELECT COUNT(*)
         FROM retos_mensuales
         WHERE usuario_id = ? AND conseguido = 1'
    );
    $stmtReto->execute([$usuarioId]);
    $retosConseguidos = (int) $stmtReto->fetchColumn();

    // Si completó al menos 1 reto mensual
    if ($retosConseguidos > 0) {
        // Obtener el logro "Campeón Mensual"
        $stmtCampeon = $pdo->prepare(
            "SELECT MIN(id) AS id, nombre
             FROM logros
             WHERE nombre = 'Campeón Mensual'
             GROUP BY nombre
             LIMIT 1"
        );
        $stmtCampeon->execute();
        $logroCampeon = $stmtCampeon->fetch(PDO::FETCH_ASSOC);

        // Si existe el logro, intentar asignarlo
        if ($logroCampeon) {
            $stmtInsertar = $pdo->prepare(
                'INSERT IGNORE INTO usuario_logros (usuario_id, logro_id)
                 VALUES (?, ?)'
            );
            $stmtInsertar->execute([$usuarioId, (int) $logroCampeon['id']]);

            // Si se insertó, añadir a nuevos logros
            if ($stmtInsertar->rowCount() > 0) {
                $nuevosLogros[] = $logroCampeon['nombre'];
            }
        }
    }

    // Devolver lista de nuevos logros desbloqueados
    return $nuevosLogros;
}

/**
 * sincronizarRetosYLogrosPorCambioLibro()
 * 
 * Cuando un usuario CAMBIA UN LIBRO (ej: lo marca como "leído"),
 * esta función ACTUALIZA todos los logros y retos afectados
 * 
 * QUÉ HACE:
 * 1. Identifica qué meses fueron afectados (fecha anterior y nueva)
 * 2. Recalcula los retos mensuales para esos meses
 * 3. Sincroniza todos los logros del usuario
 * 4. Devuelve el progreso del reto del mes actual
 * 
 * @param PDO $pdo Conexión a base de datos
 * @param int $usuarioId ID del usuario
 * @param string|null $fechaAnterior Fecha anterior del libro (ej: "2024-02-01")
 * @param string|null $fechaNueva Fecha nueva del libro (ej: "2024-03-15")
 * @return array ['reto_actual' => [...], 'nuevos_logros' => [...]]\n */
function sincronizarRetosYLogrosPorCambioLibro(PDO $pdo, int $usuarioId, ?string $fechaAnterior, ?string $fechaNueva): array
{
    // Identificar todos los meses que fueron afectados
    // (puede ser la fecha anterior, la nueva, o ambas)
    $mesesAfectados = [];

    foreach ([$fechaAnterior, $fechaNueva] as $fecha) {
        // Extraer mes y año de la fecha
        $mesAnio = obtenerMesAnioDeFecha($fecha);
        if (!$mesAnio) {
            continue;
        }

        // Usar una clave única para cada mes
        $clave = $mesAnio['anio'] . '-' . $mesAnio['mes'];
        $mesesAfectados[$clave] = $mesAnio;
    }

    // Recalcular retos para todos los meses afectados
    $resumenReto = null;
    foreach ($mesesAfectados as $mesAnio) {
        $resumen = recalcularRetoMensual($pdo, $usuarioId, $mesAnio['mes'], $mesAnio['anio']);

        // Guardar el resumen del mes ACTUAL si es ese mes
        $mesActual = (int) date('n');
        $anioActual = (int) date('Y');
        if ($mesAnio['mes'] === $mesActual && $mesAnio['anio'] === $anioActual) {
            $resumenReto = $resumen;
        }
    }

    // Sincronizar todos los logros del usuario
    $nuevosLogros = sincronizarLogrosUsuario($pdo, $usuarioId);

    // Devolver resumen del reto actual y nuevos logros
    return [
        'reto_actual' => $resumenReto,      // Progreso del reto de este mes
        'nuevos_logros' => $nuevosLogros    // Logros que se desbloquearon
    ];
}"

?>