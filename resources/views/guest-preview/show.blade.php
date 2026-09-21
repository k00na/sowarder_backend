<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $restaurant->name }} — menu preview</title>
    <style>
        :root {
            --primary: {{ $restaurant->primary_color }};
            --secondary: {{ $restaurant->secondary_color }};
        }

        body {
            font-family: system-ui, -apple-system, sans-serif;
            margin: 0;
            background: #f7f7f7;
            color: #1f2937;
        }

        header {
            background: var(--primary);
            color: #ffffff;
            padding: 24px 20px;
            text-align: center;
        }

        header img {
            max-height: 64px;
            margin-bottom: 12px;
            border-radius: 8px;
        }

        header h1 {
            margin: 0;
            font-size: 22px;
        }

        header p {
            margin: 4px 0 0;
            opacity: 0.9;
            font-size: 14px;
        }

        .banner {
            background: #fef3c7;
            color: #92400e;
            text-align: center;
            padding: 8px;
            font-size: 13px;
        }

        main {
            max-width: 640px;
            margin: 0 auto;
            padding: 16px;
        }

        .category {
            margin-bottom: 28px;
        }

        .category h2 {
            font-size: 16px;
            color: var(--secondary);
            border-bottom: 2px solid var(--secondary);
            padding-bottom: 6px;
        }

        .product {
            display: flex;
            gap: 12px;
            align-items: flex-start;
            padding: 12px 0;
            border-bottom: 1px solid #e5e7eb;
        }

        .product img {
            width: 64px;
            height: 64px;
            object-fit: cover;
            border-radius: 8px;
            background: #e5e7eb;
            flex-shrink: 0;
        }

        .product .details {
            flex: 1;
        }

        .product .name {
            font-weight: 600;
        }

        .product .description {
            font-size: 13px;
            color: #6b7280;
            margin-top: 2px;
        }

        .product .price {
            font-weight: 600;
            color: var(--primary);
            white-space: nowrap;
        }

        .empty {
            text-align: center;
            color: #6b7280;
            padding: 40px 16px;
        }
    </style>
</head>
<body>
    <header>
        @if ($restaurant->logo_path)
            <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($restaurant->logo_path) }}" alt="{{ $restaurant->name }} logo">
        @endif
        <h1>{{ $restaurant->name }}</h1>
        <p>{{ $diningTable->label }}</p>
    </header>

    <div class="banner">Owner preview — this is what guests will see for this table's QR code.</div>

    <main>
        @forelse ($categories as $category)
            <section class="category">
                <h2>{{ $category->name }}</h2>

                @foreach ($category->products as $product)
                    <div class="product">
                        @if ($product->image_path)
                            <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($product->image_path) }}" alt="{{ $product->name }}">
                        @else
                            <div class="product__image-placeholder" style="width:64px;height:64px;border-radius:8px;background:#e5e7eb;flex-shrink:0;"></div>
                        @endif

                        <div class="details">
                            <div class="name">{{ $product->name }}</div>
                            @if ($product->description)
                                <div class="description">{{ $product->description }}</div>
                            @endif
                        </div>

                        <div class="price">{{ $restaurant->currency->value }} {{ number_format($product->price_minor / 100, 2) }}</div>
                    </div>
                @endforeach
            </section>
        @empty
            <p class="empty">No available items to show yet. Add categories and products, and mark them available.</p>
        @endforelse
    </main>

    @if ($showApiTestPanel)
        <button type="button" id="guest-api-drawer-toggle" class="api-test-launcher">
            🧪 API Tests
        </button>

        <div class="api-test-drawer-overlay" id="guest-api-drawer-overlay"></div>

        <aside class="api-test-drawer" id="guest-api-drawer" aria-hidden="true">
            <style>
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
                    width: min(440px, 100vw);
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
                    margin-bottom: 16px;
                }

                .api-test-panel__field input {
                    display: block;
                    margin-top: 4px;
                    width: 160px;
                    padding: 6px 8px;
                    border-radius: 6px;
                    border: 1px solid #374151;
                    background: #1f2937;
                    color: #e5e7eb;
                    font-size: 13px;
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

            <div class="api-test-panel__inner">
                <div class="api-test-panel__header">
                    <h2>Guest API test panel</h2>
                    <span class="api-test-panel__badge">Test tool — local only</span>
                    <button type="button" id="guest-api-drawer-close" class="api-test-drawer__close" aria-label="Close">&times;</button>
                </div>

                <p class="api-test-panel__hint">
                    Exercises the real guest-facing JSON API for this table with plain
                    <code>fetch()</code> calls. This creates real <code>Order</code> /
                    <code>TableSession</code> rows and can trip the same rate limiters a
                    real guest device would hit — only ever shown outside production.
                </p>

                <dl class="api-test-panel__meta">
                    <div><dt>Table token</dt><dd id="guest-api-token">{{ $diningTable->token }}</dd></div>
                    <div><dt>Base path</dt><dd id="guest-api-base-path"></dd></div>
                </dl>

                <div class="api-test-panel__state" id="guest-api-state">No test order submitted yet.</div>

                <label class="api-test-panel__field">
                    Manual product id (optional — falls back to the first available
                    product from the loaded menu when blank)
                    <input type="number" id="guest-api-product-id" min="1" placeholder="auto">
                </label>

                <div class="api-test-panel__actions">
                    <button type="button" data-action="load-menu">1. Load menu (API)</button>
                    <button type="button" data-action="submit-order">2. Submit test order</button>
                    <button type="button" data-action="replay-order">3. Replay last order</button>
                    <button type="button" data-action="conflicting-key">4. Conflicting idempotency key</button>
                    <button type="button" data-action="join-session">5. Join existing session</button>
                    <button type="button" data-action="stale-session">6. Stale session guard</button>
                    <button type="button" data-action="invalid-product">7. Invalid product</button>
                    <button type="button" data-action="duplicate-lines">8. Duplicate product lines</button>
                    <button type="button" data-action="hammer-rate-limit">9. Hammer rate limit (12x)</button>
                    <button type="button" data-action="clear-log" class="api-test-panel__clear">Clear log</button>
                </div>

                <div class="api-test-panel__log" id="guest-api-log"></div>
            </div>
        </aside>

        <script>
            (function () {
                var TOKEN = @json($diningTable->token);
                var BASE_PATH = '/api/v1/guest/tables/' + TOKEN;
                var MENU_URL = BASE_PATH + '/menu';
                var ORDERS_URL = BASE_PATH + '/orders';

                var state = {
                    menu: null,
                    lastOrder: null,
                };

                var logEl = document.getElementById('guest-api-log');
                var stateEl = document.getElementById('guest-api-state');
                document.getElementById('guest-api-base-path').textContent = BASE_PATH;

                function escapeHtml(value) {
                    return String(value)
                        .replace(/&/g, '&amp;')
                        .replace(/</g, '&lt;')
                        .replace(/>/g, '&gt;');
                }

                function updateStateView() {
                    if (!state.lastOrder) {
                        stateEl.textContent = 'No test order submitted yet.';
                        return;
                    }

                    stateEl.innerHTML = '<strong>Last order</strong> — order_reference: <code>'
                        + escapeHtml(state.lastOrder.orderReference) + '</code>, session_id: <code>'
                        + escapeHtml(state.lastOrder.sessionId) + '</code>, idempotency_key: <code>'
                        + escapeHtml(state.lastOrder.idempotencyKey) + '</code>';
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

                async function apiFetch(method, url, body) {
                    try {
                        var res = await fetch(url, {
                            method: method,
                            headers: body !== undefined
                                ? { 'Content-Type': 'application/json', Accept: 'application/json' }
                                : { Accept: 'application/json' },
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

                function pickProductId() {
                    var manual = document.getElementById('guest-api-product-id').value.trim();
                    if (manual) {
                        return parseInt(manual, 10);
                    }

                    if (state.menu && Array.isArray(state.menu.categories)) {
                        for (var i = 0; i < state.menu.categories.length; i++) {
                            var products = state.menu.categories[i].products || [];
                            for (var j = 0; j < products.length; j++) {
                                if (products[j].available) {
                                    return parseInt(products[j].id, 10);
                                }
                            }
                        }
                    }

                    return null;
                }

                function newOrderBody(productId, overrides) {
                    return Object.assign({
                        idempotency_key: crypto.randomUUID(),
                        expected_session_id: null,
                        special_instructions: null,
                        lines: [{ product_id: productId, quantity: 1 }],
                    }, overrides || {});
                }

                function noProductAvailable(label) {
                    logResult(label, 'POST', ORDERS_URL, null, null, {
                        error: 'No product id available — load the menu first or enter one manually.',
                    });
                }

                async function loadMenu() {
                    var result = await apiFetch('GET', MENU_URL);
                    if (result.status === 200) {
                        state.menu = result.json;
                    }
                    logResult('1. Load menu (API)', 'GET', MENU_URL, result.status, result.retryAfter, result.json);
                }

                async function submitTestOrder() {
                    var productId = pickProductId();
                    if (productId === null) {
                        noProductAvailable('2. Submit test order');
                        return;
                    }

                    var body = newOrderBody(productId);
                    var result = await apiFetch('POST', ORDERS_URL, body);

                    if (result.status === 201 && result.json) {
                        state.lastOrder = {
                            idempotencyKey: body.idempotency_key,
                            body: body,
                            sessionId: result.json.session_id,
                            orderReference: result.json.order_reference,
                        };
                        updateStateView();
                    }

                    logResult('2. Submit test order', 'POST', ORDERS_URL, result.status, result.retryAfter, result.json);
                }

                async function replayLastOrder() {
                    if (!state.lastOrder) {
                        logResult('3. Replay last order', 'POST', ORDERS_URL, null, null, {
                            error: 'Submit a test order first (action 2).',
                        });
                        return;
                    }

                    var result = await apiFetch('POST', ORDERS_URL, state.lastOrder.body);
                    logResult('3. Replay last order', 'POST', ORDERS_URL, result.status, result.retryAfter, result.json);
                }

                async function conflictingIdempotencyKey() {
                    if (!state.lastOrder) {
                        logResult('4. Conflicting idempotency key', 'POST', ORDERS_URL, null, null, {
                            error: 'Submit a test order first (action 2).',
                        });
                        return;
                    }

                    var body = JSON.parse(JSON.stringify(state.lastOrder.body));
                    body.lines[0].quantity = (body.lines[0].quantity || 1) + 1;

                    var result = await apiFetch('POST', ORDERS_URL, body);
                    logResult('4. Conflicting idempotency key', 'POST', ORDERS_URL, result.status, result.retryAfter, result.json);
                }

                async function joinExistingSession() {
                    var productId = pickProductId();
                    if (productId === null) {
                        noProductAvailable('5. Join existing session');
                        return;
                    }

                    var body = newOrderBody(productId);
                    var result = await apiFetch('POST', ORDERS_URL, body);

                    var label = '5. Join existing session';
                    if (result.status === 201 && result.json && state.lastOrder) {
                        label += result.json.session_id === state.lastOrder.sessionId
                            ? ' — session matched ✓'
                            : ' — session DID NOT match ✗';
                    }

                    logResult(label, 'POST', ORDERS_URL, result.status, result.retryAfter, result.json);
                }

                async function staleSessionGuard() {
                    var productId = pickProductId();
                    if (productId === null) {
                        noProductAvailable('6. Stale session guard');
                        return;
                    }

                    var body = newOrderBody(productId, { expected_session_id: crypto.randomUUID() });
                    var result = await apiFetch('POST', ORDERS_URL, body);
                    logResult('6. Stale session guard', 'POST', ORDERS_URL, result.status, result.retryAfter, result.json);
                }

                async function invalidProduct() {
                    var body = newOrderBody(999999999);
                    var result = await apiFetch('POST', ORDERS_URL, body);
                    logResult('7. Invalid product', 'POST', ORDERS_URL, result.status, result.retryAfter, result.json);
                }

                async function duplicateProductLines() {
                    var productId = pickProductId();
                    if (productId === null) {
                        noProductAvailable('8. Duplicate product lines');
                        return;
                    }

                    var body = newOrderBody(productId);
                    body.lines.push({ product_id: productId, quantity: 1 });

                    var result = await apiFetch('POST', ORDERS_URL, body);
                    logResult('8. Duplicate product lines', 'POST', ORDERS_URL, result.status, result.retryAfter, result.json);
                }

                async function hammerRateLimit() {
                    var productId = pickProductId() || 1;

                    for (var i = 0; i < 12; i++) {
                        var body = newOrderBody(productId);
                        var result = await apiFetch('POST', ORDERS_URL, body);
                        logResult(
                            '9. Hammer rate limit (' + (i + 1) + '/12)',
                            'POST', ORDERS_URL, result.status, result.retryAfter, result.json
                        );
                    }
                }

                function clearLog() {
                    logEl.innerHTML = '';
                }

                var actions = {
                    'load-menu': loadMenu,
                    'submit-order': submitTestOrder,
                    'replay-order': replayLastOrder,
                    'conflicting-key': conflictingIdempotencyKey,
                    'join-session': joinExistingSession,
                    'stale-session': staleSessionGuard,
                    'invalid-product': invalidProduct,
                    'duplicate-lines': duplicateProductLines,
                    'hammer-rate-limit': hammerRateLimit,
                    'clear-log': clearLog,
                };

                document.querySelectorAll('#guest-api-drawer [data-action]').forEach(function (button) {
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

                var drawer = document.getElementById('guest-api-drawer');
                var overlay = document.getElementById('guest-api-drawer-overlay');
                var toggleButton = document.getElementById('guest-api-drawer-toggle');
                var closeButton = document.getElementById('guest-api-drawer-close');

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
