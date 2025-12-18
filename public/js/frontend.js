jQuery(document).ready(function($) {
    'use strict';

    const table = $('#race-results-table');
    const tbody = table.find('tbody');
    const searchInput = $('#race-results-search-input');
    const sortSelect = $('#race-results-sort-select');

    // Paginacja
    const rowsPerPage = 20;
    let currentPage = 1;
    let visibleRows = [];

    // Inicjalizacja
    function init() {
        updateVisibleRows();
        updatePagination();
    }

    // Pobierz widoczne wiersze (po wyszukiwaniu)
    function updateVisibleRows() {
        visibleRows = tbody.find('.race-row:visible').get();
    }

    // Aktualizuj paginację
    function updatePagination() {
        const totalPages = Math.ceil(visibleRows.length / rowsPerPage);

        // Aktualizuj przyciski i info
        $('#race-current-page').text(currentPage);
        $('#race-total-pages').text(totalPages);

        $('#race-pagination-prev').prop('disabled', currentPage === 1);
        $('#race-pagination-next').prop('disabled', currentPage === totalPages || totalPages === 0);

        // Ukryj paginację jeśli jest tylko 1 strona lub mniej
        if (totalPages <= 1) {
            $('#race-pagination').hide();
        } else {
            $('#race-pagination').show();
        }

        // Pokaż tylko wiersze dla aktualnej strony
        showPage(currentPage);
    }

    // Pokaż wiersze dla danej strony
    function showPage(page) {
        const start = (page - 1) * rowsPerPage;
        const end = start + rowsPerPage;

        // Ukryj wszystkie wiersze
        $(visibleRows).hide();

        // Pokaż tylko wiersze dla aktualnej strony
        for (let i = start; i < end && i < visibleRows.length; i++) {
            $(visibleRows[i]).show();
        }

        // Przewiń do góry tabeli
        $('html, body').animate({
            scrollTop: $('.race-results-table-wrap').offset().top - 100
        }, 400);
    }

    // Obsługa przycisków paginacji
    $('#race-pagination-prev').on('click', function() {
        if (currentPage > 1) {
            currentPage--;
            updatePagination();
        }
    });

    $('#race-pagination-next').on('click', function() {
        const totalPages = Math.ceil(visibleRows.length / rowsPerPage);
        if (currentPage < totalPages) {
            currentPage++;
            updatePagination();
        }
    });

    // Wyszukiwanie
    searchInput.on('keyup', function() {
        const searchTerm = $(this).val().toLowerCase();

        tbody.find('.race-row').each(function() {
            const row = $(this);
            const name = row.data('name');
            const location = row.data('location');

            if (name.includes(searchTerm) || location.includes(searchTerm)) {
                row.show();
            } else {
                row.hide();
            }
        });

        // Reset do pierwszej strony po wyszukiwaniu
        currentPage = 1;
        updateVisibleRows();
        updatePagination();
    });

    // Sortowanie
    sortSelect.on('change', function() {
        const sortValue = $(this).val();
        const rows = tbody.find('.race-row').get();

        rows.sort(function(a, b) {
            const rowA = $(a);
            const rowB = $(b);

            let valA, valB;

            switch(sortValue) {
                case 'date-asc':
                    valA = rowA.data('date');
                    valB = rowB.data('date');
                    return valA > valB ? 1 : -1;

                case 'date-desc':
                    valA = rowA.data('date');
                    valB = rowB.data('date');
                    return valA < valB ? 1 : -1;

                case 'name-asc':
                    valA = rowA.data('name');
                    valB = rowB.data('name');
                    return valA > valB ? 1 : -1;

                case 'name-desc':
                    valA = rowA.data('name');
                    valB = rowB.data('name');
                    return valA < valB ? 1 : -1;

                case 'location-asc':
                    valA = rowA.data('location');
                    valB = rowB.data('location');
                    return valA > valB ? 1 : -1;

                case 'location-desc':
                    valA = rowA.data('location');
                    valB = rowB.data('location');
                    return valA < valB ? 1 : -1;

                default:
                    return 0;
            }
        });

        $.each(rows, function(index, row) {
            tbody.append(row);
        });

        // Reset do pierwszej strony po sortowaniu
        currentPage = 1;
        updateVisibleRows();
        updatePagination();
    });

    // Inicjalizacja przy załadowaniu
    init();
});
