window.Router = {
    routes: {},
    currentRoute: null,

    register(path, handler) {
        this.routes[path] = handler;
    },

    navigate(path) {
        window.location.hash = path;
    },

    getCurrentPath() {
        return window.location.hash.slice(1) || '';
    },

    async handleRoute() {
        const path = this.getCurrentPath();
        const content = Utils.$('#mainContent');
        const titleEl = Utils.$('#pageTitle');

        if (content) Utils.showLoading(content);

        const handler = this.routes[path] || this.routes['404'];

        if (handler) {
            this.currentRoute = path;
            try {
                document.querySelectorAll('.nav-item').forEach(el => {
                    el.classList.toggle('active', el.dataset.route === path);
                });
                const title = this.getPageTitle(path);
                if (titleEl) titleEl.textContent = title;
                await handler(content);
            } catch (e) {
                console.error('Route error:', e);
                if (content) {
                    content.innerHTML = `
                        <div class="empty-state">
                            <div class="empty-state-icon">⚠️</div>
                            <h3>${I18N.error}</h3>
                            <p>${Utils.escapeHtml(e.message)}</p>
                            <button class="btn btn-primary" onclick="Router.handleRoute()" style="margin-top:16px">${I18N.refresh}</button>
                        </div>`;
                }
            }
        }
    },

    getPageTitle(path) {
        const map = {
            '': I18N.page_dashboard,
            'users': I18N.page_users,
            'products': I18N.page_products,
            'articles': I18N.page_articles,
            'system': I18N.page_system,
        };
        return map[path] || I18N.page_dashboard;
    },

    start() {
        window.addEventListener('hashchange', () => this.handleRoute());
        this.handleRoute();
    }
};
