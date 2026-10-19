<?php
require_once __DIR__ . '/../dao/EquipamientoDAO.php';
require_once __DIR__ . '/../dao/HistoricoDAO.php';
require_once __DIR__ . '/../dao/UbicacionesDAO.php';
require_once __DIR__ . '/../model/Equipamientos.php';
require_once __DIR__ . '/../model/CategoriaEquipamiento.php';

use enumCategoria\CategoriaEquipamiento;

class EquipamientoController {

    private EquipamientoDAO $equipamientoDAO;
    private ?HistoricoDAO $historicoDAO;
    private ?UbicacionesDAO $ubicacionesDAO;

    public function __construct(PDO $db) {
        $this->equipamientoDAO = new EquipamientoDAO($db);
        // Si existen los DAOs de histórico y ubicaciones, los inicializamos
        if (class_exists('HistoricoDAO')) {
            $this->historicoDAO = new HistoricoDAO($db);
        }
        if (class_exists('UbicacionesDAO')) {
            $this->ubicacionesDAO = new UbicacionesDAO($db);
        }
    }

    // 1. LISTAR TODOS LOS EQUIPOS
    public function listar(): void {
        $equipos = $this->equipamientoDAO->listarTodos();
        $ubicaciones = $this->ubicacionesDAO ? $this->ubicacionesDAO->listarTodas() : [];
        require __DIR__ . '/../view/equipamientos.php';
    }

    // 2. CREAR UN NUEVO EQUIPO
    public function guardar(): void {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $nombre      = trim($_POST['nombre'] ?? '');
            $descripcion = trim($_POST['descripcion'] ?? '');
            $marca       = trim($_POST['marca'] ?? '');
            $modelo      = trim($_POST['modelo'] ?? '');
            $categoriaStr= $_POST['categoria'] ?? 'Otros';
            $ubicacionId = (int) ($_POST['id_ubicacion'] ?? 1);

            // Convertir el texto del formulario al Enum seguro
            $categoriaEnum = CategoriaEquipamiento::tryFrom($categoriaStr) ?? CategoriaEquipamiento::OTROS;

            $nuevoEquipo = new Equipamientos(
                0, // ID 0 porque es autoincremental en la BD
                $nombre,
                $descripcion,
                $marca,
                $modelo,
                $categoriaEnum,
                $ubicacionId
            );

            $idGenerado = $this->equipamientoDAO->crear($nuevoEquipo);

            // Redirigir al listado tras guardar
            header('Location: index.php?page=equipamientos');
            exit;
        }
    }

    // 3. EDITAR / ACTUALIZAR EQUIPO
    public function actualizar(): void {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $id          = (int) ($_POST['id_equipamiento'] ?? 0);
            $nombre      = trim($_POST['nombre'] ?? '');
            $descripcion = trim($_POST['descripcion'] ?? '');
            $marca       = trim($_POST['marca'] ?? '');
            $modelo      = trim($_POST['modelo'] ?? '');
            $categoriaStr= $_POST['categoria'] ?? 'Otros';
            $ubicacionId = (int) ($_POST['id_ubicacion'] ?? 1);

            $categoriaEnum = CategoriaEquipamiento::tryFrom($categoriaStr) ?? CategoriaEquipamiento::OTROS;

            $equipo = new Equipamientos(
                $id,
                $nombre,
                $descripcion,
                $marca,
                $modelo,
                $categoriaEnum,
                $ubicacionId
            );

            $this->equipamientoDAO->actualizar($equipo);

            header('Location: index.php?page=equipamientos');
            exit;
        }
    }

    // 4. ELIMINAR EQUIPO
    public function eliminar(): void {
        $id = (int) ($_GET['id'] ?? 0);
        if ($id > 0) {
            $this->equipamientoDAO->eliminar($id);
        }
        header('Location: index.php?page=equipamientos');
        exit;
    }
}
