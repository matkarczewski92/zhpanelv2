<div class="glass-card glass-table-wrapper">
    @php
        $activeRoadmap = collect($page->roadmaps ?? [])->firstWhere('id', $page->activeRoadmapId ?? 0);
    @endphp
    <div class="card-header">
        <div class="strike"><span>Roadmap</span></div>
    </div>

    <div class="card-body d-flex flex-column gap-3">
        @if (!empty($activeRoadmap))
            <div class="d-flex flex-column gap-2">
                <div class="small text-muted text-center">
                    Otwarta roadmapa: {{ $activeRoadmap['name'] }}
                </div>

                <form method="POST" action="{{ route('panel.litters-planning.roadmaps.update', $activeRoadmap['id']) }}" class="d-flex flex-column gap-1">
                    @csrf
                    @method('PUT')
                    <input type="hidden" name="return_tab" value="roadmap">
                    <div class="d-flex flex-wrap align-items-end gap-2">
                        <div class="flex-grow-1">
                            <label class="form-label small text-muted mb-1">Nazwa roadmapy</label>
                            <input name="name" class="form-control" value="{{ $activeRoadmap['name'] }}" required>
                        </div>
                        <button type="submit" class="btn btn-outline-light text-nowrap">Zmien nazwe</button>
                        <button type="submit" form="refreshActiveRoadmapForm" class="btn btn-outline-success text-nowrap">Aktualizuj zapisana roadmape</button>
                        <a href="{{ route('panel.litters-planning.index', ['tab' => 'roadmap']) }}" class="btn btn-outline-light text-nowrap">Nowy roadmap</a>
                    </div>
                    <div class="form-text text-muted">
                        Docelowy gen/trait: {{ $activeRoadmap['search_input'] }}
                    </div>
                </form>
                </div>
            <form id="refreshActiveRoadmapForm" method="POST" action="{{ route('panel.litters-planning.roadmaps.refresh', $activeRoadmap['id']) }}">
                @csrf
            </form>
        @else
            <form method="GET" action="{{ route('panel.litters-planning.index') }}" class="row g-2 align-items-start">
                <input type="hidden" name="tab" value="roadmap">
                <input type="hidden" name="roadmap_excluded_root_pairs" value="{{ implode(',', $page->roadmapExcludedRootPairs) }}">
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
                <div class="col-12 d-flex flex-wrap align-items-center justify-content-between gap-2">
                    <div class="btn-group btn-group-sm" role="group" aria-label="Priorytet roadmapy">
                        <input
                            type="radio"
                            class="btn-check"
                            name="roadmap_priority_mode"
                            id="roadmapPriorityFastest"
                            value="fastest"
                            @checked($page->roadmapPriorityMode === 'fastest')
                        >
                        <label class="btn btn-outline-light" for="roadmapPriorityFastest">Najszybszy</label>

                        <input
                            type="radio"
                            class="btn-check"
                            name="roadmap_priority_mode"
                            id="roadmapPriorityBestPercent"
                            value="highest_probability"
                            @checked($page->roadmapPriorityMode === 'highest_probability')
                        >
                        <label class="btn btn-outline-light" for="roadmapPriorityBestPercent">Najwiekszy % celu</label>
                    </div>
                    <div class="d-flex flex-wrap align-items-center gap-2 ms-lg-auto">
                        <label class="form-check-label small text-nowrap">
                            <input type="hidden" name="strict_visual_only" value="0">
                            <input
                                type="checkbox"
                                class="form-check-input me-1"
                                name="strict_visual_only"
                                value="1"
                                @checked($page->connectionStrictVisualOnly)
                            >
                            Bez dodatkowych genow wizualnych
                        </label>
                        <button type="submit" class="btn btn-primary">Buduj</button>
                        <a href="{{ route('panel.litters-planning.index', ['tab' => 'roadmap']) }}" class="btn btn-outline-light">Wyczysc</a>
                    </div>
                </div>
            </form>
        @endif

        @if (!empty($page->roadmapExpectedTraits))
            <div class="d-flex flex-wrap align-items-center gap-2">
                <span class="small text-muted">Cel projektu:</span>
                @foreach ($page->roadmapExpectedTraits as $trait)
                    <span class="badge text-bg-light">{{ $trait }}</span>
                @endforeach
            </div>

            <div class="small text-muted">
                Pokryte cele: {{ count($page->roadmapMatchedTraits) }} / {{ count($page->roadmapExpectedTraits) }}.
                @if ($page->connectionStrictVisualOnly)
                    Wlaczony filtr: bez dodatkowych genow wizualnych.
                @endif
                Tryb: {{ $page->roadmapPriorityMode === 'highest_probability' ? 'najwiekszy % celu' : 'najszybszy' }}.
                @if ($page->roadmapTargetReachable)
                    <span class="text-success">Cel mozliwy do osiagniecia w zaproponowanym roadmap.</span>
                @else
                    <span class="text-warning">Nie pokryto calego celu w limicie pokolen.</span>
                @endif
            </div>

            @if (
                empty($activeRoadmap)
                && $page->roadmapPriorityMode === 'fastest'
                && $page->roadmapTargetReachable
                && $page->roadmapRootPairKey !== ''
            )
                @php
                    $excludedForAlternative = collect($page->roadmapExcludedRootPairs)
                        ->merge([$page->roadmapRootPairKey])
                        ->filter()
                        ->unique()
                        ->values()
                        ->implode(',');
                @endphp
                <div class="d-flex justify-content-end">
                    <a
                        href="{{ route('panel.litters-planning.index', [
                            'tab' => 'roadmap',
                            'roadmap_expected_genes' => $page->roadmapSearchInput,
                            'roadmap_priority_mode' => $page->roadmapPriorityMode,
                            'roadmap_generations' => $page->roadmapGenerations > 0 ? $page->roadmapGenerations : null,
                            'strict_visual_only' => $page->connectionStrictVisualOnly ? 1 : 0,
                            'roadmap_excluded_root_pairs' => $excludedForAlternative,
                        ]) }}"
                        class="btn btn-sm btn-outline-warning"
                    >
                        Pokaz inna mozliwosc
                    </a>
                </div>
            @endif

            @if (empty($activeRoadmap))
                <form method="POST" action="{{ route('panel.litters-planning.roadmaps.store') }}" class="row g-2 align-items-end">
                    @csrf
                    <div class="col-12 col-lg-5">
                        <label class="form-label small text-muted mb-1" for="saveRoadmapName">Nazwa roadmapy</label>
                        <input
                            id="saveRoadmapName"
                            name="name"
                            class="form-control"
                            value="{{ old('name', 'Roadmap: ' . $page->roadmapSearchInput) }}"
                            placeholder="np. Peppermint projekt"
                            required
                        >
                    </div>
                    <div class="col-12 col-lg-4">
                        <label class="form-label small text-muted mb-1">Zapisywany cel</label>
                        <input class="form-control" value="{{ $page->roadmapSearchInput }}" readonly>
                        <input type="hidden" name="roadmap_expected_genes" value="{{ $page->roadmapSearchInput }}">
                        <input type="hidden" name="roadmap_priority_mode" value="{{ $page->roadmapPriorityMode }}">
                        <input type="hidden" name="roadmap_generations" value="{{ $page->roadmapGenerations > 0 ? $page->roadmapGenerations : '' }}">
                    </div>
                    <div class="col-12 col-lg-3 d-grid">
                        <button type="submit" class="btn btn-outline-success">Zapisz Roadmap</button>
                    </div>
                </form>
            @endif
        @endif
    </div>

    @if (empty($page->roadmapExpectedTraits))
        <div class="px-3 pb-3 small text-muted">Wpisz cele projektu i kliknij "Buduj", aby wygenerowac plan wielopokoleniowy.</div>
    @elseif (empty($page->roadmapSteps))
        <div class="px-3 pb-3 small text-muted">Brak sensownych krokow roadmap dla podanych celow i aktualnej hodowli.</div>
    @else
        <div class="px-3 pb-3 d-flex flex-column gap-2">
            @foreach ($page->roadmapSteps as $step)
                <div class="connections-matched-box @if(!empty($step['is_realized'])) roadmap-step-realized @endif">
                    <div class="d-flex justify-content-between align-items-center gap-2 mb-2">
                        <div class="fw-semibold roadmap-step-title">
                            Pokolenie {{ $step['generation'] }}: {{ $step['pairing_label'] }}
                            @if (!empty($step['is_realized']))
                                <span class="text-success">(ZREALIZOWANE)</span>
                            @endif
                        </div>
                        <div class="d-flex align-items-center gap-2 flex-nowrap">
                            @if (($step['can_create_litter'] ?? false) && !empty($step['parent_male_id']) && !empty($step['parent_female_id']))
                                @if (!empty($step['existing_litter_id']) && !empty($step['existing_litter_url']))
                                    <a href="{{ $step['existing_litter_url'] }}" class="small link-reset text-nowrap">
                                        Miot:
                                        {{ !empty($step['existing_litter_code']) ? $step['existing_litter_code'] : ('#' . $step['existing_litter_id']) }}
                                        @if(!empty($step['existing_litter_season']))
                                            ({{ $step['existing_litter_season'] }})
                                        @endif
                                    </a>
                                @endif
                                <a
                                    href="{{ route('panel.litters.create', ['parent_male' => $step['parent_male_id'], 'parent_female' => $step['parent_female_id']]) }}"
                                    class="btn btn-sm btn-outline-primary text-nowrap"
                                >
                                    Utworz miot
                                </a>
                            @else
                                <span class="small text-muted">Krok wirtualny</span>
                            @endif
                            @if (!empty($activeRoadmap))
                                <form
                                    method="POST"
                                    action="{{ route('panel.litters-planning.roadmaps.step-status', $activeRoadmap['id']) }}"
                                    class="d-inline-flex align-items-center gap-1 roadmap-step-status-form"
                                >
                                    @csrf
                                    @method('PATCH')
                                    <input type="hidden" name="generation" value="{{ $step['generation'] }}">
                                    <input type="hidden" name="realized" value="{{ !empty($step['is_realized']) ? 0 : 1 }}">
                                    <button
                                        type="submit"
                                        class="btn btn-sm text-nowrap @if(!empty($step['is_realized'])) btn-success @else btn-outline-success @endif"
                                    >
                                        @if (!empty($step['is_realized']))
                                            Cofnij realizacje
                                        @else
                                            Oznacz jako zrealizowane
                                        @endif
                                    </button>
                                </form>
                            @endif
                            <span class="connections-matched-count">{{ $step['probability_label'] }}</span>
                        </div>
                    </div>
                    @if (!empty($step['has_target_row']))
                        <div class="small mb-1 text-success">Cel osiagniety w tym etapie.</div>
                    @else
                        <div class="small mb-1">Zostaw: <span class="text-info">{{ $step['keeper_label'] }}</span></div>
                    @endif
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
