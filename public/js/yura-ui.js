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

    // Las ventanas (modales) que vienen dentro de una lista en vivo se abren desde <body>: dentro de
    // una tarjeta animada quedarían detrás del fondo oscuro.
    if (window.jQuery) {
        window.jQuery(document).on('show.bs.modal', '[data-live-results] .modal, [data-live-pagination] .modal', function () {
            var stale = document.body.querySelector(':scope > #' + CSS.escape(this.id));
            if (stale && stale !== this) stale.remove();
            document.body.appendChild(this);
        });
    }

    // ---------------------------------------------------------------- Confirmación propia de la web
    // <form data-confirm="¿Eliminar este curso?"> abre la ventana de YURA en lugar del aviso del navegador.
    // Si el formulario borra (_method DELETE) el botón dice «Eliminar» en rojo; si no, «Continuar».
    function confirmModal() {
        var modal = document.getElementById('yura-confirm');
        if (modal) return modal;
        modal = document.createElement('div');
        modal.className = 'modal fade';
        modal.id = 'yura-confirm';
        modal.tabIndex = -1;
        modal.setAttribute('role', 'dialog');
        modal.setAttribute('aria-modal', 'true');
        modal.setAttribute('aria-labelledby', 'yura-confirm-title');
        modal.innerHTML =
            '<div class="modal-dialog modal-dialog-centered" role="document"><div class="modal-content yura-confirm">' +
            '<div class="modal-body text-center px-4 pt-4 pb-2">' +
            '<div class="yura-confirm-icon" aria-hidden="true"><i class="fas"></i></div>' +
            '<h5 class="modal-title mb-2" id="yura-confirm-title"></h5>' +
            '<p class="yura-confirm-text mb-0"></p></div>' +
            '<div class="modal-footer justify-content-center border-0 pb-4">' +
            '<button type="button" class="btn btn-light px-4" data-dismiss="modal">Cancelar</button>' +
            '<button type="button" class="btn px-4" data-confirm-ok></button></div></div></div>';
        document.body.appendChild(modal);
        return modal;
    }

    var pendingForm = null;
    document.addEventListener('submit', function (event) {
        var form = event.target;
        if (!form.matches || !form.matches('form[data-confirm]') || form.dataset.confirmed === '1' || !window.jQuery) return;
        event.preventDefault();
        pendingForm = form;
        var method = form.querySelector('input[name="_method"]');
        var destructive = method && method.value.toUpperCase() === 'DELETE';
        var modal = confirmModal();
        modal.querySelector('.yura-confirm-icon').classList.toggle('is-danger', destructive);
        modal.querySelector('.yura-confirm-icon i').className = 'fas ' + (destructive ? 'fa-trash-alt' : 'fa-question');
        modal.querySelector('#yura-confirm-title').textContent = form.dataset.confirmTitle || (destructive ? 'Confirmar eliminación' : 'Confirmar acción');
        modal.querySelector('.yura-confirm-text').textContent = form.dataset.confirm;
        var ok = modal.querySelector('[data-confirm-ok]');
        ok.className = 'btn px-4 ' + (destructive ? 'btn-danger' : 'btn-primary');
        ok.textContent = form.dataset.confirmOk || (destructive ? 'Eliminar' : 'Continuar');
        window.jQuery(modal).modal('show');
        window.jQuery(modal).one('shown.bs.modal', function () { modal.querySelector('[data-dismiss="modal"]').focus(); });
    }, true);

    document.addEventListener('click', function (event) {
        if (!event.target.closest('#yura-confirm [data-confirm-ok]') || !pendingForm) return;
        var form = pendingForm;
        pendingForm = null;
        form.dataset.confirmed = '1';
        window.jQuery('#yura-confirm').modal('hide');
        if (form.requestSubmit) form.requestSubmit(); else form.submit();
    });

    // ---------------------------------------------------------------- Avisos de éxito
    // Los avisos de éxito se van solos a los 2 segundos; los de error quedan hasta cerrarlos.
    function initAutoHide(alert) {
        setTimeout(function () {
            if (window.jQuery) { window.jQuery(alert).alert('close'); } else { alert.remove(); }
        }, 2000);
    }

    // ---------------------------------------------------------------- Tablas anchas
    // Barra horizontal también arriba de la tabla, sincronizada con la de abajo: siempre hay una a la vista.
    function initDualScroll(box) {
        var top = document.createElement('div');
        top.className = 'table-scroll-top';
        top.setAttribute('aria-hidden', 'true');
        var inner = document.createElement('div');
        top.appendChild(inner);
        box.parentNode.insertBefore(top, box);
        function size() {
            inner.style.width = box.scrollWidth + 'px';
            top.hidden = box.scrollWidth <= box.clientWidth + 1;
        }
        top.addEventListener('scroll', function () { if (box.scrollLeft !== top.scrollLeft) box.scrollLeft = top.scrollLeft; });
        box.addEventListener('scroll', function () { if (top.scrollLeft !== box.scrollLeft) top.scrollLeft = box.scrollLeft; });
        window.addEventListener('resize', size);
        size();
    }

    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('.alert[data-autohide]').forEach(initAutoHide);
        document.querySelectorAll('.table-scroll-box').forEach(initDualScroll);
        document.querySelectorAll('form[data-filter-required]').forEach(initRequiredFilters);
        document.querySelectorAll('form[data-live-search]').forEach(initLiveSearch);
        document.querySelectorAll('[data-live-pagination][id]').forEach(initLivePagination);
    });
})();
