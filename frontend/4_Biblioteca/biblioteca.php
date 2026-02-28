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
    <title>Mi biblioteca | K-Libro</title>
    <link rel="stylesheet" href="css/estilo.css">
</head>
<body>
    <h1>ESTO ES TU BIBLIOTECA</h1>
    <p>Aquí aparecerán los libros que hayas añadido desde el buscador y el estado :)</p>
</body>
</html>