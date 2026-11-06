<?php
    require_once __DIR__ . '/../model/Usuarios.php';
    require_once __DIR__ . '/../model/RolUsuario.php';

    use rolUsuario\RolUsuario;

    class UsuariosDAO {
        private PDO $conexion;

        public function __construct(PDO $conexion) {
            $this->conexion = $conexion;
        }

        public function crear(Usuarios $usuario): int|false {
            $sql = 'INSERT INTO usuarios (nombre, apellidos, nombre_usuario, password_hash)
                    VALUES (:nombre, :apellidos, :nombre_usuario, :password_hash)';
            $stmt = $this->conexion->prepare($sql);
            $exito = $stmt->execute([
                'nombre' => $usuario->getNombre(),
                'apellidos' => $usuario->getApellidos(),
                'nombre_usuario' => $usuario->getNombreUsuario(),
                'password_hash' => $usuario->getPasswordHash()
            ]);

            return $exito ? (int) $this->conexion->lastInsertId() : false;
        }

        public function obtenerTodas(): array {
            $stmt = $this->conexion->query('SELECT * FROM usuarios');
            $listaUsuarios = [];

            while ($fila = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $listaUsuarios[] = $this->crearModelo($fila);
            }

            return $listaUsuarios;
        }

        public function obtenerPorId(int $id): ?Usuarios {
            $stmt = $this->conexion->prepare(
                'SELECT * FROM usuarios WHERE id_usuario = :id_usuario'
            );
            $stmt->execute(['id_usuario' => $id]);
            $fila = $stmt->fetch(PDO::FETCH_ASSOC);

            return $fila === false ? null : $this->crearModelo($fila);
        }

        public function actualizar(Usuarios $usuario, bool $actualizarPassword = false): bool {
            if ($actualizarPassword) {
                $sql = 'UPDATE usuarios
                        SET nombre = :nombre, apellidos = :apellidos,
                            nombre_usuario = :nombre_usuario, password_hash = :password_hash
                        WHERE id_usuario = :id_usuario';
            } else {
                $sql = 'UPDATE usuarios
                        SET nombre = :nombre, apellidos = :apellidos,
                            nombre_usuario = :nombre_usuario
                        WHERE id_usuario = :id_usuario';
            }

            $parametros = [
                'nombre' => $usuario->getNombre(),
                'apellidos' => $usuario->getApellidos(),
                'nombre_usuario' => $usuario->getNombreUsuario(),
                'id_usuario' => $usuario->getIdUsuario()
            ];
            if ($actualizarPassword) {
                $parametros['password_hash'] = $usuario->getPasswordHash();
            }

            $stmt = $this->conexion->prepare($sql);
            return $stmt->execute($parametros);
        }

        public function actualizarPassword(Usuarios $usuario, string $nuevaPassword): bool {
            $stmt = $this->conexion->prepare(
                'UPDATE usuarios SET password_hash = :password_hash WHERE id_usuario = :id_usuario'
            );

            return $stmt->execute([
                'password_hash' => password_hash($nuevaPassword, PASSWORD_DEFAULT),
                'id_usuario' => $usuario->getIdUsuario()
            ]);
        }

        public function borrar(int $id): bool {
            $stmt = $this->conexion->prepare(
                'DELETE FROM usuarios WHERE id_usuario = :id_usuario'
            );

            return $stmt->execute(['id_usuario' => $id]);
        }

        public function login(string $nombreUsuario, string $password): bool {
            $stmt = $this->conexion->prepare(
                'SELECT password_hash FROM usuarios WHERE nombre_usuario = :nombre_usuario LIMIT 1'
            );
            $stmt->execute(['nombre_usuario' => $nombreUsuario]);
            $passwordHash = $stmt->fetchColumn();

            return $passwordHash !== false && password_verify($password, $passwordHash);
        }

        private function crearModelo(array $fila): Usuarios {
            return new Usuarios(
                (int) $fila['id_usuario'],
                $fila['nombre'],
                $fila['apellidos'],
                $fila['nombre_usuario'],
                $fila['password_hash'],
                RolUsuario::from($fila['rol'])
            );
        }
    }
?>
