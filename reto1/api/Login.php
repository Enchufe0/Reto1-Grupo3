<?php
header('Content-Type: application/json; charset=utf-8');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../Conexion/conexion.php';
require_once __DIR__ . '/../controller/LoginController.php';

function responderLogin($respuesta, int $estado = 200): void
{
    http_response_code($estado);
    echo json_encode($respuesta, JSON_UNESCAPED_UNICODE);
    exit;
}

$bufferIniciado = false;

try {
    $metodo = $_SERVER['REQUEST_METHOD'] ?? '';

    switch ($metodo) {
        case 'POST':
            $cuerpo = file_get_contents('php://input');
            $json = json_decode($cuerpo, true);
            $datos = is_array($json) ? $json : $_POST;
            $usuario = $datos['nombre_usuario'] ?? $datos['usuario'] ?? '';
            $password = $datos['password'] ?? $datos['contrasena'] ?? '';

            if (!is_string($usuario) || !is_string($password)) {
                throw new Exception('Usuario y contraseña deben ser texto', 400);
            }

            $database = new Database();
            ob_start();
            $bufferIniciado = true;
            $db = $database->getConnection();
            ob_end_clean();
            $bufferIniciado = false;

            if (!$db instanceof PDO) {
                throw new RuntimeException('No se pudo conectar con la base de datos');
            }

            $controller = new LoginController($db);
            $userData = $controller->login(trim($usuario), $password);
            responderLogin([
                'mensaje' => 'Login correcto',
                'usuario' => $userData,
            ]);

        case 'GET':
            if (isset($_SESSION['usuario_id'])) {
                responderLogin([
                    'autenticado' => true,
                    'usuario' => [
                        'id_usuario' => $_SESSION['usuario_id'],
                        'nombre' => $_SESSION['nombre'] ?? '',
                        'rol' => $_SESSION['rol'] ?? 'USER',
                    ],
                ]);
            }

            responderLogin(['autenticado' => false]);

        case 'DELETE':
            $_SESSION = [];
            if (ini_get('session.use_cookies')) {
                $params = session_get_cookie_params();
                setcookie(
                    session_name(),
                    '',
                    time() - 42000,
                    $params['path'],
                    $params['domain'],
                    $params['secure'],
                    $params['httponly']
                );
            }
            session_destroy();
            responderLogin(['mensaje' => 'Sesión cerrada correctamente']);

        default:
            throw new Exception('Método no permitido', 405);
    }
} catch (Throwable $error) {
    if ($bufferIniciado) {
        ob_end_clean();
    }

    $estado = $error->getCode();
    if ($estado < 400 || $estado > 599) {
        $estado = 500;
    }

    responderLogin(['error' => $error->getMessage()], $estado);
}
