(() => {
const apiUrl = new URL('../../api/UsuariosAPI.php', document.currentScript.src).href;

// Referencias al DOM
const contenedorTarjetas = document.getElementById('contenedorUsuarios');
const btnAnadir = document.getElementById('btnAnadir');
const dialogPopup = document.getElementById('popupUsuario');
const formUsuario = document.getElementById('formularioUsuario');
const btnCerrarPopup = document.getElementById('btnCerrarPopUp');
const tituloPopup = document.getElementById('popupTitulo');
const estadisticaTotal = document.getElementById('estadisticaTotal');
const dialogEliminar = document.getElementById('popupEliminar');
const btnCancelarEliminar = document.getElementById('btnCancelarEliminar');
const btnConfirmarEliminar = document.getElementById('btnConfirmarEliminar');
const inputEliminarId = document.getElementById('eliminarUbiId');

// GET
async function cargarUsuarios() {
    try {
        const respuesta = await fetch(apiUrl);
        if (!respuesta.ok) throw new Error(`Error HTTP ${respuesta.status}`);
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
    const passwordInput = document.getElementById('password');
    passwordInput.required = true;
    passwordInput.placeholder = '';
    tituloPopup.textContent = 'AÑADIR USUARIO';
    dialogPopup.showModal();
});

// POPUP EDITAR
window.abrirEditar = function(id, nombre, apellido, username) {
    document.getElementById('userId').value = id;
    document.getElementById('nombre').value = nombre;
    document.getElementById('apellido').value = apellido;
    document.getElementById('username').value = username;
    const passwordInput = document.getElementById('password');
    passwordInput.value = '';
    passwordInput.required = false;
    passwordInput.placeholder = 'Dejar en blanco para mantener la actual';
    
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
    
    const urlPeticion = id ? `${apiUrl}?id=${id}` : apiUrl;

    const password = document.getElementById('password').value;
    const datos = {
        nombre: document.getElementById('nombre').value,
        apellidos: document.getElementById('apellido').value,
        nombre_usuario: document.getElementById('username').value
    };
    if (password) datos.password = password;

    try {
        const respuesta = await fetch(urlPeticion, { 
            method: metodo,
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(datos)
        });

        const resultado = await respuesta.json();
        if (respuesta.ok) {
            dialogPopup.close();
            await cargarUsuarios();
        } else {
            console.error("Error al guardar el usuario:", resultado.error);
            alert(resultado.error || "Error al guardar el usuario.");
        }
    } catch (error) {
        console.error("Error al comunicarse con la API:", error);
        alert("No se pudo guardar el usuario. Comprueba la conexión e inténtalo de nuevo.");
    }
});

// DELETE 
window.borrarUsuario = function(id) {
    inputEliminarId.value = id;
    dialogEliminar.showModal();
}

btnCancelarEliminar.addEventListener('click', () => {
    dialogEliminar.close();
});

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

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', cargarUsuarios, { once: true });
} else {
    cargarUsuarios();
}
})();