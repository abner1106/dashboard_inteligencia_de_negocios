// dashboard.js
document.addEventListener('DOMContentLoaded', () => {
    if (window.ChartDataLabels) {
        Chart.register(ChartDataLabels);
    }

    const getData = (id) => {
        const el = document.getElementById(id);
        if (!el) return [];
        try {
            return JSON.parse(el.textContent);
        } catch (e) {
            console.error('Error parseando JSON de', id);
            return [];
        }
    };

    const formatCurrency = (value) =>
        new Intl.NumberFormat('es-MX', {
            style: 'currency',
            currency: 'MXN',
            minimumFractionDigits: 0,
            maximumFractionDigits: 0
        }).format(value);

    const formatNumber = (value) =>
        new Intl.NumberFormat('es-MX').format(value);

    const shortName = (name, maxLen = 22) =>
        name.length > maxLen ? name.substring(0, maxLen) + '…' : name;

    const monthNames = ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic'];
    const formatMonth = (ym) => {
        const [year, month] = ym.split('-');
        return `${monthNames[parseInt(month, 10) - 1]} ${year.slice(2)}`;
    };

    Chart.defaults.color = '#94a3b8';
    Chart.defaults.borderColor = 'rgba(56, 189, 248, 0.08)';
    Chart.defaults.font.family = "'Inter', sans-serif";
    Chart.defaults.font.size = 12;
    Chart.defaults.plugins.tooltip.backgroundColor = 'rgba(15, 23, 42, 0.95)';
    Chart.defaults.plugins.tooltip.borderColor = 'rgba(6, 182, 212, 0.3)';
    Chart.defaults.plugins.tooltip.borderWidth = 1;
    Chart.defaults.plugins.tooltip.cornerRadius = 10;
    Chart.defaults.plugins.tooltip.padding = 12;

    const palette = {
        cyan: '#06b6d4',
        cyanLight: '#22d3ee',
        green: '#10b981',
        greenLight: '#34d399',
        purple: '#8b5cf6',
        amber: '#f59e0b',
        rose: '#f43f5e',
        blue: '#3b82f6'
    };

    const chartColors = ['#06b6d4', '#10b981', '#8b5cf6', '#f59e0b', '#f43f5e', '#3b82f6'];

    const productos = getData('data-productos');
    const empleados = getData('data-empleados');
    const sucursales = getData('data-sucursales');
    const categorias = getData('data-categorias');
    const clientes = getData('data-clientes');
    const sucSeleccionada = getData('data-sucursal-seleccionada') || '';

    const createProductosChart = (data) => {
        const ctx = document.getElementById('chartProductos').getContext('2d');
        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: data.map((d) => shortName(d.descripcion, 24)),
                datasets: [{
                    label: 'Unidades vendidas',
                    data: data.map((d) => Number(d.total)),
                    backgroundColor: chartColors.map((c) => c + '88'),
                    borderColor: chartColors,
                    borderWidth: 1.5,
                    borderRadius: 6,
                    borderSkipped: false
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                indexAxis: 'y',
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        callbacks: {
                            title: (items) => data[items[0].dataIndex].descripcion,
                            label: (context) => `Unidades vendidas: ${formatNumber(context.raw)}`
                        }
                    },
                    datalabels: {
                        color: '#ffffff',
                        font: { weight: '700', size: 15 },
                        anchor: 'center',
                        align: 'center',
                        formatter: (value) => formatNumber(value)
                    }
                },
                scales: {
                    x: { grid: { color: 'rgba(56, 189, 248, 0.05)' }, ticks: { precision: 0 } },
                    y: { grid: { display: false } }
                }
            }
        });
    };

    const createCategoriasChart = (data) => {
        const ctx = document.getElementById('chartCategorias').getContext('2d');

        // Reordenar: Accesorio al final
        const reorderedData = data.filter((d) => !(d.categoria || d.nombre || '').toLowerCase().includes('accesorio'))
            .concat(data.filter((d) => (d.categoria || d.nombre || '').toLowerCase().includes('accesorio')));

        const total = reorderedData.reduce((sum, item) => sum + Number(item.ingreso || 0), 0);
        new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: reorderedData.map((d) => d.categoria || d.nombre),
                datasets: [{
                    data: reorderedData.map((d) => Number(d.ingreso || d.total || 0)),
                    backgroundColor: ['#06b6d4', '#10b981', '#8b5cf6', '#f59e0b', '#f43f5e', '#3b82f6'],
                    borderColor: '#0a0e1a',
                    borderWidth: 3,
                    hoverOffset: 8,
                    spacing: 8
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '62%',
                plugins: {
                    legend: {
                        position: 'right',
                        labels: { usePointStyle: true, pointStyle: 'rectRounded', padding: 16, font: { size: 12 } }
                    },
                    tooltip: {
                        callbacks: {
                            label: (ctx) => `${ctx.label}: ${formatCurrency(ctx.raw)} (${((ctx.raw / total) * 100).toFixed(1)}%)`
                        }
                    },
                    datalabels: {
                        color: '#ffffff',
                        font: { weight: '700', size: 12 },
                        formatter: (value) => formatCurrency(value),
                        clip: false,
                        display: (context) => context.dataset.data[context.dataIndex] > 0,
                        anchor: 'center',
                        align: 'center',
                        offset: 10
                    }
                }
            }
        });
    };

    const createEmpleadosChart = (data) => {
        const ctx = document.getElementById('chartEmpleados').getContext('2d');
        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: data.map((d) => shortName(d.nombre, 18)),
                datasets: [{
                    label: 'Ventas ($)',
                    data: data.map((d) => Number(d.total)),
                    backgroundColor: 'rgba(139, 92, 246, 0.55)',
                    borderColor: '#8b5cf6',
                    borderWidth: 1.5,
                    borderRadius: 6,
                    borderSkipped: false
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        callbacks: {
                            title: (items) => data[items[0].dataIndex].nombre,
                            label: (ctx) => `Ventas: ${formatCurrency(ctx.raw)}`
                        }
                    },
                    datalabels: {
                        color: '#ffffff',
                        font: { weight: '700', size: 14 },
                        formatter: (value) => formatCurrency(value)
                    }
                },
                scales: {
                    x: { grid: { display: false } },
                    y: { grid: { color: 'rgba(56, 189, 248, 0.05)' }, ticks: { callback: (v) => formatCurrency(v) } }
                }
            }
        });
    };

    const createSucursalesChart = (data) => {
        const ctx = document.getElementById('chartSucursales').getContext('2d');
        const labels = data.map((d) => d.nombre.replace('Sucursal ', ''));
        const bgColors = labels.map((label) => {
            if (sucSeleccionada && label === sucSeleccionada.replace('Sucursal ', '')) {
                const grad = ctx.createLinearGradient(0, 0, 0, 320);
                grad.addColorStop(0, '#f43f5e');
                grad.addColorStop(1, '#fb7185');
                return grad;
            }
            const grad = ctx.createLinearGradient(0, 0, 0, 320);
            grad.addColorStop(0, '#06b6d4');
            grad.addColorStop(1, '#0891b2');
            return grad;
        });

        new Chart(ctx, {
            type: 'bar',
            data: {
                labels,
                datasets: [{
                    label: 'Ventas ($)',
                    data: data.map((d) => Number(d.total)),
                    backgroundColor: bgColors,
                    borderColor: '#06b6d4',
                    borderWidth: 1.5,
                    borderRadius: 8,
                    borderSkipped: false
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        callbacks: {
                            label: (ctx) => `Ventas: ${formatCurrency(ctx.raw)}`
                        }
                    },
                    datalabels: {
                        color: '#ffffff',
                        font: { weight: '700', size: 14 },
                        formatter: (value) => formatCurrency(value)
                    }
                },
                scales: {
                    x: { grid: { display: false } },
                    y: { grid: { color: 'rgba(56, 189, 248, 0.05)' }, ticks: { callback: (v) => formatCurrency(v) } }
                }
            }
        });
    };

    const createClientesChart = (data) => {
        const ctx = document.getElementById('chartClientes').getContext('2d');
        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: data.map((d) => shortName(d.nombre, 20)),
                datasets: [{
                    label: 'Total gastado',
                    data: data.map((d) => Number(d.total)),
                    backgroundColor: 'rgba(6, 182, 212, 0.5)',
                    borderColor: '#06b6d4',
                    borderWidth: 1.5,
                    borderRadius: 6,
                    borderSkipped: false
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        callbacks: {
                            title: (items) => data[items[0].dataIndex].nombre,
                            label: (ctx) => `Total gastado: ${formatCurrency(ctx.raw)}`
                        }
                    },
                    datalabels: {
                        color: '#ffffff',
                        font: { weight: '700', size: 14 },
                        formatter: (value) => formatCurrency(value)
                    }
                },
                scales: {
                    x: { grid: { display: false }, ticks: { maxRotation: 45 } },
                    y: { grid: { color: 'rgba(56, 189, 248, 0.05)' }, ticks: { callback: (v) => formatCurrency(v) } }
                }
            }
        });
    };

    const createTendenciaChart = (data, granularidad = 'meses') => {
        const canvas = document.getElementById('chartMesesVentas') || document.getElementById('chartTendencia');
        if (!canvas) return;
        const ctx = canvas.getContext('2d');
        const gradient = ctx.createLinearGradient(0, 0, 0, 320);
        gradient.addColorStop(0, 'rgba(6, 182, 212, 0.25)');
        gradient.addColorStop(1, 'rgba(6, 182, 212, 0.0)');

        const labels = data.map((d) => {
            if (granularidad === 'dias') {
                return d.etiqueta || d.periodo?.split('-')[2] || d.periodo;
            }
            return formatMonth(d.etiqueta || d.periodo);
        });

        new Chart(ctx, {
            type: 'line',
            data: {
                labels,
                datasets: [{
                    label: granularidad === 'dias' ? 'Ventas del día ($)' : 'Ventas del mes ($)',
                    data: data.map((d) => Number(d.total)),
                    borderColor: palette.cyan,
                    backgroundColor: gradient,
                    borderWidth: 2.5,
                    fill: true,
                    tension: 0.4,
                    pointRadius: 4,
                    pointHoverRadius: 7,
                    pointBackgroundColor: palette.cyanLight,
                    pointBorderColor: '#0a0e1a',
                    pointBorderWidth: 2
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        callbacks: {
                            label: (ctx) => `Ventas: ${formatCurrency(ctx.raw)}`
                        }
                    },
                    datalabels: {
                        color: '#ffffff',
                        font: { weight: '700', size: 13 },
                        formatter: (value) => formatCurrency(value),
                        clip: false,
                        display: (context) => context.dataset.data[context.dataIndex] > 0
                    }
                },
                scales: {
                    x: { grid: { display: false } },
                    y: {
                        beginAtZero: true,
                        grid: { color: 'rgba(56, 189, 248, 0.05)' },
                        ticks: { callback: (v) => formatCurrency(v) }
                    }
                }
            }
        });
    };

    if (productos.length) createProductosChart(productos);
    if (categorias.length) createCategoriasChart(categorias);
    if (empleados.length) createEmpleadosChart(empleados);
    if (sucursales.length) createSucursalesChart(sucursales);
    if (clientes.length) createClientesChart(clientes);

    const tendenciaCanvas = document.getElementById('chartMesesVentas') || document.getElementById('chartTendencia');
    if (tendenciaCanvas) {
        const params = new URLSearchParams(window.location.search);
        fetch(`endpoints/ajax_evolucion.php?${params.toString()}`)
            .then((res) => res.json())
            .then((payload) => {
                const rows = payload.data || [];
                const granularidad = payload.granularidad || 'meses';
                if (rows.length) {
                    createTendenciaChart(rows, granularidad);
                }
            })
            .catch((err) => console.error(err));
    }
});