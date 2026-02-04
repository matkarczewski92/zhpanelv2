@extends('layouts.panel')

@section('title', 'Cennik')

@section('content')
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
        <div>
            <h1 class="h4 mb-1">Cennik</h1>
            <p class="text-muted mb-0">Wybierz zwierzeta i drukuj zestawienie cen.</p>
        </div>
    </div>

    @if ($errors->any())
        <div class="alert alert-danger py-2">
            @foreach ($errors->all() as $error)
                <div>{{ $error }}</div>
            @endforeach
        </div>
    @endif

    <div class="glass-card mb-3">
        <div class="card-body">
            <form method="GET" action="{{ route('admin.pricelist.index') }}" class="row g-2 align-items-end">
                <div class="col-12 col-md-6 col-xl-4">
                    <label class="form-label small text-muted mb-1">Wyszukaj po ID lub nazwie</label>
                    <input type="text" class="form-control form-control-sm" name="q" value="{{ $vm->search }}" placeholder="np. 120 lub nazwa">
                </div>
                <div class="col-auto">
                    <button type="submit" class="btn btn-outline-light btn-sm">Szukaj</button>
                </div>
                <div class="col-auto">
                    <a href="{{ route('admin.pricelist.index') }}" class="btn btn-outline-light btn-sm">Wyczyść</a>
                </div>
            </form>
        </div>
    </div>

    <form method="POST" action="{{ $vm->printUrl }}">
        @csrf
        <div class="glass-card glass-table-wrapper">
            <div class="card-header d-flex justify-content-between align-items-center gap-2 flex-wrap">
                <div class="strike flex-grow-1"><span>Zwierzęta</span></div>
                <div class="d-flex align-items-center gap-2">
                    <button type="button" class="btn btn-outline-light btn-sm" id="selectOffersBtn">Zaznacz oferty</button>
                    <button type="submit" class="btn btn-primary btn-sm">Drukuj</button>
                </div>
            </div>
            <div class="table-responsive">
                <table class="table glass-table table-sm align-middle mb-0">
                    <thead>
                        <tr class="text-muted small">
                            <th style="width: 42px;">
                                <input type="checkbox" class="form-check-input" id="priceSelectAll">
                            </th>
                            <th>ID</th>
                            <th>Nazwa</th>
                            <th>Płeć</th>
                            <th>Cena</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($vm->animals as $animal)
                            <tr>
                                <td>
                                    <input
                                        type="checkbox"
                                        class="form-check-input price-row"
                                        name="animal_ids[]"
                                        value="{{ $animal['id'] }}"
                                        data-has-offer="{{ $animal['has_offer'] ? '1' : '0' }}"
                                        @checked(in_array((string) $animal['id'], array_map('strval', old('animal_ids', [])), true))
                                    >
                                </td>
                                <td>{{ $animal['id'] }}</td>
                                <td>{{ $animal['name'] }}</td>
                                <td>{{ $animal['sex_label'] }}</td>
                                <td>{{ $animal['price_formatted'] }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center text-muted">Brak zwierząt do wyświetlenia.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </form>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const selectAll = document.getElementById('priceSelectAll');
            const rows = document.querySelectorAll('.price-row');
            const selectOffersBtn = document.getElementById('selectOffersBtn');

            if (!selectAll) return;

            selectAll.addEventListener('change', () => {
                rows.forEach((checkbox) => {
                    checkbox.checked = selectAll.checked;
                });
            });

            if (selectOffersBtn) {
                selectOffersBtn.addEventListener('click', () => {
                    rows.forEach((checkbox) => {
                        checkbox.checked = checkbox.dataset.hasOffer === '1';
                    });
                    selectAll.checked = Array.from(rows).every((checkbox) => checkbox.checked);
                });
            }
        });
    </script>
@endpush
