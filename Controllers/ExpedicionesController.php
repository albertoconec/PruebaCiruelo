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

    public function listarCarretilleros() {
        return $this->model->getCarretilleros();
    }

    public function getUsuario(int $id) {
        return $this->model->getUsuarioById($id);
    }

    public function asignar(int $ordenId, int $id_palet, int $id_camion, int $usuarioId): array {
        return $this->model->asignarPaletACamionEnOrden($ordenId, $id_palet, $id_camion, $usuarioId);
    }
}
