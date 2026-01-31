<form method="POST" action="{{ route('panel.animals.feedings.store', $animal) }}" class="vstack gap-3">
    @csrf
    <div>
        <label class="form-label" for="feed_id">Karma</label>
        <select id="feed_id" name="feed_id" class="form-select">
            @foreach ($feeds as $feed)
                <option value="{{ $feed->id }}">{{ $feed->name }}</option>
            @endforeach
        </select>
    </div>
    <div>
        <label class="form-label" for="amount">Ilość</label>
        <input id="amount" name="amount" type="number" class="form-control" required />
    </div>
    <div>
        <label class="form-label" for="feeding_occurred_at">Data (opcjonalnie)</label>
        <x-form.date-input
            id="feeding_occurred_at"
            name="occurred_at"
            :default-today="true"
        />
    </div>
    <button class="btn btn-primary" type="submit">Dodaj</button>
</form>
