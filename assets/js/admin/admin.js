/**
 * WP Travel Machine — Admin JS
 */
(function() {
    'use strict';
    const WPTM = window.wptmAdmin || {};

    /* Color pickers (Settings → Appearance) — sync swatch ↔ hex + reset */
    function initColorPickers() {
        const HEX = /^#([0-9a-f]{3}|[0-9a-f]{6})$/i;
        document.querySelectorAll('.wptm-color-field').forEach(function(field) {
            const swatch = field.querySelector('.wptm-color-field__swatch');
            const hex = field.querySelector('.wptm-color-field__hex');
            const reset = field.querySelector('.wptm-color-field__reset');
            const def = field.dataset.default || '#000000';
            if (swatch && hex) {
                swatch.addEventListener('input', function() { hex.value = swatch.value; });
                hex.addEventListener('input', function() {
                    const v = hex.value.trim();
                    if (HEX.test(v)) swatch.value = v;
                });
            }
            if (reset) reset.addEventListener('click', function() {
                hex.value = '';
                if (swatch) swatch.value = def;
            });
        });
    }

    /* Send test email (Settings → Emails) */
    function initTestEmail() {
        const btn = document.getElementById('wptm-send-test-email');
        if (!btn) return;
        const input = document.getElementById('wptm-test-email');
        const result = document.getElementById('wptm-test-email-result');
        btn.addEventListener('click', function() {
            const orig = btn.textContent;
            btn.disabled = true;
            btn.textContent = 'Sending…';
            if (result) { result.textContent = ''; result.style.color = ''; }
            const fd = new FormData();
            fd.append('action', 'wptm_send_test_email');
            fd.append('nonce', WPTM.nonce || '');
            fd.append('email', input ? input.value : '');
            fetch(WPTM.ajaxUrl, { method: 'POST', body: fd, credentials: 'same-origin' })
                .then(r => r.json())
                .then(r => {
                    if (result) {
                        result.textContent = (r.data && r.data.message) ? r.data.message : (r.success ? 'Sent.' : 'Failed.');
                        result.style.color = r.success ? '#10b981' : '#ef4444';
                    }
                })
                .catch(() => { if (result) { result.textContent = 'Request failed.'; result.style.color = '#ef4444'; } })
                .finally(() => { btn.disabled = false; btn.textContent = orig; });
        });
    }

    /* Settings Tabs — Fixed: selectors now match actual HTML classes */
    function initSettingsTabs() {
        const tabs = document.querySelectorAll('.wptm-tab');
        const panels = document.querySelectorAll('.wptm-tab-content');
        tabs.forEach(function(tab) {
            tab.addEventListener('click', function() {
                tabs.forEach(t => t.classList.remove('active'));
                panels.forEach(p => p.classList.remove('active'));
                this.classList.add('active');
                const target = this.getAttribute('data-tab');
                const panel = document.querySelector('.wptm-tab-content[data-tab="' + target + '"]');
                if (panel) panel.classList.add('active');
            });
        });
    }

    /* Settings Sidebar Navigation — panel switching, group collapse, search */
    function initSettingsNav() {
        var nav = document.querySelector('.wptm-settings__nav');
        if (!nav) return;

        var items = nav.querySelectorAll('.wptm-nav-item');
        var panels = document.querySelectorAll('.wptm-settings-panel');

        function showPanel(key) {
            items.forEach(function(i) { i.classList.toggle('is-active', i.getAttribute('data-panel') === key); });
            panels.forEach(function(p) { p.classList.toggle('is-active', p.getAttribute('data-panel') === key); });
        }

        items.forEach(function(item) {
            item.addEventListener('click', function() {
                showPanel(this.getAttribute('data-panel'));
            });
        });

        // Collapse / expand groups.
        nav.querySelectorAll('.wptm-nav-group__head').forEach(function(head) {
            head.addEventListener('click', function() {
                this.closest('.wptm-nav-group').classList.toggle('is-open');
            });
        });

        // Live search filter over nav items.
        var search = document.getElementById('wptm-settings-search');
        if (search) {
            search.addEventListener('input', function() {
                var q = this.value.trim().toLowerCase();
                nav.querySelectorAll('.wptm-nav-group').forEach(function(group) {
                    var anyVisible = false;
                    group.querySelectorAll('.wptm-nav-item').forEach(function(item) {
                        var match = !q || item.textContent.toLowerCase().indexOf(q) !== -1;
                        item.style.display = match ? '' : 'none';
                        if (match) anyVisible = true;
                    });
                    group.style.display = anyVisible ? '' : 'none';
                    if (q && anyVisible) group.classList.add('is-open');
                });
            });
        }
    }

    /* Currency select → auto-fill the symbol field */
    function initCurrencySelect() {
        var select = document.getElementById('wptm-currency-select');
        var symbol = document.getElementById('wptm-currency-symbol');
        if (!select || !symbol) return;
        select.addEventListener('change', function() {
            var opt = this.options[this.selectedIndex];
            var sym = opt ? opt.getAttribute('data-symbol') : '';
            if (sym) symbol.value = sym;
        });
    }

    /* Settings Save (AJAX) */
    function initSettingsSave() {
        const form = document.getElementById('wptm-settings-form');
        if (!form) return;
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            const btn = form.querySelector('#wptm-save-settings') || form.querySelector('[type="submit"]');
            if (!btn) return;
            const orig = btn.textContent;
            btn.disabled = true; btn.textContent = 'Saving...';

            const fd = new FormData(form);
            fd.append('action', 'wptm_save_settings');
            fd.append('nonce', WPTM.nonce);

            // Handle unchecked checkboxes — send '0' for unchecked
            form.querySelectorAll('input[type="checkbox"]').forEach(function(cb) {
                const name = cb.getAttribute('name');
                if (name && !cb.checked) {
                    fd.set(name, '');
                }
            });

            fetch(WPTM.ajaxUrl, { method: 'POST', body: fd, credentials: 'same-origin' })
                .then(r => r.json())
                .then(r => {
                    btn.disabled = false; btn.textContent = orig;
                    showNotice(r.success ? 'Settings saved successfully!' : 'Error saving settings.', r.success ? 'success' : 'error');
                })
                .catch(() => {
                    btn.disabled = false; btn.textContent = orig;
                    showNotice('Network error. Please try again.', 'error');
                });
        });
    }

    /* Search Form Builder Save */
    function initSearchFormBuilder() {
        const form = document.getElementById('wptm-search-form-builder');
        if (!form) return;
        const grid = form.querySelector('#wptm-sortable-fields');

        /* Live feedback: toggle off-state + reflect label edits in the card title */
        form.addEventListener('change', function(e) {
            if (e.target.classList.contains('wptm-sfb-enabled')) {
                const card = e.target.closest('.wptm-sfb-card');
                if (card) card.classList.toggle('is-off', !e.target.checked);
                updateActiveCount();
            }
        });
        form.addEventListener('input', function(e) {
            if (e.target.classList.contains('wptm-sfb-label')) {
                const card = e.target.closest('.wptm-sfb-card');
                const title = card && card.querySelector('.wptm-sfb-title');
                if (title) title.textContent = e.target.value || card.dataset.field;
            }
        });

        function updateActiveCount() {
            const badge = document.querySelector('.wptm-admin-header .wptm-version');
            if (!badge || !grid) return;
            const n = grid.querySelectorAll('.wptm-sfb-enabled:checked').length;
            badge.textContent = n + ' ' + (badge.textContent.replace(/^\d+\s/, ''));
        }

        /* ── Drag to reorder (native HTML5 DnD, handle-initiated) ── */
        if (grid) {
            let dragging = null;

            grid.addEventListener('mousedown', function(e) {
                const handle = e.target.closest('.wptm-sfb-drag');
                const card = handle && handle.closest('.wptm-sfb-card');
                if (card) card.setAttribute('draggable', 'true');
            });
            grid.addEventListener('mouseup', clearDraggable);
            grid.addEventListener('dragend', function() {
                if (dragging) dragging.classList.remove('is-dragging');
                grid.querySelectorAll('.is-drop-target').forEach(c => c.classList.remove('is-drop-target'));
                dragging = null;
                clearDraggable();
            });
            function clearDraggable() {
                grid.querySelectorAll('.wptm-sfb-card[draggable="true"]').forEach(c => c.setAttribute('draggable', 'false'));
            }

            grid.addEventListener('dragstart', function(e) {
                const card = e.target.closest('.wptm-sfb-card');
                if (!card) return;
                dragging = card;
                card.classList.add('is-dragging');
                e.dataTransfer.effectAllowed = 'move';
                try { e.dataTransfer.setData('text/plain', card.dataset.field); } catch (err) {}
            });
            grid.addEventListener('dragover', function(e) {
                if (!dragging) return;
                e.preventDefault();
                const after = getDragAfter(grid, e.clientX, e.clientY);
                grid.querySelectorAll('.is-drop-target').forEach(c => c.classList.remove('is-drop-target'));
                if (after == null) {
                    grid.appendChild(dragging);
                } else {
                    grid.insertBefore(dragging, after);
                    after.classList.add('is-drop-target');
                }
            });

            function getDragAfter(container, x, y) {
                const cards = [...container.querySelectorAll('.wptm-sfb-card:not(.is-dragging)')];
                let closest = { offset: Number.NEGATIVE_INFINITY, el: null };
                cards.forEach(card => {
                    const box = card.getBoundingClientRect();
                    // Use vertical midpoint; grid wraps, so also weigh rows.
                    const offset = y - box.top - box.height / 2;
                    if (offset < 0 && offset > closest.offset) {
                        closest = { offset: offset, el: card };
                    }
                });
                return closest.el;
            }
        }

        /* ── Save ── */
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            const btn = form.querySelector('[type="submit"]');
            if (!btn) return;
            const orig = btn.textContent;
            btn.disabled = true; btn.textContent = 'Saving…';

            // Renumber order inputs to match current DOM order.
            grid.querySelectorAll('.wptm-sfb-card').forEach(function(card, i) {
                const order = card.querySelector('.wptm-sfb-order');
                if (order) order.value = i + 1;
            });

            const fd = new FormData(form);
            fd.append('action', 'wptm_save_search_form');

            fetch(WPTM.ajaxUrl, { method: 'POST', body: fd, credentials: 'same-origin' })
                .then(r => r.json())
                .then(r => {
                    btn.disabled = false; btn.textContent = orig;
                    showNotice(r.success ? 'Search form saved!' : 'Error saving search form.', r.success ? 'success' : 'error');
                })
                .catch(() => {
                    btn.disabled = false; btn.textContent = orig;
                    showNotice('Network error. Please try again.', 'error');
                });
        });
    }

    /* Metabox Tabs — scoped per container so multiple tab groups can coexist */
    function initMetaboxTabs() {
        document.querySelectorAll('[data-wptm-mbtabs]').forEach(function(wrap) {
            var tabs = wrap.querySelectorAll('.wptm-mb__tab');
            var panels = wrap.querySelectorAll('.wptm-mb__panel');
            tabs.forEach(function(tab) {
                tab.addEventListener('click', function() {
                    var key = this.getAttribute('data-tab');
                    tabs.forEach(function(t) {
                        var on = t === tab;
                        t.classList.toggle('is-active', on);
                        t.setAttribute('aria-selected', on ? 'true' : 'false');
                    });
                    panels.forEach(function(p) {
                        p.classList.toggle('is-active', p.getAttribute('data-panel') === key);
                    });
                });
            });
        });
    }

    /* ─── Repeater Templates ─── */
    function wptmListRow(name, placeholder) {
        return '<div class="wptm-repeater-item"><div class="wptm-list-row">' +
            '<input type="text" name="' + name + '[]" value="" class="widefat" placeholder="' + (placeholder || '') + '">' +
            '<button type="button" class="wptm-remove-item button-link" aria-label="Remove"><span class="dashicons dashicons-trash"></span></button>' +
            '</div></div>';
    }
    var repeaterTemplates = {
        enquiry: function(idx) {
            var n = 'settings[wptm_enquiry_fields][' + idx + ']';
            return '<div class="wptm-repeater-item"><div class="wptm-enquiry-field-row">' +
                '<input type="text" name="' + n + '[label]" value="" placeholder="Field label" class="widefat">' +
                '<select name="' + n + '[type]">' +
                '<option value="text">Text</option><option value="email">Email</option><option value="tel">Phone</option><option value="number">Number</option><option value="textarea">Textarea</option><option value="select">Dropdown</option><option value="country">Country list</option>' +
                '</select>' +
                '<input type="text" name="' + n + '[options]" value="" placeholder="A, B, C" class="widefat">' +
                '<label class="wptm-enquiry-req"><input type="checkbox" name="' + n + '[required]" value="1"></label>' +
                '<button type="button" class="wptm-remove-item button-link" aria-label="Remove field"><span class="dashicons dashicons-trash"></span></button>' +
                '</div></div>';
        },
        highlights: function(idx) { return wptmListRow('wptm_highlights', 'Highlight'); },
        includes:   function(idx) { return wptmListRow('wptm_includes', 'Included item'); },
        excludes:   function(idx) { return wptmListRow('wptm_excludes', 'Excluded item'); },
        faq: function(idx) {
            return '<div class="wptm-repeater-item" data-index="' + idx + '">' +
                '<div class="wptm-repeater-header"><span class="dashicons dashicons-menu wptm-drag"></span><span class="wptm-repeater-badge">' + (idx + 1) + '</span><strong>FAQ ' + (idx + 1) + '</strong>' +
                '<button type="button" class="wptm-remove-item button-link"><span class="dashicons dashicons-trash"></span></button></div>' +
                '<div class="wptm-repeater-body">' +
                '<input type="text" name="wptm_faq[' + idx + '][question]" value="" placeholder="Question" class="widefat wptm-mb-spacer">' +
                '<textarea name="wptm_faq[' + idx + '][answer]" rows="2" placeholder="Answer" class="widefat"></textarea>' +
                '</div></div>';
        },
        facilities: function(idx) {
            return '<div class="wptm-repeater-item" data-index="' + idx + '">' +
                '<div class="wptm-repeater-header"><span class="dashicons dashicons-menu wptm-drag"></span><span class="wptm-repeater-badge">' + (idx + 1) + '</span><strong>Group ' + (idx + 1) + '</strong>' +
                '<button type="button" class="wptm-remove-item button-link"><span class="dashicons dashicons-trash"></span></button></div>' +
                '<div class="wptm-repeater-body">' +
                '<input type="text" name="wptm_facilities[' + idx + '][title]" value="" placeholder="Group title (e.g. General)" class="widefat wptm-mb-spacer">' +
                '<textarea name="wptm_facilities[' + idx + '][items]" rows="4" placeholder="One facility per line…" class="widefat"></textarea>' +
                '</div></div>';
        },
        availability: function(idx) {
            return '<div class="wptm-repeater-item"><div class="wptm-availability-row">' +
                '<input type="date" name="wptm_availability[' + idx + '][date_start]" value="">' +
                '<input type="date" name="wptm_availability[' + idx + '][date_end]" value="">' +
                '<input type="number" min="0" name="wptm_availability[' + idx + '][spots]" value="1" placeholder="0">' +
                '<select name="wptm_availability[' + idx + '][status]"><option value="available">Available</option><option value="unavailable">Blocked</option></select>' +
                '<input type="number" min="0" step="0.01" name="wptm_availability[' + idx + '][price]" value="" placeholder="Default">' +
                '<button type="button" class="wptm-remove-item button-link"><span class="dashicons dashicons-trash"></span></button>' +
                '</div></div>';
        },
        pricing: function(idx) {
            return '<div class="wptm-repeater-item"><div class="wptm-pricing-row">' +
                '<input type="text" name="wptm_pricing[' + idx + '][label]" value="" placeholder="Label">' +
                '<input type="number" name="wptm_pricing[' + idx + '][price]" value="" placeholder="0.00" step="0.01" min="0">' +
                '<input type="number" name="wptm_pricing[' + idx + '][sale_price]" value="" placeholder="0.00" step="0.01" min="0">' +
                '<button type="button" class="wptm-remove-item button-link"><span class="dashicons dashicons-trash"></span></button>' +
                '</div></div>';
        },
        itinerary: function(idx) {
            return '<div class="wptm-repeater-item" data-index="' + idx + '">' +
                '<div class="wptm-repeater-header"><span class="dashicons dashicons-menu wptm-drag"></span><span class="wptm-repeater-badge">' + (idx + 1) + '</span><strong>Day ' + (idx + 1) + '</strong>' +
                '<button type="button" class="wptm-remove-item button-link"><span class="dashicons dashicons-trash"></span></button></div>' +
                '<div class="wptm-repeater-body">' +
                '<input type="text" name="wptm_itinerary[' + idx + '][title]" value="" placeholder="Day Title" class="widefat wptm-mb-spacer">' +
                '<textarea name="wptm_itinerary[' + idx + '][description]" rows="2" placeholder="Description" class="widefat wptm-mb-spacer"></textarea>' +
                '<div class="wptm-inline">' +
                '<input type="text" name="wptm_itinerary[' + idx + '][meals]" value="" placeholder="Meals" class="widefat">' +
                '<input type="text" name="wptm_itinerary[' + idx + '][accommodation]" value="" placeholder="Accommodation" class="widefat">' +
                '</div></div></div>';
        },
        room: function(idx) {
            return '<div class="wptm-repeater-item">' +
                '<div class="wptm-repeater-header"><span class="dashicons dashicons-menu wptm-drag"></span><span class="wptm-repeater-badge">' + (idx + 1) + '</span><strong>Room ' + (idx + 1) + '</strong>' +
                '<button type="button" class="wptm-remove-item button-link"><span class="dashicons dashicons-trash"></span></button></div>' +
                '<div class="wptm-repeater-body wptm-meta-grid">' +
                '<div class="wptm-meta-field"><label>Name</label><input type="text" name="wptm_rooms[' + idx + '][name]" value="" class="widefat"></div>' +
                '<div class="wptm-meta-field"><label>Type</label><select name="wptm_rooms[' + idx + '][type]"><option value="standard">Standard</option><option value="deluxe">Deluxe</option><option value="suite">Suite</option><option value="family">Family</option><option value="presidential">Presidential</option></select></div>' +
                '<div class="wptm-meta-field"><label>Price / Night</label><input type="number" name="wptm_rooms[' + idx + '][price]" value="" step="0.01" min="0" placeholder="0.00"></div>' +
                '<div class="wptm-meta-field"><label>Sale Price</label><input type="number" name="wptm_rooms[' + idx + '][sale_price]" value="" step="0.01" min="0" placeholder="0.00"></div>' +
                '<div class="wptm-meta-field"><label>Max Guests</label><input type="number" name="wptm_rooms[' + idx + '][max_guests]" value="2" min="1"></div>' +
                '<div class="wptm-meta-field"><label>Bed Type</label><input type="text" name="wptm_rooms[' + idx + '][bed_type]" value="" placeholder="e.g. King"></div>' +
                '<div class="wptm-meta-field"><label>Size</label><input type="text" name="wptm_rooms[' + idx + '][room_size]" value="" placeholder="e.g. 35 sqm"></div>' +
                '<div class="wptm-meta-field wptm-full"><label>Description</label><textarea name="wptm_rooms[' + idx + '][description]" rows="2" class="widefat"></textarea></div>' +
                '</div></div>';
        }
    };
    // Extension point: add-ons register their own repeater row templates here
    // (key = the .wptm-add-item button's data-target).
    window.wptmRepeaterTemplates = repeaterTemplates;
    // Helpers add-ons need when programmatically filling repeaters.
    window.wptmRepeaterHelpers = { listRow: wptmListRow, syncEmptyState: syncEmptyState };

    /* Toggle the empty-state placeholder based on item count */
    function syncEmptyState(repeater) {
        if (!repeater) return;
        var empty = repeater.querySelector('.wptm-empty-state');
        if (!empty) return;
        var count = repeater.querySelectorAll('.wptm-repeater-item').length;
        empty.style.display = count ? 'none' : '';
    }

    /* Repeater (Add/Remove items) — uses inline templates */
    function initRepeaters() {
        document.addEventListener('click', function(e) {
            // Add item
            if (e.target.closest('.wptm-add-item')) {
                var btn = e.target.closest('.wptm-add-item');
                var target = btn.dataset.target;

                // Find the items container: look for the closest .wptm-repeater ancestor, then .wptm-repeater-items
                var repeater = btn.closest('.wptm-repeater');
                var container = repeater ? repeater.querySelector('.wptm-repeater-items') : null;
                if (!container) return;

                var idx = container.querySelectorAll('.wptm-repeater-item').length;

                if (repeaterTemplates[target]) {
                    var temp = document.createElement('div');
                    temp.innerHTML = repeaterTemplates[target](idx);
                    var newItem = temp.firstElementChild;
                    container.appendChild(newItem);
                    syncEmptyState(repeater);
                    newItem.querySelector('input, textarea, select') && newItem.querySelector('input, textarea, select').focus();
                }
            }
            // Remove item
            if (e.target.closest('.wptm-remove-item')) {
                var item = e.target.closest('.wptm-repeater-item');
                if (item && confirm('Remove this item?')) {
                    var rep = item.closest('.wptm-repeater');
                    item.remove();
                    syncEmptyState(rep);
                }
            }
        });
    }

    /* Gallery Uploader — Fixed: matches actual button IDs from metabox views */
    function initGalleryUploader() {
        document.addEventListener('click', function(e) {
            var btn = e.target.closest('#wptm-add-gallery, #wptm-add-hotel-gallery, .wptm-upload-gallery');
            if (!btn) return;
            e.preventDefault();

            // Determine the gallery container
            var galleryContainer = btn.closest('#wptm-gallery, #wptm-hotel-gallery');
            if (!galleryContainer) return;

            var input = galleryContainer.querySelector('input[type="hidden"]');
            var preview = galleryContainer.querySelector('.wptm-gallery-grid');

            if (!wp || !wp.media) return;
            var frame = wp.media({ title: 'Select Gallery Images', multiple: true, library: { type: 'image' } });
            frame.on('select', function() {
                var selection = frame.state().get('selection');
                var ids = input.value ? input.value.split(',').filter(Boolean) : [];

                selection.each(function(attachment) {
                    var a = attachment.toJSON();
                    ids.push(a.id);
                    var thumbUrl = (a.sizes && a.sizes.thumbnail) ? a.sizes.thumbnail.url : a.url;
                    var itemEl = document.createElement('div');
                    itemEl.className = 'wptm-gallery-item';
                    itemEl.setAttribute('data-id', a.id);
                    itemEl.innerHTML = '<img src="' + thumbUrl + '"><button type="button" class="wptm-remove-image">&times;</button>';
                    if (preview) preview.appendChild(itemEl);
                });
                input.value = ids.join(',');
            });
            frame.open();
        });
    }

    /* Gallery Remove Image — Fixed: adds handler for .wptm-remove-image buttons */
    function initGalleryRemove() {
        document.addEventListener('click', function(e) {
            if (!e.target.closest('.wptm-remove-image')) return;
            e.preventDefault();
            var item = e.target.closest('.wptm-gallery-item');
            if (!item) return;

            var galleryContainer = item.closest('#wptm-gallery, #wptm-hotel-gallery');
            if (!galleryContainer) return;

            var input = galleryContainer.querySelector('input[type="hidden"]');
            var removeId = item.getAttribute('data-id');
            item.remove();

            // Update hidden input
            if (input && removeId) {
                var ids = input.value.split(',').filter(function(id) { return id && id !== removeId; });
                input.value = ids.join(',');
            }
        });
    }

    /* Media Picker — set a single attachment URL (video / audio) into a target input */
    function initMediaPicker() {
        document.addEventListener('click', function(e) {
            var btn = e.target.closest('.wptm-media-picker');
            if (!btn) return;
            e.preventDefault();

            var target = document.querySelector(btn.dataset.target);
            if (!target || !window.wp || !wp.media) return;

            var type = btn.dataset.type || 'video';
            var titles = { audio: 'Select Audio', image: 'Select Image', video: 'Select Video' };
            var frame = wp.media({
                title: titles[type] || 'Select File',
                button: { text: 'Use this file' },
                multiple: false,
                library: { type: type }
            });
            frame.on('select', function() {
                var a = frame.state().get('selection').first().toJSON();
                target.value = a.url || '';
                target.dispatchEvent(new Event('input', { bubbles: true }));
            });
            frame.open();
        });
    }

    /* Gallery Style Selector — highlights the chosen layout card */
    function initGalleryStyle() {
        document.addEventListener('change', function(e) {
            var radio = e.target.closest('.wptm-gallery-style input[type="radio"]');
            if (!radio) return;
            var wrap = radio.closest('.wptm-gallery-style');
            if (!wrap) return;
            wrap.querySelectorAll('.wptm-gallery-style__option').forEach(function(opt) {
                opt.classList.remove('is-selected');
            });
            var label = radio.closest('.wptm-gallery-style__option');
            if (label) label.classList.add('is-selected');
        });
    }

    /* Booking Actions — Fixed: data-id attribute and booking_action field */
    function initBookingActions() {
        document.addEventListener('click', function(e) {
            const btn = e.target.closest('.wptm-booking-action');
            if (!btn) return;
            e.preventDefault();
            const action = btn.dataset.action;
            const bookingId = btn.dataset.id;
            if (!bookingId) return;
            if (!confirm('Are you sure you want to ' + action + ' this booking?')) return;

            btn.disabled = true;
            btn.textContent = 'Processing...';

            const fd = new FormData();
            fd.append('action', 'wptm_admin_booking_action');
            fd.append('nonce', WPTM.nonce);
            fd.append('booking_id', bookingId);
            fd.append('booking_action', action);

            fetch(WPTM.ajaxUrl, { method: 'POST', body: fd, credentials: 'same-origin' })
                .then(r => r.json())
                .then(r => {
                    if (r.success) {
                        showNotice('Booking ' + action + 'ed successfully.', 'success');
                        setTimeout(() => location.reload(), 800);
                    } else {
                        btn.disabled = false;
                        btn.textContent = action.charAt(0).toUpperCase() + action.slice(1);
                        showNotice('Error updating booking status.', 'error');
                    }
                })
                .catch(() => {
                    btn.disabled = false;
                    btn.textContent = action.charAt(0).toUpperCase() + action.slice(1);
                    showNotice('Network error. Please try again.', 'error');
                });
        });
    }

    /* Booking Detail Drawer */
    function initBookingDrawer() {
        var drawer = document.getElementById('wptm-booking-drawer');
        if (!drawer) return;
        var body = drawer.querySelector('.wptm-drawer__body');

        function open() { drawer.classList.add('is-open'); drawer.setAttribute('aria-hidden', 'false'); document.body.style.overflow = 'hidden'; }
        function close() { drawer.classList.remove('is-open'); drawer.setAttribute('aria-hidden', 'true'); document.body.style.overflow = ''; }

        document.addEventListener('click', function(e) {
            var trigger = e.target.closest('.wptm-view-booking');
            if (trigger) {
                e.preventDefault();
                var id = trigger.dataset.id;
                body.innerHTML = '<div class="wptm-drawer__loading"><span class="spinner is-active"></span></div>';
                open();
                var fd = new FormData();
                fd.append('action', 'wptm_get_booking');
                fd.append('nonce', WPTM.nonce);
                fd.append('booking_id', id);
                fetch(WPTM.ajaxUrl, { method: 'POST', body: fd, credentials: 'same-origin' })
                    .then(r => r.json())
                    .then(r => {
                        body.innerHTML = (r.success && r.data.html) ? r.data.html : '<p style="padding:24px;">' + ((r.data && r.data.message) || 'Could not load booking.') + '</p>';
                    })
                    .catch(() => { body.innerHTML = '<p style="padding:24px;">Network error.</p>'; });
                return;
            }
            if (e.target.closest('.wptm-drawer__close') || e.target.classList.contains('wptm-drawer__overlay')) {
                close();
            }
        });
        document.addEventListener('keydown', function(e) { if (e.key === 'Escape') close(); });
    }

    /* Bookings client-side search */
    function initBookingsSearch() {
        var input = document.getElementById('wptm-bookings-search');
        if (!input) return;
        input.addEventListener('input', function() {
            var q = this.value.trim().toLowerCase();
            document.querySelectorAll('.wptm-booking-row').forEach(function(row) {
                row.style.display = (!q || (row.dataset.search || '').indexOf(q) !== -1) ? '' : 'none';
            });
        });
    }

    /* Admin Notice */
    function showNotice(msg, type) {
        // Remove any existing notice
        const existing = document.querySelector('.wptm-notice');
        if (existing) existing.remove();

        const notice = document.createElement('div');
        notice.className = 'wptm-notice wptm-notice--' + (type || 'success');
        notice.textContent = msg;
        notice.style.cssText = 'padding:12px 16px;margin:10px 0;border-radius:8px;font-weight:500;animation:fadeIn 0.3s ease;';
        if (type === 'success') {
            notice.style.background = '#ecfdf5'; notice.style.color = '#065f46'; notice.style.border = '1px solid #a7f3d0';
        } else {
            notice.style.background = '#fef2f2'; notice.style.color = '#991b1b'; notice.style.border = '1px solid #fecaca';
        }
        const wrap = document.querySelector('.wptm-admin-wrap') || document.querySelector('.wrap');
        if (wrap) wrap.insertBefore(notice, wrap.children[1] || wrap.firstChild);
        setTimeout(() => { notice.style.opacity = '0'; notice.style.transition = 'opacity 0.3s'; setTimeout(() => notice.remove(), 300); }, 4000);
    }

    /* Generic copy-to-clipboard button (data-copy-target="#selector") */
    function initCopyButtons() {
        document.addEventListener('click', function(e) {
            var btn = e.target.closest('.wptm-copy-btn');
            if (!btn) return;
            e.preventDefault();
            var target = document.querySelector(btn.dataset.copyTarget);
            if (!target) return;
            var text = target.value != null ? target.value : target.textContent;
            var done = function() {
                var orig = btn.innerHTML;
                btn.innerHTML = '<span class="dashicons dashicons-yes"></span> Copied';
                setTimeout(function() { btn.innerHTML = orig; }, 1500);
            };
            if (navigator.clipboard) {
                navigator.clipboard.writeText(text).then(done);
            } else {
                target.select && target.select();
                document.execCommand('copy');
                done();
            }
        });
    }

    /* Booking reply box — send/copy (drawer HTML is injected, so delegate) */
    function initBookingReply() {
        document.addEventListener('click', function(e) {
            var sendBtn  = e.target.closest('.wptm-reply-send');
            var copyBtn  = e.target.closest('.wptm-reply-copy');
            if (!sendBtn && !copyBtn) return;

            var sec = (sendBtn || copyBtn).closest('.wptm-bd__reply');
            if (!sec) return;
            var id      = sec.dataset.id;
            var subject = sec.querySelector('.wptm-reply-subject');
            var message = sec.querySelector('.wptm-reply-message');

            function status(msg, kind) {
                var el = sec.querySelector('.wptm-reply__status');
                if (el) { el.textContent = msg || ''; el.className = 'wptm-reply__status' + (kind ? ' is-' + kind : ''); }
            }

            // Copy ------------------------------------------------------------
            if (copyBtn) {
                if (!message.value.trim()) { status('Nothing to copy yet.', 'error'); return; }
                if (navigator.clipboard) {
                    navigator.clipboard.writeText(message.value).then(function() { status('Copied to clipboard.', 'success'); });
                } else {
                    message.select(); document.execCommand('copy'); status('Copied to clipboard.', 'success');
                }
                return;
            }

            // Send ------------------------------------------------------------
            if (sendBtn) {
                if (!message.value.trim()) { status('Write a message first.', 'error'); return; }
                if (!confirm('Send this reply to the customer?')) return;
                var sOrig = sendBtn.innerHTML;
                sendBtn.disabled = true;
                sendBtn.innerHTML = '<span class="dashicons dashicons-update wptm-spin"></span> Sending…';
                status('', '');

                var sfd = new FormData();
                sfd.append('action', 'wptm_send_reply');
                sfd.append('nonce', WPTM.nonce || '');
                sfd.append('booking_id', id);
                sfd.append('subject', subject ? subject.value : '');
                sfd.append('message', message.value);

                fetch(WPTM.ajaxUrl, { method: 'POST', body: sfd, credentials: 'same-origin' })
                    .then(function(r) { return r.json(); })
                    .then(function(r) {
                        sendBtn.disabled = false;
                        sendBtn.innerHTML = sOrig;
                        if (r.success) {
                            status((r.data && r.data.message) || 'Reply sent.', 'success');
                            message.value = '';
                            if (typeof showNotice === 'function') showNotice('Reply sent to customer.', 'success');
                        } else {
                            status((r.data && r.data.message) || 'Send failed.', 'error');
                        }
                    })
                    .catch(function() { sendBtn.disabled = false; sendBtn.innerHTML = sOrig; status('Network error. Try again.', 'error'); });
                return;
            }
        });
    }

    /* Taxonomy term featured image — select / remove via the media library */
    function initTermImage() {
        document.addEventListener('click', function(e) {
            var uploadBtn = e.target.closest('.wptm-term-image-upload');
            var removeBtn = e.target.closest('.wptm-term-image-remove');
            if (!uploadBtn && !removeBtn) return;
            e.preventDefault();

            var field = (uploadBtn || removeBtn).closest('.wptm-term-image-field');
            if (!field) return;
            var input = field.querySelector('.wptm-term-image-input');
            var preview = field.querySelector('.wptm-term-image-preview');
            var remove = field.querySelector('.wptm-term-image-remove');

            if (removeBtn) {
                if (input) input.value = '';
                if (preview) preview.innerHTML = '';
                if (remove) remove.style.display = 'none';
                return;
            }

            if (!window.wp || !wp.media) return;
            var frame = wp.media({
                title: 'Select Image',
                button: { text: 'Use this image' },
                multiple: false,
                library: { type: 'image' }
            });
            frame.on('select', function() {
                var a = frame.state().get('selection').first().toJSON();
                var url = (a.sizes && a.sizes.medium) ? a.sizes.medium.url : a.url;
                if (input) input.value = url || '';
                if (preview) preview.innerHTML = url ? '<img src="' + url + '" alt="" style="max-width:180px;height:auto;border-radius:8px;display:block;">' : '';
                if (remove) remove.style.display = url ? '' : 'none';
            });
            frame.open();
        });

        // Clear the "Add term" image field after a term is added via AJAX.
        document.addEventListener('ajaxComplete', function() {
            var addForm = document.getElementById('addtag');
            if (!addForm) return;
            var field = addForm.querySelector('.wptm-term-image-field');
            if (!field) return;
            var input = field.querySelector('.wptm-term-image-input');
            var preview = field.querySelector('.wptm-term-image-preview');
            var remove = field.querySelector('.wptm-term-image-remove');
            if (input) input.value = '';
            if (preview) preview.innerHTML = '';
            if (remove) remove.style.display = 'none';
        });
    }

    /* Demo Content Importer (dashboard) */
    function initDemoImporter() {
        const wrap = document.querySelector('.wptm-demo-importer');
        if (!wrap) return;

        const importBtn = wrap.querySelector('#wptm-import-demo');
        const removeBtn = wrap.querySelector('#wptm-remove-demo');
        const status = wrap.querySelector('#wptm-demo-status');
        const spinner = wrap.querySelector('.spinner');
        const nonce = wrap.getAttribute('data-import-nonce') || WPTM.nonce;

        function setBusy(busy) {
            if (spinner) spinner.classList.toggle('is-active', busy);
            if (importBtn) importBtn.disabled = busy;
            if (removeBtn) removeBtn.disabled = busy;
        }

        function say(message, isError) {
            if (!status) return;
            status.textContent = message;
            status.style.color = isError ? '#b32d2e' : '';
        }

        function refreshStatus(counts) {
            if (!counts || !removeBtn) return;
            removeBtn.style.display = (counts.trip + counts.hotel) > 0 ? '' : 'none';
        }

        const imagesCb = wrap.querySelector('#wptm-demo-images');

        // Attach bundled placeholder images sequentially — one item at a time.
        function processImages(queue, idx, importMsg) {
            if (idx >= queue.length) {
                say(importMsg + ' Images imported.');
                setBusy(false);
                return;
            }
            say(importMsg + ' Importing images ' + (idx + 1) + '/' + queue.length + '…');

            const fd = new FormData();
            fd.append('action', 'wptm_import_demo_image');
            fd.append('nonce', nonce);
            fd.append('post_id', queue[idx]);

            fetch(WPTM.ajaxUrl, { method: 'POST', body: fd, credentials: 'same-origin' })
                .catch(() => {}) // keep going even if one image fails
                .finally(() => processImages(queue, idx + 1, importMsg));
        }

        if (importBtn) {
            importBtn.addEventListener('click', function() {
                const types = Array.prototype.map.call(
                    wrap.querySelectorAll('input[name="wptm_demo_type"]:checked'),
                    el => el.value
                );
                if (!types.length) {
                    say('Please choose at least one content type to import.', true);
                    return;
                }

                const wantImages = imagesCb && imagesCb.checked;

                const fd = new FormData();
                fd.append('action', 'wptm_import_demo');
                fd.append('nonce', nonce);
                types.forEach(t => fd.append('types[]', t));
                if (wantImages) {
                    fd.append('images', '1');
                }

                setBusy(true);
                say('Importing demo content…');

                fetch(WPTM.ajaxUrl, { method: 'POST', body: fd, credentials: 'same-origin' })
                    .then(r => r.json())
                    .then(res => {
                        if (res && res.success) {
                            refreshStatus(res.data.counts);
                            const queue = res.data.image_queue || [];
                            if (queue.length) {
                                processImages(queue, 0, res.data.message);
                            } else {
                                say(res.data.message);
                                setBusy(false);
                            }
                        } else {
                            say((res && res.data && res.data.message) || 'Import failed.', true);
                            setBusy(false);
                        }
                    })
                    .catch(() => { say('Import failed. Please try again.', true); setBusy(false); });
            });
        }

        if (removeBtn) {
            removeBtn.addEventListener('click', function() {
                if (!window.confirm('Remove all imported demo trips and hotels? This cannot be undone.')) {
                    return;
                }
                const fd = new FormData();
                fd.append('action', 'wptm_remove_demo');
                fd.append('nonce', nonce);

                setBusy(true);
                say('Removing demo content…');

                fetch(WPTM.ajaxUrl, { method: 'POST', body: fd, credentials: 'same-origin' })
                    .then(r => r.json())
                    .then(res => {
                        if (res && res.success) {
                            say(res.data.message);
                            refreshStatus(res.data.counts);
                        } else {
                            say((res && res.data && res.data.message) || 'Removal failed.', true);
                        }
                    })
                    .catch(() => say('Removal failed. Please try again.', true))
                    .finally(() => setBusy(false));
            });
        }
    }

    /* Init All */
    document.addEventListener('DOMContentLoaded', function() {
        initSettingsTabs();
        initSettingsNav();
        initTestEmail();
        initColorPickers();
        initCurrencySelect();
        initSettingsSave();
        initSearchFormBuilder();
        initMetaboxTabs();
        initRepeaters();
        initGalleryUploader();
        initGalleryRemove();
        initGalleryStyle();
        initMediaPicker();
        initBookingActions();
        initBookingDrawer();
        initBookingsSearch();
        initBookingReply();
        initCopyButtons();
        initTermImage();
        initDemoImporter();
    });
})();
