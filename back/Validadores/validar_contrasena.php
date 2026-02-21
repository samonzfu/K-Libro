<?php

/**
 * Valida una contraseña según criterios de seguridad
 * 
 * Requisitos:
 * - Mínimo 8 caracteres
 * - Al menos una mayúscula
 * - Al menos una minúscula
 * - Al menos un número
 * - Al menos un carácter especial (!@#$%^&*)
 * 
 * @param string $contrasena La contraseña a validar
 * @return array Array con 'valida' (bool) y 'errores' (array de mensajes)
 */
function validar_contrasena($contrasena) {
    $errores = [];
    $valida = true;

    // Validar longitud mínima
    if (strlen($contrasena) < 8) {
        $errores[] = "Mínimo 8 caracteres";
        $valida = false;
    }

    // Validar mayúscula
    if (!preg_match('/[A-Z]/', $contrasena)) {
        $errores[] = "Al menos una mayúscula";
        $valida = false;
    }

    // Validar minúscula
    if (!preg_match('/[a-z]/', $contrasena)) {
        $errores[] = "Al menos una minúscula";
        $valida = false;
    }

    // Validar número
    if (!preg_match('/[0-9]/', $contrasena)) {
        $errores[] = "Al menos un número";
        $valida = false;
    }

    // Validar carácter especial
    if (!preg_match('/[!@#$%^&*\-_=+\[\]{};:\'",.<>?\/\\|`~]/', $contrasena)) {
        $errores[] = "Al menos un carácter especial (!@#$%^&*)";
        $valida = false;
    }

    return [
        'valida' => $valida,
        'errores' => $errores
    ];
}

?>
