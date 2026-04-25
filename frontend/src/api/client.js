export const API_BASE = import.meta.env.VITE_API_BASE_URL || '/api';

class ApiClient {
    constructor() {
        this.csrfToken = null;
    }

    async request(endpoint, options = {}) {
        const url = `${API_BASE}${endpoint}`;
        const config = {
            credentials: 'include',
            headers: {
                ...(options.headers || {}),
            },
            ...options,
        };

        if (['POST', 'PUT', 'DELETE'].includes(config.method)) {
            if (this.csrfToken) {
                config.headers['X-CSRF-Token'] = this.csrfToken;
            }
        }

        if (config.body && !(config.body instanceof FormData)) {
            config.headers['Content-Type'] = 'application/json';
            config.body = JSON.stringify(config.body);
        }

        const response = await fetch(url, config);

        // SAFE JSON PARSING - won't crash on empty response
        const text = await response.text();
        const data = text ? JSON.parse(text) : {};

        if (data.data?.csrf_token) {
            this.csrfToken = data.data.csrf_token;
        }

        if (!response.ok) {
            const error = new Error(data.message || 'Request failed');
            error.status = response.status;
            error.errors = data.errors || null;
            throw error;
        }

        return data;
    }

    get(endpoint) {
        return this.request(endpoint, { method: 'GET' });
    }

    post(endpoint, body) {
        return this.request(endpoint, { method: 'POST', body });
    }

    put(endpoint, body) {
        return this.request(endpoint, { method: 'PUT', body });
    }

    delete(endpoint) {
        return this.request(endpoint, { method: 'DELETE' });
    }

    upload(endpoint, formData) {
        return this.request(endpoint, {
            method: 'POST',
            body: formData,
            headers: this.csrfToken ? { 'X-CSRF-Token': this.csrfToken } : {},
        });
    }
}

const api = new ApiClient();
export default api;