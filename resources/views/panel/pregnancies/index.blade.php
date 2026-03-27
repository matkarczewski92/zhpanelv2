@extends('layouts.panel')

@section('title', 'Ciezarne samice')

@section('content')
    @php
        $rows = $page['rows'] ?? [];
    @endphp

    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
        <div>
            <h1 class="h4 mb-1">Ciezarne samice</h1>
            <p class="text-muted mb-0">Aktywne ciaze z szybkim podgladem etapow dla wszystkich samic.</p>
        </div>
        <div class="small text-muted">Pozycji: {{ count($rows) }}</div>
    </div>

    @forelse ($rows as $row)
        <div class="card cardopacity mb-3 pregnancy-timeline-card">
            <div class="card-header d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-2">
                <div>
                    <a href="{{ $row['animal']['profile_url'] }}" class="fw-semibold link-reset">
                        {!! $row['animal']['name_display_html'] !!}
                    </a>
                    <div class="text-muted small">{{ $row['animal']['type_name'] ?? '-' }}</div>
                </div>
                <a href="{{ $row['animal']['profile_url'] }}" class="btn btn-outline-light btn-sm">Profil</a>
            </div>
            <div class="card-body">
                @include('panel.animals.partials.pregnancy-timeline-items', [
                    'timeline' => $row['timeline'],
                    'showAddButton' => false,
                ])
            </div>
        </div>
    @empty
        <div class="card cardopacity">
            <div class="card-body text-center text-muted">Brak aktywnych ciaz.</div>
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
