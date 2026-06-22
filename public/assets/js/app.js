document.addEventListener('DOMContentLoaded', async () => {
    Router.register('', (c) => Pages.dashboard(c));
    Router.register('users', (c) => Pages.users(c));
    Router.register('products', (c) => Pages.products(c));
    Router.register('articles', (c) => Pages.articles(c));
    Router.register('system', (c) => Pages.system(c));
    Router.register('404', (c) => {
        c.innerHTML = `<div class="empty-state"><div class="empty-state-icon">🔍</div><h3>404</h3><p>Page not found</p></div>`;
    });

    await Pages.init();

    const connStatus = Utils.$('#connStatus');
    if (connStatus) {
        const connected = await API.checkConnection();
        connStatus.className = `connection-status ${connected ? 'connected' : 'disconnected'}`;
        connStatus.innerHTML = `<span class="status-dot"></span><span>${connected ? I18N.connected : I18N.disconnected}</span>`;
    }

    const menuBtn = Utils.$('#menuBtn');
    const sidebar = Utils.$('#sidebar');
    const sidebarToggle = Utils.$('#sidebarToggle');

    if (menuBtn && sidebar) {
        menuBtn.onclick = () => sidebar.classList.toggle('open');
        if (sidebarToggle) sidebarToggle.onclick = () => sidebar.classList.remove('open');
    }

    document.addEventListener('click', (e) => {
        if (sidebar && sidebar.classList.contains('open')) {
            if (!sidebar.contains(e.target) && !menuBtn?.contains(e.target)) {
                sidebar.classList.remove('open');
            }
        }
    });

    Router.start();
});
