<?php
    require_once __DIR__ . '/../model/Usuarios.php';
    require_once __DIR__ . '/../model/RolUsuario.php';
    require_once __DIR__ . '/../dao/UsuariosDAO.php';

    use rolUsuario\RolUsuario;

    class UsuariosController {
        private UsuariosDAO $usuariosDAO;

        public function __construct(PDO $conexion) {
            $this->usuariosDAO = new UsuariosDAO($conexion);
        }

        public function obtenerTodas(): array {
            $lista = $this->usuariosDAO->obtenerTodas();
            $resultado = [];

            foreach ($lista as $usuario) {
                $resultado[] = $this->usuarioComoArray($usuario);
            }

            return $resultado;
        }

        public function obtenerPorId(int $id): ?array {
            $usuario = $this->usuariosDAO->obtenerPorId($id);

            return $usuario === null ? null : $this->usuarioComoArray($usuario);
        }

        public function crear(array $datos): int|false {
            if (!$this->tieneCamposTexto($datos, ['nombre', 'apellidos', 'nombre_usuario'])
                || !isset($datos['password'])
                || !is_string($datos['password'])
                || trim($datos['password']) === '') {
                return false;
            }

            $usuario = new Usuarios(
                0,
                trim($datos['nombre']),
                trim($datos['apellidos']),
                trim($datos['nombre_usuario']),
                password_hash($datos['password'], PASSWORD_DEFAULT),
                RolUsuario::USER
            );

            return $this->usuariosDAO->crear($usuario);
        }

        public function actualizar(int $id, array $datos): ?bool {
            if (!$this->tieneCamposTexto($datos, ['nombre', 'apellidos', 'nombre_usuario'])) {
                return false;
            }

            if (array_key_exists('password', $datos)
                && (!is_string($datos['password'])
                    || ($datos['password'] !== '' && trim($datos['password']) === ''))) {
                return false;
            }

            $usuarioExistente = $this->usuariosDAO->obtenerPorId($id);
            if ($usuarioExistente === null) {
                return null;
            }

            $actualizarPassword = isset($datos['password']) && $datos['password'] !== '';
            $usuario = new Usuarios(
                $id,
                trim($datos['nombre']),
                trim($datos['apellidos']),
                trim($datos['nombre_usuario']),
                $actualizarPassword
                    ? password_hash($datos['password'], PASSWORD_DEFAULT)
                    : $usuarioExistente->getPasswordHash(),
                $usuarioExistente->getCategoria()
            );

            return $this->usuariosDAO->actualizar($usuario, $actualizarPassword);
        }

        public function actualizarPassword(int $id, array $datos): ?bool {
            if (!$this->tieneCamposTexto($datos, ['password'])) {
                return false;
            }

            $usuario = $this->usuariosDAO->obtenerPorId($id);
            if ($usuario === null) {
                return null;
            }

            return $this->usuariosDAO->actualizarPassword($usuario, $datos['password']);
        }

        public function eliminar(int $id): ?bool {
            if ($this->usuariosDAO->obtenerPorId($id) === null) {
                return null;
            }

            return $this->usuariosDAO->borrar($id);
        }

        public function login(string $nombreUsuario, string $password): bool {
            return $this->usuariosDAO->login($nombreUsuario, $password);
        }

        private function tieneCamposTexto(array $datos, array $campos): bool {
            foreach ($campos as $campo) {
                if (!isset($datos[$campo])
                    || !is_string($datos[$campo])
                    || trim($datos[$campo]) === '') {
                    return false;
                }
            }

            return true;
        }

        private function usuarioComoArray(Usuarios $usuario): array {
            return [
                'id_usuario' => $usuario->getIdUsuario(),
                'nombre' => $usuario->getNombre(),
                'apellidos' => $usuario->getApellidos(),
                'nombre_usuario' => $usuario->getNombreUsuario(),
                'rol' => $usuario->getCategoria()->value
            ];
        }
    }
?>
