@php
    $enabled = (bool) ($updatePanel['enabled'] ?? false);
    $processAvailable = (bool) ($updatePanel['process_available'] ?? false);
    $execAvailable = (bool) ($updatePanel['exec_available'] ?? false);
    $commandDriver = (string) ($updatePanel['command_driver'] ?? '');
    $gitAvailable = (bool) ($updatePanel['git_available'] ?? false);
    $lastCheck = $updatePanel['last_check'] ?? null;
    $lastRun = $updatePanel['last_run'] ?? null;
    $logTail = (string) ($updatePanel['log_tail'] ?? '');
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
                            <label class="form-check-label small" for="run_migrate">migrate --force</label>
                        </div>
                        <div class="form-check form-check-inline m-0">
                            <input class="form-check-input" type="checkbox" id="run_build" name="run_build" value="1" checked>
                            <label class="form-check-label small" for="run_build">npm ci + npm run build</label>
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
</div>
