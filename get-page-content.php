<?php
/**
 * Prosty skrypt do wyciągnięcia zawartości strony z bazy WordPress
 * Umieść w głównym katalogu WordPress i uruchom: php get-page-content.php
 */

// Spróbuj załadować wp-config.php
$config_paths = [
    __DIR__ . '/wp-config.php',
    __DIR__ . '/../wp-config.php',
    __DIR__ . '/../../wp-config.php',
    __DIR__ . '/../../../wp-config.php',
];

$config_loaded = false;
foreach ($config_paths as $path) {
    if (file_exists($path)) {
        require_once($path);
        $config_loaded = true;
        break;
    }
}

if (!$config_loaded) {
    die("Nie znaleziono wp-config.php. Uruchom skrypt z katalogu WordPress.\n");
}

// Połącz się z bazą
$mysqli = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);

if ($mysqli->connect_error) {
    die("Błąd połączenia: " . $mysqli->connect_error . "\n");
}

// Pobierz zawartość strony ID 1191
$page_id = 1191;
$table_prefix = isset($table_prefix) ? $table_prefix : 'wp_';

$query = "SELECT post_title, post_content, post_type, post_status FROM {$table_prefix}posts WHERE ID = ?";
$stmt = $mysqli->prepare($query);
$stmt->bind_param("i", $page_id);
$stmt->execute();
$result = $stmt->get_result();

if ($row = $result->fetch_assoc()) {
    echo "=== STRONA ID: $page_id ===\n\n";
    echo "Tytuł: " . $row['post_title'] . "\n";
    echo "Typ: " . $row['post_type'] . "\n";
    echo "Status: " . $row['post_status'] . "\n\n";

    $content = $row['post_content'];
    $content_length = strlen($content);

    echo "Długość zawartości: $content_length znaków\n\n";

    // Sprawdź czy są tagi table
    $table_count = substr_count(strtolower($content), '<table');
    echo "Liczba tagów <table>: $table_count\n\n";

    if ($table_count > 0) {
        echo "=== ZAWARTOŚĆ (pierwsze 2000 znaków) ===\n";
        echo substr($content, 0, 2000) . "\n\n";

        // Znajdź początek i koniec tabeli
        $table_start = stripos($content, '<table');
        $table_end = stripos($content, '</table>', $table_start);

        if ($table_start !== false && $table_end !== false) {
            $table_html = substr($content, $table_start, $table_end - $table_start + 8);
            echo "=== PEŁNA TABELA HTML ===\n";
            echo $table_html . "\n\n";
        }
    } else {
        echo "BRAK TABEL W ZAWARTOŚCI!\n\n";
        echo "=== PEŁNA ZAWARTOŚĆ ===\n";
        echo $content . "\n";
    }

    // Sprawdź postmeta
    echo "\n=== META POLA ===\n";
    $meta_query = "SELECT meta_key, meta_value FROM {$table_prefix}postmeta WHERE post_id = ? AND meta_key = 'tabela_wynikow'";
    $meta_stmt = $mysqli->prepare($meta_query);
    $meta_stmt->bind_param("i", $page_id);
    $meta_stmt->execute();
    $meta_result = $meta_stmt->get_result();

    if ($meta_row = $meta_result->fetch_assoc()) {
        $value = $meta_row['meta_value'];

        echo "\n=== PEŁNA ZAWARTOŚĆ POLA 'tabela_wynikow' ===\n";
        echo "Długość: " . strlen($value) . " znaków\n\n";
        echo $value . "\n\n";

        // Spróbuj zdekodować
        echo "=== ZDEKODOWANE DANE ===\n";
        $unserialized = @unserialize($value);
        if ($unserialized !== false) {
            print_r($unserialized);
        } else {
            echo "Nie udało się zdekodować!\n";
        }
    } else {
        echo "Pole 'tabela_wynikow' nie znalezione!\n";
    }

} else {
    echo "Nie znaleziono strony o ID: $page_id\n";
}

$mysqli->close();
