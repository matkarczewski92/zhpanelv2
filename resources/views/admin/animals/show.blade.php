@extends('layouts.panel')

@section('title', 'Profil zwierzęcia')

@section('content')
    <div class="d-flex flex-wrap align-items-start justify-content-between gap-2 mb-3">
        <div>
            <h1 class="h4 mb-1">{{ $animal->name }}</h1>
            <div class="text-muted">ID: {{ $animal->id }}</div>
        </div>
        <a class="btn btn-outline-light btn-sm" href="{{ route('panel.animals.edit', $animal) }}">Edytuj</a>
    </div>

    <div class="row g-3">
        <div class="col-lg-4">
            <div class="card cardopacity h-100">
                <div class="card-body">
                    <h2 class="h6">Podstawowe informacje</h2>
                    <dl class="row mb-0 small">
                        <dt class="col-6 text-muted">Typ</dt>
                        <dd class="col-6">{{ $animal->animalType?->name ?? '-' }}</dd>
                        <dt class="col-6 text-muted">Kategoria</dt>
                        <dd class="col-6">{{ $animal->animalCategory?->name ?? '-' }}</dd>
                        <dt class="col-6 text-muted">Płeć</dt>
                        <dd class="col-6">{{ $animal->sex_label }}</dd>
                        <dt class="col-6 text-muted">Data urodzenia</dt>
                        <dd class="col-6">{{ optional($animal->date_of_birth)->format('Y-m-d') }}</dd>
                        <dt class="col-6 text-muted">Profil publiczny</dt>
                        <dd class="col-6">{{ $animal->public_profile ? 'Tak' : 'Nie' }}</dd>
                    </dl>
                </div>
            </div>
        </div>
        <div class="col-lg-8">
            <div class="card cardopacity h-100">
                <div class="card-body">
                    <h2 class="h6">Dodatkowe dane</h2>
                    <dl class="row mb-0 small">
                        <dt class="col-md-6 text-muted">Druga nazwa</dt>
                        <dd class="col-md-6">{{ $animal->second_name ?? '-' }}</dd>
                        <dt class="col-md-6 text-muted">Tag profilu</dt>
                        <dd class="col-md-6">{{ $animal->public_profile_tag ?? '-' }}</dd>
                        <dt class="col-md-6 text-muted">Interwał karmienia</dt>
                        <dd class="col-md-6">{{ $animal->feed_interval ?? '-' }}</dd>
                        <dt class="col-md-6 text-muted">Domyślna karma</dt>
                        <dd class="col-md-6">{{ $animal->feed?->name ?? '-' }}</dd>
                        <dt class="col-md-6 text-muted">Miot</dt>
                        <dd class="col-md-6">{{ $animal->litter_id ?? '-' }}</dd>
                    </dl>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3 mt-1">
        <div class="col-lg-4">
            <div class="card cardopacity mb-3">
                <div class="card-body">
                    <h3 class="h6">Dodaj karmienie</h3>
                    <div class="mt-3">
                        @include('admin.animals.partials.feeding-form', ['animal' => $animal, 'feeds' => $feeds])
                    </div>
                </div>
            </div>
            <div class="card cardopacity mb-3">
                <div class="card-body">
                    <h3 class="h6">Dodaj wagę</h3>
                    <div class="mt-3">
                        @include('admin.animals.partials.weight-form', ['animal' => $animal])
                    </div>
                </div>
            </div>
            <div class="card cardopacity">
                <div class="card-body">
                    <h3 class="h6">Dodaj wylinkę</h3>
                    <div class="mt-3">
                        @include('admin.animals.partials.molt-form', ['animal' => $animal])
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-8">
            <div class="card cardopacity mb-3">
                <div class="card-body">
                    <h3 class="h6">Ostatnie karmienia</h3>
                    <div class="glass-card glass-table-wrapper table-responsive mt-3">
                        <table class="table glass-table table-sm align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>Data</th>
                                    <th>Karma</th>
                                    <th>Ilość</th>
                                    <th class="text-end">Aktualizuj</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($animal->feedings as $feeding)
                                    <tr>
                                        <td>{{ optional($feeding->created_at)->format('Y-m-d') }}</td>
                                        <td>{{ $feeding->feed?->name ?? '-' }}</td>
                                        <td>{{ $feeding->amount }}</td>
                                        <td class="text-end">
                                            <form
                                                method="POST"
                                                action="{{ route('panel.animals.feedings.update', [$animal, $feeding]) }}"
                                                class="d-flex flex-wrap justify-content-end gap-2"
                                            >
                                                @csrf
                                                @method('PUT')
                                                <x-form.date-input
                                                    name="occurred_at"
                                                    :value="$feeding->created_at"
                                                    class="form-control form-control-sm"
                                                />
                                                <select name="feed_id" class="form-select form-select-sm">
                                                    <option value="">Karma</option>
                                                    @foreach ($feeds as $feed)
                                                        <option value="{{ $feed->id }}" @selected($feed->id === $feeding->feed_id)>
                                                            {{ $feed->name }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                                <input
                                                    type="number"
                                                    name="amount"
                                                    class="form-control form-control-sm"
                                                    value="{{ $feeding->amount }}"
                                                />
                                                <button class="btn btn-outline-light btn-sm" type="submit">Zapisz</button>
                                            </form>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="text-center text-muted">Brak danych.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="card cardopacity mb-3">
                <div class="card-body">
                    <h3 class="h6">Ostatnie wagi</h3>
                    <div class="glass-card glass-table-wrapper table-responsive mt-3">
                        <table class="table glass-table table-sm align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>Data</th>
                                    <th>Waga</th>
                                    <th class="text-end">Aktualizuj</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($animal->weights as $weight)
                                    <tr>
                                        <td>{{ optional($weight->created_at)->format('Y-m-d') }}</td>
                                        <td>{{ $weight->value }}</td>
                                        <td class="text-end">
                                            <form
                                                method="POST"
                                                action="{{ route('panel.animals.weights.update', [$animal, $weight]) }}"
                                                class="d-flex flex-wrap justify-content-end gap-2"
                                            >
                                                @csrf
                                                @method('PUT')
                                                <x-form.date-input
                                                    name="occurred_at"
                                                    :value="$weight->created_at"
                                                    class="form-control form-control-sm"
                                                />
                                                <input
                                                    type="number"
                                                    step="0.01"
                                                    name="value"
                                                    class="form-control form-control-sm"
                                                    value="{{ $weight->value }}"
                                                />
                                                <button class="btn btn-outline-light btn-sm" type="submit">Zapisz</button>
                                            </form>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="3" class="text-center text-muted">Brak danych.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="card cardopacity">
                <div class="card-body">
                    <h3 class="h6">Ostatnie wylinki</h3>
                    <div class="glass-card glass-table-wrapper table-responsive mt-3">
                        <table class="table glass-table table-sm align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>Data</th>
                                    <th class="text-end">Aktualizuj</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($animal->molts as $molt)
                                    <tr>
                                        <td>{{ optional($molt->created_at)->format('Y-m-d') }}</td>
                                        <td class="text-end">
                                            <form
                                                method="POST"
                                                action="{{ route('panel.animals.molts.update', [$animal, $molt]) }}"
                                                class="d-flex flex-wrap justify-content-end gap-2"
                                            >
                                                @csrf
                                                @method('PUT')
                                                <x-form.date-input
                                                    name="occurred_at"
                                                    :value="$molt->created_at"
                                                    class="form-control form-control-sm"
                                                />
                                                <button class="btn btn-outline-light btn-sm" type="submit">Zapisz</button>
                                            </form>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="2" class="text-center text-muted">Brak danych.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
