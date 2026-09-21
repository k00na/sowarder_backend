<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $restaurant->name }} — cashier board preview</title>
    <style>
        body {
            font-family: system-ui, -apple-system, sans-serif;
            margin: 0;
            background: #f3f4f6;
            color: #1f2937;
        }

        header {
            background: #111827;
            color: #ffffff;
            padding: 20px;
            text-align: center;
        }

        header h1 {
            margin: 0;
            font-size: 20px;
        }

        header p {
            margin: 4px 0 0;
            opacity: 0.75;
            font-size: 13px;
        }

        .banner {
            background: #fef3c7;
            color: #92400e;
            text-align: center;
            padding: 8px;
            font-size: 13px;
        }

        main {
            max-width: 900px;
            margin: 0 auto;
            padding: 20px 16px 60px;
        }

        .board-toolbar {
            display: flex;
            justify-content: space-between;
            align-items: baseline;
            margin-bottom: 12px;
        }

        .board-toolbar h2 {
            font-size: 16px;
            margin: 0;
        }

        .board-toolbar span {
            font-size: 12px;
            color: #6b7280;
        }

        .board-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(160px, 1fr));
            gap: 12px;
        }

        .board-card {
            border-radius: 10px;
            padding: 12px;
            color: #ffffff;
            min-height: 110px;
            display: flex;
            flex-direction: column;
            gap: 6px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.15);
        }

        .board-card--vacant {
            background: #9ca3af;
        }

        .board-card--occupied_unacknowledged {
            background: #ea580c;
        }

        .board-card--occupied_acknowledged {
            background: #16a34a;
        }

        .board-card__label {
            font-size: 16px;
            font-weight: 700;
        }

        .board-card__state {
            font-size: 10px;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            opacity: 0.9;
        }

        .board-card__total {
            font-size: 14px;
            font-weight: 600;
            margin-top: auto;
        }

        .board-card__preview {
            font-size: 11px;
            opacity: 0.9;
            line-height: 1.3;
        }

        .empty {
            text-align: center;
            color: #6b7280;
            padding: 40px 16px;
        }

        .api-test-launcher {
            position: fixed;
            top: 16px;
            right: 16px;
            z-index: 60;
            background: #111827;
            color: #e5e7eb;
            border: 1px solid #374151;
            border-radius: 999px;
            padding: 8px 16px;
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            cursor: pointer;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.25);
        }

        .api-test-launcher:hover {
            background: #1f2937;
        }

        .api-test-drawer-overlay {
            position: fixed;
            inset: 0;
            background: rgba(0, 0, 0, 0.5);
            z-index: 55;
            opacity: 0;
            pointer-events: none;
            transition: opacity 0.2s ease;
        }

        .api-test-drawer-overlay.is-open {
            opacity: 1;
            pointer-events: auto;
        }

        .api-test-drawer {
            position: fixed;
            top: 0;
            right: 0;
            height: 100vh;
            width: min(460px, 100vw);
            background: #111827;
            color: #e5e7eb;
            z-index: 56;
            box-shadow: -8px 0 24px rgba(0, 0, 0, 0.35);
            transform: translateX(100%);
            transition: transform 0.25s ease;
            display: flex;
            flex-direction: column;
        }

        .api-test-drawer.is-open {
            transform: translateX(0);
        }

        .api-test-panel__inner {
            padding: 16px;
            overflow-y: auto;
            flex: 1;
        }

        .api-test-panel__header {
            display: flex;
            align-items: center;
            gap: 10px;
            flex-wrap: wrap;
            position: sticky;
            top: 0;
            background: #111827;
            padding-bottom: 8px;
        }

        .api-test-panel__header h2 {
            margin: 0;
            font-size: 18px;
            flex: 1;
        }

        .api-test-drawer__close {
            background: transparent;
            border: 1px solid #374151;
            color: #e5e7eb;
            border-radius: 6px;
            width: 28px;
            height: 28px;
            font-size: 16px;
            line-height: 1;
            cursor: pointer;
        }

        .api-test-drawer__close:hover {
            background: #374151;
        }

        .api-test-panel__badge {
            background: #b91c1c;
            color: #ffffff;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            padding: 3px 8px;
            border-radius: 999px;
        }

        .api-test-panel__hint {
            font-size: 13px;
            color: #9ca3af;
            margin: 10px 0 16px;
        }

        .api-test-panel__hint code {
            color: #e5e7eb;
        }

        .api-test-panel__meta {
            display: grid;
            grid-template-columns: max-content 1fr;
            gap: 4px 12px;
            font-size: 12px;
            margin: 0 0 12px;
        }

        .api-test-panel__meta dt {
            color: #9ca3af;
        }

        .api-test-panel__meta dd {
            margin: 0;
            font-family: ui-monospace, SFMono-Regular, Menlo, monospace;
            word-break: break-all;
        }

        .api-test-panel__state {
            background: #1f2937;
            border-radius: 8px;
            padding: 10px 12px;
            font-size: 12px;
            margin-bottom: 16px;
        }

        .api-test-panel__state code {
            color: #93c5fd;
        }

        .api-test-panel__field {
            display: block;
            font-size: 12px;
            color: #9ca3af;
            margin-bottom: 12px;
        }

        .api-test-panel__field input {
            display: block;
            margin-top: 4px;
            width: 100%;
            box-sizing: border-box;
            padding: 6px 8px;
            border-radius: 6px;
            border: 1px solid #374151;
            background: #1f2937;
            color: #e5e7eb;
            font-size: 13px;
        }

        .api-test-panel__credentials {
            margin-bottom: 4px;
        }

        .api-test-panel__actions {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin-bottom: 16px;
        }

        .api-test-panel__actions button {
            background: #1f2937;
            color: #e5e7eb;
            border: 1px solid #374151;
            border-radius: 6px;
            padding: 8px 12px;
            font-size: 12px;
            cursor: pointer;
        }

        .api-test-panel__actions button:hover {
            background: #374151;
        }

        .api-test-panel__actions button:disabled {
            opacity: 0.5;
            cursor: default;
        }

        .api-test-panel__clear {
            margin-left: auto;
            background: transparent !important;
            border-style: dashed !important;
        }

        .api-test-panel__log {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .api-test-panel__entry {
            background: #1f2937;
            border-radius: 8px;
            padding: 10px 12px;
            font-size: 12px;
        }

        .api-test-panel__entry-head {
            display: flex;
            align-items: baseline;
            gap: 8px;
            flex-wrap: wrap;
            margin-bottom: 6px;
        }

        .api-test-panel__entry-meta {
            color: #9ca3af;
            font-family: ui-monospace, SFMono-Regular, Menlo, monospace;
        }

        .api-test-panel__entry-time {
            color: #6b7280;
            margin-left: auto;
        }

        .api-test-panel__status--ok,
        .api-test-panel__status--error {
            font-family: ui-monospace, SFMono-Regular, Menlo, monospace;
            font-weight: 700;
            padding: 1px 6px;
            border-radius: 4px;
        }

        .api-test-panel__status--ok {
            background: #065f46;
            color: #d1fae5;
        }

        .api-test-panel__status--error {
            background: #7f1d1d;
            color: #fee2e2;
        }

        .api-test-panel__entry pre {
            margin: 0;
            white-space: pre-wrap;
            word-break: break-word;
            font-family: ui-monospace, SFMono-Regular, Menlo, monospace;
            font-size: 11.5px;
            color: #e5e7eb;
        }
    </style>
</head>
<body>
    <header>
        <h1>{{ $restaurant->name }}</h1>
        <p>Cashier board preview · {{ $restaurant->currency->value }} · {{ $restaurant->timezone }}</p>
    </header>

    <div class="banner">Owner preview — a live snapshot of what the cashier app's table-grid board shows right now.</div>

    <main>
        <div class="board-toolbar">
            <h2>Current board</h2>
            <span>{{ count($cards) }} table(s)</span>
        </div>

        @if (count($cards) > 0)
            <div class="board-grid">
                @foreach ($cards as $card)
                    <div class="board-card board-card--{{ $card['state'] }}">
                        <span class="board-card__label">{{ $card['table_label'] }}</span>
                        <span class="board-card__state">{{ str_replace('_', ' ', $card['state']) }}</span>
                        @if (! empty($card['item_preview']))
                            <span class="board-card__preview">{{ implode(', ', $card['item_preview']) }}</span>
                        @endif
                        @if ($card['session_total_minor'] !== null)
                            <span class="board-card__total">{{ $restaurant->currency->value }} {{ number_format($card['session_total_minor'] / 100, 2) }}</span>
                        @endif
                    </div>
                @endforeach
            </div>
        @else
            <p class="empty">No tables yet. Add dining tables to see the board.</p>
        @endif
    </main>

    @if ($showApiTestPanel)
        <button type="button" id="cashier-api-drawer-toggle" class="api-test-launcher">
            🧪 API Tests
        </button>

        <div class="api-test-drawer-overlay" id="cashier-api-drawer-overlay"></div>

        <aside class="api-test-drawer" id="cashier-api-drawer" aria-hidden="true">
            <div class="api-test-panel__inner">
                <div class="api-test-panel__header">
                    <h2>Cashier API test panel</h2>
                    <span class="api-test-panel__badge">Test tool — local only</span>
                    <button type="button" id="cashier-api-drawer-close" class="api-test-drawer__close" aria-label="Close">&times;</button>
                </div>

                <p class="api-test-panel__hint">
                    Exercises the real cashier-facing JSON API with plain <code>fetch()</code>
                    calls. Login uses the actual <code>cashier_password</code> you set for this
                    restaurant in Settings — it is never stored server-side in plain text, so
                    type it below each time. Accept/End actions mutate real
                    <code>Order</code> / <code>TableSession</code> rows.
                </p>

                <dl class="api-test-panel__meta">
                    <div><dt>Restaurant ID</dt><dd id="cashier-api-restaurant-id">{{ $restaurant->id }}</dd></div>
                    <div><dt>Base path</dt><dd id="cashier-api-base-path"></dd></div>
                </dl>

                <label class="api-test-panel__field api-test-panel__credentials">
                    Cashier email
                    <input type="email" id="cashier-api-email" value="{{ $restaurant->owner?->email }}">
                </label>
                <label class="api-test-panel__field api-test-panel__credentials">
                    Cashier password (set in Settings — not prefillable)
                    <input type="password" id="cashier-api-password" placeholder="Type the cashier password">
                </label>

                <div class="api-test-panel__state" id="cashier-api-state">Not logged in yet.</div>

                <label class="api-test-panel__field">
                    Manual session id (optional — falls back to the first occupied
                    table from the last board fetch when blank)
                    <input type="text" id="cashier-api-session-id" placeholder="auto">
                </label>

                <div class="api-test-panel__actions">
                    <button type="button" data-action="login">1. Login</button>
                    <button type="button" data-action="me">2. Me</button>
                    <button type="button" data-action="board-current-all">3. Board (current, all)</button>
                    <button type="button" data-action="board-current-occupied">4. Board (current, occupied)</button>
                    <button type="button" data-action="board-today-all">5. Board (today, all)</button>
                    <button type="button" data-action="session-detail">6. Session detail</button>
                    <button type="button" data-action="accept-order">7. Accept order</button>
                    <button type="button" data-action="end-without-confirm">8. End session (no confirm)</button>
                    <button type="button" data-action="end-with-confirm">9. End session (confirm)</button>
                    <button type="button" data-action="broadcasting-auth">10. Broadcasting auth</button>
                    <button type="button" data-action="logout">11. Logout</button>
                    <button type="button" data-action="me-after-logout">12. Me after logout (expect 401)</button>
                    <button type="button" data-action="clear-log" class="api-test-panel__clear">Clear log</button>
                </div>

                <div class="api-test-panel__log" id="cashier-api-log"></div>
            </div>
        </aside>

        <script>
            (function () {
                var RESTAURANT_ID = @json((string) $restaurant->id);
                var BASE_PATH = '/api/v1/cashier';
                var AUTH_LOGIN_URL = BASE_PATH + '/auth/login';
                var AUTH_LOGOUT_URL = BASE_PATH + '/auth/logout';
                var AUTH_ME_URL = BASE_PATH + '/auth/me';
                var BOARD_URL = BASE_PATH + '/board';
                var BROADCASTING_AUTH_URL = BASE_PATH + '/broadcasting/auth';

                var state = {
                    token: null,
                    lastBoard: null,
                };

                var logEl = document.getElementById('cashier-api-log');
                var stateEl = document.getElementById('cashier-api-state');
                document.getElementById('cashier-api-base-path').textContent = BASE_PATH;

                function escapeHtml(value) {
                    return String(value)
                        .replace(/&/g, '&amp;')
                        .replace(/</g, '&lt;')
                        .replace(/>/g, '&gt;');
                }

                function updateStateView() {
                    if (!state.token) {
                        stateEl.textContent = 'Not logged in yet.';
                        return;
                    }

                    stateEl.innerHTML = '<strong>Logged in</strong> — token: <code>'
                        + escapeHtml(state.token.slice(0, 12)) + '…</code>';
                }

                function logResult(label, method, url, status, retryAfter, jsonBody) {
                    var entry = document.createElement('div');
                    entry.className = 'api-test-panel__entry';

                    var statusClass = status !== null && status < 400
                        ? 'api-test-panel__status--ok'
                        : 'api-test-panel__status--error';
                    var statusText = status === null ? 'ERROR' : status;
                    var retrySuffix = retryAfter ? ' · Retry-After: ' + escapeHtml(retryAfter) + 's' : '';

                    entry.innerHTML = '<div class="api-test-panel__entry-head">'
                        + '<span class="' + statusClass + '">' + statusText + '</span>'
                        + '<strong>' + escapeHtml(label) + '</strong>'
                        + '<span class="api-test-panel__entry-meta">' + method + ' ' + escapeHtml(url) + retrySuffix + '</span>'
                        + '<span class="api-test-panel__entry-time">' + new Date().toLocaleTimeString() + '</span>'
                        + '</div>'
                        + '<pre>' + escapeHtml(JSON.stringify(jsonBody, null, 2)) + '</pre>';

                    logEl.insertBefore(entry, logEl.firstChild);
                }

                async function apiFetch(method, url, body, useAuth) {
                    try {
                        var headers = { Accept: 'application/json' };

                        if (body !== undefined) {
                            headers['Content-Type'] = 'application/json';
                        }

                        if (useAuth && state.token) {
                            headers.Authorization = 'Bearer ' + state.token;
                        }

                        var res = await fetch(url, {
                            method: method,
                            headers: headers,
                            body: body !== undefined ? JSON.stringify(body) : undefined,
                        });
                        var text = await res.text();
                        var json = null;

                        if (text) {
                            try {
                                json = JSON.parse(text);
                            } catch (parseError) {
                                json = { raw: text };
                            }
                        }

                        return { status: res.status, retryAfter: res.headers.get('Retry-After'), json: json };
                    } catch (networkError) {
                        return { status: null, retryAfter: null, json: { error: String(networkError) } };
                    }
                }

                function requireToken(label, url) {
                    if (!state.token) {
                        logResult(label, 'GET', url, null, null, {
                            error: 'Not logged in — run action 1 (Login) first.',
                        });
                        return false;
                    }

                    return true;
                }

                function pickSessionId() {
                    var manual = document.getElementById('cashier-api-session-id').value.trim();
                    if (manual) {
                        return manual;
                    }

                    if (state.lastBoard && Array.isArray(state.lastBoard.cards)) {
                        for (var i = 0; i < state.lastBoard.cards.length; i++) {
                            if (state.lastBoard.cards[i].session_id) {
                                return state.lastBoard.cards[i].session_id;
                            }
                        }
                    }

                    return null;
                }

                function noSessionAvailable(label, method, url) {
                    logResult(label, method, url, null, null, {
                        error: 'No session id available — fetch a board with an occupied table first, or enter one manually.',
                    });
                }

                async function login() {
                    var body = {
                        email: document.getElementById('cashier-api-email').value.trim(),
                        cashier_password: document.getElementById('cashier-api-password').value,
                    };

                    var result = await apiFetch('POST', AUTH_LOGIN_URL, body, false);

                    if (result.status === 200 && result.json && result.json.token) {
                        state.token = result.json.token;
                        updateStateView();
                    }

                    logResult('1. Login', 'POST', AUTH_LOGIN_URL, result.status, result.retryAfter, result.json);
                }

                async function me() {
                    if (!requireToken('2. Me', AUTH_ME_URL)) {
                        return;
                    }

                    var result = await apiFetch('GET', AUTH_ME_URL, undefined, true);
                    logResult('2. Me', 'GET', AUTH_ME_URL, result.status, result.retryAfter, result.json);
                }

                async function fetchBoard(label, scope, occupancy) {
                    var url = BOARD_URL + '?scope=' + scope + '&occupancy=' + occupancy;

                    if (!requireToken(label, url)) {
                        return;
                    }

                    var result = await apiFetch('GET', url, undefined, true);

                    if (result.status === 200 && result.json) {
                        state.lastBoard = result.json;
                    }

                    logResult(label, 'GET', url, result.status, result.retryAfter, result.json);
                }

                async function sessionDetail() {
                    var sessionId = pickSessionId();
                    var url = BASE_PATH + '/sessions/' + (sessionId || '');

                    if (!requireToken('6. Session detail', url)) {
                        return;
                    }

                    if (!sessionId) {
                        noSessionAvailable('6. Session detail', 'GET', url);
                        return;
                    }

                    var result = await apiFetch('GET', url, undefined, true);
                    logResult('6. Session detail', 'GET', url, result.status, result.retryAfter, result.json);
                }

                async function acceptOrder() {
                    var sessionId = pickSessionId();
                    var url = BASE_PATH + '/sessions/' + (sessionId || '') + '/accept';

                    if (!requireToken('7. Accept order', url)) {
                        return;
                    }

                    if (!sessionId) {
                        noSessionAvailable('7. Accept order', 'POST', url);
                        return;
                    }

                    var result = await apiFetch('POST', url, {}, true);
                    logResult('7. Accept order', 'POST', url, result.status, result.retryAfter, result.json);
                }

                async function endSession(label, confirm) {
                    var sessionId = pickSessionId();
                    var url = BASE_PATH + '/sessions/' + (sessionId || '') + '/end';

                    if (!requireToken(label, url)) {
                        return;
                    }

                    if (!sessionId) {
                        noSessionAvailable(label, 'POST', url);
                        return;
                    }

                    var body = confirm ? { confirm_unacknowledged: true } : {};
                    var result = await apiFetch('POST', url, body, true);
                    logResult(label, 'POST', url, result.status, result.retryAfter, result.json);
                }

                async function broadcastingAuth() {
                    if (!requireToken('10. Broadcasting auth', BROADCASTING_AUTH_URL)) {
                        return;
                    }

                    var body = {
                        channel_name: 'private-restaurant.' + RESTAURANT_ID,
                        socket_id: '1234.5678',
                    };

                    var result = await apiFetch('POST', BROADCASTING_AUTH_URL, body, true);
                    logResult('10. Broadcasting auth', 'POST', BROADCASTING_AUTH_URL, result.status, result.retryAfter, result.json);
                }

                async function logout() {
                    if (!requireToken('11. Logout', AUTH_LOGOUT_URL)) {
                        return;
                    }

                    var result = await apiFetch('POST', AUTH_LOGOUT_URL, undefined, true);

                    if (result.status === 204 || result.status === 200) {
                        state.token = null;
                        updateStateView();
                    }

                    logResult('11. Logout', 'POST', AUTH_LOGOUT_URL, result.status, result.retryAfter, result.json || { note: 'No content.' });
                }

                async function meAfterLogout() {
                    var result = await apiFetch('GET', AUTH_ME_URL, undefined, true);
                    logResult('12. Me after logout (expect 401)', 'GET', AUTH_ME_URL, result.status, result.retryAfter, result.json);
                }

                function clearLog() {
                    logEl.innerHTML = '';
                }

                var actions = {
                    login: login,
                    me: me,
                    'board-current-all': function () { return fetchBoard('3. Board (current, all)', 'current', 'all'); },
                    'board-current-occupied': function () { return fetchBoard('4. Board (current, occupied)', 'current', 'occupied'); },
                    'board-today-all': function () { return fetchBoard('5. Board (today, all)', 'today', 'all'); },
                    'session-detail': sessionDetail,
                    'accept-order': acceptOrder,
                    'end-without-confirm': function () { return endSession('8. End session (no confirm)', false); },
                    'end-with-confirm': function () { return endSession('9. End session (confirm)', true); },
                    'broadcasting-auth': broadcastingAuth,
                    logout: logout,
                    'me-after-logout': meAfterLogout,
                    'clear-log': clearLog,
                };

                document.querySelectorAll('#cashier-api-drawer [data-action]').forEach(function (button) {
                    button.addEventListener('click', function () {
                        var action = actions[button.dataset.action];
                        if (!action) {
                            return;
                        }

                        button.disabled = true;
                        Promise.resolve(action()).finally(function () {
                            button.disabled = false;
                        });
                    });
                });

                var drawer = document.getElementById('cashier-api-drawer');
                var overlay = document.getElementById('cashier-api-drawer-overlay');
                var toggleButton = document.getElementById('cashier-api-drawer-toggle');
                var closeButton = document.getElementById('cashier-api-drawer-close');

                function openDrawer() {
                    drawer.classList.add('is-open');
                    overlay.classList.add('is-open');
                    drawer.setAttribute('aria-hidden', 'false');
                }

                function closeDrawer() {
                    drawer.classList.remove('is-open');
                    overlay.classList.remove('is-open');
                    drawer.setAttribute('aria-hidden', 'true');
                }

                toggleButton.addEventListener('click', openDrawer);
                closeButton.addEventListener('click', closeDrawer);
                overlay.addEventListener('click', closeDrawer);
                document.addEventListener('keydown', function (event) {
                    if (event.key === 'Escape') {
                        closeDrawer();
                    }
                });
            })();
        </script>
    @endif
</body>
</html>
