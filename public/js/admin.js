function showSection(sectionId) {
    document.querySelectorAll(".section").forEach(section => {
        section.style.display = "none";
        section.classList.remove("active");
    });
    document.getElementById(sectionId).classList.add("active");
    document.getElementById(sectionId).style.display = "block";

    if (sectionId === 'dashboard') initCharts();
}

function initCharts() {
    if (window.chart1 || window.chart2) return;

    const ctx1 = document.getElementById('interventionsChart');
    const ctx2 = document.getElementById('statutChart');

    if (!ctx1 || !ctx2) return;

    window.chart1 = new Chart(ctx1.getContext('2d'), {
        type: 'line',
        data: {
            labels: chartLabels,
            datasets: [{
                label: "Interventions",
                data: chartData,
                backgroundColor: 'rgba(0,123,255,0.2)',
                borderColor: 'rgba(0,123,255,1)',
                borderWidth: 2, tension: 0.4, fill: true
            }]
        },
        options: {
            responsive: true,
            plugins: { legend: { display: true } },
            scales: { y: { beginAtZero: true } }
        }
    });

    window.chart2 = new Chart(ctx2.getContext('2d'), {
        type: 'bar',
        data: {
            labels: ['Planifiées', 'En cours', 'Terminées'],
            datasets: [{
                data: [intvAttente, intvCours, intvTermine],
                backgroundColor: ['#e74c3c', '#f39c12', '#27ae60']
            }]
        },
        options: {
            responsive: true,
            plugins: { legend: { display: false } },
            scales: { y: { beginAtZero: true } }
        }
    });
}

function filterTable(tableId, query) {
    document.querySelectorAll('#' + tableId + ' tbody tr').forEach(row => {
        row.style.display = row.textContent.toLowerCase().includes(query.toLowerCase()) ? '' : 'none';
    });
}

window.onclick = function(e) {
    document.querySelectorAll('.modal').forEach(m => {
        if (e.target === m) m.style.display = 'none';
    });
};

document.addEventListener('DOMContentLoaded', function () {
    const urlParams = new URLSearchParams(window.location.search);
    const section = urlParams.get('section') || 'dashboard';
    showSection(section);
});