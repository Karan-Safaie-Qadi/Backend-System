window.Pages = {
    currentUser: null,

    async init() {
        try {
            const res = await API.get('auth');
            this.currentUser = res.data.user;
            document.querySelectorAll('.topbar-actions').forEach(el => {
                if (this.currentUser) {
                    el.innerHTML = `
                        <div class="user-info-bar">
                            <span>👤 ${Utils.escapeHtml(this.currentUser.username)}</span>
                            <span class="badge badge-primary">${Utils.escapeHtml(I18N[`level_${this.currentUser.access_level}`] || this.currentUser.access_level)}</span>
                            <a class="logout-link" onclick="Pages.logout()">${I18N.logout_btn}</a>
                        </div>`;
                }
            });
        } catch {}
    },

    async logout() {
        try {
            await API.post('auth', {}, { action: 'logout' });
            this.currentUser = null;
            Utils.showToast('Logged out', 'success');
            Router.handleRoute();
        } catch (e) {
            Utils.showToast(e.message, 'error');
        }
    },

    // ========== DASHBOARD ==========
    async dashboard(container) {
        try {
            const res = await API.get('dashboard');
            const d = res.data;
            let statsHtml = '<div class="stats-grid">';
            const stats = [
                { label: I18N.stats_total_users, value: d.users?.total || 0, icon: '👥', cls: 'subtle' },
                { label: I18N.stats_total_products, value: d.products?.total || 0, icon: '📦', cls: 'subtle' },
                { label: I18N.stats_total_articles, value: d.articles?.total || 0, icon: '📝', cls: 'subtle' },
                { label: I18N.stats_active_users, value: d.users?.active_today || 0, icon: '✅', cls: 'success' },
                { label: I18N.stats_low_stock, value: d.products?.out_of_stock || 0, icon: '⚠️', cls: 'warning' },
                { label: I18N.stats_on_sale, value: d.products?.on_sale || 0, icon: '🏷️', cls: 'danger' },
                { label: I18N.stats_published, value: d.articles?.published || 0, icon: '📰', cls: 'success' },
                { label: I18N.stats_today_activities, value: (d.recent_activities || []).length, icon: '📋', cls: 'info' },
            ];
            stats.forEach(s => {
                statsHtml += `
                    <div class="stat-card ${s.cls}">
                        <div class="stat-card-header">
                            <span class="stat-card-label">${s.label}</span>
                            <span class="stat-card-icon">${s.icon}</span>
                        </div>
                        <div class="stat-card-value">${s.value}</div>
                    </div>`;
            });
            statsHtml += '</div>';

            let activityHtml = '<div class="card"><div class="card-header"><h2>📋 ' + I18N.stats_today_activities + '</h2></div><div class="card-body">';
            const activities = d.recent_activities || [];
            if (activities.length === 0) {
                activityHtml += '<div class="empty-state"><p>' + I18N.no_data + '</p></div>';
            } else {
                activityHtml += '<div class="activity-list">';
                activities.slice(0, 10).forEach(a => {
                    const colors = { login: '#00b894', register: '#6c5ce7', create_product: '#0984e3', delete: '#d63031' };
                    activityHtml += `
                        <div class="activity-item">
                            <div class="activity-dot" style="background:${colors[a.action] || '#b2bec3'}"></div>
                            <div class="activity-content">
                                <div class="activity-action">${Utils.escapeHtml(a.action)}</div>
                                <div class="activity-description">${Utils.escapeHtml(a.description || '')}</div>
                                <div class="activity-time">${Utils.timeAgo(a.created_at)} — ${Utils.escapeHtml(a.username || '')}</div>
                            </div>
                        </div>`;
                });
                activityHtml += '</div>';
            }
            activityHtml += '</div></div>';

            container.innerHTML = `
                <div class="page-header" style="margin-bottom:24px">
                    <h2 style="font-size:22px;font-weight:700">📊 ${I18N.page_dashboard}</h2>
                </div>
                ${statsHtml}
                ${activityHtml}`;
        } catch (e) {
            container.innerHTML = `<div class="empty-state"><div class="empty-state-icon">⚠️</div><h3>${I18N.error}</h3><p>${Utils.escapeHtml(e.message)}</p></div>`;
        }
    },

    // ========== USERS ==========
    async users(container) {
        const render = async (page = 1) => {
            try {
                const res = await API.get('users', { page, per_page: 10 });
                const data = res.data;
                let html = `
                    <div class="page-header" style="display:flex;justify-content:space-between;align-items:center;margin-bottom:24px;flex-wrap:wrap;gap:12px">
                        <h2 style="font-size:22px;font-weight:700">👥 ${I18N.page_users}</h2>
                        <div class="btn-group">
                            <div class="search-bar">
                                <input type="text" id="userSearch" placeholder="${I18N.search}" onkeyup="if(event.key==='Enter')Pages.userSearch(this.value)">
                                <button class="btn btn-primary btn-sm" onclick="Pages.userSearch(document.getElementById('userSearch').value)">🔍</button>
                            </div>
                            <button class="btn btn-success btn-sm" onclick="Pages.showUserForm()">+ ${I18N.create}</button>
                        </div>
                    </div>`;

                if (!this.currentUser) {
                    html += `<div class="card"><div class="card-body">${this.renderLoginForm()}</div></div>`;
                    container.innerHTML = html;
                    return;
                }

                html += '<div class="card"><div class="card-body"><div class="table-wrapper"><table>';
                html += `<thead><tr>
                    <th>ID</th><th>${I18N.user_username}</th><th>${I18N.user_email}</th><th>${I18N.user_phone}</th>
                    <th>${I18N.user_access_level}</th><th>${I18N.user_is_active}</th><th>${I18N.user_last_login}</th>
                    <th>${I18N.actions}</th>
                </tr></thead><tbody>`;

                const items = data.items || data || [];
                if (items.length === 0) {
                    html += `<tr class="table-empty"><td colspan="8">${I18N.no_data}</td></tr>`;
                } else {
                    items.forEach(u => {
                        html += `<tr>
                            <td><strong>${u.id}</strong></td>
                            <td>${Utils.escapeHtml(u.username)}</td>
                            <td>${Utils.escapeHtml(u.email || '-')}</td>
                            <td>${Utils.escapeHtml(u.phone || '-')}</td>
                            <td><span class="badge ${u.access_level >= 3 ? 'badge-danger' : u.access_level >= 2 ? 'badge-warning' : 'badge-primary'}">${u.access_level}</span></td>
                            <td><span class="badge ${u.is_active ? 'badge-success' : 'badge-danger'}">${u.is_active ? I18N.active : I18N.inactive}</span></td>
                            <td>${Utils.timeAgo(u.last_login_at)}</td>
                            <td>
                                <div class="btn-group">
                                    <button class="btn btn-primary btn-sm" onclick="Pages.showUserForm(${u.id})">✏️</button>
                                    <button class="btn btn-danger btn-sm" onclick="Pages.deleteUser(${u.id})">🗑️</button>
                                </div>
                            </td>
                        </tr>`;
                    });
                }
                html += '</tbody></table></div></div></div>';
                html += Utils.renderPagination(data, 'Pages.users_page');
                container.innerHTML = html;
            } catch (e) {
                container.innerHTML = `<div class="empty-state"><div class="empty-state-icon">⚠️</div><h3>${I18N.error}</h3><p>${Utils.escapeHtml(e.message)}</p></div>`;
            }
        };
        container.innerHTML = `<div class="loading-screen"><div class="spinner"></div><p>${I18N.loading}</p></div>`;
        await render(1);
        window.Pages.users_page = render;
    },

    async userSearch(query) {
        if (!query) { Router.handleRoute(); return; }
        const container = Utils.$('#mainContent');
        try {
            const res = await API.get('users', { search: query });
            const users = res.data;
            let html = `
                <div class="page-header" style="display:flex;justify-content:space-between;align-items:center;margin-bottom:24px">
                    <h2 style="font-size:22px;font-weight:700">🔍 "${Utils.escapeHtml(query)}"</h2>
                    <button class="btn btn-secondary btn-sm" onclick="Router.handleRoute()">← ${I18N.back || 'Back'}</button>
                </div>
                <div class="card"><div class="card-body"><div class="table-wrapper"><table><thead><tr>
                    <th>ID</th><th>${I18N.user_username}</th><th>${I18N.user_email}</th><th>${I18N.user_access_level}</th><th>${I18N.actions}</th>
                </tr></thead><tbody>`;
            if (users.length === 0) {
                html += `<tr class="table-empty"><td colspan="5">${I18N.no_data}</td></tr>`;
            } else {
                users.forEach(u => {
                    html += `<tr><td>${u.id}</td><td>${Utils.escapeHtml(u.username)}</td><td>${Utils.escapeHtml(u.email || '-')}</td>
                        <td><span class="badge badge-primary">${u.access_level}</span></td>
                        <td><button class="btn btn-primary btn-sm" onclick="Pages.showUserForm(${u.id})">✏️</button></td></tr>`;
                });
            }
            html += '</tbody></table></div></div></div>';
            container.innerHTML = html;
        } catch (e) {
            Utils.showToast(e.message, 'error');
        }
    },

    async showUserForm(id = null) {
        let user = null;
        if (id) {
            try {
                const res = await API.get('users', { id });
                user = res.data;
            } catch (e) { Utils.showToast(e.message, 'error'); return; }
        }

        Utils.showModal(`
            <div class="modal-header">
                <h2>${id ? I18N.edit : I18N.create} ${I18N.nav_users}</h2>
                <button class="modal-close" onclick="Utils.closeModal()">✕</button>
            </div>
            <div class="modal-body">
                <form id="userForm">
                    <div class="form-row">
                        <div class="form-group">
                            <label>${I18N.user_username}</label>
                            <input class="form-control" name="username" value="${user ? Utils.escapeHtml(user.username) : ''}" ${id ? 'disabled' : ''}>
                        </div>
                        <div class="form-group">
                            <label>${I18N.user_display_name}</label>
                            <input class="form-control" name="display_name" value="${user ? Utils.escapeHtml(user.display_name || '') : ''}">
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>${I18N.user_email}</label>
                            <input class="form-control" name="email" value="${user ? Utils.escapeHtml(user.email || '') : ''}">
                        </div>
                        <div class="form-group">
                            <label>${I18N.user_phone}</label>
                            <input class="form-control" name="phone" value="${user ? Utils.escapeHtml(user.phone || '') : ''}">
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>${I18N.user_password}</label>
                            <input class="form-control" type="password" name="password" placeholder="${id ? I18N.info : ''}">
                        </div>
                        <div class="form-group">
                            <label>${I18N.user_access_level}</label>
                            <select class="form-control" name="access_level">
                                <option value="1" ${user && user.access_level === 1 ? 'selected' : ''}>${I18N.level_1 || 'User'}</option>
                                <option value="2" ${user && user.access_level === 2 ? 'selected' : ''}>${I18N.level_2 || 'Admin'}</option>
                                <option value="3" ${user && user.access_level === 3 ? 'selected' : ''}>${I18N.level_3 || 'Owner'}</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-check">
                        <input type="checkbox" name="is_active" value="1" ${!user || user.is_active ? 'checked' : ''}>
                        <label>${I18N.user_is_active}</label>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" onclick="Utils.closeModal()">${I18N.cancel}</button>
                <button class="btn btn-primary" onclick="Pages.saveUserForm(${id || ''})">${I18N.save}</button>
            </div>
        `);
    },

    async saveUserForm(id) {
        const data = Utils.getFormData('userForm');
        try {
            if (id) {
                await API.put('users', data, { id });
                Utils.showToast('User updated', 'success');
            } else {
                await API.post('users', data);
                Utils.showToast('User created', 'success');
            }
            Utils.closeModal();
            Router.handleRoute();
        } catch (e) { Utils.showToast(e.message, 'error'); }
    },

    async deleteUser(id) {
        const confirmed = await Utils.confirm(I18N.confirm_delete);
        if (!confirmed) return;
        try {
            await API.delete('users', { id });
            Utils.showToast('User deleted', 'success');
            Router.handleRoute();
        } catch (e) { Utils.showToast(e.message, 'error'); }
    },

    renderLoginForm() {
        return `
            <div class="login-form">
                <h2>🔐 ${I18N.login_title}</h2>
                <p class="subtitle">${I18N.info || 'Sign in to manage data'}</p>
                <form id="loginForm" onsubmit="event.preventDefault();Pages.login()">
                    <div class="form-group">
                        <label>${I18N.user_username} / ${I18N.user_email}</label>
                        <input class="form-control" name="username" placeholder="${I18N.user_username}" required>
                    </div>
                    <div class="form-group">
                        <label>${I18N.user_password}</label>
                        <input class="form-control" type="password" name="password" placeholder="••••••••" required>
                    </div>
                    <div class="form-check">
                        <input type="checkbox" name="remember" value="1" id="rm">
                        <label for="rm">${I18N.remember_me}</label>
                    </div>
                    <button class="btn btn-primary btn-lg" style="width:100%;margin-top:16px">${I18N.login_btn}</button>
                </form>
                <div style="text-align:center;margin-top:16px">
                    <button class="btn btn-secondary btn-sm" onclick="Pages.showRegisterForm()">${I18N.register_btn}</button>
                    <button class="btn btn-secondary btn-sm" onclick="Pages.showForgotForm()">${I18N.user_forgot_password}</button>
                </div>
            </div>`;
    },

    async login() {
        const data = Utils.getFormData('loginForm');
        try {
            const res = await API.post('auth', data, { action: 'login' });
            Utils.showToast(sprintf(I18N.welcome_back, res.data.username), 'success');
            this.currentUser = res.data;
            Router.handleRoute();
        } catch (e) { Utils.showToast(e.message, 'error'); }
    },

    showRegisterForm() {
        Utils.showModal(`
            <div class="modal-header">
                <h2>📝 ${I18N.register_btn}</h2>
                <button class="modal-close" onclick="Utils.closeModal()">✕</button>
            </div>
            <div class="modal-body">
                <form id="registerForm">
                    <div class="form-group">
                        <label>${I18N.user_username}</label>
                        <input class="form-control" name="username" required>
                    </div>
                    <div class="form-group">
                        <label>${I18N.user_email}</label>
                        <input class="form-control" type="email" name="email">
                    </div>
                    <div class="form-group">
                        <label>${I18N.user_phone}</label>
                        <input class="form-control" name="phone">
                    </div>
                    <div class="form-group">
                        <label>${I18N.user_password}</label>
                        <input class="form-control" type="password" name="password" required>
                    </div>
                    <div class="form-group">
                        <label>${I18N.user_display_name}</label>
                        <input class="form-control" name="display_name">
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" onclick="Utils.closeModal()">${I18N.cancel}</button>
                <button class="btn btn-primary" onclick="Pages.submitRegister()">${I18N.register_btn}</button>
            </div>
        `);
    },

    async submitRegister() {
        const data = Utils.getFormData('registerForm');
        try {
            await API.post('auth', data, { action: 'register' });
            Utils.showToast('Registration successful', 'success');
            Utils.closeModal();
        } catch (e) { Utils.showToast(e.message, 'error'); }
    },

    showForgotForm() {
        Utils.showModal(`
            <div class="modal-header">
                <h2>🔑 ${I18N.user_forgot_password}</h2>
                <button class="modal-close" onclick="Utils.closeModal()">✕</button>
            </div>
            <div class="modal-body">
                <form id="forgotForm">
                    <div class="form-group">
                        <label>${I18N.user_email}</label>
                        <input class="form-control" type="email" name="email" required>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" onclick="Utils.closeModal()">${I18N.cancel}</button>
                <button class="btn btn-primary" onclick="Pages.submitForgot()">${I18N.confirm}</button>
            </div>
        `);
    },

    async submitForgot() {
        const data = Utils.getFormData('forgotForm');
        try {
            await API.post('auth', data, { action: 'forgot_password' });
            Utils.showToast('If email exists, reset link sent', 'success');
            Utils.closeModal();
        } catch (e) { Utils.showToast(e.message, 'error'); }
    },

    // ========== PRODUCTS ==========
    async products(container) {
        const render = async (page = 1) => {
            try {
                const res = await API.get('products', { page, per_page: 10 });
                const data = res.data;
                let html = `
                    <div class="page-header" style="display:flex;justify-content:space-between;align-items:center;margin-bottom:24px;flex-wrap:wrap;gap:12px">
                        <h2 style="font-size:22px;font-weight:700">📦 ${I18N.page_products}</h2>
                        <div class="btn-group">
                            <div class="search-bar">
                                <input type="text" id="prodSearch" placeholder="${I18N.search}" onkeyup="if(event.key==='Enter')Pages.productSearch(this.value)">
                                <button class="btn btn-primary btn-sm" onclick="Pages.productSearch(document.getElementById('prodSearch').value)">🔍</button>
                            </div>
                            <button class="btn btn-success btn-sm" onclick="Pages.showProductForm()">+ ${I18N.create}</button>
                        </div>
                    </div>`;

                if (!this.currentUser) {
                    html += `<div class="card"><div class="card-body">${this.renderLoginForm()}</div></div>`;
                    container.innerHTML = html;
                    return;
                }

                html += '<div class="card"><div class="card-body"><div class="table-wrapper"><table>';
                html += `<thead><tr>
                    <th>ID</th><th>${I18N.product_name}</th><th>${I18N.product_price}</th><th>${I18N.product_sale_price}</th>
                    <th>${I18N.product_stock}</th><th>${I18N.product_featured}</th><th>${I18N.actions}</th>
                </tr></thead><tbody>`;

                const items = data.items || data || [];
                if (items.length === 0) {
                    html += `<tr class="table-empty"><td colspan="7">${I18N.no_data}</td></tr>`;
                } else {
                    items.forEach(p => {
                        html += `<tr>
                            <td><strong>${p.id}</strong></td>
                            <td>${Utils.escapeHtml(p.name)}</td>
                            <td>💰 $${parseFloat(p.price || 0).toFixed(2)}</td>
                            <td>${p.sale_price ? '💰 $' + parseFloat(p.sale_price).toFixed(2) : '-'}</td>
                            <td><span class="badge ${p.stock_quantity > 10 ? 'badge-success' : p.stock_quantity > 0 ? 'badge-warning' : 'badge-danger'}">${p.stock_quantity}</span></td>
                            <td>${p.is_featured ? '⭐ ' + I18N.featured : I18N.not_featured}</td>
                            <td>
                                <div class="btn-group">
                                    <button class="btn btn-primary btn-sm" onclick="Pages.showProductForm(${p.id})">✏️</button>
                                    <button class="btn btn-danger btn-sm" onclick="Pages.deleteProduct(${p.id})">🗑️</button>
                                </div>
                            </td>
                        </tr>`;
                    });
                }
                html += '</tbody></table></div></div></div>';
                html += Utils.renderPagination(data, 'Pages.products_page');
                container.innerHTML = html;
            } catch (e) {
                container.innerHTML = `<div class="empty-state"><div class="empty-state-icon">⚠️</div><h3>${I18N.error}</h3><p>${Utils.escapeHtml(e.message)}</p></div>`;
            }
        };
        container.innerHTML = `<div class="loading-screen"><div class="spinner"></div><p>${I18N.loading}</p></div>`;
        await render(1);
        window.Pages.products_page = render;
    },

    async productSearch(query) {
        if (!query) { Router.handleRoute(); return; }
        const container = Utils.$('#mainContent');
        try {
            const res = await API.get('products', { search: query });
            const products = res.data;
            let html = `<div class="page-header" style="margin-bottom:24px"><h2>🔍 "${Utils.escapeHtml(query)}"</h2></div>
                <div class="card"><div class="card-body"><div class="table-wrapper"><table><thead><tr>
                    <th>ID</th><th>${I18N.product_name}</th><th>${I18N.product_price}</th><th>${I18N.product_stock}</th><th>${I18N.actions}</th>
                </tr></thead><tbody>`;
            if (products.length === 0) {
                html += `<tr class="table-empty"><td colspan="5">${I18N.no_data}</td></tr>`;
            } else {
                products.forEach(p => {
                    html += `<tr><td>${p.id}</td><td>${Utils.escapeHtml(p.name)}</td><td>$${parseFloat(p.price).toFixed(2)}</td>
                        <td>${p.stock_quantity}</td>
                        <td><button class="btn btn-primary btn-sm" onclick="Pages.showProductForm(${p.id})">✏️</button></td></tr>`;
                });
            }
            html += '</tbody></table></div></div></div>';
            container.innerHTML = html;
        } catch (e) { Utils.showToast(e.message, 'error'); }
    },

    async showProductForm(id = null) {
        let product = null;
        if (id) {
            try { const res = await API.get('products', { id }); product = res.data; }
            catch (e) { Utils.showToast(e.message, 'error'); return; }
        }

        Utils.showModal(`
            <div class="modal-header">
                <h2>${id ? I18N.edit : I18N.create} ${I18N.nav_products}</h2>
                <button class="modal-close" onclick="Utils.closeModal()">✕</button>
            </div>
            <div class="modal-body">
                <form id="productForm">
                    <div class="form-group">
                        <label>${I18N.product_name}</label>
                        <input class="form-control" name="name" value="${product ? Utils.escapeHtml(product.name) : ''}" required>
                    </div>
                    <div class="form-group">
                        <label>${I18N.product_short_description}</label>
                        <input class="form-control" name="short_description" value="${product ? Utils.escapeHtml(product.short_description || '') : ''}">
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>${I18N.product_price}</label>
                            <input class="form-control" type="number" step="0.01" name="price" value="${product ? product.price : '0'}">
                        </div>
                        <div class="form-group">
                            <label>${I18N.product_sale_price}</label>
                            <input class="form-control" type="number" step="0.01" name="sale_price" value="${product ? (product.sale_price || '') : ''}">
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>${I18N.product_sku}</label>
                            <input class="form-control" name="sku" value="${product ? Utils.escapeHtml(product.sku || '') : ''}">
                        </div>
                        <div class="form-group">
                            <label>${I18N.product_stock}</label>
                            <input class="form-control" type="number" name="stock_quantity" value="${product ? product.stock_quantity : '0'}">
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-check">
                            <input type="checkbox" name="is_featured" value="1" ${product && product.is_featured ? 'checked' : ''}>
                            <label>⭐ ${I18N.product_featured}</label>
                        </div>
                        <div class="form-check">
                            <input type="checkbox" name="is_active" value="1" ${!product || product.is_active ? 'checked' : ''}>
                            <label>✅ ${I18N.active}</label>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" onclick="Utils.closeModal()">${I18N.cancel}</button>
                <button class="btn btn-primary" onclick="Pages.saveProductForm(${id || ''})">${I18N.save}</button>
            </div>
        `);
    },

    async saveProductForm(id) {
        const data = Utils.getFormData('productForm');
        try {
            if (id) { await API.put('products', data, { id }); Utils.showToast('Product updated', 'success'); }
            else { await API.post('products', data); Utils.showToast('Product created', 'success'); }
            Utils.closeModal();
            Router.handleRoute();
        } catch (e) { Utils.showToast(e.message, 'error'); }
    },

    async deleteProduct(id) {
        const confirmed = await Utils.confirm(I18N.confirm_delete);
        if (!confirmed) return;
        try { await API.delete('products', { id }); Utils.showToast('Product deleted', 'success'); Router.handleRoute(); }
        catch (e) { Utils.showToast(e.message, 'error'); }
    },

    // ========== ARTICLES ==========
    async articles(container) {
        const render = async (page = 1) => {
            try {
                const res = await API.get('articles', { page, per_page: 10 });
                const data = res.data;
                let html = `
                    <div class="page-header" style="display:flex;justify-content:space-between;align-items:center;margin-bottom:24px;flex-wrap:wrap;gap:12px">
                        <h2 style="font-size:22px;font-weight:700">📝 ${I18N.page_articles}</h2>
                        <div class="btn-group">
                            <div class="search-bar">
                                <input type="text" id="artSearch" placeholder="${I18N.search}" onkeyup="if(event.key==='Enter')Pages.articleSearch(this.value)">
                                <button class="btn btn-primary btn-sm" onclick="Pages.articleSearch(document.getElementById('artSearch').value)">🔍</button>
                            </div>
                            <button class="btn btn-success btn-sm" onclick="Pages.showArticleForm()">+ ${I18N.create}</button>
                        </div>
                    </div>`;

                if (!this.currentUser) {
                    html += `<div class="card"><div class="card-body">${this.renderLoginForm()}</div></div>`;
                    container.innerHTML = html;
                    return;
                }

                html += '<div class="card"><div class="card-body"><div class="table-wrapper"><table>';
                html += `<thead><tr>
                    <th>ID</th><th>${I18N.article_title}</th><th>${I18N.article_category}</th><th>${I18N.article_author}</th>
                    <th>${I18N.status}</th><th>${I18N.article_published_at}</th><th>${I18N.actions}</th>
                </tr></thead><tbody>`;

                const items = data.items || data || [];
                if (items.length === 0) {
                    html += `<tr class="table-empty"><td colspan="7">${I18N.no_data}</td></tr>`;
                } else {
                    items.forEach(a => {
                        html += `<tr>
                            <td><strong>${a.id}</strong></td>
                            <td><strong>${Utils.escapeHtml(a.title)}</strong></td>
                            <td>${Utils.escapeHtml(a.category_id ? '#' + a.category_id : '-')}</td>
                            <td>${Utils.escapeHtml(a.author_id ? '#' + a.author_id : '-')}</td>
                            <td><span class="badge ${a.is_published ? 'badge-success' : 'badge-warning'}">${a.is_published ? I18N.published : I18N.draft}</span></td>
                            <td>${a.published_at ? Utils.dateFormat(a.published_at) : '-'}</td>
                            <td>
                                <div class="btn-group">
                                    <button class="btn btn-primary btn-sm" onclick="Pages.showArticleForm(${a.id})">✏️</button>
                                    <button class="btn btn-danger btn-sm" onclick="Pages.deleteArticle(${a.id})">🗑️</button>
                                    ${a.is_published ? `<button class="btn btn-warning btn-sm" onclick="Pages.toggleArticle(${a.id},0)">📥</button>`
                                        : `<button class="btn btn-success btn-sm" onclick="Pages.toggleArticle(${a.id},1)">📤</button>`}
                                </div>
                            </td>
                        </tr>`;
                    });
                }
                html += '</tbody></table></div></div></div>';
                html += Utils.renderPagination(data, 'Pages.articles_page');
                container.innerHTML = html;
            } catch (e) {
                container.innerHTML = `<div class="empty-state"><div class="empty-state-icon">⚠️</div><h3>${I18N.error}</h3><p>${Utils.escapeHtml(e.message)}</p></div>`;
            }
        };
        container.innerHTML = `<div class="loading-screen"><div class="spinner"></div><p>${I18N.loading}</p></div>`;
        await render(1);
        window.Pages.articles_page = render;
    },

    async toggleArticle(id, publish) {
        try {
            await API.post('articles', { id }, { action: publish ? 'publish' : 'unpublish' });
            Utils.showToast(publish ? 'Article published' : 'Article unpublished', 'success');
            Router.handleRoute();
        } catch (e) { Utils.showToast(e.message, 'error'); }
    },

    async articleSearch(query) {
        if (!query) { Router.handleRoute(); return; }
        const container = Utils.$('#mainContent');
        try {
            const res = await API.get('articles', { search: query });
            const articles = res.data;
            let html = `<div class="page-header" style="margin-bottom:24px"><h2>🔍 "${Utils.escapeHtml(query)}"</h2></div>
                <div class="card"><div class="card-body"><div class="table-wrapper"><table><thead><tr>
                    <th>ID</th><th>${I18N.article_title}</th><th>${I18N.status}</th><th>${I18N.actions}</th>
                </tr></thead><tbody>`;
            if (articles.length === 0) {
                html += `<tr class="table-empty"><td colspan="4">${I18N.no_data}</td></tr>`;
            } else {
                articles.forEach(a => {
                    html += `<tr><td>${a.id}</td><td>${Utils.escapeHtml(a.title)}</td>
                        <td><span class="badge ${a.is_published ? 'badge-success' : 'badge-warning'}">${a.is_published ? I18N.published : I18N.draft}</span></td>
                        <td><button class="btn btn-primary btn-sm" onclick="Pages.showArticleForm(${a.id})">✏️</button></td></tr>`;
                });
            }
            html += '</tbody></table></div></div></div>';
            container.innerHTML = html;
        } catch (e) { Utils.showToast(e.message, 'error'); }
    },

    async showArticleForm(id = null) {
        let article = null;
        let sections = [];
        if (id) {
            try { const res = await API.get('articles', { id }); article = res.data; sections = article.sections || []; }
            catch (e) { Utils.showToast(e.message, 'error'); return; }
        }

        let sectionsHtml = '<div id="sectionsContainer">';
        sections.forEach((s, i) => {
            sectionsHtml += this.renderSectionItem(s, i);
        });
        sectionsHtml += '</div>';

        Utils.showModal(`
            <div class="modal-header">
                <h2>${id ? I18N.edit : I18N.create} ${I18N.nav_articles}</h2>
                <button class="modal-close" onclick="Utils.closeModal()">✕</button>
            </div>
            <div class="modal-body">
                <form id="articleForm">
                    <div class="form-group">
                        <label>${I18N.article_title}</label>
                        <input class="form-control" name="title" value="${article ? Utils.escapeHtml(article.title) : ''}" required>
                    </div>
                    <div class="form-group">
                        <label>${I18N.article_summary}</label>
                        <textarea class="form-control" name="summary">${article ? Utils.escapeHtml(article.summary || '') : ''}</textarea>
                    </div>
                    <div class="form-group">
                        <label>${I18N.article_category}</label>
                        <input class="form-control" name="category_id" type="number" value="${article ? (article.category_id || '') : ''}" placeholder="Category ID">
                    </div>
                </form>
                <hr style="border:none;border-top:1px solid var(--gray-200);margin:20px 0">
                <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px">
                    <h3 style="font-size:16px;font-weight:600">📑 ${I18N.article_section}s</h3>
                    <button class="btn btn-secondary btn-sm" onclick="Pages.addSection()">+ ${I18N.article_add_section}</button>
                </div>
                ${sectionsHtml}
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" onclick="Utils.closeModal()">${I18N.cancel}</button>
                <button class="btn btn-primary" onclick="Pages.saveArticleForm(${id || ''})">${I18N.save}</button>
            </div>
        `);
        window._sections = sections;
    },

    renderSectionItem(section, index) {
        const types = ['text', 'list', 'table', 'image', 'mixed'];
        const typeNames = [I18N.article_text, I18N.article_list, I18N.article_table, I18N.article_image, I18N.article_mixed];
        const typeOpts = types.map((t, i) => `<option value="${t}" ${section && section.section_type === t ? 'selected' : ''}>${typeNames[i]}</option>`).join('');
        return `
            <div class="section-item" data-index="${index}">
                <div class="section-header">
                    <h4>${I18N.article_section} #${index + 1}</h4>
                    <button class="btn btn-danger btn-sm" onclick="Pages.removeSection(this)">🗑️</button>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>${I18N.article_title}</label>
                        <input class="form-control section-title" value="${section ? Utils.escapeHtml(section.title || '') : ''}">
                    </div>
                    <div class="form-group">
                        <label>${I18N.article_section_type}</label>
                        <select class="form-control section-type">${typeOpts}</select>
                    </div>
                </div>
                <div class="form-group">
                    <label>${I18N.article_text}</label>
                    <textarea class="form-control section-content" rows="3">${section ? Utils.escapeHtml(section.content || '') : ''}</textarea>
                </div>
            </div>`;
    },

    addSection() {
        const container = Utils.$('#sectionsContainer');
        const index = container.children.length;
        const div = document.createElement('div');
        div.innerHTML = this.renderSectionItem({}, index);
        container.appendChild(div.firstElementChild);
    },

    removeSection(btn) {
        btn.closest('.section-item').remove();
    },

    async saveArticleForm(id) {
        const data = Utils.getFormData('articleForm');
        const sections = [];
        document.querySelectorAll('.section-item').forEach(el => {
            sections.push({
                title: el.querySelector('.section-title')?.value || '',
                section_type: el.querySelector('.section-type')?.value || 'text',
                content: el.querySelector('.section-content')?.value || '',
            });
        });
        data.sections = sections;
        try {
            if (id) { await API.put('articles', data, { id }); Utils.showToast('Article updated', 'success'); }
            else { await API.post('articles', data); Utils.showToast('Article created', 'success'); }
            Utils.closeModal();
            Router.handleRoute();
        } catch (e) { Utils.showToast(e.message, 'error'); }
    },

    async deleteArticle(id) {
        const confirmed = await Utils.confirm(I18N.confirm_delete);
        if (!confirmed) return;
        try { await API.delete('articles', { id }); Utils.showToast('Article deleted', 'success'); Router.handleRoute(); }
        catch (e) { Utils.showToast(e.message, 'error'); }
    },

    // ========== SYSTEM ==========
    async system(container) {
        try {
            const res = await API.get('system', { action: 'info' });
            const data = res.data;
            let html = `
                <div class="page-header" style="margin-bottom:24px">
                    <h2 style="font-size:22px;font-weight:700">⚙️ ${I18N.page_system}</h2>
                </div>
                <div class="info-grid">
                    <div class="info-card">
                        <h3>🖥️ ${I18N.system_server}</h3>
                        <div class="info-item"><span class="info-label">${I18N.system_php_version}</span><span class="info-value">${data.php_version}</span></div>
                        <div class="info-item"><span class="info-label">Server</span><span class="info-value">${data.server_software}</span></div>
                    </div>
                    <div class="info-card">
                        <h3>🗄️ ${I18N.system_database}</h3>
                        <div class="info-item"><span class="info-label">${I18N.system_database}</span><span class="info-value ${data.database?.connected ? '' : 'badge-danger'}">${data.database?.connected ? '✅ ' + (data.database?.version || '') : '❌ ' + (data.database?.error || '')}</span></div>
                    </div>
                    <div class="info-card">
                        <h3>⚙️ ${I18N.system_config}</h3>
                        <div class="info-item"><span class="info-label">${I18N.system_registration_mode}</span><span class="info-value">${data.app_config?.registration_mode === 'email' ? I18N.registration_mode_email : I18N.registration_mode_phone}</span></div>
                        <div class="info-item"><span class="info-label">${I18N.system_debug_mode}</span><span class="info-value">${data.app_config?.debug ? '✅ On' : '❌ Off'}</span></div>
                        <div class="info-item"><span class="info-label">${I18N.system_access_levels}</span><span class="info-value">${Object.entries(data.app_config?.access_levels || {}).map(([k,v]) => `${k}: ${v}`).join(' | ')}</span></div>
                    </div>
                </div>
                <div class="card" style="margin-top:24px">
                    <div class="card-header"><h2>🛠️ ${I18N.system_config}</h2></div>
                    <div class="card-body">
                        <pre style="background:var(--gray-50);padding:16px;border-radius:8px;overflow-x:auto;font-size:13px;direction:ltr;text-align:left">${Utils.escapeHtml(JSON.stringify(data.app_config, null, 2))}</pre>
                    </div>
                </div>`;
            container.innerHTML = html;
        } catch (e) {
            container.innerHTML = `<div class="empty-state"><div class="empty-state-icon">⚠️</div><h3>${I18N.error}</h3><p>${Utils.escapeHtml(e.message)}</p></div>`;
        }
    }
};
