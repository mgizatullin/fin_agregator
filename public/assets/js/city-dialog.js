/**
 * Модальное окно выбора города.
 * - Сохранение в localStorage, подпись в шапке по умолчанию «Вся Россия».
 * - При выборе города: если есть data-section-base у кнопки — редирект на /base или /base/slug.
 * - Поиск: от 3 символов — запрос к API, результаты поверх списка (список не скрывается и не фильтруется).
 */
(function () {
    'use strict';

    var MODAL_URL = '/city-dialog';
    var SEARCH_URL = '/api/cities/search';
    var MODAL_ROOT_ID = 'city-modal-root';
    var STORAGE_KEY_SLUG = 'selected_city_slug';
    var STORAGE_KEY_NAME = 'selected_city_name';
    var MIN_SEARCH_LEN = 2;
    var SEARCH_DEBOUNCE_MS = 300;
    var ALL_RUSSIA_LABEL = 'Вся Россия';

    function getStoredCity() {
        try {
            return {
                slug: localStorage.getItem(STORAGE_KEY_SLUG) || '',
                name: localStorage.getItem(STORAGE_KEY_NAME) || ALL_RUSSIA_LABEL
            };
        } catch (err) {
            return { slug: '', name: ALL_RUSSIA_LABEL };
        }
    }

    function setStoredCity(slug, name) {
        try {
            localStorage.setItem(STORAGE_KEY_SLUG, slug || '');
            localStorage.setItem(STORAGE_KEY_NAME, name || ALL_RUSSIA_LABEL);
        } catch (err) {}
    }

    function updateCityLabels(name) {
        document.querySelectorAll('.header-city-label').forEach(function (el) {
            el.textContent = name || ALL_RUSSIA_LABEL;
        });
    }

    var lastOpenedCityBtn = null;
    var lastAllowedCitySlugs = null;

    function getSectionBase() {
        if (lastOpenedCityBtn) {
            var base = lastOpenedCityBtn.getAttribute('data-section-base');
            if (base !== null && base !== '') return base;
        }
        var btn = document.getElementById('header-city-btn');
        return (btn && btn.getAttribute('data-section-base')) || '';
    }

    function getAllowedCitySlugs() {
        if (!lastOpenedCityBtn) return null;
        var raw = lastOpenedCityBtn.getAttribute('data-allowed-city-slugs');
        if (!raw) return null;
        try {
            var parsed = JSON.parse(raw);
            if (!Array.isArray(parsed)) return null;
            var set = {};
            parsed.forEach(function (s) {
                if (typeof s === 'string' && s.trim()) set[s.trim()] = true;
            });
            return set;
        } catch (e) {
            return null;
        }
    }

    function filterModalCities(root) {
        if (!root || !lastAllowedCitySlugs) return;

        // Links list + quick links
        root.querySelectorAll('a[data-city-slug]').forEach(function (a) {
            var slug = (a.getAttribute('data-city-slug') || '').trim();
            if (slug === '') return; // "Вся Россия" always allowed
            if (!lastAllowedCitySlugs[slug]) {
                var li = a.closest('li');
                if (li) li.remove();
            }
        });

        // Remove empty groups
        root.querySelectorAll('.city-modal__group').forEach(function (g) {
            var has = g.querySelector('a[data-city-slug]');
            if (!has) g.remove();
        });

        // Hide quick section if empty (only "Вся Россия" left)
        var quickList = root.querySelector('.city-modal__quick-list');
        if (quickList) {
            var quickLinks = quickList.querySelectorAll('a[data-city-slug]');
            if (!quickLinks || quickLinks.length <= 1) {
                var quick = root.querySelector('.city-modal__quick');
                if (quick) quick.style.display = 'none';
            }
        }
    }

    function applyCityChoice(slug, name) {
        setStoredCity(slug, name);
        updateCityLabels(name || ALL_RUSSIA_LABEL);
        var base = getSectionBase();
        if (base) {
            var path = base + (slug ? '/' + slug : '');
            path = '/' + path.replace(/^\/+/, '');
            window.location.href = path;
        }
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
        }
        var resultsEl = root.querySelector('#city-modal-search-results');
        if (resultsEl) {
            resultsEl.hidden = true;
            resultsEl.innerHTML = '';
        }

        filterModalCities(root);
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

        function onCitySelect(slug, name) {
            applyCityChoice(slug, name);
            handleClose();
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

        // Ссылки выбора города: сохраняем в localStorage; редирект только в ключевых разделах (кредиты, вклады, карты, займы, банки)
        root.addEventListener('click', function (e) {
            var link = e.target && e.target.closest && e.target.closest('a[data-city-slug]');
            if (!link) return;
            var slug = link.getAttribute('data-city-slug') || '';
            var name = (link.getAttribute('data-city-name') || '').trim() || ALL_RUSSIA_LABEL;
            setStoredCity(slug, name);
            updateCityLabels(name);
            handleClose();
            var base = (root.getAttribute('data-section-base') || '').trim();
            if (!base) {
                e.preventDefault();
                return;
            }
            var href = (link.getAttribute('href') || '').trim();
            if (href === '' || href === '#') {
                e.preventDefault();
                applyCityChoice(slug, name);
            }
        });

        // Поиск: подсказки выпадающим списком под полем ввода
        var searchInput = root.querySelector('[data-city-search]');
        var searchResultsEl = root.querySelector('#city-modal-search-results');
        if (searchInput && searchResultsEl) {
            var searchTimeout;
            searchInput.addEventListener('input', function () {
                var q = (this.value || '').trim();
                if (searchTimeout) clearTimeout(searchTimeout);
                if (q.length < MIN_SEARCH_LEN) {
                    searchResultsEl.hidden = true;
                    searchResultsEl.innerHTML = '';
                    return;
                }
                searchTimeout = setTimeout(function () {
                    searchResultsEl.hidden = false;
                    searchResultsEl.innerHTML = '<div class="city-modal__search-results-loading">Поиск...</div>';
                    var base = (root.getAttribute('data-section-base') || '').trim();
                    var basePath = base ? '/' + base.replace(/^\/+/, '') : '';
                    fetch(SEARCH_URL + '?q=' + encodeURIComponent(q), {
                        headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
                    })
                        .then(function (res) { return res.json(); })
                        .then(function (data) {
                            var cities = data.cities || [];
                            if (lastAllowedCitySlugs) {
                                cities = cities.filter(function (c) {
                                    return c && c.slug && lastAllowedCitySlugs[c.slug];
                                });
                            }
                            if (cities.length === 0) {
                                searchResultsEl.innerHTML = '<div class="city-modal__search-results-empty">Ничего не найдено</div>';
                                return;
                            }
                            var html = '<ul class="city-modal__search-result-list">';
                            cities.forEach(function (c) {
                                var slug = c.slug || '';
                                var name = (c.name || '').replace(/"/g, '&quot;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
                                var href = basePath + (slug ? '/' + slug : '');
                                html += '<li><a href="' + href + '" class="city-modal__search-result-item" data-city-slug="' + (c.slug || '').replace(/"/g, '&quot;') + '" data-city-name="' + name + '">' + (c.name || '').replace(/</g, '&lt;').replace(/>/g, '&gt;') + '</a></li>';
                            });
                            html += '</ul>';
                            searchResultsEl.innerHTML = html;
                        })
                        .catch(function () {
                            searchResultsEl.innerHTML = '<div class="city-modal__search-results-empty">Ошибка поиска</div>';
                        });
                }, SEARCH_DEBOUNCE_MS);
            });
        }
    }

    function loadAndOpen() {
        var base = getSectionBase();
        var url = MODAL_URL + (base ? '?base=' + encodeURIComponent(base) : '');
        fetch(url, {
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
        var btn = e.target && e.target.closest && e.target.closest('.city-select-btn');
        if (btn) {
            e.preventDefault();
            lastOpenedCityBtn = btn;
            lastAllowedCitySlugs = getAllowedCitySlugs();
            loadAndOpen();
        }
    });

    (function initStoredCity() {
        var stored = getStoredCity();
        updateCityLabels(stored.name || ALL_RUSSIA_LABEL);
    })();

    (function checkRedirectToCity() {
        var cfg = window.__REDIRECT_TO_CITY;
        if (!cfg || !cfg.enabled || !cfg.base) return;
        var stored = getStoredCity();
        if (!stored || !stored.slug) return;
        var cityBtn = document.querySelector('.city-select-btn[data-allowed-city-slugs]');
        if (cityBtn) {
            try {
                var allowed = JSON.parse(cityBtn.getAttribute('data-allowed-city-slugs') || '[]');
                if (Array.isArray(allowed) && allowed.length > 0 && allowed.indexOf(stored.slug) === -1) {
                    return;
                }
            } catch (e) {}
        }
        var path = '/' + String(cfg.base).replace(/^\/+/, '') + '/' + stored.slug;
        window.location.replace(path);
    })();
})();
