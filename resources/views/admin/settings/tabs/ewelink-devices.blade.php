<div class="tab-pane fade @if($vm->activeTab==='ewelink-devices') show active @endif" id="tab-ewelink-devices" role="tabpanel">
    <div class="card cardopacity">
        <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
            <span>eWeLink: konfiguracja urzadzen</span>
            <div class="d-flex flex-wrap gap-2">
                <form method="POST" action="{{ route('admin.settings.ewelink-devices.sync') }}">
                    @csrf
                    <button class="btn btn-sm btn-success" type="submit">Pobierz dane z API</button>
                </form>
            </div>
        </div>
        <div class="card-body border-bottom border-secondary-subtle">
            <div class="small text-muted mb-2">
                Callback OAuth do ustawienia w eWeLink Developer Center:
                <code>{{ config('services.ewelink.redirect_url') ?: route('panel.devices.callback') }}</code>
            </div>
            <form class="row g-2 align-items-end" method="POST" action="{{ route('admin.settings.ewelink-devices.store') }}">
                @csrf
                <div class="col-md-3">
                    <label class="form-label small text-muted mb-1">Device ID</label>
                    <input type="text" name="device_id" class="form-control form-control-sm bg-dark text-light" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label small text-muted mb-1">Typ</label>
                    <select name="device_type" class="form-select form-select-sm bg-dark text-light">
                        <option value="switch">Przelacznik</option>
                        <option value="thermostat">Termostat</option>
                        <option value="thermostat_hygrostat">Termostat + Higrostat</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label small text-muted mb-1">Opis</label>
                    <input type="text" name="description" class="form-control form-control-sm bg-dark text-light">
                </div>
                <div class="col-md-2 text-end">
                    <button class="btn btn-sm btn-primary w-100" type="submit">Dodaj</button>
                </div>
            </form>
            <div class="small text-muted mt-2">Nazwa przy dodawaniu pobiera sie automatycznie z eWeLink API.</div>
        </div>
        <div class="table-responsive">
            <table class="table table-dark table-sm align-middle mb-0">
                <thead>
                    <tr>
                        <th>ID urzadzenia</th>
                        <th>Nazwa / opis</th>
                        <th>Typ</th>
                        <th>Online</th>
                        <th>Temp.</th>
                        <th>Wilg.</th>
                        <th>ON/OFF</th>
                        <th>Przelaczniki</th>
                        <th>Zakres / cel</th>
                        <th>Harmonogram (Warszawa)</th>
                        <th>Ostatnia synchronizacja</th>
                        <th class="text-end">Opcje</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($vm->ewelinkDevices as $row)
                        @php
                            /** @var \App\Models\EwelinkDevice $device */
                            $device = $row['device'];
                            $snapshot = $row['snapshot'];
                        @endphp
                        <tr>
                            <td class="text-break">{{ $device->device_id }}</td>
                            <td>
                                <div>{{ $device->name }}</div>
                                @if($device->description)
                                    <small class="text-muted">{{ $device->description }}</small>
                                @endif
                            </td>
                            <td>{{ $device->device_type }}</td>
                            <td>{{ $snapshot['online'] }}</td>
                            <td>{{ $snapshot['temperature'] }}</td>
                            <td>{{ $snapshot['humidity'] }}</td>
                            <td>
                                @php $switchState = strtolower((string) $snapshot['switch']); @endphp
                                @if ($switchState === 'on')
                                    <span class="fw-bold text-success">ON</span>
                                @elseif ($switchState === 'off')
                                    <span class="fw-bold text-danger">OFF</span>
                                @elseif ($switchState === 'mixed')
                                    <span class="fw-bold text-warning">MIXED</span>
                                @else
                                    <span class="text-muted">{{ $snapshot['switch'] }}</span>
                                @endif
                            </td>
                            <td class="text-break">
                                @if (!empty($snapshot['switch_states']))
                                    @foreach ($snapshot['switch_states'] as $channel => $state)
                                        @php $stateValue = strtolower((string) $state); @endphp
                                        <span class="me-2">
                                            <span class="text-muted">ch{{ $channel }}:</span>
                                            @if ($stateValue === 'on')
                                                <span class="fw-bold text-success">ON</span>
                                            @elseif ($stateValue === 'off')
                                                <span class="fw-bold text-danger">OFF</span>
                                            @else
                                                <span class="fw-bold text-warning">{{ strtoupper($stateValue) }}</span>
                                            @endif
                                        </span>
                                    @endforeach
                                @else
                                    {{ $snapshot['switches'] }}
                                @endif
                            </td>
                            <td>{{ $snapshot['target_temperature'] }}</td>
                            <td class="text-break">
                                @if (!empty($snapshot['schedule_lines']))
                                    @foreach (array_slice($snapshot['schedule_lines'], 0, 4) as $line)
                                        <div class="small">{{ $line }}</div>
                                    @endforeach
                                    @if (count($snapshot['schedule_lines']) > 4)
                                        <div class="small text-muted">+{{ count($snapshot['schedule_lines']) - 4 }} wiecej</div>
                                    @endif
                                @else
                                    {{ $snapshot['schedule'] }}
                                @endif
                            </td>
                            <td>
                                {{ $device->last_synced_at?->format('Y-m-d H:i:s') ?? '-' }}
                                @if($device->last_error)
                                    <div class="small text-warning mt-1">{{ $device->last_error }}</div>
                                @endif
                            </td>
                            <td class="text-end">
                                <form method="POST" action="{{ route('admin.settings.ewelink-devices.destroy', $device) }}" onsubmit="return confirm('Usunac urzadzenie?')" class="d-inline">
                                    @csrf
                                    @method('DELETE')
                                    <button class="btn btn-sm btn-outline-danger">Usun</button>
                                </form>
                            </td>
                        </tr>
                        <tr>
                            <td colspan="12">
                                <form class="row g-1 align-items-center" method="POST" action="{{ route('admin.settings.ewelink-devices.update', $device) }}">
                                    @csrf
                                    @method('PATCH')
                                    <div class="col-md-2">
                                        <input type="text" name="device_id" class="form-control form-control-sm bg-dark text-light" value="{{ $device->device_id }}" required>
                                    </div>
                                    <div class="col-md-2">
                                        <input type="text" name="name" class="form-control form-control-sm bg-dark text-light" value="{{ $device->name }}" required>
                                    </div>
                                    <div class="col-md-2">
                                        <select name="device_type" class="form-select form-select-sm bg-dark text-light">
                                            <option value="switch" @selected($device->device_type === 'switch')>Przelacznik</option>
                                            <option value="thermostat" @selected($device->device_type === 'thermostat')>Termostat</option>
                                            <option value="thermostat_hygrostat" @selected($device->device_type === 'thermostat_hygrostat')>Termostat + Higrostat</option>
                                        </select>
                                    </div>
                                    <div class="col-md-4">
                                        <input type="text" name="description" class="form-control form-control-sm bg-dark text-light" value="{{ $device->description }}">
                                    </div>
                                    <div class="col-md-2 text-end">
                                        <button class="btn btn-sm btn-outline-light">Zapisz</button>
                                    </div>
                                </form>
                            </td>
                        </tr>
                        <tr>
                            <td colspan="12">
                                <details>
                                    <summary class="small text-muted">Surowe parametry API</summary>
                                    <pre class="small mb-0 mt-2">{{ $snapshot['params_json'] }}</pre>
                                </details>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="12" class="text-center text-muted">Brak skonfigurowanych urzadzen eWeLink.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
