<?php
    header('Content-Type: application/json; charset=utf-8');

    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    function responderUsuarios(array $datos, int $estado = 200): void {
        http_response_code($estado);
        echo json_encode($datos, JSON_UNESCAPED_UNICODE);
    }

    function leerJsonUsuarios(): ?array {
        $contenido = file_get_contents('php://input');
        $datos = json_decode($contenido === false ? '' : $contenido, true);

        if (!is_array($datos)) {
            responderUsuarios(['error' => 'El cuerpo debe ser un objeto JSON válido.'], 400);
            return null;
        }

        return $datos;
    }

    function obtenerIdUsuarioApi(array $ruta): ?int {
        if (isset($_GET['id'])) {
            $id = filter_var($_GET['id'], FILTER_VALIDATE_INT);
            return $id === false ? null : $id;
        }

        if (isset($ruta[0]) && ctype_digit($ruta[0])) {
            return (int) $ruta[0];
        }

        return null;
    }

    function obtenerRutaUsuarioApi(): array {
        $ruta = $_SERVER['PATH_INFO'] ?? '';
        if ($ruta === '') {
            $rutaCompleta = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?: '';
            $segmentos = array_values(array_filter(explode('/', trim($rutaCompleta, '/')), 'strlen'));
            $indiceUsuarios = null;

            foreach ($segmentos as $indice => $segmento) {
                $segmento = strtolower($segmento);
                if ($segmento === 'usuarios'
                    || $segmento === 'usuarios.php'
                    || $segmento === 'usuariosapi.php') {
                    $indiceUsuarios = $indice;
                }
            }

            $ruta = $indiceUsuarios === null
                ? ''
                : implode('/', array_slice($segmentos, $indiceUsuarios + 1));
        }

        return array_values(array_filter(explode('/', trim($ruta, '/')), 'strlen'));
    }

    try {
        $conexion = new PDO(
            'mysql:host=localhost;dbname=reto_2daw;charset=utf8',
            'root',
            '',
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
        );
    } catch (PDOException $error) {
        error_log('No se pudo conectar a la base de datos para la API de usuarios: ' . $error->getMessage());
        responderUsuarios(['error' => 'No se pudo conectar con la base de datos.'], 500);
        exit;
    }

    require_once __DIR__ . '/../controller/UsuariosController.php';
    $controlador = new UsuariosController($conexion);
    $metodo = $_SERVER['REQUEST_METHOD'] ?? 'GET';
    $ruta = obtenerRutaUsuarioApi();
    $id = obtenerIdUsuarioApi($ruta);

    try {
        switch ($metodo) {
            case 'GET':
                if ($ruta !== [] && (count($ruta) !== 1 || !ctype_digit($ruta[0]))) {
                    responderUsuarios(['error' => 'Ruta no encontrada.'], 404);
                    break;
                }

                if ($id !== null) {
                    if ($id < 1) {
                        responderUsuarios(['error' => 'El ID debe ser un entero positivo.'], 400);
                        break;
                    }

                    $usuario = $controlador->obtenerPorId($id);
                    if ($usuario === null) {
                        responderUsuarios(['error' => 'No se encontró el usuario.'], 404);
                        break;
                    }

                    responderUsuarios($usuario);
                    break;
                }

                responderUsuarios($controlador->obtenerTodas());
                break;

            case 'POST':
                if ($ruta === ['login'] || ($_GET['accion'] ?? '') === 'login') {
                    $datos = leerJsonUsuarios();
                    if ($datos === null) {
                        break;
                    }
                    if (!isset($datos['nombre_usuario'], $datos['password'])
                        || !is_string($datos['nombre_usuario'])
                        || trim($datos['nombre_usuario']) === ''
                        || !is_string($datos['password'])
                        || trim($datos['password']) === '') {
                        responderUsuarios(['error' => 'Se requieren nombre_usuario y password.'], 400);
                        break;
                    }

                    responderUsuarios([
                        'autenticado' => $controlador->login(
                            $datos['nombre_usuario'],
                            $datos['password']
                        )
                    ]);
                    break;
                }

                if ($ruta !== []) {
                    responderUsuarios(['error' => 'Ruta no encontrada.'], 404);
                    break;
                }

                $datos = leerJsonUsuarios();
                if ($datos === null) {
                    break;
                }

                $idCreado = $controlador->crear($datos);
                if ($idCreado === false) {
                    responderUsuarios([
                        'error' => 'Error al crear el usuario. Comprueba los datos.'
                    ], 400);
                    break;
                }

                responderUsuarios([
                    'exito' => true,
                    'id_usuario' => $idCreado
                ], 201);
                break;

            case 'PUT':
                if (($ruta !== [] && (count($ruta) !== 1 || !ctype_digit($ruta[0])))
                    || $id === null || $id < 1) {
                    responderUsuarios(['error' => 'Se requiere un ID de usuario válido.'], 400);
                    break;
                }

                if (isset($_SESSION['usuario_id']) && (int) $_SESSION['usuario_id'] === $id) {
                    responderUsuarios([
                        'error' => 'No puedes editar tu propia cuenta mientras tienes la sesión iniciada.'
                    ], 403);
                    break;
                }

                $datos = leerJsonUsuarios();
                if ($datos === null) {
                    break;
                }

                $resultado = $controlador->actualizar($id, $datos);
                if ($resultado === null) {
                    responderUsuarios(['error' => 'No se encontró el usuario.'], 404);
                } elseif ($resultado) {
                    responderUsuarios(['exito' => true]);
                } else {
                    responderUsuarios([
                        'error' => 'Error al actualizar. Comprueba los datos.'
                    ], 400);
                }
                break;

            case 'PATCH':
                if (count($ruta) !== 2 || !ctype_digit($ruta[0]) || $ruta[1] !== 'password'
                    || $id === null || $id < 1) {
                    responderUsuarios(['error' => 'Se requiere un ID y la ruta password.'], 400);
                    break;
                }

                if (isset($_SESSION['usuario_id']) && (int) $_SESSION['usuario_id'] === $id) {
                    responderUsuarios([
                        'error' => 'No puedes editar tu propia cuenta mientras tienes la sesión iniciada.'
                    ], 403);
                    break;
                }

                $datos = leerJsonUsuarios();
                if ($datos === null) {
                    break;
                }

                $resultado = $controlador->actualizarPassword($id, $datos);
                if ($resultado === null) {
                    responderUsuarios(['error' => 'No se encontró el usuario.'], 404);
                } elseif ($resultado) {
                    responderUsuarios(['exito' => true]);
                } else {
                    responderUsuarios(['error' => 'Se requiere una contraseña válida.'], 400);
                }
                break;

            case 'DELETE':
                if (($ruta !== [] && (count($ruta) !== 1 || !ctype_digit($ruta[0])))
                    || $id === null || $id < 1) {
                    responderUsuarios(['error' => 'Se requiere un ID de usuario válido.'], 400);
                    break;
                }

                if (isset($_SESSION['usuario_id']) && (int) $_SESSION['usuario_id'] === $id) {
                    responderUsuarios([
                        'error' => 'No puedes eliminar tu propia cuenta mientras tienes la sesión iniciada.'
                    ], 403);
                    break;
                }

                $resultado = $controlador->eliminar($id);
                if ($resultado === null) {
                    responderUsuarios(['error' => 'No se encontró el usuario.'], 404);
                } elseif ($resultado) {
                    responderUsuarios(['exito' => true]);
                } else {
                    responderUsuarios(['error' => 'Error al eliminar el usuario.'], 500);
                }
                break;

            default:
                responderUsuarios(['error' => 'Método HTTP no permitido.'], 405);
                break;
        }
    } catch (PDOException $error) {
        error_log('Error de base de datos en la API de usuarios: ' . $error->getMessage());
        responderUsuarios(['error' => 'Error al procesar la solicitud.'], 500);
    }

    exit;
?>
