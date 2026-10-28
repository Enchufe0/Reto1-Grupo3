<?php
class Database {
    private $host = "localhost";
    private $db_name = "reto_2daw";
    private $username = "root";
    private $password = "";
    private $conexion = null;

    public function getConnection() {
        if ($this->conexion !== null) return $this->conexion;
        try {
            $this->conexion = new PDO("mysql:host={$this->host};dbname={$this->db_name};charset=utf8mb4", $this->username, $this->password);
            $this->conexion->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->conexion->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            die("Error de conexion: " . $e->getMessage());
        }
        return $this->conexion;
    }
}
?>