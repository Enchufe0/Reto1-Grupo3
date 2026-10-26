<?php
header('Content-Type: application/json; charset=utf-8');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../Conexion/conexion.php';
require_once __DIR__ . '/../controller/HistoricoController.php';

function responderHistorico($respuesta, int $estado = 200): void
{
    http_response_code($estado);
    echo json_encode($respuesta, JSON_UNESCAPED_UNICODE);
    exit;
}

function requerirLoginHistorico(): void
{
    if (!isset($_SESSION['usuario_id'])) {
        throw new Exception('Debes iniciar sesión', 401);
    }
}

try {
    requerirLoginHistorico();

    $metodo = $_SERVER['REQUEST_METHOD'] ?? '';
    if ($metodo !== 'GET') {
        throw new Exception('Método no permitido', 405);
    }

    $idEquipo = filter_var(
        $_GET['id_equipamiento'] ?? null,
        FILTER_VALIDATE_INT
    );
    if ($idEquipo === false || $idEquipo === null || $idEquipo <= 0) {
        throw new Exception('Debes indicar un id_equipamiento válido', 422);
    }

    $database = new Database();
    $db = $database->getConnection();
    if (!$db instanceof PDO) {
        throw new RuntimeException('No se pudo conectar con la base de datos');
    }

    $controller = new HistoricoController($db);
    responderHistorico($controller->listar($idEquipo));
} catch (Throwable $error) {
    $estado = $error->getCode();
    if ($estado < 400 || $estado > 599) {
        $estado = 500;
    }

    responderHistorico(['error' => $error->getMessage()], $estado);
}
