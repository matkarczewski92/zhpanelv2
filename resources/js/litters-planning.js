const appId = 'littersPlanningApp';

const getCsrfToken = () => document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '';

const parseJson = (value, fallback = null) => {
    if (!value) return fallback;
    try {
        return JSON.parse(value);
    } catch (_) {
        return fallback;
    }
};

const parseBase64Json = (value, fallback = null) => {
    if (!value) return fallback;
    try {
        const decoded = window.atob(value);
        return JSON.parse(decoded);
    } catch (_) {
        return fallback;
    }
};

const setTab = (app, tabId) => {
    const tabs = app.querySelectorAll('[data-tab-id]');
    const buttons = app.querySelectorAll('[data-tab-target]');

    tabs.forEach((tab) => {
        tab.classList.toggle('d-none', tab.dataset.tabId !== tabId);
    });

    buttons.forEach((button) => {
        const active = button.dataset.tabTarget === tabId;
        button.classList.toggle('btn-primary', active);
        button.classList.toggle('btn-outline-light', !active);
    });
};

const firstErrorMessage = (payload) => {
    if (!payload || typeof payload !== 'object') {
        return null;
    }

    if (payload.errors && typeof payload.errors === 'object') {
        const first = Object.values(payload.errors)[0];
        if (Array.isArray(first) && first.length > 0) {
            return first[0];
        }
    }

    if (typeof payload.message === 'string' && payload.message.trim() !== '') {
        return payload.message;
    }

    return null;
};

const postJson = async (url, body) => {
    const response = await fetch(new URL(url, window.location.origin).toString(), {
        method: 'POST',
        credentials: 'same-origin',
        headers: {
            'Content-Type': 'application/json',
            Accept: 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-TOKEN': getCsrfToken(),
        },
        body: JSON.stringify(body),
    });

    const rawText = await response.text();
    const withoutBom = rawText.replace(/^\uFEFF+/, '');
    const trimmed = withoutBom.trimStart();
    const jsonStart = trimmed.search(/[\[{]/);
    const jsonText = jsonStart >= 0 ? trimmed.slice(jsonStart) : trimmed;

    const parsePayload = () => {
        try {
            return jsonText !== '' ? JSON.parse(jsonText) : {};
        } catch (_) {
            return {};
        }
    };

    if (!response.ok) {
        const payload = parsePayload();
        throw new Error(firstErrorMessage(payload) ?? `HTTP ${response.status}`);
    }

    const payload = parsePayload();
    if (!payload || typeof payload !== 'object') {
        throw new Error('Niepoprawna odpowiedz JSON.');
    }

    return payload;
};

const normalizePair = (pair) => ({
    female_id: Number(pair.female_id),
    male_id: Number(pair.male_id),
    female_name: pair.female_name ?? '',
    male_name: pair.male_name ?? '',
});

document.addEventListener('DOMContentLoaded', () => {
    const app = document.getElementById(appId);
    if (!app) return;

    const state = {
        selectedPairs: [],
    };

    const femaleSelect = app.querySelector('[data-role="planning-female"]');
    const planningResults = app.querySelector('[data-role="planning-results"]');
    const selectedCount = app.querySelector('[data-role="selected-pairs-count"]');

    const summaryModalEl = document.getElementById('litterPlanningSummaryModal');
    const summaryModal = summaryModalEl && window.bootstrap ? new window.bootstrap.Modal(summaryModalEl) : null;
    const summaryContent = summaryModalEl?.querySelector('[data-role="summary-content"]');
    const summaryPairsInput = summaryModalEl?.querySelector('[data-role="pairs-json-modal"]');
    const summaryPlanIdInput = summaryModalEl?.querySelector('[data-role="plan-id"]');
    const summaryPlanNameInput = document.getElementById('summaryPlanName');
    const summaryPlanYearInput = document.getElementById('summaryPlanYear');

    const initialPairs = parseJson(summaryPairsInput?.value, []);
    if (Array.isArray(initialPairs)) {
        state.selectedPairs = initialPairs.map(normalizePair);
    }

    const refreshCount = () => {
        if (selectedCount) {
            selectedCount.textContent = String(state.selectedPairs.length);
        }
        if (summaryPairsInput) {
            summaryPairsInput.value = JSON.stringify(state.selectedPairs);
        }
    };

    const normalizeFemaleOptionsLabels = () => {
        if (!femaleSelect) return;

        Array.from(femaleSelect.options).forEach((option, index) => {
            if (index === 0) return;

            const name = option.dataset.displayName ?? option.dataset.name ?? option.textContent?.trim() ?? '';
            const weight = Number(option.dataset.weight ?? 0);
            const used = option.dataset.used === '1' ? 'v ' : '';
            if (option.dataset.displayName) {
                option.textContent = `${used}${name}`.trim();
            } else {
                option.textContent = `${used}(${weight}g.) ${name}`.trim();
            }
        });
    };

    const renderFemalePreview = async () => {
        if (!planningResults) return;

        planningResults.innerHTML = '<div class="small text-muted">Ladowanie...</div>';

        try {
            const payload = await postJson(app.dataset.femalePreviewUrl, {
                female_id: femaleSelect?.value ? Number(femaleSelect.value) : null,
                pairs: state.selectedPairs,
            });

            planningResults.innerHTML = payload.html ?? '<div class="small text-muted">Brak danych.</div>';
        } catch (error) {
            planningResults.innerHTML = `<div class="small text-danger">${error.message}</div>`;
        }
    };

    const buildSummary = async () => {
        if (!summaryContent) return;

        summaryContent.innerHTML = '<div class="small text-muted">Ladowanie...</div>';

        try {
            const payload = await postJson(app.dataset.summaryUrl, {
                pairs: state.selectedPairs,
            });

            summaryContent.innerHTML = payload.html ?? '<p class="text-muted mb-0">Brak danych.</p>';
        } catch (error) {
            summaryContent.innerHTML = `<div class="small text-danger">${error.message}</div>`;
        }
    };

    app.querySelectorAll('[data-tab-target]').forEach((button) => {
        button.addEventListener('click', () => {
            const tab = button.dataset.tabTarget;
            if (tab) {
                setTab(app, tab);
            }
        });
    });

    setTab(app, app.dataset.activeTab || 'planning');
    normalizeFemaleOptionsLabels();
    refreshCount();
    if (femaleSelect && state.selectedPairs.length > 0) {
        femaleSelect.value = String(state.selectedPairs[0].female_id);
    }

    femaleSelect?.addEventListener('change', () => {
        renderFemalePreview();
    });

    app.addEventListener('change', (event) => {
        const target = event.target;
        if (!(target instanceof HTMLInputElement)) {
            return;
        }

        if (target.matches('[data-action="toggle-pair"]')) {
            const femaleId = Number(target.dataset.femaleId || 0);
            const maleId = Number(target.dataset.maleId || 0);
            if (!femaleId || !maleId) return;

            const maleTitle = target.closest('.glass-card')?.querySelector('h5')?.textContent?.trim() ?? `Samiec #${maleId}`;
            const femaleName = femaleSelect?.options[femaleSelect.selectedIndex]?.textContent ?? `Samica #${femaleId}`;
            const key = `${femaleId}:${maleId}`;

            const exists = state.selectedPairs.findIndex((pair) => `${pair.female_id}:${pair.male_id}` === key);
            if (target.checked && exists === -1) {
                state.selectedPairs.push(normalizePair({
                    female_id: femaleId,
                    male_id: maleId,
                    female_name: femaleName.replace(/^.*\)\s*/, ''),
                    male_name: maleTitle,
                }));
            }

            if (!target.checked && exists !== -1) {
                state.selectedPairs.splice(exists, 1);
            }

            refreshCount();
            renderFemalePreview();
        }
    });

    app.querySelector('[data-action="open-summary-modal"]')?.addEventListener('click', async () => {
        await buildSummary();
        refreshCount();
        summaryModal?.show();
    });

    summaryModalEl?.querySelector('[data-action="clear-selected-pairs"]')?.addEventListener('click', async () => {
        state.selectedPairs = [];
        refreshCount();
        await buildSummary();
        await renderFemalePreview();
    });

    app.querySelectorAll('[data-action="edit-plan"]').forEach((button) => {
        button.addEventListener('click', async () => {
            const pairs = parseBase64Json(button.dataset.planPairsB64, []);
            state.selectedPairs = Array.isArray(pairs) ? pairs.map(normalizePair) : [];
            refreshCount();

            if (summaryPlanIdInput) summaryPlanIdInput.value = button.dataset.planId ?? '';
            if (summaryPlanNameInput) summaryPlanNameInput.value = button.dataset.planName ?? '';
            if (summaryPlanYearInput) summaryPlanYearInput.value = button.dataset.planYear ?? '';

            if (femaleSelect && state.selectedPairs.length > 0) {
                femaleSelect.value = String(state.selectedPairs[0].female_id);
            }

            setTab(app, 'planning');
            await renderFemalePreview();
            await buildSummary();
            summaryModal?.show();
        });
    });

    app.querySelector('[data-action="new-plan"]')?.addEventListener('click', async () => {
        state.selectedPairs = [];
        refreshCount();

        if (summaryPlanIdInput) summaryPlanIdInput.value = '';
        if (summaryPlanNameInput) summaryPlanNameInput.value = '';

        setTab(app, 'planning');
        await renderFemalePreview();
    });

    if (femaleSelect && femaleSelect.value) {
        renderFemalePreview();
    }

    if (summaryModalEl?.dataset.openOnLoad === '1') {
        buildSummary().then(() => summaryModal?.show());
    }
});
