<?php
require_once __DIR__ . '/../dao/EquipamientoDAO.php';
require_once __DIR__ . '/../model/CategoriaEquipamiento.php';
use enumCategoria\CategoriaEquipamiento;

class EquipamientoController
{
    private EquipamientoDAO $equipamientoDAO;

    public function __construct(PDO $db)
    {
        $this->equipamientoDAO = new EquipamientoDAO($db);
    }
    public function listar(): array
    {
        $rawEquipmentList = $this->equipamientoDAO->listar();
        
        $normalizedList = [];

        foreach ($rawEquipmentList as $equipmentRow) {
            $normalizedList[] = $this->normalizarEquipo($equipmentRow);
        }

        return $normalizedList;
    }

    public function ver(int $equipmentId): array
    {
        $validatedId = $this->validarId($equipmentId, 'ID de equipamiento');
        
        $equipmentRow = $this->equipamientoDAO->buscarPorId($validatedId);

        if ($equipmentRow === null) {
            throw new Exception('Equipamiento no encontrado', 404);
        }

        return $this->normalizarEquipo($equipmentRow);
    }

    public function crear(array $requestData): bool
    {
        $this->validarDatos($requestData);
        
        $nombre = $this->limpiarTexto($requestData, 'nombre');
        if ($nombre === '') {
            throw new Exception('El nombre es obligatorio', 400);
        }

        if (!isset($requestData['categoria'])) {
            throw new Exception('La categoría es obligatoria', 400);
        }

        $categoryEnum = $this->obtenerCategoria($requestData['categoria']);

        $isCreated = $this->equipamientoDAO->crear(
            $nombre,
            $this->limpiarTexto($requestData, 'descripcion'),
            $this->limpiarTexto($requestData, 'marca'),
            $this->limpiarTexto($requestData, 'modelo'),
            $categoryEnum->value
        );

        if (!$isCreated) {
            throw new Exception('No se pudo crear el equipamiento', 500);
        }

        return true;
    }

    public function actualizar(int $equipmentId, array $requestData): bool
    {
        $this->validarDatos($requestData);
        $validatedId = $this->validarId($equipmentId, 'ID de equipamiento');
        
        $existingEquipment = $this->equipamientoDAO->buscarPorId($validatedId);
        if ($existingEquipment === null) {
            throw new Exception('Equipamiento no encontrado', 404);
        }

        $nombre = $this->limpiarTexto($requestData, 'nombre', $existingEquipment['nombre']);
        if ($nombre === '') {
            throw new Exception('El nombre es obligatorio', 400);
        }

        $categoryValue = $existingEquipment['categoria'];
        if (isset($requestData['categoria'])) {
            $categoryEnum = $this->obtenerCategoria($requestData['categoria']);
            $categoryValue = $categoryEnum->value;
        }

        $isUpdated = $this->equipamientoDAO->actualizar(
            $validatedId,
            $nombre,
            $this->limpiarTexto($requestData, 'descripcion', $existingEquipment['descripcion']),
            $this->limpiarTexto($requestData, 'marca', $existingEquipment['marca']),
            $this->limpiarTexto($requestData, 'modelo', $existingEquipment['modelo']),
            $categoryValue
        );

        if (!$isUpdated) {
            throw new Exception('No se pudo actualizar el equipamiento', 500);
        }

        return true;
    }
    public function cambiarUbicacion(int $equipmentId, int $newLocationId): bool
    {
        $validatedEquipmentId = $this->validarId($equipmentId, 'ID de equipamiento');
        $validatedLocationId = $this->validarId($newLocationId, 'ID de ubicación');

        $existingEquipment = $this->equipamientoDAO->buscarPorId($validatedEquipmentId);
        if ($existingEquipment === null) {
            throw new Exception('Equipamiento no encontrado', 404);
        }

        $isMoved = $this->equipamientoDAO->cambiarUbicacion($validatedEquipmentId, $validatedLocationId);

        if (!$isMoved) {
            throw new Exception('No se pudo cambiar la ubicación del equipamiento', 500);
        }

        return true;
    }
    public function eliminar(int $equipmentId): bool
    {
        $validatedId = $this->validarId($equipmentId, 'ID de equipamiento');

        $existingEquipment = $this->equipamientoDAO->buscarPorId($validatedId);
        if ($existingEquipment === null) {
            throw new Exception('Equipamiento no encontrado', 404);
        }

        $isDeleted = $this->equipamientoDAO->eliminar($validatedId);

        if (!$isDeleted) {
            throw new Exception('No se pudo eliminar el equipamiento', 500);
        }

        return true;
    }
    private function limpiarTexto(array $requestData, string $fieldName, string $defaultValue = ''): string
    {
        $rawValue = $defaultValue;
        
        if (isset($requestData[$fieldName])) {
            $rawValue = $requestData[$fieldName];
        }

        if (!is_string($rawValue) && !is_numeric($rawValue)) {
            throw new Exception("El campo $fieldName debe ser texto", 400);
        }

        return trim((string) $rawValue);
    }

    private function validarDatos($requestData): array
    {
        if (!is_array($requestData)) {
            throw new Exception('Los datos del equipamiento no son válidos', 400);
        }
        return $requestData;
    }

    private function obtenerCategoria($categoryString): CategoriaEquipamiento
    {
        if (!is_string($categoryString)) {
            throw new Exception('La categoría no es válida', 400);
        }

        $categoryEnum = CategoriaEquipamiento::tryFrom(trim($categoryString));
        
        if ($categoryEnum === null) {
            throw new Exception('La categoría no es válida', 400);
        }

        return $categoryEnum;
    }

    private function validarId($rawId, string $fieldName): int
    {
        $validatedId = filter_var($rawId, FILTER_VALIDATE_INT);
        
        if ($validatedId === false || $validatedId <= 0) {
            throw new Exception("$fieldName inválido", 400);
        }

        return $validatedId;
    }

    private function normalizarEquipo(array $equipmentRow): array
    {
        $normalizedData = [
            'id_equipamiento'     => (int) $equipmentRow['id_equipamiento'],
            'nombre'              => $equipmentRow['nombre'],
            'descripcion'         => $equipmentRow['descripcion'],
            'marca'               => $equipmentRow['marca'],
            'modelo'              => $equipmentRow['modelo'],
            'categoria'           => $equipmentRow['categoria'],
            'id_ubicacion_actual' => (int) $equipmentRow['id_ubicacion_actual'],
            'ubicacion'           => $equipmentRow['ubicacion'] ?? null,
        ];

        return $normalizedData;
    }
}