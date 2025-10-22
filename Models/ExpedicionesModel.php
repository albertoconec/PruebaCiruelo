<?php
require_once __DIR__ . "/ConexionPDOModel.php";

class ExpedicionesModel {
    private $db = null;

    public function __construct() {
        $this->db = (new ConexionPDOModel())->getConexion();
    }

    /* Órdenes visibles (ABIERTA / EN_CURSO) */
    public function getOrdenesVisibles(): array {
        $sql = "SELECT id, codigo, cliente, estado
                FROM ordenes
                WHERE estado IN ('ABIERTA','EN_CURSO')
                ORDER BY FIELD(estado,'EN_CURSO','ABIERTA'), id DESC";
        $st = $this->db->prepare($sql);
        $st->execute();
        return $st->fetchAll(PDO::FETCH_ASSOC);
    }

    /* Cargas (camiones) de una orden, con 'ocupados' y capacidad */
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


    /* Palet por id */
    private function getPaletById(int $id_palet): ?array {
        $st = $this->db->prepare("SELECT id, codigo, estado, orden_camion_id FROM palets WHERE id = ?");
        $st->execute([$id_palet]);
        $row = $st->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    /* Cierra una carga (orden_camion) */
    private function cerrarCarga(int $orden_camion_id): void {
        $st = $this->db->prepare("UPDATE orden_camion SET estado='CERRADA' WHERE id=?");
        $st->execute([$orden_camion_id]);
    }

    /* ▶️ Si la orden está ABIERTA y alguna carga tiene palets -> poner EN_CURSO */
    private function ponerOrdenEnCursoSiTienePalets(int $ordenId): void {
        // ¿Alguna carga de la orden tiene palets?
        $st = $this->db->prepare(
            "SELECT COUNT(*) 
             FROM orden_camion 
             WHERE orden_id = ? AND ocupados > 0"
        );
        $st->execute([$ordenId]);
        $hayPalets = (int)$st->fetchColumn() > 0;

        if ($hayPalets) {
            // Pasa la orden a EN_CURSO si sigue ABIERTA
            $up = $this->db->prepare(
                "UPDATE ordenes 
                 SET estado = 'EN_CURSO' 
                 WHERE id = ? AND estado = 'ABIERTA'"
            );
            $up->execute([$ordenId]);
        }
    }

    /* ⛔ Si TODAS las cargas están CERRADAS -> cerrar la orden */
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
     * Opción 1 (requerida): Recibe id_palet e id_camion y la orden seleccionada.
     * Valida:
     *   - Palet existe, está ETIQUETADO y sin asignar
     *   - El camión pertenece a la orden y la carga está ABIERTA/EN_CURSO
     *   - Hay capacidad disponible (ocupados < capacidad)
     * Acción:
     *   - Marca palet ASIGNADO y lo enlaza a la carga (orden_camion_id)
     *   - Incrementa 'ocupados'
     *   - Si estaba ABIERTA y ahora tiene palets -> EN_CURSO (carga y orden)
     *   - Si se llena -> CERRADA (carga) y si todas cerradas -> CERRADA (orden)
     */
    public function asignarPaletACamionEnOrden(int $ordenId, int $id_palet, int $id_camion): array {
        // 0) Palet existe
        $palet = $this->getPaletById($id_palet);
        if (!$palet) return ['ok'=>false, 'error'=>'El palet no existe'];

        try {
            $this->db->beginTransaction();

            // 1) Bloquear la carga de esa orden y camión
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

            // 2) Validar palet
            if ($palet['estado'] !== 'ETIQUETADO') throw new Exception('El palet no está en ETIQUETADO');
            if (!is_null($palet['orden_camion_id'])) throw new Exception('El palet ya está asignado');

            // 3) Asignar palet
            $up = $this->db->prepare("UPDATE palets SET estado='ASIGNADO', orden_camion_id=? WHERE id=?");
            $up->execute([(int)$carga['id'], $id_palet]);

            // 4) Incrementar ocupados
            $up2 = $this->db->prepare("UPDATE orden_camion SET ocupados = ocupados + 1 WHERE id = ?");
            $up2->execute([(int)$carga['id']]);
            $ocupadosDespues = $ocupados + 1;

            // 5) Si la carga estaba ABIERTA y ahora tiene palets -> poner EN_CURSO
            $estadoCarga = $carga['estado'];
            if ($estadoCarga === 'ABIERTA' && $ocupadosDespues > 0) {
                $this->db->prepare("UPDATE orden_camion SET estado='EN_CURSO' WHERE id=?")
                         ->execute([(int)$carga['id']]);
                $estadoCarga = 'EN_CURSO';
            }

            // 🔄 Sincronizar la ORDEN (si estaba ABIERTA y ya hay palets en alguna carga -> EN_CURSO)
            $this->ponerOrdenEnCursoSiTienePalets($ordenId);

            // 6) Cerrar carga si se llenó
            if ($ocupadosDespues >= $cap) {
                $this->cerrarCarga((int)$carga['id']);
                $estadoCarga = 'CERRADA';
            }

            // 🔄 Si todas las cargas han quedado cerradas, cerrar también la ORDEN
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
