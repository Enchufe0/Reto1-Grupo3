const apiUrl = 'http://localhost/Reto1-Grupo3/reto1/api/Usuarios.php';

// Referencias al DOM
const contenedorTarjetas = document.getElementById('contenedorUsuarios');
<<<<<<< HEAD
const btnAnadir = document.getElementById('btnAñadir');
=======
const btnAnadir = document.getElementById('btnAnadir');
>>>>>>> Adrian
const dialogPopup = document.getElementById('popupUsuario');
const formUsuario = document.getElementById('formularioUsuario');
const btnCerrarPopup = document.getElementById('btnCerrarPopUp');
const tituloPopup = document.getElementById('popupTitulo');
const estadisticaTotal = document.getElementById('estadisticaTotal');

// GET
async function cargarUsuarios() {
    try {
        const respuesta = await fetch(apiUrl);
        const usuarios = await respuesta.json();
        
        contenedorTarjetas.innerHTML = ''; 
        if (estadisticaTotal) estadisticaTotal.textContent = usuarios.length; // Actualiza el contador

        usuarios.forEach(usuario => {
            const article = document.createElement('article');
            article.className = 'tarjeta-usu';
            
            
            article.innerHTML = `
                <div class="tarjetaInfo">
                    <p><strong>Nombre:</strong> ${usuario.nombre}</p>
                    <p><strong>Apellido:</strong> ${usuario.apellidos}</p>
                    <p><strong>Usuario:</strong> ${usuario.nombre_usuario}</p>
                </div>
                <div class="tarjetaAccion">
                    <button class="btnEditar" onclick="abrirEditar(${usuario.id_usuario}, '${usuario.nombre}', '${usuario.apellidos}', '${usuario.nombre_usuario}')">Editar</button>
                    <button class="btnEliminar" aria-label="Borrar" onclick="borrarUsuario(${usuario.id_usuario})">❌</button>
                </div>
            `;
            contenedorTarjetas.appendChild(article);
        });
    } catch (error) {
        console.error("Error al cargar:", error);
    }
}

// POPUP CREAR
btnAnadir.addEventListener('click', () => {
    formUsuario.reset();
    document.getElementById('userId').value = '';
    document.getElementById('password').setAttribute('required', 'true'); 
    tituloPopup.textContent = 'AÑADIR USUARIO';
    dialogPopup.showModal();
});

// POPUP EDITAR
window.abrirEditar = function(id, nombre, apellido, username) {
    document.getElementById('userId').value = id;
    document.getElementById('nombre').value = nombre;
    document.getElementById('apellido').value = apellido;
    document.getElementById('username').value = username;
    document.getElementById('password').removeAttribute('required'); 
    
    tituloPopup.textContent = 'EDITAR USUARIO';
    dialogPopup.showModal();
}

// CERRAR POPUP
btnCerrarPopup.addEventListener('click', () => {
    dialogPopup.close();
});

// POST - PUT
formUsuario.addEventListener('submit', async (evento) => {
    evento.preventDefault();

    const id = document.getElementById('userId').value;
    const metodo = id ? 'PUT' : 'POST';
    const datos = {
        nombre: document.getElementById('nombre').value,
        apellidos: document.getElementById('apellido').value,
        nombre_usuario: document.getElementById('username').value,
        password_hash: document.getElementById('password').value
    };

    if (id) datos.id_usuario = id;

    try {
        const respuesta = await fetch(apiUrl, {
            method: metodo,
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(datos)
        });

        if (respuesta.ok) {
            dialogPopup.close();
            cargarUsuarios();
        } else {
            alert("Error al guardar el usuario.");
        }
    } catch (error) {
        console.error("Error al guardar:", error);
    }
});

// DELETE 
window.borrarUsuario = function(id) {
    inputEliminarId.value = id;
    dialogEliminar.showModal();
}

btnConfirmarEliminar.addEventListener('click', async () => {
    const id = inputEliminarId.value;
    
    try {
        const respuesta = await fetch(`${apiUrl}?id=${id}`, { method: 'DELETE' });
        
        if (respuesta.ok) {
            dialogEliminar.close(); // Cerramos la ventana si ha ido bien
            cargarUsuarios();    // Refrescamos la tabla
        } else {
            console.error("Error al borrar en la base de datos.");
        }
    } catch (error) {
        console.error("Fallo de conexión al borrar:", error);
    }
});

document.addEventListener('DOMContentLoaded', cargarUsuarios);