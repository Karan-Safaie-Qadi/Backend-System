document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('[data-i18n]').forEach(el => {
        const key = el.dataset.i18n;
        if (window.I18N && window.I18N[key]) {
            el.textContent = window.I18N[key];
        }
    });
});
