@extends('layouts.panel')

@section('title', 'Lista przewozowa')

@section('content')
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
        <div>
            <h1 class="h4 mb-1">Lista przewozowa</h1>
            <p class="text-muted mb-0">Wybierz zwierzeta z kategorii 1, 2, 4 i wydrukuj zestawienie transportowe.</p>
        </div>
    </div>

    @if ($errors->any())
        <div class="alert alert-danger py-2">
            @foreach ($errors->all() as $error)
                <div>{{ $error }}</div>
            @endforeach
        </div>
    @endif

    <form method="POST" action="{{ $vm->printUrl }}">
        @csrf
        <div class="glass-card glass-table-wrapper">
            <div class="card-header d-flex justify-content-between align-items-center gap-2 flex-wrap">
                <div class="strike flex-grow-1"><span>Zwierzęta</span></div>
                <div class="d-flex align-items-center gap-2">
                    <button type="button" class="btn btn-outline-light btn-sm" id="shippingSelectOffersOnly">Zaznacz tylko oferty</button>
                    <button type="submit" class="btn btn-primary btn-sm">Drukuj</button>
                </div>
            </div>
            <div class="table-responsive">
                <table class="table glass-table table-sm align-middle mb-0">
                    <thead>
                        <tr class="text-muted small">
                            <th style="width: 42px;">
                                <input type="checkbox" class="form-check-input" id="shippingSelectAll">
                            </th>
                            <th>ID</th>
                            <th>Nazwa</th>
                            <th>Płeć</th>
                            <th>Typ</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($vm->animals as $animal)
                            <tr>
                                <td>
                                    <input
                                        type="checkbox"
                                        class="form-check-input shipping-row"
                                        name="animal_ids[]"
                                        value="{{ $animal['id'] }}"
                                        data-has-offer="{{ !empty($animal['has_offer']) ? '1' : '0' }}"
                                        data-category-id="{{ (int) ($animal['category_id'] ?? 0) }}"
                                        @checked(in_array((string) $animal['id'], array_map('strval', old('animal_ids', [])), true))
                                    >
                                </td>
                                <td>{{ $animal['id'] }}</td>
                                <td>{{ $animal['name'] }}</td>
                                <td>{{ $animal['sex_label'] }}</td>
                                <td>{{ $animal['type_name'] }}</td>
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
            const selectAll = document.getElementById('shippingSelectAll');
            const selectOffersOnly = document.getElementById('shippingSelectOffersOnly');
            const rows = Array.from(document.querySelectorAll('.shipping-row'));

            if (!selectAll) return;

            const updateSelectAllState = () => {
                if (rows.length === 0) {
                    selectAll.checked = false;
                    selectAll.indeterminate = false;
                    return;
                }

                const checkedCount = rows.filter((checkbox) => checkbox.checked).length;
                selectAll.checked = checkedCount === rows.length;
                selectAll.indeterminate = checkedCount > 0 && checkedCount < rows.length;
            };

            selectAll.addEventListener('change', () => {
                rows.forEach((checkbox) => {
                    checkbox.checked = selectAll.checked;
                });
                selectAll.indeterminate = false;
            });

            rows.forEach((checkbox) => {
                checkbox.addEventListener('change', updateSelectAllState);
            });

            selectOffersOnly?.addEventListener('click', () => {
                rows.forEach((checkbox) => {
                    const hasOffer = checkbox.dataset.hasOffer === '1';
                    const categoryId = Number(checkbox.dataset.categoryId || 0);
                    checkbox.checked = hasOffer && categoryId !== 3 && categoryId !== 5;
                });
                updateSelectAllState();
            });

            updateSelectAllState();
        });
    </script>
@endpush
