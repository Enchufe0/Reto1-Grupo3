let todosLosEquipos = [];

if (window.ROL === 'ADMIN') {
    const btnCreate = document.getElementById('btn-crear-equipamiento');
    if (btnCreate) btnCreate.style.display = 'flex';
}

fetch('api/Equipamiento.php')
    .then(res => res.json())
    .then(data => {
        if (!document.getElementById('eq-table-body')) return; 
        todosLosEquipos = data;
        actualizarEstadisticas();
        renderizarTabla(); 
    })
    .catch(err => console.error(err));

function actualizarEstadisticas() {
    document.getElementById('stat-total').textContent = todosLosEquipos.length;
    const categoriasUnicas = new Set(todosLosEquipos.map(e => e.categoria));
    document.getElementById('stat-categories').textContent = categoriasUnicas.size;
    const ubicacionesUnicas = new Set(todosLosEquipos.map(e => e.ubicacion));
    document.getElementById('stat-locations').textContent = ubicacionesUnicas.size;

    const locSelect = document.getElementById('filter-location');
    if(locSelect) {
        locSelect.innerHTML = '<option value="">Ubicación: Todas</option>';
        ubicacionesUnicas.forEach(loc => {
            if(loc) locSelect.innerHTML += `<option value="${loc}">${loc}</option>`;
        });
    }
}

function renderizarTabla() {
    const tbody = document.getElementById('eq-table-body');
    if (!tbody) return;

    const search = document.getElementById('search-input').value.toLowerCase();
    const filterCat = document.getElementById('filter-category').value;
    const filterLoc = document.getElementById('filter-location').value;

    const filtrados = todosLosEquipos.filter(e => {
        const matchSearch = e.nombre.toLowerCase().includes(search) || 
                            e.marca.toLowerCase().includes(search) || 
                            e.modelo.toLowerCase().includes(search) ||
                            e.id_equipamiento.toString().includes(search);
        
        const matchCat = filterCat === '' || e.categoria === filterCat;
        const matchLoc = filterLoc === '' || e.ubicacion === filterLoc;

        return matchSearch && matchCat && matchLoc;
    });

    document.getElementById('eq-showing-text').textContent = `Mostrando ${filtrados.length} de ${todosLosEquipos.length} equipamientos`;

    if (filtrados.length === 0) {
        tbody.innerHTML = '<tr><td colspan="8" style="text-align:center; padding: 20px;">No se encontraron resultados.</td></tr>';
        return;
    }

    let html = '';
    filtrados.forEach(e => {
        let icon = '<i class="fa-solid fa-box"></i>';
        if (e.categoria === 'PORTATIL' || e.categoria === 'SOBREMESA') icon = '<i class="fa-solid fa-laptop"></i>';
        if (e.categoria === 'PERIFERICO') icon = '<i class="fa-solid fa-computer-mouse"></i>';
        if (e.categoria === 'AUDIOVISUAL') icon = '<i class="fa-solid fa-video"></i>';

        let btns = `<div class="eq-actions-wrapper" id="actions-${e.id_equipamiento}">`;
        if (window.ROL === 'ADMIN') {
            btns += `<button class="eq-action-btn eq-btn-edit" onclick="toggleEdit(${e.id_equipamiento})"><i class="fa-solid fa-pen"></i> Editar</button>`;
            btns += `<button class="eq-action-btn eq-btn-delete" onclick="borrarEquipo(${e.id_equipamiento})"><i class="fa-regular fa-trash-can"></i> Eliminar</button>`;
        }
        btns += '</div>';

        // Escape quotes in description to prevent JS errors
        const safeDesc = (e.descripcion || 'Sin descripción').replace(/'/g, "\\'");

        html += `
            <tr id="row-${e.id_equipamiento}">
                <td>${e.id_equipamiento}</td>
                <td>
                    <div class="eq-item-name">
                        <span class="eq-item-icon">${icon}</span>
                        <input type="text" id="edit-nombre-${e.id_equipamiento}" class="inline-edit" value="${e.nombre}" disabled>
                    </div>
                </td>
                <td><input type="text" id="edit-marca-${e.id_equipamiento}" class="inline-edit" value="${e.marca}" disabled></td>
                <td><input type="text" id="edit-modelo-${e.id_equipamiento}" class="inline-edit" value="${e.modelo}" disabled></td>
                <td>
                    <select id="edit-cat-${e.id_equipamiento}" class="inline-edit eq-badge category-select" disabled>
                        <option value="PORTATIL" ${e.categoria === 'PORTATIL' ? 'selected' : ''}>Portátil</option>
                        <option value="SOBREMESA" ${e.categoria === 'SOBREMESA' ? 'selected' : ''}>Sobremesa</option>
                        <option value="PERIFERICO" ${e.categoria === 'PERIFERICO' ? 'selected' : ''}>Periférico</option>
                        <option value="AUDIOVISUAL" ${e.categoria === 'AUDIOVISUAL' ? 'selected' : ''}>Audiovisual</option>
                        <option value="OTROS" ${e.categoria === 'OTROS' ? 'selected' : ''}>Otros</option>
                    </select>
                </td>
                <td>
                    <div class="eq-location">
                        <i class="fa-solid fa-location-dot"></i> ${e.ubicacion || 'Almacén'}
                    </div>
                </td>
                <td>
                    <div class="desc-truncate" onclick="verDescripcion('${safeDesc}')">
                        ${e.descripcion || 'Sin descripción'}
                    </div>
                </td>
                <td>${btns}</td>
            </tr>
        `;
    });
    
    tbody.innerHTML = html;
}

document.getElementById('search-input').addEventListener('input', renderizarTabla);
document.getElementById('filter-category').addEventListener('change', renderizarTabla);
document.getElementById('filter-location').addEventListener('change', renderizarTabla);

// ==========================================
// MODAL POPUP FOR DESCRIPTION
// ==========================================
function verDescripcion(texto) {
    document.getElementById('desc-text-content').textContent = texto;
    document.getElementById('modal-desc').style.display = 'flex';
}

function cerrarModalDesc() {
    document.getElementById('modal-desc').style.display = 'none';
}

// ==========================================
// INLINE EDIT LOGIC
// ==========================================
function toggleEdit(id) {
    const row = document.getElementById(`row-${id}`);
    const nombreInput = document.getElementById(`edit-nombre-${id}`);
    const marcaInput = document.getElementById(`edit-marca-${id}`);
    const modeloInput = document.getElementById(`edit-modelo-${id}`);
    const catSelect = document.getElementById(`edit-cat-${id}`);
    const actionsWrapper = document.getElementById(`actions-${id}`);

    nombreInput.disabled = false;
    marcaInput.disabled = false;
    modeloInput.disabled = false;
    catSelect.disabled = false;
    catSelect.classList.add('is-editing');
    
    row.classList.add('row-editing'); 
    
    actionsWrapper.innerHTML = `
        <button class="eq-action-btn eq-btn-save" onclick="guardarEdicion(${id})"><i class="fa-solid fa-check"></i> Guardar</button>
        <button class="eq-action-btn eq-btn-cancel" onclick="cancelarEdicion(${id})"><i class="fa-solid fa-xmark"></i> Cancelar</button>
    `;
    
    nombreInput.focus();
}

async function guardarEdicion(id) {
    const nombreInput = document.getElementById(`edit-nombre-${id}`);
    const marcaInput = document.getElementById(`edit-marca-${id}`);
    const modeloInput = document.getElementById(`edit-modelo-${id}`);
    const catSelect = document.getElementById(`edit-cat-${id}`);
    const actionsWrapper = document.getElementById(`actions-${id}`);
    const row = document.getElementById(`row-${id}`);

    if (nombreInput.value.trim() === '') {
        alert("El nombre no puede estar vacío");
        return;
    }

    actionsWrapper.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Guardando...';

    const equipoOriginal = todosLosEquipos.find(e => e.id_equipamiento == id);

    const body = {
        nombre: nombreInput.value,
        marca: marcaInput.value,
        modelo: modeloInput.value,
        categoria: catSelect.value || 'OTROS',
        descripcion: equipoOriginal.descripcion 
    };

    try {
        const res = await fetch(`api/Equipamiento.php?id=${id}`, {
            method: 'PUT',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(body)
        });

        if (!res.ok) throw new Error("Error al guardar");

        equipoOriginal.nombre = body.nombre;
        equipoOriginal.marca = body.marca;
        equipoOriginal.modelo = body.modelo;
        equipoOriginal.categoria = body.categoria;

        nombreInput.disabled = true;
        marcaInput.disabled = true;
        modeloInput.disabled = true;
        catSelect.disabled = true;
        catSelect.classList.remove('is-editing');
        row.classList.remove('row-editing'); 
        
        actionsWrapper.innerHTML = `
            <button class="eq-action-btn eq-btn-edit" onclick="toggleEdit(${id})"><i class="fa-solid fa-pen"></i> Editar</button>
            <button class="eq-action-btn eq-btn-delete" onclick="borrarEquipo(${id})"><i class="fa-regular fa-trash-can"></i> Eliminar</button>
        `;

    } catch (error) {
        alert("ERROR: " + error.message);
        cancelarEdicion(id);
    }
}

function cancelarEdicion(id) {
    const nombreInput = document.getElementById(`edit-nombre-${id}`);
    const marcaInput = document.getElementById(`edit-marca-${id}`);
    const modeloInput = document.getElementById(`edit-modelo-${id}`);
    const catSelect = document.getElementById(`edit-cat-${id}`);
    const actionsWrapper = document.getElementById(`actions-${id}`);
    const row = document.getElementById(`row-${id}`);

    const equipoOriginal = todosLosEquipos.find(e => e.id_equipamiento == id);
    nombreInput.value = equipoOriginal.nombre;
    marcaInput.value = equipoOriginal.marca;
    modeloInput.value = equipoOriginal.modelo;
    catSelect.value = equipoOriginal.categoria;

    nombreInput.disabled = true;
    marcaInput.disabled = true;
    modeloInput.disabled = true;
    catSelect.disabled = true;
    catSelect.classList.remove('is-editing');
    row.classList.remove('row-editing');

    actionsWrapper.innerHTML = `
        <button class="eq-action-btn eq-btn-edit" onclick="toggleEdit(${id})"><i class="fa-solid fa-pen"></i> Editar</button>
        <button class="eq-action-btn eq-btn-delete" onclick="borrarEquipo(${id})"><i class="fa-regular fa-trash-can"></i> Eliminar</button>
    `;
}

// ==========================================
// CREATE & DELETE LOGIC
// ==========================================
function borrarEquipo(id) {
    if (!confirm('¿Seguro que quieres borrar este equipo?')) return;
    fetch(`api/Equipamiento.php?id=${id}`, { method: 'DELETE' }).then(() => location.reload());
}

function abrirModalCrear() { document.getElementById('modal-crear').style.display = 'flex'; }
function cerrarModalCrear() { document.getElementById('modal-crear').style.display = 'none'; }

const formCrear = document.getElementById('form-crear-eq');
if (formCrear) {
    formCrear.addEventListener('submit', async (e) => {
        e.preventDefault();
        
        const body = {
            nombre: document.getElementById('new-nombre').value,
            marca: document.getElementById('new-marca').value,
            modelo: document.getElementById('new-modelo').value,
            descripcion: document.getElementById('new-desc').value,
            categoria: document.getElementById('new-categoria').value
        };

        try {
            const res = await fetch('api/Equipamiento.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(body)
            });

            // 👇 THIS WILL SHOW THE EXACT ERROR IN AN ALERT BOX 👇
            if (!res.ok) {
                const text = await res.text();
                try {
                    const json = JSON.parse(text);
                    alert("❌ ERROR DEL SERVIDOR: " + json.error);
                } catch(e) {
                    alert("❌ ERROR GRAVE (PHP Crash): " + text.substring(0, 150));
                }
                return;
            }

            // If everything is OK
            cerrarModalCrear();
            location.reload();

        } catch (error) {
            alert("❌ ERROR DE CONEXIÓN: " + error.message);
        }
    });
}