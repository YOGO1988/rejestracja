<?php
/**
 * Plugin Name: Wyniki Biegów
 * Plugin URI: https://github.com/YOGO1988/rejestracja
 * Description: Wtyczka do zarządzania wynikami biegów - wyświetla tabelę z wynikami w formacie PDF i online
 * Version: 2.0.3
 * Author: YO&GO Events
 * Text Domain: race-results
 * Domain Path: /languages
 */

// Zabezpieczenie przed bezpośrednim dostępem
if (!defined('ABSPATH')) {
    exit;
}

// Definiowanie stałych
define('RACE_RESULTS_VERSION', '2.0.3');
define('RACE_RESULTS_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('RACE_RESULTS_PLUGIN_URL', plugin_dir_url(__FILE__));
define('RACE_RESULTS_PLUGIN_FILE', __FILE__);

/**
 * Główna klasa wtyczki
 */
class Race_Results {

    private static $instance = null;

    /**
     * Singleton instance
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Konstruktor
     */
    private function __construct() {
        $this->load_dependencies();
        $this->init_hooks();
    }

    /**
     * Ładowanie zależności
     */
    private function load_dependencies() {
        require_once RACE_RESULTS_PLUGIN_DIR . 'includes/class-results-database.php';
        require_once RACE_RESULTS_PLUGIN_DIR . 'includes/class-results-admin.php';
        require_once RACE_RESULTS_PLUGIN_DIR . 'includes/class-results-frontend.php';
        require_once RACE_RESULTS_PLUGIN_DIR . 'includes/class-results-migration.php';
        require_once RACE_RESULTS_PLUGIN_DIR . 'includes/class-results-import-page.php';
    }

    /**
     * Inicjalizacja hooków WordPress
     */
    private function init_hooks() {
        register_activation_hook(RACE_RESULTS_PLUGIN_FILE, array($this, 'activate'));
        register_deactivation_hook(RACE_RESULTS_PLUGIN_FILE, array($this, 'deactivate'));

        add_action('plugins_loaded', array($this, 'init'));
    }

    /**
     * Inicjalizacja wtyczki
     */
    public function init() {
        // Inicjalizacja komponentów
        Race_Results_Database::get_instance();
        Race_Results_Admin::get_instance();
        Race_Results_Frontend::get_instance();
        Race_Results_Migration::get_instance();
        Race_Results_Import_Page::get_instance();
    }

    /**
     * Aktywacja wtyczki
     */
    public function activate() {
        Race_Results_Database::create_tables();

        // Automatyczny import danych z pliku 123.txt (jeśli istnieje i tabela jest pusta)
        $this->auto_import_data();

        flush_rewrite_rules();
    }

    /**
     * Automatyczny import danych z pliku 123.txt
     */
    private function auto_import_data() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'race_results';

        // Sprawdź czy tabela jest pusta
        $count = $wpdb->get_var("SELECT COUNT(*) FROM $table_name");

        if ($count > 0) {
            // Dane już istnieją, pomiń import
            return;
        }

        // Załaduj dane z osadzonego pliku PHP
        $data_file = RACE_RESULTS_PLUGIN_DIR . 'includes/race-data.php';
        if (!file_exists($data_file)) {
            return; // Brak pliku z danymi
        }

        $table_data = include $data_file;

        if (!is_array($table_data) || !isset($table_data['b']) || !is_array($table_data['b'])) {
            return; // Nieprawidłowe dane
        }

        // Importuj dane
        foreach ($table_data['b'] as $row) {
            if (!is_array($row) || count($row) < 3) {
                continue;
            }

            $date_cell = isset($row[0]['c']) ? $row[0]['c'] : '';
            $name_cell = isset($row[1]['c']) ? $row[1]['c'] : '';
            $location_cell = isset($row[2]['c']) ? $row[2]['c'] : '';
            $pdf_cell = isset($row[3]['c']) ? $row[3]['c'] : '';
            $online_cell = isset($row[4]['c']) ? $row[4]['c'] : '';

            $date = $this->format_date_for_import(trim(str_replace('r', '', $date_cell)));
            $name = trim($name_cell);
            $location = trim($location_cell);

            if (empty($date) || empty($name) || empty($location)) {
                continue;
            }

            $pdf_files = $this->parse_links_for_import($pdf_cell);
            $online_url = $this->extract_url_for_import($online_cell);

            $wpdb->insert(
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
        }
    }

    /**
     * Formatuj datę dla importu
     */
    private function format_date_for_import($date) {
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

    /**
     * Parsuj linki dla importu
     */
    private function parse_links_for_import($html) {
        if (empty($html)) {
            return array();
        }

        $links = array();
        preg_match_all('/<a[^>]+href=[\'"]([^\'"]+)[\'"][^>]*>([^<]+)<\/a>/i', $html, $matches, PREG_SET_ORDER);

        foreach ($matches as $match) {
            $url = trim($match[1]);
            $text = trim(strip_tags($match[2]));

            // Akceptuj wszystkie linki z PDF
            if (stripos($url, '.pdf') !== false) {
                $links[] = array('url' => $url, 'button_text' => $text);
            }
        }

        return $links;
    }

    /**
     * Wyciągnij URL dla importu
     */
    private function extract_url_for_import($html) {
        if (empty($html)) {
            return '';
        }

        preg_match('/<a[^>]+href=[\'"]([^\'"]+)[\'"][^>]*>/i', $html, $matches);
        return isset($matches[1]) ? trim($matches[1]) : '';
    }

    /**
     * Deaktywacja wtyczki
     */
    public function deactivate() {
        flush_rewrite_rules();
    }
}

// Uruchomienie wtyczki
function race_results_init() {
    return Race_Results::get_instance();
}

race_results_init();
