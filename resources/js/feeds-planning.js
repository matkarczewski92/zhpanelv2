const planningContainerId = 'feedPlanning';

const getCsrfToken = () => document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '';

const showError = (container, message) => {
    const errorBox = container.querySelector('[data-planning-error]');
    if (!errorBox) return;

    errorBox.textContent = message || '';
};

const sanitizeQuantity = (value) => {
    const parsed = parseInt(value, 10);
    return Number.isFinite(parsed) && parsed > 0 ? parsed : 0;
};

const collectPayload = (container) => {
    const items = [];

    container.querySelectorAll('tbody tr[data-feed-id]').forEach((row) => {
        const feedId = Number(row.dataset.feedId);
        if (!feedId) {
            return;
        }

        const input = row.querySelector('.js-order-qty');
        const qty = input ? sanitizeQuantity(input.value) : 0;

        items.push({
            feed_id: feedId,
            order_qty: qty,
        });
    });

    return { items };
};

const openPlanningOrder = (container) => {
    const items = [];

    container.querySelectorAll('tbody tr[data-feed-id]').forEach((row) => {
        const quantity = sanitizeQuantity(row.querySelector('.js-order-qty')?.value);
        if (quantity > 0) {
            items.push({
                name: row.querySelector('[data-feed-name]')?.textContent.trim() ?? '',
                quantity,
            });
        }
    });

    const orderWindow = window.open('', '_blank', 'width=760,height=800,scrollbars=yes,resizable=yes');
    if (!orderWindow) {
        showError(container, 'Nie udało się otworzyć zestawienia. Zezwól na wyskakujące okna i spróbuj ponownie.');
        return;
    }

    showError(container, '');
    orderWindow.opener = null;
    const orderDocument = orderWindow.document;
    orderDocument.open();
    orderDocument.write(`<!DOCTYPE html>
        <html lang="pl">
            <head>
                <meta charset="utf-8">
                <meta name="viewport" content="width=device-width, initial-scale=1">
                <title>Zamówienie karmy</title>
                <style>
                    body { margin: 0; padding: 24px; font-family: system-ui, sans-serif; color: #1f2937; background: #fff; }
                    main { max-width: 720px; margin: 0 auto; }
                    h1 { font-size: 24px; }
                    h2 { margin-top: 28px; font-size: 18px; }
                    table { width: 100%; border-collapse: collapse; }
                    th, td { padding: 10px 12px; border: 1px solid #d1d5db; text-align: left; overflow-wrap: anywhere; }
                    th { background: #f3f4f6; }
                    th:last-child, td:last-child { text-align: right; }
                    pre { padding: 16px; border: 1px solid #d1d5db; border-radius: 6px; white-space: pre-wrap; overflow-wrap: anywhere; font: inherit; }
                </style>
            </head>
            <body>
                <main>
                    <h1>Zamówienie karmy</h1>
                    <table>
                        <thead><tr><th scope="col">Nazwa</th><th scope="col">Ilość</th></tr></thead>
                        <tbody></tbody>
                    </table>
                    <h2>Tekst do skopiowania</h2>
                    <pre></pre>
                </main>
            </body>
        </html>`);
    orderDocument.close();

    const tableBody = orderDocument.querySelector('tbody');
    items.forEach(({ name, quantity }) => {
        const row = tableBody.insertRow();
        row.insertCell().textContent = name;
        row.insertCell().textContent = String(quantity);
    });

    const emptyMessage = 'Brak niezerowych pozycji zamówienia.';
    if (items.length === 0) {
        const cell = tableBody.insertRow().insertCell();
        cell.colSpan = 2;
        cell.textContent = emptyMessage;
    }
    orderDocument.querySelector('pre').textContent = items.length
        ? items.map(({ name, quantity }) => `${name} → ${quantity}`).join('\n')
        : emptyMessage;
    orderWindow.focus();
};

const updateRow = (container, feedId, data) => {
    const row = container.querySelector(`tr[data-feed-id="${feedId}"]`);
    if (!row || !data) {
        return;
    }

    const setText = (selector, value) => {
        const el = row.querySelector(selector);
        if (el) {
            el.textContent = value ?? '—';
        }
    };

    setText('.js-dk', data.dk_label);
    setText('.js-dz', data.dz_label);
    setText('.js-new-dk', data.new_dk_label);
    setText('.js-new-dz', data.new_dz_label);
    setText('.js-row-cost', data.row_cost_label);

    const input = row.querySelector('.js-order-qty');
    if (input && typeof data.order_qty === 'number') {
        input.value = data.order_qty;
    }
};

const updateTotals = (container, totalLabel) => {
    const totalEl = container.querySelector('[data-planning-total]');
    if (totalEl) {
        totalEl.textContent = totalLabel ?? '—';
    }
};

const parseValidationErrors = (payload) => {
    if (payload?.errors && typeof payload.errors === 'object') {
        const first = Object.values(payload.errors)[0];
        if (Array.isArray(first) && first.length > 0) {
            return first[0];
        }
    }

    if (payload?.message) {
        return payload.message;
    }

    return null;
};

const recalculatePlanning = async (container) => {
    const endpoint = container.dataset.url;
    const targetUrl = endpoint ? new URL(endpoint, window.location.origin).toString() : '';
    const button = container.querySelector('[data-action="planning-recalculate"]');
    const payload = collectPayload(container);

    if (!endpoint) {
        showError(container, 'Brak adresu API do przeliczenia.');
        return;
    }

    showError(container, '');
    if (button) {
        button.disabled = true;
    }

    try {
        const response = await fetch(targetUrl, {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': getCsrfToken(),
                'X-Requested-With': 'XMLHttpRequest',
            },
            body: JSON.stringify(payload),
        });

        if (response.ok) {
            const text = await response.text();
            let data = null;
            try {
                const cleaned = text ? text.replace(/^\uFEFF+/, '') : '';
                data = cleaned ? JSON.parse(cleaned) : null;
            } catch (error) {
                console.error('Planning recalculation response is not valid JSON.', error, text);
                showError(container, 'Niepoprawna odpowiedź serwera. Sprawdź konsolę.');
                return;
            }

            if (!data || typeof data !== 'object') {
                showError(container, 'Brak danych w odpowiedzi serwera.');
                return;
            }

            Object.entries(data.rows || {}).forEach(([feedId, row]) => {
                updateRow(container, feedId, row);
            });
            updateTotals(container, data.total_cost_label);
        } else if (response.status === 422) {
            const data = await response.json().catch(() => ({}));
            const message = parseValidationErrors(data);
            showError(container, message || 'Błędne dane. Sprawdź wartości zamówień.');
        } else if (response.status === 419) {
            showError(container, 'Sesja wygasła. Odśwież stronę i spróbuj ponownie.');
        } else {
            showError(container, `Nie udało się przeliczyć (HTTP ${response.status}).`);
        }
    } catch (_) {
        showError(container, `Wystąpił błąd połączenia. Spróbuj ponownie. (${targetUrl})`);
    } finally {
        if (button) {
            button.disabled = false;
        }
    }
};

document.addEventListener('DOMContentLoaded', () => {
    const container = document.getElementById(planningContainerId);
    if (!container) {
        return;
    }

    const button = container.querySelector('[data-action="planning-recalculate"]');
    button?.addEventListener('click', () => recalculatePlanning(container));

    const orderButton = container.querySelector('[data-action="planning-order"]');
    orderButton?.addEventListener('click', () => openPlanningOrder(container));
});
