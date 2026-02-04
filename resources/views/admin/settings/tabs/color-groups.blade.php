<div class="tab-pane fade @if($vm->activeTab==='color-groups') show active @endif" id="tab-color-groups" role="tabpanel">
    <div class="card cardopacity">
        <div class="card-header d-flex justify-content-between align-items-center">
            <span>Grupy kolorystyczne</span>
            <form class="d-flex gap-2 align-items-center" method="POST" action="{{ route('admin.settings.color-groups.store') }}">
                @csrf
                <input type="text" name="name" class="form-control form-control-sm bg-dark text-light" placeholder="Nowa grupa" required>
                <input type="number" name="sort_order" class="form-control form-control-sm bg-dark text-light" value="0" min="0" style="max-width: 110px;">
                <div class="form-check form-switch m-0">
                    <input class="form-check-input" type="checkbox" name="is_active" value="1" id="newColorGroupActive" checked>
                    <label class="form-check-label small" for="newColorGroupActive">Aktywna</label>
                </div>
                <button class="btn btn-sm btn-primary" type="submit">Dodaj</button>
            </form>
        </div>
        <div class="table-responsive">
            <table class="table table-dark table-sm align-middle mb-0">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nazwa</th>
                        <th>Slug</th>
                        <th class="text-center">Sort</th>
                        <th class="text-center">Status</th>
                        <th class="text-center">Użycia</th>
                        <th class="text-end">Opcje</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($vm->colorGroups as $group)
                        <tr>
                            <td>{{ $group->id }}</td>
                            <td>{{ $group->name }}</td>
                            <td class="text-muted">{{ $group->slug }}</td>
                            <td class="text-center">{{ $group->sort_order }}</td>
                            <td class="text-center">
                                @if($group->is_active)
                                    <span class="badge text-bg-success">Aktywna</span>
                                @else
                                    <span class="badge text-bg-secondary">Nieaktywna</span>
                                @endif
                            </td>
                            <td class="text-center">{{ $group->animals_count }}</td>
                            <td class="text-end">
                                <form class="d-inline-flex gap-1 align-items-center" method="POST" action="{{ route('admin.settings.color-groups.update', $group) }}">
                                    @csrf
                                    @method('PATCH')
                                    <input type="text" name="name" class="form-control form-control-sm bg-dark text-light" value="{{ $group->name }}" required style="max-width: 180px;">
                                    <input type="number" name="sort_order" class="form-control form-control-sm bg-dark text-light" value="{{ $group->sort_order }}" min="0" style="max-width: 90px;">
                                    <div class="form-check form-switch m-0">
                                        <input class="form-check-input" type="checkbox" name="is_active" value="1" @checked($group->is_active)>
                                    </div>
                                    <button class="btn btn-sm btn-outline-light" type="submit">Zapisz</button>
                                </form>
                                <form method="POST" action="{{ route('admin.settings.color-groups.destroy', $group) }}" class="d-inline">
                                    @csrf
                                    @method('DELETE')
                                    <button class="btn btn-sm btn-outline-danger" type="submit" @disabled($group->animals_count > 0)>
                                        Usuń
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center text-muted">Brak grup kolorystycznych.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

