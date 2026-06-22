window.API = {
    baseUrl: window.location.pathname + '?api=',

    async request(endpoint, method = 'GET', data = null, params = {}) {
        const url = new URL(this.baseUrl + endpoint, window.location.origin);
        for (const [k, v] of Object.entries(params)) {
            url.searchParams.set(k, v);
        }

        const options = {
            method,
            headers: { 'Content-Type': 'application/json' },
        };

        if (data && (method === 'POST' || method === 'PUT')) {
            options.body = JSON.stringify(data);
        }

        try {
            const res = await fetch(url.toString(), options);
            const json = await res.json();
            if (!res.ok || json.status === 'error') {
                throw new Error(json.message || 'Request failed');
            }
            return json;
        } catch (e) {
            if (e.name === 'TypeError') {
                throw new Error('Connection failed - server may be offline');
            }
            throw e;
        }
    },

    get(endpoint, params = {}) {
        return this.request(endpoint, 'GET', null, params);
    },

    post(endpoint, data = {}, params = {}) {
        return this.request(endpoint, 'POST', data, params);
    },

    put(endpoint, data = {}, params = {}) {
        return this.request(endpoint, 'PUT', data, params);
    },

    delete(endpoint, params = {}) {
        return this.request(endpoint, 'DELETE', null, params);
    },

    async checkConnection() {
        try {
            await this.get('auth');
            return true;
        } catch {
            return false;
        }
    }
};
