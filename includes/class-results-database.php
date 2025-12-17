<?php
/**
 * Klasa obsługująca bazę danych dla wyników biegów
 */

if (!defined('ABSPATH')) {
    exit;
}

class Race_Results_Database {

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
        $this->table_name = $wpdb->prefix . 'race_results';
    }

    /**
     * Tworzenie tabel w bazie danych
     */
    public static function create_tables() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'race_results';
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            race_date date NOT NULL,
            race_name varchar(255) NOT NULL,
            location varchar(255) NOT NULL,
            results_pdf longtext DEFAULT NULL,
            results_online_url varchar(500) DEFAULT '',
            sort_order int(11) DEFAULT 0,
            is_active tinyint(1) DEFAULT 1,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY race_date (race_date),
            KEY is_active (is_active),
            KEY sort_order (sort_order)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    /**
     * Pobieranie wszystkich aktywnych wyników
     */
    public function get_results($args = array()) {
        global $wpdb;

        $defaults = array(
            'search' => '',
            'orderby' => 'race_date',
            'order' => 'DESC',
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

        // Sortowanie: według sort_order, potem według race_date
        $order_clause = "sort_order ASC, race_date DESC";

        if ($args['orderby'] === 'race_name') {
            $order_clause = "race_name " . $args['order'];
        } elseif ($args['orderby'] === 'location') {
            $order_clause = "location " . $args['order'];
        } elseif ($args['orderby'] === 'race_date') {
            $order_clause = "race_date " . $args['order'];
        }

        $sql = "SELECT * FROM {$this->table_name} WHERE {$where_clause} ORDER BY {$order_clause}";

        return $wpdb->get_results($sql);
    }

    /**
     * Pobieranie pojedynczego wyniku
     */
    public function get_result($id) {
        global $wpdb;
        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$this->table_name} WHERE id = %d",
            $id
        ));
    }

    /**
     * Dodawanie wyniku
     */
    public function add_result($data) {
        global $wpdb;

        // Ustawienie sort_order na maksymalny + 1
        $max_order = $wpdb->get_var("SELECT MAX(sort_order) FROM {$this->table_name}");
        $data['sort_order'] = ($max_order !== null) ? $max_order + 1 : 0;

        // Konwersja tablicy results_pdf na JSON
        $results_pdf_json = '';
        if (isset($data['results_pdf']) && is_array($data['results_pdf'])) {
            $results_pdf_json = json_encode($data['results_pdf'], JSON_UNESCAPED_UNICODE);
        }

        $result = $wpdb->insert(
            $this->table_name,
            array(
                'race_date' => $data['race_date'],
                'race_name' => sanitize_text_field($data['race_name']),
                'location' => sanitize_text_field($data['location']),
                'results_pdf' => $results_pdf_json,
                'results_online_url' => esc_url_raw($data['results_online_url']),
                'sort_order' => $data['sort_order'],
                'is_active' => 1
            ),
            array('%s', '%s', '%s', '%s', '%s', '%d', '%d')
        );

        return $result ? $wpdb->insert_id : false;
    }

    /**
     * Aktualizacja wyniku
     */
    public function update_result($id, $data) {
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
        if (isset($data['results_pdf'])) {
            if (is_array($data['results_pdf'])) {
                $update_data['results_pdf'] = json_encode($data['results_pdf'], JSON_UNESCAPED_UNICODE);
            } else {
                $update_data['results_pdf'] = $data['results_pdf'];
            }
            $format[] = '%s';
        }
        if (isset($data['results_online_url'])) {
            $update_data['results_online_url'] = esc_url_raw($data['results_online_url']);
            $format[] = '%s';
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
     * Usuwanie wyniku (soft delete)
     */
    public function delete_result($id) {
        return $this->update_result($id, array('is_active' => 0));
    }

    /**
     * Fizyczne usuwanie wyniku
     */
    public function hard_delete_result($id) {
        global $wpdb;
        return $wpdb->delete(
            $this->table_name,
            array('id' => $id),
            array('%d')
        );
    }

    /**
     * Aktualizacja kolejności wyników
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
     * Statystyki
     */
    public function get_stats() {
        global $wpdb;

        $total = $wpdb->get_var("SELECT COUNT(*) FROM {$this->table_name} WHERE is_active = 1");
        $with_pdf = $wpdb->get_var("SELECT COUNT(*) FROM {$this->table_name} WHERE is_active = 1 AND results_pdf IS NOT NULL AND results_pdf != ''");
        $with_online = $wpdb->get_var("SELECT COUNT(*) FROM {$this->table_name} WHERE is_active = 1 AND results_online_url != ''");

        return array(
            'total' => intval($total),
            'with_pdf' => intval($with_pdf),
            'with_online' => intval($with_online)
        );
    }
}
