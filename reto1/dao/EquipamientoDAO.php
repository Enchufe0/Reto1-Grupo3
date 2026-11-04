<?php
class EquipamientoDAO
{
    private PDO $dbConnection;

    public function __construct(PDO $db)
    {
        $this->dbConnection = $db;
    }
    public function listar(): array
    {
        $queryText = "
            SELECT e.*, u.nombre AS ubicacion 
            FROM equipamientos e 
            JOIN ubicaciones u ON e.id_ubicacion_actual = u.id_ubicacion 
            ORDER BY e.id_equipamiento DESC
        ";

        $preparedStatement = $this->dbConnection->query($queryText);
        $resultRows = $preparedStatement->fetchAll(PDO::FETCH_ASSOC);

        return $resultRows;
    }
    public function buscarPorId(int $equipmentId): ?array
    {
        $queryText = "SELECT * FROM equipamientos WHERE id_equipamiento = :id";
        
        $preparedStatement = $this->dbConnection->prepare($queryText);
        $preparedStatement->execute([
            'id' => $equipmentId
        ]);
        
        $resultRow = $preparedStatement->fetch(PDO::FETCH_ASSOC);

        if ($resultRow !== false) {
            return $resultRow;
        } else {
            return null;
        }
    }
    public function crear(string $nombre, string $descripcion, string $marca, string $modelo, string $categoria): bool
    {
        $queryText = "
            INSERT INTO equipamientos (nombre, descripcion, marca, modelo, categoria, id_ubicacion_actual) 
            VALUES (:nombre, :descripcion, :marca, :modelo, :categoria, 1)
        ";
        
        $preparedStatement = $this->dbConnection->prepare($queryText);
        
        $isSuccessful = $preparedStatement->execute([
            'nombre'      => $nombre,
            'descripcion' => $descripcion,
            'marca'       => $marca,
            'modelo'      => $modelo,
            'categoria'   => $categoria
        ]);

        return $isSuccessful;
    }
    public function actualizar(int $equipmentId, string $nombre, string $descripcion, string $marca, string $modelo, string $categoria): bool
    {
        $queryText = "
            UPDATE equipamientos 
            SET nombre = :nombre, 
                descripcion = :descripcion, 
                marca = :marca, 
                modelo = :modelo, 
                categoria = :categoria 
            WHERE id_equipamiento = :id
        ";
        
        $preparedStatement = $this->dbConnection->prepare($queryText);
        
        $isSuccessful = $preparedStatement->execute([
            'id'          => $equipmentId,
            'nombre'      => $nombre,
            'descripcion' => $descripcion,
            'marca'       => $marca,
            'modelo'      => $modelo,
            'categoria'   => $categoria
        ]);

        return $isSuccessful;
    }

    public function cambiarUbicacion(int $equipmentId, int $newLocationId): bool
    {
        $queryText = "UPDATE equipamientos SET id_ubicacion_actual = :new_location_id WHERE id_equipamiento = :id";
        
        $preparedStatement = $this->dbConnection->prepare($queryText);
        
        $isSuccessful = $preparedStatement->execute([
            'id' => $equipmentId,
            'new_location_id' => $newLocationId
        ]);

        return $isSuccessful;
    }

    public function eliminar(int $equipmentId): bool
    {
        $queryText = "DELETE FROM equipamientos WHERE id_equipamiento = :id";
        
        $preparedStatement = $this->dbConnection->prepare($queryText);
        $isSuccessful = $preparedStatement->execute([
            'id' => $equipmentId
        ]);

        return $isSuccessful;
    }
}