@extends('layouts.panel')

@section('title', 'Urzadzenia')

@section('content')
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
        <div>
            <h1 class="h4 mb-1">Urzadzenia</h1>
            <p class="text-muted mb-0">Podglad danych z eWeLink Cloud API (SONOFF).</p>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-12 col-xl-5">
            <div class="glass-card">
                <div class="card-header">
                    <div class="strike"><span>Pobierz dane</span></div>
                </div>
                <div class="card-body">
                    <form method="GET" action="{{ route('panel.devices.index') }}" class="d-flex flex-column gap-3">
                        <div>
                            <label class="form-label small text-muted mb-1" for="deviceSelect">Urzadzenie</label>
                            <select id="deviceSelect" name="device" class="form-select">
                                @foreach ($page['devices'] as $serial)
                                    <option value="{{ $serial }}" @selected($serial === $page['selected_device'])>{{ $serial }}</option>
                                @endforeach
                            </select>
                        </div>
                        <button type="submit" class="btn btn-primary">Odswiez</button>
                    </form>
                </div>
            </div>

            <div class="glass-card mt-4">
                <div class="card-header">
                    <div class="strike"><span>Konfiguracja</span></div>
                </div>
                <div class="card-body small">
                    <div><span class="text-muted">eWeLink Cloud region:</span> {{ $page['cloud']['region'] }}</div>
                    <div><span class="text-muted">Cloud API URL:</span> {{ $page['cloud']['base_url'] !== '' ? $page['cloud']['base_url'] : '-' }}</div>
                    <div><span class="text-muted">Area code:</span> {{ $page['cloud']['area_code'] }}</div>
                    <div><span class="text-muted">Cloud login:</span> {{ $page['cloud']['email'] }}</div>
                    <div><span class="text-muted">App ID:</span> @if($page['cloud']['app_id_configured']) <span class="badge text-bg-success">ustawiony</span> @else <span class="badge text-bg-warning text-dark">brak</span> @endif</div>
                    <div><span class="text-muted">App Secret:</span> @if($page['cloud']['app_secret_configured']) <span class="badge text-bg-success">ustawiony</span> @else <span class="badge text-bg-warning text-dark">brak</span> @endif</div>
                    <div><span class="text-muted">Haslo Cloud:</span> @if($page['cloud']['password_configured']) <span class="badge text-bg-success">ustawione</span> @else <span class="badge text-bg-warning text-dark">brak</span> @endif</div>
                    <div><span class="text-muted">OAuth code:</span> @if($page['cloud']['oauth_code_configured']) <span class="badge text-bg-success">ustawiony</span> @else <span class="badge text-bg-warning text-dark">brak</span> @endif</div>
                    <div><span class="text-muted">Access token:</span> @if($page['cloud']['access_token_configured']) <span class="badge text-bg-success">ustawiony</span> @else <span class="badge text-bg-warning text-dark">brak</span> @endif</div>
                    @if (!empty($page['payloads']['cloud_oauth_authorize_url']))
                        <div class="mt-2"><span class="text-muted">URL autoryzacji:</span> <a href="{{ $page['payloads']['cloud_oauth_authorize_url'] }}" target="_blank" rel="noopener">otworz</a></div>
                    @endif
                    <div><span class="text-muted">Cloud gotowy:</span> @if($page['cloud']['complete']) <span class="badge text-bg-success">tak</span> @else <span class="badge text-bg-warning text-dark">nie</span> @endif</div>
                    @if ($page['error'] !== '')
                        <div class="text-warning mt-2">{{ $page['error'] }}</div>
                    @endif
                </div>
            </div>
        </div>

        <div class="col-12 col-xl-7">
            <div class="glass-card glass-table-wrapper">
                <div class="card-header">
                    <div class="strike"><span>Telemetry</span></div>
                </div>
                <div class="table-responsive">
                    <table class="table glass-table table-sm align-middle mb-0">
                        <tbody>
                            <tr>
                                <th style="width: 240px;">Serial</th>
                                <td>{{ $page['selected_device'] !== '' ? $page['selected_device'] : '-' }}</td>
                            </tr>
                            <tr>
                                <th>Aktualna temperatura</th>
                                <td>
                                    @if ($page['telemetry']['temperature_current'] !== null)
                                        {{ number_format((float) $page['telemetry']['temperature_current'], 2, ',', ' ') }}
                                        {{ $page['telemetry']['temperature_unit'] !== '' ? $page['telemetry']['temperature_unit'] : 'C' }}
                                    @else
                                        -
                                    @endif
                                </td>
                            </tr>
                            <tr>
                                <th>Tryb termostatu</th>
                                <td>{{ $page['telemetry']['thermostat_mode'] !== '' ? $page['telemetry']['thermostat_mode'] : '-' }}</td>
                            </tr>
                            <tr>
                                <th>Zakres docelowy (min / max)</th>
                                <td>
                                    @if ($page['telemetry']['target_low'] !== null || $page['telemetry']['target_high'] !== null)
                                        {{ $page['telemetry']['target_low'] !== null ? number_format((float) $page['telemetry']['target_low'], 2, ',', ' ') : '-' }}
                                        /
                                        {{ $page['telemetry']['target_high'] !== null ? number_format((float) $page['telemetry']['target_high'], 2, ',', ' ') : '-' }}
                                        {{ $page['telemetry']['temperature_unit'] !== '' ? $page['telemetry']['temperature_unit'] : 'C' }}
                                    @else
                                        -
                                    @endif
                                </td>
                            </tr>
                            <tr>
                                <th>Status</th>
                                <td>
                                    @if ($page['ok'])
                                        <span class="badge text-bg-success">OK</span>
                                    @else
                                        <span class="badge text-bg-warning text-dark">Brak danych</span>
                                    @endif
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="glass-card mt-4">
                <div class="card-header">
                    <div class="strike"><span>Debug API</span></div>
                </div>
                <div class="card-body">
                    <details>
                        <summary class="small text-muted">Pokaz surowe odpowiedzi API</summary>
                        <pre class="small mt-2 mb-0" style="white-space: pre-wrap;">{{ json_encode($page['payloads'], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
                    </details>
                </div>
            </div>
        </div>
    </div>
@endsection
