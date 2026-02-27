<?php
$conexion = mysqli_connect("localhost", "k_libro", "K_libro123@2024", "k_libro");
if (!$conexion) {
    die("Error de conexión: " . mysqli_connect_error());
}
?>