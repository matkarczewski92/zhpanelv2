# ZH Panel v2

ZH Panel v2 to aplikacja Laravel 12 do zarządzania hodowlą: ewidencją zwierząt, karmień, wag, wylinek, miotów, ofert sprzedażowych, finansów oraz procesów takich jak zimowanie, inkubacja i planowanie rozrodu. Repozytorium zawiera panel operacyjny, sekcję administracyjną, publiczne profile oraz read-only API.

## Co znajduje się w projekcie

### Panel operacyjny (`/panel`)

- zarządzanie zwierzętami, genotypami, grupami kolorystycznymi i galeriami
- rejestrowanie karmień, wag, wylinek i zrzutów ciążowych
- obsługa ofert, rezerwacji, paszportów i etykiet
- planowanie miotów, predykcja potomstwa i roadmapy hodowlane
- widoki ciąż, inkubacji i zimowania
- planowanie karmy i przyjęć dostaw
- finanse, masowe operacje i szybkie wyszukiwanie
- workflow QR do szybkiego zapisu karmień, wag i wylinek
- obsługa urządzeń eWeLink oraz harmonogramów

### Admin (`/admin`)

- słowniki i konfiguracja systemu
- typy i kategorie zwierząt
- kategorie genotypów, cechy i słownik genów
- etapy zimowania, pasze, kategorie finansowe i grupy kolorów
- generator konfiguracji genetyki
- synchronizacja urządzeń eWeLink
- import/eksport ustawień
- raporty, listy wysyłkowe, cenniki i etykiety
- zarządzanie galerią strony głównej
- opcjonalny moduł aktualizacji portalu

### Public + API

- landing page i wyszukiwanie publicznego profilu po kodzie
- publiczne profile zwierząt z historiami wag i wylinek
- `GET /api/offers/current` z aktualnymi ofertami
- `GET /api/animals/{secret_tag}` zabezpieczone nagłówkiem `X-API-KEY`

## Stack technologiczny

- PHP 8.2+
- Laravel 12
- Blade + Vite
- Bootstrap 5 + Bootstrap Icons
- SQLite do szybkiego local dev
- MySQL/MariaDB do importu danych legacy
- `barryvdh/laravel-dompdf` do PDF-ów
- `intervention/image` do obsługi obrazów

## Architektura

Projekt nie jest "czystym CRUD-em" na kontrolerach. Logika biznesowa jest rozdzielona zgodnie z podejściem Service Layer / Application Layer:

- `app/Application` - komendy i zapytania use-case
- `app/Domain` - kontrakty repozytoriów, zdarzenia i logika domenowa
- `app/Infrastructure/Persistence` - implementacje odczytu/zapisu
- `app/Http/Controllers` - cienkie kontrolery HTTP
- `routes/panel` i `routes/admin` - routing rozdzielony per obszar odpowiedzialności

W repo są też materiały pomocnicze dla deweloperów:

- `docs/CODEX_PLAYBOOK.md` - zasady architektury
- `docs/DB_RELATIONSHIPS.md` - mapa relacji i FK
- `docs/apiInstrukcja.txt` - instrukcja integracji z API
- `docs/old/` - kod starszej wersji systemu
- `docs/m2531_zh.sql` - zrzut legacy bazy

## Szybki start

### 1. Instalacja zależności

```bash
composer install
npm install
```

### 2. Konfiguracja środowiska

Skopiuj `.env.example` do `.env`, a następnie wygeneruj klucz aplikacji:

```bash
php artisan key:generate
```

Repo zawiera już plik `database/database.sqlite`, a domyślne `.env.example` korzysta z SQLite, więc do pierwszego uruchomienia nie trzeba od razu stawiać MySQL.

### 3. Migracje

```bash
php artisan migrate
```

### 4. Zasoby frontendu

```bash
npm run build
```

W trybie developerskim możesz zamiast tego uruchomić Vite watcher:

```bash
npm run dev
```

### 5. Pierwszy użytkownik

Projekt ma tylko logowanie, bez publicznej rejestracji. Po czystej migracji utwórz konto ręcznie, np. przez Tinker:

```text
php artisan tinker
>>> App\Models\User::create([
...     'name' => 'Admin',
...     'email' => 'admin@example.com',
...     'password' => 'secret1234',
... ]);
```

Hasło zostanie zahashowane automatycznie przez model `User`.

### 6. Start aplikacji

Najwygodniejsza komenda developerska:

```bash
composer run dev
```

Ten skrypt uruchamia jednocześnie:

- serwer Laravel
- listener kolejki
- podgląd logów przez Laravel Pail
- Vite dev server

Po starcie:

- aplikacja publiczna: `http://127.0.0.1:8000/`
- logowanie: `http://127.0.0.1:8000/login`
- panel: `http://127.0.0.1:8000/panel`
- admin: `http://127.0.0.1:8000/admin`

## Dane demo / import legacy

Seeder `DatabaseSeeder` ładuje duży zestaw danych legacy przez `ZhpanelSqlSeeder` oraz konfigurację API. To przydatne do lokalnego odtworzenia starego stanu systemu, ale trzeba uważać na dwa ograniczenia:

- seeder czyści wiele tabel przed importem
- seeder używa składni specyficznej dla MySQL (`SET FOREIGN_KEY_CHECKS`, `TRUNCATE TABLE`, surowe inserty)

To oznacza, że:

- pusty local dev możesz odpalić na SQLite
- seedowanie danych legacy powinno być robione na MySQL/MariaDB

Przykładowy przebieg dla danych legacy:

```bash
php artisan migrate:fresh --seed
```

## Przydatne komendy

```bash
composer run setup
composer run dev
composer run test
php artisan migrate
php artisan db:seed
php artisan storage:link
```

`composer run setup` instaluje zależności, kopiuje `.env`, generuje klucz, wykonuje migracje i buduje frontend. Przy pracy z domyślnym SQLite powinien działać od razu, bo repo zawiera `database/database.sqlite`.

## API

### Aktualne oferty

```http
GET /api/offers/current
```

Publiczny endpoint zwracający aktualne oferty.

### Profil zwierzęcia po `secret_tag`

```http
GET /api/animals/{secret_tag}
X-API-KEY: <token>
```

Wymagania:

- nagłówek `X-API-KEY`
- limitowanie `throttle:30,1`
- token trzymany w `system_config` pod kluczem `apiDziennik`

Przykładowe wywołanie:

```bash
curl -X GET "http://127.0.0.1:8000/api/animals/A7K2P9" \
  -H "Accept: application/json" \
  -H "X-API-KEY: TWOJ_TOKEN"
```

Szczegóły formatu odpowiedzi są opisane w `docs/apiInstrukcja.txt`.

## Testy

Testy skupiają się głównie na warstwie `Application` i logice biznesowej:

```bash
php artisan test
```

W repo znajdziesz m.in. testy dla:

- dashboardu
- planowania miotów
- workflow karmy
- komend związanych z wagami, wylinkami i zimowaniem
- eksportu etykiet i raportów admina

## Uwagi implementacyjne

- schemat danych jest legacy i trzeba go traktować jako źródło prawdy
- routing jest już rozdzielony na `panel` i `admin`
- projekt nie korzysta z Livewire
- funkcje aktualizacji portalu są kontrolowane przez zmienne `PORTAL_UPDATE_*`
- integracja z eWeLink wymaga uzupełnienia zmiennych `EWELINK_CLOUD_*`

## Struktura repozytorium

```text
app/
  Application/
  Domain/
  Infrastructure/
  Http/
config/
database/
docs/
public/
resources/
routes/
  admin/
  panel/
tests/
```

