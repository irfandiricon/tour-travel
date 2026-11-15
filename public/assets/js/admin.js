/**
 * Admin AJAX wiring (vanilla JS).
 *
 * Endpoints (relative to base-url):
 *   GET  admin/api/<entity>/list?page=&per_page=
 *   POST admin/api/<entity>/create
 *   POST admin/api/<entity>/update/{id}
 *   POST admin/api/<entity>/delete/{id}
 *   POST admin/api/site-config/save
 *
 * Markup hooks: [data-admin-list], [data-admin-form], [data-lang-field],
 * [data-error-for], #form-summary, #toast-region (aria-live).
 */
(function () {
    'use strict';

    var PER_PAGE = 10;

    function meta(name) {
        var el = document.querySelector('meta[name="' + name + '"]');
        return el ? el.getAttribute('content') || '' : '';
    }

    function baseUrl(path) {
        var base = meta('base-url');
        if (base && base.charAt(base.length - 1) !== '/') { base += '/'; }
        return base + String(path).replace(/^\/+/, '');
    }

    /* ------------------------------------------------------------ CSRF */

    function readCookie(name) {
        if (!name) { return ''; }
        var m = document.cookie.match(new RegExp('(?:^|; )' + name.replace(/[.$?*|{}()\[\]\\\/+^]/g, '\\$&') + '=([^;]*)'));
        return m ? decodeURIComponent(m[1]) : '';
    }

    function csrfToken() {
        // The cookie holds the latest hash when CSRF regeneration is on.
        return readCookie(meta('csrf-cookie')) || meta('csrf-token');
    }

    function syncCsrf(response) {
        var header = meta('csrf-header');
        var fresh = (response && header && response.headers.get(header)) || readCookie(meta('csrf-cookie'));
        if (!fresh) { return; }
        var tag = document.querySelector('meta[name="csrf-token"]');
        if (tag) { tag.setAttribute('content', fresh); }
        var name = meta('csrf-name');
        document.querySelectorAll('input[name="' + name + '"]').forEach(function (input) { input.value = fresh; });
    }

    /* ----------------------------------------------------------- Toasts */

    function toast(message, type) {
        var region = document.getElementById('toast-region');
        if (!region || !message) { return; }
        var kind = type || 'info';
        var colors = { success: 'text-bg-success', error: 'text-bg-danger', info: 'text-bg-primary' };
        var el = document.createElement('div');
        el.className = 'toast align-items-center border-0 ' + kind + ' ' + (colors[kind] || colors.info);
        el.setAttribute('role', kind === 'error' ? 'alert' : 'status');
        el.setAttribute('aria-live', kind === 'error' ? 'assertive' : 'polite');
        el.setAttribute('aria-atomic', 'true');

        var wrap = document.createElement('div');
        wrap.className = 'd-flex';
        var body = document.createElement('div');
        body.className = 'toast-body';
        body.textContent = message;
        var close = document.createElement('button');
        close.type = 'button';
        close.className = 'btn-close btn-close-white me-2 m-auto';
        close.setAttribute('data-bs-dismiss', 'toast');
        close.setAttribute('aria-label', 'Close');
        wrap.appendChild(body);
        wrap.appendChild(close);
        el.appendChild(wrap);
        region.appendChild(el);

        el.addEventListener('hidden.bs.toast', function () { if (el.parentNode) { el.parentNode.removeChild(el); } });

        if (window.bootstrap && window.bootstrap.Toast) {
            window.bootstrap.Toast.getOrCreateInstance(el, { delay: 6000, autohide: true }).show();
        } else {
            // Fallback when Bootstrap JS is unavailable: show manually and dismiss.
            el.classList.add('show');
            close.addEventListener('click', function () { if (el.parentNode) { el.parentNode.removeChild(el); } });
            setTimeout(function () { if (el.parentNode) { el.parentNode.removeChild(el); } }, 6000);
        }
    }

    /* ---------------------------------------------------------- Request */

    function request(method, path, body) {
        var headers = { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' };
        var header = meta('csrf-header');
        if (header) { headers[header] = csrfToken(); }

        var options = { method: method, headers: headers, credentials: 'same-origin' };
        if (body) { options.body = body; } // FormData: browser sets multipart boundary

        return fetch(baseUrl(path), options).then(function (response) {
            syncCsrf(response);
            return response.text().then(function (text) {
                var json = null;
                try { json = text ? JSON.parse(text) : null; } catch (e) { json = null; }
                json = json || {};
                if (json.status === undefined || json.status === null) { json.status = response.status; }
                json.httpStatus = response.status;
                return json;
            });
        }).catch(function () {
            return { status: 0, httpStatus: 0, message: 'Network error. Please try again.', data: [] };
        });
    }

    /** Normalize validation errors from response.errors or response.data into {field: message}. */
    function extractErrors(res) {
        var out = {};
        [res.errors, res.data].forEach(function (src) {
            if (!src || typeof src !== 'object' || Array.isArray(src) && src.length && typeof src[0] !== 'object') { return; }
            Object.keys(src).forEach(function (key) {
                var val = src[key];
                if (Array.isArray(val)) { val = val.join(' '); }
                if (typeof val === 'string' && val !== '' && isNaN(Number(key))) {
                    if (!out[key]) { out[key] = val; }
                }
            });
        });
        return out;
    }

    function isError(res) {
        return !res.status || res.status < 200 || res.status > 299;
    }

    /* ----------------------------------------------- Language tabs (a11y) */

    function activateTab(group, code, focus) {
        group.querySelectorAll('[data-lang-tab]').forEach(function (tab) {
            var on = tab.getAttribute('data-lang-tab') === code;
            tab.setAttribute('aria-selected', on ? 'true' : 'false');
            tab.setAttribute('tabindex', on ? '0' : '-1');
            tab.classList.toggle('active', on);
            if (on && focus) { tab.focus(); }
        });
        group.querySelectorAll('[data-lang-panel]').forEach(function (panel) {
            var on = panel.getAttribute('data-lang-panel') === code;
            panel.hidden = !on;
            if (panel.classList.contains('tab-pane')) {
                panel.classList.toggle('active', on);
                panel.classList.toggle('show', on);
            }
        });
    }

    function initTabs() {
        document.querySelectorAll('[data-lang-field]').forEach(function (group) {
            var tabs = Array.prototype.slice.call(group.querySelectorAll('[data-lang-tab]'));
            tabs.forEach(function (tab, i) {
                tab.addEventListener('click', function () { activateTab(group, tab.getAttribute('data-lang-tab'), false); });
                tab.addEventListener('keydown', function (e) {
                    var next = null;
                    if (e.key === 'ArrowRight') { next = tabs[(i + 1) % tabs.length]; }
                    if (e.key === 'ArrowLeft') { next = tabs[(i - 1 + tabs.length) % tabs.length]; }
                    if (next) { e.preventDefault(); activateTab(group, next.getAttribute('data-lang-tab'), true); }
                });
            });
        });
    }

    /* ------------------------------------------------------ Field errors */

    function clearErrors(form) {
        form.querySelectorAll('[data-error-for]').forEach(function (p) { p.hidden = true; p.textContent = ''; p.classList.remove('d-block'); });
        form.querySelectorAll('[aria-invalid], .is-invalid').forEach(function (f) {
            f.removeAttribute('aria-invalid');
            f.classList.remove('invalid', 'is-invalid');
        });
        form.querySelectorAll('[data-lang-tab]').forEach(function (t) { t.classList.remove('invalid', 'text-danger'); });
        var summary = document.getElementById('form-summary');
        if (summary) { summary.hidden = true; summary.textContent = ''; summary.classList.remove('alert-danger'); }
    }

    function showErrors(form, errors, fallbackMessage) {
        var firstField = null;
        var unmatched = [];

        Object.keys(errors).forEach(function (field) {
            var slot = form.querySelector('[data-error-for="' + field + '"]');
            var input = form.querySelector('[name="' + field + '"]');
            if (!slot || !input) { unmatched.push(field + ': ' + errors[field]); return; }
            slot.textContent = errors[field];
            slot.hidden = false;
            slot.classList.add('invalid-feedback', 'd-block');
            input.setAttribute('aria-invalid', 'true');
            input.classList.add('invalid', 'is-invalid');

            // Reveal the language tab that holds the error.
            var panel = input.closest('[data-lang-panel]');
            var group = input.closest('[data-lang-field]');
            if (panel && group) {
                var tab = group.querySelector('[data-lang-tab="' + panel.getAttribute('data-lang-panel') + '"]');
                if (tab) { tab.classList.add('invalid', 'text-danger'); }
                if (!firstField) { activateTab(group, panel.getAttribute('data-lang-panel'), false); }
            }
            if (!firstField) { firstField = input; }
        });

        var summary = document.getElementById('form-summary');
        var text = unmatched.length ? unmatched.join('; ') : (firstField ? 'Please fix the highlighted fields.' : (fallbackMessage || ''));
        if (summary && text) {
            summary.textContent = text;
            summary.classList.add('alert-danger');
            summary.hidden = false;
        }
        if (firstField) { firstField.focus(); } else if (summary && text) { summary.focus(); }
    }

    /* ---------------------------------------------------------- Forms */

    function initForm(form) {
        var entity = form.getAttribute('data-entity');
        var id = form.getAttribute('data-id');
        var isSave = form.getAttribute('data-mode') === 'save';
        var redirect = form.getAttribute('data-redirect');

        form.addEventListener('submit', function (e) {
            e.preventDefault();
            clearErrors(form);

            var path = isSave
                ? 'admin/api/' + entity + '/save'
                : (id ? 'admin/api/' + entity + '/update/' + encodeURIComponent(id) : 'admin/api/' + entity + '/create');

            var button = form.querySelector('button[type="submit"]');
            if (button) { button.disabled = true; button.setAttribute('aria-busy', 'true'); }

            request('POST', path, new FormData(form)).then(function (res) {
                if (button) { button.disabled = false; button.removeAttribute('aria-busy'); }

                if (!isError(res)) {
                    toast(res.message || 'Saved', 'success');
                    if (redirect) {
                        setTimeout(function () { window.location.href = redirect; }, 800);
                    }
                    return;
                }

                var errors = extractErrors(res);
                if (res.status === 422 || Object.keys(errors).length) {
                    showErrors(form, errors, res.message || 'Validation failed');
                    toast(res.message || 'Please correct the errors in the form.', 'error');
                } else {
                    toast(res.message || 'Something went wrong. Please try again.', 'error');
                }
            });
        });
    }

    /* ----------------------------------------------------------- Lists */

    function cell(text) {
        var td = document.createElement('td');
        td.textContent = text === null || text === undefined ? '' : String(text);
        return td;
    }

    var PAGE_SIZES = [10, 25, 50, 100];

    /** Page numbers to show: all when few, otherwise first, last and the neighbours of the current page (0 = gap). */
    function pageNumbers(current, pages) {
        var out = [];
        var i;
        if (pages <= 7) {
            for (i = 1; i <= pages; i++) { out.push(i); }
            return out;
        }
        var keep = [1, pages, current - 1, current, current + 1]
            .filter(function (n, idx, all) { return n >= 1 && n <= pages && all.indexOf(n) === idx; })
            .sort(function (x, y) { return x - y; });
        keep.forEach(function (n, idx) {
            if (idx > 0 && n - keep[idx - 1] > 1) { out.push(0); }
            out.push(n);
        });
        return out;
    }

    function storedPageSize(entity) {
        try {
            var v = Number(window.localStorage.getItem('admin-page-size:' + entity));
            return PAGE_SIZES.indexOf(v) !== -1 ? v : PER_PAGE;
        } catch (e) {
            return PER_PAGE;
        }
    }

    function storePageSize(entity, size) {
        try { window.localStorage.setItem('admin-page-size:' + entity, String(size)); } catch (e) { /* private mode */ }
    }

    function initList(table) {
        var entity = table.getAttribute('data-entity');
        var page = table.getAttribute('data-page') || entity;
        var keys = Array.prototype.map.call(table.querySelectorAll('th[data-key]'), function (th) { return th.getAttribute('data-key'); });
        var tbody = table.querySelector('tbody');
        var statusEl = document.getElementById('list-status');
        var pager = document.querySelector('[data-admin-pager]');
        var pageList = pager ? pager.querySelector('[data-page-list]') : null;
        var sizeSelect = document.querySelector('[data-page-size]');
        var searchInput = document.querySelector('[data-list-search]');
        var search = '';
        var searchTimer = null;
        var requestId = 0;
        var current = 1;
        var perPage = storedPageSize(entity);
        var lastCount = 0;
        var focusPager = false;

        if (sizeSelect) {
            sizeSelect.value = String(perPage);
            sizeSelect.addEventListener('change', function () {
                perPage = Number(sizeSelect.value) || PER_PAGE;
                storePageSize(entity, perPage);
                current = 1;
                load();
            });
        }

        if (searchInput) {
            // Wait until typing pauses; Enter searches right away, Esc (or the clear button) resets.
            var runSearch = function () {
                window.clearTimeout(searchTimer);
                var term = searchInput.value.replace(/\s+/g, ' ').trim();
                if (term === search) { return; }
                search = term;
                current = 1;
                load();
            };
            searchInput.addEventListener('input', function () {
                window.clearTimeout(searchTimer);
                searchTimer = window.setTimeout(runSearch, 350);
            });
            searchInput.addEventListener('search', runSearch); // clear (x) button in type="search"
            searchInput.addEventListener('keydown', function (e) {
                if (e.key === 'Enter') { e.preventDefault(); runSearch(); }
                if (e.key === 'Escape' && searchInput.value !== '') { e.preventDefault(); searchInput.value = ''; runSearch(); }
            });
        }

        function setStatus(msg) { if (statusEl) { statusEl.textContent = msg; } }

        function pageButton(label, target, opts) {
            var li = document.createElement('li');
            li.className = 'page-item' + (opts.active ? ' active' : '') + (opts.disabled ? ' disabled' : '');
            var el;
            if (opts.gap) {
                el = document.createElement('span');
                el.className = 'page-link';
                el.setAttribute('aria-hidden', 'true');
                el.textContent = '\u2026';
            } else {
                el = document.createElement('button');
                el.type = 'button';
                el.className = 'page-link';
                el.textContent = label;
                if (opts.aria) { el.setAttribute('aria-label', opts.aria); }
                if (opts.active) { el.setAttribute('aria-current', 'page'); }
                if (opts.disabled) { el.disabled = true; }
                el.addEventListener('click', function () {
                    if (opts.disabled || opts.active) { return; }
                    current = target;
                    focusPager = true;
                    load();
                });
            }
            li.appendChild(el);
            return li;
        }

        function renderPager(total) {
            if (!pager || !pageList) { return; }
            var pages = Math.max(1, Math.ceil(total / perPage));
            pageList.innerHTML = '';
            pager.hidden = pages <= 1;
            if (pages <= 1) { return; }

            pageList.appendChild(pageButton('Previous', current - 1, { disabled: current <= 1, aria: 'Previous page' }));
            pageNumbers(current, pages).forEach(function (n) {
                pageList.appendChild(n === 0
                    ? pageButton('', 0, { gap: true })
                    : pageButton(String(n), n, { active: n === current, aria: 'Page ' + n }));
            });
            pageList.appendChild(pageButton('Next', current + 1, { disabled: current >= pages, aria: 'Next page' }));

            if (focusPager) {
                // The buttons were rebuilt: keep keyboard focus in the pager.
                var active = pageList.querySelector('[aria-current="page"]');
                if (active) { active.focus(); }
                focusPager = false;
            }
        }

        function render(res) {
            tbody.innerHTML = '';
            var rows = Array.isArray(res.data) ? res.data : [];
            var total = Number(res.total) || 0;
            if (total < rows.length) { total = rows.length; }
            perPage = Number(res.per_page) || perPage;
            lastCount = rows.length;

            var filtered = search !== '' ? ' (filtered by "' + search + '")' : '';
            if (!rows.length) {
                setStatus(search !== '' ? 'No matching entries found for "' + search + '".' : (total ? 'No entries on this page.' : 'No entries found.'));
            } else {
                var from = (current - 1) * perPage + 1;
                setStatus('Showing ' + from + ' to ' + (from + rows.length - 1) + ' of ' + total + ' entries' + filtered);
            }

            // Row number instead of the database id; continues across pages (newest item = 1).
            var offset = (current - 1) * perPage;

            rows.forEach(function (row, index) {
                var number = offset + index + 1;
                var tr = document.createElement('tr');
                tr.appendChild(cell(number));
                keys.forEach(function (k) { tr.appendChild(cell(row[k])); });

                var actions = document.createElement('td');
                var edit = document.createElement('a');
                edit.href = baseUrl('admin/' + page + '/edit/' + row.id);
                edit.textContent = 'Edit';
                edit.className = 'btn';
                actions.appendChild(edit);
                actions.appendChild(document.createTextNode(' '));

                // Rows may opt out of deletion (e.g. Master User: your own account).
                if (row.can_delete !== false) {
                    var del = document.createElement('button');
                    del.type = 'button';
                    del.className = 'danger';
                    del.textContent = 'Delete';
                    del.setAttribute('aria-label', 'Delete item ' + number);
                    del.addEventListener('click', function () { remove(row.id); });
                    actions.appendChild(del);
                }

                tr.appendChild(actions);
                tbody.appendChild(tr);
            });

            renderPager(total);
        }

        function load() {
            setStatus('Loading…');
            var mine = ++requestId;
            var query = 'page=' + current + '&per_page=' + perPage + (search !== '' ? '&search=' + encodeURIComponent(search) : '');
            request('GET', 'admin/api/' + entity + '/list?' + query).then(function (res) {
                // A newer request (e.g. the user kept typing) already started: ignore this answer.
                if (mine !== requestId) { return; }
                if (isError(res)) {
                    tbody.innerHTML = '';
                    setStatus(res.message || 'Failed to load items.');
                    toast(res.message || 'Failed to load items.', 'error');
                    return;
                }
                // Past the last page (e.g. after deleting the only row on it): go to the last page.
                var pages = Math.max(1, Math.ceil((Number(res.total) || 0) / (Number(res.per_page) || perPage)));
                if ((!Array.isArray(res.data) || !res.data.length) && current > pages) {
                    current = pages;
                    load();
                    return;
                }
                render(res);
            });
        }

        function remove(id) {
            if (!window.confirm('Delete this item? This cannot be undone.')) { return; }
            request('POST', 'admin/api/' + entity + '/delete/' + encodeURIComponent(id)).then(function (res) {
                if (isError(res)) {
                    toast(res.message || 'Delete failed.', 'error');
                    return;
                }
                toast(res.message || 'Deleted', 'success');
                if (lastCount <= 1 && current > 1) { current--; }
                load();
            });
        }

        load();
    }
    /* ------------------------------------------------------------ Boot */

    function boot() {
        initTabs();
        document.querySelectorAll('[data-admin-form]').forEach(initForm);
        document.querySelectorAll('[data-admin-list]').forEach(initList);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', boot);
    } else {
        boot();
    }
})();
