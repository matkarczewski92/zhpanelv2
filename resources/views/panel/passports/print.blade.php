<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Paszport</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
    <style>
        body { background: #fff; }
        @page { margin: 15mm; }
        .passport-page { page-break-after: always; break-after: page; }
        .passport-page:last-child { page-break-after: auto; break-after: auto; }
        .logo-row img { max-height: 225px; }
        .section-table td { padding: .45rem .75rem; }
        .label { width: 45%; font-weight: 600; }
    </style>
</head>
<body onload="window.print()">
@foreach ($passports as $passport)
    <div class="passport-page">
        <table class="table table-borderless logo-row">
            <tr>
                <td class="text-center">
                    <img src="{{ $passport['logo_url'] }}" alt="Logo" />
                </td>
            </tr>
        </table>

        <table class="table section-table mx-auto">
            <tr>
                <td class="label">Gatunek</td>
                <td class="text-end">{{ $passport['animal_type_name'] }}</td>
            </tr>
            <tr>
                <td class="label">ID WĘŻA</td>
                <td class="text-end">{{ $passport['animal_id'] }}</td>
            </tr>
            <tr>
                <td class="label">Kod miotu</td>
                <td class="text-end">{{ $passport['litter_code'] }}</td>
            </tr>
            <tr>
                <td class="label">Data klucia</td>
                <td class="text-end">{{ $passport['date_of_birth'] ?? '-' }}</td>
            </tr>
            <tr>
                <td class="label">Nazwa</td>
                <td class="text-end">
                    @if($passport['second_name_text'])
                        <span class="text-muted">"{{ $passport['second_name_text'] }}" </span>
                    @endif
                    {!! $passport['name_display_html'] !!}
                </td>
            </tr>
                <td class="label">Płeć</td>
                <td class="text-end">{{ $passport['sex_name'] }}</td>
            </tr>
         </table>

        <table class="table section-table mx-auto mt-5">
            <tr>
                <td class="label">Hodowla</td>
                <td class="text-end">{{ $passport['breeder_name'] }}</td>
            </tr>
            <tr>
                <td class="label">Dane kontakt</td>
                <td class="text-end">{{ $passport['breeder_contact'] }}</td>
            </tr>
            <tr>
                <td class="label">E-mail</td>
                <td class="text-end">{{ $passport['breeder_email'] }}</td>
            </tr>
            <tr>
                <td class="label">KOD WĘŻA</td>
                <td class="text-end">{{ $passport['public_profile_tag'] }}</td>
            </tr>

        </table>
    </div>
@endforeach
</body>
</html>
