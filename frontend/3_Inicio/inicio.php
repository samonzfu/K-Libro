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
    <title>Inicio | K-Libro</title>
    <link rel="stylesheet" href="css/estilo.css">
</head>
<body>
    <h1>Bienvenido a K-Libro</h1>
    <nav>
        <a href="../4_Biblioteca/biblioteca.php">Ir a mi biblioteca</a> |
        <a href="../5_Mi_cuenta/mi_cuenta.php">Ir a mi cuenta</a> |
        <a href="../6_buscador/buscador.php">Ir al buscador</a>
    </nav>

<!-- SECCIÓN DE NOTICIAS. (ESTÁ HECHO MANUALMENTE, FALTA ARREGLARLO) -->
    <section id="noticias">
        <h2>Noticias y recomendaciones</h2>
        <?php
        require_once __DIR__ . '/../../backend/noticias.php';
        $noticias = obtenerNoticias();
        if (!empty($noticias)):
        ?>
            <ul>
                <?php foreach ($noticias as $noticia): ?>
                    <li>
                        <strong><?php echo htmlspecialchars($noticia['titulo']); ?></strong><br>
                        <span><?php echo htmlspecialchars($noticia['descripcion']); ?></span><br>
                        <a href="<?php echo htmlspecialchars($noticia['enlace']); ?>" target="_blank">Leer más</a>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php else: ?>
            <p>No hay noticias disponibles en este momento.</p>
        <?php endif; ?>
    </section>

</body>
</html>