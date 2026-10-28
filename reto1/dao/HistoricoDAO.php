<?php

/**
 * Data Access Object for History.
 * Its only job is to read the movement history of an equipment.
 */
class HistoricoDAO
{
    private PDO $dbConnection;

    public function __construct(PDO $db)
    {
        $this->dbConnection = $db;
    }
    public function historialDeEquipamiento(int $equipmentId): array
    {
        $queryText = "
            SELECT h.id_historico, 
                   u.nombre AS ubicacion,
                   h.fecha_inicio, 
                   h.fecha_fin
            FROM historico_ubicaciones h
            JOIN ubicaciones u ON u.id_ubicacion = h.id_ubicacion
            WHERE h.id_equipamiento = :id_equipamiento
            ORDER BY h.fecha_inicio ASC
        ";

        $preparedStatement = $this->dbConnection->prepare($queryText);
        
        $preparedStatement->execute([
            'id_equipamiento' => $equipmentId
        ]);

        $historyRows = $preparedStatement->fetchAll(PDO::FETCH_ASSOC);

        return $historyRows;
    }
}