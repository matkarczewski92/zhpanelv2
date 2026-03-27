const emitToast = (type, message) => {
    document.dispatchEvent(new CustomEvent('app:toast', {
        detail: { type, message },
    }));
};

const openPreviewWindow = (action, form) => {
    const previewForm = document.createElement('form');
    previewForm.method = 'POST';
    previewForm.action = action;
    previewForm.target = '_blank';
    previewForm.style.display = 'none';

    Array.from(new FormData(form).entries()).forEach(([key, value]) => {
        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = key;
        input.value = typeof value === 'string' ? value : '';
        previewForm.appendChild(input);
    });

    document.body.appendChild(previewForm);
    previewForm.submit();
    previewForm.remove();
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

                if (payload.status !== 'ok') {
                    emitToast('warning', payload.message ?? 'Brak danych do podgladu.');
                    return;
                }

                openPreviewWindow(previewUrl, form);
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
