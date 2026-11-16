// assets/js/student-my-reports.js

document.addEventListener('DOMContentLoaded', function () {
    // Search reports by reference, unit, category, description, or status.
    const searchInput = document.getElementById('reportSearch');
    const reportRows = document.querySelectorAll('.report-row');
    const noResults = document.getElementById('noResults');

    if (searchInput) {
        searchInput.addEventListener('input', function () {
            const searchValue = this.value.toLowerCase().trim();
            let visibleCount = 0;

            reportRows.forEach(function (row) {
                const rowText = row.textContent.toLowerCase();
                const matches = rowText.includes(searchValue);

                row.style.display = matches ? '' : 'none';

                if (matches) {
                    visibleCount++;
                }
            });

            if (noResults) {
                noResults.style.display = visibleCount === 0 ? 'block' : 'none';
            }
        });
    }
});