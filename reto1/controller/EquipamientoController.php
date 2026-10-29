<?php
require_once __DIR__ . '/../dao/EquipamientoDAO.php';
require_once __DIR__ . '/../model/CategoriaEquipamiento.php';

use enumCategoria\CategoriaEquipamiento;

class EquipamientoController {
    private EquipamientoDAO $dao;

    public function __construct(PDO $db) {
        $this->dao = new EquipamientoDAO($db);
    }

    public function listar(): array {
        return array_map([$this, 'normalizarEquipo'], $this->dao->listar());
    }

    public function ver($id): array {
        $id = $this->validarId($id, 'ID de equipamiento');
        $equipo = $this->dao->buscarPorId($id);
        if ($equipo === null) { // FIX: Changed from false to null
            throw new Exception('Equipamiento no encontrado', 404);
        }
        return $this->normalizarEquipo($equipo);
    }

    public function crear($datos): bool {
        $datos = $this->validarDatos($datos);
        $nombre = $this->texto($datos, 'nombre');
        if ($nombre === '') {
            throw new Exception('El nombre es obligatorio', 400);
        }
        if (!isset($datos['categoria'])) {
            throw new Exception('La categoría es obligatoria', 400);
        }

        $creado = $this->dao->crear(
            $nombre,
            $this->texto($datos, 'descripcion'),
            $this->texto($datos, 'marca'),
            $this->texto($datos, 'modelo'),
            $this->categoria($datos['categoria'])->value
        );
        if (!$creado) {
            throw new Exception('No se pudo crear el equipamiento', 500);
        }
        return true;
    }

    public function actualizar($id, $datos): bool {
        $datos = $this->validarDatos($datos);
        $id = $this->validarId($id, 'ID de equipamiento');
        $existente = $this->dao->buscarPorId($id);

        if ($existente === null) { // FIX: Changed from false to null
            throw new Exception('Equipamiento no encontrado', 404);
        }

        $nombre = $this->texto($datos, 'nombre', $existente['nombre']);
        if ($nombre === '') {
            throw new Exception('El nombre es obligatorio', 400);
        }

        $categoriaValue = $existente['categoria'];
        if (isset($datos['categoria'])) {
            $catEnum = CategoriaEquipamiento::tryFrom(trim($datos['categoria']));
            if ($catEnum !== null) {
                $categoriaValue = $catEnum->value;
            }
        }

        $actualizado = $this->dao->actualizar(
            $id,
            $nombre,
            $this->texto($datos, 'descripcion', $existente['descripcion']),
            $this->texto($datos, 'marca', $existente['marca']),
            $this->texto($datos, 'modelo', $existente['modelo']),
            $categoriaValue
        );

        if (!$actualizado) {
            throw new Exception('No se pudo actualizar el equipamiento', 500);
        }
        return true;
    }

    public function eliminar($id): bool {
        $id = $this->validarId($id, 'ID de equipamiento');
        if ($this->dao->buscarPorId($id) === null) { // FIX: Changed from false to null
            throw new Exception('Equipamiento no encontrado', 404);
        }
        if (!$this->dao->eliminar($id)) {
            throw new Exception('No se pudo eliminar el equipamiento', 500);
        }
        return true;
    }

    private function texto(array $datos, string $campo, string $porDefecto = ''): string {
        $valor = $datos[$campo] ?? $porDefecto;
        if (!is_string($valor) && !is_numeric($valor)) {
            return $porDefecto;
        }
        return trim((string) $valor);
    }

    private function validarDatos($datos): array {
        if (!is_array($datos)) {
            throw new Exception('Los datos no son válidos', 400);
        }
        return $datos;
    }

    private function categoria($valor): CategoriaEquipamiento {
        $categoria = CategoriaEquipamiento::tryFrom(trim((string)$valor));
        if ($categoria === null) {
            return CategoriaEquipamiento::OTROS;
        }
        return $categoria;
    }

    private function validarId($id, string $campo): int {
        $id = filter_var($id, FILTER_VALIDATE_INT);
        if ($id === false || $id <= 0) {
            throw new Exception("$campo inválido", 400);
        }
        return $id;
    }

    private function normalizarEquipo(array $equipo): array {
        return [
            'id_equipamiento' => (int) $equipo['id_equipamiento'],
            'nombre' => $equipo['nombre'],
            'descripcion' => $equipo['descripcion'],
            'marca' => $equipo['marca'],
            'modelo' => $equipo['modelo'],
            'categoria' => $equipo['categoria'],
            'id_ubicacion_actual' => (int) $equipo['id_ubicacion_actual'],
            'ubicacion' => $equipo['ubicacion'] ?? null,
        ];
    }
}