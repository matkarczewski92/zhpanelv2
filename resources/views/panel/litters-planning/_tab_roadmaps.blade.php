<div class="glass-card glass-table-wrapper">
    <div class="card-header">
        <div class="strike"><span>Zapisane roadmapy</span></div>
    </div>

    <div class="table-responsive">
        <table class="table glass-table table-sm align-middle mb-0">
            <thead>
                <tr class="text-muted small">
                    <th>Nazwa</th>
                    <th style="width: 28%;">Cel</th>
                    <th class="text-center" style="width: 95px;">Max pokolen</th>
                    <th class="text-center" style="width: 150px;">Pokrycie</th>
                    <th class="text-center" style="width: 80px;">Kroki</th>
                    <th class="text-center" style="width: 150px;">Zrealizowane</th>
                    <th style="width: 145px;">Aktualizowano</th>
                    <th style="width: 145px;">Odswiezono</th>
                    <th class="text-end" style="width: 290px;">Akcje</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($page->roadmaps as $roadmap)
                    @php
                        $isActive = (int) ($page->activeRoadmapId ?? 0) === (int) $roadmap['id'];
                    @endphp
                    <tr @if($isActive) class="roadmap-target-row" @endif>
                        <td class="fw-semibold">{{ $roadmap['name'] }}</td>
                        <td>
                            @if (!empty($roadmap['expected_traits']))
                                <div class="d-flex flex-wrap gap-1">
                                    @foreach ($roadmap['expected_traits'] as $trait)
                                        <span class="badge text-bg-light">{{ $trait }}</span>
                                    @endforeach
                                </div>
                            @else
                                <span class="text-muted">-</span>
                            @endif
                        </td>
                        <td class="text-center">
                            {{ $roadmap['generations'] > 0 ? $roadmap['generations'] : 'Dowolny' }}
                        </td>
                        <td class="text-center">
                            @php
                                $matched = count($roadmap['matched_traits'] ?? []);
                                $total = count($roadmap['expected_traits'] ?? []);
                                $completed = count($roadmap['completed_generations'] ?? []);
                                $stepsCount = (int) ($roadmap['steps_count'] ?? 0);
                            @endphp
                            <span class="@if($roadmap['target_reachable']) text-success @else text-warning @endif">
                                {{ $matched }} / {{ $total }}
                            </span>
                        </td>
                        <td class="text-center">{{ $stepsCount }}</td>
                        <td class="text-center">
                            <span class="@if($completed > 0) text-success @else text-muted @endif">
                                {{ $completed }} z {{ $stepsCount }} etapow
                            </span>
                        </td>
                        <td>{{ $roadmap['updated_at_label'] }}</td>
                        <td>{{ $roadmap['last_refreshed_at_label'] }}</td>
                        <td class="text-center align-middle">
                            <div class="roadmaps-actions h-100">
                                <a
                                    href="{{ route('panel.litters-planning.index', ['tab' => 'roadmap', 'roadmap_id' => $roadmap['id']]) }}"
                                    class="btn btn-sm btn-outline-primary"
                                >
                                    Otworz
                                </a>

                                <form method="POST" action="{{ route('panel.litters-planning.roadmaps.refresh', $roadmap['id']) }}" class="roadmaps-action-form">
                                    @csrf
                                    <button type="submit" class="btn btn-sm btn-outline-success">Aktualizuj</button>
                                </form>

                                <details class="roadmaps-action-details">
                                    <summary class="btn btn-sm btn-outline-light roadmaps-action-summary">Edytuj</summary>
                                    <div class="connections-matched-box mt-2 text-start" style="min-width: 360px;">
                                        <form method="POST" action="{{ route('panel.litters-planning.roadmaps.update', $roadmap['id']) }}" class="d-flex flex-column gap-2">
                                            @csrf
                                            @method('PUT')
                                            <input type="hidden" name="return_tab" value="roadmaps">
                                            <div>
                                                <label class="form-label small text-muted mb-1">Nazwa</label>
                                                <input
                                                    name="name"
                                                    class="form-control form-control-sm"
                                                    value="{{ $roadmap['name'] }}"
                                                    required
                                                >
                                            </div>
                                            <button type="submit" class="btn btn-sm btn-primary">Zapisz nazwe</button>
                                        </form>
                                    </div>
                                </details>

                                <form method="POST" action="{{ route('panel.litters-planning.roadmaps.destroy', $roadmap['id']) }}" class="roadmaps-action-form">
                                    @csrf
                                    @method('DELETE')
                                    <button
                                        type="submit"
                                        class="btn btn-sm btn-outline-danger"
                                        onclick="return confirm(@js('Usun roadmap ' . $roadmap['name'] . '?'))"
                                    >
                                        Usun
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9" class="text-center text-muted py-3">Brak zapisanych roadmap.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
