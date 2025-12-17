jQuery(document).ready(function($) {
    'use strict';

    console.log('=== Race Registration Admin JS Loaded ===');

    const modal = $('#race-form-modal');
    const form = $('#race-form');
    const tbody = $('#races-tbody');

    console.log('Modal found:', modal.length);
    console.log('Form found:', form.length);
    console.log('Tbody found:', tbody.length);

    if (form.length === 0) {
        console.error('ERROR: Form #race-form not found!');
        alert('BŁĄD: Formularz nie został znaleziony. Odśwież stronę.');
        return;
    }

    console.log('Setting up form submit handler...');

    // Otwieranie modala dodawania
    $('#add-race-btn').on('click', function() {
        console.log('=== ADD RACE BUTTON CLICKED ===');
        form[0].reset();
        $('#race-id').val('');
        $('#form-title').text('Dodaj zawody');
        modal.fadeIn();
    });

    // Zamykanie modala
    $('.race-modal-close, #cancel-form').on('click', function() {
        modal.fadeOut();
    });

    // Zamykanie modala po kliknięciu poza nim
    $(window).on('click', function(e) {
        if ($(e.target).is(modal)) {
            modal.fadeOut();
        }
    });

    // Dodawanie/edycja zawodu
    form.on('submit', function(e) {
        e.preventDefault();
        e.stopPropagation();

        console.log('=== FORM SUBMIT START ===');
        console.log('Date:', $('#race-date').val());
        console.log('Name:', $('#race-name').val());
        console.log('Location:', $('#location').val());
        console.log('Distance:', $('#distance').val());

        // Sprawdzenie wymaganych pól
        const raceDate = $('#race-date').val();
        const raceName = $('#race-name').val();
        const location = $('#location').val();
        const distance = $('#distance').val();

        if (!raceDate || !raceName || !location || !distance) {
            console.log('Validation failed - empty fields');
            showNotice('Wypełnij wszystkie wymagane pola', 'error');
            return false;
        }

        const raceId = $('#race-id').val();
        const action = raceId ? 'race_reg_update_race' : 'race_reg_add_race';

        console.log('Race ID:', raceId);
        console.log('Action:', action);

        const data = {
            action: action,
            nonce: raceRegAdmin.nonce,
            id: raceId,
            race_date: raceDate,
            race_name: raceName,
            location: location,
            distance: distance,
            website_url: $('#website-url').val() || '',
            registration_url: $('#registration-url').val() || '',
            is_pinned: $('#is-pinned').is(':checked') ? 1 : 0,
            is_limit_reached: $('#is-limit-reached').is(':checked') ? 1 : 0,
            is_coming_soon: $('#is-coming-soon').is(':checked') ? 1 : 0
        };

        console.log('Sending AJAX request with data:', data);

        $.post(raceRegAdmin.ajaxUrl, data)
            .done(function(response) {
                console.log('AJAX Success - Response:', response);
                if (response.success) {
                    showNotice(response.data.message, 'success');
                    modal.fadeOut();
                    setTimeout(function() {
                        location.reload();
                    }, 500);
                } else {
                    console.log('Server returned error:', response.data);
                    showNotice(response.data.message || 'Wystąpił błąd', 'error');
                }
            })
            .fail(function(xhr, status, error) {
                console.error('AJAX Failed - Status:', status, 'Error:', error);
                console.error('XHR:', xhr);
                showNotice('Błąd połączenia: ' + error, 'error');
            })
            .always(function() {
                console.log('=== FORM SUBMIT END ===');
            });

        return false;
    });

    // Edycja zawodu
    $(document).on('click', '.edit-race', function() {
        const raceId = $(this).data('id');

        console.log('=== EDIT RACE CLICKED === ID:', raceId);

        $.post(raceRegAdmin.ajaxUrl, {
            action: 'race_reg_get_race',
            nonce: raceRegAdmin.nonce,
            id: raceId
        }, function(response) {
            if (response.success) {
                const race = response.data.race;

                console.log('Race data:', race);

                // RESET formularza PRZED wypełnieniem
                form[0].reset();

                // Wypełnienie pól tekstowych
                $('#race-id').val(race.id);
                $('#race-date').val(race.race_date);
                $('#race-name').val(race.race_name);
                $('#location').val(race.location);
                $('#distance').val(race.distance);
                $('#website-url').val(race.website_url || '');
                $('#registration-url').val(race.registration_url || '');

                // Ustawienie checkboxów - konwersja na boolean
                $('#is-pinned').prop('checked', parseInt(race.is_pinned) === 1);
                $('#is-limit-reached').prop('checked', parseInt(race.is_limit_reached) === 1);
                $('#is-coming-soon').prop('checked', parseInt(race.is_coming_soon) === 1);

                console.log('Checkboxes set:');
                console.log('  is_pinned:', race.is_pinned, '→', $('#is-pinned').prop('checked'));
                console.log('  is_limit_reached:', race.is_limit_reached, '→', $('#is-limit-reached').prop('checked'));
                console.log('  is_coming_soon:', race.is_coming_soon, '→', $('#is-coming-soon').prop('checked'));

                $('#form-title').text('Edytuj zawody');
                modal.fadeIn();
            } else {
                showNotice(response.data.message, 'error');
            }
        });
    });

    // Usuwanie zawodu
    $(document).on('click', '.delete-race', function(e) {
        e.preventDefault();
        e.stopPropagation();

        if (!confirm(raceRegAdmin.strings.confirmDelete)) {
            return false;
        }

        const raceId = $(this).data('id');
        const row = $(this).closest('tr');

        $.post(raceRegAdmin.ajaxUrl, {
            action: 'race_reg_delete_race',
            nonce: raceRegAdmin.nonce,
            id: raceId
        }, function(response) {
            if (response.success) {
                row.fadeOut(300, function() {
                    $(this).remove();
                    checkEmptyTable();
                });
                showNotice(response.data.message, 'success');
            } else {
                showNotice(response.data.message, 'error');
            }
        });

        return false;
    });

    // Przełączanie przypięcia
    $(document).on('click', '.pin-race', function(e) {
        e.preventDefault();
        e.stopPropagation();

        const raceId = $(this).data('id');
        const row = $(this).closest('tr');

        $.post(raceRegAdmin.ajaxUrl, {
            action: 'race_reg_toggle_pin',
            nonce: raceRegAdmin.nonce,
            id: raceId
        }, function(response) {
            if (response.success) {
                if (response.data.is_pinned) {
                    row.addClass('pinned-row');
                    row.find('.column-name').append('<span class="dashicons dashicons-flag pinned-icon" title="Przypięte"></span>');
                } else {
                    row.removeClass('pinned-row');
                    row.find('.pinned-icon').remove();
                }
                showNotice(response.data.message, 'success');
            } else {
                showNotice(response.data.message, 'error');
            }
        });

        return false;
    });

    // Przełączanie limitu
    $(document).on('click', '.toggle-limit', function(e) {
        e.preventDefault();
        e.stopPropagation();

        const raceId = $(this).data('id');
        const row = $(this).closest('tr');

        $.post(raceRegAdmin.ajaxUrl, {
            action: 'race_reg_toggle_limit',
            nonce: raceRegAdmin.nonce,
            id: raceId
        }, function(response) {
            if (response.success) {
                const statusBadge = row.find('.status-badge');
                if (response.data.is_limit_reached) {
                    statusBadge.removeClass('active').addClass('limit-reached').text('Limit osiągnięty');
                } else {
                    statusBadge.removeClass('limit-reached').addClass('active').text('Aktywne');
                }
                showNotice(response.data.message, 'success');
            } else {
                showNotice(response.data.message, 'error');
            }
        });

        return false;
    });

    // Sortowanie drag & drop
    tbody.sortable({
        handle: '.drag-handle',
        axis: 'y',
        cursor: 'move',
        placeholder: 'sortable-placeholder',
        update: function(event, ui) {
            const order = [];
            tbody.find('tr.race-row').each(function() {
                order.push($(this).data('id'));
            });

            $.post(raceRegAdmin.ajaxUrl, {
                action: 'race_reg_update_order',
                nonce: raceRegAdmin.nonce,
                order: order
            }, function(response) {
                if (response.success) {
                    showNotice('Kolejność została zaktualizowana', 'success');
                } else {
                    showNotice(response.data.message, 'error');
                }
            });
        }
    });

    // Wyszukiwarka
    let searchTimeout;
    $('#search-races').on('keyup', function() {
        clearTimeout(searchTimeout);
        const searchTerm = $(this).val().toLowerCase();

        searchTimeout = setTimeout(function() {
            tbody.find('tr.race-row').each(function() {
                const raceName = $(this).find('.column-name').text().toLowerCase();
                const location = $(this).find('.column-location').text().toLowerCase();

                if (raceName.includes(searchTerm) || location.includes(searchTerm)) {
                    $(this).show();
                } else {
                    $(this).hide();
                }
            });
        }, 300);
    });

    // Funkcja sprawdzająca pustą tabelę
    function checkEmptyTable() {
        if (tbody.find('tr.race-row').length === 0) {
            tbody.html('<tr class="no-items"><td colspan="9">Brak zawodów do wyświetlenia. Dodaj pierwsze zawody!</td></tr>');
        }
    }

    // Wyświetlanie powiadomień
    function showNotice(message, type) {
        const noticeClass = type === 'success' ? 'notice-success' : 'notice-error';
        const notice = $('<div class="notice ' + noticeClass + ' is-dismissible"><p>' + message + '</p></div>');

        $('.race-registration-admin h1').after(notice);

        setTimeout(function() {
            notice.fadeOut(300, function() {
                $(this).remove();
            });
        }, 3000);
    }
});
