<?php

require_once __DIR__ . '/../dao/NotificacionesDAO.php';
require_once __DIR__ . '/../model/Notificaciones.php';
require_once __DIR__ . '/../model/EstadoNotificaciones.php';

use enumNotificaciones\EstadoNotificaciones;

class NotificacionesController
{
    private NotificacionesDAO $notificacionesDAO;

    public function __construct(PDO $conexion)
    {
        $this->notificacionesDAO = new NotificacionesDAO($conexion);
    }

    public function obtenerTodas(): array
    {
        return array_map([$this, 'serializar'], $this->notificacionesDAO->obtenerTodas());
    }

    public function obtenerPorUsuario(int $idUsuario): array
    {
        if ($idUsuario <= 0) {
            throw new Exception('El ID del usuario no es válido', 400);
        }

        return array_map(
            [$this, 'serializar'],
            $this->notificacionesDAO->obtenerPorUsuario($idUsuario)
        );
    }

    public function obtenerPorId(int $id): array
    {
        if ($id <= 0) {
            throw new Exception('El ID de la notificación no es válido', 400);
        }

        $notificacion = $this->notificacionesDAO->obtenerPorId($id);
        if ($notificacion === null) {
            throw new Exception("No se encontró ninguna notificación con el ID: $id", 404);
        }

        return $this->serializar($notificacion);
    }

    public function crear(array $datos): bool
    {
        $notificacion = $this->crearModelo($datos, false);
        if (!$this->notificacionesDAO->crear($notificacion)) {
            throw new Exception('Error al guardar la notificación en la base de datos', 500);
        }

        return true;
    }

    public function actualizar(array $datos): bool
    {
        $notificacion = $this->crearModelo($datos, true);
        if (!$this->notificacionesDAO->actualizar($notificacion)) {
            throw new Exception('Error al actualizar la notificación en la base de datos', 500);
        }

        return true;
    }

    public function eliminar(int $id): bool
    {
        if ($id <= 0) {
            throw new Exception('El ID de la notificación no es válido', 400);
        }

        if (!$this->notificacionesDAO->borrar($id)) {
            throw new Exception('Error al eliminar la notificación de la base de datos', 500);
        }

        return true;
    }

    private function crearModelo(array $datos, bool $incluirId): Notificaciones
    {
        $camposObligatorios = ['titulo', 'descripcion', 'fecha_creacion', 'estado', 'id_usuario'];
        if ($incluirId) {
            $camposObligatorios[] = 'id_notificacion';
        }

        foreach ($camposObligatorios as $campo) {
            if (!array_key_exists($campo, $datos)) {
                throw new Exception('Faltan datos obligatorios para la notificación', 400);
            }
        }

        foreach (['titulo', 'descripcion', 'fecha_creacion', 'estado'] as $campo) {
            if (!is_string($datos[$campo])) {
                throw new Exception("El campo $campo no es válido", 400);
            }
        }

        $idUsuario = filter_var($datos['id_usuario'], FILTER_VALIDATE_INT);
        $idNotificacion = $incluirId
            ? filter_var($datos['id_notificacion'], FILTER_VALIDATE_INT)
            : 0;

        if ($idUsuario === false || $idUsuario <= 0) {
            throw new Exception('El ID del usuario no es válido', 400);
        }
        if ($incluirId && ($idNotificacion === false || $idNotificacion <= 0)) {
            throw new Exception('El ID de la notificación no es válido', 400);
        }

        $estado = EstadoNotificaciones::tryFrom($datos['estado']);
        if ($estado === null) {
            throw new Exception('El estado de la notificación no es válido', 400);
        }

        return new Notificaciones(
            $idNotificacion,
            $datos['titulo'],
            $datos['descripcion'],
            $datos['fecha_creacion'],
            $estado,
            $idUsuario
        );
    }

    private function serializar(Notificaciones $notificacion): array
    {
        return [
            'id_notificacion' => $notificacion->getIdNotificacion(),
            'titulo' => $notificacion->getTitulo(),
            'descripcion' => $notificacion->getDescripcion(),
            'fecha_creacion' => $notificacion->getFechaCreacion(),
            'estado' => $notificacion->getEstado()->value,
            'id_usuario' => $notificacion->getIdUsuario(),
        ];
    }
}
