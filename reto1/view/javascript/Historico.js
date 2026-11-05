let todosLosEquipos = [];
let currentCategoryFilter = "";

// Helper para quitar tildes en la búsqueda
function normalizarTexto(texto) {
    if (!texto) return '';
    return texto.toString().toLowerCase().normalize("NFD").replace(/[\u0300-\u036f]/g, "");
}

// 1. Cargar Equipos al entrar a la página
fetch('api/Equipamiento.php')
    .then(res => res.json())
    .then(data => {
        if (!document.getElementById('historico-table-body')) return; 
        todosLosEquipos = data;
        renderizarTablaHistorico(); 
    })
    .catch(err => console.error("Error al cargar equipos:", err));

// 2. Lógica de las pestañas
document.querySelectorAll('.eq-tab').forEach(tab => {
    tab.addEventListener('click', (e) => {
        document.querySelectorAll('.eq-tab').forEach(t => t.classList.remove('active'));
        e.target.classList.add('active');
        currentCategoryFilter = e.target.getAttribute('data-category');
        renderizarTablaHistorico();
    });
});

// 3. Dibujar la tabla principal
function renderizarTablaHistorico() {
    const tbody = document.getElementById('historico-table-body');
    if (!tbody) return;

    const search = normalizarTexto(document.getElementById('search-input').value);

    // Filtramos solo por búsqueda y categoría
    const filtrados = todosLosEquipos.filter(e => {
        const matchSearch = normalizarTexto(e.nombre).includes(search) || 
                            normalizarTexto(e.marca).includes(search) || 
                            normalizarTexto(e.id_equipamiento).includes(search);
        const matchCat = currentCategoryFilter === '' || e.categoria === currentCategoryFilter;
        
        return matchSearch && matchCat;
    });

    if (filtrados.length === 0) {
        tbody.innerHTML = '<tr><td colspan="5" style="text-align:center; padding: 20px;">No se encontraron resultados.</td></tr>';
        return;
    }

    let html = '';
    filtrados.forEach(e => {
        let icon = '<i class="fa-solid fa-box"></i>';
        if (e.categoria === 'PORTATIL' || e.categoria === 'SOBREMESA') icon = '<i class="fa-solid fa-laptop"></i>';
        if (e.categoria === 'PERIFERICO') icon = '<i class="fa-solid fa-computer-mouse"></i>';
        if (e.categoria === 'AUDIOVISUAL') icon = '<i class="fa-solid fa-video"></i>';

        html += `
            <tr id="row-${e.id_equipamiento}">
                <td>${e.id_equipamiento}</td>
                <td>
                    <div class="eq-item-name">
                        <span class="eq-item-icon">${icon}</span> ${e.nombre}
                    </div>
                </td>
                <td>${e.marca}</td>
                <td>
                    <div class="eq-location" style="font-weight: 500; color: #111827;">
                        <i class="fa-solid fa-location-dot" style="color: #2563eb;"></i> ${e.ubicacion || 'Almacén'}
                    </div>
                </td>
                <td style="text-align: right;">
                    <button class="eq-action-btn eq-btn-edit" onclick="verHistorialExpandido(${e.id_equipamiento})">
                        <i class="fa-solid fa-route"></i> Ver más ubicaciones
                    </button>
                </td>
            </tr>
            <tr id="history-row-${e.id_equipamiento}" style="display: none; background: #fffdf5;">
                <td colspan="5" id="history-content-${e.id_equipamiento}" style="padding: 20px;"></td>
            </tr>
        `;
    });
    
    tbody.innerHTML = html;
}

// Añadir evento al buscador (ya no hay filtro de ubicación)
document.getElementById('search-input').addEventListener('input', renderizarTablaHistorico);

// 4. Función para desplegar la línea de tiempo
async function verHistorialExpandido(id) {
    const historyRow = document.getElementById(`history-row-${id}`);
    const contentDiv = document.getElementById(`history-content-${id}`);

    // Si ya está abierto, lo cerramos
    if (historyRow.style.display === 'table-row') {
        historyRow.style.display = 'none';
        return;
    }

    // Mostramos la fila y ponemos "Cargando..."
    historyRow.style.display = 'table-row';
    contentDiv.innerHTML = '<p style="text-align:center;">Buscando movimientos...</p>';

    try {
        const res = await fetch(`api/Historico.php?id_equipamiento=${id}`);
        const datos = await res.json();

        if (!res.ok) throw new Error(datos.error || 'Error al obtener historial');
        
        if (datos.length === 0) {
            contentDiv.innerHTML = '<p style="text-align:center; color: orange;">No hay movimientos registrados para este equipo.</p>';
            return;
        }

        // Dibujar la línea de tiempo horizontal
        let html = '<div class="horizontal-timeline">';

        datos.forEach((h, index) => {
            const fInicio = new Date(h.fecha_inicio);
            const fFin = h.fecha_fin ? new Date(h.fecha_fin) : null;
            const esActual = (fFin === null);

            const formatFecha = (fecha) => {
                return fecha.toLocaleString('es-ES', { weekday: 'short', day: 'numeric', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit' });
            };

            html += `
                <div class="ht-node">
                    <div class="ht-icon ${esActual ? 'current' : ''}">
                        <i class="fa-solid fa-location-dot"></i>
                    </div>
                    <div class="ht-location">${h.ubicacion}</div>
                    <div class="ht-date">${formatFecha(fInicio)}</div>
                </div>
            `;

            if (index < datos.length - 1) {
                html += `<div class="ht-line"></div>`;
            }
        });

        html += '</div>';
        contentDiv.innerHTML = html;

    } catch (err) {
        contentDiv.innerHTML = `<p style="text-align:center; color: red;">Error: ${err.message}</p>`;
    }
}