// Apuntamos al archivo PHP definitivo de la API
const urlAPI = 'http://localhost/Reto1-Grupo3/reto1/api/Ubicaciones.php';
const contenedor = document.getElementById('contenedorUbicaciones');

// Función principal para traer los datos de la base de datos (GET)
async function cargarUbicaciones() {
    try {
        const respuesta = await fetch(urlAPI);
        const ubicaciones = await respuesta.json();

        // Vaciamos el contenedor por si había algo antes
        contenedor.innerHTML = '';

        // Hacemos un bucle para dibujar cada ubicación
        ubicaciones.forEach(ubi => {
            contenedor.innerHTML += `
                <details class="desplegable-planta">
                    <summary>
                        <span class="titulo-alineado">
                            <span class="nombre-aula">${ubi.nombre} |</span>
                            <span class="descripcion-aula">${ubi.descripcion}</span>
                        </span>
                        <div class="ubi-boton-editar-eliminar">
                            <button class="ubi-boton-editar" onclick="abrirModalEditar(${ubi.id_ubicacion})">Editar</button>
                            <button class="ubi-boton-eliminar" onclick="eliminarUbicacion(${ubi.id_ubicacion})">Eliminar</button>
                        </div>
                    </summary>
                    <div class="contenido-desplegable">
                        <p>Aquí irán los equipos en el futuro...</p>
                    </div>
                </details>
            `;
        });
    } catch (error) {
        console.error("Error al cargar la base de datos (revisa que XAMPP esté encendido):", error);
    }
}

// Ejecutamos la función en cuanto el HTML termine de cargar
document.addEventListener('DOMContentLoaded', cargarUbicaciones);