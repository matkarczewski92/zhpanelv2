@php
    $enabled = (bool) ($updatePanel['enabled'] ?? false);
    $processAvailable = (bool) ($updatePanel['process_available'] ?? false);
    $execAvailable = (bool) ($updatePanel['exec_available'] ?? false);
    $commandDriver = (string) ($updatePanel['command_driver'] ?? '');
    $artisanConsoleAvailable = (bool) ($updatePanel['artisan_console_available'] ?? false);
    $gitAvailable = (bool) ($updatePanel['git_available'] ?? false);
    $lastCheck = $updatePanel['last_check'] ?? null;
    $lastRun = $updatePanel['last_run'] ?? null;
    $lastArtisanRun = $updatePanel['last_artisan_run'] ?? null;
    $maintenanceActive = (bool) ($updatePanel['maintenance_active'] ?? false);
    $maintenanceAllowedIps = $updatePanel['maintenance_allowed_ips'] ?? [];
    $lastMaintenanceRun = $updatePanel['last_maintenance_run'] ?? null;
    $logTail = (string) ($updatePanel['log_tail'] ?? '');
    $artisanRestrictions = $artisanRestrictions ?? [];
    $availableCommands = $artisanRestrictions['available_commands'] ?? [];
    $confirmCommands = $artisanRestrictions['confirm_commands'] ?? [];
    $blockedCommands = $artisanRestrictions['blocked_commands'] ?? [];
@endphp

<div class="tab-pane fade @if($vm->activeTab==='update') show active @endif" id="tab-update" role="tabpanel">
    <div class="card cardopacity">
        <div class="card-header d-flex justify-content-between align-items-center">
            <span>Aktualizacja systemu</span>
            <span class="badge text-bg-secondary">GitHub deploy</span>
        </div>

        <div class="card-body">
            @if(!$enabled)
                <div class="alert alert-warning mb-0">
                    Updater jest wylaczony. Ustaw <code>PORTAL_UPDATE_ENABLED=true</code> w pliku <code>.env</code>.
                </div>
            @elseif(!$processAvailable && !$execAvailable)
                <div class="alert alert-danger mb-0">
                    Na tym serwerze PHP ma wylaczone <code>proc_open</code> i <code>exec</code>. Automatyczna aktualizacja z panelu jest niedostepna.
                </div>
            @elseif(!$gitAvailable)
                <div class="alert alert-danger mb-0">
                    Ten serwer nie ma poprawnego repozytorium Git. Automatyczna aktualizacja jest niedostepna.
                </div>
            @else
                @if($commandDriver === 'exec')
                    <div class="alert alert-warning mb-3">
                        Updater dziala w trybie awaryjnym <code>exec</code> (bez <code>proc_open</code>).
                    </div>
                @endif

                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <div class="small text-muted">Repozytorium</div>
                        <div class="fw-semibold">
                            {{ $updatePanel['github_repo'] ?? '-' }}
                        </div>
                        @if(!empty($updatePanel['remote_url']))
                            <div class="small text-muted text-break">{{ $updatePanel['remote_url'] }}</div>
                        @endif
                    </div>
                    <div class="col-md-3">
                        <div class="small text-muted">Remote / branch</div>
                        <div class="fw-semibold">{{ $updatePanel['remote'] ?? '-' }} / {{ $updatePanel['branch'] ?? '-' }}</div>
                    </div>
                    <div class="col-md-3">
                        <div class="small text-muted">Biezacy commit</div>
                        <div class="fw-semibold font-monospace">{{ $updatePanel['local_sha_short'] ?? '-' }}</div>
                    </div>
                    <div class="col-md-3">
                        <div class="small text-muted">Silnik komend</div>
                        <div class="fw-semibold">{{ $commandDriver !== '' ? $commandDriver : '-' }}</div>
                        <div class="small text-muted text-break mt-1">PHP CLI: {{ $updatePanel['php_cli_binary'] ?? '-' }}</div>
                    </div>
                </div>

                <div class="d-flex flex-wrap gap-2 align-items-center mb-4">
                    <form method="POST" action="{{ route('admin.settings.update.check') }}" class="d-inline">
                        @csrf
                        <button type="submit" class="btn btn-outline-light btn-sm">Sprawdz aktualizacje</button>
                    </form>

                    <form method="POST" action="{{ route('admin.settings.update.run') }}" onsubmit="return confirm('Uruchomic aktualizacje na serwerze?')" class="d-flex flex-wrap gap-3 align-items-center">
                        @csrf
                        <div class="form-check form-check-inline m-0">
                            <input class="form-check-input" type="checkbox" id="run_migrate" name="run_migrate" value="1" checked>
                            <label class="form-check-label small" for="run_migrate">migrate</label>
                        </div>
                        <div class="form-check form-check-inline m-0">
                            <input class="form-check-input" type="checkbox" id="run_build" name="run_build" value="1" checked>
                            <label class="form-check-label small" for="run_build">npm run build</label>
                        </div>
                        <button type="submit" class="btn btn-danger btn-sm">Aktualizuj teraz</button>
                    </form>
                </div>

                @if(is_array($lastCheck))
                    <div class="alert alert-secondary mb-3">
                        <div class="fw-semibold mb-1">Ostatnie sprawdzenie: {{ $lastCheck['checked_at'] ?? '-' }}</div>
                        <div class="small">
                            Local: <span class="font-monospace">{{ $lastCheck['local_sha_short'] ?? '-' }}</span>,
                            Remote: <span class="font-monospace">{{ $lastCheck['remote_sha_short'] ?? '-' }}</span>,
                            Ahead: {{ $lastCheck['ahead'] ?? 0 }},
                            Behind: {{ $lastCheck['behind'] ?? 0 }}
                        </div>
                        <div class="small mt-1">
                            @if(!empty($lastCheck['has_updates']))
                                Dostepna jest nowa aktualizacja.
                            @else
                                Brak nowych commitow.
                            @endif
                        </div>
                    </div>
                @endif

                @if(is_array($lastRun))
                    <div class="alert @if(!empty($lastRun['success'])) alert-success @else alert-danger @endif mb-3">
                        <div class="fw-semibold mb-1">Ostatnia aktualizacja: {{ $lastRun['finished_at'] ?? '-' }}</div>
                        <div class="small">
                            Status: @if(!empty($lastRun['success'])) sukces @else blad @endif,
                            Commit: <span class="font-monospace">{{ $lastRun['before_sha_short'] ?? '-' }}</span>
                            ->
                            <span class="font-monospace">{{ $lastRun['after_sha_short'] ?? '-' }}</span>,
                            Updated: @if(!empty($lastRun['updated'])) tak @else nie @endif
                        </div>
                        @if(!empty($lastRun['error']))
                            <div class="small mt-1">Blad: {{ $lastRun['error'] }}</div>
                        @endif
                    </div>
                @endif

                <div>
                    <div class="small text-muted mb-2">Log aktualizacji (tail)</div>
                    <pre class="bg-black border rounded p-3 text-light small mb-0" style="max-height: 360px; overflow:auto; white-space: pre-wrap;">{{ $logTail !== '' ? $logTail : 'Brak logow aktualizacji.' }}</pre>
                </div>
            @endif
        </div>
    </div>

    <div class="card cardopacity mt-3">
        <div class="card-header d-flex justify-content-between align-items-center">
            <span>Maintenance mode</span>
            <span class="badge {{ $maintenanceActive ? 'text-bg-warning text-dark' : 'text-bg-secondary' }}">
                {{ $maintenanceActive ? 'AKTYWNY' : 'NIEAKTYWNY' }}
            </span>
        </div>

        <div class="card-body">
            @if(!$artisanConsoleAvailable)
                <div class="alert alert-danger mb-0">
                    Na tym serwerze PHP ma wylaczone <code>proc_open</code> i <code>exec</code>. Sterowanie maintenance mode jest niedostepne.
                </div>
            @else
                <div class="row g-3 align-items-start">
                    <div class="col-lg-8">
                        <div class="small text-muted mb-1">Status</div>
                        <div class="fw-semibold mb-2">
                            {{ $maintenanceActive ? 'Aplikacja dziala w maintenance mode.' : 'Aplikacja dziala normalnie.' }}
                        </div>

                        <div class="small text-muted mb-1">Przepuszczone IP</div>
                        <div class="font-monospace mb-3">
                            {{ count($maintenanceAllowedIps) ? implode(', ', $maintenanceAllowedIps) : 'Brak whitelisty IP.' }}
                        </div>

                        @if(is_array($lastMaintenanceRun))
                            <div class="alert @if(!empty($lastMaintenanceRun['success'])) alert-success @else alert-danger @endif mb-0">
                                <div class="fw-semibold mb-1">Ostatnia operacja: {{ $lastMaintenanceRun['finished_at'] ?? '-' }}</div>
                                <div class="small">
                                    Akcja: <span class="font-monospace">{{ $lastMaintenanceRun['action'] ?? '-' }}</span>
                                    @if(!empty($lastMaintenanceRun['allowed_ip']))
                                        , IP: <span class="font-monospace">{{ $lastMaintenanceRun['allowed_ip'] }}</span>
                                    @endif
                                </div>
                            </div>
                        @endif
                    </div>

                    <div class="col-lg-4">
                        <div class="d-grid gap-2">
                            <button
                                type="button"
                                class="btn btn-warning"
                                data-bs-toggle="modal"
                                data-bs-target="#maintenanceModeModal"
                            >
                                Maintenance mode ON
                            </button>

                            <form method="POST" action="{{ route('admin.settings.update.maintenance.off') }}" onsubmit="return confirm('Wylaczyc maintenance mode?')">
                                @csrf
                                <button type="submit" class="btn btn-outline-light w-100" @if(!$maintenanceActive) disabled @endif>
                                    Maintenance mode OFF
                                </button>
                            </form>
                        </div>

                        <p class="text-muted small mt-3 mb-0">
                            Po kliknieciu <code>ON</code> pojawi sie okno z prosba o IP, ktore ma zachowac dostep do panelu podczas maintenance.
                        </p>
                    </div>
                </div>
            @endif
        </div>
    </div>

    <div class="card cardopacity mt-3">
        <div class="card-header d-flex justify-content-between align-items-center">
            <span>Konsola artisan</span>
            <span class="badge text-bg-secondary">php artisan</span>
        </div>

        <div class="card-body">
            @if(!$artisanConsoleAvailable)
                <div class="alert alert-danger mb-0">
                    Na tym serwerze PHP ma wylaczone <code>proc_open</code> i <code>exec</code>. Konsola artisan jest niedostepna.
                </div>
            @else
                <p class="text-muted small mb-3">
                    Wpisz sama komende (np. <code>cache:clear</code>) albo caly prefiks <code>php artisan ...</code>.
                    Formularz i tak uruchamia wylacznie polecenia artisan po stronie serwera.
                </p>

                <form method="POST" action="{{ route('admin.settings.update.artisan') }}" class="mb-3">
                    @csrf
                    <input type="hidden" name="confirmed" id="artisanCommandConfirmed" value="0">
                    <div class="input-group">
                        <span class="input-group-text bg-dark text-light border-secondary font-monospace">php artisan</span>
                        <input
                            type="text"
                            id="artisanCommandInput"
                            name="command"
                            class="form-control bg-dark text-light border-secondary font-monospace"
                            value="{{ old('command', is_array($lastArtisanRun) ? ($lastArtisanRun['input'] ?? '') : '') }}"
                            placeholder="np. cache:clear albo route:list"
                            required
                        >
                        <button type="submit" class="btn btn-outline-light">Uruchom</button>
                    </div>
                    @error('command')
                        <div class="text-danger small mt-2">{{ $message }}</div>
                    @enderror
                </form>

                <div class="small text-muted mb-3">
                    Silnik komend: <span class="fw-semibold">{{ $commandDriver !== '' ? $commandDriver : '-' }}</span>,
                    PHP CLI: <span class="font-monospace">{{ $updatePanel['php_cli_binary'] ?? '-' }}</span>
                </div>

                <div class="row g-3 mb-3">
                    <div class="col-lg-4">
                        <div class="small text-muted mb-2">Dozwolone</div>
                        <div class="small bg-black border rounded p-3 h-100">
                            {{ count($availableCommands) ? implode(', ', $availableCommands) : 'Brak.' }}
                        </div>
                    </div>
                    <div class="col-lg-4">
                        <div class="small text-muted mb-2">Wymagaja potwierdzenia</div>
                        <div class="small bg-black border rounded p-3 h-100">
                            {{ count($confirmCommands) ? implode(', ', $confirmCommands) : 'Brak.' }}
                        </div>
                    </div>
                    <div class="col-lg-4">
                        <div class="small text-muted mb-2">Zablokowane</div>
                        <div class="small bg-black border rounded p-3 h-100">
                            {{ count($blockedCommands) ? implode(', ', $blockedCommands) : 'Brak.' }}
                        </div>
                    </div>
                </div>

                @if(is_array($lastArtisanRun))
                    <div class="alert @if(!empty($lastArtisanRun['success'])) alert-success @else alert-danger @endif mb-3">
                        <div class="fw-semibold mb-1">Ostatnie uruchomienie: {{ $lastArtisanRun['finished_at'] ?? '-' }}</div>
                        <div class="small">
                            Komenda: <span class="font-monospace">{{ $lastArtisanRun['command_display'] ?? '-' }}</span>
                        </div>
                        <div class="small mt-1">
                            Status: @if(!empty($lastArtisanRun['success'])) sukces @else blad @endif,
                            Exit code: {{ $lastArtisanRun['exit_code'] ?? '-' }},
                            Czas: {{ $lastArtisanRun['duration_ms'] ?? 0 }} ms
                        </div>
                    </div>

                    <div>
                        <div class="small text-muted mb-2">Output komendy</div>
                        <pre class="bg-black border rounded p-3 text-light small mb-0" style="max-height: 360px; overflow:auto; white-space: pre-wrap;">{{ ($lastArtisanRun['output'] ?? '') !== '' ? $lastArtisanRun['output'] : 'Brak outputu.' }}</pre>
                    </div>
                @endif
            @endif
        </div>
    </div>

    <div class="modal fade" id="maintenanceModeModal" tabindex="-1" aria-labelledby="maintenanceModeModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content bg-dark text-light border-secondary">
                <div class="modal-header border-secondary">
                    <h5 class="modal-title" id="maintenanceModeModalLabel">Maintenance mode ON</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Zamknij"></button>
                </div>
                <form method="POST" action="{{ route('admin.settings.update.maintenance.on') }}">
                    @csrf
                    <div class="modal-body">
                        <label for="maintenanceAllowedIp" class="form-label">Adres IP, ktory ma zachowac dostep</label>
                        <input
                            type="text"
                            id="maintenanceAllowedIp"
                            name="allowed_ip"
                            class="form-control bg-dark text-light border-secondary font-monospace"
                            value="{{ old('allowed_ip', request()->ip()) }}"
                            placeholder="np. 83.8.224.29"
                            required
                        >
                        @error('allowed_ip')
                            <div class="text-danger small mt-2">{{ $message }}</div>
                        @enderror
                        <div class="small text-muted mt-3">
                            To IP bedzie przepuszczone przez maintenance mode. Pozostali uzytkownicy zobacza strone 503.
                        </div>
                    </div>
                    <div class="modal-footer border-secondary">
                        <button type="button" class="btn btn-outline-light" data-bs-dismiss="modal">Anuluj</button>
                        <button type="submit" class="btn btn-warning">Wlacz maintenance</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const modalElement = document.getElementById('maintenanceModeModal');
            const inputElement = document.getElementById('maintenanceAllowedIp');
            const artisanCommandInput = document.getElementById('artisanCommandInput');
            const artisanCommandConfirmed = document.getElementById('artisanCommandConfirmed');
            const artisanCommandForm = artisanCommandInput?.closest('form');
            const confirmCommands = @json(array_values($confirmCommands));

            if (!modalElement || !inputElement) {
                if (!artisanCommandForm || !artisanCommandInput || !artisanCommandConfirmed) {
                    return;
                }
            }

            const normalizeArtisanCommand = (value) => {
                const trimmed = value.trim();

                if (trimmed === '') {
                    return '';
                }

                const parts = trimmed.match(/"([^"\\]|\\.)*"|'([^'\\]|\\.)*'|\S+/g) ?? [];
                const cleaned = parts.map((part) => {
                    if ((part.startsWith('"') && part.endsWith('"')) || (part.startsWith("'") && part.endsWith("'"))) {
                        return part.slice(1, -1);
                    }

                    return part;
                });

                if (['php', 'php.exe'].includes((cleaned[0] ?? '').toLowerCase()) && (cleaned[1] ?? '').toLowerCase() === 'artisan') {
                    cleaned.splice(0, 2);
                } else if ((cleaned[0] ?? '').toLowerCase() === 'artisan') {
                    cleaned.splice(0, 1);
                }

                return cleaned.join(' ').trim();
            };

            const commandMatchesPattern = (commandInput, pattern) => {
                return commandInput === pattern || commandInput.startsWith(`${pattern} `);
            };

            if (artisanCommandForm && artisanCommandInput && artisanCommandConfirmed) {
                artisanCommandForm.addEventListener('submit', (event) => {
                    const normalizedCommand = normalizeArtisanCommand(artisanCommandInput.value);
                    const requiresConfirmation = confirmCommands.some((pattern) => commandMatchesPattern(normalizedCommand, pattern));

                    artisanCommandConfirmed.value = '0';

                    if (!requiresConfirmation) {
                        return;
                    }

                    const accepted = window.confirm(`Ta komenda wymaga dodatkowego potwierdzenia:\n\nphp artisan ${normalizedCommand}\n\nKontynuowac?`);
                    if (!accepted) {
                        event.preventDefault();
                        return;
                    }

                    artisanCommandConfirmed.value = '1';
                });
            }

            if (modalElement && inputElement) {
                modalElement.addEventListener('shown.bs.modal', () => {
                    inputElement.focus();
                    inputElement.select();
                });

                @if($errors->has('allowed_ip'))
                    const modalInstance = bootstrap.Modal.getOrCreateInstance(modalElement);
                    modalInstance.show();
                @endif
            }
        });
    </script>
@endpush
