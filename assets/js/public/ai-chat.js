/**
 * WP Travel Machine — AI Chat Widget
 */
(function() {
    'use strict';
    const WPTM = window.wptmData || {};

    function initAIChat() {
        const widget = document.querySelector('.wptm-ai-chat');
        if (!widget) return;

        const toggle = widget.querySelector('.wptm-ai-chat__toggle');
        const win = widget.querySelector('.wptm-ai-chat__window');
        const closeBtn = widget.querySelector('.wptm-ai-chat__close');
        const input = widget.querySelector('.wptm-ai-chat__input input');
        const sendBtn = widget.querySelector('.wptm-ai-chat__input button');
        const messages = widget.querySelector('.wptm-ai-chat__messages');

        if (!toggle || !win) return;

        toggle.addEventListener('click', function() {
            win.classList.toggle('open');
            if (win.classList.contains('open') && !messages.children.length) {
                addMessage('bot', 'Hi! 👋 I\'m your AI travel assistant. How can I help you find the perfect trip?');
            }
            if (win.classList.contains('open') && input) input.focus();
        });

        if (closeBtn) closeBtn.addEventListener('click', () => win.classList.remove('open'));

        function addMessage(type, text) {
            const msg = document.createElement('div');
            msg.className = 'wptm-ai-chat__msg wptm-ai-chat__msg--' + type;
            msg.textContent = text;
            messages.appendChild(msg);
            messages.scrollTop = messages.scrollHeight;
        }

        function sendMessage() {
            const text = input.value.trim();
            if (!text) return;
            addMessage('user', text);
            input.value = '';
            sendBtn.disabled = true;

            const typing = document.createElement('div');
            typing.className = 'wptm-ai-chat__msg wptm-ai-chat__msg--bot';
            typing.innerHTML = '<span class="wptm-spinner"></span>';
            messages.appendChild(typing);
            messages.scrollTop = messages.scrollHeight;

            wptmAjax('wptm_ai_chat', { message: text, nonce: WPTM.aiNonce }, function(r) {
                typing.remove();
                sendBtn.disabled = false;
                if (r.success && r.data.reply) {
                    addMessage('bot', r.data.reply);
                } else {
                    // Surface the server's reason (bad key, rate limit, etc.) when present.
                    addMessage('bot', (r.data && r.data.message) ? r.data.message : 'Sorry, I couldn\'t process that. Please try again.');
                }
            });
        }

        if (sendBtn) sendBtn.addEventListener('click', sendMessage);
        if (input) input.addEventListener('keypress', function(e) { if (e.key === 'Enter') sendMessage(); });
    }

    /* AI Trip Recommender — [wptm_ai_recommend] shortcode */
    function initAIRecommend() {
        var box = document.querySelector('.wptm-ai-recommend');
        if (!box) return;
        var form = box.querySelector('.wptm-ai-recommend__form');
        var results = box.querySelector('.wptm-ai-recommend__results');
        var statusEl = box.querySelector('.wptm-ai-recommend__status');
        if (!form) return;

        function setStatus(msg, isError) {
            if (!statusEl) return;
            statusEl.textContent = msg || '';
            statusEl.style.display = msg ? '' : 'none';
            statusEl.classList.toggle('is-error', !!isError);
        }

        // Pull the first JSON array out of the reply (tolerates ``` fences / prose).
        function parseRecs(text) {
            if (!text) return null;
            var start = text.indexOf('['), end = text.lastIndexOf(']');
            if (start === -1 || end === -1 || end <= start) return null;
            try { var arr = JSON.parse(text.slice(start, end + 1)); return Array.isArray(arr) ? arr : null; }
            catch (e) { return null; }
        }

        // Flatten any value (string / array / object) into readable text.
        function toText(val) {
            if (val == null) return '';
            if (typeof val === 'string') return val.trim();
            if (typeof val === 'number' || typeof val === 'boolean') return String(val);
            if (Array.isArray(val)) return val.map(toText).filter(function(s) { return s !== ''; }).join(', ');
            if (typeof val === 'object') {
                if (val.name || val.title || val.label) return toText(val.name || val.title || val.label);
                return Object.keys(val).map(function(k) { return toText(val[k]); }).filter(function(s) { return s !== ''; }).join(', ');
            }
            return '';
        }

        function escapeHtml(s) {
            var d = document.createElement('div');
            d.textContent = toText(s);
            return d.innerHTML;
        }

        function render(recs) {
            results.innerHTML = recs.map(function(r) {
                var score = parseInt(r.match_score, 10);
                var badge = isNaN(score) ? '' : '<span class="wptm-ai-rec-card__score">' + score + '% match</span>';
                return '<div class="wptm-ai-rec-card">' +
                    '<div class="wptm-ai-rec-card__top"><strong>' + escapeHtml(r.title || r.name) + '</strong>' + badge + '</div>' +
                    '<p>' + escapeHtml(r.reason || r.why) + '</p>' +
                    '</div>';
            }).join('');
        }

        form.addEventListener('submit', function(e) {
            e.preventDefault();
            var prefs = (form.querySelector('[name="preferences"]') || {}).value || '';
            var budget = (form.querySelector('[name="budget"]') || {}).value || '';
            prefs = prefs.trim();
            if (!prefs) { setStatus('Tell us what you\'re looking for first.', true); return; }

            var btn = form.querySelector('[type="submit"]');
            var orig = btn ? btn.textContent : '';
            if (btn) { btn.disabled = true; btn.textContent = 'Finding trips…'; }
            setStatus('');
            results.innerHTML = '';

            wptmAjax('wptm_ai_recommend', { preferences: prefs, budget: budget, nonce: WPTM.aiNonce }, function(r) {
                if (btn) { btn.disabled = false; btn.textContent = orig; }
                if (!r.success) {
                    setStatus((r.data && r.data.message) ? r.data.message : 'Sorry, recommendations are unavailable right now.', true);
                    return;
                }
                var recs = parseRecs(r.data && r.data.recommendations);
                if (!recs || !recs.length) {
                    setStatus('No recommendations found. Try describing your trip differently.', true);
                    return;
                }
                render(recs);
            });
        });
    }

    document.addEventListener('DOMContentLoaded', function() {
        initAIChat();
        initAIRecommend();
    });
})();
