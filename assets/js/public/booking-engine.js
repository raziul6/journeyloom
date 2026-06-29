/**
 * WP Travel Machine — Booking Engine JS
 */
(function() {
    'use strict';
    const WPTM = window.wptmData || {};

    function initBookingForm() {
        const form = document.getElementById('wptm-booking-form');
        if (!form) return;

        // The actual <form> element lives inside the container div. FormData()
        // requires an HTMLFormElement, so resolve it (fall back to the container).
        const formEl = form.querySelector('.wptm-booking-fields') || form;

        const itemId = form.dataset.itemId;
        const itemType = form.dataset.itemType || 'trip';

        // Travelers count
        const travelersInput = form.querySelector('[name="travelers_count"]');
        const minusBtn = form.querySelector('.wptm-travelers-minus');
        const plusBtn = form.querySelector('.wptm-travelers-plus');

        // Multi-tier pricing (Adult / Child / …) — present only on multi-tier trips.
        const tierInputs = form.querySelectorAll('.wptm-tier-input');

        if (minusBtn && plusBtn && travelersInput) {
            minusBtn.addEventListener('click', () => {
                let v = parseInt(travelersInput.value) - 1;
                if (v < 1) v = 1;
                travelersInput.value = v;
                updatePriceSummary();
            });
            plusBtn.addEventListener('click', () => {
                let v = parseInt(travelersInput.value) + 1;
                travelersInput.value = v;
                updatePriceSummary();
            });
        }

        // Tier quantity steppers.
        form.querySelectorAll('.wptm-tier-row').forEach(function(row) {
            const inp = row.querySelector('.wptm-tier-input');
            if (!inp) return;
            const dec = row.querySelector('.wptm-tier-minus');
            const inc = row.querySelector('.wptm-tier-plus');
            if (dec) dec.addEventListener('click', () => { inp.value = Math.max(0, (parseInt(inp.value, 10) || 0) - 1); updatePriceSummary(); });
            if (inc) inc.addEventListener('click', () => { inp.value = (parseInt(inp.value, 10) || 0) + 1; updatePriceSummary(); });
            inp.addEventListener('input', updatePriceSummary);
        });

        // Date change — check availability
        const checkInInput = form.querySelector('[name="check_in"]');
        const checkOutInput = form.querySelector('[name="check_out"]');

        // Fixed-length trips: derive the end date from the departure date so the
        // visitor can't pick an arbitrary range (the price is a fixed per-person
        // package and never depends on the number of days).
        const endOffset = form.dataset.endOffset;
        function syncTripEndDate() {
            if (endOffset === undefined || endOffset === '' || !checkInInput || !checkOutInput) return;
            const hint = form.querySelector('.wptm-return-hint');
            if (!checkInInput.value) { checkOutInput.value = ''; if (hint) hint.textContent = ''; return; }
            const off = parseInt(endOffset, 10) || 0;
            const d = new Date(checkInInput.value + 'T00:00:00');
            d.setDate(d.getDate() + off);
            const pad = n => String(n).padStart(2, '0');
            checkOutInput.value = d.getFullYear() + '-' + pad(d.getMonth() + 1) + '-' + pad(d.getDate());
            if (hint) {
                hint.textContent = off > 0
                    ? '· ' + (WPTM.i18n && WPTM.i18n.returns ? WPTM.i18n.returns : 'Returns') + ' ' +
                      d.toLocaleDateString(undefined, { weekday: 'short', month: 'short', day: 'numeric', year: 'numeric' })
                    : '';
            }
        }

        if (checkInInput) {
            checkInInput.addEventListener('change', syncTripEndDate);
            checkInInput.addEventListener('change', checkAvailability);
            checkInInput.addEventListener('change', updatePriceSummary);
        }
        if (checkOutInput) {
            checkOutInput.addEventListener('change', checkAvailability);
            checkOutInput.addEventListener('change', updatePriceSummary);
        }

        // Hotel room selector — switching room type changes the nightly price.
        const roomSelect = form.querySelector('[name="room_id"]');
        if (roomSelect) {
            roomSelect.addEventListener('change', function() {
                const opt = roomSelect.options[roomSelect.selectedIndex];
                const price = opt ? parseFloat(opt.dataset.price || 0) : 0;
                if (price > 0) form.dataset.basePrice = price;
                updatePriceSummary();
            });
        }

        // Number of nights for a hotel stay (defaults to 1 when dates are missing).
        function getNights() {
            if (itemType !== 'hotel' || !checkInInput || !checkOutInput) return 1;
            if (!checkInInput.value || !checkOutInput.value) return 1;
            const inD = new Date(checkInInput.value + 'T00:00:00');
            const outD = new Date(checkOutInput.value + 'T00:00:00');
            const nights = Math.round((outD - inD) / 86400000);
            return nights > 0 ? nights : 1;
        }

        function checkAvailability() {
            const date = checkInInput ? checkInInput.value : '';
            if (!date) return;
            wptmAjax('wptm_check_availability', {
                item_id: itemId, item_type: itemType, date: date,
                guests: travelersInput ? travelersInput.value : 1
            }, function(r) {
                const el = form.querySelector('.wptm-availability-status');
                if (!el || !r.success) return;
                const d = r.data || {};
                if (d.available) {
                    var spots = d.spots_left;
                    if (d.unlimited || spots === null || spots === undefined) {
                        el.textContent = '✓ Available';
                    } else {
                        spots = parseInt(spots, 10);
                        el.textContent = '✓ Available — ' + spots + (spots === 1 ? ' spot left' : ' spots left');
                    }
                    el.className = 'wptm-availability-status available';
                } else {
                    el.textContent = '✗ Not available for this date';
                    el.className = 'wptm-availability-status unavailable';
                }
            });
        }

        function getSubtotal() {
            // Multi-tier trips: subtotal = Σ(qty × tier price); travelers = Σ qty.
            if (tierInputs.length) {
                let subtotal = 0, count = 0;
                tierInputs.forEach(function(inp) {
                    const qty = Math.max(0, parseInt(inp.value, 10) || 0);
                    subtotal += qty * parseFloat(inp.dataset.price || 0);
                    count += qty;
                });
                const totalCount = form.querySelector('.wptm-tier-total-count');
                if (totalCount) totalCount.value = count;
                return subtotal;
            }
            const basePrice = parseFloat(form.dataset.basePrice || 0);

            // Hotels are priced per room, per night — guests affect capacity, not price.
            if (itemType === 'hotel') {
                return basePrice * getNights();
            }

            const travelers = parseInt(travelersInput ? travelersInput.value : 1) || 1;
            return basePrice * travelers;
        }

        // Recompute the coupon discount against the current subtotal so it stays
        // correct when the traveler count changes (percentage coupons especially).
        function getDiscount(subtotal) {
            const type = form.dataset.couponType;
            const amount = parseFloat(form.dataset.couponAmount || 0);
            if (!type || !amount) return 0;
            let d = type === 'percentage' ? subtotal * (amount / 100) : Math.min(amount, subtotal);
            return Math.round(d * 100) / 100;
        }

        // --- Pickup points (Pro) -------------------------------------------
        const pickupBlock = form.querySelector('.wptm-pickup-block');
        const pickupWrap = pickupBlock ? pickupBlock.querySelector('.wptm-pickups') : null;
        const pickupCurrency = pickupBlock ? (pickupBlock.dataset.currency || '') : '';
        const pickupFreeLabel = pickupBlock ? (pickupBlock.dataset.freeLabel || 'No pickup') : '';
        let pickupData = [];
        if (pickupBlock) { try { pickupData = JSON.parse(pickupBlock.dataset.pickups || '[]'); } catch (e) { pickupData = []; } }

        function pickEsc(s) {
            return String(s).replace(/[&<>"']/g, function(c) {
                return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
            });
        }

        function currentTravelerCount() {
            if (tierInputs.length) {
                let c = 0; tierInputs.forEach(function(i) { c += parseInt(i.value, 10) || 0; });
                return Math.max(0, c);
            }
            return Math.max(1, parseInt(travelersInput ? travelersInput.value : 1, 10) || 1);
        }

        function pickupOptions(selectedVal) {
            let html = '<option value="">' + pickEsc(pickupFreeLabel) + '</option>';
            pickupData.forEach(function(p, idx) {
                const price = parseFloat(p.price) || 0;
                const tag = price > 0 ? ' (+' + pickupCurrency + price.toFixed(2) + ')' : ' (Free)';
                html += '<option value="' + idx + '" data-price="' + price + '"' +
                    (String(selectedVal) === String(idx) ? ' selected' : '') + '>' + pickEsc(p.label) + tag + '</option>';
            });
            return html;
        }

        function syncPickups() {
            if (!pickupWrap) return;
            const n = currentTravelerCount();
            const existing = pickupWrap.querySelectorAll('.wptm-pickup-select');
            if (existing.length === n) return; // no change

            const prev = []; existing.forEach(function(s) { prev.push(s.value); });
            pickupWrap.innerHTML = '';
            for (let i = 0; i < n; i++) {
                const row = document.createElement('div');
                row.className = 'wptm-pickup-item';
                if (n > 1) { const num = document.createElement('span'); num.className = 'wptm-pickup-num'; num.textContent = (i + 1); row.appendChild(num); }
                const select = document.createElement('select');
                select.className = 'wptm-pickup-select';
                select.name = 'pickups[' + i + ']';
                select.innerHTML = pickupOptions(prev[i] != null ? prev[i] : '');
                select.addEventListener('change', updatePriceSummary);
                row.appendChild(select);
                pickupWrap.appendChild(row);
            }
        }

        function getPickupTotal() {
            if (!pickupWrap) return 0;
            let t = 0;
            pickupWrap.querySelectorAll('.wptm-pickup-select').forEach(function(s) {
                const opt = s.options[s.selectedIndex];
                if (opt) t += parseFloat(opt.dataset.price || 0) || 0;
            });
            return Math.round(t * 100) / 100;
        }

        function updatePriceSummary() {
            syncPickups();
            const subtotal = getSubtotal();
            const discount = getDiscount(subtotal);
            const pickup = getPickupTotal();
            const total = Math.max(0, subtotal - discount) + pickup;

            // Keep the dataset discount in sync for booking submission.
            form.dataset.discount = discount;

            const subtotalEl = form.querySelector('.wptm-summary-subtotal');
            const discountEl = form.querySelector('.wptm-summary-discount');
            const totalEl = form.querySelector('.wptm-summary-total');
            const pickupEl = form.querySelector('.wptm-summary-pickup');
            const pickupLine = form.querySelector('.wptm-summary-pickup-line');
            if (subtotalEl) subtotalEl.textContent = wptmFormatPrice(subtotal);
            if (discountEl) discountEl.textContent = '-' + wptmFormatPrice(discount);
            if (pickupEl) pickupEl.textContent = wptmFormatPrice(pickup);
            if (pickupLine) pickupLine.style.display = pickup > 0 ? '' : 'none';
            if (totalEl) totalEl.textContent = wptmFormatPrice(total);
        }

        // Apply coupon
        const couponBtn = form.querySelector('.wptm-apply-coupon');
        if (couponBtn) {
            couponBtn.addEventListener('click', function() {
                const code = form.querySelector('[name="coupon_code"]').value.trim();
                if (!code) return;

                const orig = couponBtn.textContent;
                couponBtn.disabled = true;
                couponBtn.textContent = WPTM.i18n ? WPTM.i18n.loading : 'Applying...';

                wptmAjax('wptm_apply_coupon', { coupon_code: code, amount: getSubtotal() }, function(r) {
                    couponBtn.disabled = false;
                    couponBtn.textContent = orig;
                    if (r.success && parseFloat(r.data.discount) > 0) {
                        form.dataset.couponType = r.data.type || '';
                        form.dataset.couponAmount = r.data.amount || 0;
                        updatePriceSummary();
                        wptmToast('Coupon applied! -' + wptmFormatPrice(r.data.discount));
                    } else {
                        // Clear any previous coupon and surface the reason.
                        delete form.dataset.couponType;
                        delete form.dataset.couponAmount;
                        updatePriceSummary();
                        wptmToast(r.data && r.data.message ? r.data.message : 'Invalid coupon.', 'error');
                    }
                });
            });
        }

        // --- Online gateway wiring ------------------------------------------
        const PAY = window.wptmPay || { stripe: {}, paypal: {}, i18n: {} };
        const PAYI18N = PAY.i18n || {};
        const submitBtn = formEl.querySelector('[type="submit"]');
        let stripe = null, stripeCard = null, stripeReady = false;
        let paypalRendered = false;
        let currentBookingId = 0; // reused across PayPal retries to avoid duplicate rows.

        function selectedMethod() {
            const checked = form.querySelector('[name="payment_method"]:checked');
            return checked ? checked.value : 'manual';
        }

        function setLoading(on) {
            if (!submitBtn) return;
            if (on) {
                submitBtn.dataset.orig = submitBtn.dataset.orig || submitBtn.textContent;
                submitBtn.disabled = true;
                submitBtn.textContent = WPTM.i18n ? WPTM.i18n.loading : (PAYI18N.processing || 'Processing...');
            } else {
                submitBtn.disabled = false;
                if (submitBtn.dataset.orig) submitBtn.textContent = submitBtn.dataset.orig;
            }
        }

        // Shared validation before any booking/charge is attempted.
        function validateForm() {
            if (tierInputs.length) {
                const totalCount = form.querySelector('.wptm-tier-total-count');
                if (!totalCount || (parseInt(totalCount.value, 10) || 0) < 1) {
                    wptmToast('Please add at least one ticket.', 'error');
                    return false;
                }
            }
            if (!formEl.checkValidity()) {
                formEl.reportValidity();
                return false;
            }
            return true;
        }

        function collectBookingData() {
            updatePriceSummary();
            const fd = new FormData(formEl);
            const data = {};
            fd.forEach((v, k) => data[k] = v);
            data.item_id = itemId;
            data.booking_type = itemType;
            data.discount_amount = parseFloat(form.dataset.discount || 0);
            const totalEl = form.querySelector('.wptm-summary-total');
            data.total_price = totalEl ? totalEl.textContent.replace(/[^0-9.]/g, '') : 0;
            return data;
        }

        // Create (or reuse) the booking row. cb(bookingData) on success, cb(null)
        // on failure (after surfacing the message). Pricing is recomputed server-side.
        function createBooking(cb) {
            if (currentBookingId) {
                cb({ booking_id: currentBookingId });
                return;
            }
            wptmAjax('wptm_create_booking', collectBookingData(), function(r) {
                if (!r.success) {
                    wptmToast(r.data && r.data.message ? r.data.message : 'Booking failed.', 'error');
                    cb(null);
                    return;
                }
                currentBookingId = r.data.booking_id;
                cb(r.data);
            });
        }

        // --- Stripe (Elements card field → token → server charge) -----------
        function initStripe() {
            if (!PAY.stripe || !PAY.stripe.enabled || !PAY.stripe.pk) return;
            if (typeof Stripe === 'undefined') return;
            const mount = form.querySelector('.wptm-stripe-card');
            if (!mount) return;
            stripe = Stripe(PAY.stripe.pk);
            stripeCard = stripe.elements().create('card', { hidePostalCode: true });
            stripeCard.mount(mount);
            const errEl = form.querySelector('.wptm-stripe-error');
            stripeCard.on('change', function(ev) {
                if (errEl) errEl.textContent = ev.error ? ev.error.message : '';
            });
            stripeReady = true;
        }

        function stripeFail(msg) {
            setLoading(false);
            currentBookingId = 0; // let the customer retry on a clean row
            const errEl = form.querySelector('.wptm-stripe-error');
            if (errEl) errEl.textContent = msg || '';
            wptmToast(msg || (PAYI18N.payFailed || 'Payment failed.'), 'error');
        }

        function payWithStripe() {
            if (!stripeReady) { wptmToast(PAYI18N.payFailed || 'Stripe is not ready.', 'error'); return; }
            setLoading(true);

            createBooking(function(bd) {
                if (!bd) { setLoading(false); return; }

                // 1) Ask the server for a PaymentIntent client secret.
                wptmAjax('wptm_stripe_create_intent', { booking_id: bd.booking_id }, function(r) {
                    if (!r.success || !r.data || !r.data.client_secret) {
                        stripeFail(r.data && r.data.message ? r.data.message : null);
                        return;
                    }

                    // 2) Confirm the card — Stripe.js performs 3-D Secure / SCA
                    //    challenges inline when the issuer requires them.
                    const nameEl  = form.querySelector('[name="customer_name"]');
                    const emailEl = form.querySelector('[name="customer_email"]');
                    stripe.confirmCardPayment(r.data.client_secret, {
                        payment_method: {
                            card: stripeCard,
                            billing_details: {
                                name:  nameEl ? nameEl.value : undefined,
                                email: emailEl ? emailEl.value : undefined,
                            },
                        },
                    }).then(function(result) {
                        if (result.error) {
                            stripeFail(result.error.message);
                            return;
                        }
                        if (!result.paymentIntent || result.paymentIntent.status !== 'succeeded') {
                            stripeFail(null);
                            return;
                        }

                        // 3) Verify the intent server-side and mark the booking paid.
                        wptmAjax('wptm_stripe_confirm', {
                            booking_id: bd.booking_id,
                            payment_intent_id: result.paymentIntent.id,
                        }, function(c) {
                            if (c.success && c.data && c.data.redirect) {
                                window.location.href = c.data.redirect;
                            } else {
                                stripeFail(c.data && c.data.message ? c.data.message : null);
                            }
                        });
                    });
                });
            });
        }

        // --- PayPal (Smart Buttons → create order → capture) ----------------
        function initPaypal() {
            if (!PAY.paypal || !PAY.paypal.enabled) return;
            if (typeof paypal === 'undefined') return;
            const mount = form.querySelector('.wptm-paypal-buttons');
            if (!mount || paypalRendered) return;
            paypalRendered = true;

            paypal.Buttons({
                createOrder: function() {
                    return new Promise(function(resolve, reject) {
                        if (!validateForm()) { reject(new Error('invalid')); return; }
                        createBooking(function(bd) {
                            if (!bd) { reject(new Error('booking')); return; }
                            wptmAjax('wptm_paypal_create_order', { booking_id: bd.booking_id }, function(r) {
                                if (r.success && r.data && r.data.order_id) {
                                    resolve(r.data.order_id);
                                } else {
                                    wptmToast(r.data && r.data.message ? r.data.message : (PAYI18N.payFailed || 'PayPal error.'), 'error');
                                    reject(new Error('create'));
                                }
                            });
                        });
                    });
                },
                onApprove: function(data) {
                    return new Promise(function(resolve) {
                        wptmAjax('wptm_paypal_capture_order', {
                            booking_id: currentBookingId,
                            order_id: data.orderID,
                        }, function(r) {
                            if (r.success && r.data && r.data.redirect) {
                                window.location.href = r.data.redirect;
                            } else {
                                wptmToast(r.data && r.data.message ? r.data.message : (PAYI18N.payFailed || 'Payment failed.'), 'error');
                            }
                            resolve();
                        });
                    });
                },
                onError: function() {
                    wptmToast(PAYI18N.payFailed || 'PayPal error.', 'error');
                },
            }).render(mount);
        }

        // Reveal the detail area for the active method; PayPal swaps in its own
        // buttons in place of the normal submit button.
        function applyMethodUI() {
            const method = selectedMethod();
            form.querySelectorAll('.wptm-payment-detail').forEach(d => d.style.display = 'none');
            const detail = form.querySelector('.wptm-payment-detail--' + method);
            if (detail) detail.style.display = 'block';
            if (submitBtn) submitBtn.style.display = (method === 'paypal') ? 'none' : '';
        }

        // Submit handles manual + Stripe. PayPal is driven by its own buttons.
        formEl.addEventListener('submit', function(e) {
            e.preventDefault();
            if (!validateForm()) return;
            const method = selectedMethod();

            if (method === 'stripe') { payWithStripe(); return; }

            // Manual / bank transfer — create the booking, then to the order page.
            setLoading(true);
            createBooking(function(bd) {
                if (!bd) { setLoading(false); return; }
                if (bd.message) wptmToast(bd.message);
                if (bd.redirect) { window.location.href = bd.redirect; }
                else { setLoading(false); }
            });
        });

        // Payment method switch — selected-state + detail reveal.
        const paymentMethods = form.querySelectorAll('[name="payment_method"]');
        function syncPaymentSelection() {
            form.querySelectorAll('.wptm-payment-method').forEach(card => {
                const input = card.querySelector('input[type="radio"]');
                card.classList.toggle('is-selected', !!(input && input.checked));
            });
        }
        paymentMethods.forEach(radio => {
            radio.addEventListener('change', function() {
                currentBookingId = 0; // a method change starts a fresh booking attempt
                syncPaymentSelection();
                applyMethodUI();
            });
        });

        initStripe();
        initPaypal();
        syncPaymentSelection();
        applyMethodUI();

        updatePriceSummary();
    }

    /* Cart checkout form (multi-item) */
    function initCheckoutForm() {
        const form = document.getElementById('wptm-checkout-form');
        if (!form) return;

        // Payment card selected-state (mirrors the booking form behaviour).
        function syncPaymentSelection() {
            form.querySelectorAll('.wptm-payment-method').forEach(card => {
                const input = card.querySelector('input[type="radio"]');
                card.classList.toggle('is-selected', !!(input && input.checked));
            });
        }
        form.querySelectorAll('[name="payment_method"]').forEach(radio => {
            radio.addEventListener('change', syncPaymentSelection);
        });
        syncPaymentSelection();

        form.addEventListener('submit', function(e) {
            e.preventDefault();

            const submitBtn = form.querySelector('[type="submit"]');
            const origText = submitBtn.textContent;
            submitBtn.disabled = true;
            submitBtn.textContent = WPTM.i18n ? WPTM.i18n.loading : 'Processing...';

            const fd = new FormData(form);
            const data = {};
            fd.forEach((v, k) => data[k] = v);

            wptmAjax('wptm_checkout', data, function(r) {
                if (r.success) {
                    wptmToast(r.data.message);
                    if (r.data.redirect) {
                        window.location.href = r.data.redirect;
                        return;
                    }
                }
                submitBtn.disabled = false;
                submitBtn.textContent = origText;
                if (!r.success) {
                    wptmToast(r.data && r.data.message ? r.data.message : 'Checkout failed.', 'error');
                }
            });
        });
    }

    document.addEventListener('DOMContentLoaded', function() {
        initBookingForm();
        initCheckoutForm();
    });
})();
