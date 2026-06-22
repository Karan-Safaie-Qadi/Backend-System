window.Theme = {
    init() {
        const saved = localStorage.getItem('theme') || 'light';
        this.set(saved);
    },

    set(theme) {
        document.documentElement.dataset.theme = theme;
        localStorage.setItem('theme', theme);
    },

    toggle() {
        const current = document.documentElement.dataset.theme || 'light';
        this.set(current === 'light' ? 'dark' : 'light');
    }
};

document.addEventListener('DOMContentLoaded', () => Theme.init());
