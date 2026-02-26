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
    <h1> K-LIBRO</h1>
    <div class="formulario">
        <h2>Inicia Sesión</h2>
        <form method="post" action="../../backend/procesar/procesa.php">
            <div class="usuario campo">
                <input type="text" name="nombre" placeholder="Nombre de usuario" required>
            </div>
            <div class="contrasena campo">
                <input type="password" name="contrasena" placeholder="Contraseña" required>
            </div>
            <input type="hidden" name="accion" value="login">
            <input type="submit" value="Entrar">
            <div class="recordar">¿No tienes cuenta?</div>
            <div class="registrarse">
                <a href="../1_Registro/registro.php">Crear una cuenta</a>
            </div>
        </form>
    </div>
</body>
</html>