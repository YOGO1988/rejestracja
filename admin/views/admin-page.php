<?php
/**
 * Szablon strony administracyjnej dla wyników biegów
 */

if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="wrap race-results-admin">
    <h1>Wyniki Biegów</h1>

    <div class="race-results-stats">
        <div class="stat-box">
            <span class="stat-number"><?php echo esc_html($stats['total']); ?></span>
            <span class="stat-label">Wszystkie wyniki</span>
        </div>
        <div class="stat-box">
            <span class="stat-number"><?php echo esc_html($stats['with_pdf']); ?></span>
            <span class="stat-label">Z wynikami PDF</span>
        </div>
        <div class="stat-box">
            <span class="stat-number"><?php echo esc_html($stats['with_online']); ?></span>
            <span class="stat-label">Z wynikami online</span>
        </div>
    </div>

    <div class="race-results-actions">
        <button type="button" class="button button-primary" id="add-result-btn">
            <span class="dashicons dashicons-plus-alt"></span> Dodaj wyniki
        </button>
        <input type="text" id="search-results" class="race-search" placeholder="Szukaj po nazwie lub miejscowości...">
        <div class="shortcode-info">
            <strong>Shortcode:</strong> <code>[race_results_table]</code>
        </div>
    </div>

    <!-- Formularz dodawania/edycji -->
    <div id="result-form-modal" class="race-modal" style="display: none;">
        <div class="race-modal-content">
            <span class="race-modal-close">&times;</span>
            <h2 id="form-title">Dodaj wyniki</h2>

            <form id="result-form">
                <input type="hidden" id="result-id" name="result_id" value="">

                <div class="form-group">
                    <label for="race-date">Data zawodów *</label>
                    <input type="date" id="race-date" name="race_date" required>
                </div>

                <div class="form-group">
                    <label for="race-name">Nazwa biegu *</label>
                    <input type="text" id="race-name" name="race_name" required>
                </div>

                <div class="form-group">
                    <label for="location">Miejscowość *</label>
                    <input type="text" id="location" name="location" required>
                </div>

                <div class="form-group">
                    <label>Wyniki PDF</label>
                    <div id="pdf-files-container">
                        <!-- Pliki PDF będą dodawane tutaj dynamicznie -->
                    </div>
                    <button type="button" class="button" id="add-pdf-btn">
                        <span class="dashicons dashicons-plus-alt"></span> Dodaj plik PDF
                    </button>
                </div>

                <div class="form-group">
                    <label for="results-online-url">Link do wyników online</label>
                    <input type="url" id="results-online-url" name="results_online_url" placeholder="https://">
                    <p class="description">Link do wyników online (np. Athlinks, Datasport, itp.)</p>
                </div>

                <div class="form-actions">
                    <button type="submit" class="button button-primary">Zapisz</button>
                    <button type="button" class="button" id="cancel-form">Anuluj</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Tabela wyników -->
    <div class="race-results-table-wrap">
        <table class="wp-list-table widefat striped" id="results-table">
            <thead>
                <tr>
                    <th class="column-drag"></th>
                    <th class="column-date">Data</th>
                    <th class="column-name">Nazwa biegu</th>
                    <th class="column-location">Miejscowość</th>
                    <th class="column-pdf">Wyniki PDF</th>
                    <th class="column-online">Wyniki online</th>
                    <th class="column-actions">Akcje</th>
                </tr>
            </thead>
            <tbody id="results-tbody">
                <?php if (empty($results)): ?>
                    <tr class="no-items">
                        <td colspan="7">Brak wyników do wyświetlenia. Dodaj pierwsze wyniki!</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($results as $result): ?>
                        <tr class="result-row" data-id="<?php echo esc_attr($result->id); ?>">
                            <td class="drag-handle">
                                <span class="dashicons dashicons-menu"></span>
                            </td>
                            <td class="column-date">
                                <?php echo esc_html(date('d.m.Y', strtotime($result->race_date))); ?>
                            </td>
                            <td class="column-name">
                                <?php echo esc_html($result->race_name); ?>
                            </td>
                            <td class="column-location"><?php echo esc_html($result->location); ?></td>
                            <td class="column-pdf">
                                <?php if (!empty($result->results_pdf_array) && is_array($result->results_pdf_array)): ?>
                                    <div class="pdf-list">
                                        <?php foreach ($result->results_pdf_array as $pdf): ?>
                                            <span class="pdf-item"><?php echo esc_html($pdf['button_text']); ?></span>
                                        <?php endforeach; ?>
                                    </div>
                                <?php else: ?>
                                    <span class="no-link">-</span>
                                <?php endif; ?>
                            </td>
                            <td class="column-online">
                                <?php if (!empty($result->results_online_url)): ?>
                                    <a href="<?php echo esc_url($result->results_online_url); ?>" target="_blank" class="button button-small">Zobacz</a>
                                <?php else: ?>
                                    <span class="no-link">-</span>
                                <?php endif; ?>
                            </td>
                            <td class="column-actions">
                                <button type="button" class="button button-small edit-result" data-id="<?php echo esc_attr($result->id); ?>" title="Edytuj">
                                    <span class="dashicons dashicons-edit"></span>
                                </button>
                                <button type="button" class="button button-small delete-result" data-id="<?php echo esc_attr($result->id); ?>" title="Usuń">
                                    <span class="dashicons dashicons-trash"></span>
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <div class="race-results-footer">
        <p><strong>Instrukcje:</strong></p>
        <ul>
            <li>Użyj shortcode <code>[race_results_table]</code> aby wyświetlić tabelę na stronie</li>
            <li>Przeciągnij wiersze aby zmienić kolejność wyników</li>
            <li>Możesz dodać wiele plików PDF dla jednego wydarzenia (np. różne dystanse)</li>
            <li>Dla każdego pliku PDF podaj nazwę przycisku (np. "Wyniki 10km", "Wyniki 5km")</li>
        </ul>
    </div>
</div>
