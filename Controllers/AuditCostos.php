<?php

class AuditCostos extends Controllers
{
    public function __construct()
    {
    }

    public function index()
    {
        require_once 'Models/Lgs_enviosModel.php';
        require_once 'Services/Lgs_enviosService.php';
        require_once 'Models/Lgs_planeacionesModel.php';

        $db = new Mysql();
        $service = new Lgs_enviosService();

        $sqlEnvios = "SELECT id_envio, folio, costo_total, id_estado FROM lgs_envios WHERE deleted_at IS NULL AND id_estado != 0";
        $envios = $db->select_all($sqlEnvios);

        echo "Auditoría de Costos de Envíos...\n<br>";
        echo "===============================================\n<br>";
        $diferencias = 0;

        foreach ($envios as $e) {
            $id = (int)$e['id_envio'];
            $oldCosto = (float)$e['costo_total'];
            
            try {
                $newCosto = $service->recalcularCostoTotal($id);
                
                $diff = abs($newCosto - $oldCosto);
                if ($diff > 0.01) {
                    echo "Envío #{$id} [Folio: {$e['folio']}] -> Antes: $" . number_format($oldCosto, 2) . " | Ahora: $" . number_format($newCosto, 2) . " | Dif: $" . number_format($newCosto - $oldCosto, 2) . "\n<br>";
                    $diferencias++;
                }
            } catch (Exception $ex) {
                echo "Envío #{$id} [Folio: {$e['folio']}] -> ERROR en recálculo: " . $ex->getMessage() . "\n<br>";
            }
        }

        echo "===============================================\n<br>";
        echo "Total de envíos con diferencias de costo corregidos: {$diferencias}\n<br>";

        // Recalcular planeaciones
        echo "\n<br>Auditoría de Costos de Planeaciones...\n<br>";
        echo "===============================================\n<br>";
        $sqlPlan = "SELECT id_planeacion, folio, costo_total FROM lgs_planeaciones WHERE deleted_at IS NULL AND id_estado != 0";
        $planeaciones = $db->select_all($sqlPlan);
        $diferenciasPlan = 0;

        foreach ($planeaciones as $p) {
            $idPlan = (int)$p['id_planeacion'];
            $oldCostoPlan = (float)$p['costo_total'];
            
            try {
                $sqlSum = "SELECT SUM(e.costo_total) as suma FROM lgs_planeaciones_envios pe INNER JOIN lgs_envios e ON pe.id_envio = e.id_envio WHERE pe.id_planeacion = ?";
                $resSum = $db->select($sqlSum, [$idPlan]);
                $newCostoPlan = (float)($resSum['suma'] ?? 0);
                
                if (abs($newCostoPlan - $oldCostoPlan) > 0.01) {
                    $db->update("UPDATE lgs_planeaciones SET costo_total = ? WHERE id_planeacion = ?", [$newCostoPlan, $idPlan]);
                    echo "Planeación #{$idPlan} [Folio: {$p['folio']}] -> Antes: $" . number_format($oldCostoPlan, 2) . " | Ahora: $" . number_format($newCostoPlan, 2) . " | Dif: $" . number_format($newCostoPlan - $oldCostoPlan, 2) . "\n<br>";
                    $diferenciasPlan++;
                }
            } catch (Exception $ex) {}
        }
        echo "===============================================\n<br>";
        echo "Total de planeaciones con diferencias corregidas: {$diferenciasPlan}\n<br>";
    }
}
