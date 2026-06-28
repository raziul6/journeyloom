/**
 * WP Travel Machine — Location Maps (Leaflet + OpenStreetMap)
 */
(function() {
    'use strict';

    function initMaps() {
        if (typeof window.L === 'undefined') return; // Leaflet failed to load.
        var maps = document.querySelectorAll('.wptm-map');
        Array.prototype.forEach.call(maps, function(el) {
            if (el.getAttribute('data-wptm-init')) return;
            var lat = parseFloat(el.getAttribute('data-lat'));
            var lng = parseFloat(el.getAttribute('data-lng'));
            if (isNaN(lat) || isNaN(lng)) return;
            el.setAttribute('data-wptm-init', '1');

            var map = L.map(el, { scrollWheelZoom: false }).setView([lat, lng], 13);
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                maxZoom: 19,
                attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
            }).addTo(map);

            var marker = L.marker([lat, lng]).addTo(map);
            var label = el.getAttribute('data-label');
            if (label) { marker.bindPopup(label); }

            // Tiles can render grey if the container was laid out after init
            // (sticky sidebars, late fonts, tab panels) — force a recalculation.
            setTimeout(function() { map.invalidateSize(); }, 200);
        });
    }

    if (document.readyState !== 'loading') { initMaps(); }
    else { document.addEventListener('DOMContentLoaded', initMaps); }
})();
