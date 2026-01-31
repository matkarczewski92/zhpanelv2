@extends('layouts.panel')

@section('title', 'Karma')

@section('content')
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
        <div>
            <h1 class="h4 mb-1">Karma</h1>
            <p class="text-muted mb-0">Zarządzaj typami karmy wykorzystywanej w panelu.</p>
        </div>
    </div>

    <div class="card cardopacity mb-4">
        <div class="card-body">
            <form method="POST" action="{{ route('panel.feeds.store') }}" class="row g-3 align-items-end">
                @csrf
                <div class="col-12 col-md-4">
                    <label class="form-label" for="feed_name">Nazwa</label>
                    <input
                        id="feed_name"
                        name="name"
                        type="text"
                        class="form-control @error('name') is-invalid @enderror"
                        value="{{ old('name') }}"
                        required
                    />
                    @error('name')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                <div class="col-12 col-md-3">
                    <label class="form-label" for="feed_interval">Interwał (dni)</label>
                    <input
                        id="feed_interval"
                        name="feeding_interval"
                        type="number"
                        min="0"
                        class="form-control @error('feeding_interval') is-invalid @enderror"
                        value="{{ old('feeding_interval', 0) }}"
                        required
                    />
                    @error('feeding_interval')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                <div class="col-12 col-md-2">
                    <label class="form-label" for="feed_amount">Ilość</label>
                    <input
                        id="feed_amount"
                        name="amount"
                        type="number"
                        min="0"
                        class="form-control @error('amount') is-invalid @enderror"
                        value="{{ old('amount') }}"
                    />
                    @error('amount')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                <div class="col-12 col-md-2">
                    <label class="form-label" for="feed_price">Cena</label>
                    <input
                        id="feed_price"
                        name="last_price"
                        type="number"
                        step="0.01"
                        min="0"
                        class="form-control @error('last_price') is-invalid @enderror"
                        value="{{ old('last_price') }}"
                    />
                    @error('last_price')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                <div class="col-12 col-md-1 d-grid">
                    <button class="btn btn-primary" type="submit">Dodaj</button>
                </div>
            </form>
        </div>
    </div>

    <div class="glass-card glass-table-wrapper">
        <div class="card-header">
            <div class="strike"><span>Lista karm</span></div>
        </div>
        <div class="table-responsive">
            <table class="table glass-table table-hover table-sm align-middle mb-0">
                <thead>
                    <tr class="text-muted small">
                        <th style="width: 40px">ID</th>
                        <th>Nazwa</th>
                        <th>Interwał</th>
                        <th>Ilość</th>
                        <th>Cena</th>
                        <th>Utworzono</th>
                        <th class="text-end">Akcje</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($feeds as $feed)
                        <tr>
                            <td>{{ $feed['id'] }}</td>
                            <td>{{ $feed['name'] }}</td>
                            <td>{{ $feed['feeding_interval'] }}</td>
                            <td>{{ $feed['amount'] ?? '-' }}</td>
                            <td>{{ $feed['last_price'] }}</td>
                            <td>{{ $feed['created_at'] }}</td>
                            <td class="text-end">
                                <form
                                    method="POST"
                                    action="{{ route('panel.feeds.destroy', $feed['id']) }}"
                                    onsubmit="return confirm('Usunąć karmę?')"
                                    class="d-inline"
                                >
                                    @csrf
                                    @method('DELETE')
                                    <button class="btn btn-outline-light btn-sm" type="submit">Usuń</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center text-muted">Brak danych.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
