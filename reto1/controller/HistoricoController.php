<?php
require_once __DIR__ . '/../dao/HistoricoDAO.php';
class HistoricoController
{
    private HistoricoDAO $historicoDAO;

    public function __construct(PDO $db)
    {
        $this->historicoDAO = new HistoricoDAO($db);
    }
    public function listar(int $equipmentId): array
    {
        if ($equipmentId <= 0) {
            throw new InvalidArgumentException('ID de equipamiento requerido y debe ser mayor a 0', 400);
        }
        $historyData = $this->historicoDAO->historialDeEquipamiento($equipmentId);

        return $historyData;
    }
}