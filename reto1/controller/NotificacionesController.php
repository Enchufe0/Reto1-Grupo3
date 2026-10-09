<?php

use enumNotificaciones\EstadoNotificaciones;

    require_once 'Notificaciones.php';
    require_once 'NotificacionesDAO.php';
    require_once 'EstadoNotificaciones.php';

    class NotificacionesController {

        private NotificacionesDAO $notificacionesDAO;

        public function __construct(PDO $conexion) {
            $this->notificacionesDAO = new NotificacionesDAO($conexion);
        }

        //CREAR
        public function guardarNuevo () {
            //leemos la carta que trae el cliente

            $titulo_cliente = $_POST['titulo'];
            $descripcion_cliente = $_POST['descripcion'];
            $fecha_cliente = $_POST['fecha_creacion'];
            $estado_texto_cliente = $_POST['estado']; //hay que poner las dos para el enum. 
            $estado_enum = EstadoNotificaciones::from($estado_texto_cliente); //aqui lo combertimos en texto
            $id_usuario_cliente = $_POST['id_usuario'];

            //armamos la caja, ponemos id 0 por que se lo pone la bs
            $notificacioNueva = new Notificaciones( 0, $titulo_cliente, $descripcion_cliente, $fecha_cliente, $estado_enum,
                                                    $id_usuario_cliente);

            //mandamos guardar el dao
            $exito = $this->notificacionesDAO->crear($notificacioNueva);

            //si todo va bien redirigimos
            if ($exito) {
                header ("Location: RUTA PENDIENTE");
            } else {
                echo "Error al crear la notificacion";
            }
        }

        //READ TODAS
        public function obtenerTodas() {
            //pedimos el pale completo al DAO
            $listaNotificaciones = $this->notificacionesDAO->obtenerTodas();

            //lo mostramos en el escaparate
            require 'POR DEFINIR';
        }

        //READ POR ID
        public function obtenerPorId() {
            //1 leemos el id que nos piden
            $id_buscado = (int) $_GET['id'];

            //2 le pediomos esa caja exacta al DAO
            $notificacion = $this->notificacionesDAO->obtenerPorId($id_buscado);

            //3 mandamos la caja a al vista
            if ($notificacion) {
                require 'POR DEFINIR';
            } else {
                echo "la notificacion no existe";
            }
        }

        //ACTUALIZAR
        public function actualizar() {
            //leemos la carta con los datos modificados

            $id_notificacion = (int) $_POST['id_notificacion'];
            $titulo_cliente = $_POST['titulo'];
            $descripcion_cliente = $_POST['descripcion'];
            $fecha_cliente = $_POST['fecha_creacion'];
            $estado_texto_cliente = $_POST['estado']; //lo mismo, ponemos el enum asi
            $id_usuario_cliente = (int) $_POST['id_usuario'];

            $estado_enum = EstadoNotificaciones::from($estado_texto_cliente); //y aqui ponemos el enum

            //armamos la caja, esta vez si ponemos el id reald, para la caja
            $notificacionModificada = new Notificaciones(
                $id_notificacion, $titulo_cliente, $descripcion_cliente, $fecha_cliente, $estado_enum, $id_usuario_cliente
            );

            //mandamos a actualizar el DAO
            $exito = $this->notificacionesDAO->actualizar($notificacionModificada);

            //redirigimos y comprobamos
            if ($exito) {
               // header (Location: "POR DEFINIR");
            } else {
                echo "Error al actualizar la notificaciones";
            }
        }

        //DELETE
        public function eliminar() {
            //leemos el id que quiere borrr el cliente
            $id_borrar = (int) $_GET['id'];

            //damos la orden
            $this->notificacionesDAO->borrar($id_borrar());

            //recargamos la lista
            header ("Location: POR DEFINIR");
        }

    }



?>