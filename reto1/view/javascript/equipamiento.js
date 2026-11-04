let todosLosEquipos = [];

// 1. Show "Crear" button if user is ADMIN
if (window.ROL === 'ADMIN') {
    const btnCreate = document.getElementById('btn-crear-equipamiento');
    if (btnCreate) btnCreate.style.display = 'flex';
}

// 2. Fetch data immediately
fetch('api/Equipamiento.php')
    .then(res => res.json())
    .then(data => {
        const tbody = document.getElementById('eq-table-body');
        if (!tbody) return; 
        
        if (data.length === 0) {
            tbody.innerHTML = '<tr><td colspan="8" style="text-align:center; padding: 20px;">No hay equipos.</td></tr>';
            return;
        }

        todosLosEquipos = data;

        // Update Stats
        document.getElementById('stat-total').textContent = data.length;
        const categoriasUnicas = new Set(data.map(e => e.categoria));
        document.getElementById('stat-categories').textContent = categoriasUnicas.size;
        const ubicacionesUnicas = new Set(data.map(e => e.ubicacion));
        document.getElementById('stat-locations').textContent = ubicacionesUnicas.size;

        // Draw Table
        let html = '';
        data.forEach(e => {
            let icon = '<i class="fa-solid fa-box"></i>';
            if (e.categoria === 'PORTATIL' || e.categoria === 'SOBREMESA') icon = '<i class="fa-solid fa-laptop"></i>';
            if (e.categoria === 'PERIFERICO') icon = '<i class="fa-solid fa-computer-mouse"></i>';
            if (e.categoria === 'AUDIOVISUAL') icon = '<i class="fa-solid fa-video"></i>';

            // Actions Wrapper
            let btns = `<div class="eq-actions-wrapper" id="actions-${e.id_equipamiento}">`;
            if (window.ROL === 'ADMIN') {
                btns += `<button class="eq-action-btn eq-btn-edit" onclick="toggleEdit(${e.id_equipamiento})"><i class="fa-solid fa-pen"></i> Editar</button>`;
                btns += `<button class="eq-action-btn eq-btn-delete" onclick="borrarEquipo(${e.id_equipamiento})"><i class="fa-regular fa-trash-can"></i> Eliminar</button>`;
            }
            btns += '</div>';

            // 👇 HERE IS THE TABLE ROW WITH THE MODELO COLUMN 👇
            html += `
                <tr id="row-${e.id_equipamiento}">
                    <td><input type="checkbox"></td>
                    <td>${e.id_equipamiento}</td>
                    <td>
                        <div class="eq-item-name">
                            <span class="eq-item-icon">${icon}</span>
                            <input type="text" id="edit-nombre-${e.id_equipamiento}" class="inline-edit" value="${e.nombre}" disabled>
                        </div>
                    </td>
                    <td><input type="text" id="edit-marca-${e.id_equipamiento}" class="inline-edit" value="${e.marca}" disabled></td>
                    
                    <!-- 👇 NEW MODELO CELL 👇 -->
                    <td><input type="text" id="edit-modelo-${e.id_equipamiento}" class="inline-edit" value="${e.modelo}" disabled></td>
                    
                    <td>
                        <select id="edit-cat-${e.id_equipamiento}" class="inline-edit eq-badge" disabled style="border: none; background: transparent;">
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
                    <td>${btns}</td>
                </tr>
            `;
        });
        
        tbody.innerHTML = html;
        document.getElementById('eq-showing-text').textContent = `Mostrando ${data.length} de ${data.length} equipamientos`;
    })
    .catch(err => {
        const tbody = document.getElementById('eq-table-body');
        if (tbody) tbody.innerHTML = `<tr><td colspan="8" style="color:red; text-align:center;">Error al cargar datos</td></tr>`;
        console.error(err);
    });

// ==========================================
// 3. INLINE EDIT LOGIC
// ==========================================
function toggleEdit(id) {
    const row = document.getElementById(`row-${id}`);
    const nombreInput = document.getElementById(`edit-nombre-${id}`);
    const marcaInput = document.getElementById(`edit-marca-${id}`);
    const modeloInput = document.getElementById(`edit-modelo-${id}`);
    const catSelect = document.getElementById(`edit-cat-${id}`);
    const actionsWrapper = document.getElementById(`actions-${id}`);

    // Enable inputs
    nombreInput.disabled = false;
    marcaInput.disabled = false;
    modeloInput.disabled = false;
    catSelect.disabled = false;
    
    row.classList.add('row-editing'); 
    
    // 👇 SWAP BUTTONS TO GUARDAR AND CANCELAR 👇
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

        // Success! Revert to normal
        nombreInput.disabled = true;
        marcaInput.disabled = true;
        modeloInput.disabled = true;
        catSelect.disabled = true;
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

    // Revert values to original
    const equipoOriginal = todosLosEquipos.find(e => e.id_equipamiento == id);
    nombreInput.value = equipoOriginal.nombre;
    marcaInput.value = equipoOriginal.marca;
    modeloInput.value = equipoOriginal.modelo;
    catSelect.value = equipoOriginal.categoria;

    // Disable inputs
    nombreInput.disabled = true;
    marcaInput.disabled = true;
    modeloInput.disabled = true;
    catSelect.disabled = true;
    row.classList.remove('row-editing');

    // Restore Edit/Delete buttons
    actionsWrapper.innerHTML = `
        <button class="eq-action-btn eq-btn-edit" onclick="toggleEdit(${id})"><i class="fa-solid fa-pen"></i> Editar</button>
        <button class="eq-action-btn eq-btn-delete" onclick="borrarEquipo(${id})"><i class="fa-regular fa-trash-can"></i> Eliminar</button>
    `;
}

// ==========================================
// 4. CREATE & DELETE LOGIC
// ==========================================
function borrarEquipo(id) {
    if (!confirm('¿Seguro que quieres borrar este equipo?')) return;
    fetch(`api/Equipamiento.php?id=${id}`, { method: 'DELETE' }).then(() => location.reload());
}

function abrirModalCrear() { document.getElementById('modal-crear').style.display = 'flex'; }
function cerrarModalCrear() { document.getElementById('modal-crear').style.display = 'none'; }

const formCrear = document.getElementById('form-crear-eq');
if (formCrear) {
    formCrear.addEventListener('submit', (e) => {
        e.preventDefault();
        const body = {
            nombre: document.getElementById('new-nombre').value,
            marca: document.getElementById('new-marca').value,
            modelo: document.getElementById('new-modelo').value,
            descripcion: document.getElementById('new-desc').value,
            categoria: document.getElementById('new-categoria').value
        };

        fetch('api/Equipamiento.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(body)
        }).then(() => {
            cerrarModalCrear();
            location.reload();
        });
    });
}