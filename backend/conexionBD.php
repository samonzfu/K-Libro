<?php
try {
    // Volvemos a usar tu usuario k_libro y su contraseña
    $pdo = new PDO("mysql:host=localhost;dbname=k_libro;charset=utf8", "k_libro", "k_libro123$");
    
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Error de conexión: " . $e->getMessage());
}
?>