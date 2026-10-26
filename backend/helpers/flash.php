<?php
/**
 * Mensajes "flash": se guardan en la sesión y se muestran como aviso en la siguiente página.
 *
 *   flash_set('error', 'Texto en español', 'Text in English');
 *   <?= flash_render() ?>      // al final de la página (necesita js/notify.js)
 *
 * Tipos: success | error | warning | info
 */

function flash_set(string $tipo, string $es, string $en = ''): void
{
    $_SESSION['flash'][] = ['tipo' => $tipo, 'es' => $es, 'en' => $en !== '' ? $en : $es];
}

/** Devuelve (y borra) los valores que el usuario había escrito, para no perderlos tras un error. */
function flash_old(): array
{
    $old = $_SESSION['flash_old'] ?? [];
    unset($_SESSION['flash_old']);
    return is_array($old) ? $old : [];
}

/** Devuelve el <script> que muestra los avisos pendientes (y los borra de la sesión). */
function flash_render(): string
{
    if (empty($_SESSION['flash']) || !is_array($_SESSION['flash'])) {
        return '';
    }
    $items = $_SESSION['flash'];
    unset($_SESSION['flash']);

    $json = json_encode($items, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE);
    return "<script>document.addEventListener('DOMContentLoaded',function(){(" . $json
        . ").forEach(function(f){Notify.show({es:f.es,en:f.en},{type:f.tipo});});});</script>\n";
}
