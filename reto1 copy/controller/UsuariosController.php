<?php

require_once 'Usuarios.php';
require_once 'UsuariosDAO.php';

    class UsuariosController{

        private UsuariosDAO $usuariosDAO;

        public function __construct(PDO $conexion){
            $this->usuariosDAO = new UsuariosDAO($conexion);
        }

        public function listar(){
            $listaUsuarios = $this->usuariosDAO->buscarTodos();

            return 'PorDefinir';
        }

        public function guardarNuevo(){
            $nombre_cliente = $_POST['nombre'];
            $apellido_cliente = $_POST['apellido'];
            $nombre_username_cliente = $_POST['nombre_usuarios'];
            $password_hash_cliente = $_POST['password_hash'];

            $usuarioNuevo = new Usuarios(0, $nombre_cliente, $apellido_cliente, $nombre_username_cliente, $password_hash_cliente);

            $exito = $this->usuariosDAO->crear($usuarioNuevo);

            if($exito){
                header("PorDefinir");
            }else{
                echo "ERROR al guardar en la base de datos";
            }
        }

        public function actualizar(){
            $id_usuario = (int) $_POST['id'];
            $nombre_cliente = $_POST['nombre'];
            $apellido_cliente = $_POST['apellido'];
            $nombre_username_cliente = $_POST['nombre_usuarios'];
            $password_hash_cliente = $_POST['password_hash'];

            $usuarioActualizado = new Usuarios(
                $id_usuario,
                $nombre_cliente,
                $apellido_cliente,
                $nombre_username_cliente,
                $password_hash_cliente
            );

            $exito = $this->usuariosDAO->actualizar($usuarioActualizado);

            if($exito){
                header("PorDefinir");
            }else{
                echo "ERROR al actualizar en la base de datos";
            }
        }

        public function eliminar(){
            $id_borrar = (int) $_GET['id'];

            $this->usuariosDAO->borrar($id_borrar);

            header("PorDefinir");
        }
    }
?>