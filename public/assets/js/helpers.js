window.Utils = {
    $(selector) { return document.querySelector(selector); },
    $$(selector) { return document.querySelectorAll(selector); },

    escapeHtml(str) {
        const div = document.createElement('div');
        div.textContent = str;
        return div.innerHTML;
    },

    dateFormat(dateStr) {
        if (!dateStr) return '-';
        const d = new Date(dateStr.replace(' ', 'T'));
        return d.toLocaleDateString(LANG === 'fa' ? 'fa-IR' : 'en-US', {
            year: 'numeric', month: 'short', day: 'numeric',
            hour: '2-digit', minute: '2-digit'
        });
    },

    timeAgo(dateStr) {
        if (!dateStr) return '';
        const now = new Date();
        const d = new Date(dateStr.replace(' ', 'T'));
        const diff = Math.floor((now - d) / 1000);
        if (diff < 60) return LANG === 'fa' ? 'همین الان' : 'just now';
        if (diff < 3600) {
            const m = Math.floor(diff / 60);
            return LANG === 'fa' ? `${m} دقیقه پیش` : `${m}m ago`;
        }
        if (diff < 86400) {
            const h = Math.floor(diff / 3600);
            return LANG === 'fa' ? `${h} ساعت پیش` : `${h}h ago`;
        }
        if (diff < 2592000) {
            const day = Math.floor(diff / 86400);
            return LANG === 'fa' ? `${day} روز پیش` : `${day}d ago`;
        }
        return Utils.dateFormat(dateStr);
    },

    showToast(message, type = 'info') {
        const container = Utils.$('#toastContainer');
        if (!container) return;
        const toast = document.createElement('div');
        toast.className = `toast toast-${type}`;
        toast.textContent = message;
        container.appendChild(toast);
        setTimeout(() => {
            toast.style.opacity = '0';
            toast.style.transform = 'translateX(100px)';
            setTimeout(() => toast.remove(), 300);
        }, 3000);
    },

    showModal(html, large = false) {
        const overlay = Utils.$('#modalOverlay');
        const modal = Utils.$('#modalContent');
        modal.className = `modal ${large ? 'modal-lg' : ''}`;
        modal.innerHTML = html;
        overlay.style.display = 'flex';
        overlay.onclick = (e) => {
            if (e.target === overlay) Utils.closeModal();
        };
    },

    closeModal() {
        Utils.$('#modalOverlay').style.display = 'none';
    },

    confirm(message) {
        return new Promise((resolve) => {
            Utils.showModal(`
                <div class="modal-header">
                    <h2>${window.I18N.confirm || 'Confirm'}</h2>
                    <button class="modal-close" id="confirmCloseBtn">✕</button>
                </div>
                <div class="modal-body"><p>${message}</p></div>
                <div class="modal-footer">
                    <button class="btn btn-secondary" id="confirmCancelBtn">${window.I18N.cancel}</button>
                    <button class="btn btn-danger" id="confirmOkBtn">${window.I18N.yes}</button>
                </div>
            `);
            const cleanup = () => {
                Utils.closeModal();
                const cb = Utils.$('#confirmCancelBtn');
                const ob = Utils.$('#confirmOkBtn');
                const cl = Utils.$('#confirmCloseBtn');
                if (cb) cb.onclick = null;
                if (ob) ob.onclick = null;
                if (cl) cl.onclick = null;
            };
            Utils.$('#confirmCancelBtn').onclick = () => { cleanup(); resolve(false); };
            Utils.$('#confirmOkBtn').onclick = () => { cleanup(); resolve(true); };
            Utils.$('#confirmCloseBtn').onclick = () => { cleanup(); resolve(false); };
        });
    },

    showLoading(container) {
        container.innerHTML = `
            <div class="loading-screen">
                <div class="spinner"></div>
                <p>${I18N.loading}</p>
            </div>`;
    },

    buildForm(data, exclude = []) {
        const form = {};
        for (const [key, value] of Object.entries(data)) {
            if (!exclude.includes(key)) {
                form[key] = value;
            }
        }
        return form;
    },

    getFormData(formId) {
        const form = Utils.$(`#${formId}`);
        if (!form) return {};
        const data = {};
        const fd = new FormData(form);
        for (const [key, value] of fd) {
            if (data[key] !== undefined) {
                if (!Array.isArray(data[key])) data[key] = [data[key]];
                data[key].push(value);
            } else {
                data[key] = value;
            }
        }
        return data;
    },

    renderPagination(pagination, callback) {
        if (!pagination || pagination.total_pages <= 1) return '';
        let html = '<div class="pagination">';
        const prevDisabled = pagination.page <= 1 ? 'disabled' : '';
        html += `<button class="page-btn" ${prevDisabled} onclick="(function(){ ${callback}(${pagination.page - 1}) })()">‹</button>`;
        for (let i = 1; i <= pagination.total_pages; i++) {
            const active = i === pagination.page ? 'active' : '';
            html += `<button class="page-btn ${active}" onclick="(function(){ ${callback}(${i}) })()">${i}</button>`;
        }
        const nextDisabled = pagination.page >= pagination.total_pages ? 'disabled' : '';
        html += `<button class="page-btn" ${nextDisabled} onclick="(function(){ ${callback}(${pagination.page + 1}) })()">›</button>`;
        html += `<span class="page-info">${sprintf(I18N.page_of, pagination.page, pagination.total_pages)}</span>`;
        html += '</div>';
        return html;
    }
};

function sprintf(str, ...args) {
    return str.replace(/%d/g, () => args.shift()).replace(/%s/g, () => args.shift());
}
