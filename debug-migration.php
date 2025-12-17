<?php
/**
 * Skrypt diagnostyczny do debugowania migracji
 * Uruchom: php debug-migration.php
 */

// Ścieżka do WordPress
$wp_load_path = '/home/user/rejestracja'; // Zmień na właściwą ścieżkę do WordPress

// Spróbuj znaleźć wp-load.php
$possible_paths = array(
    dirname(__FILE__) . '/wp-load.php',
    dirname(__FILE__) . '/../../../wp-load.php',
    '/var/www/html/wp-load.php',
    getcwd() . '/wp-load.php',
);

$wp_loaded = false;
foreach ($possible_paths as $path) {
    if (file_exists($path)) {
        require_once($path);
        $wp_loaded = true;
        echo "✓ WordPress załadowany z: $path\n\n";
        break;
    }
}

if (!$wp_loaded) {
    echo "✗ Nie można znaleźć wp-load.php\n";
    echo "Uruchom skrypt z katalogu WordPress lub podaj ścieżkę do wp-load.php\n";
    exit(1);
}

// ID strony do sprawdzenia
$page_id = 1191;

echo "=== DIAGNOZA MIGRACJI WYNIKÓW ===\n\n";
echo "Sprawdzam stronę ID: $page_id\n\n";

// Pobierz post
$post = get_post($page_id);

if (!$post) {
    echo "✗ Nie znaleziono strony o ID: $page_id\n";
    exit(1);
}

echo "✓ Znaleziono stronę:\n";
echo "  Tytuł: {$post->post_title}\n";
echo "  Status: {$post->post_status}\n";
echo "  Typ: {$post->post_type}\n\n";

// Pokaż długość contentu
$content = $post->post_content;
$content_length = strlen($content);
echo "✓ Długość zawartości: $content_length znaków\n\n";

// Sprawdź czy są tagi <table>
$table_count = substr_count(strtolower($content), '<table');
echo "Liczba tagów <table>: $table_count\n\n";

if ($table_count === 0) {
    echo "✗ PROBLEM: Nie znaleziono żadnego tagu <table> w zawartości strony!\n";
    echo "Zawartość może być w meta polach lub blokach Gutenberg.\n\n";

    // Sprawdź meta pola
    echo "=== META POLA ===\n";
    $meta_keys = get_post_meta($page_id);
    foreach ($meta_keys as $key => $values) {
        if (strpos(strtolower($key), 'wynik') !== false || strpos(strtolower($key), 'table') !== false) {
            echo "Meta: $key\n";
            foreach ($values as $value) {
                $val_preview = substr($value, 0, 200);
                echo "  Wartość (pierwsze 200 znaków): $val_preview...\n";
            }
        }
    }
    echo "\n";
}

// Pokaż fragment contentu
echo "=== PIERWSZE 1000 ZNAKÓW ZAWARTOŚCI ===\n";
echo substr($content, 0, 1000) . "...\n\n";

// Spróbuj sparsować tabelę
echo "=== PRÓBA PARSOWANIA TABELI ===\n";

libxml_use_internal_errors(true);
$dom = new DOMDocument();
$dom->loadHTML('<?xml encoding="UTF-8">' . $content);
libxml_clear_errors();

$xpath = new DOMXPath($dom);
$table_rows = $xpath->query('//table//tr');

echo "Znaleziono wierszy <tr>: {$table_rows->length}\n\n";

if ($table_rows->length > 0) {
    echo "=== STRUKTURA TABELI ===\n";

    foreach ($table_rows as $index => $row) {
        $cells = $row->getElementsByTagName('td');
        $headers = $row->getElementsByTagName('th');

        $cell_count = $cells->length + $headers->length;

        echo "Wiersz $index: ";

        if ($headers->length > 0) {
            echo "NAGŁÓWKI ($headers->length komórek)\n";
            for ($i = 0; $i < $headers->length; $i++) {
                $text = trim($headers->item($i)->textContent);
                echo "  [$i] $text\n";
            }
        } else if ($cells->length > 0) {
            echo "DANE ($cells->length komórek)\n";
            for ($i = 0; $i < min(5, $cells->length); $i++) {
                $text = trim($cells->item($i)->textContent);
                $text_preview = substr($text, 0, 50);
                echo "  [$i] $text_preview" . (strlen($text) > 50 ? "..." : "") . "\n";
            }
        } else {
            echo "PUSTY\n";
        }

        echo "\n";

        // Pokaż tylko pierwsze 5 wierszy
        if ($index >= 4) {
            echo "... (pozostałe wiersze pominięte)\n";
            break;
        }
    }
}

// Sprawdź czy to może być tabela ACF
echo "\n=== SPRAWDZANIE POLA ACF 'tabela_wynikow' ===\n";
if (function_exists('get_field')) {
    $acf_table = get_field('tabela_wynikow', $page_id);
    if ($acf_table) {
        echo "✓ Znaleziono pole ACF 'tabela_wynikow'\n";
        echo "Typ: " . gettype($acf_table) . "\n";
        if (is_string($acf_table)) {
            echo "Zawartość (pierwsze 500 znaków):\n";
            echo substr($acf_table, 0, 500) . "...\n";
        } else if (is_array($acf_table)) {
            echo "Tablica z " . count($acf_table) . " elementami\n";
            print_r(array_slice($acf_table, 0, 2));
        }
    } else {
        echo "✗ Pole ACF 'tabela_wynikow' jest puste lub nie istnieje\n";
    }
} else {
    echo "✗ ACF nie jest zainstalowane\n";
}

echo "\n=== KONIEC DIAGNOZY ===\n";
