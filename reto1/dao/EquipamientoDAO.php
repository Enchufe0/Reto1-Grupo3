<?php
require_once __DIR__ . '/../model/Equipamientos.php';
require_once __DIR__ . '/../model/CategoriaEquipamiento.php';
require_once __DIR__ . '/HistoricoDAO.php';

use enumCategoria\CategoriaEquipamiento;

class EquipamientoDAO {

    private PDO $db;

    public function __construct(PDO $db) {
        $this->db = $db;
    }

    /* ============================================================
     *  CREATE
     * ============================================================ */

    public function crear(Equipamientos $equipo): int {
        try {
            $this->db->beginTransaction();

            $stmt = $this->db->prepare(
                "INSERT INTO equipamientos
                    (nombre, descripcion, marca, modelo, categoria, id_ubicacion_actual)
                 VALUES
                    (:nombre, :descripcion, :marca, :modelo, :categoria, :ubicacion_actual)"
            );

            $stmt->execute([
                ':nombre'           => $equipo->getNombre(),
                ':descripcion'      => $equipo->getDescripcion(),
                ':marca'            => $equipo->getMarca(),
                ':modelo'           => $equipo->getModelo(),
                ':categoria'        => $equipo->getCategoria()->value,
                ':ubicacion_actual' => $equipo->getIdUbicacionActual(),
            ]);

            $id = (int) $this->db->lastInsertId();

            // Compartimos $this->db con HistoricoDAO → misma transacción
            $historico = new HistoricoDAO($this->db);
            if (!$historico->registrarUbicacionInicial($id, $equipo->getIdUbicacionActual())) {
                $this->db->rollBack();
                error_log("[EquipamientoDAO::crear] fallo al registrar histórico para #$id");
                return 0;
            }

            $this->db->commit();
            return $id;
        } catch (Throwable $ex) {
            if ($this->db->inTransaction()) $this->db->rollBack();
            error_log('[EquipamientoDAO::crear] ' . $ex->getMessage());
            return 0;
        }
    }

    /* ============================================================
     *  READ
     * ============================================================ */

    public function listarTodos(): array {
        $sql = "SELECT e.*, u.nombre AS ubicacion_actual
                FROM equipamientos e
                JOIN ubicaciones u ON u.id_ubicacion = e.id_ubicacion_actual
                ORDER BY e.nombre";

        $filas = $this->db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
        return array_map([$this, 'filaAObjeto'], $filas);
    }

    public function buscarPorId(int $id): ?Equipamientos {
        $stmt = $this->db->prepare("SELECT * FROM equipamientos WHERE id_equipamiento = :id");
        $stmt->execute([':id' => $id]);
        $fila = $stmt->fetch(PDO::FETCH_ASSOC);
        return $fila ? $this->filaAObjeto($fila) : null;
    }

    /* ============================================================
     *  UPDATE (solo datos del equipo — NO toca ubicación)
     * ============================================================ */

    public function actualizar(Equipamientos $equipo): bool {
        $stmt = $this->db->prepare(
            "UPDATE equipamientos
                SET nombre      = :nombre,
                    descripcion = :descripcion,
                    marca       = :marca,
                    modelo      = :modelo,
                    categoria   = :categoria
              WHERE id_equipamiento = :id"
        );

        return $stmt->execute([
            ':nombre'      => $equipo->getNombre(),
            ':descripcion' => $equipo->getDescripcion(),
            ':marca'       => $equipo->getMarca(),
            ':modelo'      => $equipo->getModelo(),
            ':categoria'   => $equipo->getCategoria()->value,
            ':id'          => $equipo->getIdEquipamiento(),
        ]);
    }

    /* ============================================================
     *  MOVER EQUIPO — transaccional, usa primitivas de HistoricoDAO
     * ============================================================ */

    public function cambiarUbicacion(int $id, int $idUbicacion): bool {
        try {
            $this->db->beginTransaction();

            $historico = new HistoricoDAO($this->db);

            if (!$historico->cerrarUbicacionActual($id)) {
                $this->db->rollBack();
                error_log("[EquipamientoDAO::cambiarUbicacion] no había histórico abierto para #$id");
                return false;
            }

            if (!$historico->registrarUbicacionInicial($id, $idUbicacion)) {
                $this->db->rollBack();
                error_log("[EquipamientoDAO::cambiarUbicacion] fallo al abrir nuevo histórico para #$id");
                return false;
            }

            $stmt = $this->db->prepare(
                "UPDATE equipamientos SET id_ubicacion_actual = :ubi WHERE id_equipamiento = :id"
            );
            $stmt->execute([':ubi' => $idUbicacion, ':id' => $id]);

            $this->db->commit();
            return true;
        } catch (Throwable $ex) {
            if ($this->db->inTransaction()) $this->db->rollBack();
            error_log('[EquipamientoDAO::cambiarUbicacion] ' . $ex->getMessage());
            return false;
        }
    }

    /* ============================================================
     *  DELETE
     * ============================================================ */

    public function eliminar(int $id): bool {
        $stmt = $this->db->prepare("DELETE FROM equipamientos WHERE id_equipamiento = :id");
        return $stmt->execute([':id' => $id]);
    }

    /* ============================================================
     *  HELPER
     * ============================================================ */

    private function filaAObjeto(array $fila): Equipamientos {
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