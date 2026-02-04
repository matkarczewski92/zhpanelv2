<div class="tab-pane fade @if($vm->activeTab==='finance-categories') show active @endif" id="tab-finance-categories" role="tabpanel">
    <div class="card cardopacity">
        <div class="card-header d-flex justify-content-between align-items-center">
            <span>Kategorie finansowe</span>
            <form class="d-flex gap-2" method="POST" action="{{ route('admin.settings.finance-categories.store') }}">
                @csrf
                <input type="text" name="name" class="form-control form-control-sm bg-dark text-light" placeholder="Nowa kategoria" required>
                <button class="btn btn-sm btn-primary" type="submit">Dodaj</button>
            </form>
        </div>
        <div class="table-responsive">
            <table class="table table-dark table-sm align-middle mb-0">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nazwa</th>
                        <th class="text-center">Uzycia</th>
                        <th class="text-end">Opcje</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($vm->financeCategories as $category)
                        <tr>
                            <td>{{ $category->id }}</td>
                            <td>{{ $category->name }}</td>
                            <td class="text-center">{{ $category->finances_count }}</td>
                            <td class="text-end">
                                <form class="d-inline-flex gap-1 align-items-center" method="POST" action="{{ route('admin.settings.finance-categories.update', $category) }}">
                                    @csrf
                                    @method('PATCH')
                                    <input type="text" name="name" class="form-control form-control-sm bg-dark text-light" value="{{ $category->name }}" required style="max-width: 220px;">
                                    <button class="btn btn-sm btn-outline-light" type="submit">Zapisz</button>
                                </form>
                                <form method="POST" action="{{ route('admin.settings.finance-categories.destroy', $category) }}" class="d-inline">
                                    @csrf
                                    @method('DELETE')
                                    <button
                                        class="btn btn-sm btn-outline-danger"
                                        type="submit"
                                        @disabled($category->id <= 5 || $category->finances_count > 0)
                                    >
                                        Usun
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
