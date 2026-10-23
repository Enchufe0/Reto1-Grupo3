<?php
require_once __DIR__ . '/../dao/LoginDAO.php';

class LoginController {
    private $loginDAO;

    // Recibimos la conexión a la base de datos
    public function __construct(PDO $db) {
        $this->loginDAO = new LoginDAO($db);
    }

    // Procesa el login
    public function login($usuario, $password) {
        if (empty($usuario) || empty($password)) {
            throw new Exception("Usuario y contraseña obligatorios", 400);
        }

        // Buscamos al usuario en la BD
        $user = $this->loginDAO->buscarPorNombreUsuario($usuario);

        // Verificamos contraseña
        if (!$user || !password_verify($password, $user['password_hash'])) {
            throw new Exception("Usuario o contraseña incorrectos", 401);
        }

        // 👇 AQUÍ ESTÁ EL AUTH 👇
        // Guardamos los datos en la sesión de PHP
        $_SESSION['usuario_id'] = $user['id_usuario'];
        $_SESSION['nombre']     = $user['nombre'];
        $_SESSION['rol']        = strtoupper($user['rol']);

        // Devolvemos los datos limpios (sin el password_hash)
        return [
            'id_usuario' => $user['id_usuario'],
            'nombre'     => $user['nombre'],
            'apellidos'  => $user['apellidos'],
            'rol'        => strtoupper($user['rol'])
        ];
    }
}