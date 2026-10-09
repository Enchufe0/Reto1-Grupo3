<?php
require_once 'Usuarios.php';

class UsuariosDAO
{
    private PDO $conexion;

    public function __construct(PDO $conexion){
            $this->conexion = $conexion;
    }

    public function buscarPorId(int $id){
        $sql = "SELECT * FROM usuarios WHERE id_usuario = ?";

        $stmt = $this->conexion->prepare($sql);
        $stmt->execute([$id]);

        $fila = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($fila) {
            return new Usuarios(
                $fila['id_usuario'],
                $fila['nombre'],
                $fila['apellidos'],
                $fila['nombre_usuario'],
                $fila['password_hash']
            );
        }

        return null;
    }

    public function buscarTodos(){
        $sql = "SELECT * FROM usuarios";

        $stmt = $this->conexion->prepare($sql);
        $stmt->execute();

        $fila = $stmt->fetchAll(PDO::FETCH_OBJ);

        $usuarios=[];

        foreach($fila as $filaUsuarios){
            $usuario= new usuarios(
                $filaUsuarios->id_usuario,
                $filaUsuarios->nombre,
                $filaUsuarios->apellido,
                $filaUsuarios->nombre_usuario,
                $filaUsuarios->password_hash,
            );
            $usuarios[] = $usuario;
        }
        return $usuarios;
    }

    public function crear(Usuarios $usuario): bool{
        $sql = "INSERT INTO usuarios (nombre, apellidos, nombre_usuario, password_hash) VALUES (?, ?, ?, ?)";

        $stmt = $this->conexion->prepare($sql);
        return $stmt->execute([
            $usuario->getNombre(),
            $usuario->getApellidos(),
            $usuario->getNombreUsuario(),
            $usuario->getPasswordHash()
        ]);
    }

    public function actualizar(Usuarios $usuario): bool{
        $sql = "UPDATE usuarios SET nombre = ?, apellidos = ?, nombre_usuario = ?, password_hash = ? WHERE id_usuario = ?";

        $stmt = $this->conexion->prepare($sql);
        return $stmt->execute([
            $usuario->getNombre(),
            $usuario->getApellidos(),
            $usuario->getNombreUsuario(),
            $usuario->getPasswordHash(),
            $usuario->getIdUsuario()
        ]);
    }

    public function borrar(int $id): bool{
        $sql = "DELETE FROM usuarios WHERE id_usuario = ?";

        $stmt = $this->conexion->prepare($sql);
        return $stmt->execute([$id]);
    }
}
