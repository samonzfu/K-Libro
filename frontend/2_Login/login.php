<?php
session_start();
if (!empty($_SESSION['user_id'])) {
    header('Location: ../3_Inicio/inicio.php');
    exit;
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | K-Libro</title>
    <link rel="stylesheet" href="css/estilo.css">
</head>
<body>
    <button id="btn-lang" class="btn-lang">🌐 English</button>
    <h1>K-LIBRO</h1>
    <div class="formulario">
        <h2 data-i18n="login-h2">Inicia Sesión</h2>
        <form method="post" action="../../backend/procesar/procesa.php">
            <div class="usuario campo">
                <input type="text" name="nombre" data-i18n-ph="ph-nombre" placeholder="Nombre de usuario" required>
            </div>
            <div class="contrasena campo">
                <input type="password" name="contrasena" data-i18n-ph="ph-contrasena" placeholder="Contraseña" required>
            </div>
            <input type="hidden" name="accion" value="login">
            <input type="submit" data-i18n-val="login-submit" value="Entrar">
            <div class="recordar" data-i18n="login-recordar">¿No tienes cuenta?</div>
            <div class="registrarse">
                <a href="../1_Registro/registro.php" data-i18n="login-crear">Crear una cuenta</a>
            </div>
        </form>
    </div>
    <script src="../js/i18n.js"></script>
    <script>
    I18n.init({
        es: {
            'login-h2':       'Inicia Sesión',
            'ph-nombre':      'Nombre de usuario',
            'ph-contrasena':  'Contraseña',
            'login-submit':   'Entrar',
            'login-recordar': '¿No tienes cuenta?',
            'login-crear':    'Crear una cuenta',
        },
        en: {
            'login-h2':       'Sign In',
            'ph-nombre':      'Username',
            'ph-contrasena':  'Password',
            'login-submit':   'Enter',
            'login-recordar': "Don't have an account?",
            'login-crear':    'Create an account',
        }
    }, 'Login | K-Libro', 'Login | K-Libro');
    </script>
</body>
</html>