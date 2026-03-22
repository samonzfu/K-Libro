<?php

function asegurarColumnaFechaLectura(PDO $pdo): void
{
    static $verificada = false;

    if ($verificada) {
        return;
    }

    $stmt = $pdo->query("SHOW COLUMNS FROM biblioteca LIKE 'fecha_lectura'");
    $existe = $stmt && $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$existe) {
        $pdo->exec('ALTER TABLE biblioteca ADD COLUMN fecha_lectura DATE NULL AFTER estado');
    }

    $verificada = true;
}

?>