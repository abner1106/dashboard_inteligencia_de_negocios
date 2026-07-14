document.addEventListener('DOMContentLoaded', () => {
    Chart.register(ChartDataLabels);

    const header = document.querySelector('.hero-card');
    let lastScrollY = window.scrollY;

    const manejarScroll = () => {
        const currentScrollY = window.scrollY;
        if (header) {
            if (currentScrollY > lastScrollY && currentScrollY > 80) {
                header.classList.add('is-hidden');
            } else {
                header.classList.remove('is-hidden');
            }
        }
        lastScrollY = currentScrollY;
    };

    window.addEventListener('scroll', manejarScroll, { passive: true });

    const raw = document.getElementById('datos-evolucion')?.textContent || '[]';
    let data = [];
    try { data = JSON.parse(raw); } catch (e) { console.error('No se pudieron cargar los datos', e); }

    const currency = value => '$' + Number(value).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

    const labels = data.map(item => item.mes);
    const values = data.map(item => Number(item.total || 0));

    const ctx = document.getElementById('chartEvolucionVentas').getContext('2d');
    new Chart(ctx, {
        type: 'line',
        data: {
            labels,
            datasets: [{
                label: 'Ventas ($)',
                data: values,
                borderColor: '#2563eb',
                backgroundColor: 'rgba(37, 99, 235, 0.14)',
                borderWidth: 3,
                tension: 0.35,
                fill: true,
                pointBackgroundColor: '#0f172a',
                pointBorderColor: '#ffffff',
                pointBorderWidth: 2,
                pointRadius: 5,
                pointHoverRadius: 7
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                tooltip: {
                    callbacks: {
                        label: context => currency(context.raw)
                    }
                },
                datalabels: {
                    color: '#ffffff',
                    font: { weight: '700', size: 12 },
                    formatter: value => currency(value),
                    anchor: 'end',
                    align: 'top'
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: value => currency(value)
                    }
                }
            }
        }
    });
});
