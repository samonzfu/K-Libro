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
    <h1 data-i18n="inicio-h1">Bienvenido a K-Libro</h1>
    <nav>
        <a href="../4_Biblioteca/biblioteca.php" data-i18n="nav-biblioteca">Ir a mi biblioteca</a> |
        <a href="../5_Mi_cuenta/mi_cuenta.php" data-i18n="nav-cuenta">Ir a mi cuenta</a> |
        <a href="../6_buscador/buscador.php" data-i18n="nav-buscador">Ir al buscador</a>
        <button id="btn-lang" class="btn-lang">🌐 English</button>
    </nav>

<!-- SECCIÓN DE NOTICIAS. (ESTÁ HECHO MANUALMENTE, FALTA ARREGLARLO) -->
    <section id="noticias">
        <h2 data-i18n="noticias-titulo">Noticias y recomendaciones</h2>
        <?php
        require_once __DIR__ . '/../../backend/noticias.php';
        $noticias = obtenerNoticias();
        if (!empty($noticias)):
        ?>
            <ul>
                <?php foreach ($noticias as $noticia): ?>
                    <li>
                        <strong
                            class="news-title"
                            data-title-es="<?php echo htmlspecialchars($noticia['titulo']); ?>"
                            data-title-en="<?php echo htmlspecialchars($noticia['titulo_en'] ?? $noticia['titulo']); ?>"
                        ><?php echo htmlspecialchars($noticia['titulo']); ?></strong><br>
                        <span
                            class="news-description"
                            data-description-es="<?php echo htmlspecialchars($noticia['descripcion']); ?>"
                            data-description-en="<?php echo htmlspecialchars($noticia['descripcion_en'] ?? $noticia['descripcion']); ?>"
                        ><?php echo htmlspecialchars($noticia['descripcion']); ?></span><br>
                        <a href="<?php echo htmlspecialchars($noticia['enlace']); ?>" target="_blank" data-i18n="leer-mas">Leer más</a>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php else: ?>
            <p data-i18n="sin-noticias">No hay noticias disponibles en este momento.</p>
        <?php endif; ?>
    </section>

    <script src="../js/i18n.js"></script>
    <script>
    function updateNewsLanguage(lang) {
        const isEnglish = lang === 'en';

        document.querySelectorAll('.news-title').forEach((el) => {
            el.textContent = isEnglish ? (el.dataset.titleEn || el.dataset.titleEs || '') : (el.dataset.titleEs || '');
        });

        document.querySelectorAll('.news-description').forEach((el) => {
            el.textContent = isEnglish
                ? (el.dataset.descriptionEn || el.dataset.descriptionEs || '')
                : (el.dataset.descriptionEs || '');
        });
    }

    // Hook para aplicar traducción también en los bloques de noticias dinámicas.
    const originalSetLang = I18n.setLang.bind(I18n);
    I18n.setLang = function(lang, t) {
        originalSetLang(lang, t);
        updateNewsLanguage(lang);
    };

    I18n.init({
        es: {
            'inicio-h1':       'Bienvenido a K-Libro',
            'nav-biblioteca':  'Ir a mi biblioteca',
            'nav-cuenta':      'Ir a mi cuenta',
            'nav-buscador':    'Ir al buscador',
            'noticias-titulo': 'Noticias y recomendaciones',
            'leer-mas':        'Leer más',
            'sin-noticias':    'No hay noticias disponibles en este momento.',
        },
        en: {
            'inicio-h1':       'Welcome to K-Libro',
            'nav-biblioteca':  'Go to my library',
            'nav-cuenta':      'Go to my account',
            'nav-buscador':    'Go to search',
            'noticias-titulo': 'News & recommendations',
            'leer-mas':        'Read more',
            'sin-noticias':    'No news available at this time.',
        }
    }, 'Inicio | K-Libro', 'Home | K-Libro');
    </script>

</body>
</html>