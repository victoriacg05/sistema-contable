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

const REQUEST_TIMEOUT_MS = 30000;
const pendingForms = new Map();
let navigationTimeout = null;

const progressBar = document.createElement('div');
progressBar.setAttribute('role', 'progressbar');
progressBar.setAttribute('aria-label', 'Procesando solicitud');
progressBar.className = 'fixed left-0 top-0 z-[100] hidden h-1 w-full overflow-hidden bg-red-100';
progressBar.innerHTML = '<div class="h-full w-1/2 animate-pulse bg-[#b71c1c]"></div>';
document.body.appendChild(progressBar);

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

const mostrarProgreso = () => {
    progressBar.classList.remove('hidden');
};

const ocultarProgreso = () => {
    progressBar.classList.add('hidden');
};

const restaurarFormulario = (form) => {
    const pending = pendingForms.get(form);

    if (pending) {
        window.clearTimeout(pending.timeout);
    }

    form.removeAttribute('aria-busy');
    delete form.dataset.requestPending;

    form.querySelectorAll('button, input[type="submit"]').forEach((control) => {
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

    if (pendingForms.size === 0) {
        ocultarProgreso();
    }
};

const bloquearFormulario = (form) => {
    if (form.dataset.requestPending === 'true') {
        return false;
    }

    form.dataset.requestPending = 'true';
    form.setAttribute('aria-busy', 'true');
    mostrarProgreso();

    form.querySelectorAll('button, input[type="submit"]').forEach((control) => {
        control.dataset.originalDisabled = control.disabled ? 'true' : 'false';

        if (control.matches('button[type="submit"]')) {
            control.dataset.originalHtml = control.innerHTML;
            control.innerHTML = '<span class="inline-flex items-center gap-2">'
                + '<span class="h-4 w-4 animate-spin rounded-full border-2 border-white border-t-transparent"></span>'
                + '<span>Procesando...</span></span>';
        } else if (control.matches('input[type="submit"]')) {
            control.dataset.originalValue = control.value;
            control.value = 'Procesando...';
        }

        control.disabled = true;
    });

    const timeout = window.setTimeout(() => {
        ocultarProgreso();

        form.querySelectorAll('button[type="submit"], input[type="submit"]').forEach((control) => {
            if (control.dataset.originalHtml !== undefined) {
                control.innerHTML = 'Solicitud en proceso';
            } else if (control.dataset.originalValue !== undefined) {
                control.value = 'Solicitud en proceso';
            }
        });

        mostrarMensaje(
            'La solicitud sigue procesándose. No la envíe nuevamente; actualice la página para comprobar el resultado.',
        );
    }, REQUEST_TIMEOUT_MS);

    pendingForms.set(form, { timeout });

    return true;
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
    form.addEventListener('submit', (event) => {
        if (event.defaultPrevented) {
            return;
        }

        if (!bloquearFormulario(form)) {
            event.preventDefault();
        }
    });
});

document.addEventListener('click', (event) => {
    const link = event.target.closest('a[href]');

    if (!link
        || event.defaultPrevented
        || event.button !== 0
        || event.metaKey
        || event.ctrlKey
        || event.shiftKey
        || event.altKey
        || link.target === '_blank'
        || link.hasAttribute('download')
        || link.getAttribute('href').startsWith('#')) {
        return;
    }

    mostrarProgreso();
    link.setAttribute('aria-disabled', 'true');
    link.style.pointerEvents = 'none';

    window.clearTimeout(navigationTimeout);
    navigationTimeout = window.setTimeout(() => {
        link.removeAttribute('aria-disabled');
        link.style.pointerEvents = '';
        ocultarProgreso();
        mostrarMensaje(
            'No se pudo completar la navegación. Verifique su conexión e inténtelo nuevamente.',
        );
    }, REQUEST_TIMEOUT_MS);
});

window.addEventListener('pageshow', (event) => {
    pendingForms.forEach((_, form) => restaurarFormulario(form));
    window.clearTimeout(navigationTimeout);
    navigationTimeout = null;
    ocultarProgreso();

    document.querySelectorAll('a[aria-disabled="true"]').forEach((link) => {
        link.removeAttribute('aria-disabled');
        link.style.pointerEvents = '';
    });

    if (event.persisted && typeof window.refreshCsrfToken === 'function') {
        window.refreshCsrfToken().catch(() => {
            // El siguiente envío mostrará el error de sesión correspondiente.
        });
    }
});

window.addEventListener('online', () => {
    mostrarMensaje('La conexión se restableció.', 'success');
});

window.addEventListener('offline', () => {
    mostrarMensaje('No hay conexión a internet. Las acciones se reanudarán cuando vuelva la conexión.');
});
