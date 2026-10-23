<?php
header('Content-Type: application/json; charset=utf-8');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../Conexion/conexion.php';
require_once __DIR__ . '/../controller/EquipamientoController.php';

function responderEquipamiento(array $respuesta, int $estado = 200): void
{
    http_response_code($estado);
    echo json_encode($respuesta, JSON_UNESCAPED_UNICODE);
    exit;
}

function requerirLoginEquipamiento(): void
{
    if (!isset($_SESSION['usuario_id'])) {
        throw new Exception('Debes iniciar sesión', 401);
    }
}

function requerirAdminEquipamiento(): void
{
    if (strtoupper((string) ($_SESSION['rol'] ?? '')) !== 'ADMIN') {
        throw new Exception('Acceso solo para administradores', 403);
    }
}

try {
    $database = new Database();
    $db = $database->getConnection();
    if (!$db instanceof PDO) {
        throw new RuntimeException('No se pudo conectar con la base de datos');
    }

    $controller = new EquipamientoController($db);
    $metodo = $_SERVER['REQUEST_METHOD'] ?? '';
    
    // MEJORA: Forzamos a que el ID sea un número entero
    $id = isset($_GET['id']) ? (int)$_GET['id'] : null; 
    
    $cuerpo = file_get_contents('php://input');
    $json = json_decode($cuerpo, true);
    $datos = is_array($json) ? $json : $_POST;

    requerirLoginEquipamiento();

    switch ($metodo) {
        case 'GET':
            if ($id !== null) {
                responderEquipamiento($controller->ver($id));
            }
            responderEquipamiento($controller->listar());

        case 'POST':
            requerirAdminEquipamiento();
            $controller->crear($datos);
            responderEquipamiento([
                'ok' => true,
                'mensaje' => 'Equipo creado correctamente',
            ], 201);

        case 'PUT':
            if (array_key_exists('id_ubicacion_actual', $datos)) {
                if (strtoupper((string) ($_SESSION['rol'] ?? '')) !== 'USER') {
                    throw new Exception('Solo el conserje puede mover equipos', 403);
                }
                $controller->cambiarUbicacion($id, $datos['id_ubicacion_actual']);
            } else {
                requerirAdminEquipamiento();
                $controller->actualizar($id, $datos);
            }
            responderEquipamiento([
                'ok' => true,
                'mensaje' => 'Equipo modificado correctamente',
            ]);

        case 'DELETE':
            requerirAdminEquipamiento();
            $controller->eliminar($id);
            responderEquipamiento([
                'ok' => true,
                'mensaje' => 'Equipo eliminado correctamente',
            ]);

        default:
            throw new Exception('Método no permitido', 405);
    }
} catch (Throwable $error) {
    $estado = $error->getCode();
    if ($estado < 400 || $estado > 599) {
        $estado = 500;
    }

    responderEquipamiento(['error' => $error->getMessage()], $estado);
}