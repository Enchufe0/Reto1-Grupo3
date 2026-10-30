<?php

    use enumNotificaciones\EstadoNotificaciones; 

    header('Content-Type: application/json; charset=utf-8');

    require_once __DIR__ . '/../model/Notificaciones.php';
    require_once __DIR__ . '/../dao/NotificacionesDAO.php';
    require_once __DIR__ . '/../model/EstadoNotificaciones.php';

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
                $contenido = file_get_contents("php://input");
                $datos = json_decode($contenido, true);
                
                // Comprobamos que nos envían todos los datos necesarios
                if (isset($datos['titulo'], $datos['descripcion'], $datos['fecha_creacion'], $datos['estado'], $datos['id_usuario'])) {
                    
                    try {
                        // Convertimos el texto del estado en el Enum correspondiente
                        $estado_enum = EstadoNotificaciones::from($datos['estado']);
                        
                        // Armamos la nueva caja (id 0 porque la base de datos lo genera)
                        $nuevaNotificacion = new Notificaciones(
                            0, 
                            $datos['titulo'], 
                            $datos['descripcion'], 
                            $datos['fecha_creacion'], 
                            $estado_enum, 
                            intval($datos['id_usuario'])
                        );

                        // Mandamos la caja al almacén
                        $exito = $notificacionesDAO->crear($nuevaNotificacion);

                        if ($exito) {
                            http_response_code(201); 
                            echo json_encode(["mensaje" => "Notificación creada con éxito"]);
                        } else {
                            http_response_code(500); 
                            echo json_encode(["error" => "Error al guardar en la base de datos"]);
                        }
                    } catch (ValueError $e) {
                        // Si el estado que envían no existe en el Enum (ej: envían "patata" en vez de "LEIDA")
                        http_response_code(400);
                        echo json_encode(["error" => "El estado de la notificación no es válido"]);
                    }

                } else {
                    http_response_code(400); 
                    echo json_encode(["error" => "Faltan datos obligatorios para crear la notificación"]);
                }
                break;

            case 'PUT':
                $contenido = file_get_contents("php://input");
                $datos = json_decode($contenido, true);

                // Aquí exigimos también el id_notificacion para saber cuál modificar
                if (isset($datos['id_notificacion'], $datos['titulo'], $datos['descripcion'], $datos['fecha_creacion'], $datos['estado'], $datos['id_usuario'])) {
                    
                    try {
                        $estado_enum = EstadoNotificaciones::from($datos['estado']);
                        
                        // Armamos la caja con su ID real
                        $notificacionModificada = new Notificaciones(
                            intval($datos['id_notificacion']), 
                            $datos['titulo'], 
                            $datos['descripcion'], 
                            $datos['fecha_creacion'], 
                            $estado_enum, 
                            intval($datos['id_usuario'])
                        );

                        // 2. Forzamos la asignación del ID por si el constructor lo estaba ignorando o desordenando
                        $notificacionModificada->setIdNotificacion(intval($datos['id_notificacion']));

                        // Sobreescribimos en el almacén
                        $exito = $notificacionesDAO->actualizar($notificacionModificada);

                        if ($exito) {
                            http_response_code(200);
                            echo json_encode(["mensaje" => "Notificación actualizada correctamente"]);
                        } else {
                            http_response_code(500);
                            echo json_encode(["error" => "Error al actualizar la base de datos"]);
                        }
                    } catch (ValueError $e) {
                        http_response_code(400);
                        echo json_encode(["error" => "El estado de la notificación no es válido"]);
                    }

                } else {
                    http_response_code(400);
                    echo json_encode(["error" => "Faltan datos obligatorios para actualizar"]);
                }
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
