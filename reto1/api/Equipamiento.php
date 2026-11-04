<?php
header('Content-Type: application/json; charset=utf-8');
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function checkLogin() {
    if (!isset($_SESSION['usuario_id'])) {
        http_response_code(401);
        echo json_encode(['error' => 'No autorizado.']);
        exit;
    }
}

require_once __DIR__ . '/../config/conexion.php';
require_once __DIR__ . '/../controller/EquipamientoController.php';

try {
    $database = new Database();
    $dbConnection = $database->getConnection();
    $equipamientoController = new EquipamientoController($dbConnection);
    
    $httpMethod = $_SERVER['REQUEST_METHOD'];
    $urlId = isset($_GET['id']) ? (int)$_GET['id'] : null;
    $rawInput = file_get_contents('php://input');
    $requestPayload = json_decode($rawInput, true) ?? $_POST;

    checkLogin();

    if ($httpMethod === 'GET') {
        echo json_encode($equipamientoController->listar());
        exit;
    }

    if ($httpMethod === 'PUT') {
        // Call the controller
        $equipamientoController->actualizar($urlId, $requestPayload);
        echo json_encode(['ok' => true, 'mensaje' => 'Equipo actualizado']);
        exit;
    }

    // ... (Add your POST and DELETE logic here) ...
    
} catch (Throwable $error) {
    http_response_code(500); // Force a 500 status
    echo json_encode(['error' => $error->getMessage()]); // Send the exact error message
}