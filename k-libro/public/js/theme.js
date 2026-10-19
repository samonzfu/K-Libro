/**
 * Modo claro / oscuro de K-Libro.
 * - Oscuro por defecto. La elección se guarda en localStorage ('klibro-theme').
 * - Se carga en el <head> para aplicar el tema antes de pintar la página (sin parpadeo).
 * - El botón (#btn-theme) muestra el modo al que se cambiará, igual que el de idioma.
 */
(function () {
    var KEY = 'klibro-theme';
    var root = document.documentElement;

    function leerTema() {
        try { return localStorage.getItem(KEY) === 'light' ? 'light' : 'dark'; } catch (e) { return 'dark'; }
    }
    function guardarTema(tema) {
        try { localStorage.setItem(KEY, tema); } catch (e) { /* sin almacenamiento: se aplica solo en esta página */ }
    }
    function idioma() {
        try { return localStorage.getItem('klibro-lang') === 'en' ? 'en' : 'es'; } catch (e) { return 'es'; }
    }
    function aplicar(tema) {
        root.setAttribute('data-theme', tema);
    }

    function pintarBoton() {
        var btn = document.getElementById('btn-theme');
        if (!btn) return;
        var haciaClaro = root.getAttribute('data-theme') !== 'light';
        var en = idioma() === 'en';
        btn.textContent = haciaClaro ? (en ? '☀️ Light' : '☀️ Claro') : (en ? '🌙 Dark' : '🌙 Oscuro');
        var etiqueta = en
            ? 'Switch to ' + (haciaClaro ? 'light' : 'dark') + ' mode'
            : 'Cambiar a modo ' + (haciaClaro ? 'claro' : 'oscuro');
        btn.setAttribute('aria-label', etiqueta);
        btn.title = etiqueta;
    }

    aplicar(leerTema());

    document.addEventListener('DOMContentLoaded', function () {
        var btn = document.getElementById('btn-theme');
        if (btn) {
            btn.addEventListener('click', function () {
                var nuevo = root.getAttribute('data-theme') === 'light' ? 'dark' : 'light';
                aplicar(nuevo);
                guardarTema(nuevo);
                pintarBoton();
            });
        }
        pintarBoton();
    });

    // Si cambia el idioma (lo avisa i18n.js) se actualiza el texto del botón
    document.addEventListener('klibro:lang', pintarBoton);
    // Si se cambia el tema en otra pestaña
    window.addEventListener('storage', function (e) {
        if (e.key === KEY) { aplicar(leerTema()); pintarBoton(); }
    });
})();
