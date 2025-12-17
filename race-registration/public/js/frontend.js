jQuery(document).ready(function($) {
    'use strict';

    const table = $('#race-registration-table');
    const tbody = table.find('tbody');
    const searchInput = $('#race-search-input');
    const sortSelect = $('#race-sort-select');

    // Wyszukiwarka
    if (searchInput.length) {
        let searchTimeout;
        searchInput.on('keyup', function() {
            clearTimeout(searchTimeout);
            const searchTerm = $(this).val().toLowerCase().trim();

            searchTimeout = setTimeout(function() {
                let visibleCount = 0;

                tbody.find('tr.race-row').each(function() {
                    const raceName = $(this).data('name');
                    const location = $(this).data('location');

                    if (raceName.includes(searchTerm) || location.includes(searchTerm)) {
                        $(this).show();
                        visibleCount++;
                    } else {
                        $(this).hide();
                    }
                });

                updateResultsCount(visibleCount);

                if (visibleCount === 0) {
                    showNoResults();
                } else {
                    hideNoResults();
                }
            }, 300);
        });
    }

    // Sortowanie
    if (sortSelect.length) {
        sortSelect.on('change', function() {
            const sortValue = $(this).val();
            const rows = tbody.find('tr.race-row').get();

            rows.sort(function(a, b) {
                const aVal = getSortValue(a, sortValue);
                const bVal = getSortValue(b, sortValue);

                // Przypięte zawsze na górze
                const aPinned = $(a).hasClass('race-pinned');
                const bPinned = $(b).hasClass('race-pinned');

                if (aPinned && !bPinned) return -1;
                if (!aPinned && bPinned) return 1;

                // Sortowanie według wybranego kryterium
                if (sortValue.includes('asc')) {
                    return aVal > bVal ? 1 : -1;
                } else {
                    return aVal < bVal ? 1 : -1;
                }
            });

            $.each(rows, function(index, row) {
                tbody.append(row);
            });
        });
    }

    // Pobieranie wartości do sortowania
    function getSortValue(row, sortValue) {
        const $row = $(row);

        if (sortValue.includes('date')) {
            return $row.data('date');
        } else if (sortValue.includes('name')) {
            return $row.data('name');
        } else if (sortValue.includes('location')) {
            return $row.data('location');
        }

        return '';
    }

    // Aktualizacja licznika wyników
    function updateResultsCount(count) {
        const countElement = $('.race-count');
        if (countElement.length) {
            const word = getRaceWord(count);
            countElement.html('Wyświetlono <strong>' + count + '</strong> ' + word);
        }
    }

    // Odmiana słowa "zawody"
    function getRaceWord(count) {
        if (count === 1) {
            return 'zawód';
        } else if (count >= 2 && count <= 4) {
            return 'zawody';
        } else {
            return 'zawodów';
        }
    }

    // Wyświetlanie komunikatu "brak wyników"
    function showNoResults() {
        let noResultsRow = tbody.find('.race-no-results-row');
        if (noResultsRow.length === 0) {
            const colspan = table.find('thead th').length;
            noResultsRow = $('<tr class="race-no-results-row"><td colspan="' + colspan + '" style="text-align: center; padding: 40px 20px; color: #666;">Nie znaleziono zawodów spełniających kryteria wyszukiwania.</td></tr>');
            tbody.append(noResultsRow);
        }
        noResultsRow.show();
    }

    // Ukrywanie komunikatu "brak wyników"
    function hideNoResults() {
        tbody.find('.race-no-results-row').hide();
    }

    // Animacja przy przewijaniu
    function animateOnScroll() {
        const rows = $('.race-row');

        rows.each(function(index) {
            const row = $(this);
            const offset = row.offset().top;
            const scrollTop = $(window).scrollTop();
            const windowHeight = $(window).height();

            if (scrollTop + windowHeight > offset + 100) {
                setTimeout(function() {
                    row.addClass('race-row-visible');
                }, index * 50);
            }
        });
    }

    // Uruchomienie animacji
    if ($(window).width() > 768) {
        $(window).on('scroll', animateOnScroll);
        animateOnScroll();
    } else {
        $('.race-row').addClass('race-row-visible');
    }
});
