<?php
class ConexionPDOModel {
    private $conexion = null;
    private $server = "localhost";
    private $username = "root";
    private $password = "";
    private $database = "expediciones";

    public function __construct() {
        try{
            $dsn = "mysql:host={$this->server};dbname={$this->database};charset=utf8mb4";
            $this->conexion = new PDO($dsn, $this->username, $this->password);
            $this->conexion->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch(PDOException $e){
            die("Error en la conexión: ".$e->getMessage());
        }
    }
    public function getConexion(){ return $this->conexion; }
}
