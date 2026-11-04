let todosLosEquipos = [];
let currentCategoryFilter = ""; // Track active tab

if (window.ROL === 'ADMIN') {
    const btnCreate = document.getElementById('btn-crear-equipamiento');
    if (btnCreate) btnCreate.style.display = 'flex';
}

function normalizarTexto(texto) {
    if (!texto) return '';
    return texto.toString().toLowerCase().normalize("NFD").replace(/[\u0300-\u036f]/g, "");
}

function obtenerIconoHTML(categoria) {
    if (categoria === 'PORTATIL' || categoria === 'SOBREMESA') return '<i class="fa-solid fa-laptop"></i>';
    if (categoria === 'PERIFERICO') return '<i class="fa-solid fa-computer-mouse"></i>';
    if (categoria === 'AUDIOVISUAL') return '<i class="fa-solid fa-video"></i>';
    return '<i class="fa-solid fa-box"></i>';
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

// 👇 Tab Click Listener
document.querySelectorAll('.eq-tab').forEach(tab => {
    tab.addEventListener('click', (e) => {
        // Update active class
        document.querySelectorAll('.eq-tab').forEach(t => t.classList.remove('active'));
        e.target.classList.add('active');

        // Update filter and render
        currentCategoryFilter = e.target.getAttribute('data-category');
        renderizarTabla();
    });
});

function renderizarTabla() {
    const tbody = document.getElementById('eq-table-body');
    if (!tbody) return;

    const search = normalizarTexto(document.getElementById('search-input').value);
    const filterLoc = document.getElementById('filter-location').value;

    const filtrados = todosLosEquipos.filter(e => {
        const matchSearch = normalizarTexto(e.nombre).includes(search) || 
                            normalizarTexto(e.marca).includes(search) || 
                            normalizarTexto(e.modelo).includes(search) ||
                            normalizarTexto(e.id_equipamiento).includes(search);
        
        const matchCat = currentCategoryFilter === '' || e.categoria === currentCategoryFilter;
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
        
        html += `
            <tr id="row-${e.id_equipamiento}">
                <td>${e.id_equipamiento}</td>
                <td>
                    <div class="eq-item-name">
                        <span class="eq-item-icon" id="icon-${e.id_equipamiento}">${obtenerIconoHTML(e.categoria)}</span>
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
                    <!-- 👇 REDIRECT TO HISTORIAL WITH ID 👇 -->
                    <a href="index.html?page=historico&id=${e.id_equipamiento}" class="eq-location-link">
                        <i class="fa-solid fa-location-dot"></i> ${e.ubicacion || 'Almacén'}
                    </a>
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
document.getElementById('filter-location').addEventListener('change', renderizarTabla);

// ==========================================
// INLINE EDIT, CREATE & DELETE (Keep same as before)
// ==========================================
// ... (Paste the toggleEdit, guardarEdicion, cancelarEdicion, borrarEquipo, abrirModalCrear, etc. from your previous working code here) ...