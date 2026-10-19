<?php
require_once __DIR__ . '/../dao/LoginDAO.php';

class LoginController
{
    private PDO $db;
    private LoginDAO $loginDAO;

    public function __construct()
    {
        $this->db = Database::getConnection();
        $this->loginDAO = new LoginDAO($this->db);
    }

    public function handle(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $accion = $_GET['action'] ?? $_GET['a'] ?? 'login';

        switch ($accion) {
            case 'logout':
                $this->logout();
                break;

            case 'dashboard':
                $this->dashboard();
                break;

            case 'login':
            default:
                if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                    $this->procesarLogin();
                } else {
                    $this->mostrarLogin();
                }
                break;
        }
    }

    private function mostrarLogin(string $error = '', string $usuarioPrevio = ''): void
    {
        if (isset($_SESSION['usuario_id']) || isset($_SESSION['usuario'])) {
            $this->redirigir('index.php?page=equipamientos');
            return;
        }

        if (file_exists(__DIR__ . '/../view/LoginView.php')) {
            require __DIR__ . '/../view/LoginView.php';
        } else {
            require __DIR__ . '/../view/login.php';
        }
    }

    private function procesarLogin(): void
    {
        $usuarioInput  = trim($_POST['username'] ?? $_POST['nombre_usuario'] ?? '');
        $passwordInput = $_POST['password'] ?? $_POST['contrasena'] ?? '';

        if ($usuarioInput === '' || $passwordInput === '') {
            $this->mostrarLogin('Por favor, introduce usuario y contraseña.', $usuarioInput);
            return;
        }

        $usuario = $this->loginDAO->buscarPorNombreUsuario($usuarioInput);

        if ($usuario === null || !password_verify($passwordInput, $usuario['password_hash'])) {
            $this->mostrarLogin('Usuario o contraseña incorrectos.', $usuarioInput);
            return;
        }

        session_regenerate_id(true);

        $idUsuario = (int) ($usuario['id_usuario'] ?? $usuario['id'] ?? 0);
        $nombre    = $usuario['nombre'] ?? '';
        $rol       = $usuario['rol'] ?? 'USER';

        $_SESSION['usuario_id'] = $idUsuario;
        $_SESSION['nombre']     = $nombre;
        $_SESSION['rol']        = $rol;

        $_SESSION['usuario'] = [
            'id'     => $idUsuario,
            'nombre' => $nombre,
            'rol'    => $rol
        ];

        $this->setMensaje('success', '¡Bienvenido de nuevo, ' . htmlspecialchars($nombre) . '!');
        $this->redirigir('index.php?page=equipamientos');
    }

    public function logout(): void
    {
        $_SESSION = [];

        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params['path'],
                $params['domain'],
                $params['secure'],
                $params['httponly']
            );
        }

        session_destroy();

        $this->redirigir('index.php?page=login');
    }

    private function dashboard(): void
    {
        if (!isset($_SESSION['usuario_id']) && !isset($_SESSION['usuario'])) {
            $this->redirigir('index.php?page=login');
            return;
        }

        if (file_exists(__DIR__ . '/../view/dashboard.php')) {
            require __DIR__ . '/../view/dashboard.php';
        } else {
            $this->redirigir('index.php?page=equipamientos');
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
