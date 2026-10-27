<?php

    //le avisas al navegador que no es una web, que es un json
    header('Content-Type: application/json; charset=utf-8');

    //traemos las hrramientas necesarias
    require_once __DIR__ . '/../model/Ubicaciones.php';
    require_once __DIR__ . '/../dao/UbicacionesDAO.php';

    //conexion con la base de datos
    try {
        $conexion = new PDO("mysql:host=localhost;dbname=reto_2daw;charset=utf8", "root", "");
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(["error" => "Fallo de conexion a la base de datos"]);
        exit;
    } 

    //instanciamos el dao y pedimos los datos(transpalet para mover los pales de datos)
    $ubicacionesDAO = new UbicacionesDAO($conexion);
    
    //leer el "albaran" que metodo http usa el cliente?
    $metodo = $_SERVER["REQUEST_METHOD"];

    switch ($metodo) {

        //CASO 1. GET - LEER
        case 'GET':
            
            //si el cliente nos da un id especifico (?id=3)
            if(isset($_GET['id'])) {
                $id = intval($_GET['id']);
                
                // Llamamos al DAO para buscar la caja exacta
                $ubicacion = $ubicacionesDAO->obtenerPorId($id);

                // Si la caja existe en el almacén...
                if ($ubicacion !== null) {
                    $resultado = [
                        'id_ubicacion'  => $ubicacion->getIdUbicacion(),
                        'nombre'        => $ubicacion->getNombre(),
                        'descripcion'   => $ubicacion->getDescripcion()
                    ];
                    
                    http_response_code(200); 
                    echo json_encode($resultado, JSON_UNESCAPED_UNICODE);
                } 
                // Si la caja no existe (es null)...
                else {
                    http_response_code(404); 
                    echo json_encode(["error" => "No se encontró ninguna ubicación con el ID: $id"]);
                }
            }

            //si no nos da id, es que quiere TODAS las cajas
            else {
                $listaUbicaciones = $ubicacionesDAO->obtenerTodas();

                //mete los datos en el "pale" (array) para que qupan por la puerta
                $resultado = [];
                foreach ($listaUbicaciones as $ubicacion) {
                    $resultado[] = [
                        'id_ubicacion' => $ubicacion->getIdUbicacion(),
                        'nombre' => $ubicacion->getNombre(),
                        'descripcion' => $ubicacion->getDescripcion()
                    ];
                }

                http_response_code(200); //todo ok
                echo json_encode($resultado, JSON_UNESCAPED_UNICODE); //enviamos el camion
            }
            break;

        //CASO 2. POST - CREAR
        case 'POST':
                
            $contenido = file_get_contents("php://input");
            $datos = json_decode($contenido, true);

            // 1. Comprobamos que el paquete trae el nombre y la descripción
            if (isset($datos['nombre']) && isset($datos['descripcion'])) {
                
                // 2. Construimos la caja (ID 0 porque MySQL pone el autoincrement)
                $nuevaUbicacion = new Ubicaciones(0, $datos['nombre'], $datos['descripcion']);
                
                // 3. El carretillero la mete en el almacén
                $exito = $ubicacionesDAO->crear($nuevaUbicacion);

                if ($exito) {
                    http_response_code(201); // 201 significa "Creado"
                    echo json_encode(["mensaje" => "Ubicación creada con éxito"]);
                } else {
                    http_response_code(500); 
                    echo json_encode(["error" => "Error al guardar en la base de datos"]);
                }
            } else {
                http_response_code(400); // 400 significa "Petición incorrecta (faltan datos)"
                echo json_encode(["error" => "Faltan datos obligatorios (nombre o descripcion)"]);
            }
            break;
                
        //CASO 3. PUT - ACTUALIZAR
        case 'PUT':

            //leer los nuevos datos que nos envian
            $contenido = file_get_contents("php://input");
            $datos = json_decode($contenido, true);

            // 1. Aquí exigimos los 3 datos, incluido el ID para saber qué caja modificar
            if (isset($datos['id_ubicacion']) && isset($datos['nombre']) && isset($datos['descripcion'])) {
                
                // 2. Construimos la caja con su ID real
                $ubicacionModificada = new Ubicaciones(
                    intval($datos['id_ubicacion']), 
                    $datos['nombre'], 
                    $datos['descripcion']
                );

                // 3. El carretillero sobreescribe los datos
                $exito = $ubicacionesDAO->actualizar($ubicacionModificada);

                if ($exito) {
                    http_response_code(200); 
                    echo json_encode(["mensaje" => "Ubicación actualizada correctamente"]);
                } else {
                    http_response_code(500);
                    echo json_encode(["error" => "Error al actualizar la base de datos"]);
                }
            } else {
                http_response_code(400); 
                echo json_encode(["error" => "Faltan datos (se requiere id_ubicacion, nombre y descripcion)"]);
            }
            break;
            
        //CASO 4. DELETE - BORRAR
        case 'DELETE':
            if (isset($_GET['id'])) {
                $id = intval($_GET['id']);
                
                // Ejecutamos el borrado en el DAO
                $ubicacionesDAO->borrar($id);

                http_response_code(200); 
                echo json_encode(["mensaje" => "Ubicación eliminada correctamente"]);
            } else {
                http_response_code(400); 
                echo json_encode(["error" => "Falta el ID en la petición"]);
            }
            break;
    }   

    exit;
?>