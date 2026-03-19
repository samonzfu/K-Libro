<?php
session_start();
if (!empty($_SESSION['user_id'])) {
    header('Location: /GitHub/K-Libro/frontend/3_Inicio/inicio.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registro | K-Libro</title>
    <link rel="stylesheet" href="css/estilo.css">
</head>
<body>
    <button id="btn-lang" class="btn-lang">🌐 English</button>
    <h1>K-Libro</h1>
    <div class="formulario">
        <h2 data-i18n="reg-h2">Crear una cuenta</h2>
        <form method="post" action="../../backend/procesar/procesa.php">
            <div class="nombre campo">
                <input type="text" name="nombre" data-i18n-ph="ph-usuario" placeholder="Usuario" required>
            </div>
            <div class="contrasena campo">
                <input type="password" name="contrasena" id="contrasena" data-i18n-ph="ph-contrasena" placeholder="Contraseña" required oninput="validarContrasena()">
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
                <input type="text" name="correo" data-i18n-ph="ph-correo" placeholder="Correo" required>
            </div>
            <input type="hidden" name="accion" value="registro">
            <input type="submit" data-i18n-val="reg-submit" value="Insertar">
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
    <script src="../js/i18n.js"></script>
    <script>
    I18n.init({
        es: {
            'reg-h2':            'Crear una cuenta',
            'ph-usuario':        'Usuario',
            'ph-contrasena':     'Contraseña',
            'ph-correo':         'Correo',
            'reg-submit':        'Insertar',
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
            'req-titulo':        'Password requirements:',
            'req-longitud-txt':  'Minimum 8 characters',
            'req-mayuscula-txt': 'One uppercase letter',
            'req-minuscula-txt': 'One lowercase letter',
            'req-numero-txt':    'One number',
            'req-especial-txt':  'One special character (!@#$%^&*)',
        }
    }, 'Registro | K-Libro', 'Register | K-Libro');
    </script>

</body>
</html>