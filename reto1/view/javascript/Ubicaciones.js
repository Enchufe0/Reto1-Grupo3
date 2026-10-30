// =========================================================
// 1. CONSTANTES Y ELEMENTOS DEL DOM
// =========================================================

//const apiUrl = new URL('../../api/Ubicaciones.php', document.currentScript.src);
const apiUrl = 'http://localhost/Reto1-Grupo3/reto1/api/Ubicaciones.php';

// Referencias al DOM
const contenedorUbicaciones = document.getElementById('contenedorUbicaciones');
const btnAnadir = document.getElementById('btnAñadir');
const dialogPopup = document.getElementById('popupUbicacion');
const formUbicacion = document.getElementById('formularioUbicacion');
const btnCerrarPopup = document.getElementById('btnCerrarPopUp');
const tituloPopup = document.getElementById('popupTitulo');

// 1. CARGAR UBICACIONES (GET)
async function cargarUbicaciones() {
    try {
        const respuesta = await fetch(apiUrl);
        const ubicaciones = await respuesta.json();
        
        contenedorUbicaciones.innerHTML = ''; 

        ubicaciones.forEach(ubicacion => {
            // Creamos el elemento como en Usuarios
            const details = document.createElement('details');
            details.className = 'desplegable-planta';
            details.innerHTML = `
                <summary>
                    <span class="titulo-alineado">
                        <span class="nombre-aula">${ubicacion.nombre} |</span>
                        <span class="descripcion-aula">${ubicacion.descripcion}</span>
                    </span>
                    <div class="ubi-boton-editar-eliminar">
                        <button class="ubi-boton-editar" onclick="abrirEditar(${ubicacion.id_ubicacion}, '${ubicacion.nombre}', '${ubicacion.descripcion}')">Editar</button>
                        <button class="ubi-boton-eliminar" onclick="borrarUbicacion(${ubicacion.id_ubicacion})">Eliminar</button>
                    </div>
                </summary>
                <div class="contenido-desplegable">
                    <p>Aquí irán los equipos en el futuro...</p>
                </div>
            `;
            contenedorUbicaciones.appendChild(details);
        });
    } catch (error) {
        console.error("Error al cargar:", error);
    }
}

// 2. ABRIR POPUP PARA CREAR
btnAnadir.addEventListener('click', () => {
    formUbicacion.reset();
    document.getElementById('ubiId').value = '';
    tituloPopup.textContent = 'Añadir Nueva Ubicación';
    dialogPopup.showModal();
});

// 3. ABRIR POPUP PARA EDITAR
window.abrirEditar = function(id, nombre, descripcion) {
    document.getElementById('ubiId').value = id;
    document.getElementById('nombre').value = nombre;
    document.getElementById('descripcion').value = descripcion;
    
    tituloPopup.textContent = 'Editar Ubicación';
    dialogPopup.showModal();
}

// 4. CERRAR POPUP
btnCerrarPopup.addEventListener('click', () => {
    dialogPopup.close();
});

// 5. GUARDAR O ACTUALIZAR (POST / PUT)
formUbicacion.addEventListener('submit', async (evento) => {
    evento.preventDefault();

    const id = document.getElementById('ubiId').value;
    const metodo = id ? 'PUT' : 'POST';
    const datos = {
        nombre: document.getElementById('nombre').value,
        descripcion: document.getElementById('descripcion').value
    };

    if (id) datos.id_ubicacion = id;

    try {
        const respuesta = await fetch(apiUrl, {
            method: metodo,
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(datos)
        });

        if (respuesta.ok) {
            dialogPopup.close();
            cargarUbicaciones();
        } else {
            alert("Error al guardar la ubicación.");
        }
    } catch (error) {
        console.error("Error al guardar:", error);
    }
});

// 6. BORRAR UBICACION (DELETE)
window.borrarUbicacion = async function(id) {
    if (confirm("¿Seguro que quieres borrar esta ubicación? Esta acción no se puede deshacer.")) {
        try {
            const respuesta = await fetch(`${apiUrl}?id=${id}`, { method: 'DELETE' });
            if (respuesta.ok) cargarUbicaciones();
        } catch (error) {
            console.error("Error al borrar:", error);
        }
    }
}

// Iniciar cargando la lista
document.addEventListener('DOMContentLoaded', cargarUbicaciones);