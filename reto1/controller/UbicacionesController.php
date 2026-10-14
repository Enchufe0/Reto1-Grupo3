<?php

    //necesitas tener a mano al carretillero
    require_once 'Ubicaciones.php';
    require_once 'UbicacionesDAO.php';

    class UbicacionesController {

        //llamas al dao
        private UbicacionesDAO $ubicacionesDAO;

        //al instanciar al controlador le pasamos la conexion
        public function __construct(PDO $conexion) {
            $this->ubicacionesDAO = new UbicacionesDAO($conexion);
        }

        //READ TODAS
        public function obtenerTodas(){
            //llamar al DAO y pedirle todas las ubicaciones
            $listaUbicaciones = $this->ubicacionesDAO->obtenerTodas();

            //mostrar el resultado al cliente
            require 'por definir';
        }


        //CREAR
        public function guardarNuevo() {
            //1.leeemos el formulario que ha rellenado el cliente en la web ($_post)
            $nombre_cliente = $_POST['nombre'];
            $descripcion_cliente = $_POST['descripcion'];

            //2.armamos el objeto, EL MODELO, con los datos del cliente.
            //ponemos id 0 por que la base de datos pondra el autoincrement
            $ubicacionNueva = new Ubicaciones(0, $nombre_cliente, $descripcion_cliente);

            //3.llamamos al dao y que haga el crear
            $exito = $this->ubicacionesDAO->crear($ubicacionNueva);

            //4. si todo va bien redirige al cliente a la pagina principal
            if ($exito) {
                header ("location POR DEFINIR");
            } else {
                echo "ERROR al guardar en la base de datos";
            }
        }

        //READ POR ID
        public function obtenerPorId() {
            //1 leemos el id que nos piden
            $id_buscado = (int) $_GET['id'];

            //2 le pediomos esa caja exacta al DAO
            $ubicaciones = $this->ubicacionesDAO->obtenerPorId($id_buscado);

            //3 mandamos la caja a al vista
            if ($ubicaciones) {
                require 'POR DEFINIR';
            } else {
                echo "la ubicacion no existe";
            }
        }

        //ACTUALIZAR
        public function actualizar() {
            //leemos la carta con los datos modificados

            $id_ubicacion = (int) $_POST['id_ubicacion'];
            $nombre_cliente = $_POST['nombre'];
            $descripcion_cliente = $_POST['descripcion'];


            //armamos la caja, esta vez si ponemos el id reald, para la caja
            $ubicacionModificada = new Ubicaciones(
                $id_ubicacion, 
                $nombre_cliente, 
                $descripcion_cliente
            );

            //mandamos a actualizar el DAO
            $exito = $this->ubicacionesDAO->actualizar($ubicacionModificada);

            //redirigimos y comprobamos
            if ($exito) {
               // header (Location: "POR DEFINIR");
            } else {
                echo "Error al actualizar la notificaciones";
            }
        }

        //ACCION 3 ELIMINAR
        public function eliminar() {
            //1. leemos el id que quiere borrar el cliente
            $id_borrar = (int) $_GET['id'];

            //2. le damos la orden de borrar al dao
            $this->ubicacionesDAO->borrar($id_borrar);

            //3. recargamos la vista
            header ("Location: POR DEFINIR");
        }
    }
?>