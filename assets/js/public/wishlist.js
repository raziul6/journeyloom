/**
 * WP Travel Machine — Wishlist JS
 *
 * Works for logged-in users (saved server-side) and guests (saved in
 * localStorage), so the heart always responds. The active state is hydrated
 * from the right source on load.
 */
(function () {
    'use strict';

    var WPTM = window.wptmData || {};
    var STORE = 'wptm_wishlist';

    /* ── Local (guest) store ── */
    function localIds() {
        try { return JSON.parse(localStorage.getItem(STORE) || '[]') || []; }
        catch (e) { return []; }
    }
    function saveLocal(ids) {
        try { localStorage.setItem(STORE, JSON.stringify(ids)); } catch (e) {}
    }

    /* ── Shared UI ── */
    function setActive(id, on) {
        var sel = '.wptm-wishlist-btn[data-item-id="' + id + '"]';
        Array.prototype.forEach.call(document.querySelectorAll(sel), function (btn) {
            btn.classList.toggle('active', on);
            var label = btn.querySelector('.wptm-wishlist-btn__label');
            if (label) {
                label.textContent = on
                    ? (label.dataset.labelSaved || 'Saved')
                    : (label.dataset.labelSave || 'Save');
            }
        });
    }
    function toast(msg, type) {
        if (typeof window.wptmToast === 'function') window.wptmToast(msg, type);
    }

    /* ── Hydrate the saved state on page load ── */
    function loadActive() {
        if (WPTM.userId) {
            if (typeof window.wptmAjax !== 'function') return;
            window.wptmAjax('wptm_get_wishlist', {}, function (r) {
                if (r && r.success && r.data && r.data.items) {
                    r.data.items.forEach(function (id) { setActive(id, true); });
                }
            });
        } else {
            localIds().forEach(function (id) { setActive(id, true); });
        }
    }

    /* ── Toggle (guest = localStorage) ── */
    function toggleGuest(id) {
        var ids = localIds();
        var i = ids.indexOf(String(id));
        var added = i === -1;
        if (added) { ids.push(String(id)); } else { ids.splice(i, 1); }
        saveLocal(ids);
        setActive(id, added);
        toast(added ? 'Added to wishlist!' : 'Removed from wishlist.');
    }

    /* On the wishlist page, drop the card once it's removed (and show the
       empty state when nothing is left). */
    function removeFromWishlistPage(btn) {
        var grid = btn.closest('.wptm-wishlist-grid');
        if (!grid) return;
        var card = btn.closest('.wptm-trip-card, .wptm-hotel-card');
        if (!card) return;
        card.style.transition = 'opacity .25s ease, transform .25s ease';
        card.style.opacity = '0';
        card.style.transform = 'scale(.96)';
        setTimeout(function () {
            card.remove();
            if (!grid.querySelector('.wptm-trip-card, .wptm-hotel-card')) {
                var page = grid.closest('.wptm-wishlist-page');
                grid.remove();
                if (page) {
                    var empty = page.querySelector('.wptm-wishlist-empty');
                    if (empty) empty.style.display = '';
                }
            }
        }, 260);
    }

    /* ── Toggle (logged-in = server) ── */
    function toggleServer(btn, id, type) {
        if (typeof window.wptmAjax !== 'function') return;
        btn.style.pointerEvents = 'none';
        window.wptmAjax('wptm_toggle_wishlist', { item_id: id, item_type: type }, function (r) {
            btn.style.pointerEvents = '';
            if (r && r.success && r.data) {
                setActive(id, r.data.action === 'added');
                toast(r.data.message);
                if (r.data.action === 'removed') { removeFromWishlistPage(btn); }
            } else {
                // A non-JSON / -1 reply means an expired nonce on a cached page.
                var msg = (r && r.data && r.data.message)
                    ? r.data.message
                    : 'Could not update your wishlist. Please refresh the page and try again.';
                toast(msg, 'error');
            }
        });
    }

    function init() {
        // Explicitly disabled in settings → remove the hearts so nothing looks broken.
        if (WPTM.enableWishlist === false) {
            Array.prototype.forEach.call(document.querySelectorAll('.wptm-wishlist-btn'), function (b) { b.remove(); });
            return;
        }

        loadActive();

        document.addEventListener('click', function (e) {
            var btn = e.target.closest('.wptm-wishlist-btn, .wptm-trip-card__wishlist, .wptm-hotel-card__wishlist');
            if (!btn) return;
            e.preventDefault();

            var id = btn.dataset.itemId;
            if (!id) return;
            var type = btn.dataset.itemType || 'trip';

            if (WPTM.userId) { toggleServer(btn, id, type); }
            else { toggleGuest(id); }
        });
    }

    document.addEventListener('DOMContentLoaded', init);
})();
