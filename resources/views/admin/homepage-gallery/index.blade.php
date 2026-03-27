@extends('layouts.panel')

@section('title', 'Galeria główna')

@section('content')
    @php
        $photos = $page['photos'] ?? null;
    @endphp

    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
        <div>
            <h1 class="h4 mb-1">Galeria główna</h1>
            <p class="text-muted mb-0">Zdjęcia oznaczone na profilach zwierząt jako widoczne na stronie głównej hodowli.</p>
        </div>
    </div>

    <div class="glass-card">
        <div class="card-header d-flex flex-wrap align-items-center justify-content-between gap-2">
            <div>
                <div class="fw-semibold">Zdjęcia ze strony głównej</div>
                <div class="small text-muted">Możesz szybko przejść do profilu zwierzęcia albo odznaczyć zdjęcie z galerii głównej.</div>
            </div>
            @if ($photos)
                <div class="small text-muted">Liczba zdjęć: {{ $photos->total() }}</div>
            @endif
        </div>

        <div class="card-body">
            <div class="row g-3">
                @forelse ($photos as $photo)
                    <div class="col-12 col-md-6 col-xl-4">
                        <div class="glass-card h-100 admin-homepage-gallery-card">
                            <a href="{{ $photo['image_url'] }}" target="_blank" rel="noopener noreferrer" class="admin-homepage-gallery-thumb">
                                <img src="{{ $photo['image_url'] }}" alt="Zdjecie {{ $photo['animal_id'] }}">
                            </a>

                            <div class="card-body d-flex flex-column gap-2">
                                <div class="small text-muted">ID zwierzęcia: {{ $photo['animal_id'] }}</div>
                                <div class="fw-semibold">{!! $photo['animal_name'] !!}</div>

                                @if ($photo['type_name'] !== '')
                                    <div class="small text-muted">{{ $photo['type_name'] }}</div>
                                @endif

                                <div class="small text-muted">
                                    Public tag: {{ $photo['public_tag'] ?: '-' }}
                                </div>

                                <div class="small text-muted">
                                    Ostatnia zmiana: {{ $photo['updated_at'] ?: '-' }}
                                </div>

                                <div class="d-flex flex-wrap gap-2 mt-auto">
                                    <a href="{{ $photo['profile_url'] }}" class="btn btn-outline-light btn-sm">Profil zwierzęcia</a>
                                    <form method="POST" action="{{ $photo['remove_url'] }}" onsubmit="return confirm('Odznaczyc to zdjecie z galerii glownej?');">
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit" class="btn btn-outline-danger btn-sm">Odznacz</button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="col-12">
                        <div class="text-center text-muted py-4">Brak zdjęć oznaczonych jako galeria główna.</div>
                    </div>
                @endforelse
            </div>
        </div>

        @if ($photos && method_exists($photos, 'links'))
            <div class="card-body pt-0">
                {{ $photos->links() }}
            </div>
        @endif
    </div>
@endsection
