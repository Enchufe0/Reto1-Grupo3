// =========================================================
// 1. CONSTANTES Y ELEMENTOS DEL DOM
// =========================================================

const urlAPI = 'http://localhost/Reto1-Grupo3/reto1/api/Ubicaciones.php';
const contenedor = document.getElementById('contenedorUbicaciones');

// Botón principal
const btnAnadir = document.getElementById('btnAñadir');

// Elementos del Pop-Up: AÑADIR
const modalAnadir = document.getElementById('modalAnadir');
const formAnadir = document.getElementById('formAnadir');
const btnCancelarAnadir = document.getElementById('btnCancelarAnadir');
const inputNuevoNombre = document.getElementById('nuevoNombre');
const inputNuevaDesc = document.getElementById('nuevaDesc');

// Elementos del Pop-Up: EDITAR
const modalEditar = document.getElementById('modalEditar');
const formEditar = document.getElementById('formEditar');
const btnCancelarEditar = document.getElementById('btnCancelarEditar');
const inputEditarId = document.getElementById('editarId');
const inputEditarNombre = document.getElementById('editarNombre');
const inputEditarDesc = document.getElementById('editarDesc');

// Elementos del Pop-Up: ELIMINAR
const modalEliminar = document.getElementById('modalEliminar');
const btnCancelarEliminar = document.getElementById('btnCancelarEliminar');
const btnConfirmarEliminar = document.getElementById('btnConfirmarEliminar');
const inputEliminarId = document.getElementById('eliminarId');


// =========================================================
// 2. GET: LEER Y PINTAR UBICACIONES
// =========================================================
async function cargarUbicaciones() {
    try {
        const respuesta = await fetch(urlAPI);
        const ubicaciones = await respuesta.json();
        
        contenedor.innerHTML = '';

        ubicaciones.forEach(ubicacion => {
            contenedor.innerHTML += `
                <details class="desplegable-planta">
                    <summary>
                        <span class="titulo-alineado">
                            <span class="nombre-aula">${ubicacion.nombre} |</span>
                            <span class="descripcion-aula">${ubicacion.descripcion}</span>
                        </span>
                        <div class="ubi-boton-editar-eliminar">
                            <button class="ubi-boton-editar" 
                                data-id="${ubicacion.id_ubicacion}" 
                                data-nombre="${ubicacion.nombre}" 
                                data-desc="${ubicacion.descripcion}">Editar</button>
                                
                            <button class="ubi-boton-eliminar" 
                                data-id="${ubicacion.id_ubicacion}">Eliminar</button>
                        </div>
                    </summary>
                    <div class="contenido-desplegable">
                        <p>Aquí irán los equipos en el futuro...</p>
                    </div>
                </details>
            `;
        });
    } catch (error) {
        console.error("Error al cargar la base de datos:", error);
    }
}


// =========================================================
// 3. GESTIÓN DE POP-UPS (ABRIR Y CERRAR)
// =========================================================

// Abrir Pop-Up de Añadir
btnAnadir.addEventListener('click', () => {
    formAnadir.reset();
    modalAnadir.style.display = 'flex';
});

// Cerrar Pop-Ups con el botón Cancelar
btnCancelarAnadir.addEventListener('click', () => {
    modalAnadir.style.display = 'none';
});

btnCancelarEditar.addEventListener('click', () => {
    modalEditar.style.display = 'none';
});

btnCancelarEliminar.addEventListener('click', () => {
    modalEliminar.style.display = 'none';
});

// Abrir Pop-Ups de Editar y Eliminar (Escuchando los clics dentro de la tabla)
contenedor.addEventListener('click', (evento) => {
    
    // Si hace clic en Editar
    if (evento.target.classList.contains('ubi-boton-editar')) {
        inputEditarId.value = evento.target.getAttribute('data-id');
        inputEditarNombre.value = evento.target.getAttribute('data-nombre');
        inputEditarDesc.value = evento.target.getAttribute('data-desc');
        modalEditar.style.display = 'flex';
    }

    // Si hace clic en Eliminar
    if (evento.target.classList.contains('ubi-boton-eliminar')) {
        inputEliminarId.value = evento.target.getAttribute('data-id');
        modalEliminar.style.display = 'flex';
    }
});


// =========================================================
// 4. POST: CREAR NUEVA UBICACIÓN
// =========================================================
formAnadir.addEventListener('submit', async (evento) => {
    evento.preventDefault();
    
    const datos = {
        nombre: inputNuevoNombre.value,
        descripcion: inputNuevaDesc.value
    };

    try {
        const respuesta = await fetch(urlAPI, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(datos)
        });

        if (respuesta.ok) {
            modalAnadir.style.display = 'none';
            cargarUbicaciones(); 
        }
    } catch (error) {
        console.error("Error al añadir:", error);
    }
});


// =========================================================
// 5. PUT: ACTUALIZAR UBICACIÓN
// =========================================================
formEditar.addEventListener('submit', async (evento) => {
    evento.preventDefault();
    
    const datos = {
        id_ubicacion: inputEditarId.value,
        nombre: inputEditarNombre.value,
        descripcion: inputEditarDesc.value
    };

    try {
        const respuesta = await fetch(urlAPI, {
            method: 'PUT',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(datos)
        });

        if (respuesta.ok) {
            modalEditar.style.display = 'none';
            cargarUbicaciones(); 
        }
    } catch (error) {
        console.error("Error al editar:", error);
    }
});


// =========================================================
// 6. DELETE: ELIMINAR UBICACIÓN
// =========================================================
btnConfirmarEliminar.addEventListener('click', async () => {
    const id = inputEliminarId.value;
    
    try {
        const respuesta = await fetch(`${urlAPI}?id=${id}`, {
            method: 'DELETE'
        });

        if (respuesta.ok) {
            modalEliminar.style.display = 'none';
            cargarUbicaciones(); 
        }
    } catch (error) {
        console.error("Error al eliminar:", error);
    }
});


// =========================================================
// INICIO: Arrancar al cargar la página
// =========================================================
document.addEventListener('DOMContentLoaded', cargarUbicaciones);