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
    <title>Login | K-Libro</title>
    <link rel="icon" type="image/png" href="../../assets/img/icono.png">
    <link rel="stylesheet" href="../../css/theme.css?v=<?= @filemtime(__DIR__ . '/../../css/theme.css') ?: time() ?>">
    <link rel="stylesheet" href="../../css/ui.css?v=<?= @filemtime(__DIR__ . '/../../css/ui.css') ?: time() ?>">
    <script src="../../js/theme.js?v=<?= @filemtime(__DIR__ . '/../../js/theme.js') ?: time() ?>"></script>
    <script src="../../js/notify.js?v=<?= @filemtime(__DIR__ . '/../../js/notify.js') ?: time() ?>"></script>
    <link rel="stylesheet" href="../../css/login.css?v=<?= @filemtime(__DIR__ . '/../../css/login.css') ?: time() ?>">
</head>
<body>
    <div class="barra-sup">
        <button id="btn-theme" class="btn-lang btn-theme" type="button">☀️ Claro</button>
        <button id="btn-lang" class="btn-lang">🌐 English</button>
    </div>
    <h1>K-LIBRO</h1>
    <div class="formulario">
        <h2 data-i18n="login-h2">Inicia Sesión</h2>
        <form method="post" action="../../../backend/actions/auth.php">
            <div class="usuario campo">
                <input type="text" name="nombre" autocomplete="username" value="<?php echo htmlspecialchars($old['nombre'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" data-i18n-ph="ph-nombre" placeholder="Nombre de usuario" required>
            </div>
            <div class="contrasena campo">
                <div class="input-pass">
                    <input type="password" name="contrasena" id="contrasena" autocomplete="current-password" data-i18n-ph="ph-contrasena" placeholder="Contraseña" required>
                    <button type="button" class="btn-ojo" data-target="contrasena" aria-label="Mostrar contraseña" aria-pressed="false"><svg class="ojo-abierto" viewBox="0 0 24 24" aria-hidden="true"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg><svg class="ojo-cerrado" viewBox="0 0 24 24" aria-hidden="true"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/><path d="M1 1l22 22"/></svg></button>
                </div>
            </div>
            <input type="hidden" name="accion" value="login">
            <input type="submit" data-i18n-val="login-submit" value="Entrar">
            <div class="recordar" data-i18n="login-recordar">¿No tienes cuenta?</div>
            <div class="registrarse">
                <a href="../registro/registro.php" data-i18n="login-crear">Crear una cuenta</a>
            </div>
        </form>
    </div>
    <script src="../../js/i18n.js?v=<?= @filemtime(__DIR__ . '/../../js/i18n.js') ?: time() ?>"></script>
    <script src="../../js/password-toggle.js?v=<?= @filemtime(__DIR__ . '/../../js/password-toggle.js') ?: time() ?>"></script>
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
    <?= flash_render() ?>
</body>
</html>