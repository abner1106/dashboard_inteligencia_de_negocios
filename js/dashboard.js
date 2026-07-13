// dashboard.js
document.addEventListener('DOMContentLoaded', () => {
    // Función para parsear datos desde un script JSON
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

    // 1. Productos más vendidos
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
                indexAxis: 'y',  // Barras horizontales
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } }
            }
        });
    }

    // 2. Empleados que más venden
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
                plugins: { legend: { display: false } }
            }
        });
    }

    // 3. Sucursales
    const sucursales = getData('data-sucursales');
    if (sucursales.length) {
        new Chart(document.getElementById('chartSucursales'), {
            type: 'bar',
            data: {
                labels: sucursales.map(s => s.nombre),
                datasets: [{
                    label: 'Ventas ($)',
                    data: sucursales.map(s => s.total),
                    backgroundColor: '#e67e22',
                    borderRadius: 6
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } }
            }
        });
    }

    // 4. Ingresos por categoría
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
                plugins: { legend: { display: false } }
            }
        });
    }

    // 5. Evolución de ventas mensuales (línea)
    const mesesVentas = getData('data-mesesVentas');
    if (mesesVentas.length) {
        new Chart(document.getElementById('chartMesesVentas'), {
            type: 'line',
            data: {
                labels: mesesVentas.map(m => m.mes),
                datasets: [{
                    label: 'Total vendido ($)',
                    data: mesesVentas.map(m => m.total),
                    borderColor: '#2980b9',
                    backgroundColor: 'rgba(41, 128, 185, 0.1)',
                    fill: true,
                    tension: 0.3,
                    pointBackgroundColor: '#2980b9'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { position: 'top' } }
            }
        });
    }

    // 6. Demanda mensual (unidades) - línea con área
    const mesesDemanda = getData('data-mesesDemanda');
    if (mesesDemanda.length) {
        new Chart(document.getElementById('chartMesesDemanda'), {
            type: 'line',
            data: {
                labels: mesesDemanda.map(m => m.mes),
                datasets: [{
                    label: 'Unidades vendidas',
                    data: mesesDemanda.map(m => m.total),
                    borderColor: '#8e44ad',
                    backgroundColor: 'rgba(142, 68, 173, 0.1)',
                    fill: true,
                    tension: 0.3,
                    pointBackgroundColor: '#8e44ad'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { position: 'top' } }
            }
        });
    }
});

let chartPersonalizado;

document.getElementById('btn-consultar').addEventListener('click', () => {
    const dimension = document.getElementById('dimension').value;
    const metrica = document.getElementById('metrica').value;
    const ini = document.getElementById('fecha-ini').value;
    const fin = document.getElementById('fecha-fin').value;

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
        })
        .catch(err => console.error(err));
});