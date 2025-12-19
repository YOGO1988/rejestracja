jQuery(document).ready(function($) {
    'use strict';

    const modal = $('#result-form-modal');
    const form = $('#result-form');
    const resultId = $('#result-id');
    const formTitle = $('#form-title');
    const tbody = $('#results-tbody');
    const pdfContainer = $('#pdf-files-container');
    const onlineContainer = $('#online-links-container');

    let pdfFileCounter = 0;
    let currentPdfFiles = [];
    let onlineLinkCounter = 0;
    let currentOnlineLinks = [];

    // Otwórz modal - dodawanie
    $('#add-result-btn').on('click', function() {
        resetForm();
        formTitle.text('Dodaj wyniki');
        currentPdfFiles = [];
        currentOnlineLinks = [];
        renderPdfFiles();
        renderOnlineLinks();
        modal.fadeIn(200);
    });

    // Zamknij modal
    $('.race-modal-close, #cancel-form').on('click', function() {
        modal.fadeOut(200);
    });

    // Zamknij modal po kliknięciu poza nim
    $(window).on('click', function(e) {
        if ($(e.target).is(modal)) {
            modal.fadeOut(200);
        }
    });

    // Dodaj plik PDF
    $('#add-pdf-btn').on('click', function() {
        addPdfField();
    });

    // Dodaj link online
    $('#add-online-btn').on('click', function() {
        if (onlineContainer.find('.online-field-row').length >= 2) {
            alert('Możesz dodać maksymalnie 2 linki online');
            return;
        }
        addOnlineField();
    });

    // Funkcja dodająca pole dla pliku PDF
    function addPdfField(fileId = '', buttonText = '', fileName = '') {
        pdfFileCounter++;
        const fieldId = 'pdf-field-' + pdfFileCounter;

        const fieldHtml = `
            <div class="pdf-field-row" data-field-id="${fieldId}">
                <div class="pdf-field-group">
                    <input type="hidden" class="pdf-file-id" value="${fileId}">
                    <div class="pdf-file-info">
                        <button type="button" class="button select-pdf-file">
                            <span class="dashicons dashicons-media-document"></span>
                            ${fileName || 'Wybierz plik PDF'}
                        </button>
                        <input type="text" class="pdf-button-text" placeholder="Nazwa przycisku (np. Wyniki 10km)" value="${buttonText}">
                    </div>
                    <button type="button" class="button remove-pdf-field" title="Usuń">
                        <span class="dashicons dashicons-no-alt"></span>
                    </button>
                </div>
            </div>
        `;

        pdfContainer.append(fieldHtml);
    }

    // Wybierz plik PDF z biblioteki mediów
    $(document).on('click', '.select-pdf-file', function(e) {
        e.preventDefault();

        const button = $(this);
        const row = button.closest('.pdf-field-row');

        const mediaUploader = wp.media({
            title: 'Wybierz plik PDF',
            button: {
                text: 'Użyj tego pliku'
            },
            library: {
                type: 'application/pdf'
            },
            multiple: false
        });

        mediaUploader.on('select', function() {
            const attachment = mediaUploader.state().get('selection').first().toJSON();
            row.find('.pdf-file-id').val(attachment.id);
            button.html('<span class="dashicons dashicons-media-document"></span> ' + attachment.filename);
        });

        mediaUploader.open();
    });

    // Usuń pole PDF
    $(document).on('click', '.remove-pdf-field', function() {
        $(this).closest('.pdf-field-row').fadeOut(200, function() {
            $(this).remove();
        });
    });

    // Renderuj pola PDF z tablicy
    function renderPdfFiles() {
        pdfContainer.empty();
        pdfFileCounter = 0;

        if (currentPdfFiles.length === 0) {
            // Dodaj jedno puste pole
            addPdfField();
        } else {
            currentPdfFiles.forEach(function(pdf) {
                // Pobierz nazwę pliku
                const fileName = pdf.filename || 'Plik PDF';
                addPdfField(pdf.file_id, pdf.button_text, fileName);
            });
        }
    }

    // Zbierz dane z pól PDF
    function collectPdfFiles() {
        const pdfFiles = [];

        $('.pdf-field-row').each(function() {
            const fileId = $(this).find('.pdf-file-id').val();
            const buttonText = $(this).find('.pdf-button-text').val();

            if (fileId && buttonText) {
                pdfFiles.push({
                    file_id: parseInt(fileId),
                    button_text: buttonText
                });
            }
        });

        return pdfFiles;
    }

    // Funkcja dodająca pole dla linku online
    function addOnlineField(url = '', buttonText = '') {
        onlineLinkCounter++;
        const fieldId = 'online-field-' + onlineLinkCounter;

        const fieldHtml = `
            <div class="online-field-row" data-field-id="${fieldId}">
                <div class="online-field-group">
                    <input type="url" class="online-url" placeholder="https://" value="${url}">
                    <input type="text" class="online-button-text" placeholder="Nazwa przycisku (np. zobacz)" value="${buttonText}">
                    <button type="button" class="button remove-online-field" title="Usuń">
                        <span class="dashicons dashicons-no-alt"></span>
                    </button>
                </div>
            </div>
        `;

        onlineContainer.append(fieldHtml);
    }

    // Usuń pole online
    $(document).on('click', '.remove-online-field', function() {
        $(this).closest('.online-field-row').fadeOut(200, function() {
            $(this).remove();
        });
    });

    // Renderuj pola online z tablicy
    function renderOnlineLinks() {
        onlineContainer.empty();
        onlineLinkCounter = 0;

        if (currentOnlineLinks.length === 0) {
            // Dodaj jedno puste pole
            addOnlineField();
        } else {
            currentOnlineLinks.forEach(function(online) {
                addOnlineField(online.url, online.button_text);
            });
        }
    }

    // Zbierz dane z pól online
    function collectOnlineLinks() {
        const onlineLinks = [];

        $('.online-field-row').each(function() {
            const url = $(this).find('.online-url').val();
            const buttonText = $(this).find('.online-button-text').val();

            if (url && buttonText) {
                onlineLinks.push({
                    url: url,
                    button_text: buttonText
                });
            }
        });

        return onlineLinks;
    }

    // Resetuj formularz
    function resetForm() {
        form[0].reset();
        resultId.val('');
        pdfContainer.empty();
        pdfFileCounter = 0;
        onlineContainer.empty();
        onlineLinkCounter = 0;
    }

    // Zapisz wynik (dodaj/edytuj)
    form.on('submit', function(e) {
        e.preventDefault();

        // Walidacja pól PDF - jeśli któreś pole ma tylko plik bez nazwy lub nazwę bez pliku, pokaż błąd
        let validationError = false;
        $('.pdf-field-row').each(function() {
            const fileId = $(this).find('.pdf-file-id').val();
            const buttonText = $(this).find('.pdf-button-text').val().trim();

            if ((fileId && !buttonText) || (!fileId && buttonText)) {
                validationError = true;
            }
        });

        if (validationError) {
            alert('Dla każdego pliku PDF musisz podać zarówno plik jak i nazwę przycisku, lub usuń niepełne pole.');
            return;
        }

        const pdfFiles = collectPdfFiles();
        const onlineLinks = collectOnlineLinks();

        const data = {
            action: resultId.val() ? 'race_results_update' : 'race_results_add',
            nonce: raceResultsAdmin.nonce,
            id: resultId.val(),
            race_date: $('#race-date').val(),
            race_name: $('#race-name').val(),
            location: $('#location').val(),
            results_pdf: JSON.stringify(pdfFiles),
            results_online_url: JSON.stringify(onlineLinks)
        };

        $.ajax({
            url: raceResultsAdmin.ajaxUrl,
            type: 'POST',
            data: data,
            success: function(response) {
                if (response.success) {
                    alert(response.data.message || raceResultsAdmin.strings.success);
                    modal.fadeOut(200);
                    location.reload();
                } else {
                    alert(response.data.message || raceResultsAdmin.strings.error);
                }
            },
            error: function() {
                alert(raceResultsAdmin.strings.error);
            }
        });
    });

    // Edytuj wynik
    $(document).on('click', '.edit-result', function() {
        const id = $(this).data('id');

        $.ajax({
            url: raceResultsAdmin.ajaxUrl,
            type: 'POST',
            data: {
                action: 'race_results_get',
                nonce: raceResultsAdmin.nonce,
                id: id
            },
            success: function(response) {
                if (response.success) {
                    const result = response.data.result;

                    formTitle.text('Edytuj wynik');
                    resultId.val(result.id);
                    $('#race-date').val(result.race_date);
                    $('#race-name').val(result.race_name);
                    $('#location').val(result.location);

                    // Ustaw pliki PDF
                    currentPdfFiles = result.results_pdf_array || [];

                    // Ustaw linki online
                    currentOnlineLinks = result.results_online_array || [];

                    // Dla każdego pliku PDF pobierz nazwę z WordPress
                    if (currentPdfFiles.length > 0) {
                        let filesProcessed = 0;
                        currentPdfFiles.forEach(function(pdf, index) {
                            $.ajax({
                                url: raceResultsAdmin.ajaxUrl,
                                type: 'POST',
                                data: {
                                    action: 'race_results_get_attachment',
                                    nonce: raceResultsAdmin.nonce,
                                    attachment_id: pdf.file_id
                                },
                                success: function(attachResponse) {
                                    if (attachResponse.success) {
                                        currentPdfFiles[index].filename = attachResponse.data.filename;
                                    }
                                    filesProcessed++;
                                    if (filesProcessed === currentPdfFiles.length) {
                                        renderPdfFiles();
                                        renderOnlineLinks();
                                    }
                                },
                                error: function() {
                                    filesProcessed++;
                                    if (filesProcessed === currentPdfFiles.length) {
                                        renderPdfFiles();
                                        renderOnlineLinks();
                                    }
                                }
                            });
                        });
                    } else {
                        renderPdfFiles();
                        renderOnlineLinks();
                    }

                    modal.fadeIn(200);
                } else {
                    alert(response.data.message || raceResultsAdmin.strings.error);
                }
            },
            error: function() {
                alert(raceResultsAdmin.strings.error);
            }
        });
    });

    // Usuń wynik
    $(document).on('click', '.delete-result', function() {
        if (!confirm(raceResultsAdmin.strings.confirmDelete)) {
            return;
        }

        const id = $(this).data('id');

        $.ajax({
            url: raceResultsAdmin.ajaxUrl,
            type: 'POST',
            data: {
                action: 'race_results_delete',
                nonce: raceResultsAdmin.nonce,
                id: id
            },
            success: function(response) {
                if (response.success) {
                    alert(response.data.message || raceResultsAdmin.strings.success);
                    location.reload();
                } else {
                    alert(response.data.message || raceResultsAdmin.strings.error);
                }
            },
            error: function() {
                alert(raceResultsAdmin.strings.error);
            }
        });
    });

    // Sortowanie drag & drop
    if (tbody.length) {
        tbody.sortable({
            handle: '.drag-handle',
            placeholder: 'sortable-placeholder',
            axis: 'y',
            update: function(event, ui) {
                const order = [];
                tbody.find('.result-row').each(function() {
                    order.push($(this).data('id'));
                });

                $.ajax({
                    url: raceResultsAdmin.ajaxUrl,
                    type: 'POST',
                    data: {
                        action: 'race_results_update_order',
                        nonce: raceResultsAdmin.nonce,
                        order: order
                    },
                    success: function(response) {
                        if (!response.success) {
                            alert(response.data.message || raceResultsAdmin.strings.error);
                            location.reload();
                        }
                    },
                    error: function() {
                        alert(raceResultsAdmin.strings.error);
                        location.reload();
                    }
                });
            }
        });
    }

    // Wyszukiwanie
    $('#search-results').on('keyup', function() {
        const searchTerm = $(this).val().toLowerCase();

        tbody.find('.result-row').each(function() {
            const row = $(this);
            const name = row.find('.column-name').text().toLowerCase();
            const location = row.find('.column-location').text().toLowerCase();

            if (name.includes(searchTerm) || location.includes(searchTerm)) {
                row.show();
            } else {
                row.hide();
            }
        });
    });
});
