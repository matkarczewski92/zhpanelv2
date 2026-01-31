@extends('layouts.panel')

@section('title', 'Dodaj zwierzę')

@section('content')
    <div class="d-flex flex-wrap align-items-center justify-content-between mb-3">
        <div>
            <h1 class="h4 mb-1">Dodaj zwierzę</h1>
            <p class="text-muted mb-0">Uzupełnij dane nowego zwierzęcia.</p>
        </div>
    </div>

    <div class="card cardopacity">
        <div class="card-body">
            <form method="POST" action="{{ route('panel.animals.store') }}" class="vstack gap-4">
                @csrf

                @include('admin.animals._form')

                <div class="d-flex flex-wrap gap-2">
                    <button class="btn btn-primary" type="submit">Zapisz</button>
                    <a class="btn btn-outline-light" href="{{ route('panel.animals.index') }}">Wroc</a>
                </div>
            </form>
        </div>
    </div>
@endsection
