<?php
$conexion = mysqli_connect("localhost", "k_libro", "k_libro123$", "k_libro");
if (!$conexion) {
    die("Error de conexión: " . mysqli_connect_error());
}
?>