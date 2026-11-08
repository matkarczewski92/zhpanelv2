<script>
    (() => {
    const showError = (container, message) => {
        const errorBox = container.querySelector('[data-planning-error]');
        if (!errorBox) return;

        errorBox.textContent = message || '';
    };

    const sanitizeQuantity = (value) => {
        const parsed = parseInt(value, 10);
        return Number.isFinite(parsed) && parsed > 0 ? parsed : 0;
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

    document.addEventListener('click', (event) => {
        const button = event.target.closest('[data-action="planning-order-summary"]');
        if (!button) return;

        const container = button.closest('#feedPlanning');
        if (!container) return;

        event.preventDefault();
        openPlanningOrder(container);
    });
    })();
</script>
