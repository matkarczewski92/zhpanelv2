@extends('layouts.panel')

@section('title', $vm->title)

@section('content')
    <div class="card mb-3">
        <div class="card-header d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3">
            <div>
                <div class="fw-semibold">{{ $vm->title }}</div>
                <div class="small text-muted">Lista miotow z eksportem danych do etykiet.</div>
            </div>
            <form id="adminLitterLabelForm" method="POST" action="{{ $vm->exportUrl }}">
                @csrf
                <input type="hidden" name="litter_ids" id="adminLitterIds" />
                <button type="button" class="btn btn-outline-primary btn-sm" id="adminLitterExportBtn">
                    Eksportuj wybrane etykiety
                </button>
            </form>
        </div>
        <div class="card-body border-bottom" style="border-color: rgba(255,255,255,0.08) !important;">
            <form method="GET" action="{{ route('admin.labels.litters.print') }}" class="d-flex flex-column gap-3">
                <div>
                    <div class="small text-uppercase text-muted mb-2">Kategoria</div>
                    <div class="d-flex flex-wrap gap-3">
                        @foreach ($vm->categories as $category)
                            <label class="form-check-label d-inline-flex align-items-center gap-2">
                                <input
                                    type="checkbox"
                                    class="form-check-input mt-0"
                                    name="category_ids[]"
                                    value="{{ $category['id'] }}"
                                    @checked(in_array($category['id'], $vm->selectedCategoryIds, true))
                                >
                                <span>{{ $category['id'] }} - {{ $category['name'] }}</span>
                            </label>
                        @endforeach
                    </div>
                </div>
                <div>
                    <button type="submit" class="btn btn-outline-light btn-sm">Filtruj</button>
                </div>
            </form>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-sm mb-0">
                    <thead>
                        <tr class="text-muted small">
                            <th style="width:40px"><input type="checkbox" class="form-check-input" id="adminLitterSelectAll"></th>
                            <th>ID</th>
                            <th>Kod miotu</th>
                            <th>Sezon</th>
                            <th>Kategoria</th>
                            <th>Data laczenia</th>
                            <th>Data zniosu</th>
                            <th>Planowana data wyklucia</th>
                            <th>Ilosc zniesionych jaj</th>
                            <th>Ilosc jaj do inkubacji</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($vm->litters as $litter)
                            <tr>
                                <td><input type="checkbox" class="form-check-input admin-litter-row" value="{{ $litter['id'] }}"></td>
                                <td>{{ $litter['id'] }}</td>
                                <td>{{ $litter['litter_code'] }}</td>
                                <td>{{ $litter['season'] ?? '-' }}</td>
                                <td>{{ $litter['category_id'] }} - {{ $litter['category_name'] }}</td>
                                <td>{{ $litter['connection_date'] ?? '-' }}</td>
                                <td>{{ $litter['laying_date'] ?? '-' }}</td>
                                <td>{{ $litter['planned_hatching_date'] ?? '-' }}</td>
                                <td>{{ $litter['laying_eggs_total'] ?? '-' }}</td>
                                <td>{{ $litter['laying_eggs_ok'] ?? '-' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="10" class="text-center text-muted">Brak miotow dla wybranego filtra.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const selectAll = document.getElementById('adminLitterSelectAll');
            const rows = document.querySelectorAll('.admin-litter-row');
            const btn = document.getElementById('adminLitterExportBtn');
            const hidden = document.getElementById('adminLitterIds');
            const form = document.getElementById('adminLitterLabelForm');

            const toast = (msg, type = 'info') => {
                const container = document.getElementById('globalToastContainer');
                const wrapper = document.createElement('div');
                const cls = {
                    success: 'bg-success text-white',
                    danger: 'bg-danger text-white',
                    warning: 'bg-warning text-dark',
                    info: 'bg-info text-white',
                }[type] || 'bg-info text-white';
                wrapper.className = `toast align-items-center ${cls}`;
                wrapper.setAttribute('role', 'alert');
                wrapper.setAttribute('aria-live', 'assertive');
                wrapper.setAttribute('aria-atomic', 'true');
                wrapper.innerHTML = `<div class="d-flex"><div class="toast-body">${msg}</div><button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button></div>`;
                container.appendChild(wrapper);
                const t = new bootstrap.Toast(wrapper, { delay: 5000, autohide: true });
                t.show();
            };

            selectAll.addEventListener('change', () => {
                rows.forEach(cb => cb.checked = selectAll.checked);
            });

            btn.addEventListener('click', () => {
                const ids = Array.from(rows).filter(cb => cb.checked).map(cb => cb.value);
                if (ids.length === 0) {
                    toast('Zaznacz przynajmniej jeden miot.', 'warning');
                    return;
                }
                hidden.value = ids.join(',');
                form.submit();
            });
        });
    </script>
@endpush
