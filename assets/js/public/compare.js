/**
 * WP Travel Machine — Compare Feature
 */
(function() {
    'use strict';
    const WPTM = window.wptmData || {};
    let compareItems = JSON.parse(localStorage.getItem('wptm_compare') || '[]');

    function initCompare() {
        if (!WPTM.enableCompare) return;
        updateCompareBar();

        document.addEventListener('click', function(e) {
            const btn = e.target.closest('.wptm-compare-btn');
            if (!btn) return;
            e.preventDefault();
            const id = btn.dataset.itemId;
            const title = btn.dataset.itemTitle || '';
            const idx = compareItems.findIndex(i => i.id === id);
            if (idx > -1) {
                compareItems.splice(idx, 1);
                btn.classList.remove('active');
            } else {
                if (compareItems.length >= 4) { wptmToast('Maximum 4 items to compare.', 'error'); return; }
                compareItems.push({ id: id, title: title });
                btn.classList.add('active');
            }
            localStorage.setItem('wptm_compare', JSON.stringify(compareItems));
            updateCompareBar();
        });

        document.addEventListener('click', function(e) {
            if (e.target.closest('.wptm-compare-bar__item .remove')) {
                const item = e.target.closest('.wptm-compare-bar__item');
                const id = item.dataset.itemId;
                compareItems = compareItems.filter(i => i.id !== id);
                localStorage.setItem('wptm_compare', JSON.stringify(compareItems));
                const btn = document.querySelector('.wptm-compare-btn[data-item-id="' + id + '"]');
                if (btn) btn.classList.remove('active');
                updateCompareBar();
            }
            if (e.target.closest('.wptm-compare-bar__clear')) {
                compareItems = [];
                localStorage.setItem('wptm_compare', '[]');
                document.querySelectorAll('.wptm-compare-btn.active').forEach(b => b.classList.remove('active'));
                updateCompareBar();
            }
        });
    }

    function updateCompareBar() {
        let bar = document.querySelector('.wptm-compare-bar');
        if (!bar) {
            bar = document.createElement('div');
            bar.className = 'wptm-compare-bar';
            document.body.appendChild(bar);
        }
        if (!compareItems.length) { bar.classList.remove('visible'); return; }
        bar.classList.add('visible');
        let html = '<div class="wptm-compare-bar__items">';
        compareItems.forEach(item => {
            html += '<div class="wptm-compare-bar__item" data-item-id="' + item.id + '">' + item.title + ' <span class="remove">&times;</span></div>';
        });
        html += '</div>';
        html += '<button class="wptm-btn wptm-btn--primary wptm-btn--sm wptm-compare-bar__go">Compare (' + compareItems.length + ')</button>';
        html += '<button class="wptm-btn wptm-btn--sm wptm-compare-bar__clear">Clear</button>';
        bar.innerHTML = html;

        // Mark buttons as active
        compareItems.forEach(item => {
            const btn = document.querySelector('.wptm-compare-btn[data-item-id="' + item.id + '"]');
            if (btn) btn.classList.add('active');
        });
    }

    document.addEventListener('DOMContentLoaded', initCompare);
})();
