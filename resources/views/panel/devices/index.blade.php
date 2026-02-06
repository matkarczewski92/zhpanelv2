@extends('layouts.panel')

@section('title', 'Urzadzenia')

@section('content')
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
        <div>
            <h1 class="h4 mb-1">Urzadzenia</h1>
            <p class="text-muted mb-0">Podglad danych z eWeLink dla urzadzen skonfigurowanych w Ustawieniach portalu.</p>
        </div>
        <div class="d-flex flex-wrap gap-2">
            <a href="{{ route('panel.devices.authorize') }}" class="btn btn-outline-light btn-sm">Polacz konto eWeLink (backend)</a>
            <a href="{{ route('panel.devices.authorize', ['flow' => 'oauth']) }}" class="btn btn-outline-secondary btn-sm">Polacz przez strone OAuth</a>
            <form method="POST" action="{{ route('panel.devices.refresh') }}">
                @csrf
                <button type="submit" class="btn btn-success btn-sm">Odswiez dane</button>
            </form>
        </div>
    </div>

    <div class="glass-card mb-3">
        <div class="card-body d-flex flex-wrap align-items-center gap-3 small">
            <div>
                <span class="text-muted">Stan autoryzacji:</span>
                <strong>{{ $hasToken ? 'polaczono' : 'brak tokenu' }}</strong>
            </div>
            <div>
                <span class="text-muted">Region:</span>
                <strong>{{ $savedRegion ?: '-' }}</strong>
            </div>
            <div class="w-100 text-muted">
                Redirect URL (musi byc 1:1 w eWeLink): <code>{{ config('services.ewelink.redirect_url') ?: route('panel.devices.callback') }}</code>
            </div>
        </div>
    </div>

    <div class="glass-card glass-table-wrapper">
        <div class="card-header">
            <div class="strike"><span>Lista urzadzen</span></div>
        </div>
        <div class="table-responsive">
            <table class="table glass-table table-sm align-middle mb-0">
                <thead>
                    <tr class="text-muted small">
                        <th>ID urzadzenia</th>
                        <th>Nazwa</th>
                        <th>Typ</th>
                        <th>Online</th>
                        <th>Temperatura</th>
                        <th>Wilgotnosc</th>
                        <th>Stan ON/OFF</th>
                        <th>Zakres / cel</th>
                        <th>Harmonogram</th>
                        <th>Ostatnia synchronizacja</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($rows as $row)
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
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10" class="text-center text-muted">Brak skonfigurowanych urzadzen. Dodaj je w Ustawieniach portalu.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
