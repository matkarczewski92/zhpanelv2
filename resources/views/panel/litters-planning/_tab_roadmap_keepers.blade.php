<div class="glass-card glass-table-wrapper">
    <div class="card-header">
        <div class="strike"><span>Do zostawienia (z zapisanych roadmap)</span></div>
    </div>

    <div class="table-responsive">
        <table class="table glass-table table-sm align-middle mb-0">
            <thead>
                <tr class="text-muted small">
                    <th>Roadmapa</th>
                    <th class="text-center" style="width: 90px;">Pokolenie</th>
                    <th style="width: 30%;">Z laczenia</th>
                    <th>Do zostawienia</th>
                    <th class="text-center" style="width: 190px;">Miot (jesli utworzony)</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($page->roadmapKeepers as $row)
                    <tr>
                        <td>
                            <a
                                href="{{ route('panel.litters-planning.index', ['tab' => 'roadmap', 'roadmap_id' => $row['roadmap_id']]) }}"
                                class="link-reset"
                            >
                                {{ $row['roadmap_name'] }}
                            </a>
                        </td>
                        <td class="text-center">{{ $row['generation'] > 0 ? $row['generation'] : '-' }}</td>
                        <td>{{ $row['pairing_label'] }}</td>
                        <td>
                            <span class="badge text-bg-warning text-dark">{{ $row['keeper_label'] }}</span>
                        </td>
                        <td class="text-center">
                            @if (!empty($row['litter_id']) && !empty($row['litter_url']))
                                <a href="{{ $row['litter_url'] }}" class="link-reset">
                                    #{{ $row['litter_id'] }} @if(!empty($row['litter_code'])) ({{ $row['litter_code'] }}) @endif
                                </a>
                            @else
                                <span class="text-muted">Brak</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="text-center text-muted py-3">
                            Brak pozycji do zostawienia. Zapisz roadmapy i odswiez, aby zobaczyc rekomendacje.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

