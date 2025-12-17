<?php
/**
 * Klasa obsługująca migrację danych z ACF
 */

if (!defined('ABSPATH')) {
    exit;
}

class Race_Results_Migration {

    private static $instance = null;
    private $db;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        $this->db = Race_Results_Database::get_instance();

        add_action('admin_menu', array($this, 'add_migration_page'), 100);
        add_action('wp_ajax_race_results_migrate_html', array($this, 'ajax_migrate_html'));
        add_action('wp_ajax_race_results_preview_html', array($this, 'ajax_preview_html'));
    }

    /**
     * Dodaj stronę migracji do menu
     */
    public function add_migration_page() {
        add_submenu_page(
            'race-results',
            'Migracja z ACF',
            'Migracja z ACF',
            'manage_options',
            'race-results-migration',
            array($this, 'render_migration_page')
        );
    }

    /**
     * Renderuj stronę migracji
     */
    public function render_migration_page() {
        ?>
        <div class="wrap">
            <h1>Migracja wyników z ACF</h1>

            <div class="migration-instructions">
                <h2>Instrukcje migracji</h2>
                <p>Ta strona pomoże Ci przenieść dane z tabeli HTML w edytorze strony do nowej struktury bazy danych.</p>
                <p><strong>Przed migracją:</strong></p>
                <ol>
                    <li>Znajdź ID strony zawierającej tabelę z wynikami (np. strona "Wyniki")</li>
                    <li>Wpisz ID strony poniżej</li>
                    <li>System wyświetli podgląd danych do migracji</li>
                    <li>Sprawdź, czy dane są poprawne</li>
                    <li>Kliknij "Migruj dane" aby przenieść dane do nowej tabeli</li>
                </ol>

                <h3>Format danych w tabeli</h3>
                <p>System automatycznie wykryje i przekonwertuje tabelę HTML z kolumnami:</p>
                <ul>
                    <li><strong>Data:</strong> Format daty (DD.MM.YYYY)</li>
                    <li><strong>Nazwa wydarzenia:</strong> Tekst</li>
                    <li><strong>Miejscowość:</strong> Tekst</li>
                    <li><strong>Wyniki PDF:</strong> Linki do PDF (np. <code>&lt;a href="...pdf"&gt;Wyniki 10km&lt;/a&gt;</code>)</li>
                    <li><strong>Wyniki online:</strong> Link (np. <code>&lt;a href="..."&gt;zobacz&lt;/a&gt;</code>)</li>
                </ul>
            </div>

            <div class="migration-form">
                <h2>Konfiguracja migracji</h2>
                <form id="migration-config-form">
                    <table class="form-table">
                        <tr>
                            <th scope="row">
                                <label for="page-id">ID strony z wynikami</label>
                            </th>
                            <td>
                                <input type="number" id="page-id" name="page_id" class="regular-text" placeholder="np. 1190">
                                <p class="description">
                                    ID strony zawierającej tabelę z wynikami.
                                    Znajdziesz je w adresie URL podczas edycji strony (np. post=1190)
                                </p>
                            </td>
                        </tr>
                    </table>

                    <p class="submit">
                        <button type="button" id="preview-migration" class="button button-secondary">Podgląd migracji</button>
                        <button type="button" id="start-migration" class="button button-primary" disabled>Migruj dane</button>
                    </p>
                </form>
            </div>

            <div id="migration-preview" style="display: none;">
                <h2>Podgląd danych do migracji</h2>
                <div id="preview-content"></div>
            </div>

            <div id="migration-results" style="display: none;">
                <h2>Wyniki migracji</h2>
                <div id="results-content"></div>
            </div>
        </div>

        <style>
            .migration-instructions {
                background: #f9f9f9;
                padding: 20px;
                border-radius: 8px;
                margin: 20px 0;
            }

            .migration-form {
                background: #fff;
                padding: 20px;
                border-radius: 8px;
                box-shadow: 0 2px 5px rgba(0,0,0,0.1);
                margin: 20px 0;
            }

            #migration-preview,
            #migration-results {
                background: #fff;
                padding: 20px;
                border-radius: 8px;
                box-shadow: 0 2px 5px rgba(0,0,0,0.1);
                margin: 20px 0;
            }

            .preview-item {
                padding: 15px;
                background: #f9f9f9;
                margin-bottom: 15px;
                border-radius: 4px;
                border-left: 4px solid #2271b1;
            }

            .preview-item h4 {
                margin: 0 0 10px 0;
                color: #2271b1;
            }

            .preview-item p {
                margin: 5px 0;
            }

            .migration-success {
                background: #d4edda;
                color: #155724;
                padding: 15px;
                border-radius: 4px;
                border-left: 4px solid #28a745;
            }

            .migration-error {
                background: #f8d7da;
                color: #721c24;
                padding: 15px;
                border-radius: 4px;
                border-left: 4px solid #dc3545;
            }
        </style>

        <script>
        jQuery(document).ready(function($) {
            $('#preview-migration').on('click', function() {
                const pageId = $('#page-id').val();

                if (!pageId) {
                    alert('Podaj ID strony z wynikami');
                    return;
                }

                const data = {
                    action: 'race_results_preview_html',
                    nonce: '<?php echo wp_create_nonce('race_results_migration_nonce'); ?>',
                    page_id: pageId
                };

                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: data,
                    success: function(response) {
                        if (response.success) {
                            $('#preview-content').html(response.data.html);
                            $('#migration-preview').slideDown();
                            $('#start-migration').prop('disabled', false);
                        } else {
                            alert('Błąd: ' + response.data.message);
                        }
                    }
                });
            });

            $('#start-migration').on('click', function() {
                if (!confirm('Czy na pewno chcesz przeprowadzić migrację? Ta operacja jest nieodwracalna.')) {
                    return;
                }

                const pageId = $('#page-id').val();

                if (!pageId) {
                    alert('Podaj ID strony z wynikami');
                    return;
                }

                const data = {
                    action: 'race_results_migrate_html',
                    nonce: '<?php echo wp_create_nonce('race_results_migration_nonce'); ?>',
                    page_id: pageId
                };

                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: data,
                    success: function(response) {
                        if (response.success) {
                            $('#results-content').html('<div class="migration-success">' + response.data.message + '</div>');
                            $('#migration-results').slideDown();
                            $('#start-migration').prop('disabled', true);
                        } else {
                            $('#results-content').html('<div class="migration-error">' + response.data.message + '</div>');
                            $('#migration-results').slideDown();
                        }
                    }
                });
            });
        });
        </script>
        <?php
    }

    /**
     * AJAX: Podgląd danych z tabeli HTML
     */
    public function ajax_preview_html() {
        check_ajax_referer('race_results_migration_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Brak uprawnień'));
        }

        $page_id = intval($_POST['page_id']);

        if (!$page_id) {
            wp_send_json_error(array('message' => 'Nieprawidłowe ID strony'));
        }

        // Pobierz zawartość strony
        $post = get_post($page_id);

        if (!$post) {
            wp_send_json_error(array('message' => 'Nie znaleziono strony o ID: ' . $page_id));
        }

        // Parsuj tabelę z HTML
        $rows = $this->parse_html_table($post->post_content);

        if (empty($rows)) {
            wp_send_json_error(array('message' => 'Nie znaleziono tabeli z wynikami na stronie'));
        }

        $html = '<p>Znaleziono <strong>' . count($rows) . '</strong> wierszy do migracji:</p>';

        foreach ($rows as $row) {
            $html .= '<div class="preview-item">';
            $html .= '<h4>' . esc_html($row['name']) . '</h4>';
            $html .= '<p><strong>Data:</strong> ' . esc_html($row['date']) . '</p>';
            $html .= '<p><strong>Miejscowość:</strong> ' . esc_html($row['location']) . '</p>';
            $html .= '<p><strong>Liczba plików PDF:</strong> ' . count($row['pdf_files']) . '</p>';

            if (!empty($row['pdf_files'])) {
                $html .= '<p><strong>Pliki PDF:</strong></p><ul>';
                foreach ($row['pdf_files'] as $pdf) {
                    $html .= '<li>' . esc_html($pdf['button_text']) . ' - ' . esc_html($pdf['url']) . '</li>';
                }
                $html .= '</ul>';
            }

            if (!empty($row['online_url'])) {
                $html .= '<p><strong>Link online:</strong> ' . esc_html($row['online_url']) . '</p>';
            }

            $html .= '</div>';
        }

        wp_send_json_success(array('html' => $html));
    }

    /**
     * AJAX: Migracja danych z tabeli HTML
     */
    public function ajax_migrate_html() {
        check_ajax_referer('race_results_migration_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Brak uprawnień'));
        }

        $page_id = intval($_POST['page_id']);

        if (!$page_id) {
            wp_send_json_error(array('message' => 'Nieprawidłowe ID strony'));
        }

        // Pobierz zawartość strony
        $post = get_post($page_id);

        if (!$post) {
            wp_send_json_error(array('message' => 'Nie znaleziono strony'));
        }

        // Parsuj tabelę z HTML
        $rows = $this->parse_html_table($post->post_content);

        if (empty($rows)) {
            wp_send_json_error(array('message' => 'Nie znaleziono tabeli z wynikami'));
        }

        $migrated = 0;
        $errors = 0;

        foreach ($rows as $row) {
            // Konwersja daty na format YYYY-MM-DD
            $formatted_date = $this->format_date($row['date']);

            if (empty($formatted_date) || empty($row['name']) || empty($row['location'])) {
                $errors++;
                continue;
            }

            // Filtruj pliki PDF - usuń te które nie mają file_id
            $valid_pdf_files = array();
            foreach ($row['pdf_files'] as $pdf) {
                if (!empty($pdf['file_id'])) {
                    $valid_pdf_files[] = array(
                        'file_id' => $pdf['file_id'],
                        'button_text' => $pdf['button_text']
                    );
                }
            }

            $data = array(
                'race_date' => $formatted_date,
                'race_name' => $row['name'],
                'location' => $row['location'],
                'results_pdf' => $valid_pdf_files,
                'results_online_url' => $row['online_url']
            );

            $result = $this->db->add_result($data);

            if ($result) {
                $migrated++;
            } else {
                $errors++;
            }
        }

        $message = 'Migracja zakończona! Zmigrowano: ' . $migrated . ' wpisów.';
        if ($errors > 0) {
            $message .= ' Błędów: ' . $errors;
        }

        wp_send_json_success(array('message' => $message));
    }

    /**
     * Parsuj tabelę HTML i wyciągnij dane
     */
    private function parse_html_table($html) {
        $rows_data = array();

        // Użyj DOMDocument do parsowania HTML
        libxml_use_internal_errors(true);
        $dom = new DOMDocument();
        $dom->loadHTML('<?xml encoding="UTF-8">' . $html);
        libxml_clear_errors();

        // Znajdź wszystkie wiersze tabeli
        $xpath = new DOMXPath($dom);
        $table_rows = $xpath->query('//table//tr');

        if ($table_rows->length === 0) {
            return array();
        }

        // Pomiń pierwszy wiersz (nagłówki)
        foreach ($table_rows as $index => $row) {
            if ($index === 0) {
                continue; // Pomiń nagłówki
            }

            $cells = $row->getElementsByTagName('td');

            if ($cells->length < 3) {
                continue; // Pomiń wiersze z mniej niż 3 kolumnami
            }

            // Wyciągnij dane z komórek
            // Zakładamy kolejność: Data (0), Nazwa (1), Miejscowość (2), PDF (3), Online (4)
            $date = trim($cells->item(0)->textContent);
            $name = trim($cells->item(1)->textContent);
            $location = trim($cells->item(2)->textContent);

            // Wyniki PDF - wyciągnij wszystkie linki
            $pdf_files = array();
            if ($cells->length > 3) {
                $pdf_cell_html = $dom->saveHTML($cells->item(3));
                $pdf_files = $this->parse_pdf_links($pdf_cell_html);
            }

            // Wyniki online - wyciągnij link
            $online_url = '';
            if ($cells->length > 4) {
                $online_cell_html = $dom->saveHTML($cells->item(4));
                $online_url = $this->parse_online_link($online_cell_html);
            }

            $rows_data[] = array(
                'date' => $date,
                'name' => $name,
                'location' => $location,
                'pdf_files' => $pdf_files,
                'online_url' => $online_url
            );
        }

        return $rows_data;
    }

    /**
     * Parsuj linki PDF z HTML
     */
    private function parse_pdf_links($html) {
        if (empty($html)) {
            return array();
        }

        $pdf_files = array();

        // Regex do znalezienia wszystkich linków do PDF
        preg_match_all('/<a[^>]+href=["\']([^"\']+)["\'][^>]*>([^<]+)<\/a>/i', $html, $matches, PREG_SET_ORDER);

        foreach ($matches as $match) {
            $url = $match[1];
            $text = strip_tags($match[2]);

            // Pomiń linki które nie są PDF
            if (stripos($url, '.pdf') === false) {
                continue;
            }

            // Sprawdź czy plik jest w WordPress Media Library
            $attachment_id = attachment_url_to_postid($url);

            if ($attachment_id) {
                $pdf_files[] = array(
                    'file_id' => $attachment_id,
                    'button_text' => $text,
                    'url' => $url
                );
            } else {
                // Jeśli plik nie jest w bibliotece, spróbuj go zaimportować
                $attachment_id = $this->import_pdf_from_url($url);
                if ($attachment_id) {
                    $pdf_files[] = array(
                        'file_id' => $attachment_id,
                        'button_text' => $text,
                        'url' => $url
                    );
                } else {
                    // Jeśli import nie udał się, zapisz URL dla informacji
                    $pdf_files[] = array(
                        'file_id' => 0,
                        'button_text' => $text,
                        'url' => $url
                    );
                }
            }
        }

        return $pdf_files;
    }

    /**
     * Parsuj link online z HTML
     */
    private function parse_online_link($html) {
        if (empty($html)) {
            return '';
        }

        // Regex do znalezienia linku
        preg_match('/<a[^>]+href=["\']([^"\']+)["\'][^>]*>/i', $html, $matches);

        return isset($matches[1]) ? $matches[1] : '';
    }

    /**
     * Formatuj datę na YYYY-MM-DD
     */
    private function format_date($date) {
        if (empty($date)) {
            return '';
        }

        // Spróbuj różnych formatów
        $formats = array('Y-m-d', 'd.m.Y', 'd/m/Y', 'm/d/Y', 'Ymd');

        foreach ($formats as $format) {
            $parsed = DateTime::createFromFormat($format, $date);
            if ($parsed) {
                return $parsed->format('Y-m-d');
            }
        }

        // Jeśli żaden format nie pasuje, spróbuj strtotime
        $timestamp = strtotime($date);
        if ($timestamp !== false) {
            return date('Y-m-d', $timestamp);
        }

        return '';
    }

    /**
     * Importuj PDF z URL do WordPress Media Library
     */
    private function import_pdf_from_url($url) {
        require_once(ABSPATH . 'wp-admin/includes/media.php');
        require_once(ABSPATH . 'wp-admin/includes/file.php');
        require_once(ABSPATH . 'wp-admin/includes/image.php');

        $tmp = download_url($url);

        if (is_wp_error($tmp)) {
            return false;
        }

        $file_array = array(
            'name' => basename($url),
            'tmp_name' => $tmp
        );

        $id = media_handle_sideload($file_array, 0);

        if (is_wp_error($id)) {
            @unlink($tmp);
            return false;
        }

        return $id;
    }
}
