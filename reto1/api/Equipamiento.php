<?php
// 1. Traemos la conexión, la sesión y la seguridad desde api.php
require 'api.php'; 
require __DIR__ . '/../controller/EquipamientoController.php';

$c = new EquipamientoController($db);
$metodo = $_SERVER['REQUEST_METHOD'];
$id = isset($_GET['id']) ? (int)$_GET['id'] : null;
$datos = json_decode(file_get_contents('php://input'), true) ?? $_POST;

try {
    checkLogin(); // Todos deben estar logueados

    if ($metodo === 'GET') {
        if ($id !== null) {
            json($c->ver($id));
        }
        json($c->listar());
    }

    if ($metodo === 'POST') {
        checkAdmin(); // Solo Admin crea
        $c->crear($datos);
        json(['ok' => true, 'mensaje' => 'Equipo creado']);
    }

    if ($metodo === 'PUT') {
        if (isset($datos['id_ubicacion_actual'])) {
            // Mover equipo (Solo Conserje)
            if ($_SESSION['rol'] !== 'USER') throw new Exception("Solo el conserje mueve equipos", 403);
            $c->cambiarUbicacion($id, $datos['id_ubicacion_actual']);
        } else {
            // Editar datos (Solo Admin)
            checkAdmin();
            $c->actualizar($id, $datos);
        }
        json(['ok' => true]);
    }

    if ($metodo === 'DELETE') {
        checkAdmin(); // Solo Admin borra
        $c->eliminar($id);
        json(['ok' => true]);
    }
} catch (Throwable $e) {
    json(['error' => $e->getMessage()], $e->getCode() ?: 500);
}
?>