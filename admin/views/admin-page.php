<?php
/**
 * Szablon strony administracyjnej
 */

if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="wrap race-registration-admin">
    <h1>Rejestracja na Biegi</h1>

    <div class="race-reg-stats">
        <div class="stat-box">
            <span class="stat-number"><?php echo esc_html($stats['total']); ?></span>
            <span class="stat-label">Aktywne zawody</span>
        </div>
        <div class="stat-box">
            <span class="stat-number"><?php echo esc_html($stats['pinned']); ?></span>
            <span class="stat-label">Przypięte</span>
        </div>
        <div class="stat-box">
            <span class="stat-number"><?php echo esc_html($stats['limit_reached']); ?></span>
            <span class="stat-label">Limit osiągnięty</span>
        </div>
    </div>

    <div class="race-reg-actions">
        <button type="button" class="button button-primary" id="add-race-btn">
            <span class="dashicons dashicons-plus-alt"></span> Dodaj zawody
        </button>
        <input type="text" id="search-races" class="race-search" placeholder="Szukaj po nazwie lub miejscowości...">
        <div class="shortcode-info">
            <strong>Shortcode:</strong> <code>[race_registration_table]</code>
        </div>
    </div>

    <!-- Formularz dodawania/edycji -->
    <div id="race-form-modal" class="race-modal" style="display: none;">
        <div class="race-modal-content">
            <span class="race-modal-close">&times;</span>
            <h2 id="form-title">Dodaj zawody</h2>

            <form id="race-form">
                <input type="hidden" id="race-id" name="race_id" value="">

                <div class="form-group">
                    <label for="race-date">Data zawodów *</label>
                    <input type="date" id="race-date" name="race_date" required>
                </div>

                <div class="form-group">
                    <label for="race-name">Nazwa zawodów *</label>
                    <input type="text" id="race-name" name="race_name" required>
                </div>

                <div class="form-group">
                    <label for="location">Miejscowość *</label>
                    <input type="text" id="location" name="location" required>
                </div>

                <div class="form-group">
                    <label for="distance">Dystans *</label>
                    <input type="text" id="distance" name="distance" placeholder="np. 5km, 10km, 21km" required>
                </div>

                <div class="form-group">
                    <label for="website-url">Strona zawodów</label>
                    <input type="url" id="website-url" name="website_url" placeholder="https://">
                </div>

                <div class="form-group">
                    <label for="registration-url">Link do zapisów</label>
                    <input type="url" id="registration-url" name="registration_url" placeholder="https://">
                </div>

                <div class="form-group checkbox-group">
                    <label>
                        <input type="checkbox" id="is-pinned" name="is_pinned" value="1">
                        Przypnij do góry (wyróżnione)
                    </label>
                </div>

                <div class="form-group checkbox-group">
                    <label>
                        <input type="checkbox" id="is-limit-reached" name="is_limit_reached" value="1">
                        Limit osiągnięty
                    </label>
                </div>

                <div class="form-group checkbox-group">
                    <label>
                        <input type="checkbox" id="is-coming-soon" name="is_coming_soon" value="1">
                        Zawody wkrótce (wkrótce uruchomimy zapisy)
                    </label>
                </div>

                <div class="form-actions">
                    <button type="submit" class="button button-primary">Zapisz</button>
                    <button type="button" class="button" id="cancel-form">Anuluj</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Tabela zawodów -->
    <div class="race-reg-table-wrap">
        <table class="wp-list-table widefat striped" id="races-table">
            <thead>
                <tr>
                    <th class="column-drag"></th>
                    <th class="column-date">Data</th>
                    <th class="column-name">Nazwa zawodów</th>
                    <th class="column-location">Miejscowość</th>
                    <th class="column-distance">Dystans</th>
                    <th class="column-website">Strona zawodów</th>
                    <th class="column-registration">Zapisy</th>
                    <th class="column-status">Status</th>
                    <th class="column-actions">Akcje</th>
                </tr>
            </thead>
            <tbody id="races-tbody">
                <?php if (empty($races)): ?>
                    <tr class="no-items">
                        <td colspan="9">Brak zawodów do wyświetlenia. Dodaj pierwsze zawody!</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($races as $race): ?>
                        <tr class="race-row <?php echo $race->is_pinned ? 'pinned-row' : ''; ?>" data-id="<?php echo esc_attr($race->id); ?>">
                            <td class="drag-handle">
                                <span class="dashicons dashicons-menu"></span>
                            </td>
                            <td class="column-date">
                                <?php echo esc_html(date('d.m.Y', strtotime($race->race_date))); ?>
                            </td>
                            <td class="column-name">
                                <?php echo esc_html($race->race_name); ?>
                                <?php if ($race->is_pinned): ?>
                                    <span class="dashicons dashicons-flag pinned-icon" title="Przypięte"></span>
                                <?php endif; ?>
                            </td>
                            <td class="column-location"><?php echo esc_html($race->location); ?></td>
                            <td class="column-distance"><?php echo esc_html($race->distance); ?></td>
                            <td class="column-website">
                                <?php if (!empty($race->website_url)): ?>
                                    <a href="<?php echo esc_url($race->website_url); ?>" target="_blank" class="button button-small">Strona</a>
                                <?php else: ?>
                                    <span class="no-link">-</span>
                                <?php endif; ?>
                            </td>
                            <td class="column-registration">
                                <?php if (!empty($race->registration_url)): ?>
                                    <a href="<?php echo esc_url($race->registration_url); ?>" target="_blank" class="button button-small button-primary">Zapisy</a>
                                <?php else: ?>
                                    <span class="no-link">-</span>
                                <?php endif; ?>
                            </td>
                            <td class="column-status">
                                <?php if ($race->is_limit_reached): ?>
                                    <span class="status-badge limit-reached">Limit osiągnięty</span>
                                <?php else: ?>
                                    <span class="status-badge active">Aktywne</span>
                                <?php endif; ?>
                            </td>
                            <td class="column-actions">
                                <button type="button" class="button button-small pin-race" data-id="<?php echo esc_attr($race->id); ?>" title="Przypnij/Odepnij">
                                    <span class="dashicons dashicons-flag"></span>
                                </button>
                                <button type="button" class="button button-small toggle-limit" data-id="<?php echo esc_attr($race->id); ?>" title="Przełącz limit">
                                    <span class="dashicons dashicons-warning"></span>
                                </button>
                                <button type="button" class="button button-small edit-race" data-id="<?php echo esc_attr($race->id); ?>" title="Edytuj">
                                    <span class="dashicons dashicons-edit"></span>
                                </button>
                                <button type="button" class="button button-small delete-race" data-id="<?php echo esc_attr($race->id); ?>" title="Usuń">
                                    <span class="dashicons dashicons-trash"></span>
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <div class="race-reg-footer">
        <p><strong>Instrukcje:</strong></p>
        <ul>
            <li>Użyj shortcode <code>[race_registration_table]</code> aby wyświetlić tabelę na stronie</li>
            <li>Przeciągnij wiersze aby zmienić kolejność zawodów</li>
            <li>Przypięte zawody zawsze wyświetlają się na górze</li>
            <li>Zawody są automatycznie ukrywane dzień po wydarzeniu</li>
        </ul>
    </div>
</div>
