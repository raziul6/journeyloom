/**
 * WP Travel Machine — Booking Calendar
 *
 * A self-contained date picker for the booking form. Supports single-date and
 * range selection, disables past dates and admin-blocked (unavailable) periods,
 * and writes the chosen dates into the form's hidden check_in / check_out inputs
 * (dispatching change so the booking engine reacts).
 */
(function () {
    'use strict';

    var MONTHS = ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];
    var DOW = ['Su', 'Mo', 'Tu', 'We', 'Th', 'Fr', 'Sa'];

    function pad(n) { return String(n).padStart(2, '0'); }
    function iso(y, m, d) { return y + '-' + pad(m + 1) + '-' + pad(d); }
    function parseISO(s) { var p = (s || '').split('-'); return p.length === 3 ? new Date(+p[0], +p[1] - 1, +p[2]) : null; }
    function todayISO() { var d = new Date(); return iso(d.getFullYear(), d.getMonth(), d.getDate()); }
    function addDays(isoStr, n) { var d = parseISO(isoStr); d.setDate(d.getDate() + n); return iso(d.getFullYear(), d.getMonth(), d.getDate()); }

    function Calendar(el) {
        this.el = el;
        this.mode = el.dataset.mode === 'range' ? 'range' : 'single';
        this.min = el.dataset.min || todayISO();
        this.endOffset = el.dataset.endOffset !== undefined ? (parseInt(el.dataset.endOffset, 10) || 0) : null;
        this.currency = el.dataset.currency || '';
        try { this.blocked = JSON.parse(el.dataset.unavailable || '[]') || []; } catch (e) { this.blocked = []; }
        try { this.soldout = JSON.parse(el.dataset.soldout || '[]') || []; } catch (e2) { this.soldout = []; }
        try { this.prices = JSON.parse(el.dataset.prices || '[]') || []; } catch (e3) { this.prices = []; }

        this.dp = el.closest('.wptm-datepicker') || el.parentNode;
        this.inEl = this.dp.querySelector('[name="check_in"]');
        this.outEl = this.dp.querySelector('[name="check_out"]');
        this.inLabel = this.dp.querySelector('.wptm-dp-in');
        this.outLabel = this.dp.querySelector('.wptm-dp-out');

        this.start = null;
        this.end = null;

        var first = parseISO(this.min);
        this.viewYear = first.getFullYear();
        this.viewMonth = first.getMonth();

        this.render();
    }

    function inRanges(dateStr, ranges) {
        for (var i = 0; i < ranges.length; i++) {
            var r = ranges[i];
            if (r.start && r.end && dateStr >= r.start && dateStr <= r.end) return true;
        }
        return false;
    }

    Calendar.prototype.isBlocked = function (dateStr) { return inRanges(dateStr, this.blocked); };
    Calendar.prototype.isSoldout = function (dateStr) { return inRanges(dateStr, this.soldout); };
    Calendar.prototype.isUnavailable = function (dateStr) { return this.isBlocked(dateStr) || this.isSoldout(dateStr); };

    Calendar.prototype.isDisabled = function (dateStr) {
        return dateStr < this.min || this.isUnavailable(dateStr);
    };

    // Price override active on a date (or null).
    Calendar.prototype.priceFor = function (dateStr) {
        for (var i = 0; i < this.prices.length; i++) {
            var p = this.prices[i];
            if (p.start && p.end && dateStr >= p.start && dateStr <= p.end) return p.price;
        }
        return null;
    };

    // Any unavailable night between two dates (the checkout day itself is excluded).
    Calendar.prototype.rangeHasBlocked = function (startStr, endStr) {
        var cur = startStr;
        while (cur < endStr) {
            if (this.isUnavailable(cur)) return true;
            cur = addDays(cur, 1);
        }
        return false;
    };

    Calendar.prototype.pick = function (dateStr) {
        if (this.isDisabled(dateStr)) return;

        if (this.mode === 'single') {
            this.start = dateStr;
            this.end = null;
            this.commit();
            this.render();
            return;
        }

        // Range mode (check-in → check-out).
        if (!this.start || (this.start && this.end)) {
            this.start = dateStr;
            this.end = null;
        } else if (dateStr > this.start && !this.rangeHasBlocked(this.start, dateStr)) {
            this.end = dateStr;
        } else {
            this.start = dateStr;
            this.end = null;
        }
        this.commit();
        this.render();
    };

    Calendar.prototype.setInput = function (input, value, label) {
        if (input) {
            input.value = value || '';
            input.dispatchEvent(new Event('change', { bubbles: true }));
        }
        if (label) {
            label.textContent = value
                ? parseISO(value).toLocaleDateString(undefined, { weekday: 'short', month: 'short', day: 'numeric' })
                : 'Select date';
        }
    };

    Calendar.prototype.commit = function () {
        this.setInput(this.inEl, this.start, this.inLabel);
        if (this.mode === 'range') {
            this.setInput(this.outEl, this.end, this.outLabel);
        } else if (this.outEl && this.endOffset !== null && this.start) {
            // Fixed-length trip: derive the end date for the booking payload.
            this.setInput(this.outEl, addDays(this.start, this.endOffset), null);
        }
    };

    Calendar.prototype.render = function () {
        var firstDow = new Date(this.viewYear, this.viewMonth, 1).getDay();
        var total = new Date(this.viewYear, this.viewMonth + 1, 0).getDate();
        var minDate = parseISO(this.min);
        var canPrev = (this.viewYear > minDate.getFullYear()) || (this.viewYear === minDate.getFullYear() && this.viewMonth > minDate.getMonth());

        var html = '<div class="wptm-calendar__header">';
        html += '<button type="button" class="wptm-calendar__nav wptm-cal-prev"' + (canPrev ? '' : ' disabled') + ' aria-label="Previous month">‹</button>';
        html += '<h4>' + MONTHS[this.viewMonth] + ' ' + this.viewYear + '</h4>';
        html += '<button type="button" class="wptm-calendar__nav wptm-cal-next" aria-label="Next month">›</button></div>';
        html += '<div class="wptm-calendar__grid">';
        DOW.forEach(function (d) { html += '<div class="wptm-calendar__day-name">' + d + '</div>'; });
        for (var i = 0; i < firstDow; i++) html += '<div class="wptm-calendar__pad"></div>';

        for (var d = 1; d <= total; d++) {
            var ds = iso(this.viewYear, this.viewMonth, d);
            var cls = 'wptm-calendar__day';
            var price = null;
            if (this.isDisabled(ds)) {
                cls += this.isBlocked(ds) ? ' blocked' : (this.isSoldout(ds) ? ' soldout' : ' disabled');
            } else {
                price = this.priceFor(ds);
                if (price !== null) cls += ' has-price';
            }
            if (ds === todayISO()) cls += ' today';
            if (ds === this.start) cls += ' is-start';
            if (ds === this.end) cls += ' is-end';
            if (this.start && this.end && ds > this.start && ds < this.end) cls += ' in-range';

            var inner = '<span class="wptm-cal-num">' + d + '</span>';
            if (price !== null) inner += '<span class="wptm-cal-price">' + this.currency + Math.round(price) + '</span>';
            html += '<button type="button" class="' + cls + '" data-date="' + ds + '">' + inner + '</button>';
        }
        html += '</div>';
        var legend = [];
        if (this.blocked.length) legend.push('<span class="wptm-cal-leg"><span class="wptm-cal-dot wptm-cal-dot--blocked"></span> Unavailable</span>');
        if (this.soldout.length) legend.push('<span class="wptm-cal-leg"><span class="wptm-cal-dot wptm-cal-dot--soldout"></span> Sold out</span>');
        if (legend.length) html += '<div class="wptm-calendar__legend">' + legend.join('') + '</div>';
        this.el.innerHTML = html;

        var self = this;
        var prev = this.el.querySelector('.wptm-cal-prev');
        var next = this.el.querySelector('.wptm-cal-next');
        if (prev && canPrev) prev.addEventListener('click', function () { if (--self.viewMonth < 0) { self.viewMonth = 11; self.viewYear--; } self.render(); });
        if (next) next.addEventListener('click', function () { if (++self.viewMonth > 11) { self.viewMonth = 0; self.viewYear++; } self.render(); });
        this.el.querySelectorAll('.wptm-calendar__day').forEach(function (btn) {
            btn.addEventListener('click', function () { self.pick(this.dataset.date); });
        });
    };

    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('.wptm-calendar').forEach(function (el) { new Calendar(el); });
    });
})();
