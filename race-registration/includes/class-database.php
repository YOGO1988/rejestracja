<?php
/**
 * Klasa obsługująca bazę danych
 */

if (!defined('ABSPATH')) {
    exit;
}

class Race_Registration_Database {

    private static $instance = null;
    private $table_name;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        global $wpdb;
        $this->table_name = $wpdb->prefix . 'race_registrations';

        add_action('race_reg_cleanup_old_races', array($this, 'cleanup_old_races'));
    }

    /**
     * Tworzenie tabel w bazie danych
     */
    public static function create_tables() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'race_registrations';
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            race_date date NOT NULL,
            race_name varchar(255) NOT NULL,
            location varchar(255) NOT NULL,
            distance varchar(100) NOT NULL,
            website_url varchar(500) DEFAULT '',
            registration_url varchar(500) DEFAULT '',
            is_pinned tinyint(1) DEFAULT 0,
            is_limit_reached tinyint(1) DEFAULT 0,
            sort_order int(11) DEFAULT 0,
            is_active tinyint(1) DEFAULT 1,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY race_date (race_date),
            KEY is_pinned (is_pinned),
            KEY is_active (is_active),
            KEY sort_order (sort_order)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    /**
     * Pobieranie wszystkich aktywnych zawodów
     */
    public function get_races($args = array()) {
        global $wpdb;

        $defaults = array(
            'search' => '',
            'orderby' => 'race_date',
            'order' => 'ASC',
            'active_only' => true
        );

        $args = wp_parse_args($args, $defaults);

        $where = array();
        $where[] = "1=1";

        if ($args['active_only']) {
            $where[] = "is_active = 1";
        }

        if (!empty($args['search'])) {
            $search = $wpdb->esc_like($args['search']);
            $where[] = $wpdb->prepare(
                "(race_name LIKE %s OR location LIKE %s)",
                '%' . $search . '%',
                '%' . $search . '%'
            );
        }

        $where_clause = implode(' AND ', $where);

        // Sortowanie: najpierw przypięte, potem według sort_order, potem według race_date
        $order_clause = "is_pinned DESC, sort_order ASC, race_date ASC";

        if ($args['orderby'] === 'race_name') {
            $order_clause = "is_pinned DESC, race_name " . $args['order'];
        } elseif ($args['orderby'] === 'location') {
            $order_clause = "is_pinned DESC, location " . $args['order'];
        } elseif ($args['orderby'] === 'race_date') {
            $order_clause = "is_pinned DESC, race_date " . $args['order'];
        }

        $sql = "SELECT * FROM {$this->table_name} WHERE {$where_clause} ORDER BY {$order_clause}";

        return $wpdb->get_results($sql);
    }

    /**
     * Pobieranie pojedynczego zawodu
     */
    public function get_race($id) {
        global $wpdb;
        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$this->table_name} WHERE id = %d",
            $id
        ));
    }

    /**
     * Dodawanie zawodu
     */
    public function add_race($data) {
        global $wpdb;

        // Ustawienie sort_order na maksymalny + 1
        $max_order = $wpdb->get_var("SELECT MAX(sort_order) FROM {$this->table_name}");
        $data['sort_order'] = ($max_order !== null) ? $max_order + 1 : 0;

        $result = $wpdb->insert(
            $this->table_name,
            array(
                'race_date' => $data['race_date'],
                'race_name' => sanitize_text_field($data['race_name']),
                'location' => sanitize_text_field($data['location']),
                'distance' => sanitize_text_field($data['distance']),
                'website_url' => esc_url_raw($data['website_url']),
                'registration_url' => esc_url_raw($data['registration_url']),
                'is_pinned' => isset($data['is_pinned']) ? intval($data['is_pinned']) : 0,
                'is_limit_reached' => isset($data['is_limit_reached']) ? intval($data['is_limit_reached']) : 0,
                'sort_order' => $data['sort_order'],
                'is_active' => 1
            ),
            array('%s', '%s', '%s', '%s', '%s', '%s', '%d', '%d', '%d', '%d')
        );

        return $result ? $wpdb->insert_id : false;
    }

    /**
     * Aktualizacja zawodu
     */
    public function update_race($id, $data) {
        global $wpdb;

        $update_data = array();
        $format = array();

        if (isset($data['race_date'])) {
            $update_data['race_date'] = $data['race_date'];
            $format[] = '%s';
        }
        if (isset($data['race_name'])) {
            $update_data['race_name'] = sanitize_text_field($data['race_name']);
            $format[] = '%s';
        }
        if (isset($data['location'])) {
            $update_data['location'] = sanitize_text_field($data['location']);
            $format[] = '%s';
        }
        if (isset($data['distance'])) {
            $update_data['distance'] = sanitize_text_field($data['distance']);
            $format[] = '%s';
        }
        if (isset($data['website_url'])) {
            $update_data['website_url'] = esc_url_raw($data['website_url']);
            $format[] = '%s';
        }
        if (isset($data['registration_url'])) {
            $update_data['registration_url'] = esc_url_raw($data['registration_url']);
            $format[] = '%s';
        }
        if (isset($data['is_pinned'])) {
            $update_data['is_pinned'] = intval($data['is_pinned']);
            $format[] = '%d';
        }
        if (isset($data['is_limit_reached'])) {
            $update_data['is_limit_reached'] = intval($data['is_limit_reached']);
            $format[] = '%d';
        }
        if (isset($data['sort_order'])) {
            $update_data['sort_order'] = intval($data['sort_order']);
            $format[] = '%d';
        }
        if (isset($data['is_active'])) {
            $update_data['is_active'] = intval($data['is_active']);
            $format[] = '%d';
        }

        return $wpdb->update(
            $this->table_name,
            $update_data,
            array('id' => $id),
            $format,
            array('%d')
        );
    }

    /**
     * Usuwanie zawodu (soft delete)
     */
    public function delete_race($id) {
        return $this->update_race($id, array('is_active' => 0));
    }

    /**
     * Fizyczne usuwanie zawodu
     */
    public function hard_delete_race($id) {
        global $wpdb;
        return $wpdb->delete(
            $this->table_name,
            array('id' => $id),
            array('%d')
        );
    }

    /**
     * Aktualizacja kolejności zawodów
     */
    public function update_order($order_data) {
        global $wpdb;

        foreach ($order_data as $index => $id) {
            $wpdb->update(
                $this->table_name,
                array('sort_order' => $index),
                array('id' => intval($id)),
                array('%d'),
                array('%d')
            );
        }

        return true;
    }

    /**
     * Automatyczne czyszczenie starych zawodów (dzień po wydarzeniu)
     */
    public function cleanup_old_races() {
        global $wpdb;

        $yesterday = date('Y-m-d', strtotime('-1 day'));

        $wpdb->update(
            $this->table_name,
            array('is_active' => 0),
            array('race_date <' => $yesterday, 'is_pinned' => 0),
            array('%d'),
            array('%s', '%d')
        );
    }

    /**
     * Statystyki
     */
    public function get_stats() {
        global $wpdb;

        $total = $wpdb->get_var("SELECT COUNT(*) FROM {$this->table_name} WHERE is_active = 1");
        $pinned = $wpdb->get_var("SELECT COUNT(*) FROM {$this->table_name} WHERE is_active = 1 AND is_pinned = 1");
        $limit_reached = $wpdb->get_var("SELECT COUNT(*) FROM {$this->table_name} WHERE is_active = 1 AND is_limit_reached = 1");

        return array(
            'total' => intval($total),
            'pinned' => intval($pinned),
            'limit_reached' => intval($limit_reached)
        );
    }
}
