<?php
session_start();
if (empty($_SESSION['user_id'])) {
    header('Location: /GitHub/K-Libro/frontend/2_Login/login.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mi cuenta | K-Libro</title>
    <link rel="stylesheet" href="css/estilo.css">
</head>
<body>
    <h1>Mi cuenta de usuario</h1>
    <p>Aquí podremos cerrar sesión, ver la info de nuestra cuenta y los logros.</p>
</body>
</html>
