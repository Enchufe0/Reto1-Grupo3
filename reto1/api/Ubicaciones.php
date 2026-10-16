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
                //aqui solo buscaria esa caja
                //$ubicaciones = $ubicacionesDAO->obtenerPorId($id);

                echo json_encode(["mesnaje"]);
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
                
            //el carretillero le el contenido del paquete, el json
            $contenido = file_get_contents("php://input");
            $datos = json_decode($contenido, true);

            //aqui llamamos al DAO: $ubicacionesDAO->crear
            http_response_code(201); //crado con exiti
            echo json_encode(["mesane"]);
            break;

        //CASO 3. PUT - ACTUALIZAR
        case 'PUT':

            //leer los nuevos datos que nos envian
            $contenido = file_get_contents("php://input");
            $datos = json_decode($contenido, true);

            //aqui llamariamos al dao: $ubicacionesDAO->actualizar

            http_response_code(200); //todo ok
            echo json_encode(["mesnaje"]);
            break;

        //CASO 4. DELETE - BORRAR
        case 'DELETE':
            if (isset($_GET['id'])) {
                $id = intval($_GET['id']);

                //aqui llamariamos al dao: $ubicacionesDAO->borrar($id)

                http_response_code(200); //todo ok
                echo json_encode(["mensaje"]);
            } else {
                http_response_code(400); //error
                echo json_encode(["error"]);
            }
            break;

            


                

    }   
?>