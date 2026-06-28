/**
 * WP Travel Machine — Main Public App
 */
(function() {
    'use strict';
    const WPTM = window.wptmData || {};

    /* Toast Notification */
    window.wptmToast = function(msg, type) {
        type = type || 'success';
        const el = document.createElement('div');
        el.className = 'wptm-toast wptm-toast--' + type;
        el.textContent = msg;
        document.body.appendChild(el);
        setTimeout(() => { el.style.opacity = '0'; setTimeout(() => el.remove(), 300); }, 3000);
    };

    /* AJAX Helper */
    window.wptmAjax = function(action, data, cb) {
        data = data || {};
        const fd = new FormData();
        fd.append('action', action);
        fd.append('nonce', data.nonce || WPTM.nonce || '');
        Object.keys(data).forEach(k => { if (k !== 'nonce') fd.append(k, data[k]); });
        fetch(WPTM.ajaxUrl, { method: 'POST', body: fd, credentials: 'same-origin' })
            .then(r => r.json())
            .then(r => cb && cb(r))
            .catch(e => { console.error('WPTM AJAX Error:', e); if (cb) cb({ success: false }); });
    };

    /* Format Currency */
    window.wptmFormatPrice = function(amount) {
        const sym = WPTM.currency || '$';
        const pos = WPTM.currencyPos || 'before';
        const formatted = parseFloat(amount).toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ',');
        return pos === 'before' ? sym + formatted : formatted + sym;
    };

    /* Recently Viewed Tracker */
    function trackRecentlyViewed() {
        const body = document.body;
        if (!body.classList.contains('single-wptm_trip') && !body.classList.contains('single-wptm_hotel')) return;
        const match = body.className.match(/postid-(\d+)/);
        if (!match) return;
        const id = match[1];
        let ids = (document.cookie.match(/wptm_recently_viewed=([^;]+)/) || [, ''])[1].split(',').filter(Boolean);
        ids = ids.filter(v => v !== id);
        ids.unshift(id);
        ids = ids.slice(0, 10);
        document.cookie = 'wptm_recently_viewed=' + ids.join(',') + ';path=/;max-age=' + (86400 * 30);
    }

    /* Lazy Load Images */
    function initLazyLoad() {
        if ('IntersectionObserver' in window) {
            const obs = new IntersectionObserver((entries) => {
                entries.forEach(e => {
                    if (e.isIntersecting) {
                        const img = e.target;
                        if (img.dataset.src) { img.src = img.dataset.src; img.removeAttribute('data-src'); }
                        obs.unobserve(img);
                    }
                });
            }, { rootMargin: '100px' });
            document.querySelectorAll('img[data-src]').forEach(img => obs.observe(img));
        }
    }

    /* Sticky Booking Bar */
    function initStickyBar() {
        const bar = document.querySelector('.wptm-sticky-bar');
        if (!bar) return;
        const trigger = document.querySelector('.wptm-booking-form') || document.querySelector('.wptm-single-sidebar');
        if (!trigger) return;
        window.addEventListener('scroll', () => {
            const rect = trigger.getBoundingClientRect();
            bar.classList.toggle('visible', rect.bottom < 0);
        }, { passive: true });
    }

    /* Gallery Lightbox — navigable (prev/next, counter, keyboard) */
    function initLightbox() {
        var lb = null, items = [], idx = 0;

        function ensure() {
            if (lb) return lb;
            lb = document.createElement('div');
            lb.className = 'wptm-lightbox';
            lb.innerHTML =
                '<button class="wptm-lightbox__close" aria-label="Close">&times;</button>' +
                '<button class="wptm-lightbox__nav wptm-lightbox__nav--prev" aria-label="Previous">&#8249;</button>' +
                '<figure class="wptm-lightbox__stage"><img src="" alt=""></figure>' +
                '<button class="wptm-lightbox__nav wptm-lightbox__nav--next" aria-label="Next">&#8250;</button>' +
                '<div class="wptm-lightbox__counter"><span class="cur">1</span> / <span class="total">1</span></div>';
            document.body.appendChild(lb);
            lb.querySelector('.wptm-lightbox__close').addEventListener('click', close);
            lb.querySelector('.wptm-lightbox__nav--prev').addEventListener('click', function (e) { e.stopPropagation(); step(-1); });
            lb.querySelector('.wptm-lightbox__nav--next').addEventListener('click', function (e) { e.stopPropagation(); step(1); });
            lb.addEventListener('click', function (e) {
                if (e.target === lb || e.target.closest('.wptm-lightbox__stage')) close();
            });
            return lb;
        }
        function render() {
            ensure();
            var img = lb.querySelector('.wptm-lightbox__stage img');
            img.classList.remove('is-in');
            img.src = items[idx];
            requestAnimationFrame(function () { img.classList.add('is-in'); });
            lb.querySelector('.cur').textContent = idx + 1;
            lb.querySelector('.total').textContent = items.length;
            lb.classList.toggle('is-single', items.length < 2);
        }
        function open(list, start) {
            if (!list.length) return;
            items = list; idx = start || 0;
            ensure(); lb.classList.add('open'); render();
            document.addEventListener('keydown', onKey);
        }
        function close() { if (lb) lb.classList.remove('open'); document.removeEventListener('keydown', onKey); }
        function step(d) { if (!items.length) return; idx = (idx + d + items.length) % items.length; render(); }
        function onKey(e) {
            if (e.key === 'Escape') close();
            else if (e.key === 'ArrowLeft') step(-1);
            else if (e.key === 'ArrowRight') step(1);
        }
        function groupFor(el) {
            var scope = el.closest('[data-hero-gallery]') || el.closest('.wptm-gallery') || document;
            var list = [], seen = {};
            scope.querySelectorAll('.wptm-lightbox-trigger, .wptm-gallery-item img').forEach(function (im) {
                var s = im.dataset.full || im.src;
                if (s && !seen[s]) { seen[s] = 1; list.push(s); }
            });
            return list;
        }

        document.addEventListener('click', function (e) {
            var trigger = e.target.closest('.wptm-lightbox-trigger, .wptm-gallery-item img');
            if (trigger) {
                e.preventDefault();
                var list = groupFor(trigger);
                var src = trigger.dataset.full || trigger.src;
                open(list, Math.max(0, list.indexOf(src)));
                return;
            }
            var openAll = e.target.closest('[data-gallery-open]');
            if (openAll) {
                e.preventDefault();
                open(groupFor(openAll), 0);
            }
        });
    }

    /* Hero Gallery Slider — featured image first, then gallery images one by one */
    function initHeroGallery() {
        document.querySelectorAll('[data-hero-gallery]').forEach(function(gal) {
            var slides = gal.querySelectorAll('.wptm-hero-gallery__slide');
            if (slides.length < 2) return;

            var dots = gal.querySelectorAll('.wptm-hg-dot');
            var current = gal.querySelector('.wptm-hg-current');
            var idx = 0, timer = null;

            function show(i) {
                idx = (i + slides.length) % slides.length;
                slides.forEach(function(s, n) { s.classList.toggle('is-active', n === idx); });
                dots.forEach(function(d, n) { d.classList.toggle('is-active', n === idx); });
                if (current) current.textContent = idx + 1;
            }
            function play() { timer = setInterval(function() { show(idx + 1); }, 6000); }
            function restart() { clearInterval(timer); play(); }

            var prev = gal.querySelector('.wptm-hero-gallery__nav--prev');
            var next = gal.querySelector('.wptm-hero-gallery__nav--next');
            if (prev) prev.addEventListener('click', function() { show(idx - 1); restart(); });
            if (next) next.addEventListener('click', function() { show(idx + 1); restart(); });
            dots.forEach(function(d) {
                d.addEventListener('click', function() { show(parseInt(this.dataset.index, 10) || 0); restart(); });
            });
            gal.addEventListener('mouseenter', function() { clearInterval(timer); });
            gal.addEventListener('mouseleave', play);
            play();
        });
    }

    /* Build a video embed from a URL (YouTube / Vimeo / direct file) */
    function buildVideoEmbed(url) {
        var yt = url.match(/(?:youtube\.com\/(?:watch\?v=|embed\/)|youtu\.be\/)([\w-]{11})/);
        if (yt) return '<iframe src="https://www.youtube.com/embed/' + yt[1] + '?autoplay=1&rel=0" allow="autoplay; encrypted-media; fullscreen" allowfullscreen></iframe>';
        var vm = url.match(/vimeo\.com\/(?:video\/)?(\d+)/);
        if (vm) return '<iframe src="https://player.vimeo.com/video/' + vm[1] + '?autoplay=1" allow="autoplay; fullscreen" allowfullscreen></iframe>';
        return '<video src="' + url + '" controls autoplay playsinline></video>';
    }

    /* Hero Video — open a modal with the embedded video */
    function initHeroVideo() {
        document.addEventListener('click', function(e) {
            var btn = e.target.closest('.wptm-hero-video-btn');
            if (!btn) return;
            e.preventDefault();
            var url = btn.dataset.video;
            if (!url) return;

            var modal = document.createElement('div');
            modal.className = 'wptm-video-modal';
            modal.innerHTML = '<div class="wptm-video-modal__inner">' +
                '<button class="wptm-video-modal__close" aria-label="Close">&times;</button>' +
                '<div class="wptm-video-modal__frame">' + buildVideoEmbed(url) + '</div></div>';
            document.body.appendChild(modal);
            requestAnimationFrame(function() { modal.classList.add('open'); });

            function close() { modal.remove(); document.removeEventListener('keydown', onKey); }
            function onKey(ev) { if (ev.key === 'Escape') close(); }
            modal.addEventListener('click', function(ev) {
                if (ev.target === modal || ev.target.closest('.wptm-video-modal__close')) close();
            });
            document.addEventListener('keydown', onKey);
        });
    }

    /* Hero Audio — toggle play/pause */
    function initHeroAudio() {
        document.addEventListener('click', function(e) {
            var btn = e.target.closest('.wptm-hero-audio-btn');
            if (!btn) return;
            e.preventDefault();
            var wrap = btn.closest('.wptm-hero-gallery__bar') || document;
            var audio = wrap.querySelector('.wptm-hero-audio');
            if (!audio) return;
            var label = btn.querySelector('.wptm-hero-audio-label');
            if (audio.paused) {
                audio.play();
                btn.classList.add('is-playing');
                btn.setAttribute('aria-pressed', 'true');
                if (label) label.textContent = 'Pause Audio';
            } else {
                audio.pause();
                btn.classList.remove('is-playing');
                btn.setAttribute('aria-pressed', 'false');
                if (label) label.textContent = 'Play Audio';
            }
        });
    }

    /* Enquiry form submit (admin-configurable fields) */
    function initEnquiryForm() {
        var form = document.querySelector('.wptm-enquiry-form');
        if (!form) return;
        var statusEl = form.querySelector('.wptm-enquiry__status');

        function setStatus(msg, isError) {
            if (!statusEl) return;
            statusEl.textContent = msg || '';
            statusEl.style.display = msg ? 'block' : 'none';
            statusEl.classList.toggle('is-error', !!isError);
        }

        form.addEventListener('submit', function(e) {
            e.preventDefault();
            if (!form.checkValidity()) { form.reportValidity(); return; }

            var btn = form.querySelector('[type="submit"]');
            var orig = btn ? btn.textContent : '';
            if (btn) { btn.disabled = true; btn.textContent = 'Sending…'; }
            setStatus('');

            var data = { nonce: WPTM.nonce, post_id: form.getAttribute('data-post-id') || 0 };
            new FormData(form).forEach(function(v, k) { data[k] = v; });

            wptmAjax('wptm_enquiry', data, function(r) {
                if (btn) { btn.disabled = false; btn.textContent = orig; }
                if (r.success) {
                    form.reset();
                    setStatus((r.data && r.data.message) || 'Your enquiry has been sent.', false);
                } else {
                    setStatus((r.data && r.data.message) || 'Something went wrong. Please try again.', true);
                }
            });
        });
    }

    /* Smoothly animate <details> accordions (itinerary days + FAQ items) */
    function initSmoothAccordions() {
        var items = document.querySelectorAll('details.wptm-itinerary__day, details.wptm-faq__item');
        if (!items.length) return;
        // Respect reduced-motion / browsers without the Web Animations API.
        var reduce = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;

        Array.prototype.forEach.call(items, function(detail) {
            var summary = detail.querySelector('summary');
            var content = summary ? summary.nextElementSibling : null;
            if (!summary || !content || typeof detail.animate !== 'function' || reduce) return;

            var animation = null, isClosing = false, isExpanding = false;

            function onFinish(open) {
                detail.open = open;
                animation = null; isClosing = false; isExpanding = false;
                detail.style.height = '';
                detail.style.overflow = '';
            }
            function expand() {
                isExpanding = true;
                var start = detail.offsetHeight + 'px';
                var end = (summary.offsetHeight + content.offsetHeight) + 'px';
                if (animation) animation.cancel();
                animation = detail.animate({ height: [start, end] }, { duration: 280, easing: 'ease' });
                animation.onfinish = function() { onFinish(true); };
                animation.oncancel = function() { isExpanding = false; };
            }
            function shrink() {
                isClosing = true;
                var start = detail.offsetHeight + 'px';
                var end = summary.offsetHeight + 'px';
                if (animation) animation.cancel();
                animation = detail.animate({ height: [start, end] }, { duration: 240, easing: 'ease' });
                animation.onfinish = function() { onFinish(false); };
                animation.oncancel = function() { isClosing = false; };
            }

            summary.addEventListener('click', function(e) {
                e.preventDefault();
                detail.style.overflow = 'hidden';
                if (isClosing || !detail.open) {
                    detail.style.height = detail.offsetHeight + 'px';
                    detail.open = true;
                    window.requestAnimationFrame(expand);
                } else if (isExpanding || detail.open) {
                    shrink();
                }
            });
        });
    }

    /* Itinerary "Expand all / Collapse all" toggle */
    function initItineraryToggle() {
        var btn = document.querySelector('.wptm-itinerary__toggle-all');
        if (!btn) return;
        var days = document.querySelectorAll('.wptm-itinerary .wptm-itinerary__day');
        if (!days.length) return;

        var expandLabel = btn.getAttribute('data-expand') || 'Expand all';
        var collapseLabel = btn.getAttribute('data-collapse') || 'Collapse all';
        var labelEl = btn.querySelector('.wptm-itinerary__toggle-label') || btn;

        function allOpen() {
            return Array.prototype.every.call(days, function(d) { return d.open; });
        }
        function sync() {
            var open = allOpen();
            btn.classList.toggle('is-open', open);
            btn.setAttribute('aria-expanded', open ? 'true' : 'false');
            labelEl.textContent = open ? collapseLabel : expandLabel;
        }

        btn.addEventListener('click', function() {
            var open = !allOpen(); // not all open → open all; otherwise collapse all
            Array.prototype.forEach.call(days, function(d) { d.open = open; });
            sync();
        });
        // Keep the button label accurate when individual days are toggled.
        Array.prototype.forEach.call(days, function(d) { d.addEventListener('toggle', sync); });
        sync();
    }

    /* Init */
    document.addEventListener('DOMContentLoaded', function() {
        trackRecentlyViewed();
        initLazyLoad();
        initStickyBar();
        initLightbox();
        initHeroGallery();
        initHeroVideo();
        initHeroAudio();
        initEnquiryForm();
        initSmoothAccordions();
        initItineraryToggle();
    });
})();
