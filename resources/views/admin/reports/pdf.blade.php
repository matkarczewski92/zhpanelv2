<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="utf-8">
    <title>{{ $report['title'] ?? 'Raport' }}</title>
    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 12px;
            color: #111;
        }

        h1 {
            margin: 0 0 8px;
            font-size: 20px;
        }

        .meta {
            margin-bottom: 18px;
            font-size: 11px;
            color: #333;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }

        th,
        td {
            border: 1px solid #cfcfcf;
            padding: 6px 8px;
            vertical-align: top;
            text-align: left;
        }

        th {
            background: #f1f1f1;
        }

        .muted {
            color: #666;
        }

        .stack div {
            margin-bottom: 4px;
        }

        .col-id {
            width: 5%;
        }

        .col-name {
            width: 45%;
        }

        .col-feedings {
            width: 18%;
        }

        .col-weights {
            width: 16%;
        }

        .col-molts {
            width: 16%;
        }
    </style>
</head>
<body>
    <h1>{{ $report['title'] ?? 'Raport' }}</h1>
    <div class="meta">
        <div>Wygenerowano: {{ $report['meta']['generated_at'] ?? '-' }}</div>
        @if (($report['type'] ?? null) === 'sales')
            <div>Zakres: {{ $report['meta']['range_label'] ?? '-' }}</div>
            <div>Suma: {{ $report['meta']['total_amount_label'] ?? '-' }}</div>
        @elseif (($report['type'] ?? null) === 'qr_scanner_session')
            <div>Sesja: {{ $report['meta']['session_label'] ?? '-' }}</div>
            <div>Karmienia / Wazenia / Wylinki: {{ $report['meta']['feedings_count'] ?? 0 }} / {{ $report['meta']['weights_count'] ?? 0 }} / {{ $report['meta']['molts_count'] ?? 0 }}</div>
        @else
            <div>Data: {{ $report['meta']['report_date_label'] ?? '-' }}</div>
            <div>Karmienia / Wazenia / Wylinki: {{ $report['meta']['feedings_count'] ?? 0 }} / {{ $report['meta']['weights_count'] ?? 0 }} / {{ $report['meta']['molts_count'] ?? 0 }}</div>
        @endif
        <div>Pozycji: {{ $report['meta']['item_count'] ?? 0 }}</div>
    </div>

    @if (($report['type'] ?? null) === 'sales')
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Nazwa</th>
                    <th>Public tag</th>
                    <th>Data sprzedazy</th>
                    <th>Cena</th>
                </tr>
            </thead>
            <tbody>
                @foreach (($report['rows'] ?? []) as $row)
                    <tr>
                        <td>{{ $row['animal_id'] }}</td>
                        <td>{!! $row['animal_name'] !!}</td>
                        <td>{{ $row['public_tag'] ?? '-' }}</td>
                        <td>{{ $row['sale_date'] }}</td>
                        <td>{{ $row['sale_price_label'] }}</td>
                    </tr>
                @endforeach
                <tr>
                    <td colspan="4" style="text-align:right;"><strong>Suma</strong></td>
                    <td><strong>{{ $report['meta']['total_amount_label'] ?? '-' }}</strong></td>
                </tr>
            </tbody>
        </table>
    @elseif (($report['type'] ?? null) === 'daily_entered_data')
        @forelse (($report['groups'] ?? []) as $group)
            <h2 style="margin: 18px 0 8px; font-size: 15px;">{{ $group['label'] }}</h2>
            @foreach (($group['types'] ?? []) as $type)
                <h3 style="margin: 12px 0 6px; font-size: 13px; color: #444;">{{ $type['label'] }}</h3>
                <table>
                    <colgroup>
                        <col class="col-id">
                        <col class="col-name">
                        <col class="col-feedings">
                        <col class="col-weights">
                        <col class="col-molts">
                    </colgroup>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Nazwa</th>
                            <th>Karmienia</th>
                            <th>Wazenia</th>
                            <th>Wylinki</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach (($type['rows'] ?? []) as $row)
                            <tr>
                                <td>{{ $row['animal_id'] }}</td>
                                <td>{!! $row['animal_name'] !!}</td>
                                <td class="stack">
                                    @forelse (($row['feedings'] ?? []) as $entry)
                                        <div>{{ $entry['label'] }}</div>
                                    @empty
                                        <span class="muted">-</span>
                                    @endforelse
                                </td>
                                <td class="stack">
                                    @forelse (($row['weights'] ?? []) as $entry)
                                        <div>{{ $entry['label'] }}</div>
                                    @empty
                                        <span class="muted">-</span>
                                    @endforelse
                                </td>
                                <td class="stack">
                                    @forelse (($row['molts'] ?? []) as $entry)
                                        <div>{{ $entry['label'] }}</div>
                                    @empty
                                        <span class="muted">-</span>
                                    @endforelse
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endforeach
        @empty
            <div class="muted">Brak danych w kategoriach 1-4.</div>
        @endforelse
    @else
        <table>
            <colgroup>
                <col class="col-id">
                <col class="col-name">
                <col class="col-feedings">
                <col class="col-weights">
                <col class="col-molts">
            </colgroup>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Nazwa</th>
                    <th>Karmienia</th>
                    <th>Wazenia</th>
                    <th>Wylinki</th>
                </tr>
            </thead>
            <tbody>
                @foreach (($report['rows'] ?? []) as $row)
                    <tr>
                        <td>{{ $row['animal_id'] }}</td>
                        <td>{!! $row['animal_name'] !!}</td>
                        <td class="stack">
                            @forelse (($row['feedings'] ?? []) as $entry)
                                <div>{{ $entry['label'] }}</div>
                            @empty
                                <span class="muted">-</span>
                            @endforelse
                        </td>
                        <td class="stack">
                            @forelse (($row['weights'] ?? []) as $entry)
                                <div>{{ $entry['label'] }}</div>
                            @empty
                                <span class="muted">-</span>
                            @endforelse
                        </td>
                        <td class="stack">
                            @forelse (($row['molts'] ?? []) as $entry)
                                <div>{{ $entry['label'] }}</div>
                            @empty
                                <span class="muted">-</span>
                            @endforelse
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif
</body>
</html>
