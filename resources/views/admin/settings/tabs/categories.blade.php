<div class="tab-pane fade @if($vm->activeTab==='categories') show active @endif" id="tab-categories" role="tabpanel">
    <div class="card cardopacity">
        <div class="card-header d-flex justify-content-between align-items-center">
            <span>Kategorie</span>
            <form class="d-flex gap-2" method="POST" action="{{ route('admin.settings.animal-categories.store') }}">
                @csrf
                <input type="text" name="name" class="form-control form-control-sm bg-dark text-light" placeholder="Nowa kategoria" required>
                <button class="btn btn-sm btn-primary" type="submit">Dodaj</button>
            </form>
        </div>
        <div class="table-responsive">
            <table class="table table-dark table-sm align-middle mb-0">
                <thead><tr><th>ID</th><th>Nazwa</th><th class="text-end">Opcje</th></tr></thead>
                <tbody>
                @foreach($vm->animalCategories as $cat)
                    <tr>
                        <td>{{ $cat->id }}</td>
                        <td>
                            <form method="POST" action="{{ route('admin.settings.animal-categories.update', $cat) }}" class="d-flex gap-2 align-items-center">
                                @csrf
                                @method('PATCH')
                                <input type="text" name="name" class="form-control form-control-sm bg-dark text-light" value="{{ $cat->name }}" required>
                                <button class="btn btn-sm btn-outline-light" type="submit">Zapisz</button>
                            </form>
                        </td>
                        <td class="text-end">
                            <form method="POST" action="{{ route('admin.settings.animal-categories.destroy', $cat) }}" onsubmit="return confirm('Usunąć?')">
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
