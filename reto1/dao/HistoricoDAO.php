<?php
require_once __DIR__ . '/../model/Historico_ubicaciones.php';
class HistoricoDAO {
    private PDO $db;

    public function __construct(PDO $db) {
        $this->db = $db;
    }

    public function historialDeEquipamiento(int $idEquipamiento): array {
        $sql = "SELECT h.id_historico, u.nombre AS ubicacion,
                       h.fecha_inicio, h.fecha_fin
                FROM historico_ubicaciones h
                JOIN ubicaciones u ON u.id_ubicacion = h.id_ubicacion
                WHERE h.id_equipamiento = :id
                ORDER BY h.fecha_inicio";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $idEquipamiento]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    public function ubicacionActual(int $idEquipamiento): ?Historico_ubicaciones {
        $sql = "SELECT * FROM historico_ubicaciones
                WHERE id_equipamiento = :id AND fecha_fin IS NULL
                ORDER BY fecha_inicio DESC LIMIT 1";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $idEquipamiento]);
        $fila = $stmt->fetch(PDO::FETCH_ASSOC);

        return $fila ? $this->filaAObjeto($fila) : null;
    }

    public function registrarUbicacionInicial(int $idEquipamiento, int $idUbicacion, ?string $fechaInicio = null): bool {
        $fecha = $fechaInicio ?? date('Y-m-d H:i:s');

        $stmt = $this->db->prepare(
            "INSERT INTO historico_ubicaciones (id_equipamiento, id_ubicacion, fecha_inicio, fecha_fin)
             VALUES (:id_equipamiento, :id_ubicacion, :fecha_inicio, NULL)"
        );

        return $stmt->execute([
            ':id_equipamiento' => $idEquipamiento,
            ':id_ubicacion' => $idUbicacion,
            ':fecha_inicio' => $fecha,
        ]);
    }

    public function registrarTraslado(int $idEquipamiento, int $idUbicacion, ?string $fechaMovimiento = null): bool {
        $fecha = $fechaMovimiento ?? date('Y-m-d H:i:s');

        try {
            $this->db->beginTransaction();

            $this->cerrarUbicacionActual($idEquipamiento, $fecha);

            $stmt = $this->db->prepare(
                "INSERT INTO historico_ubicaciones (id_equipamiento, id_ubicacion, fecha_inicio, fecha_fin)
                 VALUES (:id_equipamiento, :id_ubicacion, :fecha_inicio, NULL)"
            );

            $ok = $stmt->execute([
                ':id_equipamiento' => $idEquipamiento,
                ':id_ubicacion' => $idUbicacion,
                ':fecha_inicio' => $fecha,
            ]);

            if (!$ok) {
                $this->db->rollBack();
                return false;
            }

            $this->db->commit();
            return true;
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            return false;
        }
    }

    public function cerrarUbicacionActual(int $idEquipamiento, ?string $fechaFin = null): bool {
        $fecha = $fechaFin ?? date('Y-m-d H:i:s');

        $sql = "UPDATE historico_ubicaciones
                SET fecha_fin = :fecha_fin
                WHERE id_equipamiento = :id_equipamiento AND fecha_fin IS NULL";

        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            ':fecha_fin' => $fecha,
            ':id_equipamiento' => $idEquipamiento,
        ]);
    }

    public function equipamientosEnUbicacion(int $idUbicacion): array {
        $sql = "SELECT e.id_equipamiento, e.nombre, h.fecha_inicio
                FROM historico_ubicaciones h
                JOIN equipamientos e ON e.id_equipamiento = h.id_equipamiento
                WHERE h.id_ubicacion = :id AND h.fecha_fin IS NULL
                ORDER BY e.nombre";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $idUbicacion]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // fila SQL -> objeto modelo
    private function filaAObjeto(array $fila): Historico_ubicaciones {
        return new Historico_ubicaciones(
            (int) $fila['id_historico'],
            (int) $fila['id_equipamiento'],
            (int) $fila['id_ubicacion'],
            $fila['fecha_inicio'],
            $fila['fecha_fin']   
        );
    }
}