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
