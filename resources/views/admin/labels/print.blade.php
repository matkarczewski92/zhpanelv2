@extends('layouts.panel')

@section('title', $vm->title)

@section('content')
    <div class="card mb-3">
        <div class="card-header d-flex justify-content-between align-items-center">
            <span>{{ $vm->title }} - lista zwierząt (kategorie 1,2,4)</span>
            <form id="adminLabelForm" method="POST" action="{{ $vm->exportUrl }}">
                @csrf
                <input type="hidden" name="animal_ids" id="adminAnimalIds" />
                <button type="button" class="btn btn-outline-primary btn-sm" id="adminExportBtn">Eksportuj wybrane etykiety</button>
            </form>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-sm mb-0">
                    <thead>
                        <tr class="text-muted small">
                            <th style="width:40px"><input type="checkbox" class="form-check-input" id="adminSelectAll"></th>
                            <th>ID</th>
                            <th>Nazwa</th>
                            <th>Typ</th>
                            <th>Płeć</th>
                            <th>Kategoria</th>
                            <th>Kod węża</th>
                            <th>Secret tag</th>
                            <th>Data urodzenia</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($vm->animals as $animal)
                            <tr>
                                <td><input type="checkbox" class="form-check-input admin-row" value="{{ $animal['id'] }}"></td>
                                <td>{{ $animal['id'] }}</td>
                                <td>{{ $animal['name'] }}</td>
                                <td>{{ $animal['type'] }}</td>
                                <td>{{ $animal['sex'] }}</td>
                                <td>{{ $animal['category'] }}</td>
                                <td>{{ $animal['public_profile_tag'] }}</td>
                                <td>{{ $animal['secret_tag'] }}</td>
                                <td>{{ $animal['date_of_birth'] ?? '-' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="text-center text-muted">Brak danych.</td>
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
            const selectAll = document.getElementById('adminSelectAll');
            const rows = document.querySelectorAll('.admin-row');
            const btn = document.getElementById('adminExportBtn');
            const hidden = document.getElementById('adminAnimalIds');
            const form = document.getElementById('adminLabelForm');

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
                    toast('Zaznacz przynajmniej jedno zwierzę.', 'warning');
                    return;
                }
                hidden.value = ids.join(',');
                form.submit();
            });
        });
    </script>
@endpush



