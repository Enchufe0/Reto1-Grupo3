if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', cargarNotificaciones);
} else {
    cargarNotificaciones();
}

async function cargarNotificaciones() {
    const cuerpoTabla = document.querySelector('.notificaciones-lista tbody');
    if (!cuerpoTabla) return;

    try {
        const respuesta = await fetch('api/Notificaciones.php?mine=1');
        if (!respuesta.ok) {
            throw new Error(`No se pudieron cargar las notificaciones (${respuesta.status}).`);
        }

        const notificaciones = await respuesta.json();
        if (!Array.isArray(notificaciones)) {
            throw new Error('La respuesta de notificaciones no es válida.');
        }

        const idsLeidosGuardados = localStorage.getItem(
            `ordenza-notificaciones-leidas-${window.USUARIO_ID}`
        );
        const idsLeidos = idsLeidosGuardados === null
            ? new Set()
            : new Set(JSON.parse(idsLeidosGuardados).map(String));

        cuerpoTabla.replaceChildren();

        if (notificaciones.length === 0) {
            mostrarMensaje(cuerpoTabla, 'No tienes notificaciones.', 'empty');
            return;
        }

        notificaciones.forEach(notificacion => {
            const leida = idsLeidos.has(String(notificacion.id_notificacion));
            const fila = document.createElement('tr');
            fila.className = leida ? 'notificacion-leida' : 'notificacion-no-leida';

            [
                notificacion.fecha_creacion,
                notificacion.titulo,
                notificacion.descripcion,
                notificacion.estado,
                leida ? 'Leída' : 'No leída'
            ].forEach(valor => {
                const celda = document.createElement('td');
                celda.textContent = valor;
                fila.appendChild(celda);
            });

            cuerpoTabla.appendChild(fila);
        });
    } catch (error) {
        console.error('Error al cargar la lista de notificaciones:', error);
        mostrarMensaje(cuerpoTabla, 'No se pudieron cargar las notificaciones. Inténtalo de nuevo.', 'error');
    }
}

function mostrarMensaje(cuerpoTabla, mensaje, tipo) {
    const fila = document.createElement('tr');
    const celda = document.createElement('td');
    celda.colSpan = 5;
    celda.className = `notificaciones-lista__mensaje notificaciones-lista__mensaje--${tipo}`;
    celda.textContent = mensaje;
    fila.appendChild(celda);
    cuerpoTabla.replaceChildren(fila);
}
