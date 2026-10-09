<?php
require_once __DIR__ . '/../models/Equipamientos.php';

class EquipamientoDAO {

    private PDO $db;

    public function __construct(PDO $db) {
        $this->db = $db;
    }

    // CREATE
    public function crear(Equipamientos $e): int {
        $sql = "INSERT INTO equipamientos
                    (nombre, descripcion, marca, modelo, categoria, id_ubicacion_actual)
                VALUES
                    (:nombre, :descripcion, :marca, :modelo, :categoria, 1)";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':nombre'      => $e->getNombre(),
            ':descripcion' => $e->getDescripcion(),
            ':marca'       => $e->getMarca(),
            ':modelo'      => $e->getModelo(),
            ':categoria'   => $e->getCategoria(),
        ]);

        return (int) $this->db->lastInsertId();
    }

    // READ
   
    public function listarTodos(): array {
        $sql = "SELECT e.*, u.nombre AS ubicacion_actual
                FROM equipamientos e
                JOIN ubicaciones u ON u.id_ubicacion = e.id_ubicacion_actual
                ORDER BY e.nombre";

        return $this->db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Busca UN equipamiento por su id.
     *
     * @return Equipamientos|null El objeto modelo, o null si no existe.
     */
    public function buscarPorId(int $id): ?Equipamientos {
        $sql = "SELECT * FROM equipamientos WHERE id_equipamiento = :id";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $id]);
        $fila = $stmt->fetch(PDO::FETCH_ASSOC);

        return $fila ? $this->filaAObjeto($fila) : null;
    }

    // UPDATE

    /**
     * Si cambia id_ubicacion_actual, el trigger
     * `trg_equipamiento_traslado` cierra el histórico anterior
     * y abre uno nuevo. solo hacemos el UPDATE.
     */
    public function actualizar(Equipamientos $e): bool {
        $sql = "UPDATE equipamientos
                   SET nombre = :nombre,
                       descripcion = :descripcion,
                       marca = :marca,
                       modelo = :modelo,
                       categoria = :categoria,
                       id_ubicacion_actual = :ubicacion
                 WHERE id_equipamiento = :id";

        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            ':nombre'      => $e->getNombre(),
            ':descripcion' => $e->getDescripcion(),
            ':marca'       => $e->getMarca(),
            ':modelo'      => $e->getModelo(),
            ':categoria'   => $e->getCategoria(),
            ':ubicacion'   => $e->getIdUbicacionActual(),
            ':id'          => $e->getIdEquipamiento(),
        ]);
    }

    // DELETE
    /**
     * Borra un equipamiento por id.
     * (ON DELETE CASCADE).
     */
    public function eliminar(int $id): bool {
        $stmt = $this->db->prepare(
            "DELETE FROM equipamientos WHERE id_equipamiento = :id"
        );
        return $stmt->execute([':id' => $id]);
    }

    // Esto convierte una fila SQL en objeto modelo

    private function filaAObjeto(array $fila): Equipamientos {
        return new Equipamientos(
            (int) $fila['id_equipamiento'],
            $fila['nombre'],
            $fila['descripcion'],
            $fila['marca'],
            $fila['modelo'],
            $fila['categoria'],
            (int) $fila['id_ubicacion_actual']
        );
    }
}