<div class="list-group list-group-flush glass-table">
    @forelse($items as $w)
        <div class="list-group-item d-flex justify-content-between bg-transparent px-0">
            <span>{{ $w['date_label'] }}</span>
            <span class="fw-semibold">{{ $w['value'] }} g</span>
        </div>
    @empty
        <div class="text-muted small">Brak danych wag.</div>
    @endforelse
</div>
<div class="d-flex justify-content-between align-items-center mt-2">
    <button class="btn btn-outline-light btn-sm" type="button" data-page="{{ $pagination['prev_page'] ?? '' }}" @disabled(!$pagination['prev_page'])>Poprzednie</button>
    <div class="text-muted small">Strona {{ $pagination['current_page'] }} / {{ $pagination['last_page'] }}</div>
    <button class="btn btn-outline-light btn-sm" type="button" data-page="{{ $pagination['next_page'] ?? '' }}" @disabled(!$pagination['next_page'])>Następne</button>
</div>
