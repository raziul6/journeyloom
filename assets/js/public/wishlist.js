/**
 * WP Travel Machine — Wishlist JS
 */
(function() {
    'use strict';
    const WPTM = window.wptmData || {};

    function initWishlist() {
        if (!WPTM.enableWishlist) return;

        // Load user's wishlist
        if (document.body.dataset.userId) {
            wptmAjax('wptm_get_wishlist', {}, function(r) {
                if (r.success && r.data.items) {
                    r.data.items.forEach(function(id) {
                        const btn = document.querySelector('.wptm-wishlist-btn[data-item-id="' + id + '"]');
                        if (btn) btn.classList.add('active');
                    });
                }
            });
        }

        // Toggle wishlist
        document.addEventListener('click', function(e) {
            const btn = e.target.closest('.wptm-wishlist-btn, .wptm-trip-card__wishlist');
            if (!btn) return;
            e.preventDefault();

            const itemId = btn.dataset.itemId;
            const itemType = btn.dataset.itemType || 'trip';

            btn.style.pointerEvents = 'none';
            wptmAjax('wptm_toggle_wishlist', { item_id: itemId, item_type: itemType }, function(r) {
                btn.style.pointerEvents = '';
                if (r.success) {
                    const isAdded = r.data.action === 'added';
                    btn.classList.toggle('active', isAdded);
                    const label = btn.querySelector('.wptm-wishlist-btn__label');
                    if (label) {
                        label.textContent = isAdded
                            ? (label.dataset.labelSaved || 'Saved')
                            : (label.dataset.labelSave || 'Save');
                    }
                    wptmToast(r.data.message);
                } else {
                    wptmToast(r.data && r.data.message ? r.data.message : 'Please log in.', 'error');
                }
            });
        });
    }

    document.addEventListener('DOMContentLoaded', initWishlist);
})();
