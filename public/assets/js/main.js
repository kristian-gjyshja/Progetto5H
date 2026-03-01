function confermaAzione(tipo, id) {
    let messaggi = {
        archivia: "Sei sicuro di archiviare questo abbonamento?",
        disattiva: "Sei sicuro di disattivare questo abbonamento?",
        riattiva: "Sei sicuro di riattivare questo abbonamento con la nuova data?",
        dearchivia: "Vuoi rimuovere questo abbonamento dagli archiviati?",
        elimina: "ATTENZIONE: eliminazione definitiva. Continuare?"
    };

    const buildActionUrl = function (extraParams) {
        const url = new URL(window.location.href);
        ['archivia', 'disattiva', 'riattiva', 'riattiva_data', 'dearchivia', 'elimina', 'success', 'error'].forEach(function (param) {
            url.searchParams.delete(param);
        });
        url.searchParams.set(tipo, id);

        if (extraParams) {
            Object.keys(extraParams).forEach(function (param) {
                url.searchParams.set(param, extraParams[param]);
            });
        }

        return url.toString();
    };

    if (tipo === 'riattiva') {
        const oggi = new Date();
        const oggiIso = oggi.getFullYear()
            + '-' + String(oggi.getMonth() + 1).padStart(2, '0')
            + '-' + String(oggi.getDate()).padStart(2, '0');

        const nuovaDataInput = window.prompt('Inserisci la nuova data di scadenza (YYYY-MM-DD)', oggiIso);
        if (nuovaDataInput === null) {
            return;
        }

        const nuovaData = nuovaDataInput.trim();
        const matchData = nuovaData.match(/^(\d{4})-(\d{2})-(\d{2})$/);
        if (!matchData) {
            alert('Data non valida. Usa il formato YYYY-MM-DD.');
            return;
        }

        const anno = Number.parseInt(matchData[1], 10);
        const mese = Number.parseInt(matchData[2], 10);
        const giorno = Number.parseInt(matchData[3], 10);
        const dataVerifica = new Date(anno, mese - 1, giorno);
        const dataValida = dataVerifica.getFullYear() === anno
            && dataVerifica.getMonth() === mese - 1
            && dataVerifica.getDate() === giorno;

        if (!dataValida) {
            alert('Data non valida. Inserisci una data reale.');
            return;
        }

        if (confirm(messaggi[tipo])) {
            window.location.href = buildActionUrl({ riattiva_data: nuovaData });
        }
        return;
    }

    if (confirm(messaggi[tipo])) {
        window.location.href = buildActionUrl();
    }
}

document.addEventListener('DOMContentLoaded', function () {
    const parseSortableNumber = function (value) {
        const cleaned = value.replace(/[^0-9,.\-]/g, '');
        if (cleaned === '') {
            return null;
        }

        const hasComma = cleaned.includes(',');
        const hasDot = cleaned.includes('.');
        let normalized = cleaned;

        if (hasComma && hasDot) {
            normalized = normalized.replace(/\./g, '').replace(',', '.');
        } else if (hasComma) {
            normalized = normalized.replace(',', '.');
        }

        const parsed = Number.parseFloat(normalized);
        return Number.isNaN(parsed) ? null : parsed;
    };

    const parseSortableDate = function (value) {
        const isoDatePattern = /^\d{4}-\d{2}-\d{2}$/;
        if (isoDatePattern.test(value)) {
            const timestamp = Date.parse(value + 'T00:00:00');
            return Number.isNaN(timestamp) ? null : timestamp;
        }

        const itDateMatch = value.match(/^(\d{2})\/(\d{2})\/(\d{4})$/);
        if (!itDateMatch) {
            return null;
        }

        const timestamp = Date.parse(itDateMatch[3] + '-' + itDateMatch[2] + '-' + itDateMatch[1] + 'T00:00:00');
        return Number.isNaN(timestamp) ? null : timestamp;
    };

    const getCellValue = function (row, index) {
        const cell = row.cells[index];
        if (!cell) {
            return '';
        }

        return cell.textContent.replace(/\s+/g, ' ').trim();
    };

    const compareRowValues = function (valueA, valueB) {
        const dateA = parseSortableDate(valueA);
        const dateB = parseSortableDate(valueB);
        if (dateA !== null && dateB !== null) {
            return dateA - dateB;
        }

        const numberA = parseSortableNumber(valueA);
        const numberB = parseSortableNumber(valueB);
        if (numberA !== null && numberB !== null) {
            return numberA - numberB;
        }

        return valueA.localeCompare(valueB, 'it', {
            sensitivity: 'base',
            numeric: true
        });
    };

    const initSortableTables = function () {
        const tables = document.querySelectorAll('table.admin-table');

        tables.forEach(function (table) {
            const thead = table.querySelector('thead');
            const tbody = table.querySelector('tbody');
            if (!thead || !tbody) {
                return;
            }

            const allHeaders = Array.from(thead.querySelectorAll('th'));
            if (allHeaders.length === 0) {
                return;
            }

            const sortableIndexes = [];

            allHeaders.forEach(function (header, index) {
                const label = header.textContent.replace(/\s+/g, ' ').trim();
                const isActionColumn = /^azioni?$/i.test(label);

                if (label === '' || isActionColumn) {
                    return;
                }

                sortableIndexes.push(index);
                header.classList.add('sortable-th');
                header.setAttribute('title', 'Ordina per questa colonna');
                header.setAttribute('role', 'button');
                header.setAttribute('tabindex', '0');
                header.dataset.sortDirection = '';
            });

            if (sortableIndexes.length === 0) {
                return;
            }

            const updateHeaderDirection = function (activeIndex, direction) {
                sortableIndexes.forEach(function (index) {
                    allHeaders[index].dataset.sortDirection = index === activeIndex ? direction : '';
                });
            };

            const sortByColumn = function (index) {
                const dataRows = Array.from(tbody.querySelectorAll('tr')).filter(function (row) {
                    return row.dataset.staticRow !== 'true';
                });

                if (dataRows.length < 2) {
                    return;
                }

                const staticRows = Array.from(tbody.querySelectorAll('tr')).filter(function (row) {
                    return row.dataset.staticRow === 'true';
                });

                const currentIndex = Number.parseInt(table.dataset.sortIndex || '-1', 10);
                const currentDirection = table.dataset.sortDirection === 'desc' ? 'desc' : 'asc';

                let nextDirection = 'asc';
                if (currentIndex === index) {
                    nextDirection = currentDirection === 'asc' ? 'desc' : 'asc';
                }

                table.dataset.sortIndex = String(index);
                table.dataset.sortDirection = nextDirection;

                dataRows.sort(function (rowA, rowB) {
                    const valueA = getCellValue(rowA, index);
                    const valueB = getCellValue(rowB, index);
                    const result = compareRowValues(valueA, valueB);
                    return nextDirection === 'asc' ? result : -result;
                });

                dataRows.forEach(function (row) {
                    tbody.appendChild(row);
                });

                staticRows.forEach(function (row) {
                    tbody.appendChild(row);
                });

                updateHeaderDirection(index, nextDirection);
            };

            sortableIndexes.forEach(function (index) {
                const header = allHeaders[index];

                header.addEventListener('click', function () {
                    sortByColumn(index);
                });

                header.addEventListener('keydown', function (event) {
                    if (event.key !== 'Enter' && event.key !== ' ') {
                        return;
                    }

                    event.preventDefault();
                    sortByColumn(index);
                });
            });
        });
    };

    initSortableTables();

    const searchInputs = document.querySelectorAll('[data-table-search]');

    searchInputs.forEach(function (searchInput) {
        const tableId = searchInput.getAttribute('data-table-search');
        if (!tableId) {
            return;
        }

        const table = document.getElementById(tableId);
        if (!table) {
            return;
        }

        const tbody = table.querySelector('tbody');
        if (!tbody) {
            return;
        }

        const noResultsId = searchInput.getAttribute('data-no-results');
        const noResultsRow = noResultsId ? document.getElementById(noResultsId) : null;

        const rows = Array.from(tbody.querySelectorAll('tr')).filter(function (row) {
            const isStaticRow = row.dataset.staticRow === 'true';
            return row !== noResultsRow && !isStaticRow;
        });

        if (rows.length === 0) {
            return;
        }

        const applySearch = function () {
            const query = searchInput.value.trim().toLowerCase();
            let visibleCount = 0;

            rows.forEach(function (row) {
                const content = row.textContent.toLowerCase();
                const isVisible = query === '' || content.includes(query);
                row.classList.toggle('hidden', !isVisible);

                if (isVisible) {
                    visibleCount++;
                }
            });

            if (noResultsRow) {
                noResultsRow.classList.toggle('hidden', visibleCount !== 0);
            }
        };

        searchInput.addEventListener('input', applySearch);
        searchInput.addEventListener('keyup', applySearch);
        applySearch();
    });
});
