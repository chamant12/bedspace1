{{-- Virtual receptionist chat widget (public pages). Plain JS/CSS, no dependencies. --}}
<div id="bs-chat" data-csrf="{{ csrf_token() }}">
    <button type="button" id="bs-chat-toggle" aria-label="Chat with our receptionist">
        <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
    </button>
    <div id="bs-chat-panel" role="dialog" aria-label="Bedspace receptionist" hidden>
        <div id="bs-chat-header">
            <div>
                <strong>Bedspace receptionist</strong>
                <div id="bs-chat-sub">Ask about stays, prices or your booking</div>
            </div>
            <div>
                <button type="button" id="bs-chat-reset" title="New conversation" aria-label="New conversation">&#8635;</button>
                <button type="button" id="bs-chat-close" aria-label="Close">&times;</button>
            </div>
        </div>
        <div id="bs-chat-log" aria-live="polite"></div>
        <form id="bs-chat-form">
            <input type="text" id="bs-chat-input" maxlength="1000" placeholder="Type your question..." autocomplete="off" aria-label="Your message">
            <button type="submit" id="bs-chat-send">Send</button>
        </form>
        <div id="bs-chat-note">I can share information and links; bookings are made on the website.</div>
    </div>
</div>
<style>
#bs-chat { position: fixed; right: 20px; bottom: 20px; z-index: 10000; font-family: inherit; font-size: 14px; }
#bs-chat-toggle { width: 58px; height: 58px; border-radius: 50%; border: 0; background: #0d6efd; color: #fff; cursor: pointer; box-shadow: 0 4px 14px rgba(0,0,0,.25); display: flex; align-items: center; justify-content: center; }
#bs-chat-panel { position: absolute; right: 0; bottom: 70px; width: 360px; max-width: calc(100vw - 40px); height: 520px; max-height: calc(100vh - 110px); background: #fff; border-radius: 12px; box-shadow: 0 8px 30px rgba(0,0,0,.25); display: flex; flex-direction: column; overflow: hidden; }
#bs-chat-panel[hidden] { display: none; }
#bs-chat-header { background: #0d6efd; color: #fff; padding: 10px 12px; display: flex; justify-content: space-between; align-items: center; }
#bs-chat-header button { background: transparent; border: 0; color: #fff; font-size: 20px; cursor: pointer; margin-left: 4px; }
#bs-chat-sub { font-size: 12px; opacity: .85; }
#bs-chat-log { flex: 1; overflow-y: auto; padding: 12px; background: #f6f7f9; }
.bs-msg { max-width: 85%; margin-bottom: 8px; padding: 8px 11px; border-radius: 12px; line-height: 1.4; white-space: pre-wrap; word-wrap: break-word; }
.bs-msg a { color: inherit; text-decoration: underline; }
.bs-msg-user { margin-left: auto; background: #0d6efd; color: #fff; border-bottom-right-radius: 3px; }
.bs-msg-bot { background: #fff; color: #222; border: 1px solid #e3e5e8; border-bottom-left-radius: 3px; }
.bs-typing { color: #888; font-style: italic; }
#bs-chat-form { display: flex; border-top: 1px solid #e3e5e8; }
#bs-chat-input { flex: 1; border: 0; padding: 12px; font-size: 14px; outline: none; }
#bs-chat-send { border: 0; background: #0d6efd; color: #fff; padding: 0 16px; cursor: pointer; }
#bs-chat-send:disabled { opacity: .6; cursor: default; }
#bs-chat-note { font-size: 11px; color: #888; padding: 4px 12px 8px; }
@media (max-width: 480px) { #bs-chat-panel { width: calc(100vw - 24px); right: -8px; height: calc(100vh - 100px); } }
</style>
<script>
(function () {
    var root = document.getElementById('bs-chat');
    if (!root || root.dataset.ready) return;
    root.dataset.ready = '1';

    var csrf = root.dataset.csrf;
    var panel = document.getElementById('bs-chat-panel');
    var log = document.getElementById('bs-chat-log');
    var form = document.getElementById('bs-chat-form');
    var input = document.getElementById('bs-chat-input');
    var send = document.getElementById('bs-chat-send');
    var loaded = false;

    // Escape the text, then turn http(s) links into clickable links
    function render(text) {
        var div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML.replace(/(https?:\/\/[^\s<)]+[^\s<).,!?;:'"])/g, function (url) {
            return '<a href="' + url + '" target="_blank" rel="noopener">' + url + '</a>';
        });
    }

    function add(role, text, extraClass) {
        var el = document.createElement('div');
        el.className = 'bs-msg ' + (role === 'user' ? 'bs-msg-user' : 'bs-msg-bot') + (extraClass ? ' ' + extraClass : '');
        el.innerHTML = render(text);
        log.appendChild(el);
        log.scrollTop = log.scrollHeight;
        return el;
    }

    function request(method, url, body) {
        return fetch(url, {
            method: method,
            credentials: 'same-origin',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf },
            body: body ? JSON.stringify(body) : undefined
        }).then(function (r) {
            if (r.status === 429) throw new Error('Too many messages - please wait a moment.');
            if (!r.ok) throw new Error('Something went wrong. Please try again.');
            return r.json();
        });
    }

    function load() {
        if (loaded) return;
        loaded = true;
        request('GET', '/chat/status').then(function (data) {
            (data.history || []).forEach(function (m) { add(m.role, m.content); });
            if (!data.history || !data.history.length) {
                add('bot', 'Hello! Welcome to Bedspace. How can I help you with your stay in Sri Lanka?');
            }
        }).catch(function () {
            add('bot', 'Hello! Welcome to Bedspace. How can I help you with your stay in Sri Lanka?');
        });
    }

    document.getElementById('bs-chat-toggle').addEventListener('click', function () {
        panel.hidden = !panel.hidden;
        if (!panel.hidden) { load(); input.focus(); }
    });
    document.getElementById('bs-chat-close').addEventListener('click', function () { panel.hidden = true; });
    document.getElementById('bs-chat-reset').addEventListener('click', function () {
        request('POST', '/chat/reset').finally(function () {
            log.innerHTML = '';
            add('bot', 'Hello! How can I help you with your stay in Sri Lanka?');
        });
    });

    form.addEventListener('submit', function (e) {
        e.preventDefault();
        var text = input.value.trim();
        if (!text || send.disabled) return;
        input.value = '';
        add('user', text);
        var typing = add('bot', 'Typing...', 'bs-typing');
        send.disabled = true;
        request('POST', '/chat/message', { message: text }).then(function (data) {
            typing.remove();
            add('bot', data.reply);
        }).catch(function (err) {
            typing.remove();
            add('bot', err.message);
        }).finally(function () {
            send.disabled = false;
            input.focus();
        });
    });
})();
</script>
