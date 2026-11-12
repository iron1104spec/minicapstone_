// assets/js/admin-analytics.js

// Chart Defaults
Chart.defaults.font.family = "'Inter', sans-serif";
Chart.defaults.font.size = 10;
Chart.defaults.color = '#84918B';

const chartColors = [
    '#4E968B', '#245B54', '#82B8AA', '#A9CFC3', '#6B9E91', 
    '#D1E4DC', '#688B81', '#B5CFC5', '#8BB3A5', '#49776C'
];

/*
|--------------------------------------------------------------------------
| MONTHLY REPORT CHART
|--------------------------------------------------------------------------
*/
const monthlyCanvas = document.getElementById('monthlyChart');
if (monthlyCanvas) {
    new Chart(monthlyCanvas, {
        type: 'line',
        data: {
            labels: window.monthlyLabels,
            datasets: [{
                label: 'Issue Reports',
                data: window.monthlyData,
                borderColor: '#4E968B',
                backgroundColor: 'rgba(78,150,139,0.12)',
                borderWidth: 3,
                fill: true,
                tension: 0.35,
                pointRadius: 4,
                pointHoverRadius: 6,
                pointBackgroundColor: '#245B54'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                tooltip: { backgroundColor: '#245B54', padding: 12, cornerRadius: 8 }
            },
            scales: {
                y: { beginAtZero: true, ticks: { precision: 0 }, grid: { color: '#EDF1EF' } },
                x: { grid: { display: false } }
            }
        }
    });
}

/*
|--------------------------------------------------------------------------
| CATEGORY DISTRIBUTION CHART
|--------------------------------------------------------------------------
*/
const categoryCanvas = document.getElementById('categoryChart');
if (categoryCanvas) {
    new Chart(categoryCanvas, {
        type: 'doughnut',
        data: {
            labels: window.categoryLabels,
            datasets: [{
                data: window.categoryData,
                backgroundColor: chartColors,
                borderColor: '#FFFFFF',
                borderWidth: 3,
                hoverOffset: 7
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            cutout: '65%',
            plugins: {
                legend: { position: 'bottom', labels: { usePointStyle: true, pointStyle: 'circle', padding: 17 } },
                tooltip: { backgroundColor: '#245B54', padding: 12, cornerRadius: 8 }
            }
        }
    });
}

/*
|--------------------------------------------------------------------------
| STATUS DISTRIBUTION CHART
|--------------------------------------------------------------------------
*/
const statusColors = {
    'Pending': '#D6A04D',
    'Under Inspection': '#6B91BA',
    'Under Repair': '#9476B8',
    'Resolved': '#5A9B75',
    'Unresolved': '#C66F61'
};

const statusCanvas = document.getElementById('statusChart');
if (statusCanvas) {
    new Chart(statusCanvas, {
        type: 'doughnut',
        data: {
            labels: window.statusLabels,
            datasets: [{
                data: window.statusData,
                backgroundColor: window.statusLabels.map(label => statusColors[label] || '#A0AAA4'),
                borderColor: '#FFFFFF',
                borderWidth: 3,
                hoverOffset: 7
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            cutout: '65%',
            plugins: {
                legend: { position: 'bottom', labels: { usePointStyle: true, pointStyle: 'circle', padding: 17 } },
                tooltip: { backgroundColor: '#245B54', padding: 12, cornerRadius: 8 }
            }
        }
    });
}

/*
|--------------------------------------------------------------------------
| COMPUTER UNIT CHART
|--------------------------------------------------------------------------
*/
const unitCanvas = document.getElementById('unitChart');
if (unitCanvas) {
    new Chart(unitCanvas, {
        type: 'bar',
        data: {
            labels: window.unitLabels,
            datasets: [{
                label: 'Issue Reports',
                data: window.unitData,
                backgroundColor: '#4E968B',
                hoverBackgroundColor: '#245B54',
                borderRadius: 6,
                maxBarThickness: 28
            }]
        },
        options: {
            indexAxis: 'y',
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                tooltip: { backgroundColor: '#245B54', padding: 12, cornerRadius: 8 }
            },
            scales: {
                x: { beginAtZero: true, ticks: { precision: 0 }, grid: { color: '#EDF1EF' } },
                y: { grid: { display: false } }
            }
        }
    });
}

/*
|--------------------------------------------------------------------------
| RESPONSIVE SIDEBAR
|--------------------------------------------------------------------------
*/
const sidebar = document.getElementById('sidebar');
const sidebarToggle = document.getElementById('sidebarToggle');
const sidebarOverlay = document.getElementById('sidebarOverlay');

if (sidebar && sidebarToggle && sidebarOverlay) {
    function closeSidebar() {
        sidebar.classList.remove('show');
        sidebarOverlay.classList.remove('show');
    }

    sidebarToggle.addEventListener('click', function () {
        sidebar.classList.toggle('show');
        sidebarOverlay.classList.toggle('show');
    });

    sidebarOverlay.addEventListener('click', closeSidebar);
}