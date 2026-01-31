<form method="POST" action="{{ route('panel.animals.molts.store', $animal) }}" class="vstack gap-3">
    @csrf
    <div>
        <label class="form-label" for="molt_occurred_at">Data (opcjonalnie)</label>
        <x-form.date-input
            id="molt_occurred_at"
            name="occurred_at"
            :default-today="true"
        />
    </div>
    <button class="btn btn-primary" type="submit">Dodaj</button>
</form>
