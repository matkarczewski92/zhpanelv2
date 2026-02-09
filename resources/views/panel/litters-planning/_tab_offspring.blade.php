<div class="glass-card glass-table-wrapper">
    <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
        <div class="strike flex-grow-1"><span>Mozliwe potomstwo (sezon)</span></div>
        <div class="d-flex flex-wrap gap-2">
            @foreach ($page->seasons as $season)
                <a
                    href="{{ route('panel.litters-planning.index', [
                        'tab' => 'offspring',
                        'season' => $season,
                        'offspring_sort' => $page->seasonOffspringSort,
                        'offspring_direction' => $page->seasonOffspringDirection,
                        'offspring_summary_sort' => $page->seasonOffspringSummarySort,
                        'offspring_summary_direction' => $page->seasonOffspringSummaryDirection,
                    ]) }}"
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
                    <th style="width: 70px;">
                        <a class="link-reset" href="{{ $page->seasonOffspringSortLinks['litter_id']['url'] }}">
                            Id @if($page->seasonOffspringSortLinks['litter_id']['indicator'])<span>{{ $page->seasonOffspringSortLinks['litter_id']['indicator'] }}</span>@endif
                        </a>
                    </th>
                    <th style="width: 120px;">
                        <a class="link-reset" href="{{ $page->seasonOffspringSortLinks['litter_code']['url'] }}">
                            Kod miotu @if($page->seasonOffspringSortLinks['litter_code']['indicator'])<span>{{ $page->seasonOffspringSortLinks['litter_code']['indicator'] }}</span>@endif
                        </a>
                    </th>
                    <th style="width: 90px;">
                        <a class="link-reset" href="{{ $page->seasonOffspringSortLinks['season']['url'] }}">
                            Sezon @if($page->seasonOffspringSortLinks['season']['indicator'])<span>{{ $page->seasonOffspringSortLinks['season']['indicator'] }}</span>@endif
                        </a>
                    </th>
                    <th style="width: 20%;">
                        <a class="link-reset" href="{{ $page->seasonOffspringSortLinks['traits_name']['url'] }}">
                            Nazwa @if($page->seasonOffspringSortLinks['traits_name']['indicator'])<span>{{ $page->seasonOffspringSortLinks['traits_name']['indicator'] }}</span>@endif
                        </a>
                    </th>
                    <th>
                        <a class="link-reset" href="{{ $page->seasonOffspringSortLinks['traits']['url'] }}">
                            Traits @if($page->seasonOffspringSortLinks['traits']['indicator'])<span>{{ $page->seasonOffspringSortLinks['traits']['indicator'] }}</span>@endif
                        </a>
                    </th>
                    <th style="width: 90px;" class="text-center">
                        <a class="link-reset" href="{{ $page->seasonOffspringSortLinks['traits_count']['url'] }}">
                            #Traits @if($page->seasonOffspringSortLinks['traits_count']['indicator'])<span>{{ $page->seasonOffspringSortLinks['traits_count']['indicator'] }}</span>@endif
                        </a>
                    </th>
                    <th style="width: 100px;" class="text-center">
                        <a class="link-reset" href="{{ $page->seasonOffspringSortLinks['percentage']['url'] }}">
                            Percent @if($page->seasonOffspringSortLinks['percentage']['indicator'])<span>{{ $page->seasonOffspringSortLinks['percentage']['indicator'] }}</span>@endif
                        </a>
                    </th>
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

    <div class="mt-5"></div>
    <div class="card-header">
        <div class="strike"><span>Możliwe potomstwo - podsumowanie</span></div>
    </div>
    <div class="table-responsive">
        <table class="table glass-table table-sm align-middle mb-0">
            <thead>
                <tr class="text-muted small">
                    <th style="width: 30%;">
                        <a class="link-reset" href="{{ $page->seasonOffspringSummarySortLinks['morph_name']['url'] }}">
                            Nazwa @if($page->seasonOffspringSummarySortLinks['morph_name']['indicator'])<span>{{ $page->seasonOffspringSummarySortLinks['morph_name']['indicator'] }}</span>@endif
                        </a>
                    </th>
                    <th style="width: 140px;" class="text-center">
                        <a class="link-reset" href="{{ $page->seasonOffspringSummarySortLinks['percentage_sum']['url'] }}">
                            Suma % @if($page->seasonOffspringSummarySortLinks['percentage_sum']['indicator'])<span>{{ $page->seasonOffspringSummarySortLinks['percentage_sum']['indicator'] }}</span>@endif
                        </a>
                    </th>
                    <th style="width: 140px;" class="text-center">
                        <a class="link-reset" href="{{ $page->seasonOffspringSummarySortLinks['avg_eggs_to_incubation']['url'] }}">
                            Śr. jaj do inkubacji @if($page->seasonOffspringSummarySortLinks['avg_eggs_to_incubation']['indicator'])<span>{{ $page->seasonOffspringSummarySortLinks['avg_eggs_to_incubation']['indicator'] }}</span>@endif
                        </a>
                    </th>
                    <th style="width: 120px;" class="text-center">
                        <a class="link-reset" href="{{ $page->seasonOffspringSummarySortLinks['numeric_count']['url'] }}">
                            Liczbowo @if($page->seasonOffspringSummarySortLinks['numeric_count']['indicator'])<span>{{ $page->seasonOffspringSummarySortLinks['numeric_count']['indicator'] }}</span>@endif
                        </a>
                    </th>
                    <th style="width: 100px;" class="text-center">
                        <a class="link-reset" href="{{ $page->seasonOffspringSummarySortLinks['litters_count']['url'] }}">
                            Liczba miotów @if($page->seasonOffspringSummarySortLinks['litters_count']['indicator'])<span>{{ $page->seasonOffspringSummarySortLinks['litters_count']['indicator'] }}</span>@endif
                        </a>
                    </th>
                    <th style="width: 100px;" class="text-center">
                        <a class="link-reset" href="{{ $page->seasonOffspringSummarySortLinks['grouped_rows']['url'] }}">
                            Wystąpienia @if($page->seasonOffspringSummarySortLinks['grouped_rows']['indicator'])<span>{{ $page->seasonOffspringSummarySortLinks['grouped_rows']['indicator'] }}</span>@endif
                        </a>
                    </th>
                </tr>
            </thead>
            <tbody>
                @forelse ($page->seasonOffspringSummaryRows as $row)
                    <tr>
                        <td>
                            @if ($row['morph_name'] !== '-')
                                <span class="badge text-bg-light">{{ $row['morph_name'] }}</span>
                            @else
                                <span class="text-muted">-</span>
                            @endif
                        </td>
                        <td class="text-center fw-semibold">{{ $row['percentage_sum_label'] }}</td>
                        <td class="text-center">{{ $row['avg_eggs_to_incubation_label'] }}</td>
                        <td class="text-center fw-semibold">{{ $row['numeric_count_label'] }}</td>
                        <td class="text-center">{{ $row['litters_count'] }}</td>
                        <td class="text-center">{{ $row['grouped_rows'] }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="text-center text-muted py-3">Brak danych do podsumowania.</td>
                    </tr>
                @endforelse
            </tbody>
            @php
                $summaryNumericCountTotal = collect($page->seasonOffspringSummaryRows)
                    ->sum(fn (array $row): float => (float) ($row['numeric_count'] ?? 0));
            @endphp
            <tfoot>
                <tr class="border-top border-light border-opacity-25">
                    <td colspan="3" class="text-end fw-semibold">Suma Liczbowo</td>
                    <td class="text-center fw-semibold">{{ number_format($summaryNumericCountTotal, 0, ',', ' ') }}</td>
                    <td></td>
                    <td></td>
                </tr>
            </tfoot>
        </table>
    </div>
</div>
