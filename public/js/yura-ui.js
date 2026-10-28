/*
 * Utilidades comunes de las pantallas internas de YURA.
 *
 *  - YuraToast.show(mensaje, 'success' | 'error'): aviso flotante abajo a la derecha.
 *  - <form data-filter-required="subject_id,parallel_id">: los filtros solo recargan la
 *    página cuando todos los campos requeridos tienen valor (una sola petición).
 *  - <form data-live-search="#contenedor">: búsqueda en vivo. Al escribir (con espera de
 *    350 ms) o cambiar un filtro, trae la página por fetch y reemplaza solo el contenedor.
 *    Al vaciar el buscador, la lista vuelve completa de inmediato.
 */
(function () {
    'use strict';

    var YuraToast = {
        container: function () {
            var el = document.getElementById('yura-toast-container');
            if (!el) {
                el = document.createElement('div');
                el.id = 'yura-toast-container';
                el.className = 'yura-toast-container';
                el.setAttribute('aria-live', 'polite');
                document.body.appendChild(el);
            }
            return el;
        },
        show: function (message, type) {
            var toast = document.createElement('div');
            toast.className = 'yura-toast ' + (type === 'error' ? 'is-error' : 'is-success');
            toast.setAttribute('role', type === 'error' ? 'alert' : 'status');
            toast.textContent = message;
            this.container().appendChild(toast);
            requestAnimationFrame(function () { toast.classList.add('is-visible'); });
            setTimeout(function () {
                toast.classList.remove('is-visible');
                setTimeout(function () { toast.remove(); }, 300);
            }, type === 'error' ? 4000 : 2000);
        }
    };
    window.YuraToast = YuraToast;

    function initRequiredFilters(form) {
        var required = form.dataset.filterRequired.split(',').map(function (n) { return n.trim(); });
        var hint = form.querySelector('[data-filter-hint]');

        function missing() {
            return required.filter(function (name) {
                var field = form.elements[name];
                return !field || String(field.value).trim() === '';
            });
        }

        form.addEventListener('change', function (event) {
            if (!event.target.name) return;
            var field = event.target.closest('.fb-field');
            if (field) field.classList.toggle('is-set', String(event.target.value).trim() !== '');

            if (missing().length === 0) {
                if (hint) hint.hidden = true;
                form.submit();
            } else if (hint) {
                hint.hidden = false;
            }
        });
    }

    function initLiveSearch(form) {
        var target = document.querySelector(form.dataset.liveSearch);
        if (!target) return;
        var timer = null;
        var controller = null;

        function load(url) {
            if (controller) controller.abort();
            controller = new AbortController();
            target.classList.add('is-loading');
            fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' }, signal: controller.signal })
                .then(function (response) {
                    if (!response.ok) throw new Error(response.status);
                    return response.text();
                })
                .then(function (html) {
                    var doc = new DOMParser().parseFromString(html, 'text/html');
                    var fresh = doc.querySelector(form.dataset.liveSearch);
                    if (fresh) target.innerHTML = fresh.innerHTML;
                    target.classList.remove('is-loading');
                    history.replaceState(null, '', url);
                })
                .catch(function (error) {
                    if (error.name === 'AbortError') return;
                    target.classList.remove('is-loading');
                    YuraToast.show('No se pudo actualizar la lista. Revisa tu conexión.', 'error');
                });
        }

        function urlFromForm() {
            var params = new URLSearchParams(new FormData(form));
            Array.from(params.keys()).forEach(function (key) {
                if (params.get(key) === '') params.delete(key);
            });
            var query = params.toString();
            return form.action + (query ? '?' + query : '');
        }

        form.addEventListener('submit', function (event) {
            event.preventDefault();
            clearTimeout(timer);
            load(urlFromForm());
        });
        form.addEventListener('input', function (event) {
            if (event.target.type !== 'search' && event.target.type !== 'text') return;
            clearTimeout(timer);
            var empty = event.target.value.trim() === '';
            timer = setTimeout(function () { load(urlFromForm()); }, empty ? 0 : 350);
        });
        form.addEventListener('change', function (event) {
            if (event.target.tagName !== 'SELECT') return;
            var field = event.target.closest('.fb-field');
            if (field) field.classList.toggle('is-set', event.target.value !== '');
            clearTimeout(timer);
            load(urlFromForm());
        });
        // La paginación dentro del contenedor también se carga sin recargar la página.
        target.addEventListener('click', function (event) {
            var link = event.target.closest('.pagination a');
            if (!link) return;
            event.preventDefault();
            load(link.href);
        });
    }

    // <div id="..." data-live-pagination>: sus enlaces de página se cargan sin recargar ni subir la pantalla.
    function initLivePagination(box) {
        box.addEventListener('click', function (event) {
            var link = event.target.closest('.pagination a');
            if (!link) return;
            event.preventDefault();
            box.classList.add('is-loading');
            fetch(link.href, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
                .then(function (response) {
                    if (!response.ok) throw new Error(response.status);
                    return response.text();
                })
                .then(function (html) {
                    var fresh = new DOMParser().parseFromString(html, 'text/html').getElementById(box.id);
                    if (fresh) box.innerHTML = fresh.innerHTML;
                    box.classList.remove('is-loading');
                    history.replaceState(null, '', link.href);
                })
                .catch(function () {
                    box.classList.remove('is-loading');
                    window.location.href = link.href;
                });
        });
    }

    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('form[data-filter-required]').forEach(initRequiredFilters);
        document.querySelectorAll('form[data-live-search]').forEach(initLiveSearch);
        document.querySelectorAll('[data-live-pagination][id]').forEach(initLivePagination);
    });
})();
