/**
 * Botón "ojo" para mostrar u ocultar la contraseña (login y registro).
 * Cada botón .btn-ojo indica en data-target el id del input de contraseña.
 */
(function () {
    'use strict';

    function idioma() {
        try { return localStorage.getItem('klibro-lang') === 'en' ? 'en' : 'es'; } catch (e) { return 'es'; }
    }
    function etiqueta(visible) {
        if (idioma() === 'en') { return visible ? 'Hide password' : 'Show password'; }
        return visible ? 'Ocultar contraseña' : 'Mostrar contraseña';
    }
    function actualizar(btn, input) {
        var visible = input.type === 'text';
        var texto = etiqueta(visible);
        btn.setAttribute('aria-pressed', visible ? 'true' : 'false');
        btn.setAttribute('aria-label', texto);
        btn.title = texto;
    }
    function botones(fn) {
        document.querySelectorAll('.btn-ojo').forEach(function (btn) {
            var input = document.getElementById(btn.getAttribute('data-target'));
            if (input) { fn(btn, input); }
        });
    }

    document.addEventListener('DOMContentLoaded', function () {
        botones(function (btn, input) {
            actualizar(btn, input);
            btn.addEventListener('click', function () {
                var inicio = input.selectionStart;
                var fin = input.selectionEnd;
                input.type = input.type === 'password' ? 'text' : 'password';
                actualizar(btn, input);
                input.focus();
                try { input.setSelectionRange(inicio, fin); } catch (e) { /* algunos navegadores no lo permiten */ }
            });
        });
    });

    // Si cambia el idioma (lo avisa i18n.js) se actualiza la etiqueta
    document.addEventListener('klibro:lang', function () { botones(actualizar); });
})();
