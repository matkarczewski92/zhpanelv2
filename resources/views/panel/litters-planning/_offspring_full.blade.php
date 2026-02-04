@if (empty($rows))
    <div class="small text-muted">Wybierz pare i kliknij Generuj, aby wyswietlic pelny widok mozliwego potomstwa.</div>
@else
    <div class="table-responsive">
        <table class="table glass-table table-sm align-middle mb-0">
            <thead>
                <tr class="text-muted small">
                    <th style="width: 60px;">Lp.</th>
                    <th style="width: 120px;">Procent</th>
                    <th style="width: 22%;">Morf</th>
                    <th>Fenotyp (vis)</th>
                    <th>Genotyp (het)</th>
                    <th style="width: 90px;" class="text-center">#Traits</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($rows as $index => $row)
                    <tr>
                        <td>{{ $index + 1 }}</td>
                        <td>{{ $row['percentage_label'] }}</td>
                        <td>{{ $row['morph_name'] }}</td>
                        <td>
                            @if (!empty($row['visual_traits']))
                                @foreach ($row['visual_traits'] as $trait)
                                    <span class="badge text-bg-success">{{ $trait }}</span>
                                @endforeach
                            @else
                                <span class="text-muted">-</span>
                            @endif
                        </td>
                        <td>
                            @if (!empty($row['carrier_traits']))
                                @foreach ($row['carrier_traits'] as $trait)
                                    <span class="badge @if (str_starts_with($trait, '50%')) text-bg-secondary @elseif (str_starts_with($trait, '66%')) text-bg-info @else text-bg-primary @endif">{{ $trait }}</span>
                                @endforeach
                            @else
                                <span class="text-muted">-</span>
                            @endif
                        </td>
                        <td class="text-center">{{ $row['traits_count'] }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endif

