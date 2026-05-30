<?php
session_start();
if (empty($_SESSION['user_id'])) {
    header('Location: ../login/login.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inicio | K-Libro</title>
    <link rel="stylesheet" href="../../css/inicio.css">
</head>
<body>
    <h1 data-i18n="inicio-h1">K-Libro</h1>
    <nav>
        <a href="../inicio/inicio.php" data-i18n="nav-inicio">Inicio</a>
        <a href="../biblioteca/biblioteca.php" data-i18n="nav-biblioteca">Biblioteca</a> 
        <a href="../mi_cuenta/mi_cuenta.php" data-i18n="nav-cuenta">Mi cuenta</a> 
        <a href="../buscador/buscador.php" data-i18n="nav-buscador">Buscador</a>
        <?php if (!empty($_SESSION['rol']) && in_array($_SESSION['rol'], ['admin', 'moderador'], true)): ?>
            | <a href="../admin/panel_admin.php" style="color: #e74c3c; font-weight: bold;">Panel Admin</a>
        <?php endif; ?>
        <button id="btn-lang" class="btn-lang">🌐 English</button>
    </nav>

<!-- SECCIÓN DE NOTICIAS. (ESTÁ HECHO MANUALMENTE, FALTA ARREGLARLO) -->
    <section id="noticias">
        <h2 data-i18n="noticias-titulo">Recomendaciones</h2>
        <?php
        require_once __DIR__ . '/../../../backend/noticias.php';
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
                        <a href="<?php echo htmlspecialchars($noticia['enlace']); ?>" target="_blank" data-i18n="enlace">Enlace al contenido</a>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php else: ?>
            <p data-i18n="sin-noticias">No hay noticias disponibles en este momento.</p>
        <?php endif; ?>
    </section>

    <script src="../../js/i18n.js"></script>
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
            'inicio-h1':       'K-Libro',
            'nav-inicio':        'Inicio',
            'nav-biblioteca':  'Biblioteca',
            'nav-cuenta':      'Mi cuenta',
            'nav-buscador':    'Buscador',
            'noticias-titulo': 'Noticias y recomendaciones',
            'enlace':          'Enlace al contenido',
            'sin-noticias':    'No hay noticias disponibles en este momento.',
        },
        en: {
            'inicio-h1':       'K-Libro',
            'nav-inicio':        'Home',
            'nav-biblioteca':  'Library',
            'nav-inicio':        'Home',
            'nav-cuenta':      'My Account',
            'nav-buscador':    'Search',
            'noticias-titulo': 'News & recommendations',
            'enlace':          'Content link',
            'sin-noticias':    'No news available at this time.',
        }
    }, 'Inicio | K-Libro', 'Home | K-Libro');
    </script>

</body>
</html>