jQuery(document).ready(function($) {
    'use strict';

    const modal = $('#race-form-modal');
    const form = $('#race-form');
    const tbody = $('#races-tbody');

    // Otwieranie modala dodawania
    $('#add-race-btn').on('click', function() {
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

        const raceId = $('#race-id').val();
        const action = raceId ? 'race_reg_update_race' : 'race_reg_add_race';

        const data = {
            action: action,
            nonce: raceRegAdmin.nonce,
            id: raceId,
            race_date: $('#race-date').val(),
            race_name: $('#race-name').val(),
            location: $('#location').val(),
            distance: $('#distance').val(),
            website_url: $('#website-url').val(),
            registration_url: $('#registration-url').val(),
            is_pinned: $('#is-pinned').is(':checked') ? 1 : 0,
            is_limit_reached: $('#is-limit-reached').is(':checked') ? 1 : 0,
            is_coming_soon: $('#is-coming-soon').is(':checked') ? 1 : 0
        };

        $.post(raceRegAdmin.ajaxUrl, data, function(response) {
            if (response.success) {
                showNotice(response.data.message, 'success');
                modal.fadeOut();
                location.reload();
            } else {
                showNotice(response.data.message, 'error');
            }
        });
    });

    // Edycja zawodu
    $(document).on('click', '.edit-race', function() {
        const raceId = $(this).data('id');

        $.post(raceRegAdmin.ajaxUrl, {
            action: 'race_reg_get_race',
            nonce: raceRegAdmin.nonce,
            id: raceId
        }, function(response) {
            if (response.success) {
                const race = response.data.race;

                $('#race-id').val(race.id);
                $('#race-date').val(race.race_date);
                $('#race-name').val(race.race_name);
                $('#location').val(race.location);
                $('#distance').val(race.distance);
                $('#website-url').val(race.website_url);
                $('#registration-url').val(race.registration_url);
                $('#is-pinned').prop('checked', race.is_pinned == 1);
                $('#is-limit-reached').prop('checked', race.is_limit_reached == 1);
                $('#is-coming-soon').prop('checked', race.is_coming_soon == 1);

                $('#form-title').text('Edytuj zawody');
                modal.fadeIn();
            } else {
                showNotice(response.data.message, 'error');
            }
        });
    });

    // Usuwanie zawodu
    $(document).on('click', '.delete-race', function() {
        if (!confirm(raceRegAdmin.strings.confirmDelete)) {
            return;
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
    });

    // Przełączanie przypięcia
    $(document).on('click', '.pin-race', function() {
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
    });

    // Przełączanie limitu
    $(document).on('click', '.toggle-limit', function() {
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
