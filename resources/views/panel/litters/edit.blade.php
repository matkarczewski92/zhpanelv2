@extends('layouts.panel')

@section('title', 'Edycja miotu')

@section('content')
    @php
        $litter = $page->litter;
    @endphp

    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
        <div>
            <h1 class="h4 mb-1">Edycja miotu {{ $litter['code'] }}</h1>
            <p class="text-muted mb-0">Zmien dane miotu, daty oraz informacje o zniesieniu i kluciu.</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('panel.litters.show', $litter['id']) }}" class="btn btn-outline-light btn-sm">Szczegoly</a>
            <a href="{{ route('panel.litters.index') }}" class="btn btn-outline-light btn-sm">Lista miotow</a>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-12 col-xl-8">
            <div class="glass-card">
                <div class="card-header">
                    <div class="strike"><span>Formularz edycji</span></div>
                </div>
                <div class="card-body">
                    @include('panel.litters._form', [
                        'form' => $form,
                        'action' => route('panel.litters.update', $litter['id']),
                        'method' => 'PUT',
                        'submitLabel' => 'Zapisz zmiany',
                        'litter' => $litter,
                    ])
                </div>
            </div>
        </div>
        <div class="col-12 col-xl-4">
            <div class="glass-card p-3 mb-3">
                <div class="strike mb-2"><span>Status</span></div>
                <div class="fw-semibold">{{ $litter['status_label'] }}</div>
                <div class="text-muted small">{{ $litter['category_label'] }}</div>
            </div>

            <div class="glass-card p-3">
                <div class="strike mb-2"><span>Akcje</span></div>
                <form method="POST" action="{{ route('panel.litters.destroy', $litter['id']) }}" onsubmit="return confirm('Usunac ten miot?');">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-outline-danger btn-sm w-100">Usun miot</button>
                </form>
            </div>
        </div>
    </div>
@endsection
