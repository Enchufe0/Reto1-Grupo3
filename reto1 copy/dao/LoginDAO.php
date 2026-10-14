<?php
class LoginDAO
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    public function buscarPorNombreUsuario(string $nombreUsuario): ?array
    {
        $stmt = $this->db->prepare('
            SELECT id_usuario, nombre, apellidos, password_hash, rol
            FROM usuarios
            WHERE nombre_usuario = :nombre_usuario
            LIMIT 1
        ');
        $stmt->execute(['nombre_usuario' => $nombreUsuario]);
        $fila = $stmt->fetch(PDO::FETCH_ASSOC);

        return $fila ?: null;
    }
}
