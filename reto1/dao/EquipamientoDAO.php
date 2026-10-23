<?php
class EquipamientoDAO {
    private $db;

    // Recibe la conexión a la base de datos
    public function __construct(PDO $db) {
        $this->db = $db;
    }

    // 1. LEER TODOS (Con el nombre de la ubicación)
    public function listar() {
        $sql = "SELECT e.*, u.nombre AS ubicacion 
                FROM equipamientos e 
                JOIN ubicaciones u ON e.id_ubicacion_actual = u.id_ubicacion 
                ORDER BY e.id_equipamiento DESC";
        return $this->db->query($sql)->fetchAll();
    }

    // 2. LEER UNO SOLO
    public function buscarPorId($id) {
        $stmt = $this->db->prepare("SELECT * FROM equipamientos WHERE id_equipamiento = ?");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    // 3. CREAR
    public function crear($nombre, $descripcion, $marca, $modelo, $categoria) {
        // IMPORTANTE: Tu SQL tiene un Trigger llamado 'trg_equipamiento_ubicacion_inicial' 
        // que fuerza a que id_ubicacion_actual sea 1 (Almacén). Le pasamos 1 por defecto.
        $sql = "INSERT INTO equipamientos (nombre, descripcion, marca, modelo, categoria, id_ubicacion_actual) 
                VALUES (?, ?, ?, ?, ?, 1)";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$nombre, $descripcion, $marca, $modelo, $categoria]);
    }

    // 4. ACTUALIZAR (Solo los datos, NO la ubicación)
    public function actualizar($id, $nombre, $descripcion, $marca, $modelo, $categoria) {
        $sql = "UPDATE equipamientos 
                SET nombre = ?, descripcion = ?, marca = ?, modelo = ?, categoria = ? 
                WHERE id_equipamiento = ?";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$nombre, $descripcion, $marca, $modelo, $categoria, $id]);
    }

    // 5. CAMBIAR UBICACIÓN (Solo Conserje)
    public function cambiarUbicacion($id, $idUbicacion) {
        // IMPORTANTE: Tu SQL tiene un Trigger llamado 'trg_equipamiento_traslado'
        // que se encarga automáticamente de cerrar el histórico viejo y abrir el nuevo.
        // Por eso aquí SOLO hacemos un UPDATE simple.
        $sql = "UPDATE equipamientos SET id_ubicacion_actual = ? WHERE id_equipamiento = ?";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$idUbicacion, $id]);
    }

    // 6. ELIMINAR
    public function eliminar($id) {
        $stmt = $this->db->prepare("DELETE FROM equipamientos WHERE id_equipamiento = ?");
        return $stmt->execute([$id]);
    }
}