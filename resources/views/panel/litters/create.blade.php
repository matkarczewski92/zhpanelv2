@extends('layouts.panel')

@section('title', 'Nowy miot')

@section('content')
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
        <div>
            <h1 class="h4 mb-1">Nowy miot</h1>
            <p class="text-muted mb-0">Utworz miot lub planowany miot na podstawie rodzicow.</p>
        </div>
        <a href="{{ route('panel.litters.index') }}" class="btn btn-outline-light btn-sm">Powrot do listy</a>
    </div>

    <div class="row justify-content-center">
        <div class="col-12 col-xl-8">
            <div class="glass-card">
                <div class="card-header">
                    <div class="strike"><span>Dane miotu</span></div>
                </div>
                <div class="card-body">
                    @include('panel.litters._form', [
                        'form' => $form,
                        'action' => route('panel.litters.store'),
                        'method' => 'POST',
                        'submitLabel' => 'Dodaj miot',
                        'errorBag' => 'litterCreate',
                    ])
                </div>
            </div>
        </div>
    </div>
@endsection

