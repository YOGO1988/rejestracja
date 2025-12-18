<?php
/**
 * Strona importu danych w panelu admin
 */

if (!defined('ABSPATH')) {
    exit;
}

class Race_Results_Import_Page {

    private static $instance = null;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action('admin_menu', array($this, 'add_import_page'), 101);
        add_action('wp_ajax_race_results_import_data', array($this, 'ajax_import_data'));
        add_action('wp_ajax_race_results_clear_data', array($this, 'ajax_clear_data'));
    }

    /**
     * Dodaj stronę importu do menu
     */
    public function add_import_page() {
        add_submenu_page(
            'race-results',
            'Import Danych',
            'Import Danych',
            'manage_options',
            'race-results-import',
            array($this, 'render_import_page')
        );
    }

    /**
     * Renderuj stronę importu
     */
    public function render_import_page() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'race_results';
        $count = $wpdb->get_var("SELECT COUNT(*) FROM $table_name");

        $file_path = RACE_RESULTS_PLUGIN_DIR . 'get-page-content.php.html';
        $file_exists = file_exists($file_path);
        if (!$file_exists) {
            $file_path = RACE_RESULTS_PLUGIN_DIR . '123.txt';
            $file_exists = file_exists($file_path);
        }
        ?>
        <div class="wrap">
            <h1>Import Danych Wyników</h1>

            <div class="notice notice-info">
                <p><strong>Status:</strong></p>
                <ul>
                    <li>Liczba rekordów w bazie: <strong><?php echo $count; ?></strong></li>
                    <li>Plik danych: <strong><?php echo $file_exists ? basename($file_path) : 'NIE ZNALEZIONO'; ?></strong></li>
                </ul>
            </div>

            <?php if (!$file_exists): ?>
                <div class="notice notice-error">
                    <p><strong>Błąd:</strong> Nie znaleziono pliku z danymi (get-page-content.php.html lub 123.txt)</p>
                    <p>Upewnij się, że plik znajduje się w katalogu wtyczki.</p>
                </div>
            <?php else: ?>
                <div class="card" style="max-width: 800px;">
                    <h2>Importuj dane z pliku</h2>
                    <p>Kliknij przycisk poniżej aby zaimportować dane z pliku <code><?php echo basename($file_path); ?></code></p>

                    <?php if ($count > 0): ?>
                        <div class="notice notice-warning inline">
                            <p><strong>Uwaga:</strong> W bazie znajdują się już dane. Import doda nowe rekordy (nie usuwa istniejących).</p>
                            <p>Jeśli chcesz wyczyścić bazę przed importem, użyj przycisku "Wyczyść bazę danych" poniżej.</p>
                        </div>
                    <?php endif; ?>

                    <p>
                        <button type="button" id="import-data" class="button button-primary button-hero">
                            <span class="dashicons dashicons-download" style="margin-top: 3px;"></span>
                            Importuj dane teraz
                        </button>
                    </p>
                </div>

                <?php if ($count > 0): ?>
                    <div class="card" style="max-width: 800px; margin-top: 20px;">
                        <h2>Wyczyść bazę danych</h2>
                        <p>Usuń wszystkie rekordy z bazy danych wyników.</p>
                        <p>
                            <button type="button" id="clear-data" class="button button-secondary">
                                <span class="dashicons dashicons-trash" style="margin-top: 3px;"></span>
                                Wyczyść bazę danych
                            </button>
                        </p>
                    </div>
                <?php endif; ?>

                <div id="import-results" style="display: none; margin-top: 20px;">
                    <div id="results-content"></div>
                </div>
            <?php endif; ?>
        </div>

        <style>
            .import-success {
                background: #d4edda;
                color: #155724;
                padding: 15px;
                border-radius: 4px;
                border-left: 4px solid #28a745;
                margin-top: 20px;
            }

            .import-error {
                background: #f8d7da;
                color: #721c24;
                padding: 15px;
                border-radius: 4px;
                border-left: 4px solid #dc3545;
                margin-top: 20px;
            }

            .import-progress {
                background: #d1ecf1;
                color: #0c5460;
                padding: 15px;
                border-radius: 4px;
                border-left: 4px solid #17a2b8;
                margin-top: 20px;
            }
        </style>

        <script>
        jQuery(document).ready(function($) {
            $('#import-data').on('click', function() {
                const button = $(this);
                button.prop('disabled', true).text('Importowanie...');

                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'race_results_import_data',
                        nonce: '<?php echo wp_create_nonce('race_results_import_nonce'); ?>'
                    },
                    success: function(response) {
                        button.prop('disabled', false).text('Importuj dane teraz');

                        if (response.success) {
                            $('#results-content').html(
                                '<div class="import-success">' +
                                '<h3>Import zakończony pomyślnie!</h3>' +
                                '<p>' + response.data.message + '</p>' +
                                '</div>'
                            );
                            $('#import-results').slideDown();

                            // Odśwież stronę po 2 sekundach
                            setTimeout(function() {
                                location.reload();
                            }, 2000);
                        } else {
                            $('#results-content').html(
                                '<div class="import-error">' +
                                '<h3>Błąd podczas importu</h3>' +
                                '<p>' + response.data.message + '</p>' +
                                '</div>'
                            );
                            $('#import-results').slideDown();
                        }
                    },
                    error: function() {
                        button.prop('disabled', false).text('Importuj dane teraz');
                        $('#results-content').html(
                            '<div class="import-error">' +
                            '<h3>Błąd połączenia</h3>' +
                            '<p>Nie udało się połączyć z serwerem.</p>' +
                            '</div>'
                        );
                        $('#import-results').slideDown();
                    }
                });
            });

            $('#clear-data').on('click', function() {
                if (!confirm('Czy na pewno chcesz usunąć WSZYSTKIE dane z bazy? Ta operacja jest nieodwracalna!')) {
                    return;
                }

                const button = $(this);
                button.prop('disabled', true).text('Czyszczenie...');

                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'race_results_clear_data',
                        nonce: '<?php echo wp_create_nonce('race_results_import_nonce'); ?>'
                    },
                    success: function(response) {
                        if (response.success) {
                            location.reload();
                        } else {
                            alert('Błąd: ' + response.data.message);
                            button.prop('disabled', false).text('Wyczyść bazę danych');
                        }
                    }
                });
            });
        });
        </script>
        <?php
    }

    /**
     * AJAX: Importuj dane
     */
    public function ajax_import_data() {
        check_ajax_referer('race_results_import_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Brak uprawnień'));
        }

        // Uruchom import
        $result = $this->run_import();

        if ($result['success']) {
            wp_send_json_success(array('message' => $result['message']));
        } else {
            wp_send_json_error(array('message' => $result['message']));
        }
    }

    /**
     * AJAX: Wyczyść dane
     */
    public function ajax_clear_data() {
        check_ajax_referer('race_results_import_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Brak uprawnień'));
        }

        global $wpdb;
        $table_name = $wpdb->prefix . 'race_results';
        $wpdb->query("TRUNCATE TABLE $table_name");

        wp_send_json_success(array('message' => 'Baza danych została wyczyszczona'));
    }

    /**
     * Uruchom import (używa tej samej logiki co auto_import_data)
     */
    private function run_import() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'race_results';

        // Sprawdź czy plik istnieje
        $file_path = RACE_RESULTS_PLUGIN_DIR . 'get-page-content.php.html';
        if (!file_exists($file_path)) {
            $file_path = RACE_RESULTS_PLUGIN_DIR . '123.txt';
            if (!file_exists($file_path)) {
                return array('success' => false, 'message' => 'Nie znaleziono pliku z danymi');
            }
        }

        // Wczytaj plik
        $content = file_get_contents($file_path);

        // Znajdź serializowane dane ACF - szukaj linii zaczynającej się od a:5:{s:5:"acftf"
        if (preg_match('/(a:5:\{s:5:"acftf".*)/s', $content, $matches)) {
            // Znaleziono - wyciągnij do końca lub do następnego ===
            $serialized = preg_split('/\s*===/', $matches[1])[0];
            // Dekoduj encje HTML (plik z GitHub ma &gt;, &quot;, itp.)
            $serialized = html_entity_decode($serialized, ENT_QUOTES | ENT_HTML5, 'UTF-8');
            $table_data = @unserialize($serialized);
        } else {
            return array('success' => false, 'message' => 'Nie znaleziono danych ACF w pliku');
        }

        if ($table_data === false || !is_array($table_data)) {
            return array('success' => false, 'message' => 'Błąd deserializacji - dane mogą być uszkodzone');
        }

        if (!isset($table_data['b']) || !is_array($table_data['b'])) {
            return array('success' => false, 'message' => 'Brak danych do importu w pliku');
        }

        $imported = 0;
        $skipped = 0;

        // Importuj dane
        foreach ($table_data['b'] as $row) {
            if (!is_array($row) || count($row) < 3) {
                $skipped++;
                continue;
            }

            $date_cell = isset($row[0]['c']) ? $row[0]['c'] : '';
            $name_cell = isset($row[1]['c']) ? $row[1]['c'] : '';
            $location_cell = isset($row[2]['c']) ? $row[2]['c'] : '';
            $pdf_cell = isset($row[3]['c']) ? $row[3]['c'] : '';
            $online_cell = isset($row[4]['c']) ? $row[4]['c'] : '';

            $date = $this->format_date(trim(str_replace('r', '', $date_cell)));
            $name = trim($name_cell);
            $location = trim($location_cell);

            if (empty($date) || empty($name) || empty($location)) {
                $skipped++;
                continue;
            }

            $pdf_files = $this->parse_links($pdf_cell);
            $online_url = $this->extract_url($online_cell);

            $result = $wpdb->insert(
                $table_name,
                array(
                    'race_date' => $date,
                    'race_name' => $name,
                    'location' => $location,
                    'results_pdf' => !empty($pdf_files) ? json_encode($pdf_files) : null,
                    'results_online_url' => $online_url,
                    'created_at' => current_time('mysql'),
                    'updated_at' => current_time('mysql')
                ),
                array('%s', '%s', '%s', '%s', '%s', '%s', '%s')
            );

            if ($result) {
                $imported++;
            } else {
                $skipped++;
            }
        }

        return array(
            'success' => true,
            'message' => sprintf('Zaimportowano %d wpisów. Pominięto: %d', $imported, $skipped)
        );
    }

    private function format_date($date) {
        if (empty($date)) {
            return null;
        }

        $date = rtrim($date, '.');
        $parsed = DateTime::createFromFormat('d.m.Y', $date);

        if ($parsed) {
            return $parsed->format('Y-m-d');
        }

        $timestamp = strtotime($date);
        return $timestamp !== false ? date('Y-m-d', $timestamp) : null;
    }

    private function parse_links($html) {
        if (empty($html)) {
            return array();
        }

        $links = array();
        preg_match_all('/<a[^>]+href=[\'"]([^\'"]+)[\'"][^>]*>([^<]+)<\/a>/i', $html, $matches, PREG_SET_ORDER);

        foreach ($matches as $match) {
            $url = trim($match[1]);
            $text = trim(strip_tags($match[2]));

            if (stripos($url, '.pdf') !== false) {
                $links[] = array('url' => $url, 'button_text' => $text);
            }
        }

        return $links;
    }

    private function extract_url($html) {
        if (empty($html)) {
            return '';
        }

        preg_match('/<a[^>]+href=[\'"]([^\'"]+)[\'"][^>]*>/i', $html, $matches);
        return isset($matches[1]) ? trim($matches[1]) : '';
    }
}
