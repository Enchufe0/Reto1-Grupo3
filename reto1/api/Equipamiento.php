<?php
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../config/conexion.php';
require_once __DIR__ . '/../model/Equipamientos.php';
require_once __DIR__ . '/../model/CategoriaEquipamiento.php';
require_once __DIR__ . '/../dao/EquipamientoDAO.php';

use enumCategoria\CategoriaEquipamiento;

ob_start();
$db = (new Database())->getConnection();
ob_end_clean();

if (!$db instanceof PDO) {
    http_response_code(500);
    echo json_encode(['error' => 'Error de conexión con la base de datos'], JSON_UNESCAPED_UNICODE);
    exit;
}

$equipamientoDAO = new EquipamientoDAO($db);
$metodo = $_SERVER['REQUEST_METHOD'];

switch ($metodo) {

    case 'GET':
        if (isset($_GET['id'])) {
            $id = (int) $_GET['id'];
            $equipo = $equipamientoDAO->buscarPorId($id);

            if ($equipo === null) {
                http_response_code(404);
                echo json_encode(['error' => "No existe ningún equipamiento con ID $id"], JSON_UNESCAPED_UNICODE);
                break;
            }

            $categoria = $equipo->getCategoria();
            $catTexto = ($categoria instanceof CategoriaEquipamiento) ? $categoria->value : (string) $categoria;

            http_response_code(200);
            echo json_encode([
                'id_equipamiento'      => $equipo->getIdEquipamiento(),
                'nombre'               => $equipo->getNombre(),
                'descripcion'          => $equipo->getDescripcion(),
                'marca'                => $equipo->getMarca(),
                'modelo'               => $equipo->getModelo(),
                'categoria'            => $catTexto,
                'id_ubicacion_actual'  => $equipo->getIdUbicacionActual()
            ], JSON_UNESCAPED_UNICODE);
        } else {
            $todos = $equipamientoDAO->listarTodos();
            http_response_code(200);
            echo json_encode($todos, JSON_UNESCAPED_UNICODE);
        }
        break;

    case 'POST':
        $cuerpo = file_get_contents('php://input');
        $datos = json_decode($cuerpo, true);
        if (empty($datos)) {
            $datos = $_POST;
        }

        $nombre      = trim($datos['nombre'] ?? '');
        $descripcion = trim($datos['descripcion'] ?? '');
        $marca       = trim($datos['marca'] ?? '');
        $modelo      = trim($datos['modelo'] ?? '');
        $catTexto    = trim($datos['categoria'] ?? 'Otros');
        $idUbicacion = (int) ($datos['id_ubicacion_actual'] ?? 1);

        if ($nombre === '') {
            http_response_code(400);
            echo json_encode(['error' => 'El campo nombre es obligatorio'], JSON_UNESCAPED_UNICODE);
            break;
        }

        $categoriaEnum = CategoriaEquipamiento::tryFrom($catTexto) ?? CategoriaEquipamiento::OTROS;

        // ⚠️ se pasa el ENUM, no ->value (el modelo lo tipa estricto)
        $nuevo = new Equipamientos(0, $nombre, $descripcion, $marca, $modelo, $categoriaEnum, $idUbicacion);
        $idGenerado = $equipamientoDAO->crear($nuevo);

        if ($idGenerado > 0) {
            http_response_code(201);
            echo json_encode([
                'mensaje'         => 'Equipamiento creado con éxito',
                'id_equipamiento' => $idGenerado
            ], JSON_UNESCAPED_UNICODE);
        } else {
            http_response_code(500);
            echo json_encode(['error' => 'No se pudo guardar el equipamiento en la base de datos'], JSON_UNESCAPED_UNICODE);
        }
        break;

    case 'PUT':
        $cuerpo = file_get_contents('php://input');
        $datos = json_decode($cuerpo, true) ?? [];

        $id = (int) ($datos['id_equipamiento'] ?? ($_GET['id'] ?? 0));

        if ($id <= 0) {
            http_response_code(400);
            echo json_encode(['error' => 'ID de equipamiento inválido'], JSON_UNESCAPED_UNICODE);
            break;
        }

        $existente = $equipamientoDAO->buscarPorId($id);
        if ($existente === null) {
            http_response_code(404);
            echo json_encode(['error' => 'Equipamiento no encontrado para actualizar'], JSON_UNESCAPED_UNICODE);
            break;
        }

        $nombre      = trim($datos['nombre'] ?? $existente->getNombre());
        $descripcion = trim($datos['descripcion'] ?? $existente->getDescripcion());
        $marca       = trim($datos['marca'] ?? $existente->getMarca());
        $modelo      = trim($datos['modelo'] ?? $existente->getModelo());
        $catTexto    = trim($datos['categoria'] ?? $existente->getCategoria()->value);
        $idUbicacion = (int) ($datos['id_ubicacion_actual'] ?? $existente->getIdUbicacionActual());

        $catEnum = CategoriaEquipamiento::tryFrom($catTexto) ?? CategoriaEquipamiento::OTROS;

        $actualizado = new Equipamientos($id, $nombre, $descripcion, $marca, $modelo, $catEnum, $idUbicacion);

        if ($equipamientoDAO->actualizar($actualizado)) {
            http_response_code(200);
            echo json_encode(['mensaje' => 'Equipamiento actualizado con éxito'], JSON_UNESCAPED_UNICODE);
        } else {
            http_response_code(500);
            echo json_encode(['error' => 'Error al actualizar el equipamiento'], JSON_UNESCAPED_UNICODE);
        }
        break;

    case 'DELETE':
        $id = (int) ($_GET['id'] ?? 0);
        if ($id <= 0) {
            $cuerpo = file_get_contents('php://input');
            $datos = json_decode($cuerpo, true);
            $id = (int) ($datos['id_equipamiento'] ?? 0);
        }

        if ($id <= 0) {
            http_response_code(400);
            echo json_encode(['error' => 'Falta el parámetro ID del equipamiento a eliminar'], JSON_UNESCAPED_UNICODE);
            break;
        }

        if ($equipamientoDAO->eliminar($id)) {
            http_response_code(200);
            echo json_encode(['mensaje' => "Equipamiento $id eliminado correctamente"], JSON_UNESCAPED_UNICODE);
        } else {
            http_response_code(500);
            echo json_encode(['error' => 'No se pudo eliminar el equipamiento'], JSON_UNESCAPED_UNICODE);
        }
        break;

    default:
        http_response_code(405);
        echo json_encode(['error' => 'Método HTTP no permitido'], JSON_UNESCAPED_UNICODE);
        break;
}