<div class="glass-card glass-table-wrapper">
    <div class="card-header">
        <div class="strike"><span>Roadmap</span></div>
    </div>

    <div class="card-body d-flex flex-column gap-3">
        <form method="GET" action="{{ route('panel.litters-planning.index') }}" class="row g-2 align-items-end">
            <input type="hidden" name="tab" value="roadmap">
            <div class="col-12 col-lg-8">
                <label class="form-label small text-muted mb-1" for="roadmapExpectedGenes">Docelowe geny / traity</label>
                <div class="position-relative">
                    <input
                        id="roadmapExpectedGenes"
                        name="roadmap_expected_genes"
                        class="form-control"
                        value="{{ $page->roadmapSearchInput }}"
                        placeholder="np. Snow, Diffused, het Cinder"
                        data-role="roadmap-genes-input"
                        data-gene-suggestions='@json($page->connectionGeneSuggestions)'
                        autocomplete="off"
                    >
                    <div class="connections-suggestions list-group position-absolute w-100 d-none" data-role="roadmap-genes-suggestions"></div>
                </div>
                <div class="form-text text-muted">Dziala jak w wyszukiwarce polaczen. Traity beda mapowane na geny.</div>
            </div>
            <div class="col-12 col-lg-2">
                <label class="form-label small text-muted mb-1" for="roadmapGenerations">Max pokolen</label>
                <select id="roadmapGenerations" name="roadmap_generations" class="form-select">
                    <option value="" @selected($page->roadmapGenerations === 0)>Dowolny</option>
                    @foreach ([2, 3, 4, 5] as $gen)
                        <option value="{{ $gen }}" @selected($page->roadmapGenerations === $gen)>{{ $gen }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-12 col-lg-2 d-flex gap-2">
                <button type="submit" class="btn btn-primary flex-grow-1">Buduj</button>
                <a href="{{ route('panel.litters-planning.index', ['tab' => 'roadmap']) }}" class="btn btn-outline-light">Wyczysc</a>
            </div>
        </form>

        @if (!empty($page->roadmapExpectedTraits))
            <div class="d-flex flex-wrap align-items-center gap-2">
                <span class="small text-muted">Cel projektu:</span>
                @foreach ($page->roadmapExpectedTraits as $trait)
                    <span class="badge text-bg-light">{{ $trait }}</span>
                @endforeach
            </div>

            <div class="small text-muted">
                Pokryte cele: {{ count($page->roadmapMatchedTraits) }} / {{ count($page->roadmapExpectedTraits) }}.
                @if ($page->roadmapTargetReachable)
                    <span class="text-success">Cel mozliwy do osiagniecia w zaproponowanym roadmap.</span>
                @else
                    <span class="text-warning">Nie pokryto calego celu w limicie pokolen.</span>
                @endif
            </div>
        @endif
    </div>

    @if (empty($page->roadmapExpectedTraits))
        <div class="px-3 pb-3 small text-muted">Wpisz cele projektu i kliknij "Buduj", aby wygenerowac plan wielopokoleniowy.</div>
    @elseif (empty($page->roadmapSteps))
        <div class="px-3 pb-3 small text-muted">Brak sensownych krokow roadmap dla podanych celow i aktualnej hodowli.</div>
    @else
        <div class="px-3 pb-3 d-flex flex-column gap-2">
            @foreach ($page->roadmapSteps as $step)
                <div class="connections-matched-box">
                    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-2">
                        <div class="fw-semibold">Pokolenie {{ $step['generation'] }}: {{ $step['pairing_label'] }}</div>
                        <div class="d-flex flex-wrap align-items-center gap-2">
                            @if (($step['can_create_litter'] ?? false) && !empty($step['parent_male_id']) && !empty($step['parent_female_id']))
                                <a
                                    href="{{ route('panel.litters.create', ['parent_male' => $step['parent_male_id'], 'parent_female' => $step['parent_female_id']]) }}"
                                    class="btn btn-sm btn-outline-primary"
                                >
                                    Utworz miot
                                </a>
                            @else
                                <span class="small text-muted">Krok wirtualny</span>
                            @endif
                            <span class="connections-matched-count">{{ $step['probability_label'] }}</span>
                        </div>
                    </div>
                    <div class="small mb-1">Zostaw: <span class="text-info">{{ $step['keeper_label'] }}</span></div>
                    <div class="d-flex flex-wrap gap-1">
                        @foreach ($step['matched_targets'] as $trait)
                            <span class="badge text-bg-success">{{ $trait }}</span>
                        @endforeach
                    </div>
                    <div class="small text-muted mt-2">Postep celu: {{ $step['matched_count'] }} / {{ $step['total_targets'] }}</div>

                    <div class="table-responsive mt-2">
                        <table class="table glass-table table-sm align-middle mb-0">
                            <thead>
                                <tr class="text-muted small">
                                    <th style="width: 110px;">Procent</th>
                                    <th style="width: 130px;">Decyzja</th>
                                    <th style="width: 22%;">Nazwa</th>
                                    <th>Traits</th>
                                    <th style="width: 220px;">Pokryte cele</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($step['offspring_rows'] as $offspring)
                                    <tr class="@if($offspring['is_keeper']) roadmap-keeper-row @endif @if($offspring['is_target']) roadmap-target-row @endif">
                                        <td>{{ $offspring['percentage_label'] }}</td>
                                        <td>
                                            @if ($offspring['is_keeper'] && $offspring['is_target'])
                                                <span class="badge text-bg-warning text-dark">ZOSTAW + CEL</span>
                                            @elseif ($offspring['is_target'])
                                                <span class="badge text-bg-success">CEL</span>
                                            @elseif ($offspring['is_keeper'])
                                                <span class="badge text-bg-warning text-dark">ZOSTAW</span>
                                            @else
                                                <span class="badge text-bg-secondary">Opcja</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if ($offspring['traits_name'] !== '')
                                                <span class="badge text-bg-light">{{ $offspring['traits_name'] }}</span>
                                            @else
                                                <span class="text-muted">-</span>
                                            @endif
                                        </td>
                                        <td>
                                            @foreach ($offspring['visual_traits'] as $trait)
                                                <span class="badge text-bg-success">{{ $trait }}</span>
                                            @endforeach
                                            @foreach ($offspring['carrier_traits'] as $trait)
                                                <span class="badge @if (str_starts_with($trait, '50%')) text-bg-secondary @elseif (str_starts_with($trait, '66%')) text-bg-info @else text-bg-primary @endif">{{ $trait }}</span>
                                            @endforeach
                                        </td>
                                        <td>
                                            @if (!empty($offspring['matched_targets']))
                                                @foreach ($offspring['matched_targets'] as $trait)
                                                    <span class="badge text-bg-success">{{ $trait }}</span>
                                                @endforeach
                                            @else
                                                <span class="text-muted">-</span>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endforeach

            @if (!empty($page->roadmapMissingTraits))
                <div class="small text-warning">
                    Brakujace cele po roadmap:
                    @foreach ($page->roadmapMissingTraits as $trait)
                        <span class="badge text-bg-secondary">{{ $trait }}</span>
                    @endforeach
                </div>
            @endif
        </div>
    @endif
</div>
