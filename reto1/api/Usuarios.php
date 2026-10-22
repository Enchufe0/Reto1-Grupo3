<?php
require_once __DIR__ . '/../model/Usuarios.php';

class UsuariosDAO
{
    private string $apiBaseUrl;

    public function __construct(string $apiBaseUrl){
        $this->apiBaseUrl = rtrim($apiBaseUrl, '/');
    }

    private function enviarPeticionApi(string $metodo, string $ruta, ?array $datos = null): mixed
    {
        $url = $this->apiBaseUrl . '/api/' . ltrim($ruta, '/');
        $curl = curl_init($url);
        if ($curl === false) {
            throw new RuntimeException('No se pudo iniciar la petición a la API.');
        }

        $opciones = [
            CURLOPT_CUSTOMREQUEST => $metodo,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Accept: application/json'
            ],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 10
        ];

        if ($datos !== null) {
            $opciones[CURLOPT_POSTFIELDS] = json_encode($datos, JSON_THROW_ON_ERROR);
        }

        if (!curl_setopt_array($curl, $opciones)) {
            $error = curl_error($curl);
            curl_close($curl);
            throw new RuntimeException('No se pudo configurar la petición a la API: ' . $error);
        }

        $respuesta = curl_exec($curl);
        $estadoHttp = curl_getinfo($curl, CURLINFO_HTTP_CODE);

        if ($respuesta === false) {
            $error = curl_error($curl);
            curl_close($curl);
            throw new RuntimeException('Error al conectar con la API: ' . $error);
        }

        curl_close($curl);

        if ($estadoHttp < 200 || $estadoHttp >= 300) {
            throw new RuntimeException('La API respondió con el estado HTTP ' . $estadoHttp . '.', $estadoHttp);
        }

        return json_decode($respuesta, true, 512, JSON_THROW_ON_ERROR);
    }

    private function normalizarUsuarioPublico(mixed $usuario): array
    {
        $idUsuario = is_array($usuario) && array_key_exists('id_usuario', $usuario)
            ? filter_var($usuario['id_usuario'], FILTER_VALIDATE_INT)
            : false;

        if (
            !is_array($usuario)
            || $idUsuario === false
            || !isset($usuario['nombre']) || !is_string($usuario['nombre'])
            || !isset($usuario['apellidos']) || !is_string($usuario['apellidos'])
            || !isset($usuario['nombre_usuario']) || !is_string($usuario['nombre_usuario'])
        ) {
            throw new UnexpectedValueException('La respuesta de la API no contiene los datos públicos esperados del usuario.');
        }

        return [
            'id_usuario' => $idUsuario,
            'nombre' => $usuario['nombre'],
            'apellidos' => $usuario['apellidos'],
            'nombre_usuario' => $usuario['nombre_usuario']
        ];
    }

    private function validarRespuestaExito(mixed $respuesta): bool
    {
        if (!is_array($respuesta) || !isset($respuesta['exito']) || !is_bool($respuesta['exito'])) {
            throw new UnexpectedValueException('La respuesta de la API no contiene un campo exito válido.');
        }

        return $respuesta['exito'];
    }

    public function login(string $nombre_usuario, string $password): bool
    {
        $resultado = $this->enviarPeticionApi('POST', 'usuarios/login', [
            'nombre_usuario' => $nombre_usuario,
            'password' => $password
        ]);
        if (!is_array($resultado) || !isset($resultado['autenticado']) || !is_bool($resultado['autenticado'])) {
            throw new UnexpectedValueException('La respuesta de la API no contiene un campo autenticado válido.');
        }

        return $resultado['autenticado'];
    }

    public function buscarPorId(int $id): ?array
    {
        try {
            $usuario = $this->enviarPeticionApi('GET', 'usuarios/' . $id);
        } catch (RuntimeException $error) {
            if ($error->getCode() === 404) {
                return null;
            }
            throw $error;
        }

        return $this->normalizarUsuarioPublico($usuario);
    }

    public function buscarTodos(): array
    {
        $respuesta = $this->enviarPeticionApi('GET', 'usuarios');
        if (!is_array($respuesta) || !array_is_list($respuesta)) {
            throw new UnexpectedValueException('La API debe responder con una lista JSON de usuarios.');
        }

        $usuarios = [];
        foreach ($respuesta as $usuario) {
            $usuarios[] = $this->normalizarUsuarioPublico($usuario);
        }

        return $usuarios;
    }

    public function crear(Usuarios $usuario): bool
    {
        $respuesta = $this->enviarPeticionApi('POST', 'usuarios', [
            'nombre' => $usuario->getNombre(),
            'apellidos' => $usuario->getApellidos(),
            'nombre_usuario' => $usuario->getNombreUsuario(),
            'password_hash' => $usuario->getPasswordHash()
        ]);

        return $this->validarRespuestaExito($respuesta);
    }

    public function actualizar(Usuarios $usuario): bool
    {
        $respuesta = $this->enviarPeticionApi('PUT', 'usuarios/' . $usuario->getIdUsuario(), [
            'nombre' => $usuario->getNombre(),
            'apellidos' => $usuario->getApellidos(),
            'nombre_usuario' => $usuario->getNombreUsuario(),
            'password_hash' => $usuario->getPasswordHash()
        ]);

        return $this->validarRespuestaExito($respuesta);
    }

    public function actualizar_password(Usuarios $usuario, string $nuevaPassword): bool
    {
        $respuesta = $this->enviarPeticionApi(
            'PATCH',
            'usuarios/' . $usuario->getIdUsuario() . '/password',
            ['password' => $nuevaPassword]
        );

        return $this->validarRespuestaExito($respuesta);
    }

    public function borrar(int $id): bool
    {
        try {
            $respuesta = $this->enviarPeticionApi('DELETE', 'usuarios/' . $id);
        } catch (RuntimeException $error) {
            if ($error->getCode() === 404) {
                return false;
            }
            throw $error;
        }

        return $this->validarRespuestaExito($respuesta);
    }
}