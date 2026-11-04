async function buscarHistorial() {
    const id = document.getElementById('id_equipo').value;
    const resultado = document.getElementById('resultado');

    if (!id) {
        alert("Por favor, introduce un ID válido");
        return;
    }

    resultado.innerHTML = '<div class="eq-table-container"><p style="padding: 20px;">Cargando...</p></div>';

    try {
        const res = await fetch(`api/Historico.php?id_equipamiento=${id}`);
        const datos = await res.json();

        if (!res.ok) {
            throw new Error(datos.error || 'Error al obtener el historial');
        }

        if (datos.length === 0) {
            resultado.innerHTML = `<div class="eq-table-container"><p style="color:orange; padding: 20px;">No hay registros históricos para el equipamiento con ID ${id}.</p></div>`;
            return;
        }

        // We build the table using your eq-table classes
        let html = '<div class="eq-table-container"><table class="eq-table">';
        html += '<thead><tr><th>ID Movimiento</th><th>Ubicación</th><th>Fecha Inicio</th><th>Fecha Fin</th></tr></thead>';
        html += '<tbody>';
        
        datos.forEach(h => {
            // Using the badge class for the "Current Location"
            const fechaFin = h.fecha_fin ? h.fecha_fin : '<span class="eq-badge" style="background:#dcfce7; color:#166534;">Ubicación Actual</span>';
            
            html += `<tr>
                <td>${h.id_historico}</td>
                <td><i class="fa-solid fa-location-dot" style="color: #9ca3af; margin-right: 8px;"></i> ${h.ubicacion}</td>
                <td>${h.fecha_inicio}</td>
                <td>${fechaFin}</td>
            </tr>`;
        });
        
        html += '</tbody></table></div>';
        resultado.innerHTML = html;

    } catch (err) {
        resultado.innerHTML = `<div class="eq-table-container"><p style="color:red; padding: 20px;">Error: ${err.message}</p></div>`;
    }
}