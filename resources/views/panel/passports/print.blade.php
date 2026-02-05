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
        .passport-page { page-break-after: always; break-after: page; display: flex; flex-direction: column; min-height: 100%; }
        .passport-page:last-child { page-break-after: auto; break-after: auto; }
        .logo-row img { max-height: 225px; }
        .section-table td { padding: .45rem .75rem; }
        .label { width: 45%; font-weight: 600; }
        .passport-footer { margin-top: auto; padding-top: 0.95rem; }
        .journal-title { font-weight: 700; text-align: center; margin-top: 0; }
        .journal-text { font-size: 0.95rem; }
        .secret-tag { font-weight: 700; font-size: calc(1em + 2pt); }
    </style>
</head>
<body onload="window.print()">
@foreach ($passports as $passport)
    <div class="passport-page">
        <div class="passport-content">
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
            <tr>
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

        <div class="passport-footer">
            <div class="journal-title">Dziennik Hodowlany</div>
            <div class="journal-text mt-2">
                Aby uzyskać dostęp do Dziennika Hodowlanego wejdź na strone www.dziennik.makssnake.pl lub kliknij Dziennik Hodowlany w menu na naszej stronie. W formularzu rejestracji podaj SECRET TAG <span class="secret-tag">{{ $passport['secret_tag'] }}</span> tego węża. Dzięki temu uzyskasz dostęp do pełnej historii Twojego wężą od momenu jego wyklucia do momentu opuszczenia hodowli (ważenia, wylinki, karmienia, genotyp, metryczka).
                <br><br>
                Jeżeli posiadasz już konto w naszym Dzienniku Hodowlanym, wejdź w zakładkę "Zwierzęta" w prawym górym rogu kliknij "Pobierz dane z hodowli" w oknie które sie pojawi wprowadź SECRET TAG <span class="secret-tag">{{ $passport['secret_tag'] }}</span> swojego węża.
            </div>
        </div>
    </div>
@endforeach
</body>
</html>
