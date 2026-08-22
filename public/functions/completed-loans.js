/* ============================================================
   COMPLETED LOANS PAGE - JavaScript
   ------------------------------------------------------------
   This file handles 3 things:
   1. Searching the table (filter by name/email/borrower id)
   2. Choosing how many rows to show per page
   3. Pagination (Prev / Page X of Y / Next)

   Note: Sorting (Newest, Oldest, etc.) is NOT here.
   Sorting reloads the page using ?sort=... because the
   server needs to calculate "completed_at" dates.
   ============================================================ */

// These variables "remember" the current state of the table.
let currentPage = 1;
let rowsPerPage = 10;


/* ------------------------------------------------------------
   STEP 1: Get all table rows
   ------------------------------------------------------------ */
function getAllRows() {
    const tableBody = document.getElementById('loanTableBody');
    if (!tableBody) {
        return [];
    }
    return Array.from(tableBody.querySelectorAll('tr'));
}


/* ------------------------------------------------------------
   STEP 2: Get only the rows that match the search box
   ------------------------------------------------------------ */
function getMatchingRows() {
    const searchInput = document.getElementById('filterSearchInput');
    const searchText = searchInput ? searchInput.value.trim().toLowerCase() : '';

    const allRows = getAllRows();

    if (searchText === '') {
        return allRows;
    }

    const matchingRows = [];
    for (let i = 0; i < allRows.length; i++) {
        const row = allRows[i];
        const rowText = row.getAttribute('data-search');
        if (rowText.includes(searchText)) {
            matchingRows.push(row);
        }
    }
    return matchingRows;
}


/* ------------------------------------------------------------
   STEP 3: Show the correct rows for the current page
   ------------------------------------------------------------ */
function showCurrentPage() {
    const allRows = getAllRows();
    const matchingRows = getMatchingRows();

    const noResultsBox = document.getElementById('filteredNoResults');
    const tableWrap = document.getElementById('loanTableWrap');
    const paginationWrap = document.getElementById('paginationWrap');

    // Hide every row first
    allRows.forEach(function (row) {
        row.style.display = 'none';
    });

    // No matches at all
    if (matchingRows.length === 0) {
        if (noResultsBox) noResultsBox.style.display = 'block';
        if (tableWrap) tableWrap.style.display = 'none';
        if (paginationWrap) paginationWrap.style.display = 'none';
        return;
    }

    if (noResultsBox) noResultsBox.style.display = 'none';
    if (tableWrap) tableWrap.style.display = '';
    if (paginationWrap) paginationWrap.style.display = 'flex';

    // Work out total pages
    let totalPages;
    if (rowsPerPage === 'all') {
        totalPages = 1;
    } else {
        totalPages = Math.ceil(matchingRows.length / rowsPerPage);
    }
    if (totalPages < 1) {
        totalPages = 1;
    }

    // Keep currentPage inside valid range
    if (currentPage > totalPages) {
        currentPage = totalPages;
    }
    if (currentPage < 1) {
        currentPage = 1;
    }

    // Work out which rows belong on this page
    let startIndex;
    let endIndex;

    if (rowsPerPage === 'all') {
        startIndex = 0;
        endIndex = matchingRows.length;
    } else {
        startIndex = (currentPage - 1) * rowsPerPage;
        endIndex = startIndex + rowsPerPage;
    }

    // Show only rows between startIndex and endIndex
    for (let i = startIndex; i < endIndex && i < matchingRows.length; i++) {
        matchingRows[i].style.display = '';
    }

    updatePaginationInfo(matchingRows.length, startIndex, endIndex);
    updatePaginationStatus(totalPages);
}


/* ------------------------------------------------------------
   STEP 4: Update the "Showing X-Y of Z" text (left side)
   ------------------------------------------------------------ */
function updatePaginationInfo(totalMatches, startIndex, endIndex) {
    const infoBox = document.getElementById('paginationInfo');
    if (!infoBox) return;

    const shownStart = totalMatches === 0 ? 0 : startIndex + 1;
    let shownEnd = endIndex;
    if (shownEnd > totalMatches) {
        shownEnd = totalMatches;
    }

    infoBox.textContent = 'Showing ' + shownStart + '-' + shownEnd + ' of ' + totalMatches;
}


/* ------------------------------------------------------------
   STEP 5: Update "Page X of Y" text + enable/disable Prev/Next
   ------------------------------------------------------------ */
function updatePaginationStatus(totalPages) {
    const pageStatusText = document.getElementById('pageStatusText');
    const prevButton = document.getElementById('prevPageBtn');
    const nextButton = document.getElementById('nextPageBtn');

    if (pageStatusText) {
        pageStatusText.textContent = 'Page ' + currentPage + ' of ' + totalPages;
    }

    if (prevButton) {
        prevButton.disabled = (currentPage === 1);
    }
    if (nextButton) {
        nextButton.disabled = (currentPage === totalPages);
    }
}


/* ------------------------------------------------------------
   STEP 6: Functions connected to the HTML (onclick / onkeyup)
   ------------------------------------------------------------ */

function goToPreviousPage() {
    currentPage = currentPage - 1;
    showCurrentPage();
}

function goToNextPage() {
    currentPage = currentPage + 1;
    showCurrentPage();
}

function onSearchChanged() {
    currentPage = 1; // always go back to page 1 on a new search
    showCurrentPage();
}

function onRowsPerPageChanged() {
    const select = document.getElementById('rowsPerPageSelect');
    const value = select.value;

    if (value === 'all') {
        rowsPerPage = 'all';
    } else {
        rowsPerPage = parseInt(value, 10);
    }

    currentPage = 1; // reset to page 1 whenever rows-per-page changes
    showCurrentPage();
}


/* ------------------------------------------------------------
   STEP 7: Run everything once the page has fully loaded
   ------------------------------------------------------------ */
document.addEventListener('DOMContentLoaded', function () {
    if (document.getElementById('loanTableWrap')) {
        showCurrentPage();
    }
});