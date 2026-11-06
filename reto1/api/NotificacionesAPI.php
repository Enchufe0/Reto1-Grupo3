<?php
    header('Content-Type: application/json; charset=utf-8');
    
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    function exigirInicioSesion(): void {
        if (!isset($_SESSION['usuario_id'])) {
            http_response_code(401);
            echo json_encode(["error" => "Es necesario iniciar sesion"]);
            exit;
        }
    }

    function exigirAdministrador(): void {
        exigirInicioSesion();
        if (($_SESSION['rol'] ?? null) !== 'ADMIN') {
            http_response_code(403);
            echo json_encode(["error" => "Solo los administradores pueden realizar esta accion"]);
            exit;
        }
    }

    require_once __DIR__ . '/../controller/NotificacionesController.php';

    // conexion con la base de datos
    try {
        $conexion = new PDO("mysql:host=localhost;dbname=reto_2daw;charset=utf8", "root", "");
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(["error" => "Fallo de conexion a la base de datos"]);
        exit;
    }

    $controlador = new NotificacionesController($conexion);
    $metodo = $_SERVER["REQUEST_METHOD"];

    switch ($metodo) {
        case 'GET':
            if (isset($_GET['mine'])) {
                exigirInicioSesion();
                $resultado = $controlador->obtenerPorUsuario((int) $_SESSION['usuario_id']);
                http_response_code(200);
                echo json_encode($resultado, JSON_UNESCAPED_UNICODE);

            } elseif (isset($_GET['all'])) {
                exigirAdministrador();
                $resultado = $controlador->obtenerTodas();
                http_response_code(200);
                echo json_encode($resultado, JSON_UNESCAPED_UNICODE);

            } elseif (isset($_GET['id'])) {
                exigirInicioSesion();
                $id = intval($_GET['id']);
                $resultado = $controlador->obtenerPorId($id);

                if ($resultado !== null) {
                    if (($_SESSION['rol'] ?? null) !== 'ADMIN'
                        && (int) $resultado['id_usuario'] !== (int) $_SESSION['usuario_id']) {
                        http_response_code(403);
                        echo json_encode(["error" => "No tienes permiso para ver esta notificacion"]);
                        break;
                    }
                    http_response_code(200);
                    echo json_encode($resultado, JSON_UNESCAPED_UNICODE);
                } else {
                    http_response_code(404);
                    echo json_encode(["error" => "No se encontro la notificacion"]);
                }
            } else {
                exigirAdministrador();
                $resultado = $controlador->obtenerTodas();
                http_response_code(200);
                echo json_encode($resultado, JSON_UNESCAPED_UNICODE);
            }
            break;

        case 'POST':
            exigirAdministrador();
            $contenido = file_get_contents("php://input");
            $datos = json_decode($contenido, true);
            
            $exito = $controlador->crear($datos);
            
            if ($exito) {
                http_response_code(201); 
                echo json_encode(["mensaje" => "Notificacion creada con exito"]);
            } else {
                http_response_code(400); 
                echo json_encode(["error" => "Faltan datos o el estado es invalido"]);
            }
            break;

        case 'PUT':
            exigirAdministrador();
            $contenido = file_get_contents("php://input");
            $datos = json_decode($contenido, true);

            $exito = $controlador->actualizar($datos);
            
            if ($exito) {
                http_response_code(200);
                echo json_encode(["mensaje" => "Notificacion actualizada correctamente"]);
            } else {
                http_response_code(400);
                echo json_encode(["error" => "Faltan datos o el estado es invalido"]);
            }
            break;

        case 'PATCH':
            exigirAdministrador();
            $datos = json_decode(file_get_contents("php://input"), true);

            if (!is_array($datos)
                || !isset($datos['id_notificacion'], $datos['estado'])
                || filter_var($datos['id_notificacion'], FILTER_VALIDATE_INT) === false
                || (int) $datos['id_notificacion'] < 1
                || !is_string($datos['estado'])) {
                http_response_code(400);
                echo json_encode(["error" => "Faltan el ID o el estado de la notificacion"]);
                break;
            }

            $id = (int) $datos['id_notificacion'];
            $notificacion = $controlador->obtenerPorId($id);
            if ($notificacion === null) {
                http_response_code(404);
                echo json_encode(["error" => "No se encontro la notificacion"]);
                break;
            }

            if ($notificacion['estado'] === 'REALIZADA') {
                if ($datos['estado'] !== 'REALIZADA') {
                    http_response_code(409);
                    echo json_encode(["error" => "Una notificacion completada no puede cambiar de estado"]);
                    break;
                }
                http_response_code(200);
                echo json_encode(["mensaje" => "La notificacion ya estaba completada"]);
                break;
            }

            if ($datos['estado'] === $notificacion['estado']) {
                http_response_code(200);
                echo json_encode(["mensaje" => "El estado no ha cambiado"]);
                break;
            }

            if ($controlador->actualizarEstado($id, $datos['estado'])) {
                http_response_code(200);
                echo json_encode(["mensaje" => "Estado actualizado correctamente"]);
            } else {
                $estadoActual = $controlador->obtenerPorId($id);
                if ($estadoActual === null) {
                    http_response_code(404);
                    echo json_encode(["error" => "No se encontro la notificacion"]);
                } elseif ($estadoActual['estado'] === 'REALIZADA') {
                    http_response_code(409);
                    echo json_encode(["error" => "Una notificacion completada no puede cambiar de estado"]);
                } else {
                    http_response_code(400);
                    echo json_encode(["error" => "El estado de la notificacion no es valido"]);
                }
            }
            break;

        case 'DELETE':
            exigirAdministrador();
            if (isset($_GET['id'])) {
                $id = intval($_GET['id']);
                $exito = $controlador->eliminar($id);
                
                if ($exito) {
                    http_response_code(200);
                    echo json_encode(["mensaje" => "Notificacion eliminada correctamente"]);
                } else {
                    http_response_code(500);
                    echo json_encode(["error" => "Error al eliminar la notificacion"]);
                }
            } else {
                http_response_code(400);
                echo json_encode(["error" => "Falta el ID de la notificacion a eliminar"]);
            }
            break;
            
        default:
            http_response_code(405);
            echo json_encode(["error" => "Metodo HTTP no permitido"]);
            break;
    }
    
    exit;
?>