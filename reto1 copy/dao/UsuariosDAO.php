<?php
require_once 'Usuarios.php';

class UsuariosDAO
{
    private PDO $conexion;

    public function __construct(PDO $conexion){
            $this->conexion = $conexion;
    }

    public function login(string $nombre_usuario, string $password): bool
    {
        $sql = "SELECT password_hash FROM usuarios WHERE nombre_usuario = ?";

        $stmt = $this->conexion->prepare($sql);
        $stmt->execute([$nombre_usuario]);

        $password_hash = $stmt->fetchColumn();

        return $password_hash !== false && password_verify($password, $password_hash);
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

    public function actualizar_password(Usuarios $usuario, string $nuevaPassword): bool
    {
        $hashed_password = password_hash($nuevaPassword, PASSWORD_DEFAULT);
        $sql = "UPDATE usuarios SET password_hash = ? WHERE id_usuario = ?";

        $stmt = $this->conexion->prepare($sql);

        return $stmt->execute([
            $hashed_password,
            $usuario->getIdUsuario()
        ]);
    }

    public function borrar(int $id): bool{
        $sql = "DELETE FROM usuarios WHERE id_usuario = ?";

        $stmt = $this->conexion->prepare($sql);
        return $stmt->execute([$id]);
    }
}
