<form method="POST" action="{{ route('panel.animals.weights.store', $animal) }}" class="vstack gap-3">
    @csrf
    <div>
        <label class="form-label" for="weight_value">Waga</label>
        <input id="weight_value" name="value" type="number" step="0.01" class="form-control" required />
    </div>
    <div>
        <label class="form-label" for="weight_occurred_at">Data (opcjonalnie)</label>
        <x-form.date-input
            id="weight_occurred_at"
            name="occurred_at"
            :default-today="true"
        />
    </div>
    <button class="btn btn-primary" type="submit">Dodaj</button>
</form>
