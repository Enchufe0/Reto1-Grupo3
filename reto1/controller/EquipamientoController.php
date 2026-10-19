<?php
require_once __DIR__ . '/../model/Equipamientos.php';
require_once __DIR__ . '/../model/Ubicaciones.php';
require_once __DIR__ . '/../dao/EquipamientoDAO.php';
require_once __DIR__ . '/../dao/UbicacionesDAO.php';

class EquipamientoController
{
    private const CATEGORIAS = ['PORTATIL', 'SOBREMESA', 'PERIFERICO', 'AUDIOVISUAL', 'OTROS'];

    private EquipamientoDAO $equipamientoDAO; 
    private UbicacionesDAO $ubicacionDAO;      

    public function __construct()
    {
        $db = Database::getConnection();                 
        $this->equipamientoDAO = new EquipamientoDAO($db); 
        $this->ubicacionDAO = new UbicacionesDAO($db);       
    }

    public function handle(): void
    {
        $this->requireLogin();

        $accion = $_GET['action'] ?? 'list';              
        $id = (int) ($_GET['id'] ?? 0);                    
        $esPost = $_SERVER['REQUEST_METHOD'] === 'POST';   

        switch ($accion) {
            case 'create':
                $this->requireRole('ADMIN');
                if ($esPost) {
                    $this->guardar();
                } else {
                    $this->mostrarFormulario($this->equipamientoVacio());
                }
                break;      

            case 'edit':                                  
                $this->requireRole('ADMIN');   
                if ($esPost) {
                    $this->actualizar($id);                
                } else {
                    $this->mostrarFormulario($this->buscarOSalir($id)); 
                }
                break;

            case 'delete':                                 
                $this->requireRole('ADMIN');  
                if ($esPost) {
                    $this->eliminar();                     
                } else {
                    $this->redirigir($this->urlListado());         
                }
                break;

            case 'move':                                   
                $this->requireRole('USER');    
                if ($esPost) {
                    $this->mover();                        
                } else {
                    $this->redirigir($this->urlListado());        
                }
                break;

            default:                                       
                $this->listar();
                break;
        }
    }

    private function listar(): void
    {
        $equipamientos = $this->equipamientoDAO->listarTodos();
        $ubicaciones = $this->ubicacionDAO->listarTodas();

        $modo = 'list';
        $categorias = self::CATEGORIAS;
        require __DIR__ . '/../view/equipamientos.php';
    }

    private function mostrarFormulario(Equipamientos $equipamiento): void
    {
        $ubicaciones = $this->ubicacionDAO->listarTodas();
        $modo = 'form';
        $categorias = self::CATEGORIAS;
        require __DIR__ . '/../view/equipamientos.php';
    }

    private function guardar(): void
    {
        $nombre = trim($_POST['nombre'] ?? '');
        $descripcion = trim($_POST['descripcion'] ?? '');
        $marca = trim($_POST['marca'] ?? '');
        $modelo = trim($_POST['modelo'] ?? '');
        $categoria = $_POST['categoria'] ?? 'OTROS';

        if ($nombre === '') {
            $this->setMensaje('error', 'El nombre del equipamiento es obligatorio.');
            $this->mostrarFormulario(new Equipamientos(0, $nombre, $descripcion, $marca, $modelo, $categoria, 1));
            return;
        }

        $nuevo = new Equipamientos(0, $nombre, $descripcion, $marca, $modelo, $categoria, 1);
        $id = $this->equipamientoDAO->crear($nuevo);

        if ($id > 0) {
            $this->setMensaje('success', 'Equipamiento creado correctamente en Almacén.');
        } else {
            $this->setMensaje('error', 'No se pudo crear el equipamiento.');
        }

        $this->redirigir($this->urlListado());
    }

    private function actualizar(int $id): void
    {
        $actual = $this->buscarOSalir($id);

        $nombre = trim($_POST['nombre'] ?? '');
        $descripcion = trim($_POST['descripcion'] ?? '');
        $marca = trim($_POST['marca'] ?? '');
        $modelo = trim($_POST['modelo'] ?? '');
        $categoria = $_POST['categoria'] ?? 'OTROS';

        if ($nombre === '') {
            $this->setMensaje('error', 'El nombre del equipamiento es obligatorio.');
            $actual->setDescripcion($descripcion);
            $actual->setMarca($marca);
            $actual->setModelo($modelo);
            $actual->setCategoria($categoria);
            $this->mostrarFormulario($actual);
            return;
        }

        $actual->setNombre($nombre);
        $actual->setDescripcion($descripcion);
        $actual->setMarca($marca);
        $actual->setModelo($modelo);
        $actual->setCategoria($categoria);

        $ok = $this->equipamientoDAO->actualizar($actual);
        if ($ok) {
            $this->setMensaje('success', 'Equipamiento actualizado correctamente.');
        } else {
            $this->setMensaje('error', 'Error al actualizar el equipamiento.');
        }

        $this->redirigir($this->urlListado());
    }

    private function eliminar(): void
    {
        $id = (int) ($_POST['id'] ?? 0);
        if ($id <= 0) {
            $this->setMensaje('error', 'Identificador de equipamiento no válido.');
            $this->redirigir($this->urlListado());
            return;
        }

        $ok = $this->equipamientoDAO->eliminar($id);
        if ($ok) {
            $this->setMensaje('success', 'Equipamiento eliminado correctamente.');
        } else {
            $this->setMensaje('error', 'No se pudo eliminar el equipamiento.');
        }

        $this->redirigir($this->urlListado());
    }

    private function mover(): void
    {
        $idEquipamiento = (int) ($_POST['id_equipamiento'] ?? 0);
        $nuevaUbicacion = (int) ($_POST['id_ubicacion'] ?? 0);

        if ($idEquipamiento <= 0 || $nuevaUbicacion <= 0) {
            $this->setMensaje('error', 'Datos de traslado incompletos.');
            $this->redirigir($this->urlListado());
            return;
        }

        $equipamiento = $this->equipamientoDAO->buscarPorId($idEquipamiento);
        if ($equipamiento === null) {
            $this->setMensaje('error', 'Equipamiento no encontrado.');
            $this->redirigir($this->urlListado());
            return;
        }

        if ($equipamiento->getIdUbicacionActual() === $nuevaUbicacion) {
            $this->setMensaje('info', 'El equipamiento ya está en esa ubicación.');
            $this->redirigir($this->urlListado());
            return;
        }

        $equipamiento->setIdUbicacionActual($nuevaUbicacion);
        $ok = $this->equipamientoDAO->actualizar($equipamiento);

        if ($ok) {
            $this->setMensaje('success', 'Traslado registrado con éxito.');
        } else {
            $this->setMensaje('error', 'Error al registrar el traslado.');
        }

        $this->redirigir($this->urlListado());
    }

    private function buscarOSalir(int $id): Equipamientos
    {
        $eq = $this->equipamientoDAO->buscarPorId($id);
        if ($eq === null) {
            $this->setMensaje('error', 'Equipamiento no encontrado.');
            $this->redirigir($this->urlListado());
        }
        return $eq;
    }

    private function equipamientoVacio(): Equipamientos
    {
        return new Equipamientos(0, '', '', '', '', 'OTROS', 1);
    }

    private function urlListado(): string
    {
        return 'index.php?page=equipamientos';
    }

    private function requireLogin(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        if (!isset($_SESSION['usuario_id']) && !isset($_SESSION['usuario'])) {
            $this->redirigir('index.php?page=login');
        }
    }

    private function requireRole(string $rolEsperado): void
    {
        $this->requireLogin();
        $rolActual = $_SESSION['rol'] ?? $_SESSION['usuario']['rol'] ?? '';
        if ($rolActual !== $rolEsperado) {
            $this->setMensaje('error', 'No tienes permisos para realizar esta acción.');
            $this->redirigir($this->urlListado());
        }
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
