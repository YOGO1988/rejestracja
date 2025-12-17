<?php
/**
 * Klasa obsługująca frontend (wyświetlanie tabeli)
 */

if (!defined('ABSPATH')) {
    exit;
}

class Race_Registration_Frontend {

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

        add_shortcode('race_registration_table', array($this, 'render_table_shortcode'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_frontend_assets'));
    }

    /**
     * Ładowanie CSS i JS dla frontendu
     */
    public function enqueue_frontend_assets() {
        // CSS
        wp_enqueue_style(
            'race-reg-frontend-css',
            RACE_REG_PLUGIN_URL . 'public/css/frontend.css',
            array(),
            RACE_REG_VERSION
        );

        // JavaScript
        wp_enqueue_script(
            'race-reg-frontend-js',
            RACE_REG_PLUGIN_URL . 'public/js/frontend.js',
            array('jquery'),
            RACE_REG_VERSION,
            true
        );
    }

    /**
     * Shortcode wyświetlający tabelę zawodów
     * Użycie: [race_registration_table]
     */
    public function render_table_shortcode($atts) {
        $atts = shortcode_atts(array(
            'limit' => -1,
            'show_search' => 'yes',
            'show_sort' => 'yes'
        ), $atts);

        $races = $this->db->get_races(array('active_only' => true));

        if ($atts['limit'] > 0) {
            $races = array_slice($races, 0, intval($atts['limit']));
        }

        ob_start();
        ?>
        <div class="race-registration-table-wrap">
            <?php if ($atts['show_search'] === 'yes'): ?>
            <div class="race-table-controls">
                <div class="race-search-box">
                    <input type="text" id="race-search-input" class="race-search-input" placeholder="Szukaj zawodów po nazwie lub miejscowości...">
                </div>
                <?php if ($atts['show_sort'] === 'yes'): ?>
                <div class="race-sort-box">
                    <label for="race-sort-select">Sortuj:</label>
                    <select id="race-sort-select" class="race-sort-select">
                        <option value="date-asc">Data (najbliższe)</option>
                        <option value="date-desc">Data (najdalsze)</option>
                        <option value="name-asc">Nazwa (A-Z)</option>
                        <option value="name-desc">Nazwa (Z-A)</option>
                        <option value="location-asc">Miejscowość (A-Z)</option>
                        <option value="location-desc">Miejscowość (Z-A)</option>
                    </select>
                </div>
                <?php endif; ?>
            </div>
            <?php endif; ?>

            <?php if (empty($races)): ?>
                <div class="race-no-results">
                    <p>Brak aktualnych zawodów. Sprawdź ponownie wkrótce!</p>
                </div>
            <?php else: ?>
                <div class="race-table-responsive">
                    <table class="race-registration-table" id="race-registration-table">
                        <thead>
                            <tr>
                                <th class="race-col-date" data-sort="date">Data</th>
                                <th class="race-col-name" data-sort="name">Nazwa zawodów</th>
                                <th class="race-col-location" data-sort="location">Miejscowość</th>
                                <th class="race-col-distance">Dystans</th>
                                <th class="race-col-website">Strona zawodów</th>
                                <th class="race-col-registration">Zapisy</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($races as $race): ?>
                                <tr class="race-row <?php echo $race->is_pinned ? 'race-pinned' : ''; ?>"
                                    data-date="<?php echo esc_attr($race->race_date); ?>"
                                    data-name="<?php echo esc_attr(strtolower($race->race_name)); ?>"
                                    data-location="<?php echo esc_attr(strtolower($race->location)); ?>">
                                    <td class="race-col-date" data-label="Data">
                                        <span class="race-date"><?php echo esc_html(date('d.m.Y', strtotime($race->race_date))); ?></span>
                                        <?php if ($race->is_pinned): ?>
                                            <span class="race-featured-badge">Wyróżnione</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="race-col-name" data-label="Nazwa zawodów">
                                        <?php echo esc_html($race->race_name); ?>
                                    </td>
                                    <td class="race-col-location" data-label="Miejscowość">
                                        <?php echo esc_html($race->location); ?>
                                    </td>
                                    <td class="race-col-distance" data-label="Dystans">
                                        <?php echo esc_html($race->distance); ?>
                                    </td>
                                    <td class="race-col-website" data-label="Strona zawodów">
                                        <?php if (!empty($race->website_url)): ?>
                                            <a href="<?php echo esc_url($race->website_url); ?>"
                                               target="_blank"
                                               rel="noopener noreferrer"
                                               class="race-btn race-btn-website">
                                                Strona
                                            </a>
                                        <?php else: ?>
                                            <span class="race-na">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="race-col-registration" data-label="Zapisy">
                                        <?php if ($race->is_coming_soon): ?>
                                            <span class="race-coming-soon-badge">Wkrótce uruchomimy zapisy</span>
                                        <?php elseif ($race->is_limit_reached): ?>
                                            <span class="race-limit-badge">Limit osiągnięty</span>
                                        <?php elseif (!empty($race->registration_url)): ?>
                                            <a href="<?php echo esc_url($race->registration_url); ?>"
                                               target="_blank"
                                               rel="noopener noreferrer"
                                               class="race-btn race-btn-registration">
                                                Zapisy
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

                <div class="race-results-info">
                    <span class="race-count">Wyświetlono <strong><?php echo count($races); ?></strong></span>
                </div>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }
}
