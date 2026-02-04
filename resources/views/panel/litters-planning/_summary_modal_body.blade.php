@if (empty($summaryRows))
    <p class="text-muted mb-0">Nie wybrano zadnych polaczen.</p>
@else
    <div class="small text-muted mb-2">Razem: {{ count($summaryRows) }}</div>

    <div class="d-flex flex-column gap-4">
        @foreach ($summaryRows as $pair)
            <div>
                <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-2">
                    <div class="fw-semibold">{{ $pair['female_name'] }} ({{ $pair['female_weight'] }}g.)</div>
                    <div class="fw-semibold">{{ $pair['male_name'] }} ({{ $pair['male_weight'] }}g.)</div>
                </div>

                <div class="table-responsive">
                    <table class="table glass-table table-sm align-middle mb-0">
                        <thead>
                            <tr class="text-muted small">
                                <th style="width: 12%;">Procent</th>
                                <th style="width: 20%;">Nazwa</th>
                                <th>Traits</th>
                                <th style="width: 10%;">#Traits</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($pair['rows'] as $row)
                                <tr>
                                    <td>{{ $row['percentage_label'] }}</td>
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
                                    <td>{{ $row['traits_count'] }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endforeach
    </div>
@endif
