jQuery(document).ready(function($) {
    'use strict';

    const table = $('#race-results-table');
    const tbody = table.find('tbody');
    const searchInput = $('#race-results-search-input');
    const sortSelect = $('#race-results-sort-select');

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

        updateResultsCount();
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
    });

    // Aktualizacja licznika wyników
    function updateResultsCount() {
        const visibleRows = tbody.find('.race-row:visible').length;
        $('.race-count strong').text(visibleRows);
    }
});
