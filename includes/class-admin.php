<?php
/**
 * Klasa obsługująca panel administracyjny
 */

if (!defined('ABSPATH')) {
    exit;
}

class Race_Registration_Admin {

    private static $instance = null;
    private $db;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        $this->db = Race_Registration_Database::get_instance();

        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));
        add_action('wp_ajax_race_reg_add_race', array($this, 'ajax_add_race'));
        add_action('wp_ajax_race_reg_update_race', array($this, 'ajax_update_race'));
        add_action('wp_ajax_race_reg_delete_race', array($this, 'ajax_delete_race'));
        add_action('wp_ajax_race_reg_update_order', array($this, 'ajax_update_order'));
        add_action('wp_ajax_race_reg_toggle_pin', array($this, 'ajax_toggle_pin'));
        add_action('wp_ajax_race_reg_toggle_limit', array($this, 'ajax_toggle_limit'));
        add_action('wp_ajax_race_reg_get_race', array($this, 'ajax_get_race'));
    }

    /**
     * Dodawanie menu w panelu administracyjnym
     */
    public function add_admin_menu() {
        add_menu_page(
            'Rejestracja na Biegi',
            'Biegi',
            'manage_options',
            'race-registration',
            array($this, 'render_admin_page'),
            'dashicons-flag',
            30
        );
    }

    /**
     * Ładowanie CSS i JS dla panelu administracyjnego
     */
    public function enqueue_admin_assets($hook) {
        if ('toplevel_page_race-registration' !== $hook) {
            return;
        }

        // CSS
        wp_enqueue_style(
            'race-reg-admin-css',
            RACE_REG_PLUGIN_URL . 'admin/css/admin.css',
            array(),
            RACE_REG_VERSION
        );

        // jQuery UI dla sortowania
        wp_enqueue_script('jquery-ui-sortable');

        // JavaScript
        wp_enqueue_script(
            'race-reg-admin-js',
            RACE_REG_PLUGIN_URL . 'admin/js/admin.js',
            array('jquery', 'jquery-ui-sortable'),
            RACE_REG_VERSION,
            true
        );

        // Przekazanie zmiennych do JavaScript
        wp_localize_script('race-reg-admin-js', 'raceRegAdmin', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('race_reg_admin_nonce'),
            'strings' => array(
                'confirmDelete' => 'Czy na pewno chcesz usunąć ten zawód?',
                'error' => 'Wystąpił błąd. Spróbuj ponownie.',
                'success' => 'Operacja zakończona sukcesem.',
                'fillRequired' => 'Wypełnij wszystkie wymagane pola.'
            )
        ));
    }

    /**
     * Renderowanie strony administracyjnej
     */
    public function render_admin_page() {
        $races = $this->db->get_races(array('active_only' => true));
        $stats = $this->db->get_stats();

        include RACE_REG_PLUGIN_DIR . 'admin/views/admin-page.php';
    }

    /**
     * AJAX: Dodawanie zawodu
     */
    public function ajax_add_race() {
        check_ajax_referer('race_reg_admin_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Brak uprawnień'));
        }

        $data = array(
            'race_date' => sanitize_text_field($_POST['race_date']),
            'race_name' => sanitize_text_field($_POST['race_name']),
            'location' => sanitize_text_field($_POST['location']),
            'distance' => sanitize_text_field($_POST['distance']),
            'website_url' => esc_url_raw($_POST['website_url']),
            'registration_url' => esc_url_raw($_POST['registration_url']),
            'is_pinned' => isset($_POST['is_pinned']) ? 1 : 0,
            'is_limit_reached' => isset($_POST['is_limit_reached']) ? 1 : 0,
            'is_coming_soon' => isset($_POST['is_coming_soon']) ? 1 : 0
        );

        $result = $this->db->add_race($data);

        if ($result) {
            wp_send_json_success(array(
                'message' => 'Zawód został dodany',
                'id' => $result
            ));
        } else {
            wp_send_json_error(array('message' => 'Nie udało się dodać zawodu'));
        }
    }

    /**
     * AJAX: Aktualizacja zawodu
     */
    public function ajax_update_race() {
        check_ajax_referer('race_reg_admin_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Brak uprawnień'));
        }

        $id = intval($_POST['id']);

        error_log('=== UPDATE RACE START ===');
        error_log('Race ID: ' . $id);

        $data = array(
            'race_date' => sanitize_text_field($_POST['race_date']),
            'race_name' => sanitize_text_field($_POST['race_name']),
            'location' => sanitize_text_field($_POST['location']),
            'distance' => sanitize_text_field($_POST['distance']),
            'website_url' => esc_url_raw($_POST['website_url']),
            'registration_url' => esc_url_raw($_POST['registration_url']),
            'is_pinned' => isset($_POST['is_pinned']) ? 1 : 0,
            'is_limit_reached' => isset($_POST['is_limit_reached']) ? 1 : 0,
            'is_coming_soon' => isset($_POST['is_coming_soon']) ? 1 : 0
        );

        error_log('Data: ' . print_r($data, true));

        $result = $this->db->update_race($id, $data);

        global $wpdb;
        error_log('Update result: ' . var_export($result, true));
        error_log('WPDB last error: ' . $wpdb->last_error);
        error_log('=== UPDATE RACE END ===');

        if ($result !== false) {
            wp_send_json_success(array('message' => 'Zawód został zaktualizowany'));
        } else {
            wp_send_json_error(array('message' => 'Nie udało się zaktualizować zawodu. Błąd: ' . $wpdb->last_error));
        }
    }

    /**
     * AJAX: Usuwanie zawodu
     */
    public function ajax_delete_race() {
        check_ajax_referer('race_reg_admin_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Brak uprawnień'));
        }

        $id = intval($_POST['id']);
        $result = $this->db->delete_race($id);

        if ($result !== false) {
            wp_send_json_success(array('message' => 'Zawód został usunięty'));
        } else {
            wp_send_json_error(array('message' => 'Nie udało się usunąć zawodu'));
        }
    }

    /**
     * AJAX: Aktualizacja kolejności zawodów
     */
    public function ajax_update_order() {
        check_ajax_referer('race_reg_admin_nonce', 'nonce');

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
     * AJAX: Przełączanie przypięcia
     */
    public function ajax_toggle_pin() {
        check_ajax_referer('race_reg_admin_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Brak uprawnień'));
        }

        $id = intval($_POST['id']);
        $race = $this->db->get_race($id);

        if (!$race) {
            wp_send_json_error(array('message' => 'Nie znaleziono zawodu'));
        }

        $new_pinned = $race->is_pinned ? 0 : 1;
        $result = $this->db->update_race($id, array('is_pinned' => $new_pinned));

        if ($result !== false) {
            wp_send_json_success(array(
                'message' => 'Status przypięcia został zmieniony',
                'is_pinned' => $new_pinned
            ));
        } else {
            wp_send_json_error(array('message' => 'Nie udało się zmienić statusu'));
        }
    }

    /**
     * AJAX: Przełączanie limitu
     */
    public function ajax_toggle_limit() {
        check_ajax_referer('race_reg_admin_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Brak uprawnień'));
        }

        $id = intval($_POST['id']);
        $race = $this->db->get_race($id);

        if (!$race) {
            wp_send_json_error(array('message' => 'Nie znaleziono zawodu'));
        }

        $new_limit = $race->is_limit_reached ? 0 : 1;
        $result = $this->db->update_race($id, array('is_limit_reached' => $new_limit));

        if ($result !== false) {
            wp_send_json_success(array(
                'message' => 'Status limitu został zmieniony',
                'is_limit_reached' => $new_limit
            ));
        } else {
            wp_send_json_error(array('message' => 'Nie udało się zmienić statusu'));
        }
    }

    /**
     * AJAX: Pobieranie danych zawodu
     */
    public function ajax_get_race() {
        check_ajax_referer('race_reg_admin_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Brak uprawnień'));
        }

        $id = intval($_POST['id']);
        $race = $this->db->get_race($id);

        if ($race) {
            wp_send_json_success(array('race' => $race));
        } else {
            wp_send_json_error(array('message' => 'Nie znaleziono zawodu'));
        }
    }
}
