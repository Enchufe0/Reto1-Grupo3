<?php
    class Database{
        private $host = "localhost";
        private $db_name = "Grupo03";
        private $username = "root";
        private $password = "";
        private ?PDO $conexion = null;

        public function getConnection(){
            $this->conexion = null;
            try{
                $this->conexion = new PDO("mysql:host={$this->host};dbname={$this->db_name};",
                    $this->username,
                    $this->password
                );
                $this->conexion->exec("SET NAMES utf8");
            } catch (PDOException $e){
                echo "Error de conexion: " . $e->getMessage();
            }

            return $this->conexion;
        }
    }
?>