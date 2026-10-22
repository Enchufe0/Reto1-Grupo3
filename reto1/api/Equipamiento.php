<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/conexion.php';
require_once __DIR__ . '/../model/Equipamientos.php';
require_once __DIR__ . '/../model/CategoriaEquipamiento.php';
require_once __DIR__ . '/../dao/EquipamientoDAO.php';
require_once __DIR__ . '/../dao/HistoricoDAO.php';

use enumCategoria\CategoriaEquipamiento;

function requiereLoginApi(): void
{
    if (!isset($_SESSION['usuario_id']) && !isset($_SESSION['usuario'])) {
        http_response_code(401);
        echo json_encode(['error' => 'No autorizado. Inicie sesión.'], JSON_UNESCAPED_UNICODE);
        exit;
    }
}

function requiereAdminApi(): void
{
    requiereLoginApi();
    if (strtoupper((string) ($_SESSION['rol'] ?? '')) !== 'ADMIN') {
        http_response_code(403);
        echo json_encode(['error' => 'Se requieren permisos de administrador.'], JSON_UNESCAPED_UNICODE);
        exit;
    }
}

requiereLoginApi();   

ob_start();
$db = (new Database())->getConnection();
ob_end_clean();

if (!$db instanceof PDO) {
    http_response_code(500);
    echo json_encode(['error' => 'Error de conexión con la base de datos'], JSON_UNESCAPED_UNICODE);
    exit;
}

$equipamientoDAO = new EquipamientoDAO($db);
$historicoDAO    = new HistoricoDAO($db);
$metodo          = $_SERVER['REQUEST_METHOD'];

function leerJson(): array
{
    $cuerpo = file_get_contents('php://input');
    $datos  = json_decode($cuerpo, true);
    if (!is_array($datos) || $datos === []) {
        $datos = $_POST;
    }
    return is_array($datos) ? $datos : [];
}

function equipoToArray(Equipamientos $e): array
{
    $cat = $e->getCategoria();
    return [
        'id_equipamiento'     => $e->getIdEquipamiento(),
        'nombre'              => $e->getNombre(),
        'descripcion'         => $e->getDescripcion(),
        'marca'               => $e->getMarca(),
        'modelo'              => $e->getModelo(),
        'categoria'           => $cat instanceof CategoriaEquipamiento ? $cat->value : (string) $cat,
        'id_ubicacion_actual' => $e->getIdUbicacionActual(),
    ];
}

switch ($metodo) {

    /*
     *  GET  (USER y ADMIN)
    */
    case 'GET':
        if (isset($_GET['id'])) {
            $id     = (int) $_GET['id'];
            $equipo = $equipamientoDAO->buscarPorId($id);

            if ($equipo === null) {
                http_response_code(404);
                echo json_encode(['error' => "No existe ningún equipamiento con ID $id"], JSON_UNESCAPED_UNICODE);
                break;
            }

            http_response_code(200);
            echo json_encode(equipoToArray($equipo), JSON_UNESCAPED_UNICODE);
            break;
        }


        if (isset($_GET['categoria'])) {
            $catEnum = CategoriaEquipamiento::tryFrom((string) $_GET['categoria']) ?? CategoriaEquipamiento::OTROS;
            $filas   = array_filter(
                $equipamientoDAO->listarTodos(),
                fn ($f) => ($f['categoria'] ?? null) === $catEnum->value
            );
            http_response_code(200);
            echo json_encode(array_values($filas), JSON_UNESCAPED_UNICODE);
            break;
        }

        // Listado completo
        $todos = $equipamientoDAO->listarTodos();
        http_response_code(200);
        echo json_encode($todos, JSON_UNESCAPED_UNICODE);
        break;

    /*
     *  POST  
     */
    case 'POST':
        $datos = leerJson();

        /* ---------- Acción especial: TRASLADO (USER y ADMIN) ---------- */
        if (($datos['accion'] ?? null) === 'cambiar_ubicacion') {
            $idEquipo = (int) ($datos['id_equipamiento'] ?? 0);
            $idUbi    = (int) ($datos['id_ubicacion']    ?? 0);

            if ($idEquipo <= 0 || $idUbi <= 0) {
                http_response_code(422);
                echo json_encode(['error' => 'Se requieren id_equipamiento e id_ubicacion válidos'], JSON_UNESCAPED_UNICODE);
                break;
            }

            $equipo = $equipamientoDAO->buscarPorId($idEquipo);
            if ($equipo === null) {
                http_response_code(404);
                echo json_encode(['error' => "No existe el equipamiento $idEquipo"], JSON_UNESCAPED_UNICODE);
                break;
            }

            // 1) Actualiza equipamientos.id_ubicacion_actual
            $equipo->setIdUbicacionActual($idUbi);
            if (!$equipamientoDAO->actualizar($equipo)) {
                http_response_code(500);
                echo json_encode(['error' => 'No se pudo actualizar la ubicación del equipamiento'], JSON_UNESCAPED_UNICODE);
                break;
            }

            // 2) Cierra la ubicación anterior y abre la nueva en histórico (transacción interna)
            if (!$historicoDAO->registrarTraslado($idEquipo, $idUbi)) {
                http_response_code(500);
                echo json_encode(['error' => 'No se pudo registrar el histórico del traslado'], JSON_UNESCAPED_UNICODE);
                break;
            }

            http_response_code(200);
            echo json_encode([
                'mensaje'         => 'Traslado registrado correctamente',
                'id_equipamiento' => $idEquipo,
                'id_ubicacion'    => $idUbi,
            ], JSON_UNESCAPED_UNICODE);
            break;
        }

        /* ---------- Alta de equipo: solo ADMIN ---------- */
        requiereAdminApi();

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

        $nuevo      = new Equipamientos(0, $nombre, $descripcion, $marca, $modelo, $categoriaEnum, $idUbicacion);
        $idGenerado = $equipamientoDAO->crear($nuevo);

        if ($idGenerado > 0) {
            http_response_code(201);
            echo json_encode([
                'mensaje'         => 'Equipamiento creado con éxito',
                'id_equipamiento' => $idGenerado,
            ], JSON_UNESCAPED_UNICODE);
        } else {
            http_response_code(500);
            echo json_encode(['error' => 'No se pudo guardar el equipamiento en la base de datos'], JSON_UNESCAPED_UNICODE);
        }
        break;

    /* ---------------------------------------------------------- *
     *  PUT  (solo ADMIN)
     * ---------------------------------------------------------- */
    case 'PUT':
        requiereAdminApi();

        $datos = leerJson();
        $id    = (int) ($datos['id_equipamiento'] ?? ($_GET['id'] ?? 0));

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

        $catActual      = $existente->getCategoria();
        $catTextoActual = $catActual instanceof CategoriaEquipamiento ? $catActual->value : (string) $catActual;

        $nombre      = trim($datos['nombre']            ?? $existente->getNombre());
        $descripcion = trim($datos['descripcion']       ?? $existente->getDescripcion());
        $marca       = trim($datos['marca']             ?? $existente->getMarca());
        $modelo      = trim($datos['modelo']            ?? $existente->getModelo());
        $catTexto    = trim($datos['categoria']         ?? $catTextoActual);
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

    /* ---------------------------------------------------------- *
     *  DELETE  (solo ADMIN)
     * ---------------------------------------------------------- */
    case 'DELETE':
        requiereAdminApi();

        $id = (int) ($_GET['id'] ?? 0);
        if ($id <= 0) {
            $cuerpo = file_get_contents('php://input');
            $datos  = json_decode($cuerpo, true);
            $id     = (int) ($datos['id_equipamiento'] ?? 0);
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

    /* ---------------------------------------------------------- *
     *  Otros métodos
     * ---------------------------------------------------------- */
    default:
        http_response_code(405);
        echo json_encode(['error' => 'Método HTTP no permitido'], JSON_UNESCAPED_UNICODE);
        break;
}