import QrScanner from 'qr-scanner';
import qrScannerWorkerPath from 'qr-scanner/qr-scanner-worker.min?url';

QrScanner.WORKER_PATH = qrScannerWorkerPath;

const root = document.querySelector('[data-qr-scanner]');

if (root) {
    const configNode = root.querySelector('[data-role="qr-scanner-config"]');
    const config = configNode ? JSON.parse(configNode.textContent || '{}') : {};
    const modeConfig = config.modes ?? {};
    const endpoints = config.endpoints ?? {};
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '';
    const cooldownMs = Number(config.cooldown_ms ?? 2500);

    const modeButtons = Array.from(root.querySelectorAll('[data-role="qr-mode-button"]'));
    const statusLabel = root.querySelector('[data-role="qr-status-label"]');
    const stateBadge = root.querySelector('[data-role="qr-state-badge"]');
    const cameraHint = root.querySelector('[data-role="qr-camera-hint"]');
    const feedback = root.querySelector('[data-role="qr-feedback"]');
    const clearFeedbackButton = root.querySelector('[data-role="qr-clear-feedback"]');
    const restartCameraButton = root.querySelector('[data-role="qr-camera-restart"]');
    const video = root.querySelector('[data-role="qr-video"]');
    const manualForm = root.querySelector('[data-role="qr-manual-form"]');
    const manualInput = root.querySelector('[data-role="qr-manual-input"]');
    const manualSubmit = root.querySelector('[data-role="qr-manual-submit"]');
    const modeTitle = root.querySelector('[data-role="qr-mode-title"]');
    const modeDescription = root.querySelector('[data-role="qr-mode-description"]');
    const activityList = root.querySelector('[data-role="qr-activity-list"]');
    const confirmationPanel = root.querySelector('[data-role="qr-confirmation-panel"]');
    const confirmationText = root.querySelector('[data-role="qr-confirmation-text"]');
    const confirmButton = root.querySelector('[data-role="qr-confirm-button"]');
    const confirmCancel = root.querySelector('[data-role="qr-confirm-cancel"]');
    const weightSheet = root.querySelector('[data-role="qr-weight-sheet"]');
    const weightAnimal = root.querySelector('[data-role="qr-weight-animal"]');
    const weightForm = root.querySelector('[data-role="qr-weight-form"]');
    const weightInput = root.querySelector('[data-role="qr-weight-input"]');
    const weightCancelButtons = Array.from(root.querySelectorAll('[data-role="qr-weight-cancel"]'));
    const weightConfirmation = root.querySelector('[data-role="qr-weight-confirmation"]');
    const weightDuplicateActions = root.querySelector('[data-role="qr-weight-duplicate-actions"]');
    const weightConfirmSubmit = root.querySelector('[data-role="qr-weight-confirm-submit"]');
    const weightDuplicateCancel = root.querySelector('[data-role="qr-weight-duplicate-cancel"]');

    const state = {
        mode: config.default_mode ?? 'feeding',
        scanner: null,
        processing: false,
        paused: false,
        lastPayload: '',
        lastPayloadAt: 0,
        pendingConfirmation: null,
        weightContext: null,
        activityItems: [],
    };

    const setUiState = (name, label, badgeClass) => {
        if (statusLabel instanceof HTMLElement) {
            statusLabel.textContent = label;
        }
        if (stateBadge instanceof HTMLElement) {
            stateBadge.textContent = name;
            stateBadge.className = `badge qr-state-badge ${badgeClass}`;
        }
    };

    const setMode = (mode) => {
        state.mode = mode;
        modeButtons.forEach((button) => {
            button.classList.toggle('active', button.dataset.mode === mode);
        });

        const currentMode = modeConfig[mode] ?? {};

        if (modeTitle instanceof HTMLElement) {
            modeTitle.textContent = currentMode.label ?? mode;
        }

        if (modeDescription instanceof HTMLElement) {
            modeDescription.textContent = currentMode.description ?? '';
        }

        if (manualSubmit instanceof HTMLButtonElement) {
            manualSubmit.textContent = currentMode.manual_action_label ?? 'Wykonaj akcje';
        }

        if (cameraHint instanceof HTMLElement) {
            cameraHint.textContent = mode === 'weight'
                ? 'Zeskanuj kod, potem wpisz wage.'
                : 'Ustaw kod QR w ramce.';
        }
    };

    const flash = (type, message) => {
        if (!(feedback instanceof HTMLElement)) {
            return;
        }

        feedback.className = `alert mb-3 alert-${type}`;
        feedback.textContent = message;
        feedback.classList.remove('d-none');
    };

    const clearFlash = () => {
        if (!(feedback instanceof HTMLElement)) {
            return;
        }

        feedback.classList.add('d-none');
        feedback.textContent = '';
    };

    const vibrate = (pattern) => {
        if (navigator.vibrate) {
            navigator.vibrate(pattern);
        }
    };

    const addActivity = (outcome) => {
        if (!(activityList instanceof HTMLElement)) {
            return;
        }

        const item = document.createElement('div');
        item.className = 'list-group-item';
        item.innerHTML = `
            <div class="d-flex justify-content-between align-items-start gap-2">
                <div>
                    <div class="fw-semibold">${outcome.modeLabel}</div>
                    <div class="small">${outcome.label}</div>
                </div>
                <span class="small text-muted">${new Date().toLocaleTimeString()}</span>
            </div>
            <div class="small text-muted mt-1">${outcome.message}</div>
        `;

        state.activityItems.unshift(item);
        state.activityItems = state.activityItems.slice(0, 6);
        activityList.innerHTML = '';
        state.activityItems.forEach((node) => activityList.appendChild(node));
    };

    const modeLabel = (mode) => modeConfig[mode]?.label ?? mode;

    const setProcessing = (processing) => {
        state.processing = processing;
    };

    const closeConfirmation = () => {
        state.pendingConfirmation = null;

        if (confirmationPanel instanceof HTMLElement) {
            confirmationPanel.classList.add('d-none');
        }
    };

    const openConfirmation = (response) => {
        state.pendingConfirmation = response;

        if (confirmationPanel instanceof HTMLElement) {
            confirmationPanel.classList.remove('d-none');
        }

        if (confirmationText instanceof HTMLElement) {
            confirmationText.textContent = response.message;
        }
    };

    const openWeightSheet = (animal, payload) => {
        state.weightContext = {
            payload,
            animal,
            confirmDuplicate: false,
        };

        closeConfirmation();
        clearFlash();

        if (weightAnimal instanceof HTMLElement) {
            weightAnimal.textContent = `${animal.label} (${animal.public_tag || 'brak tagu'})`;
        }

        if (weightConfirmation instanceof HTMLElement) {
            weightConfirmation.classList.add('d-none');
            weightConfirmation.textContent = '';
        }

        if (weightDuplicateActions instanceof HTMLElement) {
            weightDuplicateActions.classList.add('d-none');
        }

        if (weightSheet instanceof HTMLElement) {
            weightSheet.classList.remove('d-none');
            weightSheet.setAttribute('aria-hidden', 'false');
        }

        pauseScanner();
        setUiState('Waga', `Wpisz wage dla: ${animal.label}`, 'text-bg-warning');

        window.setTimeout(() => {
            weightInput?.focus();
            weightInput?.select();
        }, 40);
    };

    const closeWeightSheet = async () => {
        state.weightContext = null;

        if (weightSheet instanceof HTMLElement) {
            weightSheet.classList.add('d-none');
            weightSheet.setAttribute('aria-hidden', 'true');
        }

        if (weightForm instanceof HTMLFormElement) {
            weightForm.reset();
        }

        if (weightConfirmation instanceof HTMLElement) {
            weightConfirmation.classList.add('d-none');
            weightConfirmation.textContent = '';
        }

        if (weightDuplicateActions instanceof HTMLElement) {
            weightDuplicateActions.classList.add('d-none');
        }

        await resumeScanner();
    };

    const extractErrorMessage = async (response) => {
        try {
            const payload = await response.json();

            if (typeof payload.message === 'string' && payload.message.length > 0) {
                return payload.message;
            }

            const errors = payload.errors ?? {};
            const firstKey = Object.keys(errors)[0];

            if (firstKey && Array.isArray(errors[firstKey]) && errors[firstKey].length > 0) {
                return String(errors[firstKey][0]);
            }
        } catch (_) {
            return 'Wystapil nieoczekiwany blad.';
        }

        return 'Wystapil nieoczekiwany blad.';
    };

    const requestJson = async (url, payload) => {
        const response = await fetch(url, {
            method: 'POST',
            headers: {
                Accept: 'application/json',
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                'X-Requested-With': 'XMLHttpRequest',
            },
            credentials: 'same-origin',
            body: JSON.stringify(payload),
        });

        const data = response.headers.get('content-type')?.includes('application/json')
            ? await response.json()
            : null;

        if (!response.ok && !data) {
            throw new Error(await extractErrorMessage(response));
        }

        if (!response.ok && data) {
            const message = typeof data.message === 'string' ? data.message : 'Nie udalo sie wykonac operacji.';
            const error = new Error(message);
            error.responseData = data;
            error.status = response.status;
            throw error;
        }

        return data;
    };

    const normalizePayload = (payload) => payload.trim();

    const shouldIgnorePayload = (payload) => {
        const now = Date.now();

        if (payload === state.lastPayload && now - state.lastPayloadAt < cooldownMs) {
            return true;
        }

        state.lastPayload = payload;
        state.lastPayloadAt = now;

        return false;
    };

    const pauseScanner = () => {
        if (state.scanner && typeof state.scanner.pause === 'function') {
            state.scanner.pause();
            state.paused = true;
        }
    };

    const resumeScanner = async () => {
        closeConfirmation();
        setProcessing(false);

        if (!state.scanner) {
            await startScanner();
            return;
        }

        try {
            await state.scanner.start();
            state.paused = false;
            setUiState('Skanowanie', `Tryb: ${modeLabel(state.mode)}. Czekam na kod QR.`, 'text-bg-success');
        } catch (error) {
            setUiState('Blad', 'Nie udalo sie wznowic kamery. Uzyj pola recznego.', 'text-bg-danger');
            flash('danger', error instanceof Error ? error.message : 'Nie udalo sie wznowic kamery.');
        }
    };

    const handleSuccess = async (response, payload) => {
        const animal = response.animal ?? {};
        const label = animal.label ?? animal.name ?? 'Zwierze';
        const detail = response.mode === 'weight'
            ? `${label} - ${response.data?.value ?? ''} g`
            : `${label} - ${animal.public_tag ?? ''}`;

        closeConfirmation();
        setProcessing(false);
        flash('success', `${response.message} ${label}`);
        vibrate(35);
        addActivity({
            modeLabel: modeLabel(response.mode),
            label: detail.trim(),
            message: response.message,
        });
        await resumeScanner();
    };

    const handleActionError = (error, fallbackMode) => {
        setProcessing(false);
        const responseData = error?.responseData;

        if (responseData?.status === 'duplicate_confirmation_required') {
            if (fallbackMode === 'weight') {
                if (weightConfirmation instanceof HTMLElement) {
                    weightConfirmation.classList.remove('d-none');
                    weightConfirmation.textContent = responseData.message;
                }

                if (weightDuplicateActions instanceof HTMLElement) {
                    weightDuplicateActions.classList.remove('d-none');
                }

                return;
            }

            openConfirmation(responseData);
            setUiState('Potwierdz', responseData.message, 'text-bg-warning');
            return;
        }

        const message = error instanceof Error ? error.message : 'Nie udalo sie wykonac operacji.';
        flash('danger', message);
        vibrate([60, 30, 60]);
        setUiState('Blad', message, 'text-bg-danger');
    };

    const submitAction = async (mode, payload, { reopenWeight = false } = {}) => {
        if (!endpoints[mode]) {
            flash('danger', 'Brakuje konfiguracji endpointu.');
            return;
        }

        setProcessing(true);
        closeConfirmation();
        clearFlash();
        setUiState('Przetwarzanie', 'Trwa zapis danych...', 'text-bg-primary');

        try {
            const response = await requestJson(endpoints[mode], payload);

            if (mode === 'weight' && reopenWeight) {
                await closeWeightSheet();
            }

            await handleSuccess(response, payload.payload);
        } catch (error) {
            if (mode === 'weight' && reopenWeight) {
                setUiState('Waga', 'Zweryfikuj wage lub potwierdz duplikat.', 'text-bg-warning');
            }

            handleActionError(error, mode);
        }
    };

    const resolveForWeight = async (payload) => {
        setProcessing(true);
        closeConfirmation();
        clearFlash();
        setUiState('Rozpoznawanie', 'Szukam zwierzecia dla zeskanowanego kodu...', 'text-bg-info');

        try {
            const response = await requestJson(endpoints.resolve, { payload });
            setProcessing(false);
            openWeightSheet(response.animal ?? {}, payload);
        } catch (error) {
            handleActionError(error, 'weight');
        }
    };

    const processPayload = async (rawPayload) => {
        const payload = normalizePayload(rawPayload);

        if (payload === '' || state.processing || state.weightContext || state.pendingConfirmation) {
            return;
        }

        if (shouldIgnorePayload(payload)) {
            return;
        }

        if (state.mode === 'weight') {
            await resolveForWeight(payload);
            return;
        }

        await submitAction(state.mode, {
            payload,
            confirm_duplicate: false,
        });
    };

    const startScanner = async () => {
        if (!(video instanceof HTMLVideoElement)) {
            flash('danger', 'Brakuje elementu video dla kamery.');
            return;
        }

        if (!state.scanner) {
            state.scanner = new QrScanner(
                video,
                (result) => {
                    const raw = typeof result === 'string' ? result : result?.data ?? '';
                    void processPayload(raw);
                },
                {
                    preferredCamera: 'environment',
                    maxScansPerSecond: 5,
                    returnDetailedScanResult: true,
                    highlightScanRegion: true,
                }
            );
        }

        try {
            await state.scanner.start();
            state.paused = false;
            setUiState('Skanowanie', `Tryb: ${modeLabel(state.mode)}. Czekam na kod QR.`, 'text-bg-success');
        } catch (error) {
            const message = error instanceof Error
                ? error.message
                : 'Nie udalo sie uruchomic kamery. Uzyj pola recznego.';
            flash('warning', message);
            setUiState('Brak kamery', message, 'text-bg-warning');
        }
    };

    modeButtons.forEach((button) => {
        button.addEventListener('click', () => {
            if (state.processing || state.weightContext || state.pendingConfirmation) {
                flash('warning', 'Najpierw zakoncz aktualna akcje.');
                return;
            }

            setMode(button.dataset.mode ?? 'feeding');
            void resumeScanner();
        });
    });

    restartCameraButton?.addEventListener('click', () => {
        void startScanner();
    });

    clearFeedbackButton?.addEventListener('click', () => {
        clearFlash();
    });

    manualForm?.addEventListener('submit', (event) => {
        event.preventDefault();
        const payload = manualInput instanceof HTMLInputElement ? manualInput.value : '';
        void processPayload(payload);
    });

    confirmButton?.addEventListener('click', () => {
        if (!state.pendingConfirmation) {
            return;
        }

        const response = state.pendingConfirmation;

        void submitAction(response.mode, {
            payload: state.lastPayload,
            confirm_duplicate: true,
        });
    });

    confirmCancel?.addEventListener('click', () => {
        closeConfirmation();
        clearFlash();
        void resumeScanner();
    });

    weightForm?.addEventListener('submit', (event) => {
        event.preventDefault();

        if (!(weightInput instanceof HTMLInputElement) || !state.weightContext) {
            return;
        }

        const value = weightInput.value.trim();

        void submitAction('weight', {
            payload: state.weightContext.payload,
            value,
            confirm_duplicate: state.weightContext.confirmDuplicate,
        }, { reopenWeight: true });
    });

    weightConfirmSubmit?.addEventListener('click', () => {
        if (!(weightInput instanceof HTMLInputElement) || !state.weightContext) {
            return;
        }

        state.weightContext.confirmDuplicate = true;

        void submitAction('weight', {
            payload: state.weightContext.payload,
            value: weightInput.value.trim(),
            confirm_duplicate: true,
        }, { reopenWeight: true });
    });

    weightDuplicateCancel?.addEventListener('click', () => {
        if (weightConfirmation instanceof HTMLElement) {
            weightConfirmation.classList.add('d-none');
        }

        if (weightDuplicateActions instanceof HTMLElement) {
            weightDuplicateActions.classList.add('d-none');
        }

        if (state.weightContext) {
            state.weightContext.confirmDuplicate = false;
        }
    });

    weightCancelButtons.forEach((button) => {
        button.addEventListener('click', () => {
            clearFlash();
            void closeWeightSheet();
        });
    });

    document.addEventListener('visibilitychange', () => {
        if (!state.scanner) {
            return;
        }

        if (document.hidden) {
            pauseScanner();
            return;
        }

        if (!state.weightContext) {
            void resumeScanner();
        }
    });

    setMode(state.mode);
    void startScanner();
}
