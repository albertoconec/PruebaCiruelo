<?php
require_once __DIR__ . "/../Models/ExpedicionesModel.php";

class ExpedicionesController {
    private $model;

    public function __construct() { $this->model = new ExpedicionesModel(); }

    public function listarOrdenes() {
        return $this->model->getOrdenesVisibles();
    }

    public function listarCargasDeOrden(int $ordenId) {
        return $this->model->getCargasByOrden($ordenId);
    }

    // ⬇️ Lógica exacta requerida: recibe id_palet e id_camion (+ id de la orden seleccionada)
    public function asignar(int $ordenId, int $id_palet, int $id_camion): array {
        return $this->model->asignarPaletACamionEnOrden($ordenId, $id_palet, $id_camion);
    }
}
