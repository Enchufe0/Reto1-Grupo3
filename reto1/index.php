<?php
// 1. Conexión a la base de datos
$conexion = new PDO("mysql:host=localhost;dbname=reto_2daw;charset=utf8", "root", "");
// 2. Requerimos el controlador desde su carpeta
require_once 'controller/UbicacionesController.php';

// 3. Instanciamos el controlador con un nombre estandar
$ubicacionesController = new UbicacionesController($conexion);

// 4. Ejecutamos la prueba
echo "<h1>Probando la conexión...</h1>";
$ubicacionesController->obtenerTodas();
?>