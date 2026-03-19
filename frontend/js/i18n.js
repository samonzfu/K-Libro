/**
 * K-Libro - Motor de internacionalización (i18n)
 * Uso: incluir este script y llamar a I18n.init(translations, 'Título ES', 'Título EN')
 */
const I18n = {
    setLang(lang, t) {
        // Textos normales
        document.querySelectorAll('[data-i18n]').forEach(el => {
            const v = t[lang]?.[el.dataset.i18n];
            if (v !== undefined) el.textContent = v;
        });
        // Placeholders de inputs
        document.querySelectorAll('[data-i18n-ph]').forEach(el => {
            const v = t[lang]?.[el.dataset.i18nPh];
            if (v !== undefined) el.placeholder = v;
        });
        // Valores de inputs type=submit
        document.querySelectorAll('[data-i18n-val]').forEach(el => {
            const v = t[lang]?.[el.dataset.i18nVal];
            if (v !== undefined) el.value = v;
        });
        document.documentElement.lang = lang;
        localStorage.setItem('klibro-lang', lang);
    },

    init(t, titleEs, titleEn) {
        const apply = (lang) => {
            I18n.setLang(lang, t);
            document.title = lang === 'en' ? titleEn : titleEs;
            const btn = document.getElementById('btn-lang');
            if (btn) btn.textContent = lang === 'es' ? '🌐 English' : '🌐 Español';
        };
        const btn = document.getElementById('btn-lang');
        if (btn) {
            btn.addEventListener('click', () => {
                const next = (localStorage.getItem('klibro-lang') || 'es') === 'es' ? 'en' : 'es';
                apply(next);
            });
        }
        apply(localStorage.getItem('klibro-lang') || 'es');
    },

    /** Devuelve la traducción de una clave para el idioma activo (útil en JS dinámico) */
    t(key, translations) {
        const lang = localStorage.getItem('klibro-lang') || 'es';
        return translations[lang]?.[key] ?? key;
    }
};
