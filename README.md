# Wtyczka WordPress: Wyniki Biegów

Wtyczka do zarządzania i wyświetlania wyników biegów na stronie WordPress. Umożliwia dodawanie wyników w formacie PDF oraz linków do wyników online.

## Funkcje

- ✅ Zarządzanie wynikami biegów z panelu administracyjnego WordPress
- ✅ Dodawanie wielu plików PDF dla jednego wydarzenia (np. różne dystanse)
- ✅ Indywidualne nazwy przycisków dla każdego pliku PDF
- ✅ Link do wyników online (np. Athlinks, Datasport)
- ✅ Responsywna tabela z wynikami dla frontendu
- ✅ Wyszukiwanie i sortowanie wyników
- ✅ Drag & drop do zmiany kolejności wyników
- ✅ Migracja danych z ACF (Advanced Custom Fields)

## Instalacja

1. Skopiuj folder wtyczki do katalogu `/wp-content/plugins/`
2. Aktywuj wtyczkę w panelu WordPress (Wtyczki → Zainstalowane wtyczki)
3. Przejdź do menu "Wyniki" w panelu administracyjnym

## Użycie

### Panel administracyjny

1. **Dodawanie wyników:**
   - Kliknij przycisk "Dodaj wyniki"
   - Wypełnij formularz:
     - Data zawodów (wymagane)
     - Nazwa biegu (wymagane)
     - Miejscowość (wymagane)
     - Wyniki PDF (opcjonalne - możesz dodać wiele plików)
     - Link do wyników online (opcjonalne)
   - Dla każdego pliku PDF:
     - Kliknij "Wybierz plik PDF" aby wybrać plik z biblioteki mediów
     - Wpisz nazwę przycisku (np. "Wyniki 10km", "Wyniki 5km")
   - Kliknij "Zapisz"

2. **Edycja wyników:**
   - Kliknij ikonę ołówka przy wybranym wyniku
   - Zmodyfikuj dane
   - Kliknij "Zapisz"

3. **Usuwanie wyników:**
   - Kliknij ikonę kosza przy wybranym wyniku
   - Potwierdź usunięcie

4. **Zmiana kolejności:**
   - Przeciągnij wiersze w tabeli za pomocą ikony menu (☰)
   - Kolejność zostanie zapisana automatycznie

### Wyświetlanie na stronie

Użyj shortcode'a `[race_results_table]` na dowolnej stronie lub poście:

```
[race_results_table]
```

**Opcje shortcode'a:**

```
[race_results_table limit="10" show_search="yes" show_sort="yes"]
```

- `limit` - liczba wyświetlanych wyników (domyślnie: wszystkie)
- `show_search` - czy pokazać wyszukiwarkę (domyślnie: yes)
- `show_sort` - czy pokazać opcje sortowania (domyślnie: yes)

### Migracja z ACF

Jeśli masz już dane w polach ACF, możesz je łatwo przenieść do nowej struktury:

1. Przejdź do "Wyniki → Migracja z ACF"
2. Wypełnij formularz konfiguracji:
   - Podaj post type lub grupę ACF
   - Podaj nazwy pól ACF dla każdego typu danych:
     - Pole z datą biegu
     - Pole z nazwą biegu
     - Pole z miejscowością
     - Pole z wynikami PDF (HTML z linkami)
     - Pole z wynikami online (HTML z linkiem)
3. Kliknij "Podgląd migracji" aby zobaczyć, jakie dane zostaną przeniesione
4. Sprawdź poprawność danych
5. Kliknij "Migruj dane" aby przeprowadzić migrację

**Format danych ACF:**

System automatycznie wykryje i przekonwertuje:
- Linki PDF z HTML (np. `<a href="...pdf">Wyniki 10km</a>`)
- Link online z HTML (np. `<a href="...">zobacz</a>`)
- Różne formaty dat (YYYY-MM-DD, DD.MM.YYYY, itp.)

**Przykład pola z wynikami PDF w ACF:**
```html
<a href="https://yogoevents.pl/wp-content/uploads/2025/12/wyniki_10km.pdf" target="_blank">Wyniki 10km</a>
<br>
<a href="https://yogoevents.pl/wp-content/uploads/2025/12/wyniki_5km.pdf" target="_blank">Wyniki 5km</a>
```

## Struktura plików

```
race-results/
├── admin/
│   ├── css/
│   │   └── admin.css
│   ├── js/
│   │   └── admin.js
│   └── views/
│       └── admin-page.php
├── includes/
│   ├── class-results-admin.php
│   ├── class-results-database.php
│   ├── class-results-frontend.php
│   └── class-results-migration.php
├── public/
│   ├── css/
│   │   └── frontend.css
│   └── js/
│       └── frontend.js
├── .gitignore
├── README.md
└── race-results.php
```

## Wymagania

- WordPress 5.0 lub nowszy
- PHP 7.0 lub nowszy
- MySQL 5.6 lub nowszy

## Wsparcie

W razie problemów lub pytań:
- Otwórz issue na GitHubie
- Skontaktuj się z autorem

## Autor

YOGO1988

## Licencja

GPL v2 lub nowsza

## Changelog

### 1.0.0 (2025-12-17)
- Pierwsza wersja wtyczki
- Podstawowa funkcjonalność zarządzania wynikami
- Panel administracyjny z formularzem
- Frontend z responsywną tabelą
- Wyszukiwanie i sortowanie
- Drag & drop do zmiany kolejności
- Migracja z ACF
- Obsługa wielu plików PDF z nazwami przycisków
- Integracja z WordPress Media Library
