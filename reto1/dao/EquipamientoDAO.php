<?php
require_once __DIR__ . '/../model/Equipamientos.php';
require_once __DIR__ . '/../model/CategoriaEquipamiento.php';

use enumCategoria\CategoriaEquipamiento;

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
                    (:nombre, :descripcion, :marca, :modelo, :categoria, :ubicacion_actual)";

        try {
            $this->db->beginTransaction();

            $stmt = $this->db->prepare($sql);
        
            $cat = $e->getCategoria();
            $categoriaValor = ($cat instanceof CategoriaEquipamiento) ? $cat->value : (string) $cat;

            $ok = $stmt->execute([
                ':nombre'           => $e->getNombre(),
                ':descripcion'      => $e->getDescripcion(),
                ':marca'            => $e->getMarca(),
                ':modelo'           => $e->getModelo(),
                ':categoria'        => $categoriaValor,
                ':ubicacion_actual' => $e->getIdUbicacionActual(),
            ]);

            if (!$ok) {
                $this->db->rollBack();
                return 0;
            }

            $id = (int) $this->db->lastInsertId();

            $stmtHistorico = $this->db->prepare(
                "INSERT INTO historico_ubicaciones (id_equipamiento, id_ubicacion, fecha_inicio, fecha_fin)
                 VALUES (:id_equipamiento, :id_ubicacion, :fecha_inicio, NULL)"
            );

            $okHistorico = $stmtHistorico->execute([
                ':id_equipamiento' => $id,
                ':id_ubicacion'    => $e->getIdUbicacionActual(),
                ':fecha_inicio'    => date('Y-m-d H:i:s'),
            ]);

            if (!$okHistorico) {
                $this->db->rollBack();
                return 0;
            }

            $this->db->commit();
            return $id;
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            return 0;
        }
    }

    // READ
    public function listarTodos(): array {
        $sql = "SELECT e.*, u.nombre AS ubicacion_actual
                FROM equipamientos e
                JOIN ubicaciones u ON u.id_ubicacion = e.id_ubicacion_actual
                ORDER BY e.nombre";

        return $this->db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }

    public function buscarPorId(int $id): ?Equipamientos {
        $sql = "SELECT * FROM equipamientos WHERE id_equipamiento = :id";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $id]);
        $fila = $stmt->fetch(PDO::FETCH_ASSOC);

        return $fila ? $this->filaAObjeto($fila) : null;
    }

    // UPDATE
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

        $cat = $e->getCategoria();
        $categoriaValor = ($cat instanceof CategoriaEquipamiento) ? $cat->value : (string) $cat;

        return $stmt->execute([
            ':nombre'      => $e->getNombre(),
            ':descripcion' => $e->getDescripcion(),
            ':marca'       => $e->getMarca(),
            ':modelo'      => $e->getModelo(),
            ':categoria'   => $categoriaValor,
            ':ubicacion'   => $e->getIdUbicacionActual(),
            ':id'          => $e->getIdEquipamiento(),
        ]);
    }

    // DELETE
    public function eliminar(int $id): bool {
        $stmt = $this->db->prepare(
            "DELETE FROM equipamientos WHERE id_equipamiento = :id"
        );
        return $stmt->execute([':id' => $id]);
    }

    // Convierte una fila SQL en objeto Equipamientos
    private function filaAObjeto(array $fila): Equipamientos {
        // Convierte el string de la BD al enum CategoriaEquipamiento correspondiente
        $categoriaEnum = CategoriaEquipamiento::tryFrom($fila['categoria']) ?? CategoriaEquipamiento::OTROS;

        return new Equipamientos(
            (int) $fila['id_equipamiento'],
            $fila['nombre'],
            $fila['descripcion'],
            $fila['marca'],
            $fila['modelo'],
            $categoriaEnum,
            (int) $fila['id_ubicacion_actual']
        );
    }
}
