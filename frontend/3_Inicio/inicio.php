<?php
session_start();
if (empty($_SESSION['user_id'])) {
    header('Location: ../2_Login/login.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inicio | K-Libro</title>
</head>
<body>
    <h1>ESTO ES EL INICIO</h1>
</body>
</html>