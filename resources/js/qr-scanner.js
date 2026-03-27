const initQrScanner = () => {
    const root = document.querySelector('[data-qr-scanner]');

    if (!root) {
        return;
    }

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
    const sessionSummaryButton = root.querySelector('[data-role="qr-session-summary-button"]');
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

    const barcodeDetector = window.BarcodeDetector
        ? new window.BarcodeDetector({ formats: ['qr_code'] })
        : null;

    const state = {
        mode: config.default_mode ?? 'feeding',
        processing: false,
        paused: false,
        lastPayload: '',
        lastPayloadAt: 0,
        pendingConfirmation: null,
        weightContext: null,
        activityEntries: [],
        detector: barcodeDetector,
        stream: null,
        scanTimer: null,
        scanInFlight: false,
        sessionStartedAt: new Date().toISOString(),
        summaryGenerating: false,
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

    const updateSessionSummaryButton = () => {
        if (!(sessionSummaryButton instanceof HTMLButtonElement)) {
            return;
        }

        sessionSummaryButton.classList.toggle('d-none', state.activityEntries.length === 0);
        sessionSummaryButton.disabled = state.activityEntries.length === 0 || state.summaryGenerating || state.processing;
        sessionSummaryButton.textContent = state.summaryGenerating ? 'Generowanie...' : 'Wygeneruj podsumowanie';
    };

    const renderActivityList = () => {
        if (!(activityList instanceof HTMLElement)) {
            return;
        }

        activityList.innerHTML = '';

        if (state.activityEntries.length === 0) {
            const empty = document.createElement('div');
            empty.className = 'list-group-item text-muted small';
            empty.textContent = 'Brak akcji w tej sesji.';
            activityList.appendChild(empty);
            updateSessionSummaryButton();
            return;
        }

        state.activityEntries.forEach((entry) => {
            const item = document.createElement('div');
            item.className = 'list-group-item';
            item.innerHTML = `
                <div class="d-flex justify-content-between align-items-start gap-2">
                    <div>
                        <div class="fw-semibold">${entry.modeLabel}</div>
                        <div class="small">${entry.label}</div>
                    </div>
                    <span class="small text-muted">${entry.timeLabel}</span>
                </div>
                <div class="small text-muted mt-1">${entry.message}</div>
            `;
            activityList.appendChild(item);
        });

        updateSessionSummaryButton();
    };

    const addActivity = (entry) => {
        state.activityEntries.unshift(entry);
        renderActivityList();
    };

    const modeLabel = (mode) => modeConfig[mode]?.label ?? mode;

    const setProcessing = (processing) => {
        state.processing = processing;
        updateSessionSummaryButton();
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

    const stopScanLoop = () => {
        if (state.scanTimer) {
            window.clearTimeout(state.scanTimer);
            state.scanTimer = null;
        }
    };

    const stopCamera = () => {
        stopScanLoop();

        if (state.stream) {
            state.stream.getTracks().forEach((track) => track.stop());
            state.stream = null;
        }

        state.scanInFlight = false;

        if (video instanceof HTMLVideoElement) {
            video.pause();
            video.srcObject = null;
        }
    };

    const scheduleScan = (delay = 180) => {
        stopScanLoop();

        if (!state.stream || state.paused) {
            return;
        }

        state.scanTimer = window.setTimeout(() => {
            void scanFrame();
        }, delay);
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
        state.paused = true;
        stopScanLoop();
    };

    const resumeScanner = async () => {
        closeConfirmation();
        setProcessing(false);
        state.paused = false;

        if (!state.stream) {
            await startScanner();
            return;
        }

        setUiState('Skanowanie', `Tryb: ${modeLabel(state.mode)}. Czekam na kod QR.`, 'text-bg-success');
        scheduleScan();
    };

    const handleSuccess = async (response) => {
        const animal = response.animal ?? {};
        const label = animal.label ?? animal.name ?? 'Zwierze';
        const detail = response.mode === 'weight'
            ? `${label} - ${response.data?.value ?? ''} g`
            : `${label} - ${animal.public_tag ?? ''}`;
        const occurredAt = response.data?.occurred_at ?? new Date().toISOString();

        closeConfirmation();
        setProcessing(false);
        flash('success', `${response.message} ${label}`);
        vibrate(35);
        addActivity({
            modeLabel: modeLabel(response.mode),
            label: detail.trim(),
            message: response.message,
            timeLabel: new Date(occurredAt).toLocaleTimeString(),
            mode: response.mode,
            animalId: Number(animal.id ?? 0),
            occurredAt,
            feedType: response.mode === 'feeding' ? String(response.data?.feed_type ?? '') : null,
            quantity: response.mode === 'feeding' ? Number(response.data?.quantity ?? 0) : null,
            value: response.mode === 'weight' ? Number(response.data?.value ?? 0) : null,
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

            await handleSuccess(response);
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

        if (payload === '' || state.processing || state.weightContext) {
            return;
        }

        if (state.pendingConfirmation) {
            if (payload === state.lastPayload) {
                return;
            }

            closeConfirmation();
            clearFlash();
            setUiState('Skanowanie', `Tryb: ${modeLabel(state.mode)}. Czekam na kod QR.`, 'text-bg-success');
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

    const generateSessionSummary = async () => {
        if (!endpoints.session_summary || state.activityEntries.length === 0 || state.summaryGenerating) {
            return;
        }

        state.summaryGenerating = true;
        updateSessionSummaryButton();
        clearFlash();

        try {
            const response = await requestJson(endpoints.session_summary, {
                session_started_at: state.sessionStartedAt,
                entries: state.activityEntries
                    .slice()
                    .reverse()
                    .map((entry) => ({
                        mode: entry.mode,
                        animal_id: entry.animalId,
                        occurred_at: entry.occurredAt,
                        feed_type: entry.feedType,
                        quantity: entry.quantity,
                        value: entry.value,
                    })),
            });

            flash('success', response.message ?? 'Podsumowanie sesji zostalo zapisane.');
        } catch (error) {
            const message = error instanceof Error ? error.message : 'Nie udalo sie wygenerowac podsumowania sesji.';
            flash('danger', message);
        } finally {
            state.summaryGenerating = false;
            updateSessionSummaryButton();
        }
    };

    const detectQrCode = async () => {
        if (!(video instanceof HTMLVideoElement) || !state.detector) {
            return null;
        }

        const barcodes = await state.detector.detect(video);
        const match = Array.isArray(barcodes)
            ? barcodes.find((barcode) => typeof barcode.rawValue === 'string' && barcode.rawValue.trim() !== '')
            : null;

        return match?.rawValue ?? null;
    };

    async function scanFrame() {
        if (!(video instanceof HTMLVideoElement)) {
            return;
        }

        if (!state.stream || state.paused) {
            return;
        }

        if (state.scanInFlight) {
            scheduleScan();
            return;
        }

        if (video.readyState < HTMLMediaElement.HAVE_CURRENT_DATA) {
            scheduleScan();
            return;
        }

        state.scanInFlight = true;
        let hasPayload = false;

        try {
            const payload = await detectQrCode();
            hasPayload = Boolean(payload);

            if (payload) {
                await processPayload(payload);
            }
        } catch (error) {
            const message = error instanceof Error ? error.message : '';

            if (message && !/not supported/i.test(message)) {
                flash('warning', `Skanowanie chwilowo niedostepne: ${message}`);
            }
        } finally {
            state.scanInFlight = false;
            scheduleScan(hasPayload ? cooldownMs : 180);
        }
    }

    const getCameraStream = async () => {
        const primaryConstraints = {
            video: {
                facingMode: { ideal: 'environment' },
            },
            audio: false,
        };

        try {
            return await navigator.mediaDevices.getUserMedia(primaryConstraints);
        } catch (_) {
            return navigator.mediaDevices.getUserMedia({
                video: true,
                audio: false,
            });
        }
    };

    const startScanner = async () => {
        if (!(video instanceof HTMLVideoElement)) {
            flash('danger', 'Brakuje elementu video dla kamery.');
            return;
        }

        if (!window.isSecureContext) {
            const message = 'Kamera wymaga bezpiecznego polaczenia HTTPS.';
            flash('warning', message);
            setUiState('Brak kamery', message, 'text-bg-warning');
            return;
        }

        if (!navigator.mediaDevices?.getUserMedia) {
            const message = 'Ta przegladarka nie obsluguje dostepu do kamery.';
            flash('warning', message);
            setUiState('Brak kamery', message, 'text-bg-warning');
            return;
        }

        if (!state.detector) {
            const message = 'Ta przegladarka nie obsluguje skanowania QR. Uzyj Chrome na Androidzie lub pola recznego.';
            flash('warning', message);
            setUiState('Brak skanera', message, 'text-bg-warning');
            return;
        }

        stopCamera();
        state.paused = false;

        try {
            state.stream = await getCameraStream();
            video.srcObject = state.stream;
            await video.play();
            setUiState('Skanowanie', `Tryb: ${modeLabel(state.mode)}. Czekam na kod QR.`, 'text-bg-success');
            scheduleScan(250);
        } catch (error) {
            stopCamera();

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

    sessionSummaryButton?.addEventListener('click', () => {
        void generateSessionSummary();
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
        if (document.hidden) {
            pauseScanner();
            stopCamera();
            return;
        }

        if (!state.weightContext) {
            void startScanner();
        }
    });

    window.addEventListener('beforeunload', () => {
        stopCamera();
    });

    setMode(state.mode);
    renderActivityList();
    void startScanner();
};

document.addEventListener('DOMContentLoaded', initQrScanner);
