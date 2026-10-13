<?php
class HistoricoController
{
    private EquipamientoDAO $equipamientoDAO; 
    private HistoricoDAO $historicoDAO;       

    public function __construct()
    {
        $db = Database::getConnection();                  
        $this->equipamientoDAO = new EquipamientoDAO($db); 
        $this->historicoDAO = new HistoricoDAO($db);
    }

    public function handle(): void
    {
        UsuarioController::requireLogin();                

        $id = (int) ($_GET['id'] ?? 0);                   
        $equipamiento = $this->equipamientoDAO->buscarPorId($id); 

        if ($equipamiento === null) {                      
            flash('error', 'Equipamiento no encontrado.'); 
            redirect('index.php?page=equipamientos');      
        }

        $historial = $this->historicoDAO->historialDeEquipamiento($id); 

        $ubicacionActual = '';                             
        foreach ($historial as $fila) {                   
            if ($fila['fecha_fin'] === null) {             
                $ubicacionActual = $fila['ubicacion'];     
            }
        }

        $modo = 'history';                                 
        require __DIR__ . '/../view/equipamientos.php';    
    }
}