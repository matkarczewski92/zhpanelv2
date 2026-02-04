@if (!$femaleId)
    <div class="small text-muted">Wybierz samice, aby zobaczyc mozliwe polaczenia.</div>
@elseif (empty($rows))
    <div class="small text-muted">Brak samcow dla wybranej samicy.</div>
@else
    <div class="d-flex flex-column gap-3">
        @foreach ($rows as $male)
            <div class="glass-card glass-table-wrapper">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-2">
                        <h5 class="mb-0 text-{{ $male['male_color'] }}">{{ $male['male_name'] }} ({{ $male['male_weight'] }}g.)</h5>
                        <div class="d-flex align-items-center gap-3">
                            @if($male['used_count'] > 0)
                                <span class="badge text-bg-success">uzyty {{ $male['used_count'] }} razy</span>
                            @endif
                            <label class="form-check-label small">
                                <input
                                    class="form-check-input me-1"
                                    type="checkbox"
                                    data-action="toggle-pair"
                                    data-female-id="{{ $femaleId }}"
                                    data-male-id="{{ $male['male_id'] }}"
                                    @checked($male['checked'])
                                >
                                dodaj do podsumowania
                            </label>
                        </div>
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
                                @foreach ($male['rows'] as $row)
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
            </div>
        @endforeach
    </div>
@endif
