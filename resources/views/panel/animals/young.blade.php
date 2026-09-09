@extends('layouts.panel')

@section('title', 'Maluchy')

@section('content')
    <div id="young-animals-print">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4 class="mb-0">Maluchy</h4>
        <div class="d-flex align-items-center gap-3">
            <span class="text-muted">Liczba zwierząt: {{ $animals->count() }}</span>
            <button type="button" class="btn btn-outline-light d-print-none" onclick="window.print()">
                <i class="bi bi-printer" aria-hidden="true"></i> Drukuj
            </button>
        </div>
    </div>
    <div class="card">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table id="young-animals-table" class="table table-striped table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            @foreach (['ID', 'Nazwa', 'Kod Miotu', 'Płeć', 'Foto', 'Oferta', 'Karmienia', 'Cena', 'Publiczny'] as $column => $label)
                                <th scope="col" class="{{ $column >= 2 ? 'text-center' : '' }}" aria-sort="{{ $column === 0 ? 'ascending' : 'none' }}">
                                    <button type="button" class="btn btn-link p-0 text-reset text-decoration-none fw-bold text-nowrap" data-sort-column="{{ $column }}">
                                        {{ $label }} <span data-sort-indicator aria-hidden="true">{{ $column === 0 ? '▲' : '↕' }}</span>
                                    </button>
                                </th>
                            @endforeach
                            <th scope="col" class="d-print-none"><span class="visually-hidden">Profil</span></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($animals as $animal)
                            <tr>
                                <td data-sort-value="{{ $animal['id'] }}">{{ $animal['id'] }}</td>
                                <td>{{ $animal['name'] }}</td>
                                <td class="text-center">{{ $animal['litter_code'] }}</td>
                                <td class="text-center">{{ $animal['sex_label'] }}</td>
                                @foreach (['has_photo', 'has_offer', 'feedings_ok'] as $status)
                                    <td class="text-center" data-sort-value="{{ (int) $animal[$status] }}">
                                        <span class="{{ $animal[$status] ? 'text-success' : 'text-danger' }}">
                                            <i class="bi {{ $animal[$status] ? 'bi-check-lg' : 'bi-x-lg' }}" aria-hidden="true"></i>
                                            <span class="visually-hidden">{{ $animal[$status] ? 'Tak' : 'Nie' }}</span>
                                        </span>
                                    </td>
                                @endforeach
                                <td class="text-center" data-sort-value="{{ $animal['price'] }}">{{ $animal['price'] !== null ? number_format((float) $animal['price'], 2, ',', ' ') . ' zł' : 'n/d' }}</td>
                                <td class="text-center" data-sort-value="{{ (int) $animal['is_public'] }}">
                                    <span class="{{ $animal['is_public'] ? 'text-success' : 'text-danger' }}">
                                        <i class="bi {{ $animal['is_public'] ? 'bi-check-lg' : 'bi-x-lg' }}" aria-hidden="true"></i>
                                        <span class="visually-hidden">{{ $animal['is_public'] ? 'Tak' : 'Nie' }}</span>
                                    </span>
                                    @if ($animal['is_public'] && (! $animal['has_photo'] || ! $animal['has_offer'] || ! $animal['feedings_ok']))
                                        <span class="ms-1" style="color: #fd7e14; print-color-adjust: exact; -webkit-print-color-adjust: exact;" title="Profil publiczny: brakuje zdjęcia, oferty lub trzech prawidłowych karmień." data-public-warning>
                                            <i class="bi bi-exclamation-triangle-fill" aria-hidden="true"></i>
                                            <span class="visually-hidden">Uwaga: brakuje zdjęcia, oferty lub trzech prawidłowych karmień.</span>
                                        </span>
                                    @endif
                                </td>
                                <td class="d-print-none"><a class="btn btn-sm btn-outline-light" href="{{ $animal['profile_url'] }}">Profil</a></td>
                            </tr>
                        @empty
                            <tr><td colspan="10" class="text-center text-muted py-4">Brak maluchów.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    </div>
@endsection
@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const table = document.getElementById('young-animals-table');
            const tbody = table.tBodies[0];
            const buttons = table.querySelectorAll('[data-sort-column]');
            const collator = new Intl.Collator('pl', { numeric: true, sensitivity: 'base' });
            const numericColumns = new Set([0, 4, 5, 6, 7, 8]);
            let activeColumn = 0;
            let ascending = true;

            buttons.forEach((button) => {
                button.addEventListener('click', () => {
                    const column = Number(button.dataset.sortColumn);
                    ascending = column === activeColumn ? !ascending : true;
                    activeColumn = column;
                    const rows = Array.from(tbody.rows).filter((row) => row.cells.length === 10);
                    const value = (row) => {
                        const cell = row.cells[column];
                        return cell.dataset.sortValue ?? cell.textContent.trim();
                    };

                    rows.sort((a, b) => {
                        const left = value(a);
                        const right = value(b);
                        // Keep missing prices at the end in either direction.
                        if (left === '' || right === '') {
                            if (left !== right) return left === '' ? 1 : -1;
                        }
                        const comparison = numericColumns.has(column)
                            ? Number(left) - Number(right)
                            : collator.compare(left, right);
                        return comparison === 0
                            ? Number(a.cells[0].textContent) - Number(b.cells[0].textContent)
                            : comparison * (ascending ? 1 : -1);
                    });
                    tbody.append(...rows);

                    buttons.forEach((headerButton) => {
                        const active = Number(headerButton.dataset.sortColumn) === column;
                        headerButton.closest('th').setAttribute('aria-sort', active ? (ascending ? 'ascending' : 'descending') : 'none');
                        headerButton.querySelector('[data-sort-indicator]').textContent = active ? (ascending ? '▲' : '▼') : '↕';
                    });
                });
            });
        });
    </script>
@endpush
@push('styles')
    <style>
        @media print {
            @page { size: A4 landscape; margin: 10mm; }
            body * { visibility: hidden; }
            #young-animals-print, #young-animals-print * { visibility: visible; }
            #young-animals-print {
                position: absolute;
                inset: 0 auto auto 0;
                width: 100%;
                color: #000 !important;
                background: #fff !important;
                font-size: 10px;
            }
            #young-animals-print .table-responsive { overflow: visible; }
            #young-animals-print .card { border: 0; background: #fff !important; }
            #young-animals-print table { width: 100%; }
            #young-animals-print th, #young-animals-print td {
                color: #000 !important;
                background: #fff !important;
                box-shadow: none !important;
                border-bottom: 1px solid #ccc;
                padding: 4px;
            }
            #young-animals-print thead { display: table-header-group; }
            #young-animals-print tr { break-inside: avoid; }
            #young-animals-print .text-muted { color: #444 !important; }
            #young-animals-print [data-sort-column] { font-size: inherit; }
            #young-animals-print [data-sort-indicator] { display: none; }
            #young-animals-print .text-success, #young-animals-print .text-danger {
                print-color-adjust: exact;
                -webkit-print-color-adjust: exact;
            }
        }
    </style>
@endpush