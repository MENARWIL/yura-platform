document.addEventListener('DOMContentLoaded', () => {
    const forms = document.querySelectorAll('form');
    const messages = {
        required: document.documentElement.lang === 'qu' ? 'Kayqa huntachinapaqmi.' : 'Este campo es obligatorio.',
        name: document.documentElement.lang === 'qu' ? 'Sutipi qillqakunalla, tupanachisqa espacioswan.' : 'Usa solo letras y espacios simples.',
        email: document.documentElement.lang === 'qu' ? 'Emailqa allin formatopi kanman.' : 'Ingresa un correo válido.',
        phone: document.documentElement.lang === 'qu' ? 'Telefonoqa yupaykunallawan kanman.' : 'El teléfono solo acepta números y símbolos permitidos.',
        ci: document.documentElement.lang === 'qu' ? 'CIqa yupaykunawan, qillqakunawan utaq guionwan kanman.' : 'La C.I. solo acepta letras, números y guiones.',
        password: document.documentElement.lang === 'qu' ? 'Contraseñaqa 8 qillqayuq, hatun qillqayuq, yupayuq chaymanta simboloyuq kanman.' : 'La contraseña debe tener 8 caracteres, mayúsculas, números y símbolos.'
    };

    function validateField(field) {
        const value = field.value.trim();
        const rule = field.dataset.rule;
        let message = '';

        if (field.required && !value) {
            message = messages.required;
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
            field.addEventListener('input', () => validateField(field));
            field.addEventListener('blur', () => validateField(field));
            field.addEventListener('change', () => validateField(field));
        });
        form.addEventListener('submit', event => {
            const valid = Array.from(form.querySelectorAll('.js-user-field')).every(validateField);
            if (!valid) event.preventDefault();
        });
    });
});
