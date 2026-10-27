let todosLosEquipos = [];

async function cargarEquipos() {
    const tbody = document.getElementById('eq-table-body');
    tbody.innerHTML = '<tr><td colspan="7" style="text-align:center; padding: 20px;">Cargando datos...</td></tr>';
    try {
        const res = await fetch('api/Equipamiento.php');
        const datos = await res.json();
        if (!res.ok) throw new Error(datos.error);
        todosLosEquipos = datos;
        actualizarEstadisticas();
        renderizarTabla();
    } catch (err) {
        tbody.innerHTML = `<tr><td colspan="7" style="text-align:center; color: #ef4444; padding: 20px;">Error: ${err.message}</td></tr>`;
    }
}

function actualizarEstadisticas() {
    document.getElementById('stat-total').textContent = todosLosEquipos.length;
    const categoriasUnicas = new Set(todosLosEquipos.map(e => e.categoria));
    document.getElementById('stat-categories').textContent = categoriasUnicas.size;
    const ubicacionesUnicas = new Set(todosLosEquipos.map(e => e.ubicacion));
    document.getElementById('stat-locations').textContent = ubicacionesUnicas.size;
    
    const locSelect = document.getElementById('filter-location');
    locSelect.innerHTML = '<option value="">Ubicación: Todas</option>';
    ubicacionesUnicas.forEach(loc => {
        if(loc) locSelect.innerHTML += `<option value="${loc}">${loc}</option>`;
    });
}

function getIcon(categoria) {
    if (categoria === 'PORTATIL' || categoria === 'SOBREMESA') return '<i class="fa-solid fa-laptop"></i>';
    if (categoria === 'PERIFERICO') return '<i class="fa-solid fa-computer-mouse"></i>';
    if (categoria === 'AUDIOVISUAL') return '<i class="fa-solid fa-video"></i>';
    return '<i class="fa-solid fa-box"></i>';
}

function renderizarTabla() {
    const search = document.getElementById('search-input').value.toLowerCase();
    const filterCat = document.getElementById('filter-category').value;
    const filterLoc = document.getElementById('filter-location').value;
    const tbody = document.getElementById('eq-table-body');
    tbody.innerHTML = '';

    const filtrados = todosLosEquipos.filter(e => {
        const matchesSearch = e.nombre.toLowerCase().includes(search) || e.marca.toLowerCase().includes(search) || e.id_equipamiento.toString().includes(search);
        const matchesCat = filterCat === '' || e.categoria === filterCat;
        const matchesLoc = filterLoc === '' || e.ubicacion === filterLoc;
        return matchesSearch && matchesCat && matchesLoc;
    });

    document.getElementById('eq-showing-text').textContent = `Mostrando ${filtrados.length} de ${todosLosEquipos.length} equipamientos`;

    if (filtrados.length === 0) {
        tbody.innerHTML = '<tr><td colspan="7" style="text-align:center; padding: 30px; color: #6b7280;">No se encontraron resultados</td></tr>';
        return;
    }

    filtrados.forEach(e => {
        let accionesHTML = '<div class="eq-actions-wrapper">';
        
        if (window.ROL === 'ADMIN' || window.ROL === 'USER') {
            accionesHTML += `<button class="eq-action-btn eq-btn-move" onclick="moverEquipo(${e.id_equipamiento})">
                <i class="fa-solid fa-arrow-right-arrow-left"></i> Mover</button>`;
        }
        
        // ADDING THE VERTICAL LINE HERE
        if (window.ROL === 'ADMIN' && window.ROL === 'USER') { 
            accionesHTML += `<div class="action-separator"></div>`;
        }

        if (window.ROL === 'ADMIN') {
            accionesHTML += `<button class="eq-action-btn eq-btn-delete" onclick="borrarEquipo(${e.id_equipamiento})">
                <i class="fa-regular fa-trash-can"></i> Eliminar</button>`;
        }
        accionesHTML += '</div>';

        tbody.innerHTML += `
            <tr>
                <td><input type="checkbox"></td>
                <td>${e.id_equipamiento}</td>
                <td>
                    <div class="eq-item-name">
                        <span class="eq-item-icon">${getIcon(e.categoria)}</span>
                        ${e.nombre}
                    </div>
                </td>
                <td>${e.marca}</td>
                <td><span class="eq-badge">${e.categoria}</span></td>
                <td>
                    <div class="eq-location">
                        <i class="fa-solid fa-location-dot"></i>
                        ${e.ubicacion || 'Almacén'}
                    </div>
                </td>
                <td>${accionesHTML}</td>
            </tr>
        `;
    });
}

document.getElementById('search-input').addEventListener('input', renderizarTabla);
document.getElementById('filter-category').addEventListener('change', renderizarTabla);
document.getElementById('filter-location').addEventListener('change', renderizarTabla);

function abrirModalCrear() { document.getElementById('modal-crear').style.display = 'flex'; }
function cerrarModalCrear() { document.getElementById('modal-crear').style.display = 'none'; }

document.getElementById('form-crear-eq').onsubmit = async (e) => {
    e.preventDefault();
    const body = {
        nombre: document.getElementById('new-nombre').value,
        marca: document.getElementById('new-marca').value,
        modelo: document.getElementById('new-modelo').value,
        descripcion: document.getElementById('new-desc').value,
        categoria: document.getElementById('new-categoria').value
    };
    try {
        const res = await fetch('api/Equipamiento.php', { method: 'POST', body: JSON.stringify(body) });
        if (!res.ok) throw new Error("Error al crear");
        cerrarModalCrear();
        cargarEquipos();
        e.target.reset();
    } catch(err) { alert(err.message); }
};

async function moverEquipo(id) {
    const ubi = prompt("Introduce el ID de la nueva ubicación:");
    if (!ubi) return;
    await fetch(`api/Equipamiento.php?id=${id}`, { method: 'PUT', body: JSON.stringify({ id_ubicacion_actual: parseInt(ubi) }) });
    cargarEquipos();
}

async function borrarEquipo(id) {
    if(!confirm('¿Seguro que quieres borrar este equipo?')) return;
    await fetch(`api/Equipamiento.php?id=${id}`, { method: 'DELETE' });
    cargarEquipos();
}

cargarEquipos();