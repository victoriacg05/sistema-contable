import axios from 'axios';
window.axios = axios;

window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';
window.axios.defaults.timeout = 20000;

const csrfMeta = document.querySelector('meta[name="csrf-token"]');
const csrfRefreshMeta = document.querySelector('meta[name="csrf-refresh-url"]');

const actualizarTokenCsrf = (token) => {
    if (!token) {
        return;
    }

    csrfMeta?.setAttribute('content', token);
    window.axios.defaults.headers.common['X-CSRF-TOKEN'] = token;

    document.querySelectorAll('input[name="_token"]').forEach((input) => {
        input.value = token;
    });
};

actualizarTokenCsrf(csrfMeta?.content);

window.refreshCsrfToken = async () => {
    if (!csrfRefreshMeta?.content) {
        return null;
    }

    const response = await window.axios.get(csrfRefreshMeta.content, {
        headers: { Accept: 'application/json' },
        skipCsrfRefresh: true,
    });

    actualizarTokenCsrf(response.data.token);

    return response.data;
};

window.axios.interceptors.response.use(
    (response) => response,
    async (error) => {
        const config = error.config;

        if (error.response?.status === 419
            && config
            && !config.csrfRetried
            && !config.skipCsrfRefresh) {
            config.csrfRetried = true;

            await window.refreshCsrfToken();
            config.headers['X-CSRF-TOKEN'] = csrfMeta?.content;

            return window.axios(config);
        }

        return Promise.reject(error);
    },
);
