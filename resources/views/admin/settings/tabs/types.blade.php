<div class="tab-pane fade @if($vm->activeTab==='types') show active @endif" id="tab-types" role="tabpanel">
    <div class="card cardopacity">
        <div class="card-header d-flex justify-content-between align-items-center">
            <span>Typy</span>
            <form class="d-flex gap-2" method="POST" action="{{ route('admin.settings.animal-types.store') }}">
                @csrf
                <input type="text" name="name" class="form-control form-control-sm bg-dark text-light" placeholder="Nowy typ" required>
                <button class="btn btn-sm btn-primary" type="submit">Dodaj</button>
            </form>
        </div>
        <div class="table-responsive">
            <table class="table table-dark table-sm align-middle mb-0">
                <thead><tr><th>ID</th><th>Nazwa</th><th class="text-end">Opcje</th></tr></thead>
                <tbody>
                @foreach($vm->animalTypes as $type)
                    <tr>
                        <td>{{ $type->id }}</td>
                        <td>
                            <form method="POST" action="{{ route('admin.settings.animal-types.update', $type) }}" class="d-flex gap-2 align-items-center">
                                @csrf
                                @method('PATCH')
                                <input type="text" name="name" class="form-control form-control-sm bg-dark text-light" value="{{ $type->name }}" required>
                                <button class="btn btn-sm btn-outline-light" type="submit">Zapisz</button>
                            </form>
                        </td>
                        <td class="text-end">
                            <form method="POST" action="{{ route('admin.settings.animal-types.destroy', $type) }}" onsubmit="return confirm('Usunąć?')">
                                @csrf
                                @method('DELETE')
                                <button class="btn btn-sm btn-outline-danger">Usuń</button>
                            </form>
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
