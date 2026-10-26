<?php
require_once __DIR__ . '/../dao/LoginDAO.php';

class LoginController
{
    private LoginDAO $loginDAO;

    public function __construct(PDO $db)
    {
        $this->loginDAO = new LoginDAO($db);
    }

    public function login(string $nombreUsuario, string $password): array
    {
        $nombreUsuario = trim($nombreUsuario);

        if ($nombreUsuario === '' || $password === '') {
            throw new InvalidArgumentException('Usuario y contraseña obligatorios', 400);
        }

        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $usuario = $this->loginDAO->buscarPorNombreUsuario($nombreUsuario);

        if ($usuario === null || !password_verify($password, (string) $usuario['password_hash'])) {
            throw new InvalidArgumentException('Credenciales inválidas', 401);
        }

        $_SESSION['usuario_id'] = (int) $usuario['id_usuario'];
        $_SESSION['nombre'] = $usuario['nombre'];
        $_SESSION['apellidos'] = $usuario['apellidos'] ?? '';
        $_SESSION['nombre_usuario'] = $nombreUsuario;
        $_SESSION['rol'] = $usuario['rol'] ?? 'USER';

        return [
            'id_usuario' => (int) $usuario['id_usuario'],
            'nombre' => $usuario['nombre'],
            'apellidos' => $usuario['apellidos'] ?? '',
            'nombre_usuario' => $nombreUsuario,
            'rol' => $usuario['rol'] ?? 'USER',
        ];
    }
}