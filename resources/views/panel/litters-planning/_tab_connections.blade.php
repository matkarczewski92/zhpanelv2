<div class="glass-card glass-table-wrapper">
    <div class="card-header">
        <div class="strike"><span>Wyszukiwarka polaczen</span></div>
    </div>

    <div class="card-body d-flex flex-column gap-3">
        <form method="GET" action="{{ route('panel.litters-planning.index') }}" class="row g-2 align-items-end">
            <input type="hidden" name="tab" value="connections">
            <div class="col-12 col-lg-9">
                <label class="form-label small text-muted mb-1" for="connectionExpectedGenes">Oczekiwane geny potomstwa</label>
                <div class="position-relative">
                    <input
                        id="connectionExpectedGenes"
                        name="expected_genes"
                        class="form-control"
                        value="{{ $page->connectionSearchInput }}"
                        placeholder="np. Amel, Anery, het Cinder"
                        data-role="connections-genes-input"
                        data-gene-suggestions='@json($page->connectionGeneSuggestions)'
                        autocomplete="off"
                    >
                    <div class="connections-suggestions list-group position-absolute w-100 d-none" data-role="connections-genes-suggestions"></div>
                </div>
                <div class="form-text text-muted">Oddzielaj geny przecinkiem, np. "Amel, het Cinder". Mozesz wpisac tez trait, np. "Snow".</div>
            </div>
            <div class="col-12 col-lg-3 d-flex flex-column gap-2">
                <label class="form-check-label small">
                    <input
                        type="checkbox"
                        class="form-check-input me-1"
                        name="strict_visual_only"
                        value="1"
                        @checked($page->connectionStrictVisualOnly)
                    >
                    Bez dodatkowych genow wizualnych
                </label>
                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary flex-grow-1">Szukaj</button>
                    <a href="{{ route('panel.litters-planning.index', ['tab' => 'connections']) }}" class="btn btn-outline-light">Wyczysc</a>
                </div>
            </div>
        </form>

        @if (!empty($page->connectionExpectedTraits))
            <div class="d-flex flex-wrap align-items-center gap-2">
                <span class="small text-muted">Szukane geny:</span>
                @foreach ($page->connectionExpectedTraits as $trait)
                    <span class="badge text-bg-light">{{ $trait }}</span>
                @endforeach
            </div>

            <div class="small text-muted">
                Sprawdzone pary: {{ $page->connectionCheckedPairs }}.
                Znalezione dopasowania: {{ count($page->connectionSearchRows) }}.
                @if ($page->connectionStrictVisualOnly)
                    Wlaczony filtr: bez dodatkowych genow wizualnych.
                @endif
            </div>
        @endif
    </div>

    @if (empty($page->connectionExpectedTraits))
        <div class="px-3 pb-3 small text-muted">Wpisz oczekiwane geny i kliknij "Szukaj", aby wyznaczyc pary samica-samiec.</div>
    @elseif (empty($page->connectionSearchRows))
        <div class="px-3 pb-3 small text-muted">Brak par, ktore daja takie potomstwo.</div>
    @else
        <div class="table-responsive">
            <table class="table glass-table table-sm align-middle mb-0">
                <thead>
                    <tr class="text-muted small">
                        <th style="width: 130px;">Prawdopodobienstwo</th>
                        <th style="width: 25%;">Samica</th>
                        <th style="width: 25%;">Samiec</th>
                        <th>Dopasowane wyniki</th>
                        <th style="width: 140px;" class="text-end">Akcje</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($page->connectionSearchRows as $row)
                        <tr>
                            <td class="fw-semibold">{{ $row['probability_label'] }}</td>
                            <td>
                                <a href="{{ route('panel.animals.show', $row['female_id']) }}" class="link-reset">
                                    {{ $row['female_name'] }}
                                </a>
                            </td>
                            <td>
                                <a href="{{ route('panel.animals.show', $row['male_id']) }}" class="link-reset">
                                    {{ $row['male_name'] }}
                                </a>
                            </td>
                            <td>
                                <div class="connections-matched-box d-flex flex-column gap-2">
                                    <div>
                                        <span class="connections-matched-count">{{ $row['matched_rows_count'] }} dopasowania</span>
                                    </div>
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
                            </td>
                            <td class="text-end">
                                <a
                                    href="{{ route('panel.litters.create', ['parent_male' => $row['male_id'], 'parent_female' => $row['female_id']]) }}"
                                    class="btn btn-sm btn-outline-primary"
                                >
                                    Realizuj miot
                                </a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>
