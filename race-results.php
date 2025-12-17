<?php
/**
 * Plugin Name: Wyniki Biegów
 * Plugin URI: https://github.com/YOGO1988/rejestracja
 * Description: Wtyczka do zarządzania wynikami biegów - wyświetla tabelę z wynikami w formacie PDF i online
 * Version: 1.0.0
 * Author: YOGO1988
 * Text Domain: race-results
 * Domain Path: /languages
 */

// Zabezpieczenie przed bezpośrednim dostępem
if (!defined('ABSPATH')) {
    exit;
}

// Definiowanie stałych
define('RACE_RESULTS_VERSION', '1.0.0');
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
    }

    /**
     * Aktywacja wtyczki
     */
    public function activate() {
        Race_Results_Database::create_tables();
        flush_rewrite_rules();
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
