<div class="glass-card glass-table-wrapper">
    <div class="card-header">
        <div class="strike"><span>Mozliwe polaczenia</span></div>
    </div>

    <div class="card-body d-flex flex-column gap-3">
        <form method="GET" action="{{ route('panel.litters-planning.index') }}" class="row g-2 align-items-end">
            <input type="hidden" name="tab" value="possible-connections">
            <div class="col-12 col-lg-9">
                <label class="form-label small text-muted mb-1" for="possibleConnectionsGenes">Filtruj po genach/traits</label>
                <div class="position-relative">
                    <input
                        id="possibleConnectionsGenes"
                        name="possible_connections_genes"
                        class="form-control"
                        value="{{ $page->possibleConnectionsSearchInput }}"
                        placeholder="np. Amel, het Anery, Snow"
                        data-role="possible-connections-genes-input"
                        data-gene-suggestions='@json($page->possibleConnectionsGeneSuggestions)'
                        autocomplete="off"
                    >
                    <div class="connections-suggestions list-group position-absolute w-100 d-none" data-role="possible-connections-genes-suggestions"></div>
                </div>
                <div class="form-text text-muted">Mozesz wpisac wiele pozycji po przecinku. Obsluguje tez "het Gen".</div>
            </div>
            <div class="col-12 col-lg-3 d-flex gap-2">
                <button type="submit" class="btn btn-primary flex-grow-1">Filtruj</button>
                <a href="{{ route('panel.litters-planning.index', ['tab' => 'possible-connections']) }}" class="btn btn-outline-light">Wyczysc</a>
            </div>
        </form>

        @if (!empty($page->possibleConnectionsExpectedTraits))
            <div class="d-flex flex-wrap align-items-center gap-2">
                <span class="small text-muted">Szukane geny:</span>
                @foreach ($page->possibleConnectionsExpectedTraits as $trait)
                    <span class="badge text-bg-light">{{ $trait }}</span>
                @endforeach
            </div>
        @endif

        <div class="small text-muted">
            Wszystkie pary: {{ $page->possibleConnectionsTotalPairs }}.
            @if (!empty($page->possibleConnectionsExpectedTraits))
                Dopasowane pary: {{ $page->possibleConnectionsMatchedPairs }}.
            @endif
        </div>
    </div>

    @if ($page->possibleConnectionsPaginator->isEmpty())
        <div class="px-3 pb-3 small text-muted">Brak wynikow dla ustawionych filtrow.</div>
    @else
        <div class="d-flex flex-column gap-3 px-3 pb-3">
            @foreach ($page->possibleConnectionsPaginator as $row)
                <div class="glass-card p-3 d-flex flex-column gap-2">
                    <div class="small text-muted text-uppercase">Mozliwe polaczenie</div>
                    <div class="h6 mb-0">
                        <a href="{{ route('panel.animals.show', $row['female_id']) }}" class="link-reset">{{ $row['female_name'] }}</a>
                        <span class="text-muted">x</span>
                        <a href="{{ route('panel.animals.show', $row['male_id']) }}" class="link-reset">{{ $row['male_name'] }}</a>
                    </div>
                    <div class="small text-muted">
                        Prawdopodobienstwo dopasowania: {{ $row['probability_label'] }}.
                        Wynikow: {{ $row['matched_rows_count'] }}.
                    </div>
                    <div class="d-flex flex-column gap-2">
                        @foreach ($row['matched_rows'] as $matched)
                            <div class="connections-matched-row">
                                <span class="badge text-bg-secondary">{{ $matched['percentage_label'] }}</span>
                                @if ($matched['traits_name'] !== '')
                                    <span class="badge text-bg-light">{{ $matched['traits_name'] }}</span>
                                @endif
                                @foreach ($matched['visual_traits'] as $trait)
                                    <span class="badge text-bg-success">{{ $trait }}</span>
                                @endforeach
                                @foreach ($matched['carrier_traits'] as $trait)
                                    <span class="badge @if (str_starts_with($trait, '50%')) text-bg-secondary @elseif (str_starts_with($trait, '66%')) text-bg-info @else text-bg-primary @endif">{{ $trait }}</span>
                                @endforeach
                            </div>
                        @endforeach
                    </div>
                </div>
            @endforeach
        </div>

        <div class="px-3 pb-3">
            {{ $page->possibleConnectionsPaginator->onEachSide(1)->links() }}
        </div>
    @endif
</div>
