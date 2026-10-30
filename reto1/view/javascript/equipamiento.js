let todosLosEquipos = [];

// ============== Iconos, tildes etc..==============//

// Quitar tildes en la busqueda
function normalizarTexto(texto) {
    if (!texto) return '';
    return texto.toString()
        .toLowerCase()
        .normalize("NFD") 
        .replace(/[\u0300-\u036f]/g, ""); // Elimina las tildes
}
// Obtener el icono segun la categoría
function obtenerIconoHTML(categoria) {
    if (categoria === 'PORTATIL' || categoria === 'SOBREMESA') return '<i class="fa-solid fa-laptop"></i>';
    if (categoria === 'PERIFERICO') return '<i class="fa-solid fa-computer-mouse"></i>';
    if (categoria === 'AUDIOVISUAL') return '<i class="fa-solid fa-video"></i>';
    return '<i class="fa-solid fa-box"></i>';
}

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

    // 3. Usar el normalizador en la búsqueda
    const search = normalizarTexto(document.getElementById('search-input').value);
    const filterCat = document.getElementById('filter-category').value;
    const filterLoc = document.getElementById('filter-location').value;

    const filtrados = todosLosEquipos.filter(e => {
        const matchSearch = normalizarTexto(e.nombre).includes(search) || 
                            normalizarTexto(e.marca).includes(search) || 
                            normalizarTexto(e.modelo).includes(search) ||
                            normalizarTexto(e.id_equipamiento).includes(search);
        
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
        let btns = `<div class="eq-actions-wrapper" id="actions-${e.id_equipamiento}">`;
        if (window.ROL === 'ADMIN') {
            btns += `<button class="eq-action-btn eq-btn-edit" onclick="toggleEdit(${e.id_equipamiento})"><i class="fa-solid fa-pen"></i> Editar</button>`;
            btns += `<button class="eq-action-btn eq-btn-delete" onclick="borrarEquipo(${e.id_equipamiento})"><i class="fa-regular fa-trash-can"></i> Eliminar</button>`;
        }
        btns += '</div>';

        const safeDesc = (e.descripcion || 'Sin descripción').replace(/'/g, "\\'");
        
        // 4. Usar el helper del icono
        const iconHTML = obtenerIconoHTML(e.categoria);

        html += `
            <tr id="row-${e.id_equipamiento}">
                <td>${e.id_equipamiento}</td>
                <td>
                    <div class="eq-item-name">
                        <span class="eq-item-icon" id="icon-${e.id_equipamiento}">${iconHTML}</span>
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
                    <div class="desc-truncate" id="desc-div-${e.id_equipamiento}" onclick="verDescripcion('${safeDesc}')">
                        ${e.descripcion || 'Sin descripción'}
                    </div>
                    <input type="text" id="edit-desc-${e.id_equipamiento}" class="inline-edit" value="${e.descripcion || ''}" disabled style="display: none;">
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
    const descDiv = document.getElementById(`desc-div-${id}`);
    const descInput = document.getElementById(`edit-desc-${id}`);
    const actionsWrapper = document.getElementById(`actions-${id}`);

    nombreInput.disabled = false;
    marcaInput.disabled = false;
    modeloInput.disabled = false;
    catSelect.disabled = false;
    catSelect.classList.add('is-editing');
    
    descDiv.style.display = 'none';
    descInput.style.display = 'block';
    descInput.disabled = false;
    
    row.classList.add('row-editing'); 
    
    actionsWrapper.innerHTML = `
        <button class="eq-action-btn eq-btn-save" onclick="guardarEdicion(${id})"><i class="fa-solid fa-check"></i> Guardar</button>
        <button class="eq-action-btn eq-btn-cancel" onclick="cancelarEdicion(${id})"><i class="fa-solid fa-xmark"></i> Cancelar</button>
    `;
    
    nombreInput.focus();
}

async function guardarEdicion(id) {
    const row = document.getElementById(`row-${id}`);
    const nombreInput = document.getElementById(`edit-nombre-${id}`);
    const marcaInput = document.getElementById(`edit-marca-${id}`);
    const modeloInput = document.getElementById(`edit-modelo-${id}`);
    const catSelect = document.getElementById(`edit-cat-${id}`);
    const descDiv = document.getElementById(`desc-div-${id}`);
    const descInput = document.getElementById(`edit-desc-${id}`);
    const actionsWrapper = document.getElementById(`actions-${id}`);
    const iconSpan = document.getElementById(`icon-${id}`); // 👈 Referencia al icono

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
        descripcion: descInput.value
    };

    try {
        const res = await fetch(`api/Equipamiento.php?id=${id}`, {
            method: 'PUT',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(body)
        });

        if (!res.ok) throw new Error("Error al guardar");

        // Actualizar array local
        equipoOriginal.nombre = body.nombre;
        equipoOriginal.marca = body.marca;
        equipoOriginal.modelo = body.modelo;
        equipoOriginal.categoria = body.categoria;
        equipoOriginal.descripcion = body.descripcion;

        // Desactivar inputs
        nombreInput.disabled = true;
        marcaInput.disabled = true;
        modeloInput.disabled = true;
        catSelect.disabled = true;
        catSelect.classList.remove('is-editing');
        
        descInput.disabled = true;
        descInput.style.display = 'none';
        descDiv.style.display = 'block';
        descDiv.textContent = body.descripcion || 'Sin descripción';
        
        const newSafeDesc = (body.descripcion || 'Sin descripción').replace(/'/g, "\\'");
        descDiv.setAttribute('onclick', `verDescripcion('${newSafeDesc}')`);

        // 👇 5. CAMBIAR EL ICONO DINÁMICAMENTE 👇
        iconSpan.innerHTML = obtenerIconoHTML(body.categoria);

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
    const row = document.getElementById(`row-${id}`);
    const nombreInput = document.getElementById(`edit-nombre-${id}`);
    const marcaInput = document.getElementById(`edit-marca-${id}`);
    const modeloInput = document.getElementById(`edit-modelo-${id}`);
    const catSelect = document.getElementById(`edit-cat-${id}`);
    const descDiv = document.getElementById(`desc-div-${id}`);
    const descInput = document.getElementById(`edit-desc-${id}`);
    const actionsWrapper = document.getElementById(`actions-${id}`);

    const equipoOriginal = todosLosEquipos.find(e => e.id_equipamiento == id);
    
    nombreInput.value = equipoOriginal.nombre;
    marcaInput.value = equipoOriginal.marca;
    modeloInput.value = equipoOriginal.modelo;
    catSelect.value = equipoOriginal.categoria;
    descInput.value = equipoOriginal.descripcion || '';

    nombreInput.disabled = true;
    marcaInput.disabled = true;
    modeloInput.disabled = true;
    catSelect.disabled = true;
    catSelect.classList.remove('is-editing');
    
    descInput.disabled = true;
    descInput.style.display = 'none';
    descDiv.style.display = 'block';

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

            cerrarModalCrear();
            location.reload();

        } catch (error) {
            alert("❌ ERROR DE CONEXIÓN: " + error.message);
        }
    });
}