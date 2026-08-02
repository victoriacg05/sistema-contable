import axios from 'axios';
window.axios = axios;

window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';
window.axios.defaults.timeout = 10000;

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
        timeout: 5000,
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

            try {
                await window.refreshCsrfToken();
                config.headers = config.headers ?? {};
                config.headers['X-CSRF-TOKEN'] = csrfMeta?.content;

                return window.axios(config);
            } catch (refreshError) {
                window.dispatchEvent(new CustomEvent('app:error', {
                    detail: {
                        message: 'La sesión venció. Recargue la página para continuar.',
                    },
                }));
                if (refreshError && typeof refreshError === 'object') {
                    refreshError.appNotified = true;
                }

                return Promise.reject(refreshError);
            }
        }

        if (error.code === 'ECONNABORTED' || !error.response) {
            window.dispatchEvent(new CustomEvent('app:error', {
                detail: {
                    message: 'La solicitud no respondió a tiempo. Verifique su conexión.',
                },
            }));
            error.appNotified = true;
        }

        return Promise.reject(error);
    },
);
