<?php
require_once __DIR__ . '/../model/Equipamientos.php';
require_once __DIR__ . '/../dao/EquipamientoDAO.php';
require_once __DIR__ . '/../dao/HistoricoDAO.php';

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
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        if (!isset($_SESSION['usuario_id']) && !isset($_SESSION['usuario'])) {
            $this->redirigir('index.php?page=login');
            return;
        }

        $id = (int) ($_GET['id'] ?? 0);                   
        $equipamiento = $this->equipamientoDAO->buscarPorId($id); 

        if ($equipamiento === null) {                      
            $this->setMensaje('error', 'Equipamiento no encontrado.'); 
            $this->redirigir('index.php?page=equipamientos');      
        }

        $historial = $this->historicoDAO->historialDeEquipamiento($id); 

        $ubicacionActual = 'Desconocida';                             
        foreach ($historial as $fila) {                   
            if ($fila['fecha_fin'] === null) {             
                $ubicacionActual = $fila['ubicacion'];     
                break;
            }
        }
        
        $modo = 'history';                                 
        require __DIR__ . '/../view/equipamientos.php';    
    }

    private function redirigir(string $url): void
    {
        if (function_exists('redirect')) {
            redirect($url);
        } else {
            header("Location: $url");
            exit;
        }
    }

    private function setMensaje(string $tipo, string $texto): void
    {
        if (function_exists('flash')) {
            flash($tipo, $texto);
        } else {
            if (session_status() === PHP_SESSION_NONE) {
                session_start();
            }
            $_SESSION['flash'][$tipo] = $texto;
        }
    }
}
