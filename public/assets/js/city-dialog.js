/**
 * City selection modal: load HTML via fetch, open on .city-select-btn click.
 * On city select: save to localStorage and update button label; no redirect.
 */
(function () {
    'use strict';

    var MODAL_URL = '/city-dialog';
    var MODAL_ROOT_ID = 'city-modal-root';
    var STORAGE_KEY_SLUG = 'selected_city_slug';
    var STORAGE_KEY_NAME = 'selected_city_name';

    function getStoredCity() {
        try {
            return {
                slug: localStorage.getItem(STORAGE_KEY_SLUG) || '',
                name: localStorage.getItem(STORAGE_KEY_NAME) || ''
            };
        } catch (err) {
            return { slug: '', name: '' };
        }
    }

    function setStoredCity(slug, name) {
        try {
            localStorage.setItem(STORAGE_KEY_SLUG, slug || '');
            localStorage.setItem(STORAGE_KEY_NAME, name || '');
        } catch (err) {}
    }

    function updateCityLabels(name) {
        document.querySelectorAll('.header-city-label').forEach(function (el) {
            el.textContent = name || 'Выбрать город';
        });
    }

    function openModal(html) {
        var root = document.getElementById(MODAL_ROOT_ID);
        if (!root) {
            var wrap = document.createElement('div');
            wrap.innerHTML = html;
            root = wrap.firstElementChild;
            if (root) {
                root.id = MODAL_ROOT_ID;
                document.body.appendChild(root);
            }
        }
        if (!root) return;

        root.setAttribute('aria-hidden', 'false');
        root.classList.add('city-modal--open');
        document.body.classList.add('city-modal-open');

        var searchInput = root.querySelector('[data-city-search]');
        if (searchInput) {
            searchInput.value = '';
            searchInput.dispatchEvent(new Event('input', { bubbles: true }));
        }

        bindModalEvents(root);
    }

    function closeModal() {
        var root = document.getElementById(MODAL_ROOT_ID);
        if (root) {
            root.setAttribute('aria-hidden', 'true');
            root.classList.remove('city-modal--open');
            document.body.classList.remove('city-modal-open');
        }
    }

    function removeModal() {
        var root = document.getElementById(MODAL_ROOT_ID);
        if (root && root.parentNode) {
            root.parentNode.removeChild(root);
        }
    }

    function bindModalEvents(root) {
        if (!root || root._cityDialogBound) return;
        root._cityDialogBound = true;

        function handleClose() {
            closeModal();
            setTimeout(removeModal, 300);
        }

        root.querySelectorAll('[data-city-modal-close]').forEach(function (el) {
            el.addEventListener('click', handleClose);
        });

        document.addEventListener('keydown', function onEsc(e) {
            if (e.key === 'Escape') {
                closeModal();
                setTimeout(removeModal, 300);
                document.removeEventListener('keydown', onEsc);
            }
        });

        var searchInput = root.querySelector('[data-city-search]');
        if (searchInput) {
            searchInput.addEventListener('input', function () {
                var q = (this.value || '').trim().toLowerCase();
                root.querySelectorAll('.city-modal__group').forEach(function (group) {
                    var visible = false;
                    group.querySelectorAll('.city-modal-item').forEach(function (item) {
                        var name = (item.getAttribute('data-city-name') || '').toLowerCase();
                        var match = !q || name.indexOf(q) !== -1;
                        item.style.display = match ? '' : 'none';
                        if (match) visible = true;
                    });
                    group.style.display = visible ? '' : 'none';
                });
            });
        }

        root.querySelectorAll('.city-modal-item').forEach(function (link) {
            link.addEventListener('click', function (e) {
                e.preventDefault();
                var slug = this.getAttribute('data-city-slug');
                var name = (this.getAttribute('data-city-name') || '').trim();
                if (!slug) return;
                setStoredCity(slug, name);
                updateCityLabels(name || 'Выбрать город');
                handleClose();
            });
        });
    }

    function loadAndOpen() {
        fetch(MODAL_URL, {
            method: 'GET',
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'text/html' }
        })
            .then(function (res) { return res.text(); })
            .then(function (html) {
                openModal(html);
            })
            .catch(function () {
                console.warn('City dialog: failed to load');
            });
    }

    document.addEventListener('click', function (e) {
        if (e.target && e.target.closest && e.target.closest('.city-select-btn')) {
            e.preventDefault();
            loadAndOpen();
        }
    });

    (function initStoredCity() {
        var stored = getStoredCity();
        if (stored.name) {
            updateCityLabels(stored.name);
        }
    })();
})();
