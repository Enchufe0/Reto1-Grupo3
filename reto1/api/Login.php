<?php
header('Content-Type: application/json; charset=utf-8');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/conexion.php';
require_once __DIR__ . '/../controller/LoginController.php';

function sendJsonResponse(array $responseData, int $statusCode = 200): void 
{
    http_response_code($statusCode); 
    echo json_encode($responseData, JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    $database = new Database();
    $dbConnection = $database->getConnection();

    if (!$dbConnection instanceof PDO) {
        throw new RuntimeException('No se pudo conectar con la base de datos', 500);
    }

    $loginController = new LoginController($dbConnection);
    $httpMethod = $_SERVER['REQUEST_METHOD'];

    if ($httpMethod === 'POST') {
    
        $rawBody = file_get_contents('php://input');
     
        $decodedData = json_decode($rawBody, true);
        
        if (!is_array($decodedData)) {
            throw new InvalidArgumentException('Formato JSON inválido', 400);
        }

        $inputUsuario = $decodedData['nombre_usuario'] ?? '';
        $inputPassword = $decodedData['password'] ?? '';

        $loggedUserData = $loginController->login($inputUsuario, $inputPassword);

        $successResponse = [
            'mensaje' => 'Login correcto',
            'usuario' => $loggedUserData
        ];

        sendJsonResponse($successResponse, 200);
    }

    if ($httpMethod === 'DELETE') {
        $_SESSION = [];
        session_destroy();
        
        sendJsonResponse(['mensaje' => 'Sesión cerrada correctamente'], 200);
    }

    throw new Exception('Método HTTP no permitido', 405);

} catch (Throwable $error) {
    
    $errorCode = $error->getCode();

    if ($errorCode < 400 || $errorCode > 599) {
        $errorCode = 500; 
    }

    $errorResponse = [
        'error' => $error->getMessage()
    ];
    sendJsonResponse($errorResponse, $errorCode);
}