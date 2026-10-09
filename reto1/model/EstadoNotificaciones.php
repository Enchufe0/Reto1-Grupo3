<?php
    namespace enumNotificaciones;

    enum EstadoNotificaciones: string {
        case PENDIENTE = 'Pendiente';
        case EN_PROCESO = 'En proceso';
        case REALIZADA = 'Realizada';
    }
?>
