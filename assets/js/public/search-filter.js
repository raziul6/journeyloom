/**
 * WP Travel Machine — Search & Filter JS
 *
 * Progressive enhancement for the advanced search form: live-filters the
 * results grid via AJAX. Falls back to a normal GET submit if anything is
 * missing. Form fields are namespaced as wptm_search[...]; they're flattened to
 * the bare names the `wptm_filter_trips` AJAX handler expects.
 */
(function() {
    'use strict';
    const WPTM = window.wptmData || {};

    function initSearchFilter() {
        // When the page has a paginated grid, the unified filter-bar.js controller
        // owns filtering + pagination — don't double-bind the search form here.
        if (document.querySelector('.wptm-pagination-wrap')) return;

        // The actual <form> is .wptm-search-fields (.wptm-search-form is its wrapper).
        const form = document.querySelector('form.wptm-search-fields')
            || document.querySelector('.wptm-search-form form');
        if (!form || typeof form.elements === 'undefined') return;

        const resultsContainer = document.querySelector('.wptm-search-results')
            || document.querySelector('.wptm-grid');
        if (!resultsContainer) return;

        form.addEventListener('submit', function(e) {
            e.preventDefault();
            doSearch();
        });

        // Live filter when any select/date/number changes.
        form.querySelectorAll('select, input[type="date"], input[type="number"]').forEach(el => {
            el.addEventListener('change', doSearch);
        });

        // Debounced keyword typing.
        let timer;
        const textInput = form.querySelector('input[name="wptm_search[keyword]"]');
        if (textInput) {
            textInput.addEventListener('input', function() {
                clearTimeout(timer);
                timer = setTimeout(doSearch, 500);
            });
        }

        /**
         * Collect form values, flattening wptm_search[key] -> key.
         */
        function collect() {
            const data = { nonce: WPTM.searchNonce || WPTM.nonce };
            new FormData(form).forEach(function(value, name) {
                const m = name.match(/^wptm_search\[([^\]]+)\]$/);
                const key = m ? m[1] : name;
                if (key === 'post_type') return;
                if (value !== '' && value != null) data[key] = value;
            });
            return data;
        }

        function doSearch() {
            resultsContainer.style.opacity = '0.5';
            wptmAjax('wptm_filter_trips', collect(), function(r) {
                resultsContainer.style.opacity = '1';
                if (r && r.success && typeof r.data.html === 'string' && r.data.html.trim() !== '') {
                    resultsContainer.innerHTML = r.data.html;
                } else {
                    resultsContainer.innerHTML = '<p class="wptm-no-results" style="text-align:center;padding:40px 0;color:#94a3b8;">'
                        + (WPTM.i18n ? WPTM.i18n.noResults : 'No results found.') + '</p>';
                }
            });
        }
    }

    document.addEventListener('DOMContentLoaded', initSearchFilter);
})();
