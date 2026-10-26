<?php
require_once __DIR__ . '/../dao/HistoricoDAO.php';
class HistoricoController {
    private $dao;

    public function __construct(PDO $db) {
        $this->dao = new HistoricoDAO($db);
    }

    // Devuelve el array con el historial del equipo
    public function listar($idEquipamiento) {
        if (empty($idEquipamiento)) {
            throw new Exception("ID de equipamiento requerido", 400);
        }
        return $this->dao->historialDeEquipamiento($idEquipamiento);
    }
}