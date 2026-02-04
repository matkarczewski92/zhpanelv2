<div class="glass-card glass-table-wrapper">
    <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
        <div class="strike flex-grow-1"><span>Mozliwe potomstwo (sezon)</span></div>
        <div class="d-flex flex-wrap gap-2">
            @foreach ($page->seasons as $season)
                <a
                    href="{{ route('panel.litters-planning.index', ['tab' => 'offspring', 'season' => $season]) }}"
                    class="btn btn-sm @if($page->selectedSeason === $season) btn-primary @else btn-outline-light @endif"
                >
                    {{ $season }}
                </a>
            @endforeach
        </div>
    </div>

    <div class="table-responsive">
        <table class="table glass-table table-sm align-middle mb-0">
            <thead>
                <tr class="text-muted small">
                    <th style="width: 70px;">Id</th>
                    <th style="width: 120px;">Kod miotu</th>
                    <th style="width: 90px;">Sezon</th>
                    <th style="width: 20%;">Nazwa</th>
                    <th>Traits</th>
                    <th style="width: 90px;" class="text-center">#Traits</th>
                    <th style="width: 100px;" class="text-center">Percent</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($page->seasonOffspringRows as $row)
                    <tr>
                        <td>{{ $row['litter_id'] }}</td>
                        <td><a href="{{ $row['litter_url'] }}" class="link-reset">{{ $row['litter_code'] }}</a></td>
                        <td>{{ $row['season'] }}</td>
                        <td>
                            @if ($row['traits_name'] !== '')
                                <span class="badge text-bg-light">{{ $row['traits_name'] }}</span>
                            @endif
                        </td>
                        <td>
                            @foreach ($row['visual_traits'] as $trait)
                                <span class="badge text-bg-success">{{ $trait }}</span>
                            @endforeach
                            @foreach ($row['carrier_traits'] as $trait)
                                <span class="badge @if (str_starts_with($trait, '50%')) text-bg-secondary @elseif (str_starts_with($trait, '66%')) text-bg-info @else text-bg-primary @endif">{{ $trait }}</span>
                            @endforeach
                        </td>
                        <td class="text-center">{{ $row['traits_count'] }}</td>
                        <td class="text-center">{{ $row['percentage_label'] }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="text-center text-muted py-3">Brak danych dla sezonu {{ $page->selectedSeason }}.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
