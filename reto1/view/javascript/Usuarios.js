const apiUrl = 'http://localhost/Reto1-Grupo3/reto1/api/Usuarios.php';

// Referencias al DOM
const contenedorTarjetas = document.querySelector('.tarjetaUsuario');
const btnAnadir = document.getElementById('btnAñadir');
const dialogPopup = document.getElementById('popupUsuario');
const formUsuario = document.getElementById('formularioUsuario');
const btnCerrarPopup = document.getElementById('btnCerrarPopUp');
const tituloPopup = document.getElementById('popupTitulo');

// 1. CARGAR USUARIOS (GET)
async function cargarUsuarios() {
    try {
        const respuesta = await fetch(apiUrl);
        const usuarios = await respuesta.json();
        
        contenedorTarjetas.innerHTML = ''; // Limpiamos las tarjetas de ejemplo

        usuarios.forEach(usuario => {
            const article = document.createElement('article');
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

// 2. ABRIR POPUP PARA CREAR
btnAnadir.addEventListener('click', () => {
    formUsuario.reset();
    document.getElementById('userId').value = '';
    tituloPopup.textContent = 'AÑADIR USUARIO';
    dialogPopup.showModal();
});

// 3. ABRIR POPUP PARA EDITAR
window.abrirEditar = function(id, nombre, apellido, username) {
    document.getElementById('userId').value = id;
    document.getElementById('nombre').value = nombre;
    document.getElementById('apellido').value = apellido;
    document.getElementById('username').value = username;
    document.getElementById('password').removeAttribute('required'); // La contraseña es opcional al editar
    document.getElementById('stat-total').textContent = usuarios.length;
    
    tituloPopup.textContent = 'EDITAR USUARIO';
    dialogPopup.showModal();
}

// 4. CERRAR POPUP
btnCerrarPopup.addEventListener('click', () => {
    dialogPopup.close();
});

// 5. GUARDAR O ACTUALIZAR (POST / PUT)
formUsuario.addEventListener('submit', async (e) => {
    e.preventDefault();

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

// 6. BORRAR USUARIO (DELETE)
window.borrarUsuario = async function(id) {
    if (confirm("¿Seguro que quieres borrar este usuario?")) {
        try {
            const respuesta = await fetch(`${apiUrl}?id=${id}`, { method: 'DELETE' });
            if (respuesta.ok) cargarUsuarios();
        } catch (error) {
            console.error("Error al borrar:", error);
        }
    }
}

// Iniciar cargando la lista
document.addEventListener('DOMContentLoaded', cargarUsuarios);