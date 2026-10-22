<?php
header('Content-Type: application/json; charset=utf-8');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/conexion.php';
require_once __DIR__ . '/../dao/LoginDAO.php';

ob_start();
$db = (new Database())->getConnection();
ob_end_clean();

if (!$db instanceof PDO) {
    http_response_code(500);
    echo json_encode(['error' => 'Error de conexión con la base de datos'], JSON_UNESCAPED_UNICODE);
    exit;
}

$loginDAO = new LoginDAO($db);
$metodo = $_SERVER['REQUEST_METHOD'];

switch ($metodo) {

    case 'POST':
        $cuerpo = file_get_contents('php://input');
        $datos = json_decode($cuerpo, true);
        if (empty($datos)) {
            $datos = $_POST;
        }

        $usuario  = trim($datos['nombre_usuario'] ?? $datos['usuario'] ?? '');
        $password = (string) ($datos['password'] ?? $datos['contrasena'] ?? '');

        if ($usuario === '' || $password === '') {
            http_response_code(400);
            echo json_encode(['error' => 'Debes indicar usuario y contraseña'], JSON_UNESCAPED_UNICODE);
            break;
        }

        $fila = $loginDAO->buscarPorNombreUsuario($usuario);

        if ($fila === null || !password_verify($password, $fila['password_hash'])) {
            http_response_code(401);
            echo json_encode(['error' => 'Usuario o contraseña incorrectos'], JSON_UNESCAPED_UNICODE);
            break;
        }

        $_SESSION['usuario_id'] = (int) $fila['id_usuario'];
        $_SESSION['nombre']     = $fila['nombre'];
        $_SESSION['apellidos']  = $fila['apellidos'];
        $_SESSION['rol']        = strtoupper($fila['rol']);

        http_response_code(200);
        echo json_encode([
            'mensaje' => 'Login correcto',
            'usuario' => [
                'id_usuario' => (int) $fila['id_usuario'],
                'nombre'     => $fila['nombre'],
                'apellidos'  => $fila['apellidos'],
                'rol'        => strtoupper($fila['rol'])
            ]
        ], JSON_UNESCAPED_UNICODE);
        break;

    case 'GET':
        if (isset($_SESSION['usuario_id'])) {
            http_response_code(200);
            echo json_encode([
                'autenticado' => true,
                'usuario' => [
                    'id_usuario' => $_SESSION['usuario_id'],
                    'nombre'     => $_SESSION['nombre'] ?? '',
                    'apellidos'  => $_SESSION['apellidos'] ?? '',
                    'rol'        => $_SESSION['rol'] ?? 'USER'
                ]
            ], JSON_UNESCAPED_UNICODE);
        } else {
            http_response_code(200);
            echo json_encode(['autenticado' => false], JSON_UNESCAPED_UNICODE);
        }
        break;

    case 'DELETE':
        $_SESSION = [];
        if (session_id()) {
            session_destroy();
        }
        http_response_code(200);
        echo json_encode(['mensaje' => 'Sesión cerrada correctamente'], JSON_UNESCAPED_UNICODE);
        break;

    default:
        http_response_code(405);
        echo json_encode(['error' => 'Método no permitido'], JSON_UNESCAPED_UNICODE);
        break;
}