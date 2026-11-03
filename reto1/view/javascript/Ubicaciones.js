const apiUrl = 'http://localhost/Reto1-Grupo3/reto1/api/Ubicaciones.php';

// Referencias al DOM
const contenedorUbicaciones = document.getElementById('contenedorUbicaciones');
const btnAnadir = document.getElementById('btnAnadir'); // ID sin 'ñ'
const dialogPopup = document.getElementById('popupUbicacion');
const formUbicacion = document.getElementById('formularioUbicacion');
const tituloPopup = document.getElementById('popupTitulo');

// 1. GET
async function cargarUbicaciones() {
    try {
        const respuesta = await fetch(apiUrl);
        if (!respuesta.ok) throw new Error("Error en la respuesta del servidor");
        
        const ubicaciones = await respuesta.json();
        contenedorUbicaciones.innerHTML = ''; 

        ubicaciones.forEach(ubicacion => {
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
        console.error("Fallo al cargar ubicaciones. Revisa XAMPP y la URL:", error);
    }
}

// 2. ABRIR POPUP PARA CREAR
if (btnAnadir) {
    btnAnadir.addEventListener('click', () => {
        formUbicacion.reset();
        document.getElementById('ubiId').value = '';
        tituloPopup.textContent = 'Añadir Nueva Ubicación';
        dialogPopup.showModal();
    });
} else {
    console.error("No se encontró el botón btnAnadir en el HTML.");
}

// 3. ABRIR POPUP PARA EDITAR
window.abrirEditar = function(id, nombre, descripcion) {
    document.getElementById('ubiId').value = id;
    document.getElementById('nombre').value = nombre;
    document.getElementById('descripcion').value = descripcion;
    tituloPopup.textContent = 'Editar Ubicación';
    dialogPopup.showModal();
}

// 4. POST - PUT
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
            console.error("El servidor devolvió un error al intentar guardar.");
            alert("Error al guardar la ubicación en la base de datos.");
        }
    } catch (error) {
        console.error("Error al enviar los datos al servidor:", error);
    }
});

// 5. DELETE
window.borrarUbicacion = async function(id) {
    if (confirm("¿Seguro que quieres borrar esta ubicación? Esta acción no se puede deshacer.")) {
        try {
            const respuesta = await fetch(`${apiUrl}?id=${id}`, { method: 'DELETE' });
            if (respuesta.ok) cargarUbicaciones();
        } catch (error) {
            console.error("Error al intentar borrar:", error);
        }
    }
}

document.addEventListener('DOMContentLoaded', cargarUbicaciones);