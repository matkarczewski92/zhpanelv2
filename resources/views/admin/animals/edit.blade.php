@extends('layouts.panel')

@section('title', 'Edytuj zwierzę')

@section('content')
    <div class="d-flex flex-wrap align-items-center justify-content-between mb-3">
        <div>
            <h1 class="h4 mb-1">Edytuj zwierzę</h1>
            <p class="text-muted mb-0">Zaktualizuj dane zwierzęcia.</p>
        </div>
    </div>

    <div class="card cardopacity">
        <div class="card-body">
            <form method="POST" action="{{ route('panel.animals.update', $animal) }}" class="vstack gap-4">
                @csrf
                @method('PUT')

                @include('admin.animals._form')

                <div class="d-flex flex-wrap gap-2">
                    <button class="btn btn-primary" type="submit">Zapisz zmiany</button>
                    <a class="btn btn-outline-light" href="{{ route('panel.animals.show', $animal) }}">Wroc</a>
                </div>
            </form>
        </div>
    </div>
@endsection
