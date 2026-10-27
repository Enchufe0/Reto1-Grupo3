<?php
require 'api.php';
require __DIR__ . '/../controller/HistoricoController.php';

try {

    checkLogin();

    $httpMethod = $_SERVER['REQUEST_METHOD'];
    if ($httpMethod !== 'GET') {
        throw new Exception('Método HTTP no permitido. Solo se acepta GET.', 405);
    }

    $rawIdFromUrl = $_GET['id_equipamiento'] ?? null;
    
    if ($rawIdFromUrl === null) {
        throw new Exception('Debes indicar un id_equipamiento en la URL', 422);
    }

    $validatedEquipmentId = filter_var($rawIdFromUrl, FILTER_VALIDATE_INT);
    
    if ($validatedEquipmentId === false || $validatedEquipmentId <= 0) {
        throw new Exception('El id_equipamiento debe ser un número válido', 422);
    }

    $database = new Database();
    $dbConnection = $database->getConnection();

    if (!$dbConnection instanceof PDO) {
        throw new RuntimeException('Error de conexión a la base de datos', 500);
    }

    $historicoController = new HistoricoController($dbConnection);

    $historyData = $historicoController->listar($validatedEquipmentId);
    
    json($historyData, 200);

} catch (Throwable $error) {
    $errorCode = $error->getCode();
    if ($errorCode < 400 || $errorCode > 599) {
        $errorCode = 500;
    }

    json(['error' => $error->getMessage()], $errorCode);
}