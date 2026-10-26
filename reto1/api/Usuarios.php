<?php

header('Content-Type: application/json; charset=utf-8');

function responderUsuarios(array $datos, int $estado = 200): void
{
    http_response_code($estado);
    echo json_encode($datos, JSON_UNESCAPED_UNICODE);
}

function leerJsonUsuarios(): ?array
{
    $contenido = file_get_contents('php://input');
    $datos = json_decode($contenido === false ? '' : $contenido, true);

    if (!is_array($datos)) {
        responderUsuarios(['error' => 'El cuerpo debe ser un objeto JSON válido.'], 400);
        return null;
    }

    return $datos;
}

function tieneCamposTextoUsuarios(array $datos, array $campos): bool
{
    foreach ($campos as $campo) {
        if (!isset($datos[$campo]) || !is_string($datos[$campo]) || trim($datos[$campo]) === '') {
            return false;
        }
    }

    return true;
}

function existeUsuario(PDO $conexion, int $id): bool
{
    $consulta = $conexion->prepare('SELECT 1 FROM usuarios WHERE id_usuario = :id_usuario');
    $consulta->execute(['id_usuario' => $id]);

    return $consulta->fetchColumn() !== false;
}

function obtenerIdUsuarioApi(array $ruta): ?int
{
    if (isset($_GET['id'])) {
        return filter_var($_GET['id'], FILTER_VALIDATE_INT) ?: null;
    }

    if (isset($ruta[0]) && ctype_digit($ruta[0])) {
        return (int) $ruta[0];
    }

    return null;
}

function obtenerRutaUsuarioApi(): array
{
    $ruta = $_SERVER['PATH_INFO'] ?? '';
    if ($ruta === '') {
        $ruta = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?: '';
        $segmentos = array_values(array_filter(explode('/', trim($ruta, '/')), 'strlen'));
        $indiceUsuarios = null;

        foreach ($segmentos as $indice => $segmento) {
            if (strtolower($segmento) === 'usuarios' || strtolower($segmento) === 'usuarios.php') {
                $indiceUsuarios = $indice;
            }
        }

        $ruta = $indiceUsuarios === null ? '' : implode('/', array_slice($segmentos, $indiceUsuarios + 1));
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

                $consulta = $conexion->prepare(
                    'SELECT id_usuario, nombre, apellidos, nombre_usuario
                     FROM usuarios WHERE id_usuario = :id_usuario'
                );
                $consulta->execute(['id_usuario' => $id]);
                $usuario = $consulta->fetch(PDO::FETCH_ASSOC);

                if ($usuario === false) {
                    responderUsuarios(['error' => 'No se encontró el usuario.'], 404);
                    break;
                }

                responderUsuarios($usuario);
                break;
            }

            $consulta = $conexion->query(
                'SELECT id_usuario, nombre, apellidos, nombre_usuario FROM usuarios'
            );
            responderUsuarios($consulta->fetchAll(PDO::FETCH_ASSOC));
            break;

        case 'POST':
            if (($ruta === ['login']) || ($_GET['accion'] ?? '') === 'login') {
                $datos = leerJsonUsuarios();
                if ($datos === null) {
                    break;
                }
                if (!tieneCamposTextoUsuarios($datos, ['nombre_usuario', 'password'])) {
                    responderUsuarios(['error' => 'Se requieren nombre_usuario y password.'], 400);
                    break;
                }

                $consulta = $conexion->prepare(
                    'SELECT password_hash FROM usuarios WHERE nombre_usuario = :nombre_usuario LIMIT 1'
                );
                $consulta->execute(['nombre_usuario' => $datos['nombre_usuario']]);
                $passwordHash = $consulta->fetchColumn();

                responderUsuarios([
                    'autenticado' => $passwordHash !== false && password_verify($datos['password'], $passwordHash)
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
            if (!tieneCamposTextoUsuarios($datos, ['nombre', 'apellidos', 'nombre_usuario'])) {
                responderUsuarios(['error' => 'Se requieren nombre, apellidos y nombre_usuario.'], 400);
                break;
            }

            $password = $datos['password'] ?? null;
            $passwordHash = $datos['password_hash'] ?? null;
            if (is_string($password) && $password !== '') {
                $passwordHash = password_hash($password, PASSWORD_DEFAULT);
            } elseif (!is_string($passwordHash) || $passwordHash === '') {
                responderUsuarios(['error' => 'Se requiere una contraseña válida.'], 400);
                break;
            }

            $consulta = $conexion->prepare(
                'INSERT INTO usuarios (nombre, apellidos, nombre_usuario, password_hash)
                 VALUES (:nombre, :apellidos, :nombre_usuario, :password_hash)'
            );
            $consulta->execute([
                'nombre' => trim($datos['nombre']),
                'apellidos' => trim($datos['apellidos']),
                'nombre_usuario' => trim($datos['nombre_usuario']),
                'password_hash' => $passwordHash
            ]);

            responderUsuarios([
                'exito' => true,
                'id_usuario' => (int) $conexion->lastInsertId()
            ], 201);
            break;

        case 'PUT':
            if (($ruta !== [] && (count($ruta) !== 1 || !ctype_digit($ruta[0])))
                || $id === null || $id < 1) {
                responderUsuarios(['error' => 'Se requiere un ID de usuario válido.'], 400);
                break;
            }

            $datos = leerJsonUsuarios();
            if ($datos === null) {
                break;
            }
            if (!tieneCamposTextoUsuarios($datos, ['nombre', 'apellidos', 'nombre_usuario'])) {
                responderUsuarios(['error' => 'Se requieren nombre, apellidos y nombre_usuario.'], 400);
                break;
            }

            $consulta = $conexion->prepare(
                'UPDATE usuarios
                 SET nombre = :nombre, apellidos = :apellidos, nombre_usuario = :nombre_usuario
                 WHERE id_usuario = :id_usuario'
            );
            $consulta->execute([
                'nombre' => trim($datos['nombre']),
                'apellidos' => trim($datos['apellidos']),
                'nombre_usuario' => trim($datos['nombre_usuario']),
                'id_usuario' => $id
            ]);

            if ($consulta->rowCount() === 0 && !existeUsuario($conexion, $id)) {
                responderUsuarios(['error' => 'No se encontró el usuario.'], 404);
                break;
            }

            responderUsuarios(['exito' => true]);
            break;

        case 'PATCH':
            if (count($ruta) !== 2 || !ctype_digit($ruta[0]) || $ruta[1] !== 'password'
                || $id === null || $id < 1) {
                responderUsuarios(['error' => 'Se requiere un ID y la ruta password.'], 400);
                break;
            }

            $datos = leerJsonUsuarios();
            if ($datos === null) {
                break;
            }
            if (!tieneCamposTextoUsuarios($datos, ['password'])) {
                responderUsuarios(['error' => 'Se requiere una contraseña válida.'], 400);
                break;
            }

            $consulta = $conexion->prepare(
                'UPDATE usuarios SET password_hash = :password_hash WHERE id_usuario = :id_usuario'
            );
            $consulta->execute([
                'password_hash' => password_hash($datos['password'], PASSWORD_DEFAULT),
                'id_usuario' => $id
            ]);

            if ($consulta->rowCount() === 0 && !existeUsuario($conexion, $id)) {
                responderUsuarios(['error' => 'No se encontró el usuario.'], 404);
                break;
            }

            responderUsuarios(['exito' => true]);
            break;

        case 'DELETE':
            if (($ruta !== [] && (count($ruta) !== 1 || !ctype_digit($ruta[0])))
                || $id === null || $id < 1) {
                responderUsuarios(['error' => 'Se requiere un ID de usuario válido.'], 400);
                break;
            }

            $consulta = $conexion->prepare('DELETE FROM usuarios WHERE id_usuario = :id_usuario');
            $consulta->execute(['id_usuario' => $id]);

            if ($consulta->rowCount() === 0) {
                responderUsuarios(['error' => 'No se encontró el usuario.'], 404);
                break;
            }

            responderUsuarios(['exito' => true]);
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
