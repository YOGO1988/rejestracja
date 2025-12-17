<?php
/**
 * Skrypt do bezpośredniego importu wyników z pliku 123.txt
 * Uruchom: php import-results.php
 */

// Ładuj WordPress
$wp_config_paths = [
    __DIR__ . '/wp-config.php',
    __DIR__ . '/../wp-config.php',
    __DIR__ . '/../../wp-config.php',
    __DIR__ . '/../../../wp-config.php',
];

$wp_loaded = false;
foreach ($wp_config_paths as $path) {
    if (file_exists($path)) {
        require_once($path);
        require_once(ABSPATH . 'wp-load.php');
        $wp_loaded = true;
        break;
    }
}

if (!$wp_loaded) {
    die("Nie znaleziono wp-config.php. Uruchom skrypt z katalogu WordPress.\n");
}

echo "=== Import wyników z pliku danych ===\n\n";

// Wczytaj plik (priorytet get-page-content.php.html, fallback 123.txt)
$file_path = __DIR__ . '/get-page-content.php.html';
if (!file_exists($file_path)) {
    $file_path = __DIR__ . '/123.txt';
    if (!file_exists($file_path)) {
        die("Błąd: Nie znaleziono pliku get-page-content.php.html ani 123.txt\n");
    }
}

echo "Używam pliku: " . basename($file_path) . "\n";

$content = file_get_contents($file_path);

// Znajdź serializowane dane ACF
preg_match('/=== PEŁNA ZAWARTOŚĆ POLA \'tabela_wynikow\' ===.*?Długość: \d+ znaków\s+(a:\d+:\{.*?\})\s+===/s', $content, $matches);

if (!isset($matches[1])) {
    die("Błąd: Nie znaleziono danych ACF w pliku\n");
}

$serialized_data = $matches[1];

// Deserializuj dane
$table_data = @unserialize($serialized_data);

if ($table_data === false || !is_array($table_data)) {
    die("Błąd: Nie udało się deserializować danych\n");
}

echo "Znaleziono dane ACF Table Field\n";
echo "Wersja pluginu: " . ($table_data['acftf']['v'] ?? 'nieznana') . "\n\n";

// Sprawdź czy są dane
if (!isset($table_data['b']) || !is_array($table_data['b'])) {
    die("Błąd: Brak danych do importu\n");
}

$total_rows = count($table_data['b']);
echo "Liczba wierszy do importu: $total_rows\n\n";

// Pobierz instancję klasy bazy danych
global $wpdb;
$table_name = $wpdb->prefix . 'race_results';

// Sprawdź czy tabela istnieje
$table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'") === $table_name;

if (!$table_exists) {
    die("Błąd: Tabela $table_name nie istnieje. Upewnij się, że wtyczka jest aktywna.\n");
}

// Funkcje pomocnicze
function parse_pdf_links_simple($html) {
    if (empty($html)) {
        return array();
    }

    $pdf_files = array();
    preg_match_all('/<a[^>]+href=[\'"]([^\'"]+)[\'"][^>]*>([^<]+)<\/a>/i', $html, $matches, PREG_SET_ORDER);

    foreach ($matches as $match) {
        $url = trim($match[1]);
        $text = trim(strip_tags($match[2]));

        // Akceptuj wszystkie linki z PDF
        if (stripos($url, '.pdf') !== false) {
            $pdf_files[] = array(
                'url' => $url,
                'button_text' => $text
            );
        }
    }

    return $pdf_files;
}

function parse_online_link_simple($html) {
    if (empty($html)) {
        return '';
    }

    preg_match('/<a[^>]+href=[\'"]([^\'"]+)[\'"][^>]*>/i', $html, $matches);
    return isset($matches[1]) ? trim($matches[1]) : '';
}

function format_date_simple($date) {
    if (empty($date)) {
        return null;
    }

    // Usuń 'r' z końca
    $date = trim(str_replace('r', '', $date));

    // Usuń kropkę z końca jeśli istnieje
    $date = rtrim($date, '.');

    // Spróbuj parsować d.m.Y
    $parsed = DateTime::createFromFormat('d.m.Y', $date);
    if ($parsed) {
        return $parsed->format('Y-m-d');
    }

    // Spróbuj d/m/Y
    $parsed = DateTime::createFromFormat('d/m/Y', $date);
    if ($parsed) {
        return $parsed->format('Y-m-d');
    }

    // Fallback do strtotime
    $timestamp = strtotime($date);
    if ($timestamp !== false) {
        return date('Y-m-d', $timestamp);
    }

    return null;
}

// Importuj dane
$imported = 0;
$skipped = 0;
$errors = 0;

echo "Rozpoczynam import...\n\n";

foreach ($table_data['b'] as $index => $row) {
    if (!is_array($row) || count($row) < 3) {
        $skipped++;
        continue;
    }

    // Wyciągnij wartości z komórek
    $date_cell = isset($row[0]['c']) ? $row[0]['c'] : '';
    $name_cell = isset($row[1]['c']) ? $row[1]['c'] : '';
    $location_cell = isset($row[2]['c']) ? $row[2]['c'] : '';
    $pdf_cell = isset($row[3]['c']) ? $row[3]['c'] : '';
    $online_cell = isset($row[4]['c']) ? $row[4]['c'] : '';

    $date = format_date_simple($date_cell);
    $name = trim($name_cell);
    $location = trim($location_cell);

    // Pomiń wpisy bez podstawowych danych
    if (empty($date) || empty($name) || empty($location)) {
        echo "Pomijam wiersz " . ($index + 1) . ": brak podstawowych danych\n";
        $skipped++;
        continue;
    }

    // Parsuj linki
    $pdf_files = parse_pdf_links_simple($pdf_cell);
    $online_url = parse_online_link_simple($online_cell);

    // Przygotuj dane do zapisu
    $results_pdf = !empty($pdf_files) ? json_encode($pdf_files) : null;

    // Wstaw do bazy
    $result = $wpdb->insert(
        $table_name,
        array(
            'race_date' => $date,
            'race_name' => $name,
            'location' => $location,
            'results_pdf' => $results_pdf,
            'results_online_url' => $online_url,
            'created_at' => current_time('mysql'),
            'updated_at' => current_time('mysql')
        ),
        array('%s', '%s', '%s', '%s', '%s', '%s', '%s')
    );

    if ($result) {
        $imported++;
        if ($imported % 10 == 0) {
            echo "Zaimportowano: $imported/$total_rows\n";
        }
    } else {
        $errors++;
        echo "Błąd przy wierszu " . ($index + 1) . ": " . $wpdb->last_error . "\n";
    }
}

echo "\n=== PODSUMOWANIE ===\n";
echo "Całkowita liczba wierszy: $total_rows\n";
echo "Zaimportowano: $imported\n";
echo "Pominięto: $skipped\n";
echo "Błędów: $errors\n";
echo "\nImport zakończony!\n";
