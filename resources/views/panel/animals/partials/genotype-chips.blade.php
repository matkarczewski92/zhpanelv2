@if (count($chips))
    <div class="genotype-chips mb-3" id="genotypeChipsList">
        @foreach ($chips as $chip)
            <div class="genotype-chip genotype-chip--{{ $chip['type_code'] }}">
                <span class="genotype-chip__label" title="{{ $chip['type_label'] }}">{{ $chip['label'] }}</span>
                <button
                    type="button"
                    class="genotype-chip__remove"
                    data-genotype-delete="{{ $chip['delete_url'] }}"
                    aria-label="Usuń"
                >
                    &times;
                </button>
            </div>
        @endforeach
    </div>
@else
    <div class="text-muted small mb-3">Brak danych genotypu.</div>
@endif
