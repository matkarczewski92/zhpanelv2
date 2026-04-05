@extends('layouts.panel')

@section('title', 'Inkubacja')

@section('content')
    @php
        $incubator = $page['incubator'] ?? [];
        $rows = $page['rows'] ?? [];
    @endphp

    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
        <div>
            <h1 class="h4 mb-1">Inkubacja</h1>
            <p class="text-muted mb-0">Aktywna inkubacja z szybkim podgladem etapow dla wszystkich miotow.</p>
        </div>
        <div class="small text-muted">Pozycji: {{ count($rows) }}</div>
    </div>

    <div class="glass-card mb-3 incubation-status-bar">
        <div class="card-body d-flex flex-wrap align-items-center gap-4">
            <div class="d-flex flex-column">
                <div class="small text-muted text-uppercase">Inkubator</div>
                @if (!empty($incubator['found']))
                    <div class="fw-semibold">{{ $incubator['device_name'] }}</div>
                    <div class="small text-muted">Online: {{ $incubator['online'] }} | Ostatnia synchronizacja: {{ $incubator['last_synced_at'] }}</div>
                @else
                    <div class="fw-semibold">{{ $incubator['message'] ?? 'Brak danych inkubatora.' }}</div>
                @endif
            </div>

            @if (!empty($incubator['found']))
                <div class="d-flex flex-wrap align-items-center gap-4 ms-auto">
                    <div class="incubation-status-metric">
                        <i class="bi {{ $incubator['status_icon'] ?? 'bi-question-circle' }} incubation-status-icon {{ $incubator['status_class'] ?? 'text-muted' }}"></i>
                        <div>
                            <div class="small text-muted">Stan</div>
                            <div class="fw-semibold {{ $incubator['status_class'] ?? 'text-muted' }}">{{ $incubator['status_label'] ?? '-' }}</div>
                        </div>
                    </div>
                    <div class="incubation-status-metric">
                        <i class="bi bi-thermometer-half incubation-status-icon text-warning"></i>
                        <div>
                            <div class="small text-muted">Temperatura</div>
                            <div class="fw-semibold">{{ $incubator['temperature'] ?? '-' }}</div>
                        </div>
                    </div>
                    <div class="incubation-status-metric">
                        <i class="bi bi-droplet-half incubation-status-icon text-info"></i>
                        <div>
                            <div class="small text-muted">Wilgotnosc</div>
                            <div class="fw-semibold">{{ $incubator['humidity'] ?? '-' }}</div>
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </div>

    @forelse ($rows as $row)
        <div class="card cardopacity mb-3 pregnancy-timeline-card">
            <div class="card-header d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-2">
                <div>
                    <a href="{{ $row['litter']['profile_url'] }}" class="fw-semibold link-reset">
                        {{ $row['litter']['code'] }}
                        @if (!empty($row['litter']['season']))
                            <span class="text-muted small">/ sezon {{ $row['litter']['season'] }}</span>
                        @endif
                    </a>
                    <div class="text-muted small">
                        Samica: {{ $row['litter']['female_name'] }} | Samiec: {{ $row['litter']['male_name'] }}
                    </div>
                    <div class="text-muted small">
                        Jaja do inkubacji: {{ $row['litter']['eggs_to_incubation_label'] }} | Wyklute: {{ $row['litter']['hatching_eggs_label'] }}
                    </div>
                </div>
                <a href="{{ $row['litter']['profile_url'] }}" class="btn btn-outline-light btn-sm">Profil</a>
            </div>
            <div class="card-body">
                @include('panel.litters.partials.incubation-timeline-items', [
                    'timeline' => $row['timeline'],
                ])
            </div>
        </div>
    @empty
        <div class="card cardopacity">
            <div class="card-body text-center text-muted">Brak aktywnej inkubacji.</div>
        </div>
    @endforelse
@endsection

@push('styles')
    <style>
        .incubation-status-bar .card-body {
            background: transparent;
        }

        .incubation-status-metric {
            display: inline-flex;
            align-items: center;
            gap: 0.9rem;
            min-width: 180px;
        }

        .incubation-status-icon {
            font-size: 1.8rem;
            line-height: 1;
        }
    </style>
@endpush

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach((el) => {
                bootstrap.Tooltip.getOrCreateInstance(el, {
                    container: 'body',
                    trigger: 'hover focus',
                });
            });
        });
    </script>
@endpush
