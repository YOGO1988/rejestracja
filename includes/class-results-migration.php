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
        add_action('wp_ajax_race_results_migrate_acf', array($this, 'ajax_migrate_acf'));
        add_action('wp_ajax_race_results_preview_acf', array($this, 'ajax_preview_acf'));
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
                <p>Ta strona pomoże Ci przenieść dane z pól ACF do nowej struktury bazy danych.</p>
                <p><strong>Przed migracją:</strong></p>
                <ol>
                    <li>Podaj nazwę grupy ACF lub ID pola, z którego chcesz migrować dane</li>
                    <li>System wyświetli podgląd danych do migracji</li>
                    <li>Sprawdź, czy dane są poprawne</li>
                    <li>Kliknij "Migruj dane" aby przenieść dane do nowej tabeli</li>
                </ol>

                <h3>Format danych ACF</h3>
                <p>System automatycznie wykryje i przekonwertuje dane w następujących formatach:</p>
                <ul>
                    <li><strong>Data:</strong> Format daty (YYYY-MM-DD lub DD.MM.YYYY)</li>
                    <li><strong>Nazwa biegu:</strong> Tekst</li>
                    <li><strong>Miejscowość:</strong> Tekst</li>
                    <li><strong>Wyniki PDF:</strong> HTML z linkami do PDF (np. <code>&lt;a href="...pdf"&gt;Wyniki 10km&lt;/a&gt;</code>)</li>
                    <li><strong>Wyniki online:</strong> HTML z linkiem (np. <code>&lt;a href="..."&gt;zobacz&lt;/a&gt;</code>)</li>
                </ul>
            </div>

            <div class="migration-form">
                <h2>Konfiguracja migracji</h2>
                <form id="migration-config-form">
                    <table class="form-table">
                        <tr>
                            <th scope="row">
                                <label for="acf-group">Grupa ACF lub Post Type</label>
                            </th>
                            <td>
                                <input type="text" id="acf-group" name="acf_group" class="regular-text" placeholder="np. group_xxxxx lub post">
                                <p class="description">Nazwa grupy ACF lub post type, z którego pobrać dane</p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="field-date">Pole ACF: Data</label>
                            </th>
                            <td>
                                <input type="text" id="field-date" name="field_date" class="regular-text" placeholder="np. data_biegu">
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="field-name">Pole ACF: Nazwa biegu</label>
                            </th>
                            <td>
                                <input type="text" id="field-name" name="field_name" class="regular-text" placeholder="np. nazwa_biegu">
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="field-location">Pole ACF: Miejscowość</label>
                            </th>
                            <td>
                                <input type="text" id="field-location" name="field_location" class="regular-text" placeholder="np. miejscowosc">
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="field-pdf">Pole ACF: Wyniki PDF</label>
                            </th>
                            <td>
                                <input type="text" id="field-pdf" name="field_pdf" class="regular-text" placeholder="np. wyniki_pdf">
                                <p class="description">Pole zawierające HTML z linkami do PDF</p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="field-online">Pole ACF: Wyniki online</label>
                            </th>
                            <td>
                                <input type="text" id="field-online" name="field_online" class="regular-text" placeholder="np. wyniki_online">
                                <p class="description">Pole zawierające HTML z linkiem online</p>
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
                const data = {
                    action: 'race_results_preview_acf',
                    nonce: '<?php echo wp_create_nonce('race_results_migration_nonce'); ?>',
                    acf_group: $('#acf-group').val(),
                    field_date: $('#field-date').val(),
                    field_name: $('#field-name').val(),
                    field_location: $('#field-location').val(),
                    field_pdf: $('#field-pdf').val(),
                    field_online: $('#field-online').val()
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

                const data = {
                    action: 'race_results_migrate_acf',
                    nonce: '<?php echo wp_create_nonce('race_results_migration_nonce'); ?>',
                    acf_group: $('#acf-group').val(),
                    field_date: $('#field-date').val(),
                    field_name: $('#field-name').val(),
                    field_location: $('#field-location').val(),
                    field_pdf: $('#field-pdf').val(),
                    field_online: $('#field-online').val()
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
     * AJAX: Podgląd danych z ACF
     */
    public function ajax_preview_acf() {
        check_ajax_referer('race_results_migration_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Brak uprawnień'));
        }

        $acf_group = sanitize_text_field($_POST['acf_group']);
        $fields = array(
            'date' => sanitize_text_field($_POST['field_date']),
            'name' => sanitize_text_field($_POST['field_name']),
            'location' => sanitize_text_field($_POST['field_location']),
            'pdf' => sanitize_text_field($_POST['field_pdf']),
            'online' => sanitize_text_field($_POST['field_online'])
        );

        // Pobierz dane z ACF (przykładowo z postów)
        $args = array(
            'post_type' => $acf_group,
            'posts_per_page' => -1,
            'post_status' => 'publish'
        );

        $posts = get_posts($args);

        if (empty($posts)) {
            wp_send_json_error(array('message' => 'Nie znaleziono postów w grupie: ' . $acf_group));
        }

        $html = '<p>Znaleziono <strong>' . count($posts) . '</strong> wpisów do migracji:</p>';

        foreach ($posts as $post) {
            $date = get_field($fields['date'], $post->ID);
            $name = get_field($fields['name'], $post->ID);
            $location = get_field($fields['location'], $post->ID);
            $pdf = get_field($fields['pdf'], $post->ID);
            $online = get_field($fields['online'], $post->ID);

            $html .= '<div class="preview-item">';
            $html .= '<h4>' . esc_html($name) . '</h4>';
            $html .= '<p><strong>Data:</strong> ' . esc_html($date) . '</p>';
            $html .= '<p><strong>Miejscowość:</strong> ' . esc_html($location) . '</p>';
            $html .= '<p><strong>Wyniki PDF (HTML):</strong> ' . esc_html(substr($pdf, 0, 100)) . '...</p>';
            $html .= '<p><strong>Wyniki online (HTML):</strong> ' . esc_html(substr($online, 0, 100)) . '...</p>';
            $html .= '</div>';
        }

        wp_send_json_success(array('html' => $html));
    }

    /**
     * AJAX: Migracja danych z ACF
     */
    public function ajax_migrate_acf() {
        check_ajax_referer('race_results_migration_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Brak uprawnień'));
        }

        $acf_group = sanitize_text_field($_POST['acf_group']);
        $fields = array(
            'date' => sanitize_text_field($_POST['field_date']),
            'name' => sanitize_text_field($_POST['field_name']),
            'location' => sanitize_text_field($_POST['field_location']),
            'pdf' => sanitize_text_field($_POST['field_pdf']),
            'online' => sanitize_text_field($_POST['field_online'])
        );

        // Pobierz dane z ACF
        $args = array(
            'post_type' => $acf_group,
            'posts_per_page' => -1,
            'post_status' => 'publish'
        );

        $posts = get_posts($args);

        if (empty($posts)) {
            wp_send_json_error(array('message' => 'Nie znaleziono postów do migracji'));
        }

        $migrated = 0;
        $errors = 0;

        foreach ($posts as $post) {
            $date = get_field($fields['date'], $post->ID);
            $name = get_field($fields['name'], $post->ID);
            $location = get_field($fields['location'], $post->ID);
            $pdf_html = get_field($fields['pdf'], $post->ID);
            $online_html = get_field($fields['online'], $post->ID);

            // Parsuj HTML z linkami PDF
            $pdf_files = $this->parse_pdf_links($pdf_html);

            // Parsuj link online
            $online_url = $this->parse_online_link($online_html);

            // Konwersja daty na format YYYY-MM-DD
            $formatted_date = $this->format_date($date);

            if (empty($formatted_date) || empty($name) || empty($location)) {
                $errors++;
                continue;
            }

            $data = array(
                'race_date' => $formatted_date,
                'race_name' => $name,
                'location' => $location,
                'results_pdf' => $pdf_files,
                'results_online_url' => $online_url
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
     * Parsuj linki PDF z HTML
     */
    private function parse_pdf_links($html) {
        if (empty($html)) {
            return array();
        }

        $pdf_files = array();

        // Regex do znalezienia wszystkich linków do PDF
        preg_match_all('/<a[^>]+href=["\']([^"\']+\.pdf)["\'][^>]*>([^<]+)<\/a>/i', $html, $matches, PREG_SET_ORDER);

        foreach ($matches as $match) {
            $url = $match[1];
            $text = strip_tags($match[2]);

            // Sprawdź czy plik jest w WordPress Media Library
            $attachment_id = attachment_url_to_postid($url);

            if ($attachment_id) {
                $pdf_files[] = array(
                    'file_id' => $attachment_id,
                    'button_text' => $text
                );
            } else {
                // Jeśli plik nie jest w bibliotece, spróbuj go zaimportować
                $attachment_id = $this->import_pdf_from_url($url);
                if ($attachment_id) {
                    $pdf_files[] = array(
                        'file_id' => $attachment_id,
                        'button_text' => $text
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
