/**
 * WP Travel Machine — Archive Filter + Pagination Controller
 *
 * Drives live AJAX filtering for trip/hotel archives plus the admin-chosen
 * pagination mode: numbered AJAX pagination or an AJAX "Load More" button.
 * Progressive enhancement — without JS the server still renders the grid and
 * classic numbered pagination.
 */
(function () {
    'use strict';
    var WPTM = window.wptmData || {};

    function initArchive() {
        var grid = document.querySelector('.wptm-search-results');
        if (!grid) return;

        var wrap = document.querySelector('.wptm-pagination-wrap');

        // Filter source: the new filter bar, or a [wptm_search_form] on the page.
        var form = document.querySelector('.wptm-filter-bar')
            || document.querySelector('form.wptm-search-fields')
            || document.querySelector('.wptm-search-form form');
        var isFilterBar = !!(form && form.classList && form.classList.contains('wptm-filter-bar'));
        var countEl = isFilterBar ? form.querySelector('.wptm-filter-count') : null;

        // Type priority: pagination wrap → filter bar → hotel body class → trip.
        var type = (wrap && wrap.getAttribute('data-type')) ? wrap.getAttribute('data-type')
            : (isFilterBar && form.getAttribute('data-filter-type') === 'hotel') ? 'hotel'
                : (document.body.className.indexOf('-wptm_hotel') > -1 ? 'hotel' : 'trip');
        var action = type === 'hotel' ? 'wptm_filter_hotels' : 'wptm_filter_trips';
        var paginationType = WPTM.paginationType || 'pagination';

        var state = {
            page: 1,
            max: wrap ? (parseInt(wrap.getAttribute('data-max'), 10) || 1) : 1,
            total: wrap ? (parseInt(wrap.getAttribute('data-total'), 10) || 0) : 0
        };
        var timer;

        function filters() {
            var data = { nonce: WPTM.searchNonce || WPTM.nonce };
            if (form) {
                new FormData(form).forEach(function (value, name) {
                    // Flatten the search form's wptm_search[key] names to bare keys.
                    var m = name.match(/^wptm_search\[([^\]]+)\]$/);
                    var key = m ? m[1] : name;
                    if (key === 'post_type' || key === 'nonce') return;
                    if (value !== '' && value != null) data[key] = value;
                });
            }
            return data;
        }

        function setCount() {
            if (!countEl) return;
            countEl.textContent = state.total + (state.total === 1 ? ' result' : ' results');
        }

        function request(page, append) {
            grid.classList.add('is-loading');
            var data = filters();
            data.page = page;
            wptmAjax(action, data, function (r) {
                grid.classList.remove('is-loading');
                var ok = r && r.success;
                var html = ok && typeof r.data.html === 'string' ? r.data.html : '';
                if (append) {
                    if (html) grid.insertAdjacentHTML('beforeend', html);
                } else {
                    grid.innerHTML = html || ('<p class="wptm-no-results">' + (WPTM.i18n && WPTM.i18n.noResults ? WPTM.i18n.noResults : 'No results found.') + '</p>');
                }
                state.page = page;
                state.max = ok ? (parseInt(r.data.pages, 10) || 1) : 1;
                state.total = ok ? (parseInt(r.data.total, 10) || 0) : 0;
                setCount();
                renderPager();
            });
        }

        function pageBtn(p, label, disabled, cls) {
            if (disabled) return '<span class="wptm-pager__btn is-disabled">' + label + '</span>';
            return '<button type="button" class="wptm-pager__btn ' + (cls || '') + '" data-page="' + p + '">' + label + '</button>';
        }

        function renderPager() {
            if (!wrap) return;
            if (state.max <= 1) { wrap.innerHTML = ''; return; }

            if (paginationType === 'load_more') {
                if (state.page >= state.max) { wrap.innerHTML = ''; return; }
                wrap.innerHTML = '<button type="button" class="wptm-btn wptm-btn--outline wptm-load-more">'
                    + (WPTM.i18n && WPTM.i18n.loadMore ? WPTM.i18n.loadMore : 'Load More') + '</button>';
                wrap.querySelector('.wptm-load-more').addEventListener('click', function () {
                    request(state.page + 1, true);
                });
                return;
            }

            // Numbered pager.
            var html = '<nav class="wptm-pager" aria-label="Pagination">';
            html += pageBtn(state.page - 1, '‹', state.page <= 1, 'wptm-pager__nav');
            for (var i = 1; i <= state.max; i++) {
                if (i === 1 || i === state.max || Math.abs(i - state.page) <= 1) {
                    html += pageBtn(i, i, false, i === state.page ? 'is-current' : '');
                } else if (Math.abs(i - state.page) === 2) {
                    html += '<span class="wptm-pager__dots">…</span>';
                }
            }
            html += pageBtn(state.page + 1, '›', state.page >= state.max, 'wptm-pager__nav');
            html += '</nav>';
            wrap.innerHTML = html;
            wrap.querySelectorAll('[data-page]').forEach(function (b) {
                b.addEventListener('click', function () {
                    var p = parseInt(this.getAttribute('data-page'), 10);
                    if (p && p !== state.page) {
                        request(p, false);
                        var top = grid.getBoundingClientRect().top + window.pageYOffset - 100;
                        window.scrollTo({ top: top, behavior: 'smooth' });
                    }
                });
            });
        }

        function onFilterChange() { request(1, false); }

        if (form) {
            form.querySelectorAll('select, input[type="date"], input[type="number"]').forEach(function (el) {
                el.addEventListener('change', onFilterChange);
            });
            var kw = form.querySelector('input[type="search"], input[name="keyword"], input[name="wptm_search[keyword]"]');
            if (kw) {
                kw.addEventListener('input', function () {
                    clearTimeout(timer);
                    timer = setTimeout(onFilterChange, 450);
                });
            }
            form.addEventListener('submit', function (e) { e.preventDefault(); onFilterChange(); });
            form.addEventListener('reset', function () { setTimeout(onFilterChange, 0); });
        }

        // Enhance the server-rendered pager into the JS-driven one on load
        // (so Load More / AJAX paging work from the very first view).
        setCount();
        renderPager();
    }

    document.addEventListener('DOMContentLoaded', initArchive);
})();
