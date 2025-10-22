<?php
require_once __DIR__ . "/ConexionPDOModel.php";

class ExpedicionesModel {
    private $db = null;

    public function __construct() {
        $this->db = (new ConexionPDOModel())->getConexion();
    }

    /* === Usuarios === */
    public function getCarretilleros(): array {
        $st = $this->db->prepare("SELECT id, nombre FROM usuarios WHERE rol = 'carretillero' ORDER BY nombre ASC");
        $st->execute();
        return $st->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getUsuarioById(int $id): ?array {
        $st = $this->db->prepare("SELECT id, nombre, email, rol FROM usuarios WHERE id = ?");
        $st->execute([$id]);
        $row = $st->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    /* === Órdenes & cargas === */
    public function getOrdenesVisibles(): array {
        $sql = "SELECT id, codigo, cliente, estado
                FROM ordenes
                WHERE estado IN ('ABIERTA','EN_CURSO')
                ORDER BY FIELD(estado,'EN_CURSO','ABIERTA'), id DESC";
        $st = $this->db->prepare($sql);
        $st->execute();
        return $st->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getCargasByOrden(int $ordenId): array {
        $sql = "SELECT oc.id, oc.estado, oc.ocupados,
                       c.id AS id_camion, c.matricula, c.capacidad
                FROM orden_camion oc
                JOIN camiones c ON c.id = oc.camion_id
                WHERE oc.orden_id = ?
                  AND oc.estado IN ('ABIERTA','EN_CURSO')
                ORDER BY oc.id DESC";
        $st = $this->db->prepare($sql);
        $st->execute([$ordenId]);
        return $st->fetchAll(PDO::FETCH_ASSOC);
    }

    private function getPaletById(int $id_palet): ?array {
        $st = $this->db->prepare("SELECT id, codigo, estado, orden_camion_id FROM palets WHERE id = ?");
        $st->execute([$id_palet]);
        $row = $st->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    private function cerrarCarga(int $orden_camion_id): void {
        $st = $this->db->prepare("UPDATE orden_camion SET estado='CERRADA' WHERE id=?");
        $st->execute([$orden_camion_id]);
    }

    private function ponerOrdenEnCursoSiTienePalets(int $ordenId): void {
        $st = $this->db->prepare(
            "SELECT COUNT(*) 
             FROM orden_camion 
             WHERE orden_id = ? AND ocupados > 0"
        );
        $st->execute([$ordenId]);
        $hayPalets = (int)$st->fetchColumn() > 0;

        if ($hayPalets) {
            $up = $this->db->prepare(
                "UPDATE ordenes 
                 SET estado = 'EN_CURSO' 
                 WHERE id = ? AND estado = 'ABIERTA'"
            );
            $up->execute([$ordenId]);
        }
    }

    private function cerrarOrdenSiTodasCargasCerradas(int $ordenId): void {
        $st = $this->db->prepare(
            "SELECT SUM(estado IN ('ABIERTA','EN_CURSO')) AS abiertas
             FROM orden_camion
             WHERE orden_id = ?"
        );
        $st->execute([$ordenId]);
        $abiertas = (int)$st->fetchColumn();

        if ($abiertas === 0) {
            $up = $this->db->prepare("UPDATE ordenes SET estado = 'CERRADA' WHERE id = ?");
            $up->execute([$ordenId]);
        }
    }

    /**
     * Recibe: ordenId, id_palet, id_camion, usuarioId (carretillero en sesión)
     * - Valida palet, carga, capacidad
     * - Asigna palet y setea palets.usuario_id
     * - Cambia estados (EN_CURSO/CERRADA) y sincroniza orden
     */
    public function asignarPaletACamionEnOrden(int $ordenId, int $id_palet, int $id_camion, int $usuarioId): array {
        $palet = $this->getPaletById($id_palet);
        if (!$palet) return ['ok'=>false, 'error'=>'El palet no existe'];

        try {
            $this->db->beginTransaction();

            $st = $this->db->prepare(
                "SELECT oc.id, oc.estado, oc.ocupados,
                        c.capacidad, c.matricula
                 FROM orden_camion oc
                 JOIN camiones c ON c.id = oc.camion_id
                 WHERE oc.orden_id = ? AND oc.camion_id = ?
                 FOR UPDATE"
            );
            $st->execute([$ordenId, $id_camion]);
            $carga = $st->fetch(PDO::FETCH_ASSOC);
            if (!$carga) throw new Exception('Ese camión no pertenece a la orden seleccionada');
            if (!in_array($carga['estado'], ['ABIERTA','EN_CURSO'])) throw new Exception('La carga del camión no está disponible');

            $ocupados = (int)$carga['ocupados'];
            $cap      = (int)$carga['capacidad'];
            if ($ocupados >= $cap) throw new Exception('Camión sin capacidad');

            if ($palet['estado'] !== 'ETIQUETADO') throw new Exception('El palet no está en ETIQUETADO');
            if (!is_null($palet['orden_camion_id'])) throw new Exception('El palet ya está asignado');

            // asignar palet + traza de usuario
            $up = $this->db->prepare("UPDATE palets SET estado='ASIGNADO', orden_camion_id=?, usuario_id=? WHERE id=?");
            $up->execute([(int)$carga['id'], $usuarioId, $id_palet]);

            // incrementar ocupación
            $this->db->prepare("UPDATE orden_camion SET ocupados = ocupados + 1 WHERE id = ?")
                     ->execute([(int)$carga['id']]);
            $ocupadosDespues = $ocupados + 1;

            // actualizar estado carga ABIERTA -> EN_CURSO
            $estadoCarga = $carga['estado'];
            if ($estadoCarga === 'ABIERTA' && $ocupadosDespues > 0) {
                $this->db->prepare("UPDATE orden_camion SET estado='EN_CURSO' WHERE id=?")
                         ->execute([(int)$carga['id']]);
                $estadoCarga = 'EN_CURSO';
            }

            // sincronizar orden ABIERTA -> EN_CURSO si ya hay palets
            $this->ponerOrdenEnCursoSiTienePalets($ordenId);

            // cerrar carga si se llenó
            if ($ocupadosDespues >= $cap) {
                $this->cerrarCarga((int)$carga['id']);
                $estadoCarga = 'CERRADA';
            }

            // si todas las cargas cerradas, cerrar orden
            $this->cerrarOrdenSiTodasCargasCerradas($ordenId);

            $this->db->commit();

            return [
                'ok'        => true,
                'mensaje'   => 'Palet asignado correctamente',
                'palet'     => ['id_palet' => $id_palet],
                'camion'    => ['id_camion' => $id_camion, 'matricula' => $carga['matricula']],
                'orden'     => ['id_orden' => $ordenId, 'orden_camion_id' => (int)$carga['id'], 'estado_carga' => $estadoCarga],
                'ocupacion' => ['usados' => $ocupadosDespues, 'capacidad' => $cap]
            ];
        } catch (Exception $e) {
            if ($this->db->inTransaction()) $this->db->rollBack();
            return ['ok'=>false, 'error'=>$e->getMessage()];
        }
    }
}
