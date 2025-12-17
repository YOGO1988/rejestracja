# Rejestracja na Biegi - Wtyczka WordPress

Profesjonalna wtyczka WordPress do zarządzania rejestracją na biegi. Umożliwia łatwe tworzenie i wyświetlanie tabeli z zawodami sportowymi wraz z linkami do rejestracji.

## Funkcje

### Panel Administracyjny
- ✅ Intuicyjny panel zarządzania zawodami
- ✅ Dodawanie, edycja i usuwanie zawodów
- ✅ Formularz z walidacją pól
- ✅ Statystyki zawodów w czasie rzeczywistym
- ✅ Przyjazny interfejs użytkownika

### Zarządzanie Zawodami
- ✅ **Data zawodów** - automatyczne sortowanie według daty
- ✅ **Nazwa zawodów** - pełna nazwa wydarzenia
- ✅ **Miejscowość** - lokalizacja zawodów
- ✅ **Dystans** - dystans biegu (np. 5km, 10km, 21km)
- ✅ **Strona zawodów** - automatyczny przycisk "Strona"
- ✅ **Zapisy** - automatyczny przycisk "Zapisy"

### Zaawansowane Funkcje
- ✅ **Przypinanie zawodów** - wyróżnianie ważnych zawodów (zawsze na górze)
- ✅ **Przeciąganie wierszy** - ręczne sortowanie przez drag & drop
- ✅ **Limit osiągnięty** - oznaczanie zawodów z pełną listą uczestników
- ✅ **Automatyczne usuwanie** - zawody znikają dzień po wydarzeniu
- ✅ **Ręczne usuwanie** - przycisk do wygaszania zawodów
- ✅ **Wyszukiwarka** - po nazwie zawodów lub miejscowości
- ✅ **Sortowanie** - według daty, nazwy lub miejscowości

### Frontend
- ✅ Responsywna tabela dostosowana do urządzeń mobilnych
- ✅ Ładny, nowoczesny design
- ✅ Wyszukiwarka na żywo
- ✅ Dynamiczne sortowanie
- ✅ Animacje przy przewijaniu
- ✅ Optymalizacja SEO

## Instalacja

### Metoda 1: Przez panel WordPress
1. Pobierz folder `race-registration`
2. Spakuj go do pliku ZIP
3. W panelu WordPress przejdź do **Wtyczki → Dodaj nową**
4. Kliknij **Wyślij wtyczkę na serwer**
5. Wybierz plik ZIP i kliknij **Zainstaluj**
6. Aktywuj wtyczkę

### Metoda 2: Przez FTP
1. Wgraj folder `race-registration` do `/wp-content/plugins/`
2. W panelu WordPress przejdź do **Wtyczki**
3. Znajdź "Rejestracja na Biegi" i kliknij **Aktywuj**

## Użytkowanie

### Panel Administracyjny

Po aktywacji wtyczki w menu WordPress pojawi się nowa pozycja **"Biegi"**.

#### Dodawanie zawodów:
1. Kliknij przycisk **"Dodaj zawody"**
2. Wypełnij formularz:
   - **Data zawodów** (wymagane)
   - **Nazwa zawodów** (wymagane)
   - **Miejscowość** (wymagane)
   - **Dystans** (wymagane)
   - **Strona zawodów** (opcjonalnie)
   - **Link do zapisów** (opcjonalnie)
   - **Przypnij do góry** (opcjonalnie) - zawód będzie wyróżniony
   - **Limit osiągnięty** (opcjonalnie) - pokaże komunikat zamiast przycisku zapisu
3. Kliknij **"Zapisz"**

#### Edycja zawodów:
1. Kliknij ikonę ołówka przy zawodzie
2. Zmień potrzebne dane
3. Kliknij **"Zapisz"**

#### Usuwanie zawodów:
- Kliknij ikonę kosza przy zawodzie
- Potwierdź usunięcie

#### Przypinanie zawodów:
- Kliknij ikonę flagi - zawód zostanie przypięty do góry i wyróżniony

#### Oznaczanie limitu:
- Kliknij ikonę ostrzeżenia - wyświetli się komunikat "Limit osiągnięty"

#### Zmiana kolejności:
- Przeciągnij wiersz za pomocą ikony menu po lewej stronie

#### Wyszukiwanie:
- Wpisz nazwę zawodów lub miejscowość w pole wyszukiwania

### Wyświetlanie na stronie

Użyj shortcode'a w edytorze strony lub wpisu:

```
[race_registration_table]
```

#### Parametry shortcode'a:

```
[race_registration_table limit="10" show_search="yes" show_sort="yes"]
```

**Parametry:**
- `limit` - maksymalna liczba wyświetlanych zawodów (domyślnie: wszystkie)
- `show_search` - pokazuj wyszukiwarkę: `yes` lub `no` (domyślnie: `yes`)
- `show_sort` - pokazuj sortowanie: `yes` lub `no` (domyślnie: `yes`)

**Przykłady:**

```
[race_registration_table limit="5"]
```
Wyświetl 5 najbliższych zawodów

```
[race_registration_table show_search="no"]
```
Wyświetl tabelę bez wyszukiwarki

```
[race_registration_table limit="10" show_search="yes" show_sort="no"]
```
Wyświetl 10 zawodów z wyszukiwarką bez sortowania

## Automatyzacja

### Automatyczne usuwanie starych zawodów

Wtyczka automatycznie ukrywa zawody dzień po wydarzeniu. Działa to poprzez WordPress Cron:
- Uruchamia się raz dziennie
- Sprawdza zawody starsze niż wczoraj
- Automatycznie ukrywa je (nie usuwa!)
- Przypięte zawody NIE są usuwane automatycznie

Jeśli chcesz zachować zawód dłużej, zaznacz opcję **"Przypnij do góry"**.

## Struktura wtyczki

```
race-registration/
├── race-registration.php          # Główny plik wtyczki
├── README.md                       # Dokumentacja
├── includes/
│   ├── class-database.php         # Obsługa bazy danych
│   ├── class-admin.php            # Panel administracyjny
│   └── class-frontend.php         # Frontend (shortcode)
├── admin/
│   ├── css/
│   │   └── admin.css              # Style panelu admin
│   ├── js/
│   │   └── admin.js               # JavaScript panelu admin
│   └── views/
│       └── admin-page.php         # Szablon strony admin
└── public/
    ├── css/
    │   └── frontend.css           # Style frontendu
    └── js/
        └── frontend.js            # JavaScript frontendu
```

## Baza danych

Wtyczka tworzy tabelę `wp_race_registrations` z następującymi polami:

- `id` - ID zawodu
- `race_date` - Data zawodów
- `race_name` - Nazwa zawodów
- `location` - Miejscowość
- `distance` - Dystans
- `website_url` - Link do strony zawodów
- `registration_url` - Link do zapisów
- `is_pinned` - Czy przypięty (0/1)
- `is_limit_reached` - Czy osiągnięto limit (0/1)
- `sort_order` - Kolejność sortowania
- `is_active` - Czy aktywny (0/1)
- `created_at` - Data utworzenia
- `updated_at` - Data aktualizacji

## Wymagania

- WordPress 5.0 lub nowszy
- PHP 7.0 lub nowszy
- MySQL 5.6 lub nowszy

## Wsparcie

W razie problemów lub pytań:
1. Sprawdź dokumentację powyżej
2. Upewnij się, że wtyczka jest aktywowana
3. Sprawdź, czy shortcode jest poprawnie wstawiony
4. Wyczyść cache WordPress

## Autor

**YOGO1988**
- GitHub: [@YOGO1988](https://github.com/YOGO1988)
- Projekt: [rejestracja](https://github.com/YOGO1988/rejestracja)

## Licencja

Ta wtyczka jest darmowa i może być używana dowolnie.

## Changelog

### Wersja 1.0.0 (2025-12-17)
- Pierwsza wersja wtyczki
- Panel administracyjny do zarządzania zawodami
- Shortcode do wyświetlania tabeli
- Wyszukiwarka i sortowanie
- Przypinanie i oznaczanie limitu
- Automatyczne usuwanie starych zawodów
- Responsywny design
- Drag & drop sortowanie
