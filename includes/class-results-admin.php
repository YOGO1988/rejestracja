<?php
/**
 * Klasa obsługująca panel administracyjny dla wyników biegów
 */

if (!defined('ABSPATH')) {
    exit;
}

class Race_Results_Admin {

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

        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));
        add_action('wp_ajax_race_results_add', array($this, 'ajax_add_result'));
        add_action('wp_ajax_race_results_update', array($this, 'ajax_update_result'));
        add_action('wp_ajax_race_results_delete', array($this, 'ajax_delete_result'));
        add_action('wp_ajax_race_results_update_order', array($this, 'ajax_update_order'));
        add_action('wp_ajax_race_results_get', array($this, 'ajax_get_result'));
        add_action('wp_ajax_race_results_upload_pdf', array($this, 'ajax_upload_pdf'));
        add_action('wp_ajax_race_results_get_attachment', array($this, 'ajax_get_attachment'));
    }

    /**
     * Dodawanie menu w panelu administracyjnym
     */
    public function add_admin_menu() {
        add_menu_page(
            'Wyniki Biegów',
            'Wyniki',
            'manage_options',
            'race-results',
            array($this, 'render_admin_page'),
            'dashicons-awards',
            31
        );
    }

    /**
     * Ładowanie CSS i JS dla panelu administracyjnego
     */
    public function enqueue_admin_assets($hook) {
        if ('toplevel_page_race-results' !== $hook) {
            return;
        }

        // WordPress Media Library
        wp_enqueue_media();

        // CSS
        wp_enqueue_style(
            'race-results-admin-css',
            RACE_RESULTS_PLUGIN_URL . 'admin/css/admin.css',
            array(),
            RACE_RESULTS_VERSION
        );

        // jQuery UI dla sortowania
        wp_enqueue_script('jquery-ui-sortable');

        // JavaScript
        wp_enqueue_script(
            'race-results-admin-js',
            RACE_RESULTS_PLUGIN_URL . 'admin/js/admin.js',
            array('jquery', 'jquery-ui-sortable'),
            RACE_RESULTS_VERSION,
            true
        );

        // Przekazanie zmiennych do JavaScript
        wp_localize_script('race-results-admin-js', 'raceResultsAdmin', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('race_results_admin_nonce'),
            'strings' => array(
                'confirmDelete' => 'Czy na pewno chcesz usunąć ten wynik?',
                'error' => 'Wystąpił błąd. Spróbuj ponownie.',
                'success' => 'Operacja zakończona sukcesem.',
                'fillRequired' => 'Wypełnij wszystkie wymagane pola.',
                'addPdf' => 'Dodaj plik PDF',
                'selectPdf' => 'Wybierz plik PDF',
                'uploadPdf' => 'Upload pliku PDF'
            )
        ));
    }

    /**
     * Renderowanie strony administracyjnej
     */
    public function render_admin_page() {
        $results = $this->db->get_results(array('active_only' => true));
        $stats = $this->db->get_stats();

        // Dekodowanie JSON dla wyników PDF
        foreach ($results as $result) {
            if (!empty($result->results_pdf)) {
                $result->results_pdf_array = json_decode($result->results_pdf, true);
            } else {
                $result->results_pdf_array = array();
            }
        }

        include RACE_RESULTS_PLUGIN_DIR . 'admin/views/admin-page.php';
    }

    /**
     * AJAX: Upload pliku PDF
     */
    public function ajax_upload_pdf() {
        check_ajax_referer('race_results_admin_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Brak uprawnień'));
        }

        if (!function_exists('wp_handle_upload')) {
            require_once(ABSPATH . 'wp-admin/includes/file.php');
        }

        $uploadedfile = $_FILES['file'];
        $upload_overrides = array('test_form' => false);

        $movefile = wp_handle_upload($uploadedfile, $upload_overrides);

        if ($movefile && !isset($movefile['error'])) {
            // Dodanie do biblioteki mediów
            $wp_filetype = wp_check_filetype($movefile['file'], null);
            $attachment = array(
                'post_mime_type' => $wp_filetype['type'],
                'post_title' => sanitize_file_name($uploadedfile['name']),
                'post_content' => '',
                'post_status' => 'inherit'
            );

            $attach_id = wp_insert_attachment($attachment, $movefile['file']);
            require_once(ABSPATH . 'wp-admin/includes/image.php');
            $attach_data = wp_generate_attachment_metadata($attach_id, $movefile['file']);
            wp_update_attachment_metadata($attach_id, $attach_data);

            wp_send_json_success(array(
                'id' => $attach_id,
                'url' => $movefile['url'],
                'filename' => basename($movefile['file'])
            ));
        } else {
            wp_send_json_error(array('message' => $movefile['error']));
        }
    }

    /**
     * AJAX: Dodawanie wyniku
     */
    public function ajax_add_result() {
        check_ajax_referer('race_results_admin_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Brak uprawnień'));
        }

        // Dekodowanie results_pdf z JSON
        $results_pdf = array();
        if (!empty($_POST['results_pdf'])) {
            $results_pdf = json_decode(stripslashes($_POST['results_pdf']), true);
        }

        $data = array(
            'race_date' => sanitize_text_field($_POST['race_date']),
            'race_name' => sanitize_text_field($_POST['race_name']),
            'location' => sanitize_text_field($_POST['location']),
            'results_pdf' => $results_pdf,
            'results_online_url' => esc_url_raw($_POST['results_online_url'])
        );

        $result = $this->db->add_result($data);

        if ($result) {
            wp_send_json_success(array(
                'message' => 'Wynik został dodany',
                'id' => $result
            ));
        } else {
            wp_send_json_error(array('message' => 'Nie udało się dodać wyniku'));
        }
    }

    /**
     * AJAX: Aktualizacja wyniku
     */
    public function ajax_update_result() {
        check_ajax_referer('race_results_admin_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Brak uprawnień'));
        }

        $id = intval($_POST['id']);

        // Dekodowanie results_pdf z JSON
        $results_pdf = array();
        if (!empty($_POST['results_pdf'])) {
            $results_pdf = json_decode(stripslashes($_POST['results_pdf']), true);
        }

        $data = array(
            'race_date' => sanitize_text_field($_POST['race_date']),
            'race_name' => sanitize_text_field($_POST['race_name']),
            'location' => sanitize_text_field($_POST['location']),
            'results_pdf' => $results_pdf,
            'results_online_url' => esc_url_raw($_POST['results_online_url'])
        );

        $result = $this->db->update_result($id, $data);

        if ($result !== false) {
            wp_send_json_success(array('message' => 'Wynik został zaktualizowany'));
        } else {
            global $wpdb;
            wp_send_json_error(array('message' => 'Nie udało się zaktualizować wyniku. Błąd: ' . $wpdb->last_error));
        }
    }

    /**
     * AJAX: Usuwanie wyniku
     */
    public function ajax_delete_result() {
        check_ajax_referer('race_results_admin_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Brak uprawnień'));
        }

        $id = intval($_POST['id']);
        $result = $this->db->delete_result($id);

        if ($result !== false) {
            wp_send_json_success(array('message' => 'Wynik został usunięty'));
        } else {
            wp_send_json_error(array('message' => 'Nie udało się usunąć wyniku'));
        }
    }

    /**
     * AJAX: Aktualizacja kolejności wyników
     */
    public function ajax_update_order() {
        check_ajax_referer('race_results_admin_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Brak uprawnień'));
        }

        $order = isset($_POST['order']) ? $_POST['order'] : array();

        if (empty($order) || !is_array($order)) {
            wp_send_json_error(array('message' => 'Nieprawidłowe dane'));
        }

        $result = $this->db->update_order($order);

        if ($result) {
            wp_send_json_success(array('message' => 'Kolejność została zaktualizowana'));
        } else {
            wp_send_json_error(array('message' => 'Nie udało się zaktualizować kolejności'));
        }
    }

    /**
     * AJAX: Pobieranie danych wyniku
     */
    public function ajax_get_result() {
        check_ajax_referer('race_results_admin_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Brak uprawnień'));
        }

        $id = intval($_POST['id']);
        $result = $this->db->get_result($id);

        if ($result) {
            // Dekodowanie JSON dla PDF
            if (!empty($result->results_pdf)) {
                $result->results_pdf_array = json_decode($result->results_pdf, true);
            } else {
                $result->results_pdf_array = array();
            }

            wp_send_json_success(array('result' => $result));
        } else {
            wp_send_json_error(array('message' => 'Nie znaleziono wyniku'));
        }
    }

    /**
     * AJAX: Pobieranie informacji o załączniku
     */
    public function ajax_get_attachment() {
        check_ajax_referer('race_results_admin_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Brak uprawnień'));
        }

        $attachment_id = intval($_POST['attachment_id']);
        $filename = basename(get_attached_file($attachment_id));

        if ($filename) {
            wp_send_json_success(array('filename' => $filename));
        } else {
            wp_send_json_error(array('message' => 'Nie znaleziono pliku'));
        }
    }
}
