@extends('layouts.panel')

@section('title', 'Maluchy')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4 class="mb-0">Maluchy</h4>
        <span class="text-muted">Liczba zwierząt: {{ $animals->count() }}</span>
    </div>
    <div class="card">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-striped table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th scope="col">ID</th>
                            <th scope="col">Nazwa</th>
                            <th scope="col">Kod Miotu</th>
                            <th scope="col">Płeć</th>
                            <th scope="col">Foto</th>
                            <th scope="col">Oferta</th>
                            <th scope="col">Karmienia</th>
                            <th scope="col">Cena</th>
                            <th scope="col">Publiczny</th>
                            <th scope="col"><span class="visually-hidden">Profil</span></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($animals as $animal)
                            <tr>
                                <td>{{ $animal['id'] }}</td>
                                <td>{{ $animal['name'] }}</td>
                                <td>{{ $animal['litter_code'] }}</td>
                                <td>{{ $animal['sex_label'] }}</td>
                                @foreach (['has_photo', 'has_offer', 'feedings_ok'] as $status)
                                    <td>
                                        <span class="{{ $animal[$status] ? 'text-success' : 'text-danger' }}">
                                            <i class="bi {{ $animal[$status] ? 'bi-check-lg' : 'bi-x-lg' }}" aria-hidden="true"></i>
                                            <span class="visually-hidden">{{ $animal[$status] ? 'Tak' : 'Nie' }}</span>
                                        </span>
                                    </td>
                                @endforeach
                                <td>{{ $animal['price'] !== null ? number_format((float) $animal['price'], 2, ',', ' ') . ' zł' : 'n/d' }}</td>
                                <td>
                                    <span class="{{ $animal['is_public'] ? 'text-success' : 'text-danger' }}">
                                        <i class="bi {{ $animal['is_public'] ? 'bi-check-lg' : 'bi-x-lg' }}" aria-hidden="true"></i>
                                        <span class="visually-hidden">{{ $animal['is_public'] ? 'Tak' : 'Nie' }}</span>
                                    </span>
                                </td>
                                <td><a class="btn btn-sm btn-outline-light" href="{{ $animal['profile_url'] }}">Profil</a></td>
                            </tr>
                        @empty
                            <tr><td colspan="10" class="text-center text-muted py-4">Brak maluchów.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection