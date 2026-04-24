<?php
/**
 * Plugin Name: Rejestracja na Biegi
 * Plugin URI: https://github.com/YOGO1988/rejestracja
 * Description: Wtyczka do zarządzania rejestracją na biegi - wyświetla tabelę z linkami do rejestracji
 * Version: 2.0.0
 * Author: YOGO1988
 * Text Domain: race-registration
 * Domain Path: /languages
 */

// Zabezpieczenie przed bezpośrednim dostępem
if (!defined('ABSPATH')) {
    exit;
}

// Zabezpieczenie przed podwójnym załadowaniem
if (defined('RACE_REG_VERSION')) {
    return;
}

// Definiowanie stałych
define('RACE_REG_VERSION', '2.0.0');
define('RACE_REG_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('RACE_REG_PLUGIN_URL', plugin_dir_url(__FILE__));
define('RACE_REG_PLUGIN_FILE', __FILE__);

/**
 * Główna klasa wtyczki
 */
class Race_Registration {

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
        require_once RACE_REG_PLUGIN_DIR . 'includes/class-database.php';
        require_once RACE_REG_PLUGIN_DIR . 'includes/class-admin.php';
        require_once RACE_REG_PLUGIN_DIR . 'includes/class-frontend.php';
    }

    /**
     * Inicjalizacja hooków WordPress
     */
    private function init_hooks() {
        register_activation_hook(RACE_REG_PLUGIN_FILE, array($this, 'activate'));
        register_deactivation_hook(RACE_REG_PLUGIN_FILE, array($this, 'deactivate'));

        add_action('plugins_loaded', array($this, 'init'));
    }

    /**
     * Inicjalizacja wtyczki
     */
    public function init() {
        // Inicjalizacja komponentów
        Race_Registration_Database::get_instance();
        Race_Registration_Admin::get_instance();
        Race_Registration_Frontend::get_instance();
    }

    /**
     * Aktywacja wtyczki
     */
    public function activate() {
        Race_Registration_Database::create_tables();

        // Harmonogram automatycznego usuwania starych zawodów
        if (!wp_next_scheduled('race_reg_cleanup_old_races')) {
            wp_schedule_event(time(), 'daily', 'race_reg_cleanup_old_races');
        }

        flush_rewrite_rules();
    }

    /**
     * Deaktywacja wtyczki
     */
    public function deactivate() {
        wp_clear_scheduled_hook('race_reg_cleanup_old_races');
        flush_rewrite_rules();
    }
}

// Uruchomienie wtyczki
function race_registration_init() {
    return Race_Registration::get_instance();
}

race_registration_init();
