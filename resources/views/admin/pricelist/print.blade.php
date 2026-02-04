<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Cennik</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; color: #111; }
        h1 { margin: 0 0 8px; font-size: 24px; }
        .meta { margin-bottom: 18px; font-size: 13px; color: #444; }
        table { width: 100%; border-collapse: collapse; font-size: 13px; }
        th, td { border: 1px solid #ccc; padding: 6px 8px; text-align: left; }
        th { background: #f2f2f2; }
        .price { text-align: right; white-space: nowrap; }

        @media print {
            body { margin: 10mm; }
            .no-print { display: none; }
            a[href]:after { content: none !important; }
        }
    </style>
</head>
<body>
    <button class="no-print" type="button" onclick="window.print()">Drukuj</button>
    <h1>Cennik</h1>
    <div class="meta">Wydrukowano: {{ $vm->printedAt }} | Łącznie zwierząt: {{ $vm->totalAnimals }}</div>

    <table>
        <thead>
            <tr>
                <th style="width: 80px;">ID</th>
                <th>Nazwa</th>
                <th style="width: 140px;">Płeć</th>
                <th style="width: 140px;" class="price">Cena</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($vm->animals as $animal)
                <tr>
                    <td>{{ $animal['id'] }}</td>
                    <td>{{ $animal['name'] }}</td>
                    <td>{{ $animal['sex_label'] }}</td>
                    <td class="price">{{ $animal['price_formatted'] }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="4">Brak danych do wydruku.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>

