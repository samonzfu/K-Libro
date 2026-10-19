<?php

/**
 * ==================== VALIDADOR DE CONTRASEÑA ====================
 * 
 * Función que VALIDA contraseñas según criterios de SEGURIDAD FUERTE
 * 
 * Se usa cuando un usuario se REGISTRA para asegurar una contraseña segura
 * 
 * REQUISITOS que debe cumplir una contraseña válida:
 * ✓ Mínimo 8 caracteres
 * ✓ Al menos una MAYÚSCULA (A-Z)
 * ✓ Al menos una minúscula (a-z)
 * ✓ Al menos un NÚMERO (0-9)
 * ✓ Al menos un CARÁCTER ESPECIAL (!@#$%^&*-_=+[]...)
 * 
 * @param string $contrasena La contraseña a validar
 * @return array Array con dos claves:
 *               - 'valida' => true si pasa todas las verificaciones, false si falla alguna
 *               - 'errores' => array con mensajes de error (vacío si todo OK)
 * 
 * EJEMPLO DE USO:
 * $resultado = validar_contrasena("MiPass123!");
 * if (!$resultado['valida']) {
 *     echo "Errores: " . implode(", ", $resultado['errores']);
 * }
 */
function validar_contrasena($contrasena) {
    // Array donde almacenamos los mensajes de error que encontremos
    $errores = [];
    // Bandera que indica si la contraseña es válida (hasta que demuestre lo contrario)
    $valida = true;

    // ========== VERIFICACIÓN 1: LONGITUD MÍNIMA ==========
    if (strlen($contrasena) < 8) {
        $errores[] = "Mínimo 8 caracteres";
        $valida = false;
    }

    // ========== VERIFICACIÓN 2: AL MENOS UNA MAYÚSCULA ==========
    if (!preg_match('/[A-Z]/', $contrasena)) {
        $errores[] = "Al menos una mayúscula";
        $valida = false;
    }

    // ========== VERIFICACIÓN 3: AL MENOS UNA MINÚSCULA ==========
    if (!preg_match('/[a-z]/', $contrasena)) {
        $errores[] = "Al menos una minúscula";
        $valida = false;
    }

    // ========== VERIFICACIÓN 4: AL MENOS UN NÚMERO ==========
    if (!preg_match('/[0-9]/', $contrasena)) {
        $errores[] = "Al menos un número";
        $valida = false;
    }

    // ========== VERIFICACIÓN 5: AL MENOS UN CARÁCTER ESPECIAL ==========
    if (!preg_match('/[!@#$%^&*\-_=+\[\]{};:\'",.<>?\/\\|`~]/', $contrasena)) {
        $errores[] = "Al menos un carácter especial (!@#$%^&*)";
        $valida = false;
    }

    // DEVOLVER RESULTADO
    return [
        'valida' => $valida,        // true si pasó todas las verificaciones
        'errores' => $errores       // array de mensajes de error (vacío si válida = true)
    ];
}

?>
