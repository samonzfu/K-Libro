<?php
session_start();
require_once __DIR__ . '/../../../backend/helpers/flash.php';
$old = flash_old();
if (!empty($_SESSION['user_id'])) {
    header('Location: ../inicio/inicio.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registro | K-Libro</title>
    <link rel="icon" type="image/png" href="../../assets/img/icono.png">
    <link rel="stylesheet" href="../../css/theme.css?v=<?= @filemtime(__DIR__ . '/../../css/theme.css') ?: time() ?>">
    <link rel="stylesheet" href="../../css/ui.css?v=<?= @filemtime(__DIR__ . '/../../css/ui.css') ?: time() ?>">
    <script src="../../js/theme.js?v=<?= @filemtime(__DIR__ . '/../../js/theme.js') ?: time() ?>"></script>
    <script src="../../js/notify.js?v=<?= @filemtime(__DIR__ . '/../../js/notify.js') ?: time() ?>"></script>
    <link rel="stylesheet" href="../../css/registro.css?v=<?= @filemtime(__DIR__ . '/../../css/registro.css') ?: time() ?>">
</head>
<body>
    <div class="barra-sup">
        <button id="btn-theme" class="btn-lang btn-theme" type="button">☀️ Claro</button>
        <button id="btn-lang" class="btn-lang">🌐 English</button>
    </div>
    <h1>K-Libro</h1>
    <div class="formulario">
        <h2 data-i18n="reg-h2">Crear una cuenta</h2>
        <form method="post" action="../../../backend/actions/auth.php">
            <div class="nombre campo">
                <input type="text" name="nombre" maxlength="50" autocomplete="username" value="<?php echo htmlspecialchars($old['nombre'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" data-i18n-ph="ph-usuario" placeholder="Usuario" required>
            </div>
            <div class="contrasena campo">
                <div class="input-pass">
                    <input type="password" name="contrasena" id="contrasena" autocomplete="new-password" data-i18n-ph="ph-contrasena" placeholder="Contraseña" required oninput="validarContrasena()">
                    <button type="button" class="btn-ojo" data-target="contrasena" aria-label="Mostrar contraseña" aria-pressed="false"><svg class="ojo-abierto" viewBox="0 0 24 24" aria-hidden="true"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg><svg class="ojo-cerrado" viewBox="0 0 24 24" aria-hidden="true"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/><path d="M1 1l22 22"/></svg></button>
                </div>
                <div id="requisitos" style="display: none;font-size: 0.9em; margin-top: 10px; color: black;">
                    <p><strong data-i18n="req-titulo">Requisitos de contraseña:</strong></p>
                    <ul style="margin: 5px 0; padding-left: 20px;">
                        <li id="req-longitud" style="color: white;" data-i18n="req-longitud-txt">Mínimo 8 caracteres</li>
                        <li id="req-mayuscula" style="color: white;" data-i18n="req-mayuscula-txt">Una mayúscula</li>
                        <li id="req-minuscula" style="color: white;" data-i18n="req-minuscula-txt">Una minúscula</li>
                        <li id="req-numero" style="color: white;" data-i18n="req-numero-txt">Un número</li>
                        <li id="req-especial" style="color: white;" data-i18n="req-especial-txt">Un carácter especial (!@#$%^&*)</li>
                    </ul>
                </div>
            </div>
            <div class="correo campo">
                <input type="email" name="correo" maxlength="100" autocomplete="email" value="<?php echo htmlspecialchars($old['correo'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" data-i18n-ph="ph-correo" placeholder="Correo" required>
            </div>
            <input type="hidden" name="accion" value="registro">
            <input type="submit" data-i18n-val="reg-submit" value="Insertar">
            <div class="recordar" data-i18n="registro-iniciar">¿Ya tienes una cuenta?</div>
            <div class="iniciar-sesion">
                <a href="../login/login.php" data-i18n="registro-log">Iniciar sesión</a>
            </div>
        </form>
    </div>
    <script>
        function validarContrasena(){
            const contrasena = document.getElementById('contrasena').value;
            const requisitos = document.getElementById('requisitos');

            // Mostrar requisitos si hay algo escrito:
            if(contrasena.length > 0){
                requisitos.style.display = 'block';
            }else{
                requisitos.style.display = 'none';
            }

            // Validar cada requisito:
            const validaciones = {
                'req-longitud' : contrasena.length >=8,
                'req-mayuscula' : /[A-Z]/.test(contrasena),
                'req-minuscula' : /[a-z]/.test(contrasena),
                'req-numero' : /[0-9]/.test(contrasena),
                'req-especial' : /[!@#$%^&*\-_=+\[\]{};:\'",.<>?\/\\|`~]/.test(contrasena)
            };

            // Actualizar colores de requisitos:
            for (const [id, valido] of Object.entries(validaciones)){
                const elemento = document.getElementById(id);
                if (valido){
                    elemento.style.color = 'green';
                }else{
                    elemento.style.color = 'gray';
                }
            }

            // Habilitar/deshabilitar botón de envío:
            const allValid = Object.values(validaciones).every(v => v);
            document.querySelector('input[type="submit"]').disabled = !allValid;
        }
    </script>
    <script src="../../js/i18n.js?v=<?= @filemtime(__DIR__ . '/../../js/i18n.js') ?: time() ?>"></script>
    <script src="../../js/password-toggle.js?v=<?= @filemtime(__DIR__ . '/../../js/password-toggle.js') ?: time() ?>"></script>
    <script>
    I18n.init({
        es: {
            'reg-h2':            'Crear una cuenta',
            'ph-usuario':        'Usuario',
            'ph-contrasena':     'Contraseña',
            'ph-correo':         'Correo',
            'reg-submit':        'Insertar',
            'registro-iniciar':  '¿Ya tienes una cuenta?',
            'registro-log':      'Iniciar sesión',
            'req-titulo':        'Requisitos de contraseña:',
            'req-longitud-txt':  'Mínimo 8 caracteres',
            'req-mayuscula-txt': 'Una mayúscula',
            'req-minuscula-txt': 'Una minúscula',
            'req-numero-txt':    'Un número',
            'req-especial-txt':  'Un carácter especial (!@#$%^&*)',
        },
        en: {
            'reg-h2':            'Create an account',
            'ph-usuario':        'Username',
            'ph-contrasena':     'Password',
            'ph-correo':         'Email',
            'reg-submit':        'Register',
            'registro-iniciar':  'Do you already have an account?',
            'registro-log':      'Login',
            'req-titulo':        'Password requirements:',
            'req-longitud-txt':  'Minimum 8 characters',
            'req-mayuscula-txt': 'One uppercase letter',
            'req-minuscula-txt': 'One lowercase letter',
            'req-numero-txt':    'One number',
            'req-especial-txt':  'One special character (!@#$%^&*)',
        }
    }, 'Registro | K-Libro', 'Register | K-Libro');
    </script>

    <?= flash_render() ?>
</body>
</html>