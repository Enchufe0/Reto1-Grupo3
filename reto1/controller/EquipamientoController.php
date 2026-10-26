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

        if ($equipo === false) {
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

        if ($existente === false) {
            throw new Exception('Equipamiento no encontrado', 404);
        }

        $nombre = $this->texto($datos, 'nombre', $existente['nombre']);
        if ($nombre === '') {
            throw new Exception('El nombre es obligatorio', 400);
        }

        $actualizado = $this->dao->actualizar(
            $id,
            $nombre,
            $this->texto($datos, 'descripcion', $existente['descripcion']),
            $this->texto($datos, 'marca', $existente['marca']),
            $this->texto($datos, 'modelo', $existente['modelo']),
            isset($datos['categoria'])
                ? $this->categoria($datos['categoria'])->value
                : $existente['categoria']
        );

        if (!$actualizado) {
            throw new Exception('No se pudo actualizar el equipamiento', 500);
        }

        return true;
    }

    public function cambiarUbicacion($id, $idUbicacion): bool {
        $id = $this->validarId($id, 'ID de equipamiento');
        $idUbicacion = filter_var($idUbicacion, FILTER_VALIDATE_INT);
        $idUbicacion = $this->validarId($idUbicacion, 'ID de ubicación');

        if ($this->dao->buscarPorId($id) === false) {
            throw new Exception('Equipamiento no encontrado', 404);
        }

        if (!$this->dao->cambiarUbicacion($id, $idUbicacion)) {
            throw new Exception('No se pudo cambiar la ubicación del equipamiento', 500);
        }

        return true;
    }

    public function eliminar($id): bool {
        $id = $this->validarId($id, 'ID de equipamiento');

        if ($this->dao->buscarPorId($id) === false) {
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
            throw new Exception("El campo $campo debe ser texto", 400);
        }

        return trim((string) $valor);
    }

    private function validarDatos($datos): array {
        if (!is_array($datos)) {
            throw new Exception('Los datos del equipamiento no son válidos', 400);
        }

        return $datos;
    }

    private function categoria($valor): CategoriaEquipamiento {
        if (!is_string($valor)) {
            throw new Exception('La categoría no es válida', 400);
        }

        $categoria = CategoriaEquipamiento::tryFrom(trim($valor));
        if ($categoria === null) {
            throw new Exception('La categoría no es válida', 400);
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