@if (empty($rows))
    <div class="small text-muted">Wybierz samice i samca, aby zobaczyc podsumowanie.</div>
@else
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
                @foreach ($rows as $row)
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
@endif

