// dashboard.js
document.addEventListener('DOMContentLoaded', () => {
    Chart.register(ChartDataLabels);

    const getData = id => {
        const el = document.getElementById(id);
        if (!el) return [];
        try { return JSON.parse(el.textContent); }
        catch (e) { console.error('Error parseando JSON de', id); return []; }
    };

    // Formateador de moneda
    const currencyFormat = (value) => '$' + value.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

    // Opciones base para datalabels
    const dataLabelOptions = {
        color: '#1a3b5c',
        font: { weight: 'bold', size: 16 },
        anchor: 'center',
        align: 'center'
    };

    // ==================== PRODUCTOS ====================
    const productos = getData('data-productos');
    if (productos.length) {
        new Chart(document.getElementById('chartProductos'), {
            type: 'bar',
            data: {
                labels: productos.map(p => p.descripcion),
                datasets: [{
                    label: 'Unidades vendidas',
                    data: productos.map(p => p.total),
                    backgroundColor: '#3498db',
                    borderRadius: 6
                }]
            },
            options: {
                indexAxis: 'y',
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    datalabels: { ...dataLabelOptions, formatter: v => v.toLocaleString() }
                }
            }
        });
    }

    // ==================== EMPLEADOS ====================
    const empleados = getData('data-empleados');
    if (empleados.length) {
        new Chart(document.getElementById('chartEmpleados'), {
            type: 'bar',
            data: {
                labels: empleados.map(e => e.nombre),
                datasets: [{
                    label: 'Ventas ($)',
                    data: empleados.map(e => e.total),
                    backgroundColor: '#27ae60',
                    borderRadius: 6
                }]
            },
            options: {
                indexAxis: 'y',
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    datalabels: { ...dataLabelOptions, formatter: currencyFormat }
                }
            }
        });
    }

    // ==================== SUCURSALES ====================
    const sucursales = getData('data-sucursales');
    const sucSeleccionada = getData('data-sucursal-seleccionada') || '';
    if (sucursales.length) {
        const labels = sucursales.map(s => s.nombre);
        const data = sucursales.map(s => s.total);
        const backgroundColors = labels.map(n => n === sucSeleccionada ? '#e74c3c' : '#bdc3c7');

        new Chart(document.getElementById('chartSucursales'), {
            type: 'bar',
            data: {
                labels,
                datasets: [{
                    label: 'Ventas ($)',
                    data,
                    backgroundColor: backgroundColors,
                    borderRadius: 6
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    datalabels: { ...dataLabelOptions, formatter: currencyFormat }
                },
                scales: { y: { beginAtZero: true } }
            }
        });
    }

    // ==================== CATEGORÍAS ====================
    const categorias = getData('data-categorias');
    if (categorias.length) {
        new Chart(document.getElementById('chartCategorias'), {
            type: 'bar',
            data: {
                labels: categorias.map(c => c.categoria),
                datasets: [{
                    label: 'Ingresos ($)',
                    data: categorias.map(c => c.ingreso),
                    backgroundColor: ['#9b59b6', '#3498db', '#e74c3c', '#2ecc71'],
                    borderRadius: 6
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    datalabels: { ...dataLabelOptions, formatter: currencyFormat }
                }
            }
        });
    }

    // ==================== EVOLUCIÓN MENSUAL (VERSIÓN LIMPIA) ====================
    let chartMesesVentas = null;
    const evolAnio = document.getElementById('evol-anio');
    const evolProducto = document.getElementById('evol-producto');
    const evolTotalDiv = document.getElementById('evol-total').querySelector('strong');

    const cargarEvolucion = () => {
        const params = new URLSearchParams();
        const anio = evolAnio.value;
        const producto = evolProducto.value;
        if (anio) params.append('anio', anio);
        if (producto) params.append('producto', producto);

        // filtros globales actuales
        const urlParams = new URLSearchParams(window.location.search);
        const fechaIni = urlParams.get('fecha_ini') || '';
        const fechaFin = urlParams.get('fecha_fin') || '';
        const sucursal = urlParams.get('sucursal') || '';
        if (fechaIni) params.append('fecha_ini', fechaIni);
        if (fechaFin) params.append('fecha_fin', fechaFin);
        if (sucursal) params.append('sucursal', sucursal);

        fetch(`endpoints/ajax_evolucion.php?${params.toString()}`)
            .then(res => res.json())
            .then(data => {
                // Actualizar el total
                const total = data.reduce((sum, d) => sum + parseFloat(d.total), 0);
                evolTotalDiv.textContent = currencyFormat(total);

                const labels = data.map(d => d.mes);
                const valores = data.map(d => parseFloat(d.total));

                const ctx = document.getElementById('chartMesesVentas').getContext('2d');
                if (chartMesesVentas) chartMesesVentas.destroy();

                chartMesesVentas = new Chart(ctx, {
                    type: 'line',
                    data: {
                        labels: labels,
                        datasets: [{
                            label: 'Ventas ($)',
                            data: valores,
                            borderColor: '#2c3e50',
                            backgroundColor: 'rgba(44, 62, 80, 0.05)',
                            borderWidth: 2,
                            fill: true,
                            tension: 0.3,
                            pointBackgroundColor: '#2c3e50',
                            pointRadius: 4,
                            pointHoverRadius: 6
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: { display: false },
                            tooltip: {
                                callbacks: {
                                    label: (ctx) => currencyFormat(ctx.raw)
                                }
                            }
                        },
                        scales: {
                            y: {
                                beginAtZero: true,
                                ticks: {
                                    callback: (value) => currencyFormat(value)
                                }
                            }
                        }
                    }
                });
            })
            .catch(err => console.error(err));
    };

    evolAnio.addEventListener('change', cargarEvolucion);
    evolProducto.addEventListener('change', cargarEvolucion);
    cargarEvolucion();

    // ==================== CLIENTES ====================
    const clientes = getData('data-clientes');
    if (clientes.length) {
        new Chart(document.getElementById('chartClientes'), {
            type: 'bar',
            data: {
                labels: clientes.map(c => c.nombre),
                datasets: [{
                    label: 'Total gastado ($)',
                    data: clientes.map(c => c.total),
                    backgroundColor: '#f39c12',
                    borderRadius: 6
                }]
            },
            options: {
                indexAxis: 'y',
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    datalabels: {
                        anchor: 'end',
                        align: 'end',
                        color: '#1a3b5c',
                        font: { weight: 'bold', size: 14 },
                        formatter: currencyFormat
                    }
                }
            }
        });
    }

    // ==================== CONSULTA PERSONALIZADA ====================
    let chartPersonalizado;
    const consultaEstado = document.getElementById('consulta-estado');
    document.getElementById('btn-consultar').addEventListener('click', () => {
        const dimension = document.getElementById('dimension').value;
        const metrica = document.getElementById('metrica').value;
        const ini = document.getElementById('fecha-ini').value;
        const fin = document.getElementById('fecha-fin').value;

        consultaEstado.textContent = 'Consultando...';
        const params = new URLSearchParams({ dimension, metrica });
        if (ini) params.append('fecha_ini', ini);
        if (fin) params.append('fecha_fin', fin);

        fetch(`endpoints/ajax_consulta.php?${params.toString()}`)
            .then(res => res.json())
            .then(data => {
                const ctx = document.getElementById('chartPersonalizado').getContext('2d');
                if (chartPersonalizado) chartPersonalizado.destroy();
                chartPersonalizado = new Chart(ctx, {
                    type: 'bar',
                    data: {
                        labels: data.map(d => d.etiqueta),
                        datasets: [{
                            label: metrica === 'cantidad' ? 'Unidades' : 'Monto ($)',
                            data: data.map(d => parseFloat(d.valor)),
                            backgroundColor: '#16a085',
                            borderRadius: 6
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false
                    }
                });
                consultaEstado.textContent = 'Gráfico actualizado.';
            })
            .catch(err => {
                console.error(err);
                consultaEstado.textContent = 'Error al consultar.';
            });
    });
});