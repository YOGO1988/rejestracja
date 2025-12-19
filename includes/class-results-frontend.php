<?php
/**
 * Klasa obsługująca frontend (wyświetlanie tabeli wyników)
 */

if (!defined('ABSPATH')) {
    exit;
}

class Race_Results_Frontend {

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

        add_shortcode('race_results_table', array($this, 'render_table_shortcode'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_frontend_assets'));
        add_action('wp_head', array($this, 'add_viewport_meta'), 1);
    }

    /**
     * Dodaj viewport meta tag dla responsywności
     */
    public function add_viewport_meta() {
        echo '<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0">' . "\n";
    }

    /**
     * Ładowanie CSS i JS dla frontendu
     */
    public function enqueue_frontend_assets() {
        // CSS
        wp_enqueue_style(
            'race-results-frontend-css',
            RACE_RESULTS_PLUGIN_URL . 'public/css/frontend.css',
            array(),
            RACE_RESULTS_VERSION
        );

        // JavaScript
        wp_enqueue_script(
            'race-results-frontend-js',
            RACE_RESULTS_PLUGIN_URL . 'public/js/frontend.js',
            array('jquery'),
            RACE_RESULTS_VERSION,
            true
        );
    }

    /**
     * Shortcode wyświetlający tabelę wyników
     * Użycie: [race_results_table]
     */
    public function render_table_shortcode($atts) {
        $atts = shortcode_atts(array(
            'limit' => -1,
            'show_search' => 'yes',
            'show_sort' => 'yes'
        ), $atts);

        $results = $this->db->get_results(array('active_only' => true));

        if ($atts['limit'] > 0) {
            $results = array_slice($results, 0, intval($atts['limit']));
        }

        // Dekodowanie JSON dla wyników PDF
        foreach ($results as $result) {
            if (!empty($result->results_pdf)) {
                $result->results_pdf_array = json_decode($result->results_pdf, true);
            } else {
                $result->results_pdf_array = array();
            }
        }

        ob_start();
        ?>
        <div class="race-results-table-wrap">
            <?php if ($atts['show_search'] === 'yes'): ?>
            <div class="race-table-controls">
                <div class="race-search-box">
                    <input type="text" id="race-results-search-input" class="race-search-input" placeholder="Wyszukaj bieg po nazwie lub miejscowości...">
                </div>
                <?php if ($atts['show_sort'] === 'yes'): ?>
                <div class="race-sort-box">
                    <label for="race-results-sort-select">Sortuj:</label>
                    <select id="race-results-sort-select" class="race-sort-select">
                        <option value="date-desc">Data (najnowsze)</option>
                        <option value="date-asc">Data (najstarsze)</option>
                        <option value="name-asc">Nazwa (A-Z)</option>
                        <option value="name-desc">Nazwa (Z-A)</option>
                        <option value="location-asc">Miejscowość (A-Z)</option>
                        <option value="location-desc">Miejscowość (Z-A)</option>
                    </select>
                </div>
                <?php endif; ?>
            </div>
            <?php endif; ?>

            <?php if (empty($results)): ?>
                <div class="race-no-results">
                    <p>Brak wyników. Sprawdź ponownie wkrótce!</p>
                </div>
            <?php else: ?>
                <div class="race-table-responsive">
                    <table class="race-results-table" id="race-results-table">
                        <thead>
                            <tr>
                                <th class="race-col-date" data-sort="date">Data</th>
                                <th class="race-col-name" data-sort="name">Nazwa Biegu</th>
                                <th class="race-col-location" data-sort="location">Miejscowość</th>
                                <th class="race-col-pdf">Wyniki pdf</th>
                                <th class="race-col-online">Na żywo</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($results as $result): ?>
                                <tr class="race-row"
                                    data-date="<?php echo esc_attr($result->race_date); ?>"
                                    data-name="<?php echo esc_attr(strtolower($result->race_name)); ?>"
                                    data-location="<?php echo esc_attr(strtolower($result->location)); ?>">
                                    <td class="race-col-date" data-label="Data">
                                        <span class="race-date"><?php echo esc_html(date('d.m.Y', strtotime($result->race_date))); ?></span>
                                    </td>
                                    <td class="race-col-name" data-label="Nazwa Biegu">
                                        <?php echo esc_html($result->race_name); ?>
                                    </td>
                                    <td class="race-col-location" data-label="Miejscowość">
                                        <?php echo esc_html($result->location); ?>
                                    </td>
                                    <td class="race-col-pdf" data-label="Wyniki pdf">
                                        <?php if (!empty($result->results_pdf_array) && is_array($result->results_pdf_array)): ?>
                                            <div class="race-pdf-buttons">
                                                <?php foreach ($result->results_pdf_array as $pdf): ?>
                                                    <?php
                                                    // Obsługa dwóch formatów: url (z importu) lub file_id (z admina)
                                                    if (isset($pdf['url']) && !empty($pdf['url'])) {
                                                        $file_url = $pdf['url'];
                                                    } elseif (isset($pdf['file_id'])) {
                                                        $file_url = wp_get_attachment_url($pdf['file_id']);
                                                    } else {
                                                        $file_url = false;
                                                    }

                                                    if ($file_url):
                                                    ?>
                                                        <a href="<?php echo esc_url($file_url); ?>"
                                                           target="_blank"
                                                           rel="noopener noreferrer"
                                                           class="race-btn race-btn-pdf">
                                                            <?php echo esc_html($pdf['button_text']); ?>
                                                        </a>
                                                    <?php endif; ?>
                                                <?php endforeach; ?>
                                            </div>
                                        <?php else: ?>
                                            <span class="race-na">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="race-col-online" data-label="Na żywo">
                                        <?php if (!empty($result->results_online_url)): ?>
                                            <a href="<?php echo esc_url($result->results_online_url); ?>"
                                               target="_blank"
                                               rel="noopener noreferrer"
                                               class="race-btn race-btn-online">
                                                zobacz
                                            </a>
                                        <?php else: ?>
                                            <span class="race-na">-</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Paginacja -->
                <div class="race-pagination" id="race-pagination">
                    <button class="race-pagination-btn race-pagination-prev" id="race-pagination-prev" disabled>
                        ← Poprzednia
                    </button>
                    <span class="race-pagination-info">
                        Strona <span id="race-current-page">1</span> z <span id="race-total-pages">1</span>
                    </span>
                    <button class="race-pagination-btn race-pagination-next" id="race-pagination-next">
                        Następna →
                    </button>
                </div>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }
}
