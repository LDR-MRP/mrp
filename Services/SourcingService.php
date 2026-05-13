<?php

declare(strict_types=1);

use Requests\Requisition\StoreQuotationRequest;

class SourcingService
{
    use \Loggable;

    private \Com_requisicionModel $requisicionModel;
    private readonly \Com_requisicionCotizacionModel $requisicionCotizacionModel;
    private \Inv_inventarioModel $inventarioModel;
    private object $db;

    public function __construct() {
        $this->requisicionModel = new \Com_requisicionModel();
        $this->requisicionCotizacionModel = new \Com_requisicionCotizacionModel();
        $this->inventarioModel = new \Inv_inventarioModel();
        $this->db = $this->requisicionModel->getConexion();
    }

    public function getComparisonData(int $idReqArt, array $userContext): ServiceResponse {
        // 1. Obtener la ficha técnica (Precio Objetivo)
        $item = $this->requisicionModel->getItemSpecs($idReqArt);
        if (!$item) return ServiceResponse::error("No se encontró la ficha técnica.", 404);

        // 2. Obtener cotizaciones con datos de proveedor y contacto
        $cotizaciones = $this->requisicionCotizacionModel->getComparisonTable($idReqArt);

        return ServiceResponse::success([
            'item' => $item,
            'cotizaciones' => $cotizaciones
        ]);
    }

    public function storeQuotation(array $userContext): ServiceResponse {
        $request = new StoreQuotationRequest();
        try {
            $request->validate();
            $payload = $request->all();
            $userId = (int)$userContext['id'];

            $this->db->beginTransaction();

            // 1. Obtener Ficha Técnica para validar el ahorro (Petición de Tito)
            $specs = $this->requisicionModel->getItemSpecs((int)$payload['idrequisicionarticulo']);
            
            // 2. Gestionar PDF
            $file = $_FILES['cotizacion_pdf'];
            $path = "Assets/uploads/quotations/art_{$payload['idrequisicionarticulo']}/";
            if (!is_dir($path)) mkdir($path, 0755, true);
            
            $fileName = "COT_" . time() . "_" . bin2hex(random_bytes(4)) . ".pdf";
            $fullPath = $path . $fileName;

            if (!move_uploaded_file($file['tmp_name'], $fullPath)) {
                throw new Exception("Falla al mover archivo al servidor.");
            }

            // 3. Persistencia en DB
            $cotData = [
                'idrequisicionarticulo' => $payload['idrequisicionarticulo'],
                'id_proveedor'          => $payload['id_proveedor'],
                'precio_unitario'       => (float)$payload['precio_unitario'],
                'moneda'                => $payload['moneda'] ?? 'MXN',
                'tipo_cambio'           => (float)($payload['tipo_cambio'] ?? 1.0),
                'url_pdf_cotizacion'    => $fullPath,
                'comentarios_comprador' => $payload['comentarios_comprador'] ?? ''
            ];
            
            $this->requisicionCotizacionModel->insertQuotation($cotData);

            // 4. Cálculo de Ahorro para el mensaje de éxito
            $precioCotizadoMXN = $cotData['precio_unitario'] * $cotData['tipo_cambio'];
            $ahorro = (float)$specs['precio_objetivo'] - $precioCotizadoMXN;
            
            $msg = $ahorro >= 0 
                ? "Cotización guardada. ¡Ahorro detectado!" 
                : "Cotización guardada. NOTA: Excede el Precio Objetivo.";

            $this->db->commit();
            return ServiceResponse::success(null, $msg);

        } catch (\InvalidArgumentException $i) {
            if ($this->db->inTransaction()) $this->db->rollBack();
            return ServiceResponse::validation($i->getMessage());
        } catch (Exception $e) {
            if ($this->db->inTransaction()) $this->db->rollBack();
            return ServiceResponse::error($e->getMessage());
        }
    }

    /**
     * Marca una cotización como ganadora y actualiza la partida de la requisición.
     * 
     * @param array $data { idcotizacion }
     * @param array $userContext Contexto del usuario autenticado.
     */
    public function selectWinner(array $data, array $userContext): ServiceResponse
    {
        try {
            $idCotizacion = (int)($data['idcotizacion'] ?? 0);
            $userId = (int)$userContext['id'];

            $this->db->beginTransaction();

            // 1. Obtener datos de la cotización
            $cotizacion = $this->requisicionCotizacionModel->getQuotationById($idCotizacion);
            if (!$cotizacion) throw new \Exception("La cotización no existe.", 404);

            $idReqArt = (int)$cotizacion['idrequisicionarticulo'];

            // 2. RESETEAR GANADORAS PREVIAS (Regla: Solo una ganadora por partida)
            $this->requisicionCotizacionModel->resetWinnersByPartida($idReqArt);

            // 3. MARCAR NUEVA GANADORA
            $this->requisicionCotizacionModel->setWinner($idCotizacion);

            // 4. SINCRONIZAR CON REQUISICIÓN (Consistencia Financiera)
            // Calculamos el precio real en MXN (Precio * Tipo Cambio)
            $precioRealMXN = (float)$cotizacion['precio_unitario'] * (float)$cotizacion['tipo_cambio'];
            
            // Actualizamos el precio_unitario_estimado en la tabla de detalle original
            $this->requisicionModel->updatePartidaPrice($idReqArt, $precioRealMXN);

            // 5. RECALCULAR TOTAL DE LA REQUISICIÓN PADRE
            $this->requisicionModel->updateEstimatedAmount((int)$cotizacion['requisicionid']);

            // 6. AUDITORÍA
            $this->requisicionCotizacionModel->logAudit(
                (int)$cotizacion['requisicionid'], 
                AuditAction::UPDATED, 
                "Proveedor seleccionado para partida #{$idReqArt}. Cotización ganadora: #{$idCotizacion}", 
                $userId
            );

            $this->db->commit();

            return ServiceResponse::success(
                ['precio_final' => $precioRealMXN], 
                "Cotización seleccionada como ganadora. El presupuesto de la requisición ha sido actualizado."
            );

        } catch (\Exception $e) {
            if ($this->db->inTransaction()) $this->db->rollBack();
            return ServiceResponse::error($e->getMessage(), (int)$e->getCode() ?: 500);
        }
    }

    /**
     * Promueve un artículo especial a artículo oficial del catálogo maestro.
     */
    public function promoteToCatalog(array $userContext): ServiceResponse {
        $request = new \Requests\Sourcing\PromoteToCatalogRequest();
        
        try {
            $request->validate();
            $payload = $request->all();
            $userId = (int)$userContext['id'];

            $this->db->beginTransaction();

            $idReqArt = (int)$payload['idrequisicionarticulo'];

            // 1. Obtener la cotización ganadora para heredar el costo real
            $winner = $this->requisicionCotizacionModel->getWinnerQuotation($idReqArt);
            if (!$winner) throw new \Exception("Debe seleccionar una cotización ganadora antes de promover el artículo.", 403);

            // 2. Obtener especificaciones para heredar la descripción técnica
            $specs = $this->requisicionModel->getItemSpecs($idReqArt);

            // 3. PREPARAR DATA PARA EL MAESTRO (Consistente con DDL)
            $inventoryData = [
                'cve_articulo'    => strtoupper(trim($payload['cve_articulo'])),
                'descripcion'     => $specs['especificaciones_tecnicas'] ?? 'Artículo nuevo de sourcing',
                'lineaproductoid' => (int)$payload['lineaproductoid'],
                'tipo_elemento'   => $payload['tipo_elemento'], // K,P,S,H,C
                'ultimo_costo'    => (float)$winner['precio_unitario'] * (float)$winner['tipo_cambio'],
                'unidad_salida'   => $payload['unidad_salida'],
                'unidad_empaque'  => 1,
                'control_almacen' => 'FIFO',
                'factor_unidades' => 1,
                'serie'           => 'N',
                'lote'            => 'N',
                'pedimiento'      => 'N'
            ];

            $newInventoryId = $this->inventarioModel->insertOfficialItem($inventoryData);
            if (!$newInventoryId) throw new \Exception("Error al crear el SKU en el inventario.");

            // --- INICIO CAMBIO: VÍNCULO PROVEEDOR-ARTÍCULO ---
            // Creamos el convenio oficial para que Compras ya tenga este dato a futuro
            $this->inventarioModel->linkSupplierToItem([
                'id_proveedor'      => (int)$winner['id_proveedor'],
                'idinventario'      => $newInventoryId,
                'precio_referencia' => (float)$winner['precio_unitario'],
                'id_moneda'         => $winner['moneda'],
                'created_by'        => $userId
            ]);
            // --- FIN CAMBIO ---

            // 4. ACTUALIZAR REQUISICIÓN (Vincular el nuevo ID)
            // Reemplazamos el inventarioid NULL por el ID que acabamos de generar
            $this->requisicionModel->linkOfficialInventoryItem($idReqArt, $newInventoryId);

            // 5. AUDITORÍA
            $this->requisicionCotizacionModel->logAudit(
                (int)$winner['requisicionid'], 
                AuditAction::CREATED, 
                "Artículo promovido a Catálogo Maestro. Nuevo SKU: {$inventoryData['cve_articulo']}", 
                $userId
            );

            $this->db->commit();
            return ServiceResponse::success(['idinventario' => $newInventoryId], "Artículo catalogado exitosamente. Ya puede generar la Orden de Compra.");

        } catch (\InvalidArgumentException $i) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            return ServiceResponse::validation(errors: $i->getMessage());
            
        } catch (\PDOException $p) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            $this->logMessage($p, \LogLevel::CRITICAL, [
                'action' => 'storeRequisition',
                'id_user' => $userContext['id']
            ]);
            return ServiceResponse::error(message: "Ocurrió un error de integridad en la base de datos.");
            
        } catch (\Exception $e) {
            if ($this->db->inTransaction()) $this->db->rollBack();
            return ServiceResponse::error($e->getMessage(), 500);
        }
    }
}