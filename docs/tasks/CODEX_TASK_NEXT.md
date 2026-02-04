Read docs/CODEX.md and strictly follow it

(thin controllers, no DB queries in Blade, PANEL vs ADMIN separation).

CEL:
Przepisz (unowocześnij i dostosuj) podstronę /finances ze starego systemu (docs/old) do nowej aplikacji. W nowym systemie route /finances już istnieje, ale jest pusty — usuń aktualną zawartość tej podstrony i zastąp ją nową implementacją. Nie zadawaj pytań przed tworzeniem nowych plików. Nie wykonuj testów.

ZASADA ŹRÓDŁA PRAWDY:
- Funkcjonalnie i merytorycznie punktem odniesienia jest stara podstrona finances z docs/old (route, UI, logika, wyliczenia, filtrowania, podsumowania).
- Nowy system ma zachować sens i działanie, ale UI/UX ma być unowocześniony (czytelniejsze karty, tabele, filtry, podsumowania), zgodny ze stylem PANEL.
- Jeśli w nowym systemie istnieją już modele/tabele/serwisy powiązane z finansami — użyj ich i dopasuj, zamiast tworzyć duplikaty.

WYMAGANIA ARCHITEKTONICZNE (BEZWZGLĘDNIE):
- PANEL vs ADMIN: to ma trafić do PANEL.
- Thin controllers: kontroler ma tylko delegować do serwisu/use-case i zwracać response.
- NO DB queries in Blade: Blade dostaje gotowe dane (DTO/ViewModel/arrays).
- Walidacja przez FormRequest tam, gdzie jest input (filtry, tworzenie/edycja, import itd. — zależnie co było w starym /finances).
- Logika (zestawienia, sumy, filtry, agregacje) w Service/QueryService/UseCase, najlepiej z wyraźnie wydzielonymi metodami.

ZAKRES FUNKCJONALNY (ODTWÓRZ Z /docs/old/finances):
1) Zidentyfikuj w docs/old komplet funkcji /finances:
   - route’y
   - controller
   - widoki
   - modele/tabele
   - logikę liczenia sald/sum, filtrów, zakresów dat, kategorii, przychodów/rozchodów, itp.
2) Odtwórz to w nowym systemie:
   - ta sama funkcjonalność (co najmniej parity),
   - ale UI nowoczesne: sekcja filtrów, sekcja podsumowań (kafelki: suma przychodów, suma kosztów, saldo, itp.), tabela/lista pozycji, akcje (jeśli były: dodaj/edytuj/usuń/eksport/import).
3) Jeśli w starym systemie była obsługa:
   - kategorii / tagów,
   - typów transakcji,
   - cyklicznych kosztów,
   - miesięcznych zestawień,
   - eksportu CSV/XLS/PDF,
   - importu,
   - wykresów,
   to przenieś to do nowego systemu w sposób zgodny z aktualnym frontendem (minimalny JS, jeśli projekt go używa; w innym wypadku Blade + backend).
   Jeżeli coś było „hackowate” w starym systemie — popraw to (ale nie zmieniaj logiki biznesowej).
4) Nie wykonuj testów. Nie dodawaj testów. (Jeśli w repo istnieją testy stare, nie ruszaj.)

KROKI IMPLEMENTACYJNE (KONKRETNIE):
A) Analiza starego kodu:
- Zlokalizuj w docs/old wszystko dot. finances (szukaj: “finances”, “finance”, “transactions”, “payments”, “income”, “expense”, “saldo”, “budżet”, “kategorie”).
- Wypisz (krótko w komentarzu w PR / podsumowaniu pracy) mapowanie:
  - stare route’y -> nowe route’y,
  - stare tabele/pola -> nowe tabele/pola,
  - stare klasy -> nowe klasy.

B) Nowy system (PANEL):
1. Route:
- Upewnij się, że /finances działa w PANEL (aktualne route’y w routes/..., zgodnie z konwencją projektu).
- Usuń aktualną pustą zawartość /finances (widok/placeholder) i zastąp pełną implementacją.

2. Controller:
- Utwórz/zmodyfikuj FinanceController w PANEL (lub zgodnie z naming w projekcie).
- Metody zgodne z funkcjonalnością: index (lista + filtry + podsumowania), oraz inne jeśli były (create/store/edit/update/destroy/export/import).
- Kontroler ma być cienki.

3. Service / UseCase:
- Utwórz FinanceSummaryService / FinanceQueryService (lub zgodnie z konwencją) do:
  - pobierania listy pozycji wg filtrów,
  - liczenia sum i agregacji,
  - budowania danych pod ewentualne wykresy,
  - formatowania/normalizacji danych na potrzeby widoku (ale bez logiki prezentacji w Blade).
- Użyj transakcji DB tam, gdzie to konieczne (store/update/import).

4. FormRequest:
- Dodaj FormRequest dla filtrów (np. FinanceIndexRequest) jeśli filtry są rozbudowane i walidowalne.
- Dodaj FormRequest dla tworzenia/edycji (jeśli występuje).

5. Widok (Blade):
- Stwórz nowy widok /finances w PANEL:
  - Sekcja filtrów (zakres dat, typ, kategoria, tekst, itp. — zgodnie ze starym systemem).
  - Kafelki podsumowań (przychody, koszty, saldo, itd.).
  - Tabela pozycji: czytelne kolumny, akcje, paginacja.
  - UI spójne z resztą PANEL (użyj istniejących komponentów, klas, layoutów).
- Zero zapytań DB w Blade.

C) Migracje / DB:
- NIE twórz migracji, jeśli możesz zmapować na istniejące struktury nowego systemu.
- Jeśli absolutnie brakuje tabel/kolumn do odtworzenia starej funkcjonalności: dodaj minimalne migracje i aktualizacje modeli — ale tylko wtedy, gdy to konieczne do parity.

Ograniczenia:
- Nie pytaj o zgodę na tworzenie nowych plików — twórz je od razu zgodnie z potrzebą.
- Nie wykonuj testów i nie dodawaj testów.

DEFINICJA DONE:
- /finances w PANEL działa i jest wypełnione funkcjonalnością z docs/old/finances (parity).
- Aktualny placeholder/pusta zawartość nowego /finances została usunięta.
- Kod zgodny z docs/CODEX.md: thin controllers, brak DB query w Blade, separacja PANEL/ADMIN.
- Na końcu wypisz listę zmienionych/dodanych plików oraz krótką instrukcję ręcznego sprawdzenia (3 kroki).
