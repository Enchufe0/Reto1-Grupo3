<?php

header('Content-Type: application/json; charset=utf-8');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/conexion.php';
require_once __DIR__ . '/../controller/NotificacionesController.php';

$responder = static function (int $codigo, array $contenido): void {
    http_response_code($codigo);
    echo json_encode($contenido, JSON_UNESCAPED_UNICODE);
};

try {
    $database = new Database();
    $controller = new NotificacionesController($database->getConnection());

    switch ($_SERVER['REQUEST_METHOD']) {
        case 'GET':
            if (isset($_GET['mine'])) {
                if (!isset($_SESSION['usuario_id'])) {
                    throw new Exception('Es necesario iniciar sesión', 401);
                }

                $resultado = $controller->obtenerPorUsuario((int) $_SESSION['usuario_id']);
            } elseif (isset($_GET['id'])) {
                $resultado = $controller->obtenerPorId((int) $_GET['id']);
            } else {
                $resultado = $controller->obtenerTodas();
            }

            $responder(200, $resultado);
            break;

        case 'POST':
            $datos = json_decode(file_get_contents('php://input'), true);
            if (!is_array($datos)) {
                throw new Exception('El contenido JSON de la solicitud no es válido', 400);
            }

            $controller->crear($datos);
            $responder(201, ['mensaje' => 'Notificación creada con éxito']);
            break;

        case 'PUT':
            $datos = json_decode(file_get_contents('php://input'), true);
            if (!is_array($datos)) {
                throw new Exception('El contenido JSON de la solicitud no es válido', 400);
            }

            $controller->actualizar($datos);
            $responder(200, ['mensaje' => 'Notificación actualizada correctamente']);
            break;

        case 'DELETE':
            if (!isset($_GET['id'])) {
                throw new Exception('Falta el ID de la notificación a eliminar', 400);
            }

            $id = (int) $_GET['id'];
            $controller->eliminar($id);
            $responder(200, ['mensaje' => "Notificación $id eliminada de la base de datos"]);
            break;

        default:
            throw new Exception('Método HTTP no permitido', 405);
    }
} catch (Throwable $error) {
    $codigoError = (int) $error->getCode();
    $codigoHttp = in_array($codigoError, [400, 401, 404, 405], true) ? $codigoError : 500;

    if ($codigoHttp === 500) {
        error_log($error->getMessage());
        $mensaje = 'Error interno del servidor';
    } else {
        $mensaje = $error->getMessage();
    }

    $responder($codigoHttp, ['error' => $mensaje]);
}
