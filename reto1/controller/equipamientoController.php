<?php
class ControllerEquipamiento
{
    private const CATEGORIAS = ['PORTATIL', 'SOBREMESA', 'PERIFERICO', 'AUDIOVISUAL', 'OTROS'];

    private EquipamientoDAO $equipamientoDAO; 
    private ubicacionDAO $ubicacionDAO;      

    public function __construct()
    {
        $db = Database::getConnection();                 
        $this->equipamientoDAO = new EquipamientoDAO($db); 
        $this->ubicacionDAO = new ubicacionDAO($db);       
    }

    public function handle(): void
    {
        UsuarioController::requireLogin();                 

        $accion = $_GET['action'] ?? 'list';              
        $id = (int) ($_GET['id'] ?? 0);                    
        $esPost = $_SERVER['REQUEST_METHOD'] === 'POST';   

        switch ($accion) {
            case 'create':                                 
                UsuarioController::requireRole('ADMIN');   
                if ($esPost) {
                    $this->guardar();                      
                } else {
                    $this->mostrarFormulario($this->equipamientoVacio()); 
                break;
                } 

            case 'edit':                                  
                UsuarioController::requireRole('ADMIN');   
                if ($esPost) {
                    $this->actualizar($id);                
                } else {
                    $this->mostrarFormulario($this->buscarOSalir($id)); 
                }
                break;

            case 'delete':                                 
                UsuarioController::requireRole('ADMIN');  
                if ($esPost) {
                    $this->eliminar();                     
                } else {
                    redirect($this->urlListado());         
                }
                break;

            case 'move':                                   
                UsuarioController::requireRole('USER');    
                if ($esPost) {
                    $this->mover();                        
                } else {
                    redirect($this->urlListado());        
                }
                break;

            default:                                       
                $this->listar();
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
        flash('success', 'Equipamiento creado en la ubicación Almacén.'); 
        redirect($this->urlListado());                    
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
        flash('success', 'Equipamiento actualizado.');     
        redirect($this->urlListado());                     
    }

   
    private function eliminar(): void
    {
        $id = (int) ($_POST['id_equipamiento'] ?? 0);      

        if ($this->equipamientoDAO->buscarPorId($id) === null) { 
            flash('error', 'Equipamiento no encontrado.'); 
        } else {
            $this->equipamientoDAO->eliminar($id);         
            flash('success', 'Equipamiento eliminado.');   
        }

        redirect($this->urlListado());                    
    }
    private function mover(): void
    {
        $id = (int) ($_POST['id_equipamiento'] ?? 0);      
        $idUbicacion = (int) ($_POST['id_ubicacion'] ?? 0); 
        $categoria = $this->categoriaValida($_POST['categoria'] ?? ''); 
        $equipamiento = $this->equipamientoDAO->buscarPorId($id); 
        $ubicaciones = $this->nombresUbicaciones();        

        if ($equipamiento === null || !isset($ubicaciones[$idUbicacion])) { 
            flash('error', 'Equipamiento o ubicación no encontrados.');
        } elseif ($equipamiento->getIdUbicacionActual() === $idUbicacion) { 
            flash('info', 'El equipamiento ya está en esa ubicación.');
        } else {
            $equipamiento->setIdUbicacionActual($idUbicacion); 
            $this->equipamientoDAO->actualizar($equipamiento); 
            flash('success', 'Equipamiento movido a ' . $ubicaciones[$idUbicacion] . '.');
        }

        redirect($this->urlListado($categoria));           
    }


    private function buscarOSalir(int $id): Equipamientos
    {
        $equipamiento = $this->equipamientoDAO->buscarPorId($id); 

        if ($equipamiento === null) {                      
            flash('error', 'Equipamiento no encontrado.'); 
            redirect($this->urlListado());                 
        }

        return $equipamiento;                             
    }

    private function nombresUbicaciones(): array
    {
        $nombres = [];                                    
        foreach ($this->ubicacionDAO->findAll() as $ubicacion) { 
            $nombres[$ubicacion->getIdUbicacion()] = $ubicacion->getNombre(); // ej.: $nombres[2] = 'Aula 101'
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
}