<?php
//namespace Models;

use Mysql; // Asumiendo que tu clase base está en el namespace global o ajusta según tu autoloader

class Com_ordenCompraModel extends Mysql {

    public function __construct() {
        parent::__construct();
    }

    /**
     * Crea la cabecera de la Orden de Compra.
     * @return int El ID de la nueva OC (idcompra).
     */
    public function createHeader(array $data): int {
        $query = "INSERT INTO com_ordenes_compra 
                  (requisicionid, proveedorid, almacenid, usuarioid, estatus, moneda, tipo_cambio, observaciones, created_by) 
                  VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $values = [
            $data['requisicionid'],
            $data['proveedorid'],
            $data['almacenid'],
            $data['usuarioid'],
            $data['estatus'],
            $data['moneda'],
            $data['tipo_cambio'],
            $data['observaciones'],
            $data['created_by']
        ];

        return $this->insert($query, $values);
    }

    /**
     * Crea una partida (detalle) de la Orden de Compra.
     */
    public function createDetail(int $ocId, array $data): bool {
        $query = "INSERT INTO com_ordenes_compra_detalle 
                  (compraid, idrequisicionarticulo, inventarioid, cantidad, costo_unitario, descuento_partida, impuesto_partida, subtotal_partida) 
                  VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        
        $values = [
            $ocId,
            $data['idrequisicionarticulo'],
            $data['inventarioid'],
            $data['cantidad'],
            $data['costo_unitario'],
            $data['descuento_partida'],
            $data['impuesto_partida'],
            $data['subtotal_partida']
        ];

        // Usamos insert porque devuelve el ID, si es > 0 fue exitoso.
        return $this->insert($query, $values) > 0;
    }

    /**
     * Actualiza los totales financieros en la cabecera de la OC.
     */
    public function updateTotals(int $ocId, float $subtotal, float $iva, float $total): bool {
        $query = "UPDATE com_ordenes_compra 
                  SET subtotal = ?, iva = ?, total = ? 
                  WHERE idcompra = ?";
        
        return $this->update($query, [$subtotal, $iva, $total, $ocId]);
    }
}
?>