document.addEventListener('DOMContentLoaded', () => {
    const forms = document.querySelectorAll('form');
    const messages = {
        required: 'Este campo es obligatorio.',
        name: 'Usa solo letras y espacios simples.',
        email: 'Ingresa un correo válido.',
        phone: 'El teléfono solo acepta números y símbolos permitidos.',
        ci: 'La C.I. solo acepta letras, números y guiones.',
        password: 'La contraseña debe tener al menos 8 caracteres, con una mayúscula, una minúscula, un número y un símbolo.',
        confirm: 'Las contraseñas no coinciden. Escríbelas igual en ambos campos.',
        confirmRequired: 'Repite la contraseña para confirmarla.',
        // Los mismos que da el servidor para el año de una gestión.
        integer: 'Este campo debe ser un número entero.',
        year: 'Escribe un año entre :min y :max.'
    };

    function validateField(field) {
        const value = field.value.trim();
        const rule = field.dataset.rule;
        let message = '';

        if (rule === 'year' && field.validity && field.validity.badInput) {
            // En un campo numérico lo mal escrito («20e») llega vacío: no falta, está mal escrito.
            message = messages.integer;
        } else if (field.required && !value) {
            message = messages.required;
        } else if (value && rule === 'year' && !/^-?\d+$/.test(value)) {
            message = messages.integer;
        } else if (value && rule === 'year'
            && ((field.min !== '' && Number(value) < Number(field.min)) || (field.max !== '' && Number(value) > Number(field.max)))) {
            message = messages.year.replace(':min', field.min).replace(':max', field.max);
        } else if (value && rule === 'name' && !/^[\p{L}]+(?:[ \t]+[\p{L}]+)*$/u.test(value)) {
            message = messages.name;
        } else if (value && rule === 'email' && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value)) {
            message = messages.email;
        } else if (value && rule === 'phone' && !/^[0-9+() -]+$/.test(value)) {
            message = messages.phone;
        } else if (value && rule === 'ci' && !/^[0-9A-Za-z-]+$/.test(value)) {
            message = messages.ci;
        } else if (value && rule === 'password' && !/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[^A-Za-z\d]).{8,}$/.test(value)) {
            message = messages.password;
        } else if (rule === 'confirm') {
            // Debe coincidir con el campo indicado en data-match (se compara sin recortar espacios).
            const target = document.getElementById(field.dataset.match);
            const original = target ? target.value : '';
            if (!field.value && original) {
                message = messages.confirmRequired;
            } else if (field.value !== original) {
                message = messages.confirm;
            }
        }

        field.setCustomValidity(message);
        field.classList.toggle('is-invalid', Boolean(message));
        const feedback = field.parentElement.querySelector('.invalid-feedback');
        if (feedback) feedback.textContent = message;
        return !message;
    }

    forms.forEach(form => {
        form.querySelectorAll('.js-user-field, .js-person-field').forEach(field => {
            if (field.dataset.rule === 'phone') {
                field.addEventListener('input', () => {
                    field.value = field.value.replace(/[^0-9+() -]/g, '');
                });
            }
            field.addEventListener('input', () => {
                // El año se revisa al salir del campo o al enviar: con «2», «20»... no se avisa a medio
                // escribir. Si ya tenía aviso, sí se revisa, para quitarlo apenas quede bien.
                if (field.dataset.rule === 'year' && !field.classList.contains('is-invalid')) return;
                validateField(field);
                // Si cambia la contraseña y ya hay algo en la confirmación, se vuelve a comparar.
                if (field.id) {
                    form.querySelectorAll('[data-rule="confirm"][data-match="' + field.id + '"]').forEach(confirmField => {
                        if (confirmField.value) validateField(confirmField);
                    });
                }
            });
            field.addEventListener('blur', () => validateField(field));
            field.addEventListener('change', () => validateField(field));
        });
        form.addEventListener('submit', event => {
            const fields = Array.from(new Set(form.querySelectorAll('.js-user-field, [data-rule="confirm"]')));
            const valid = fields.map(validateField).every(Boolean);
            if (!valid) event.preventDefault();
        });
    });
});
