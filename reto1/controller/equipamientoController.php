<?php
require_once __DIR__ . '/../model/Equipamientos.php';
require_once __DIR__ . '/../model/Ubicaciones.php';

require_once __DIR__ . '/../dao/equipamientoDAO.php';
require_once __DIR__ . '/../dao/UbicacionesDAO.php';

require_once __DIR__ . '/UsuariosController.php';

class equipamientoController
{
    private const CATEGORIAS = ['PORTATIL', 'SOBREMESA', 'PERIFERICO', 'AUDIOVISUAL', 'OTROS'];

    private equipamientoDAO $equipamientoDAO; 
    private UbicacionesDAO $ubicacionDAO;      

    public function __construct()
    {
        $db = Database::getConnection();                 
        $this->equipamientoDAO = new equipamientoDAO($db); 
        $this->ubicacionDAO = new UbicacionesDAO($db);       
    }

    public function handle(): void
    {
        UsuariosController::requireLogin();                 

        $accion = $_GET['action'] ?? 'list';              
        $id = (int) ($_GET['id'] ?? 0);                    
        $esPost = $_SERVER['REQUEST_METHOD'] === 'POST';   

        switch ($accion) {
            case 'create':
                UsuariosController::requireRole('ADMIN');
                if ($esPost) {
                    $this->guardar();
                } else {
                    $this->mostrarFormulario($this->equipamientoVacio());
                }
                break;      

            case 'edit':                                  
                UsuariosController::requireRole('ADMIN');   
                if ($esPost) {
                    $this->actualizar($id);                
                } else {
                    $this->mostrarFormulario($this->buscarOSalir($id)); 
                }
                break;

            case 'delete':                                 
                UsuariosController::requireRole('ADMIN');  
                if ($esPost) {
                    $this->eliminar();                     
                } else {
                    $this->redirigir($this->urlListado());         
                }
                break;

            case 'move':                                   
                UsuariosController::requireRole('USER');    
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
        $categoria = $this->categoriaValida($_GET['categoria'] ?? ''); 
        $filas = $this->equipamientoDAO->listarTodos();    

        if ($categoria !== '') {                           
            $filtradas = [];                               
            foreach ($filas as $fila) {                   
                if ($fila['categoria'] === $categoria) {   
                    $filtradas[] = $fila;                 
                }
            }
            $filas = $filtradas;                           
        }

        $modo = 'list';                                    
        $equipamientos = $filas;                           
        $categorias = self::CATEGORIAS;                   
        $ubicaciones = $this->nombresUbicaciones();        
        require __DIR__ . '/../view/equipamientos.php';   
    }

    private function mostrarFormulario(Equipamientos $equipamiento, array $errores = []): void
    {
        $ubicaciones = $this->nombresUbicaciones();        
        $nombreUbicacion = $ubicaciones[$equipamiento->getIdUbicacionActual()] ?? 'Almacén'; 

        $modo = 'form';                                    
        $categorias = self::CATEGORIAS;                   
        require __DIR__ . '/../view/equipamientos.php';    
    }

    private function guardar(): void
    {
        $datos = $this->leerFormulario();                  
        $errores = $this->validar($datos);                 
        if ($errores) {                                    
            $this->mostrarFormulario($this->crearObjeto(0, $datos, 0), $errores); 
            return;                                       
        }

        $this->equipamientoDAO->crear($this->crearObjeto(0, $datos, 0)); 
        $this->setMensaje('success', 'Equipamiento creado en la ubicación Almacén.'); 
        $this->redirigir($this->urlListado());                    
    }

    private function actualizar(int $id): void
    {
        $equipamiento = $this->buscarOSalir($id);         
        $datos = $this->leerFormulario();                 
        $errores = $this->validar($datos);                 

        if ($errores) {                                    
            $idUbicacion = $equipamiento->getIdUbicacionActual(); 
            $this->mostrarFormulario($this->crearObjeto($id, $datos, $idUbicacion), $errores); 
            return;                                        
        }

        $equipamiento->setNombre($datos['nombre']);       
        $equipamiento->setDescripcion($datos['descripcion']);
        $equipamiento->setMarca($datos['marca']);
        $equipamiento->setModelo($datos['modelo']);
        $equipamiento->setCategoria($datos['categoria']);

        $this->equipamientoDAO->actualizar($equipamiento); 
        $this->setMensaje('success', 'Equipamiento actualizado.');     
        $this->redirigir($this->urlListado());                     
    }

    private function eliminar(): void
    {
        $id = (int) ($_POST['id_equipamiento'] ?? 0);      

        if ($this->equipamientoDAO->buscarPorId($id) === null) { 
            $this->setMensaje('error', 'Equipamiento no encontrado.'); 
        } else {
            $this->equipamientoDAO->eliminar($id);         
            $this->setMensaje('success', 'Equipamiento eliminado.');   
        }

        $this->redirigir($this->urlListado());                    
    }

    private function mover(): void
    {
        $id = (int) ($_POST['id_equipamiento'] ?? 0);      
        $idUbicacion = (int) ($_POST['id_ubicacion'] ?? 0); 
        $categoria = $this->categoriaValida($_POST['categoria'] ?? ''); 
        $equipamiento = $this->equipamientoDAO->buscarPorId($id); 
        $ubicaciones = $this->nombresUbicaciones();        

        if ($equipamiento === null || !isset($ubicaciones[$idUbicacion])) { 
            $this->setMensaje('error', 'Equipamiento o ubicación no encontrados.');
        } elseif ($equipamiento->getIdUbicacionActual() === $idUbicacion) { 
            $this->setMensaje('info', 'El equipamiento ya está en esa ubicación.');
        } else {
            $equipamiento->setIdUbicacionActual($idUbicacion); 
            $this->equipamientoDAO->actualizar($equipamiento); 
            $this->setMensaje('success', 'Equipamiento movido a ' . $ubicaciones[$idUbicacion] . '.');
        }

        $this->redirigir($this->urlListado($categoria));           
    }

    private function buscarOSalir(int $id): Equipamientos
    {
        $equipamiento = $this->equipamientoDAO->buscarPorId($id); 

        if ($equipamiento === null) {                      
            $this->setMensaje('error', 'Equipamiento no encontrado.'); 
            $this->redirigir($this->urlListado());                 
        }

        return $equipamiento;                             
    }

    private function nombresUbicaciones(): array
    {
        $nombres = [];                                    
      
        $lista = method_exists($this->ubicacionDAO, 'findAll') 
            ? $this->ubicacionDAO->findAll() 
            : (method_exists($this->ubicacionDAO, 'listarTodas') ? $this->ubicacionDAO->listarTodas() : $this->ubicacionDAO->listar());

        foreach ($lista as $ubicacion) { 
            $nombres[$ubicacion->getIdUbicacion()] = $ubicacion->getNombre();
        }
        return $nombres;                                 
    }

    private function categoriaValida(string $valor): string
    {
        return in_array($valor, self::CATEGORIAS, true) ? $valor : ''; 
    }

    private function urlListado(string $categoria = ''): string
    {
        $url = 'index.php?page=equipamientos';            
        if ($categoria !== '') {                           
            $url .= '&categoria=' . urlencode($categoria); 
        }
        return $url;
    }

    private function leerFormulario(): array
    {
        return [
            'nombre'      => trim($_POST['nombre'] ?? ''),
            'descripcion' => trim($_POST['descripcion'] ?? ''),
            'marca'       => trim($_POST['marca'] ?? ''),
            'modelo'      => trim($_POST['modelo'] ?? ''),
            'categoria'   => (string) ($_POST['categoria'] ?? ''),
        ];
    }

    private function validar(array $d): array
    {
        $errores = [];                                    

        if ($d['nombre'] === '' || mb_strlen($d['nombre']) > 120) {
            $errores[] = 'El nombre es obligatorio (máximo 120 caracteres).';
        }
        if ($d['descripcion'] === '' || mb_strlen($d['descripcion']) > 255) {
            $errores[] = 'La descripción es obligatoria (máximo 255 caracteres).';
        }
        if ($d['marca'] === '' || mb_strlen($d['marca']) > 100) {
            $errores[] = 'La marca es obligatoria (máximo 100 caracteres).';
        }
        if ($d['modelo'] === '' || mb_strlen($d['modelo']) > 100) {
            $errores[] = 'El modelo es obligatorio (máximo 100 caracteres).';
        }
        if (!in_array($d['categoria'], self::CATEGORIAS, true)) { 
            $errores[] = 'La categoría no es válida.';
        }

        return $errores;
    }

    private function crearObjeto(int $id, array $d, int $idUbicacion): Equipamientos
    {
        return new Equipamientos(                         
            $id,
            $d['nombre'],
            $d['descripcion'],
            $d['marca'],
            $d['modelo'],
            $d['categoria'],
            $idUbicacion
        );
    }

    private function equipamientoVacio(): Equipamientos
    {
        return new Equipamientos(0, '', '', '', '', self::CATEGORIAS[0], 0);
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
