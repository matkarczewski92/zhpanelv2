@extends('layouts.panel')

@section('title', 'Masowe Dane')

@section('content')
    @php
        $massErrors = $errors->getBag('massData');
        $selectedTransactionDate = old('transaction_date', now()->format('Y-m-d'));
    @endphp

    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
        <div>
            <h1 class="h4 mb-1">Masowe Dane</h1>
            <p class="text-muted mb-0">Szybkie wprowadzanie karmien i wazen dla wielu zwierzat jednoczesnie.</p>
        </div>
    </div>

    <div class="glass-card mb-3">
        <div class="card-body">
            <div class="row g-2 align-items-end">
                <div class="col-12 col-md-4 col-xl-3">
                    <label for="massDataTransactionDate" class="form-label small text-muted mb-1">Data zapisu</label>
                    <input
                        id="massDataTransactionDate"
                        type="date"
                        class="form-control form-control-sm @if($massErrors->has('transaction_date')) is-invalid @endif"
                        value="{{ $selectedTransactionDate }}"
                    >
                    @if($massErrors->has('transaction_date'))
                        <div class="invalid-feedback d-block">{{ $massErrors->first('transaction_date') }}</div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    @if ($massErrors->any() && !old('category_id'))
        <div class="alert alert-danger py-2">
            @foreach ($massErrors->all() as $error)
                <div>{{ $error }}</div>
            @endforeach
        </div>
    @endif

    @foreach ($page->sections as $section)
        @php
            $showSectionErrors = $massErrors->any() && (string) old('category_id') === (string) $section['category_id'];
        @endphp
        <div class="glass-card glass-table-wrapper mb-3">
            <div class="card-header">
                <div class="strike"><span>{{ $section['title'] }}</span></div>
            </div>
            <form method="POST" action="{{ route('panel.massdata.commit') }}">
                @csrf
                <input type="hidden" name="category_id" value="{{ $section['category_id'] }}">
                <input type="hidden" name="transaction_date" value="{{ $selectedTransactionDate }}" data-massdata-transaction-date>

                <div class="table-responsive">
                    <table class="table glass-table table-sm align-middle mb-0">
                        <thead>
                            <tr class="text-muted small">
                                <th class="text-start">ID</th>
                                <th class="text-start">Nazwa</th>
                                <th style="width: 14%;">Wazenie</th>
                                <th style="width: 28%;">Karma</th>
                                <th style="width: 8%;" class="text-center">Czy karmic</th>
                                <th style="width: 7%;" class="text-end">Profil</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($section['animals'] as $animal)
                                @php
                                    $rowPrefix = "rows.{$animal['id']}";
                                    $selectedFeedId = old("{$rowPrefix}.feed_id", $animal['default_feed_id']);
                                    $selectedAmount = old("{$rowPrefix}.amount", $animal['default_amount']);
                                    $selectedWeight = old("{$rowPrefix}.weight", '');
                                    $feedCheckValue = old("{$rowPrefix}.feed_check", $animal['default_feed_check'] ? '1' : '0');
                                @endphp
                                <tr>
                                    <td class="text-start">
                                        <a href="{{ $animal['profile_url'] }}" class="link-reset">{{ $animal['id'] }}</a>
                                    </td>
                                    <td class="text-start">
                                        <a href="{{ $animal['profile_url'] }}" class="link-reset {{ $animal['is_wintering'] ? 'wintering-name' : '' }}">
                                            @if($animal['is_wintering'])
                                                <span class="wintering-icon" aria-hidden="true">&#10052;</span>
                                            @endif
                                            {!! $animal['name_html'] !!}
                                        </a>
                                    </td>
                                    <td>
                                        <input type="hidden" name="rows[{{ $animal['id'] }}][animal_id]" value="{{ $animal['id'] }}">
                                        <input
                                            type="text"
                                            class="form-control form-control-sm"
                                            name="rows[{{ $animal['id'] }}][weight]"
                                            value="{{ $selectedWeight }}"
                                            inputmode="decimal"
                                        >
                                    </td>
                                    <td>
                                        <div class="input-group input-group-sm">
                                            <select class="form-select" name="rows[{{ $animal['id'] }}][feed_id]">
                                                <option value="">Wybierz karme</option>
                                                @foreach ($page->feeds as $feed)
                                                    <option value="{{ $feed['id'] }}" @selected((string) $selectedFeedId === (string) $feed['id'])>
                                                        {{ $feed['name'] }}
                                                    </option>
                                                @endforeach
                                            </select>
                                            <input
                                                type="text"
                                                class="form-control"
                                                name="rows[{{ $animal['id'] }}][amount]"
                                                value="{{ $selectedAmount }}"
                                                inputmode="numeric"
                                                placeholder="Ilosc"
                                            >
                                        </div>
                                    </td>
                                    <td class="text-center">
                                        <input type="hidden" name="rows[{{ $animal['id'] }}][feed_check]" value="0">
                                        @if($animal['is_wintering'])
                                            <input
                                                class="form-check-input"
                                                type="checkbox"
                                                value="0"
                                                disabled
                                            >
                                        @else
                                            <input
                                                class="form-check-input"
                                                type="checkbox"
                                                name="rows[{{ $animal['id'] }}][feed_check]"
                                                value="1"
                                                @checked((string) $feedCheckValue === '1')
                                            >
                                        @endif
                                    </td>
                                    <td class="text-end">
                                        <a href="{{ $animal['profile_url'] }}" class="link-reset">{{ $animal['id'] }}</a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center text-muted">Brak zwierzat w tej sekcji.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="card-body border-top border-opacity-10 border-light">
                    @if ($showSectionErrors)
                        <div class="small text-danger mb-2">
                            @foreach ($massErrors->all() as $error)
                                <div>{{ $error }}</div>
                            @endforeach
                        </div>
                    @endif

                    <button type="submit" class="btn btn-success w-100">Wprowadz dane</button>
                </div>
            </form>
        </div>
    @endforeach
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const sourceInput = document.getElementById('massDataTransactionDate');
            if (!sourceInput) return;

            const sync = () => {
                document.querySelectorAll('[data-massdata-transaction-date]').forEach((hiddenInput) => {
                    hiddenInput.value = sourceInput.value || '';
                });
            };

            sourceInput.addEventListener('change', sync);
            sync();
        });
    </script>
@endpush
