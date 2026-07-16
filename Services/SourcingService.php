<?php

declare(strict_types=1);

use Requests\Sourcing\StoreQuotationRequest;
use Requests\Sourcing\StoreSourcingEventRequest;
class SourcingService
{
    use \Loggable;

    private \Com_requisicionModel $requisicionModel;
    private readonly \Com_requisicionCotizacionModel $requisicionCotizacionModel;
    private \Inv_inventarioModel $inventarioModel;
    private Models\Src_eventoSourcingModel $eventoSourcingModel;  
    private Prv_proveedorModel $proveedorModel;  
    private object $db;

    public function __construct() {
        $this->requisicionModel = new \Com_requisicionModel();
        $this->requisicionCotizacionModel = new \Com_requisicionCotizacionModel();
        $this->inventarioModel = new \Inv_inventarioModel();
        $this->eventoSourcingModel = new Models\Src_eventoSourcingModel();
        $this->proveedorModel = new Prv_proveedorModel();
        $this->db = $this->requisicionModel->getConexion();
    }

    /**
     * Recupera el "Espacio de Trabajo" completo para un evento de negociación.
     * Orquesta la cabecera, el menú lateral de items y la comparativa inicial.
     */
    public function getEventWorkspace(int $eventId, array $userContext): ServiceResponse
    {
        try {
            // 1. Detectar si solicitan un ítem específico
            $targetItemId = isset($_GET['target_item']) ? (int)$_GET['target_item'] : null;

            // 2. Al momento de elegir el initial_item:
            $initialItem = null;

            // 1. Obtener la cabecera del evento (Folio, Comprador, etc.)
            $eventHeader = $this->eventoSourcingModel->getEventHeader($eventId);
            if (!$eventHeader) return ServiceResponse::error("Evento no encontrado.", 404);

            // 2. Obtener todas las partidas vinculadas (El query que probamos en el Paso 1)
            $items = $this->eventoSourcingModel->getItemsByEvent($eventId);
            if (empty($items)) return ServiceResponse::error("El evento no tiene partidas vinculadas.", 404);

            // 3. Obtener el cuadro comparativo de la PRIMERA partida por defecto
            if ($targetItemId) {
                // Buscamos el ítem específico dentro de la lista del evento
                foreach ($items as $item) {
                    if ((int)$item['idrequisicionarticulo'] === $targetItemId) {
                        $initialItem = $item;
                        break;
                    }
                }
            }

            // Si no hay target o no se encontró en este evento, usamos el primero por defecto
            if (!$initialItem) {
                $initialItem = $items[0];
            }

            $idReqArt = (int)$initialItem['idrequisicionarticulo'];
            
            // Obtenemos cotizaciones y specs de esa partida
            $specs = $this->requisicionModel->getItemSpecs($idReqArt);
            $quotations = $this->requisicionCotizacionModel->getComparisonTable($idReqArt);

            // 4. Paquete de respuesta unificado
            return ServiceResponse::success([
                'event' => $eventHeader,
                'items' => $items, // Esto alimentará la columna izquierda (33%)
                'initial_item' => [
                    'specs'      => $specs,
                    'quotations' => $quotations // Esto alimentará la columna derecha (66%)
                ]
            ]);

        } catch (\Exception $e) {
            $this->logMessage($e, \LogLevel::ERROR);
            return ServiceResponse::error("Error al cargar el espacio de trabajo de la negociación.");
        }
    }

    /**
     * Recupera los eventos de negociación activos con KPIs básicos por folio.
     */
    public function getActiveEvents(array $userContext): ServiceResponse
    {
        try {
            $plantaId = (int)$userContext['plantaid'];
            $events = $this->eventoSourcingModel->getSourcingEvents($plantaId);

            // Enriquecemos con lógica de negocio básica (ej. días transcurridos)
            $formattedEvents = array_map(function($ev) {
                $ev['dias_abierto'] = (new \DateTime($ev['created_at']))->diff(new \DateTime())->days;
                return $ev;
            }, $events);

            return ServiceResponse::success($formattedEvents);
        } catch (\Exception $e) {
            return ServiceResponse::error("Error al cargar la bandeja de negociaciones.");
        }
    }

    /**
     * Recupera toda la información necesaria para el Cuadro Comparativo.
     */
    public function getComparisonData(int $idReqArt): ServiceResponse
    {
        // 1. Obtener contexto de la partida (Folio Requisición, Cantidad, Evento asignado)
        // Consultamos com_requisiciones_detalle
        $lineItem = $this->requisicionModel->getLineItemContext($idReqArt);
        if (!$lineItem) {
            return ServiceResponse::error("La partida #{$idReqArt} no existe.", 404);
        }

        // 2. Obtener la Ficha Técnica (Query de com_requisicion_items_nuevos)
        $specs = $this->requisicionModel->getItemSpecs($idReqArt);

        // 3. Obtener la tabla de cotizaciones (Bids de proveedores)
        $quotations = $this->requisicionCotizacionModel->getComparisonTable($idReqArt);

        return ServiceResponse::success([
            'context'    => $lineItem,   // Datos de la REQ y el Evento Sourcing
            'item'      => $specs,      // Datos técnicos y Precio Objetivo
            'cotizaciones' => $quotations  // Array de ofertas side-by-side
        ]);
    }

    /**
     * Recupera la lista de partidas 'Sourcing' listas para ser procesadas.
     * Bandeja de Entrada Inbox.
     */
    public function getPendingSourcingItems(array $userContext): ServiceResponse
    {
        try {
            $plantaId = (int)$userContext['plantaid'];
            $items = $this->eventoSourcingModel->getPendingSourcingItems($plantaId);

            return ServiceResponse::success($items, "Pendientes recuperados correctamente.");
        } catch (\Exception $e) {
            $this->logMessage($e, \LogLevel::ERROR);
            return ServiceResponse::error("Error al recuperar los ítems pendientes.");
        }
    }

    /**
     * Orquestador para agrupar partidas en un nuevo folio de negociación.
     * Implementa transacción atómica para asegurar la vinculación.
     */
    public function createEvent(array $userContext): ServiceResponse
    {
        $request = new StoreSourcingEventRequest();
        try {
            $request->validate();
            $payload = $request->all();
            $userId = (int)$userContext['id'];
            $userPlantaId = (int)$userContext['plantaid'];
            $this->db->beginTransaction();

            // 1. Generar Folio Único (Regla de Negocio: SOUR-AAMMDD-XXXX)
            $folio = 'SOUR-' . date('ymd') . '-' . strtoupper(bin2hex(random_bytes(2)));

            // 2. Crear cabecera del evento
            $eventData = [
                'folio'        => $folio,
                'titulo'       => $payload['titulo'] ?? 'Negociación de Compras',
                'comprador_id' => $userId,
                'planta_id'    => $userPlantaId,
                'created_by'   => $userId,
                'created_at'   => date('Y-m-d H:i:s')
            ];

            $eventId = (int)$this->eventoSourcingModel->createEventHeader($eventData);

            if ($eventId <= 0) {
                throw new \Exception("No se pudo generar la cabecera del evento.");
            }

            // 3. Vinculación de Partidas (Bulk Update)
            // Se asume que el modelo de requisición tiene este método para inyectar la FK
            $linkedErrors = [];
            foreach ($payload['items'] as $idReqArt) {
                $linked = $this->requisicionModel->updateEventLink((int)$idReqArt, $eventId);
                if (!$linked) {
                    $linkedErrors[] = $idReqArt;
                }
            }

            if (!empty($linkedErrors)) {
                throw new \Exception("Error al vincular la(s) partida(s) #" . implode(', #', $linkedErrors) . " al evento.");
            }

            // 4. Log de Auditoría
            $this->eventoSourcingModel->logAudit(
                $eventId, 
                AuditAction::CREATED, 
                "Evento de Sourcing", 
                $userId
            );

            $this->db->commit();

            return ServiceResponse::success(
                ['idevento' => $eventId, 'folio' => $folio], 
                "Evento {$folio} creado exitosamente. Las partidas han sido agrupadas."
            );

        }
        catch (\InvalidArgumentException $i)
        {
            if ($this->db->inTransaction())
            {
                $this->db->rollBack();
            }
            return ServiceResponse::validation($i->getMessage());
        }
        catch (\PDOException $p)
        {
            if ($this->db->inTransaction())
            {
                $this->db->rollBack();
            }
            $this->logMessage($p, \LogLevel::CRITICAL, [
                'action' => 'createEvent',
                'id_user' => $userId
            ]);
            return ServiceResponse::error(message: "Ocurrió un error de integridad en la base de datos.");            
        }
        catch (\Exception $e)
        {
            if ($this->db->inTransaction())
            {
                $this->db->rollBack();
            }
            $this->logMessage($e, \LogLevel::CRITICAL, ['payload' => $payload]);
            return ServiceResponse::error($e->getMessage());
        }
    }

    /**
     * Registra una cotización de proveedor, procesando la evidencia documental (PDF) 
     * y la evidencia física (Foto con redimensionamiento).
     * 
     * @param array $userContext Contexto del usuario autenticado.
     * @return ServiceResponse
     */
    public function storeQuotation(array $userContext): ServiceResponse {
        $request = new StoreQuotationRequest();
        try {
            $request->validate();
            $payload = $request->all();
            $userId = (int)$userContext['id'];
            $idReqArt = (int)$payload['idrequisicionarticulo'];

            $this->db->beginTransaction();

            // 1. Obtener Ficha Técnica para validar el ahorro
            $specs = $this->requisicionModel->getItemSpecs($idReqArt);
            $item = $this->requisicionModel->getLineItemContext($idReqArt);
            $eventId = $item['src_evento_sourcing_id'] ?? null;
            
            // 2. Gestionar PDF
            $path = "Assets/uploads/quotations/art_{$idReqArt}/";
            if (!is_dir($path)) mkdir($path, 0755, true);
            
            // 2. Gestionar PDF (Standard)
            $pdfFile = $_FILES['cotizacion_pdf'];
            $pdfName = "COT_" . time() . "_" . bin2hex(random_bytes(4)) . ".pdf";
            $pdfPath = $path . $pdfName;
            move_uploaded_file($pdfFile['tmp_name'], $pdfPath);

            // 3. NUEVO: Gestionar Foto con Redimensionamiento (GD Library)
            $photoPath = null;
            if (isset($_FILES['foto_producto']) && $_FILES['foto_producto']['error'] === UPLOAD_ERR_OK) {
                $photoFile = $_FILES['foto_producto'];
                $photoName = "IMG_" . time() . "_" . bin2hex(random_bytes(4)) . ".jpg";
                $photoPath = $path . $photoName;
                $this->resizeImage($photoFile['tmp_name'], $photoPath, 1200);
            }

            $esProspecto = ($payload['es_prospecto'] ?? '0') === '1';
            $incluyeIva = ($payload['iva_inc'] ?? '0') === '1';

            // --- INICIO CÁLCULO DE NORMALIZACIÓN ---
            $precioOriginal = (float)$payload['precio_unitario'];
            $tc = (float)($payload['tipo_cambio'] ?? 1.0);

            // Si incluye IVA, bajamos al subtotal (Normalización)
            $subtotalMXN = $incluyeIva ? ($precioOriginal / 1.16) : $precioOriginal;

            // Precio final comparable (Subtotal en Moneda Local)
            $precioBaseMXN = $subtotalMXN * $tc;
            // --- FIN CÁLCULO ---

            // 4. Persistencia en DB
            $cotData = [
                'idrequisicionarticulo'      => $idReqArt,
                'src_evento_sourcing_id'     => $eventId,
                'id_proveedor'               => $esProspecto ? null : $payload['id_proveedor'],
                'tipo_fuente'                => $payload['tipo_fuente'],
                'nombre_prospecto'           => $esProspecto ? $payload['nombre_prospecto'] : null,
                'precio_unitario'            => (float)$payload['precio_unitario'],
                'moneda'                     => $payload['moneda'] ?? 'MXN',
                'tipo_cambio'                => $tc,
                'iva_inc'                    => $incluyeIva ? 1 : 0, // <--- GUARDAR ESTADO
                'precio_base_mxn'            => $precioBaseMXN,     // <--- CAMPO PARA EL ORDER BY
                'url_pdf_cotizacion'         => $pdfPath,
                'url_foto_producto'          => $photoPath,
                'comentarios_comprador'      => $payload['comentarios_comprador'] ?? '',
                'specs_particulares_proveedor' => $payload['specs_particulares_proveedor'],
                'pago_inmediato'             => (int)($payload['pago_inmediato'] ?? 0),
                'url_referencia'             => $payload['url_referencia'] ?? null,
                'created_by'                 => $userId,
            ];
            
            $this->requisicionCotizacionModel->insertQuotation($cotData);

            // 5. Cálculo de Ahorro para el mensaje de éxito
            $ahorro = (float)$specs['precio_objetivo'] - $precioBaseMXN;
            
            $msg = $ahorro >= 0 
                ? "Cotización guardada. ¡Ahorro detectado!" 
                : "Cotización guardada. NOTA: Excede el Precio Objetivo.";

            $this->db->commit();
            return ServiceResponse::success(null, $msg);

        } catch (\InvalidArgumentException $i) {
            if ($this->db->inTransaction()) $this->db->rollBack();
            return ServiceResponse::validation($i->getMessage());
        } catch (\PDOException $p) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            $this->logMessage($p, \LogLevel::CRITICAL, [
                'action' => 'storeQuotation',
                'id_user' => $userId
            ]);
            return ServiceResponse::error(message: "Ocurrió un error de integridad en la base de datos.");            
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
            $userPlantaId = (int)$userContext['plantaid'];

            $this->db->beginTransaction();

            // 1. Obtener datos de la cotización
            $cotizacion = $this->requisicionCotizacionModel->getQuotationById($idCotizacion);
            if (!$cotizacion) throw new \Exception("La cotización no existe.", 404);

            // --- REGLA DE NEGOCIO: Mínimo 3 Cotizaciones ---
            $count = $this->requisicionCotizacionModel->countActiveQuotations((int)$cotizacion['idrequisicionarticulo']);
            if ($count < 3) {
                throw new \Exception("Compliance Error: Se requieren al menos 3 y máximo 5 cotizaciones para elegir una ganadora (Actuales: {$count}).", 409);
            }

            $idReqArt = (int)$cotizacion['idrequisicionarticulo'];

            // --- GUARDA 1: Impedir cambio de ganador si ya se promovió a SKU ---
            $partida = $this->requisicionModel->getLineItemContext($idReqArt);
            if (!empty($partida['inventarioid'])) {
                throw new \Exception("Esta partida ya fue catalogada en el WMS. La decisión comercial es inmutable.", 409);
            }

            $role = RoleEnum::tryFrom((int)$userContext['rolid']);
            $scope = $role?->getScope() ?? 'propio';
            
            // --- GUARDA 2: Validación IDOR
            $isAllowed = match($scope) {
                'propio' => (int)$partida['usuarioid'] === $userId,
                'planta'  => (int)$partida['plantaid'] === $userPlantaId,
                'total'  => true,
                default  => false
            };

            // Validación de Seguridad
            if (!$isAllowed) {
                return ServiceResponse::error("Conflicto de intereses: No tienes permitido aprobar o rechazar tus propias solicitudes.", 403);
            }

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

        } catch (\PDOException $p) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            $this->logMessage($p, \LogLevel::CRITICAL, [
                'action' => 'selectWinner',
                'id_user' => $userContext['id']
            ]);
            return ServiceResponse::error(message: "Ocurrió un error de integridad en la base de datos.");
            
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
            $idReqArt = (int)$payload['idrequisicionarticulo'];            
            $item = $this->requisicionModel->getItemSpecs($idReqArt);
            $eventId = isset($item['src_evento_sourcing_id']) ? (int)$item['src_evento_sourcing_id'] : null;

            if (!empty($item['inventarioid'])) {
                throw new \Exception("Operación inválida: El artículo ya cuenta con el SKU {$item['sku_oficial']} en el catálogo maestro.", 409);
            }
            
            // 1. Obtener la cotización ganadora para heredar el costo real
            $winner = $this->requisicionCotizacionModel->getWinnerQuotation($idReqArt);
            if (!$winner) throw new \Exception("Debe seleccionar una cotización ganadora antes de promover el artículo.", 409);
            
            $this->db->beginTransaction();

            $finalProviderId = $this->resolveProviderId($winner);

            // 3. PREPARAR DATA PARA EL MAESTRO (Consistente con DDL)
            $inventoryData = [
                'cve_articulo'    => strtoupper(trim($payload['cve_articulo'])),
                'descripcion'     => $payload['descripcion'] ?? 'Artículo nuevo de sourcing',
                'lineaproductoid' => (int)$payload['lineaproductoid'],
                'tipo_elemento'   => $payload['tipo_elemento'], // K,P,S,H,C
                'ultimo_costo'    => (float)$winner['precio_base_mxn'],
                'unidad_salida'   => $payload['unidad_salida'],
                'unidad_empaque'  => 1,
                'control_almacen' => ($payload['tipo_elemento'] === 'S') ? 'NONE' : 'FIFO',
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
                'id_proveedor'      => $finalProviderId,
                'idinventario'      => $newInventoryId,
                'precio_referencia' => (float)$winner['precio_unitario'],
                'id_moneda'         => $winner['moneda'],
                'created_by'        => $userId
            ]);
            // --- FIN CAMBIO ---

            // 4. ACTUALIZAR REQUISICIÓN (Vincular el nuevo ID)
            // Reemplazamos el inventarioid NULL por el ID que acabamos de generar
            $this->requisicionModel->linkOfficialInventoryItem($idReqArt, $finalProviderId, $newInventoryId);

            // IMPORTANTE: Actualizamos la cotización ganadora con este nuevo ID 
            // para que la trazabilidad sea permanente.
            $this->requisicionCotizacionModel->updateProviderLink(
                (int)$winner['idcotizacion'], 
                $finalProviderId
            );

            // 5. AUDITORÍA
            $this->requisicionCotizacionModel->logAudit(
                (int)$winner['requisicionid'], 
                AuditAction::CREATED, 
                "Artículo promovido a Catálogo Maestro. Nuevo SKU: {$inventoryData['cve_articulo']}", 
                $userId
            );

            // Solo si la partida estaba vinculada a un evento, disparamos la verificación de cierre
            if ($eventId) {
                /**
                 * checkAndCloseEvent() debe:
                 * 1. Marcar esta partida como "PROCESADA".
                 * 2. Contar si quedan más partidas pendientes en ese evento.
                 * 3. Si contador == 0, cambiar src_eventos_sourcing.estatus_evento a 'ADJUDICADO'.
                 */
                $this->eventoSourcingModel->checkAndCloseEvent($eventId, $userId);
            }

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
                'action' => 'promoteToCatalog',
                'id_user' => $userContext['id']
            ]);
            return ServiceResponse::error(message: "Ocurrió un error de integridad en la base de datos.");
            
        } catch (\Exception $e) {
            if ($this->db->inTransaction()) $this->db->rollBack();
            return ServiceResponse::error($e->getMessage(), (int)$e->getCode() ?: 500);
        }
    }

    /**
     * Resuelve el ID de proveedor definitivo para el catálogo maestro.
     * Maneja los tres escenarios: Activo, Retail y Prospecto (Pre-registro).
     * 
     * @param array $winner Datos de la cotización ganadora.
     * @return int ID de proveedor válido en el catálogo.
     */
    private function resolveProviderId(array $winner): int
    {
        // ESCENARIO 1: Proveedor Retail (Amazon/ML)
        // Si la cotización se marcó como pago inmediato/retail, mapeamos al genérico.
        if ($winner['tipo_fuente'] === 'RETAIL') {
            return 999; // ID del Proveedor Genérico Retail
        }

        // ESCENARIO 2: Proveedor Activo o Prospecto ya registrado
        // Si ya existe un ID en el catálogo, simplemente lo devolvemos.
        if (!empty($winner['id_proveedor'])) {
            return (int)$winner['id_proveedor'];
        }
        
        // ESCENARIO 3: Prospecto Puro (id_proveedor es NULL)
        // Realizamos un Pre-registro "Lite" para obtener un ID y cumplir la integridad.
        $tempRFC = 'TEMP-' . strtoupper(bin2hex(random_bytes(4)));
        $prospectData = [
            'razon_social'       => $winner['nombre_prospecto'],
            'rfc'                => $tempRFC,
            'rfc_activo'         => $tempRFC,
            'estatus_onboarding' => 'Prospecto', // Este estatus congela la OC posterior
            'created_at'         => date('Y-m-d H:i:s')
        ];

        // Insertamos en la tabla maestra de proveedores (prv_cat_proveedores)
        $newProviderId = $this->proveedorModel->insertLite($prospectData);

        return $newProviderId;
    }

    /**
     * Elimina lógicamente una cotización.
     * HU: Limpieza de Sourcing.
     * 
     * @param int $id ID de la cotización.
     * @param array $userContext Usuario autenticado.
     */
    public function deleteQuotation(int $id, array $userContext): ServiceResponse
    {
        try {
            $this->db->beginTransaction();

            // 1. Obtener y Validar
            $cot = $this->requisicionCotizacionModel->getQuotationById($id);
            if (!$cot) throw new \Exception("La cotización no existe.", 404);

            // 2. REGLA DE NEGOCIO: No se puede borrar la ganadora
            if ((int)$cot['es_ganadora'] === 1) {
                throw new \Exception("No puedes eliminar la cotización ganadora. Primero debes seleccionar otra opción o desmarcarla.", 409);
            }

            // 3. Ejecutar Soft Delete
            $this->requisicionCotizacionModel->softDelete($id, $userContext['id']);

            // 4. Auditoría
            $this->requisicionCotizacionModel->logAudit(
                (int)$cot['requisicionid'], 
                AuditAction::DELETED, 
                "Cotización del proveedor #{$cot['id_proveedor']} eliminada por el usuario.", 
                $userContext['id']
            );

            $this->db->commit();
            return ServiceResponse::success(null, "Cotización removida del cuadro comparativo.");

        } catch (\PDOException $p) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            $this->logMessage($p, \LogLevel::CRITICAL, [
                'action' => 'deleteQuotation',
                'id_user' => $userContext['id']
            ]);
            return ServiceResponse::error(message: "Ocurrió un error de integridad en la base de datos.");
            
        } catch (\Exception $e) {
            if ($this->db->inTransaction()) $this->db->rollBack();
            return ServiceResponse::error($e->getMessage(), (int)$e->getCode() ?: 500);
        }
    }

    /**
     * Redimensiona una imagen para optimizar espacio en disco.
     * @private
     */
    private function resizeImage(string $sourcePath, string $destPath, int $maxWidth): void {
        list($origWidth, $origHeight, $type) = getimagesize($sourcePath);
        $ratio = $maxWidth / $origWidth;
        $newWidth = $maxWidth;
        $newHeight = (int)($origHeight * $ratio);

        $srcImage = match($type) {
            IMAGETYPE_JPEG => imagecreatefromjpeg($sourcePath),
            IMAGETYPE_PNG  => imagecreatefrompng($sourcePath),
            default => throw new \Exception("Formato de imagen no soportado para redimensionamiento.")
        };

        $newImage = imagecreatetruecolor($newWidth, $newHeight);
        imagecopyresampled($newImage, $srcImage, 0, 0, 0, 0, $newWidth, $newHeight, $origWidth, $origHeight);
        imagejpeg($newImage, $destPath, 85); // Save as JPG with 85% quality
        imagedestroy($srcImage);
        imagedestroy($newImage);
    }
}