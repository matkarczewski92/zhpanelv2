@extends('layouts.panel')

@section('title', 'Zwierzęta')

@section('content')
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
        <div>
            <h1 id="animals-index-title" class="h4 mb-1" tabindex="-1">Zwierzęta</h1>
            <p class="text-muted mb-0">Lista zwierząt w hodowli.</p>
        </div>
        <a class="btn btn-primary" href="{{ route('panel.animals.create') }}">Dodaj</a>
    </div>

    <div class="card cardopacity mb-4">
        <div class="card-body">
            <form method="GET" action="{{ route('panel.animals.index') }}" class="row g-3 align-items-end">
                <div class="col-12 col-md-6 col-xl-3">
                    <label class="form-label" for="q">Szukaj</label>
                    <input
                        id="q"
                        name="q"
                        type="text"
                        class="form-control js-animals-search"
                        value="{{ $filters['q'] ?? '' }}"
                    />
                </div>
                <div class="col-12 col-md-6 col-xl-3">
                    <label class="form-label" for="type_id">Typ</label>
                    <select id="type_id" name="type_id" class="form-select">
                        <option value="">Wszystkie</option>
                        @foreach ($types as $type)
                            <option value="{{ $type['id'] }}" @selected(($filters['type_id'] ?? null) == $type['id'])>
                                {{ $type['name'] }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12 col-md-6 col-xl-3">
                    <label class="form-label" for="category_id">Kategoria</label>
                    <select id="category_id" name="category_id" class="form-select">
                        @foreach ($categories as $category)
                            <option value="{{ $category['id'] }}" @selected(($filters['category_id'] ?? null) == $category['id'])>
                                {{ $category['name'] }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12 col-md-6 col-xl-3">
                    <label class="form-label" for="feed_id">Karma</label>
                    <select id="feed_id" name="feed_id" class="form-select">
                        <option value="">Wszystkie</option>
                        @foreach ($feeds as $feed)
                            <option value="{{ $feed['id'] }}" @selected(($filters['feed_id'] ?? null) == $feed['id'])>
                                {{ $feed['name'] }}
                            </option>
                        @endforeach
                    </select>
                </div>
                @if (!empty($sort))
                    <input type="hidden" name="sort" value="{{ $sort }}" />
                    <input type="hidden" name="direction" value="{{ $direction }}" />
                @endif
                <div class="col-12 d-flex flex-wrap gap-2">
                    <button class="btn btn-primary" type="submit">Filtruj</button>
                    <a class="btn btn-outline-light" href="{{ route('panel.animals.index') }}">Reset</a>
                </div>
            </form>
        </div>
    </div>

    <div id="animals-index-container">
        @forelse ($groups as $group)
            <div class="glass-card glass-table-wrapper mb-4">
                <div class="card-header">
                    <div class="strike"><span>{{ $group['type']['name'] }}</span></div>
                    <div class="text-center text-muted small mt-1">{{ $group['count_label'] }}</div>
                </div>
                <div class="table-responsive">
                    <table class="table glass-table table-hover table-sm align-middle mb-0 animals-table">
                        <colgroup>
                            <col style="width: 6%">
                            <col style="width: 45%">
                            <col style="width: 6%">
                            <col style="width: 8%">
                            <col style="width: 12%">
                            <col style="width: 10%">
                            <col style="width: 10%">
                            <col style="width: 8%">
                        </colgroup>
                        <thead>
                            <tr>
                                <th class="d-none d-md-table-cell">
                                    <a class="link-reset js-animals-sort" href="{{ $sortLinks['id']['url'] }}">
                                        ID
                                        @if ($sortLinks['id']['indicator'])
                                            <span class="ms-1">{{ $sortLinks['id']['indicator'] }}</span>
                                        @endif
                                    </a>
                                </th>
                                <th>
                                    <a class="link-reset js-animals-sort" href="{{ $sortLinks['name']['url'] }}">
                                        Nazwa
                                        @if ($sortLinks['name']['indicator'])
                                            <span class="ms-1">{{ $sortLinks['name']['indicator'] }}</span>
                                        @endif
                                    </a>
                                </th>
                                <th>Płeć</th>
                                <th>
                                    <a class="link-reset js-animals-sort" href="{{ $sortLinks['weight']['url'] }}">
                                        Waga
                                        @if ($sortLinks['weight']['indicator'])
                                            <span class="ms-1">{{ $sortLinks['weight']['indicator'] }}</span>
                                        @endif
                                    </a>
                                </th>
                                <th class="d-none d-md-table-cell">
                                    <a class="link-reset js-animals-sort" href="{{ $sortLinks['feed']['url'] }}">
                                        Karma
                                        @if ($sortLinks['feed']['indicator'])
                                            <span class="ms-1">{{ $sortLinks['feed']['indicator'] }}</span>
                                        @endif
                                    </a>
                                </th>
                                <th class="d-none d-md-table-cell">Ostatnie karmienie</th>
                                <th class="d-none d-md-table-cell">Następne karmienie</th>
                                <th class="d-none d-md-table-cell text-end">Akcje</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($group['animals'] as $animal)
                                <tr class="{{ $animal['is_wintering'] ? 'row-wintering' : '' }}">
                                    <td class="d-none d-md-table-cell">{{ $animal['id'] }}</td>
                                    <td>
                                        <div class="d-flex align-items-center gap-2">
                                            @if ($animal['is_wintering'])
                                                <span class="wintering-icon" aria-hidden="true">&#10052;</span>
                                            @endif
                                            <a class="link-reset flex-grow-1" href="{{ route('panel.animals.show', $animal['id']) }}">
                                                {!! $animal['name_display_html'] !!}
                                            </a>
                                            <a class="btn btn-outline-light btn-sm d-md-none" href="{{ route('panel.animals.show', $animal['id']) }}">
                                                <span aria-hidden="true">›</span>
                                                <span class="visually-hidden">Podgląd</span>
                                            </a>
                                        </div>
                                    </td>
                                    <td>{{ $animal['sex_label'] }}</td>
                                    <td>{{ $animal['weight_label'] }}</td>
                                    <td class="d-none d-md-table-cell">{{ $animal['feed_name'] ?? '-' }}</td>
                                    <td class="d-none d-md-table-cell">{{ $animal['last_feed_at'] ?? '-' }}</td>
                                    <td class="d-none d-md-table-cell">{{ $animal['next_feed_at'] ?? '-' }}</td>
                                    <td class="d-none d-md-table-cell text-end">
                                        <a class="btn btn-outline-light btn-sm" href="{{ route('panel.animals.show', $animal['id']) }}">Podgląd</a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="text-center text-muted">Brak danych.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        @empty
            <div class="card cardopacity">
                <div class="card-body text-center text-muted">Brak danych.</div>
            </div>
        @endforelse

        <div class="d-flex justify-content-center mt-3">
            {{ $animals->appends(request()->query())->links('vendor.pagination.tailwind') }}
        </div>
    </div>
@endsection
