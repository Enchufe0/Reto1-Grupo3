<?php
class EquipamientoDAO {
    private PDO $dbConnection;

    public function __construct(PDO $db) {
        $this->dbConnection = $db;
    }

    public function listar(): array {
        $query = "SELECT e.*, u.nombre AS ubicacion FROM equipamientos e 
                  JOIN ubicaciones u ON e.id_ubicacion_actual = u.id_ubicacion 
                  ORDER BY e.id_equipamiento DESC";
        return $this->dbConnection->query($query)->fetchAll(PDO::FETCH_ASSOC);
    }

    public function buscarPorId(int $id): ?array {
        $stmt = $this->dbConnection->prepare("SELECT * FROM equipamientos WHERE id_equipamiento = :id");
        $stmt->execute([':id' => $id]);
        $fila = $stmt->fetch(PDO::FETCH_ASSOC);
        return $fila ?: null;
    }

    public function crear(string $nombre, string $descripcion, string $marca, string $modelo, string $categoria): bool {
        $query = "INSERT INTO equipamientos (nombre, descripcion, marca, modelo, categoria, id_ubicacion_actual) 
                  VALUES (:nombre, :descripcion, :marca, :modelo, :categoria, 1)";
        $stmt = $this->dbConnection->prepare($query);
        return $stmt->execute([
            ':nombre' => $nombre,
            ':descripcion' => $descripcion,
            ':marca' => $marca,
            ':modelo' => $modelo,
            ':categoria' => $categoria
        ]);
    }

    public function actualizar(int $id, string $nombre, string $descripcion, string $marca, string $modelo, string $categoria): bool {
        $query = "UPDATE equipamientos SET nombre = :nombre, descripcion = :descripcion, 
                  marca = :marca, modelo = :modelo, categoria = :categoria WHERE id_equipamiento = :id";
        $stmt = $this->dbConnection->prepare($query);
        return $stmt->execute([
            ':id' => $id,
            ':nombre' => $nombre,
            ':descripcion' => $descripcion,
            ':marca' => $marca,
            ':modelo' => $modelo,
            ':categoria' => $categoria
        ]);
    }

    public function eliminar(int $id): bool {
        $stmt = $this->dbConnection->prepare("DELETE FROM equipamientos WHERE id_equipamiento = :id");
        return $stmt->execute([':id' => $id]);
    }
}