<div class="tab-pane fade @if($vm->activeTab==='ewelink-devices') show active @endif" id="tab-ewelink-devices" role="tabpanel">
    <div class="card cardopacity">
        <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
            <span>eWeLink: konfiguracja urządzeń</span>
            <div class="d-flex flex-wrap gap-2">
                <a class="btn btn-sm btn-outline-light" href="{{ route('panel.devices.authorize') }}">Połącz konto eWeLink</a>
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
                <div class="col-md-2">
                    <label class="form-label small text-muted mb-1">Device ID</label>
                    <input type="text" name="device_id" class="form-control form-control-sm bg-dark text-light" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label small text-muted mb-1">Nazwa</label>
                    <input type="text" name="name" class="form-control form-control-sm bg-dark text-light" required>
                </div>
                <div class="col-md-2">
                    <label class="form-label small text-muted mb-1">Typ</label>
                    <select name="device_type" class="form-select form-select-sm bg-dark text-light">
                        <option value="switch">Przełącznik</option>
                        <option value="thermostat">Termostat</option>
                        <option value="thermostat_hygrostat">Termostat + Higrostat</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label small text-muted mb-1">Opis</label>
                    <input type="text" name="description" class="form-control form-control-sm bg-dark text-light">
                </div>
                <div class="col-md-2 text-end">
                    <button class="btn btn-sm btn-primary w-100" type="submit">Dodaj</button>
                </div>
            </form>
        </div>
        <div class="table-responsive">
            <table class="table table-dark table-sm align-middle mb-0">
                <thead>
                    <tr>
                        <th>ID urządzenia</th>
                        <th>Nazwa / opis</th>
                        <th>Typ</th>
                        <th>Online</th>
                        <th>Temp.</th>
                        <th>Wilg.</th>
                        <th>ON/OFF</th>
                        <th>Zakres / cel</th>
                        <th>Harmonogram</th>
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
                            <td>{{ $snapshot['switch'] }}</td>
                            <td>{{ $snapshot['target_temperature'] }}</td>
                            <td class="text-break">{{ $snapshot['schedule'] }}</td>
                            <td>
                                {{ $device->last_synced_at?->format('Y-m-d H:i:s') ?? '-' }}
                                @if($device->last_error)
                                    <div class="small text-warning mt-1">{{ $device->last_error }}</div>
                                @endif
                            </td>
                            <td class="text-end">
                                <form method="POST" action="{{ route('admin.settings.ewelink-devices.destroy', $device) }}" onsubmit="return confirm('Usunąć urządzenie?')" class="d-inline">
                                    @csrf
                                    @method('DELETE')
                                    <button class="btn btn-sm btn-outline-danger">Usuń</button>
                                </form>
                            </td>
                        </tr>
                        <tr>
                            <td colspan="11">
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
                                            <option value="switch" @selected($device->device_type === 'switch')>Przełącznik</option>
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
                            <td colspan="11">
                                <details>
                                    <summary class="small text-muted">Surowe parametry API</summary>
                                    <pre class="small mb-0 mt-2">{{ $snapshot['params_json'] }}</pre>
                                </details>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="11" class="text-center text-muted">Brak skonfigurowanych urządzeń eWeLink.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
