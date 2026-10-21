<?php

    header('Content-Type: application/json; charset=utf-8');

    require_once __DIR__ . '/../model/Notificaciones.php';
    require_once __DIR__ . '/../dao/NotificacionesDAO.php';

    //conexion con la base de datos
    try {
        $conexion = new PDO("mysql:host=localhost;dbname=reto_2daw;charset=utf8", "root", "");
    } catch (PDOException $e) {
        echo json_encode(["error" => "Fallo de conexion a la base de datos"]);
        exit;
    }

    //instanciamos el dao y pedimos los datos
    $notificacionesDAO = new NotificacionesDAO($conexion);

    //leer el el metodo hhtp
    $metodo = $_SERVER["REQUEST_METHOD"];

    switch ($metodo) {

        //CASO 1. GET
        case 'GET':
            if (isset($_GET['id'])){
                $id = intval($_GET['id']);
                $notificacion = $notificacionesDAO->obtenerPorId($id);

                if ($notificacion !== null) {
                    $resultado = [
                        'id_notificacion'   => $notificacion-> getIdNotificacion(),
                        'titulo'            => $notificacion -> getTitulo(),
                        'descripcion'       => $notificacion -> getDescripcion(),
                        'fecha_creacion'    => $notificacion -> getFechaCreacion(),
                        'estado'            => $notificacion -> getEstado()->value ?? $notificacion->getEstado()->name,
                        'id_usuario'        => $notificacion -> getIdUsuario()
                    ];

                    http_response_code(200);
                    echo json_encode($resultado, JSON_UNESCAPED_UNICODE);
                } else {
                    http_response_code(404);
                    echo json_encode(["error" => "No se encontró ninguna notificación con el ID: $id"]);
                }

            } else {

                $listaNotificaciones = $notificacionesDAO->obtenerTodas();

                $resultado = [];
                foreach ($listaNotificaciones as $notificacion) {
                    $resultado[] = [
                        'id_notificacion'   => $notificacion-> getIdNotificacion(),
                        'titulo'            => $notificacion -> getTitulo(),
                        'descripcion'       => $notificacion -> getDescripcion(),
                        'fecha_creacion'    => $notificacion -> getFechaCreacion(),
                        'estado'            => $notificacion -> getEstado()->value ?? $notificacion->getEstado()->name,
                        'id_usuario'        => $notificacion -> getIdUsuario()
                    ];
                }

                http_response_code(200);
                echo json_encode($resultado, JSON_UNESCAPED_UNICODE);
            }
            break;

            case 'POST':
                $contenido = file_get_contents("php//input");
                $datos = json_decode($contenido, true);
                
                http_response_code(201); 
                echo json_encode(["mensaje" => "Notificación creada con éxito"]);
                break;

            case 'PUT':
                $contenido = file_get_contents("php//input");
                $datos = json_decode($contenido, true);

                http_response_code(200);
                echo json_encode(["mensaje" => "Notificación actualizada correctamente"]);
                break;

            case 'DELETE':
                IF (isset($_GET['id'])) {
                    $id = intval($_GET['id']);

                    $notificacionesDAO->borrar($id);

                    http_response_code(200);
                    echo json_encode(["mensaje" => "Notificación $id eliminada de la base de datos"]);
                } else {
                    http_response_code(400);
                    echo json_encode(["error" => "Falta el ID de la notificación a eliminar"]);
                }
                break;
                
            default:
                http_response_code(405);
                echo json_encode(["error" => "Método HTTP no permitido"]);
            break;

    }
    
    exit;

?>
