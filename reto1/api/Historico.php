<?php
declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../config/conexion.php';
require_once __DIR__ . '/../dao/HistoricoDAO.php';

if (!isset($_SESSION['usuario_id']) && !isset($_SESSION['usuario'])) {
    http_response_code(401);
    echo json_encode(['error' => 'No autorizado. Inicie sesión.'], JSON_UNESCAPED_UNICODE);
    exit;
}

ob_start();
$db = (new Database())->getConnection();
ob_end_clean();

if (!$db instanceof PDO) {
    http_response_code(500);
    echo json_encode(['error' => 'Error de conexión con la base de datos'], JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    $dao = new HistoricoDAO($db);

    if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
        http_response_code(405);
        echo json_encode(['error' => 'Método no permitido'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $idEquipo = (int) ($_GET['id_equipamiento'] ?? 0);
    if ($idEquipo <= 0) {
        http_response_code(422);
        echo json_encode(['error' => 'El parámetro id_equipamiento es obligatorio y debe ser numérico'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $historial = $dao->historialDeEquipamiento($idEquipo);
    http_response_code(200);
    echo json_encode($historial, JSON_UNESCAPED_UNICODE);

} catch (\Throwable $ex) {
    http_response_code(500);
    echo json_encode([
        'error'   => 'Error interno del servidor',
        'detalle' => $ex->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}