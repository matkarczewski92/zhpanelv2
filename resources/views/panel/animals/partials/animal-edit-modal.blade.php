<div class="modal fade" id="animalEditModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content photobg">
            <div class="modal-header">
                <h5 class="modal-title">Edycja zwierzęcia</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Zamknij"></button>
            </div>
            <form method="POST" action="{{ $profile->edit['update_url'] ?? '#' }}">
                @csrf
                @method('PUT')
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Imię</label>
                            <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name', $profile->edit['values']['name'] ?? '') }}" required>
                            @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Drugie imię</label>
                            <input type="text" name="second_name" class="form-control @error('second_name') is-invalid @enderror" value="{{ old('second_name', $profile->edit['values']['second_name'] ?? '') }}">
                            @error('second_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Typ</label>
                            <select name="animal_type_id" class="form-select @error('animal_type_id') is-invalid @enderror" required>
                                <option value="">-- wybierz --</option>
                                @foreach ($profile->edit['options']['types'] ?? [] as $type)
                                    <option value="{{ $type['id'] }}" @selected(old('animal_type_id', $profile->edit['values']['animal_type_id'] ?? null) == $type['id'])>{{ $type['name'] }}</option>
                                @endforeach
                            </select>
                            @error('animal_type_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Kategoria</label>
                            <select name="animal_category_id" class="form-select @error('animal_category_id') is-invalid @enderror" required>
                                <option value="">-- wybierz --</option>
                                @foreach ($profile->edit['options']['categories'] ?? [] as $cat)
                                    <option value="{{ $cat['id'] }}" @selected(old('animal_category_id', $profile->edit['values']['animal_category_id'] ?? null) == $cat['id'])>{{ $cat['name'] }}</option>
                                @endforeach
                            </select>
                            @error('animal_category_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Płeć</label>
                            <select name="sex" class="form-select @error('sex') is-invalid @enderror" required>
                                @foreach ($profile->edit['options']['sex'] ?? [] as $sex)
                                    <option value="{{ $sex['value'] }}" @selected(old('sex', $profile->edit['values']['sex'] ?? null) == $sex['value'])>{{ $sex['label'] }}</option>
                                @endforeach
                            </select>
                            @error('sex')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Data urodzenia</label>
                            <input type="date" name="date_of_birth" class="form-control @error('date_of_birth') is-invalid @enderror" value="{{ old('date_of_birth', $profile->edit['values']['date_of_birth'] ?? '') }}" required>
                            @error('date_of_birth')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Interwał karmienia (dni)</label>
                            <input type="number" name="feed_interval" class="form-control @error('feed_interval') is-invalid @enderror" value="{{ old('feed_interval', $profile->edit['values']['feed_interval'] ?? '') }}" min="0">
                            @error('feed_interval')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Domyślna karma</label>
                            <select name="feed_id" class="form-select @error('feed_id') is-invalid @enderror">
                                <option value="">-- wybierz --</option>
                                @foreach ($profile->edit['options']['feeds'] ?? [] as $feed)
                                    <option value="{{ $feed['id'] }}" @selected(old('feed_id', $profile->edit['values']['feed_id'] ?? null) == $feed['id'])>{{ $feed['name'] }}</option>
                                @endforeach
                            </select>
                            @error('feed_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Publiczny tag</label>
                            <input type="text" class="form-control" value="{{ $profile->edit['values']['public_profile_tag'] ?? '-' }}" disabled readonly>
                        </div>
                    </div>
                </div>
                <div class="modal-footer flex-wrap gap-2">
                    <button type="button" class="btn btn-outline-light" data-bs-dismiss="modal">Zamknij</button>
                    <button type="submit" form="deleteAnimalForm" class="btn btn-outline-danger" onclick="return confirm('{{ ($profile->edit['is_deleted_category'] ?? false) ? 'Usunąć TRWALE? Tej operacji nie można cofnąć.' : 'Przenieść do Usunięte?' }}')">Usuń</button>
                    <button type="submit" class="btn btn-primary">Zapisz</button>
                </div>
            </form>
            <form id="deleteAnimalForm" method="POST" action="{{ $profile->edit['delete_url'] ?? '#' }}">
                @csrf
            </form>
        </div>
    </div>
</div>
