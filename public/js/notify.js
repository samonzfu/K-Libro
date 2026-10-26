/**
 * Avisos y confirmaciones de K-Libro (sustituyen a alert() y confirm()).
 *
 *   Notify.show(mensaje, { type: 'success' | 'error' | 'warning' | 'info', duration: ms })
 *   Notify.confirm({ message, title, confirmText, cancelText, danger }) -> Promise<boolean>
 *
 * Los textos pueden ser una cadena o un objeto { es: '...', en: '...' }.
 * Formularios: <form data-confirm-es="..." data-confirm-en="..." data-confirm-danger>
 * piden confirmación antes de enviarse.
 */
(function () {
    'use strict';

    var ICONOS = {
        success: '<svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="10"/><path d="M8 12.5l2.7 2.7L16.5 9.5"/></svg>',
        error:   '<svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="10"/><path d="M15 9l-6 6M9 9l6 6"/></svg>',
        warning: '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 3l10 18H2L12 3z"/><path d="M12 10v5M12 18h.01"/></svg>',
        info:    '<svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="10"/><path d="M12 11v5M12 8h.01"/></svg>'
    };
    var ICONO_CERRAR = '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M6 6l12 12M18 6L6 18"/></svg>';

    function idioma() {
        try { return localStorage.getItem('klibro-lang') === 'en' ? 'en' : 'es'; } catch (e) { return 'es'; }
    }
    function traducir(texto) {
        if (texto && typeof texto === 'object') { return texto[idioma()] || texto.es || texto.en || ''; }
        return texto == null ? '' : String(texto);
    }

    /* ---------------------------------------------------------------- Avisos */
    function contenedor() {
        var c = document.getElementById('ui-toasts');
        if (!c) {
            c = document.createElement('div');
            c.id = 'ui-toasts';
            c.className = 'ui-toasts';
            c.setAttribute('aria-live', 'polite');
            document.body.appendChild(c);
        }
        return c;
    }

    function show(mensaje, opciones) {
        opciones = opciones || {};
        var tipo = ICONOS[opciones.type] ? opciones.type : 'info';

        var toast = document.createElement('div');
        toast.className = 'ui-toast ui-toast-' + tipo;
        toast.setAttribute('role', tipo === 'error' ? 'alert' : 'status');

        var icono = document.createElement('span');
        icono.className = 'ui-toast-icono';
        icono.innerHTML = ICONOS[tipo];

        var texto = document.createElement('p');
        texto.className = 'ui-toast-texto';
        texto.textContent = traducir(mensaje);

        var cerrar = document.createElement('button');
        cerrar.type = 'button';
        cerrar.className = 'ui-toast-cerrar';
        cerrar.setAttribute('aria-label', idioma() === 'en' ? 'Close' : 'Cerrar');
        cerrar.innerHTML = ICONO_CERRAR;

        toast.appendChild(icono);
        toast.appendChild(texto);
        toast.appendChild(cerrar);

        var duracion = typeof opciones.duration === 'number' ? opciones.duration : ((tipo === 'error' || tipo === 'warning') ? 7000 : 4500);
        var temporizador = null;

        function cerrarToast() {
            clearTimeout(temporizador);
            toast.classList.add('ui-toast-saliendo');
            setTimeout(function () { if (toast.parentNode) { toast.parentNode.removeChild(toast); } }, 250);
        }
        function programar() { if (duracion > 0) { temporizador = setTimeout(cerrarToast, duracion); } }

        cerrar.addEventListener('click', cerrarToast);
        toast.addEventListener('mouseenter', function () { clearTimeout(temporizador); });
        toast.addEventListener('mouseleave', programar);

        var caja = contenedor();
        while (caja.children.length >= 4) { caja.removeChild(caja.firstChild); }
        caja.appendChild(toast);
        programar();

        return { close: cerrarToast };
    }

    /* ---------------------------------------------------------- Confirmación */
    function boton(clase, texto) {
        var b = document.createElement('button');
        b.type = 'button';
        b.className = clase;
        b.textContent = traducir(texto);
        return b;
    }

    function confirmar(opciones) {
        opciones = opciones || {};
        return new Promise(function (resolve) {
            var previo = document.activeElement;

            var overlay = document.createElement('div');
            overlay.className = 'ui-modal-overlay';

            var dialogo = document.createElement('div');
            dialogo.className = 'ui-modal';
            dialogo.setAttribute('role', 'alertdialog');
            dialogo.setAttribute('aria-modal', 'true');
            dialogo.setAttribute('aria-labelledby', 'ui-modal-titulo');
            dialogo.setAttribute('aria-describedby', 'ui-modal-texto');

            var titulo = document.createElement('h2');
            titulo.id = 'ui-modal-titulo';
            titulo.className = 'ui-modal-titulo';
            titulo.textContent = traducir(opciones.title || { es: 'Confirmar acción', en: 'Confirm action' });

            var texto = document.createElement('p');
            texto.id = 'ui-modal-texto';
            texto.className = 'ui-modal-texto';
            texto.textContent = traducir(opciones.message);

            var acciones = document.createElement('div');
            acciones.className = 'ui-modal-acciones';
            var btnCancelar = boton('ui-btn ui-btn-secundario', opciones.cancelText || { es: 'Cancelar', en: 'Cancel' });
            var btnOk = boton('ui-btn ' + (opciones.danger ? 'ui-btn-peligro' : 'ui-btn-primario'), opciones.confirmText || { es: 'Confirmar', en: 'Confirm' });
            acciones.appendChild(btnCancelar);
            acciones.appendChild(btnOk);

            dialogo.appendChild(titulo);
            dialogo.appendChild(texto);
            dialogo.appendChild(acciones);
            overlay.appendChild(dialogo);

            function teclas(e) {
                if (e.key === 'Escape') { e.preventDefault(); cerrar(false); return; }
                if (e.key !== 'Tab') { return; }
                var foco = document.activeElement;
                if (foco !== btnOk && foco !== btnCancelar) { e.preventDefault(); btnCancelar.focus(); }
                else if (e.shiftKey && foco === btnCancelar) { e.preventDefault(); btnOk.focus(); }
                else if (!e.shiftKey && foco === btnOk) { e.preventDefault(); btnCancelar.focus(); }
            }
            function cerrar(valor) {
                document.removeEventListener('keydown', teclas, true);
                document.body.classList.remove('ui-modal-abierto');
                overlay.classList.add('ui-modal-saliendo');
                setTimeout(function () { if (overlay.parentNode) { overlay.parentNode.removeChild(overlay); } }, 150);
                if (previo && previo.focus) { previo.focus(); }
                resolve(valor);
            }

            btnCancelar.addEventListener('click', function () { cerrar(false); });
            btnOk.addEventListener('click', function () { cerrar(true); });
            overlay.addEventListener('mousedown', function (e) { if (e.target === overlay) { cerrar(false); } });
            document.addEventListener('keydown', teclas, true);

            document.body.appendChild(overlay);
            document.body.classList.add('ui-modal-abierto');
            (opciones.danger ? btnCancelar : btnOk).focus();
        });
    }

    /* ----------------------------- Formularios con data-confirm-es / -en */
    document.addEventListener('submit', function (e) {
        var form = e.target;
        if (!form || !form.hasAttribute || !form.hasAttribute('data-confirm-es')) { return; }
        if (form.dataset.confirmado === '1') { delete form.dataset.confirmado; return; }
        e.preventDefault();

        // {rol} se sustituye por la opción elegida en el selector del formulario (panel de administración)
        var selector = form.querySelector('select[name="nuevo_rol"]');
        var rol = selector && selector.options[selector.selectedIndex] ? selector.options[selector.selectedIndex].text : '';
        var es = form.getAttribute('data-confirm-es') || '';
        var en = form.getAttribute('data-confirm-en') || es;
        var enviador = e.submitter;

        confirmar({
            message: { es: es.replace('{rol}', rol), en: en.replace('{rol}', rol) },
            confirmText: {
                es: form.getAttribute('data-confirm-ok-es') || 'Confirmar',
                en: form.getAttribute('data-confirm-ok-en') || 'Confirm'
            },
            danger: form.hasAttribute('data-confirm-danger')
        }).then(function (ok) {
            if (!ok) { return; }
            form.dataset.confirmado = '1';
            if (form.requestSubmit) { form.requestSubmit(enviador || undefined); } else { form.submit(); }
        });
    }, true);

    function conTipo(tipo) { return function (m, o) { o = o || {}; o.type = tipo; return show(m, o); }; }

    window.Notify = {
        show: show,
        success: conTipo('success'),
        error: conTipo('error'),
        warning: conTipo('warning'),
        info: conTipo('info'),
        confirm: confirmar
    };
})();
