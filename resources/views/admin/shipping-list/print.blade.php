<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Lista przewozowa</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; color: #111; }
        h1 { margin: 0 0 8px; font-size: 24px; }
        .meta { margin-bottom: 18px; font-size: 13px; color: #444; }
        .group { margin-bottom: 24px; }
        .group-title { font-size: 16px; font-weight: 700; margin: 0 0 8px; }
        table { width: 100%; border-collapse: collapse; font-size: 13px; }
        th, td { border: 1px solid #ccc; padding: 6px 8px; text-align: left; }
        th { background: #f2f2f2; }

        @media print {
            body { margin: 10mm; }
            .no-print { display: none; }
            a[href]:after { content: none !important; }
        }
    </style>
</head>
<body>
    <button class="no-print" type="button" onclick="window.print()">Drukuj</button>
    <h1>Lista przewozowa</h1>
    <div class="meta">Wydrukowano: {{ $vm->printedAt }} | Łącznie zwierząt: {{ $vm->totalAnimals }}</div>

    @forelse ($vm->groups as $group)
        <section class="group">
            <h2 class="group-title">{{ $group['type_name'] }} - Razem: {{ $group['total'] }}</h2>
            <table>
                <thead>
                    <tr>
                        <th style="width: 80px;">ID</th>
                        <th>Nazwa</th>
                        <th style="width: 140px;">Płeć</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($group['animals'] as $animal)
                        <tr>
                            <td>{{ $animal['id'] }}</td>
                            <td>{{ $animal['name'] }}</td>
                            <td>{{ $animal['sex_label'] }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </section>
    @empty
        <p>Brak danych do wydruku.</p>
    @endforelse
</body>
</html>

