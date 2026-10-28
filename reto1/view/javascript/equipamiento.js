let todosLosEquipos = [];

setTimeout(() => {
    if (window.ROL === 'ADMIN') {
        const btnCreate = document.getElementById('btn-crear-equipamiento');
        if (btnCreate) btnCreate.style.display = 'flex';
    }

    fetch('api/Equipamiento.php')
        .then(res => res.json())
        .then(data => {
            const tbody = document.getElementById('eq-table-body');
            if (data.length === 0) {
                tbody.innerHTML = '<tr><td colspan="7" style="text-align:center; padding: 20px;">No hay equipos.</td></tr>';
                return;
            }

            todosLosEquipos = data;

            document.getElementById('stat-total').textContent = data.length;
            const categoriasUnicas = new Set(data.map(e => e.categoria));
            document.getElementById('stat-categories').textContent = categoriasUnicas.size;
            const ubicacionesUnicas = new Set(data.map(e => e.ubicacion));
            document.getElementById('stat-locations').textContent = ubicacionesUnicas.size;

            let html = '';
            data.forEach(e => {
                let icon = '<i class="fa-solid fa-box"></i>';
                if (e.categoria === 'PORTATIL' || e.categoria === 'SOBREMESA') icon = '<i class="fa-solid fa-laptop"></i>';
                if (e.categoria === 'PERIFERICO') icon = '<i class="fa-solid fa-computer-mouse"></i>';
                if (e.categoria === 'AUDIOVISUAL') icon = '<i class="fa-solid fa-video"></i>';

                let btns = '<div class="eq-actions-wrapper">';
                if (window.ROL === 'ADMIN') {
                    btns += `<button id="btn-edit-${e.id_equipamiento}" class="eq-action-btn eq-btn-edit" onclick="toggleEdit(${e.id_equipamiento})"><i class="fa-solid fa-pen"></i> Editar</button>`;
                    btns += `<button class="eq-action-btn eq-btn-delete" onclick="borrarEquipo(${e.id_equipamiento})"><i class="fa-regular fa-trash-can"></i> Eliminar</button>`;
                }
                btns += '</div>';

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
        .catch(err => console.error(err));
}, 200);

// ==========================================
// INLINE EDIT LOGIC
// ==========================================
async function toggleEdit(id) {
    const row = document.getElementById(`row-${id}`);
    const nombreInput = document.getElementById(`edit-nombre-${id}`);
    const marcaInput = document.getElementById(`edit-marca-${id}`);
    const catSelect = document.getElementById(`edit-cat-${id}`);
    const btn = document.getElementById(`btn-edit-${id}`);

    if (nombreInput.disabled) {
        // --- 1. ENABLE EDIT MODE ---
        nombreInput.disabled = false;
        marcaInput.disabled = false;
        catSelect.disabled = false;
        
        // Highlight the row (Yellow background)
        row.classList.add('row-editing');
        
        btn.innerHTML = '<i class="fa-solid fa-check"></i> Guardar';
        btn.classList.add('eq-btn-save');
        btn.classList.remove('eq-btn-edit');
        nombreInput.focus();

    } else {
        // --- 2. SAVE CHANGES ---
        if (nombreInput.value.trim() === '') {
            alert("El nombre no puede estar vacío");
            return;
        }

        const originalBtnHtml = btn.innerHTML;
        btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Guardando...';
        btn.disabled = true;

        const equipoOriginal = todosLosEquipos.find(e => e.id_equipamiento == id);

        const body = {
            nombre: nombreInput.value,
            marca: marcaInput.value,
            categoria: catSelect.value || 'OTROS', // Fallback if empty
            descripcion: equipoOriginal.descripcion,
            modelo: equipoOriginal.modelo
        };

        try {
            const res = await fetch(`api/Equipamiento.php?id=${id}`, {
                method: 'PUT',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(body)
            });

            if (!res.ok) {
                const text = await res.text();
                try {
                    const json = JSON.parse(text);
                    throw new Error(json.error || "Error desconocido");
                } catch(e) {
                    throw new Error("PHP Error: " + text.substring(0, 100));
                }
            }

            // Success! Revert to normal
            nombreInput.disabled = true;
            marcaInput.disabled = true;
            catSelect.disabled = true;
            row.classList.remove('row-editing'); // Remove highlight
            
            btn.innerHTML = '<i class="fa-solid fa-pen"></i> Editar';
            btn.classList.remove('eq-btn-save');
            btn.classList.add('eq-btn-edit');
            btn.disabled = false;

        } catch (error) {
            alert("ERROR: " + error.message);
            btn.innerHTML = originalBtnHtml;
            btn.disabled = false;
        }
    }
}

// ... (Delete and Create logic remains the same) ...
function borrarEquipo(id) {
    if (!confirm('¿Seguro que quieres borrar este equipo?')) return;
    fetch(`api/Equipamiento.php?id=${id}`, { method: 'DELETE' }).then(() => location.reload());
}

function abrirModalCrear() { document.getElementById('modal-crear').style.display = 'flex'; }
function cerrarModalCrear() { document.getElementById('modal-crear').style.display = 'none'; }

document.getElementById('form-crear-eq')?.addEventListener('submit', (e) => {
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