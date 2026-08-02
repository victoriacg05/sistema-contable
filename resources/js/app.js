import './bootstrap';

import Alpine from 'alpinejs';

window.Alpine = Alpine;

Alpine.start();

// Traduce al español los mensajes de validación nativos del navegador (HTML5).
const mensajesValidacion = (input) => {
    const validity = input.validity;

    if (validity.valueMissing) {
        if (input.type === 'checkbox' || input.type === 'radio') {
            return 'Por favor, marca esta casilla si deseas continuar.';
        }
        if (input.tagName === 'SELECT') {
            return 'Por favor, selecciona una opción de la lista.';
        }
        return 'Por favor, completa este campo.';
    }

    if (validity.typeMismatch) {
        if (input.type === 'email') {
            return 'Por favor, introduce una dirección de correo electrónico válida.';
        }
        if (input.type === 'url') {
            return 'Por favor, introduce una URL válida.';
        }
        return 'Por favor, introduce un valor con el formato correcto.';
    }

    if (validity.patternMismatch) {
        return 'Por favor, respeta el formato solicitado.';
    }

    if (validity.tooShort) {
        return `Usa al menos ${input.minLength} caracteres (actualmente estás usando ${input.value.length}).`;
    }

    if (validity.tooLong) {
        return `Usa como máximo ${input.maxLength} caracteres (actualmente estás usando ${input.value.length}).`;
    }

    if (validity.rangeUnderflow) {
        return `El valor debe ser mayor o igual a ${input.min}.`;
    }

    if (validity.rangeOverflow) {
        return `El valor debe ser menor o igual a ${input.max}.`;
    }

    if (validity.stepMismatch) {
        return 'Por favor, introduce un valor válido.';
    }

    if (validity.badInput) {
        return 'Por favor, introduce un número válido.';
    }

    if (validity.customError) {
        return 'El valor ingresado no es válido.';
    }

    return 'Por favor, revisa el valor ingresado.';
};

document.addEventListener('invalid', (event) => {
    const input = event.target;

    if (typeof input.setCustomValidity !== 'function') {
        return;
    }

    input.setCustomValidity(mensajesValidacion(input));
}, true);

const limpiarMensajeValidacion = (event) => {
    const input = event.target;

    if (typeof input.setCustomValidity === 'function') {
        input.setCustomValidity('');
    }
};

document.addEventListener('input', limpiarMensajeValidacion, true);
document.addEventListener('change', limpiarMensajeValidacion, true);

const pendingForms = new Map();
const CSRF_MAX_AGE_MS = 30 * 60 * 1000;
let lastCsrfRefresh = Date.now();

const notificationContainer = document.createElement('div');
notificationContainer.className = 'fixed right-4 top-4 z-[110] flex max-w-md flex-col gap-3';
document.body.appendChild(notificationContainer);

const mostrarMensaje = (message, type = 'error') => {
    const notification = document.createElement('div');
    const colors = type === 'success'
        ? 'border-green-300 bg-green-50 text-green-800'
        : 'border-red-300 bg-red-50 text-red-800';

    notification.className = `rounded-xl border px-5 py-4 font-semibold shadow-lg ${colors}`;
    notification.textContent = message;
    notificationContainer.appendChild(notification);

    window.setTimeout(() => notification.remove(), 6000);
};

const restaurarFormulario = (form) => {
    form.removeAttribute('aria-busy');
    delete form.dataset.requestPending;

    form.querySelectorAll('button[type="submit"], input[type="submit"]').forEach((control) => {
        if (control.dataset.originalDisabled === 'true') {
            control.disabled = true;
        } else {
            control.disabled = false;
        }

        if (control.dataset.originalHtml !== undefined) {
            control.innerHTML = control.dataset.originalHtml;
            delete control.dataset.originalHtml;
        }

        if (control.dataset.originalValue !== undefined) {
            control.value = control.dataset.originalValue;
            delete control.dataset.originalValue;
        }

        delete control.dataset.originalDisabled;
    });

    pendingForms.delete(form);
};

const bloquearFormulario = (form) => {
    if (form.dataset.requestPending === 'true') {
        return false;
    }

    form.dataset.requestPending = 'true';
    form.setAttribute('aria-busy', 'true');

    form.querySelectorAll('button[type="submit"], input[type="submit"]').forEach((control) => {
        control.dataset.originalDisabled = control.disabled ? 'true' : 'false';

        if (control.matches('button[type="submit"]')) {
            control.dataset.originalHtml = control.innerHTML;
            control.textContent = 'Procesando...';
        } else if (control.matches('input[type="submit"]')) {
            control.dataset.originalValue = control.value;
            control.value = 'Procesando...';
        }

        control.disabled = true;
    });

    pendingForms.set(form, true);

    return true;
};

const refrescarCsrfSiEsNecesario = async () => {
    if (Date.now() - lastCsrfRefresh < CSRF_MAX_AGE_MS
        || typeof window.refreshCsrfToken !== 'function') {
        return;
    }

    await window.refreshCsrfToken();
    lastCsrfRefresh = Date.now();
};

document.querySelectorAll('form[data-submit-on-click]').forEach((form) => {
    form.addEventListener('keydown', (event) => {
        const target = event.target;
        const isActionControl = target.matches('button, input[type="submit"]');

        if (event.key === 'Enter'
            && target.tagName !== 'TEXTAREA'
            && !target.isContentEditable
            && !isActionControl) {
            event.preventDefault();
        }
    });
});

document.querySelectorAll('form').forEach((form) => {
    form.addEventListener('submit', async (event) => {
        if (event.defaultPrevented) {
            return;
        }

        if (form.dataset.requestPending === 'true') {
            event.preventDefault();
            return;
        }

        if (Date.now() - lastCsrfRefresh >= CSRF_MAX_AGE_MS
            && form.dataset.csrfReady !== 'true') {
            event.preventDefault();
            const submitter = event.submitter;
            bloquearFormulario(form);

            try {
                await refrescarCsrfSiEsNecesario();
                restaurarFormulario(form);
                form.dataset.csrfReady = 'true';

                if (submitter) {
                    form.requestSubmit(submitter);
                } else {
                    form.requestSubmit();
                }
            } catch (error) {
                restaurarFormulario(form);
                mostrarMensaje(
                    'No se pudo validar la sesión. Recargue la página e intente nuevamente.',
                );
            } finally {
                delete form.dataset.csrfReady;
            }

            return;
        }

        if (!bloquearFormulario(form)) {
            event.preventDefault();
        }
    });
});

window.addEventListener('pageshow', (event) => {
    pendingForms.forEach((_, form) => restaurarFormulario(form));

    if (event.persisted && typeof window.refreshCsrfToken === 'function') {
        window.refreshCsrfToken()
            .then(() => {
                lastCsrfRefresh = Date.now();
            })
            .catch(() => {});
    }
});

document.addEventListener('visibilitychange', () => {
    if (document.visibilityState === 'visible') {
        refrescarCsrfSiEsNecesario().catch(() => {});
    }
});

window.addEventListener('app:error', (event) => {
    mostrarMensaje(event.detail?.message ?? 'Ocurrió un error inesperado.');
});

window.addEventListener('unhandledrejection', (event) => {
    if (event.reason?.appNotified) {
        return;
    }

    mostrarMensaje('No se pudo completar una operación. Inténtelo nuevamente.');
});

window.addEventListener('error', () => {
    mostrarMensaje('Ocurrió un error en la interfaz. Recargue la página.');
});

window.addEventListener('online', () => {
    mostrarMensaje('La conexión se restableció.', 'success');
});

window.addEventListener('offline', () => {
    mostrarMensaje('No hay conexión a internet. Las acciones se reanudarán cuando vuelva la conexión.');
});
