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

        //ACCION 1 MOSTRAR EL INVENTARIO
        public function listar(){
            //llamar al DAO y pedirle todas las ubicaciones
            $listaUbicaciones = $this->ubicacionesDAO->obtenerTodas();

            //mostrar el resultado al cliente
            require 'vistas/lista_ubicaciones.php';
        }


        //ACION 2 RECIBIR Y CREAR NUEVO FORMULARIO
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

        //ACCION 3 ELIMINAR
        public function eliminar() {
            //1. leemos el id que quiere borrar el cliente
            $id_borrar = (int) $_GET['id'];

            //2. le damos la orden de borrar al dao
            $this->ubicacionesDAO->borrar($id_borrar);

            //3. recargamos la vista
            header ("location POR DEFINIR");
        }
    }
?>