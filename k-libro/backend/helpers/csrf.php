<?php
/**
 * Protección CSRF: genera y valida el token guardado en $_SESSION['csrf_token'].
 * Requiere que la sesión esté iniciada (session_start()) antes de usarlo.
 *
 * En formularios:  <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token()) ?>">
 * En fetch/AJAX:   cabecera 'X-CSRF-Token': <?= json_encode(csrf_token()) ?>
 */

/** Devuelve el token de la sesión actual (lo crea si no existe). */
function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/** Comprueba el token recibido (campo POST 'csrf_token' o cabecera X-CSRF-Token). */
function csrf_valido(): bool
{
    $enviado  = $_POST['csrf_token'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
    $esperado = $_SESSION['csrf_token'] ?? '';
    return is_string($enviado) && $esperado !== '' && hash_equals($esperado, $enviado);
}
