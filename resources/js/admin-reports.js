const emitToast = (type, message) => {
    document.dispatchEvent(new CustomEvent('app:toast', {
        detail: { type, message },
    }));
};

const extractErrorMessage = async (response) => {
    try {
        const payload = await response.json();

        if (typeof payload.message === 'string' && payload.message !== '') {
            return payload.message;
        }

        const errors = payload.errors ?? {};
        const firstKey = Object.keys(errors)[0];

        if (firstKey && Array.isArray(errors[firstKey]) && errors[firstKey].length > 0) {
            return String(errors[firstKey][0]);
        }
    } catch (_) {
        return 'Nie udalo sie przygotowac podgladu raportu.';
    }

    return 'Nie udalo sie przygotowac podgladu raportu.';
};

const initAdminReportsPreview = () => {
    const buttons = Array.from(document.querySelectorAll('[data-role="admin-report-preview"]'));

    if (buttons.length === 0) {
        return;
    }

    buttons.forEach((button) => {
        button.addEventListener('click', async () => {
            if (!(button instanceof HTMLButtonElement)) {
                return;
            }

            const form = button.closest('form');
            const previewUrl = button.dataset.previewUrl;

            if (!(form instanceof HTMLFormElement) || !previewUrl) {
                return;
            }

            const originalLabel = button.textContent ?? 'Podglad HTML';
            button.disabled = true;
            button.textContent = 'Ladowanie...';

            try {
                const response = await fetch(previewUrl, {
                    method: 'POST',
                    headers: {
                        Accept: 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    credentials: 'same-origin',
                    body: new FormData(form),
                });

                if (!response.ok) {
                    emitToast('warning', await extractErrorMessage(response));
                    return;
                }

                const payload = await response.json();

                if (payload.status !== 'ok' || typeof payload.html !== 'string') {
                    emitToast('warning', payload.message ?? 'Brak danych do podgladu.');
                    return;
                }

                const previewWindow = window.open('', '_blank', 'noopener,noreferrer');

                if (!previewWindow) {
                    emitToast('warning', 'Przegladarka zablokowala nowe okno podgladu.');
                    return;
                }

                previewWindow.document.open();
                previewWindow.document.write(payload.html);
                previewWindow.document.close();

                if (typeof payload.title === 'string' && payload.title !== '') {
                    previewWindow.document.title = payload.title;
                }
            } catch (_) {
                emitToast('danger', 'Nie udalo sie przygotowac podgladu raportu.');
            } finally {
                button.disabled = false;
                button.textContent = originalLabel;
            }
        });
    });
};

document.addEventListener('DOMContentLoaded', initAdminReportsPreview);
