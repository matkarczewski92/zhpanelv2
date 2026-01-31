<div class="card cardopacity mb-3" id="molts">
    <div class="card-header d-flex flex-wrap align-items-center justify-content-between">
        <span>Wylinki</span>
        <span class="text-muted small">{{ $profile->moltsCount }} wpisów</span>
    </div>
    <div class="card-body">
        <form method="POST" action="{{ route('panel.animals.molts.store', $profile->animal['id']) }}" class="row g-2 align-items-end">
            @csrf
            <div class="col-8">
                <label class="form-label" for="molt_date">Data</label>
                <x-form.date-input id="molt_date" name="occurred_at" :default-today="true" />
            </div>
            <div class="col-4">
                <button class="btn btn-primary w-100" type="submit">Dodaj</button>
            </div>
        </form>
    </div>

    <div class="list-group list-group-flush feedings-list" id="moltsList" data-items='@json($profile->molts)'></div>
    <div class="card-body d-flex justify-content-between align-items-center">
        <button class="btn btn-outline-light btn-sm" type="button" id="moltsPrev">Poprzednie</button>
        <div class="text-muted small" id="moltsPageInfo"></div>
        <button class="btn btn-outline-light btn-sm" type="button" id="moltsNext">Następne</button>
    </div>
</div>

{{-- Edit modal --}}
<div class="modal fade" id="moltEditModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content photobg">
            <div class="modal-header">
                <h5 class="modal-title">Edytuj wylinkę</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Zamknij"></button>
            </div>
            <form method="POST" id="moltEditForm">
                @csrf
                @method('PUT')
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label" for="editMoltDate">Data</label>
                        <x-form.date-input id="editMoltDate" name="occurred_at" />
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-light" data-bs-dismiss="modal">Anuluj</button>
                    <button type="submit" class="btn btn-primary">Zapisz</button>
                </div>
            </form>
        </div>
    </div>
</div>
