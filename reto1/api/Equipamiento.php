<?php
header('Content-Type: application/json; charset=utf-8');
ini_set('display_errors', '0');
error_reporting(E_ALL);

// Herramientas necesarias xd
require_once __DIR__ . '/../../model/Equipamientos.php';
require_once __DIR__ . '/../../model/CategoriaEquipamiento.php';
require_once __DIR__ . '/../../dao/EquipamientoDAO.php';
require_once __DIR__ . '/../../dao/UbicacionesDAO.php';

use enumCategoria\CategoriaEquipamiento;

// Conexión con la base de datos
try {
    $conexion = new PDO(
        "mysql:host=localhost;dbname=reto_2daw;charset=utf8",
        "root",
        "",
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]
    );
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(
        ["error" => "Fallo de conexión a la base de datos"],
        JSON_UNESCAPED_UNICODE
    );
    exit;
}

$equipamientoDAO = new EquipamientoDAO($conexion);
$ubicacionesDAO  = new UbicacionesDAO($conexion);

$metodo = $_SERVER["REQUEST_METHOD"];


function responder(int $codigo, array $cuerpo): void {
    http_response_code($codigo);
    echo json_encode(
        $cuerpo,
        JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
    );
    exit;
}

function equipamientoToArray(Equipamientos $e): array {
    return [
        'id_equipamiento'     => $e->getIdEquipamiento(),
        'nombre'              => $e->getNombre(),
        'descripcion'         => $e->getDescripcion(),
        'marca'               => $e->getMarca(),
        'modelo'              => $e->getModelo(),
        'categoria'           => $e->getCategoria()->value,
        'id_ubicacion_actual' => $e->getIdUbicacionActual(),
    ];
}

function validarPayload(array $datos, bool $esCreacion): array {
    $errores = [];

    if ($esCreacion || isset($datos['nombre'])) {
        $nombre = trim((string)($datos['nombre'] ?? ''));
        if ($nombre === '') {
            $errores['nombre'] = "El campo 'nombre' es obligatorio";
        } elseif (strlen($nombre) > 100) {
            $errores['nombre'] = "Máximo 100 caracteres";
        }
    }

    if ($esCreacion || isset($datos['id_ubicacion'])) {
        $idUbi = (int)($datos['id_ubicacion'] ?? 0);
        if ($idUbi <= 0) {
            $errores['id_ubicacion'] = "El campo 'id_ubicacion' es obligatorio";
        }
    }

    if (isset($datos['categoria']) && $datos['categoria'] !== '') {
        if (CategoriaEquipamiento::tryFrom((string)$datos['categoria']) === null) {
            $validas = array_map(fn($c) => $c->value, CategoriaEquipamiento::cases());
            $errores['categoria'] = "Valor inválido. Valores permitidos: " . implode(', ', $validas);
        }
    }

    return $errores;
}



switch ($metodo) {

    // ═══ GET — LISTAR / BUSCAR POR ID ═══
    case 'GET':
        try {
            // GET /api/equipamientos.php?id=5
            if (isset($_GET['id'])) {
                $id = (int) $_GET['id'];
                if ($id <= 0) {
                    responder(400, ["error" => "ID inválido"]);
                }

                $equipo = $equipamientoDAO->buscarPorId($id);
                if (!$equipo) {
                    responder(404, ["error" => "Equipamiento no encontrado"]);
                }

                responder(200, equipamientoToArray($equipo));
            }

            // GET /api/equipamientos.php  → listado completo
            $lista = $equipamientoDAO->listarTodos();
            responder(200, $lista);

        } catch (Throwable $e) {
            error_log("Equipamientos GET: " . $e->getMessage());
            responder(500, ["error" => "Error interno del servidor"]);
        }

    // ═══ POST — CREAR ═══
    case 'POST':
        try {
            $raw   = file_get_contents("php://input");
            $datos = json_decode($raw, true);

            if (!is_array($datos)) {
                responder(400, ["error" => "JSON inválido o cuerpo vacío"]);
            }

            $errores = validarPayload($datos, true);
            if ($errores) {
                responder(422, ["errors" => $errores]);
            }

            // Convertir categoría al enum (o caer al valor por defecto)
            $categoriaEnum = CategoriaEquipamiento::tryFrom((string)($datos['categoria'] ?? ''))
                ?? CategoriaEquipamiento::OTROS;

            $nuevo = new Equipamientos(
                0,
                trim((string) $datos['nombre']),
                trim((string) ($datos['descripcion'] ?? '')),
                trim((string) ($datos['marca']       ?? '')),
                trim((string) ($datos['modelo']      ?? '')),
                $categoriaEnum,
                (int) $datos['id_ubicacion']
            );

            $id = $equipamientoDAO->crear($nuevo);

            if ($id === 0) {
                responder(500, ["error" => "No se pudo crear el equipamiento"]);
            }

            responder(201, [
                "mensaje"         => "Equipamiento creado correctamente",
                "id_equipamiento" => $id,
            ]);

        } catch (Throwable $e) {
            error_log("Equipamientos POST: " . $e->getMessage());
            responder(500, ["error" => "Error interno del servidor"]);
        }

    // ═══ PUT — ACTUALIZAR ═══
    case 'PUT':
        try {
            $raw   = file_get_contents("php://input");
            $datos = json_decode($raw, true);

            if (!is_array($datos)) {
                responder(400, ["error" => "JSON inválido o cuerpo vacío"]);
            }

            $id = (int) ($datos['id_equipamiento'] ?? $_GET['id'] ?? 0);
            if ($id <= 0) {
                responder(422, ["error" => "Falta 'id_equipamiento' para actualizar"]);
            }

            $existente = $equipamientoDAO->buscarPorId($id);
            if (!$existente) {
                responder(404, ["error" => "Equipamiento no encontrado"]);
            }

            $errores = validarPayload($datos, false);
            if ($errores) {
                responder(422, ["errors" => $errores]);
            }

            $categoriaStr = $datos['categoria'] ?? $existente->getCategoria()->value;
            $categoriaEnum = CategoriaEquipamiento::tryFrom((string)$categoriaStr)
                ?? CategoriaEquipamiento::OTROS;

            $actualizado = new Equipamientos(
                $id,
                trim((string) ($datos['nombre']      ?? $existente->getNombre())),
                trim((string) ($datos['descripcion'] ?? $existente->getDescripcion())),
                trim((string) ($datos['marca']       ?? $existente->getMarca())),
                trim((string) ($datos['modelo']      ?? $existente->getModelo())),
                $categoriaEnum,
                (int) ($datos['id_ubicacion'] ?? $existente->getIdUbicacionActual())
            );

            $ok = $equipamientoDAO->actualizar($actualizado);
            if (!$ok) {
                responder(500, ["error" => "No se pudo actualizar el equipamiento"]);
            }

            responder(200, [
                "mensaje" => "Equipamiento actualizado correctamente",
                "equipamiento" => equipamientoToArray($actualizado),
            ]);

        } catch (Throwable $e) {
            error_log("Equipamientos PUT: " . $e->getMessage());
            responder(500, ["error" => "Error interno del servidor"]);
        }

    // ═══ DELETE — BORRAR ═══
    case 'DELETE':
        try {
            $id = (int) ($_GET['id'] ?? 0);
            if ($id <= 0) {
                responder(400, ["error" => "Falta el parámetro 'id'"]);
            }

            $existente = $equipamientoDAO->buscarPorId($id);
            if (!$existente) {
                responder(404, ["error" => "Equipamiento no encontrado"]);
            }

            $ok = $equipamientoDAO->eliminar($id);
            if (!$ok) {
                responder(500, ["error" => "No se pudo eliminar el equipamiento"]);
            }

            responder(200, ["mensaje" => "Equipamiento eliminado correctamente"]);

        } catch (Throwable $e) {
            error_log("Equipamientos DELETE: " . $e->getMessage());
            responder(500, ["error" => "Error interno del servidor"]);
        }

    // ═══ CUALQUIER OTRO MÉTODO ═══
    default:
        responder(405, ["error" => "Método HTTP no soportado: $metodo"]);
}