@extends('layouts.panel')

@section('title', 'Inkubacja')

@section('content')
    @php
        $rows = $page['rows'] ?? [];
    @endphp

    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
        <div>
            <h1 class="h4 mb-1">Inkubacja</h1>
            <p class="text-muted mb-0">Aktywna inkubacja z szybkim podgladem etapow dla wszystkich miotow.</p>
        </div>
        <div class="small text-muted">Pozycji: {{ count($rows) }}</div>
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
