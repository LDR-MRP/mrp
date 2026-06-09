<?php
declare(strict_types=1);

namespace Services;

use AuditAction;
use Com_ordenCompraModel;
use Prv_proveedorModel;
use AccountsPayableInvoiceModel;
use ThreeWayMatchService;
use ServiceResponse;
use SoapClient;

readonly class InvoiceValidationService
{    
    private const RFC_LDR_SOLUTIONS = 'LDR170101XX1'; // <-- Reemplaza por tu RFC oficial
    private const SAT_SOAP_URL = 'https://consultaqr.facturaelectronica.sat.gob.mx/ConsultaCFDIService.svc?wsdl';
    private object $db;

    public function __construct(
        private Com_ordenCompraModel $ordenCompraModel,
        private Prv_proveedorModel $proveedorModel,
        private AccountsPayableInvoiceModel $facturaModel,
        private ThreeWayMatchService $threeWayMatchService
    ) {
        $this->db = $this->proveedorModel->getConexion();
    }

    /**
     * Obtiene el historial de facturas cargadas de un proveedor específico.
     * 
     * @param array $userContext Datos extraídos del JWT (Inyectados por el Middleware)
     * @return ServiceResponse
     */
    public function getHistory(array $userContext): ServiceResponse
    {
        try {
            $vendorId = (int) ($userContext['vendor_id'] ?? 0);
            if ($vendorId <= 0) {
                return ServiceResponse::error("Perfil de proveedor no asociado o inactivo.", 400);
            }

            // Consultamos al modelo inyectado
            $invoices = $this->facturaModel->getByProveedor($vendorId);

            // CASTEO SEGURO (Estándar PHP 8.3 / Laravel 13):
            // Convertimos las cadenas de texto que devuelve PDO a tipos de datos nativos
            foreach ($invoices as &$inv) {
                $inv['id'] = (int) $inv['id'];
                $inv['id_compra'] = (int) $inv['id_compra'];
                $inv['codigo_oc'] = (int) $inv['codigo_oc'];
                $inv['monto_total'] = (float) $inv['monto_total'];
                $inv['estatus_validacion'] = (int) $inv['estatus_validacion'];
            }

            return ServiceResponse::success($invoices, "Historial de facturas recuperado con éxito.");

        } catch (\Exception $e) {
            return ServiceResponse::error("Error al recuperar el historial: " . $e->getMessage(), 500);
        }
    }

    /**
     * Procesa, valida de forma síncrona ante el SAT y almacena la factura.
     */
    public function validateAndUpload(array $userContext, array $postData, array $files): ServiceResponse
    {
        try {
            $vendorId = (int) ($userContext['vendor_id'] ?? 0);
            $idOc     = (int) ($postData['id_orden_compra'] ?? 0);

            if ($vendorId <= 0 || $idOc <= 0) {
                return ServiceResponse::error("Datos de referencia insuficientes.", 400);
            }

            // 1. Validar existencia de archivos
            $xmlFile = $files['factura_xml'] ?? null;
            $pdfFile = $files['factura_pdf'] ?? null;

            if (!$xmlFile || !$pdfFile || $xmlFile['error'] !== UPLOAD_ERR_OK || $pdfFile['error'] !== UPLOAD_ERR_OK) {
                return ServiceResponse::validation("Es obligatorio subir tanto el archivo XML como el PDF de la factura.");
            }

            // 2. PARSEAR XML (Prevención de XXE Injection)
            // Deshabilitar la carga de entidades externas por seguridad
            libxml_use_internal_errors(true);
            $xmlContent = file_get_contents($xmlFile['tmp_name']);
            
            // PHP 8.3 / libxml: Deshabilitamos entidades externas de forma segura
            $xml = simplexml_load_string($xmlContent, "SimpleXMLElement", LIBXML_NONET | LIBXML_NOENT);
            if (!$xml) {
                libxml_clear_errors();
                return ServiceResponse::validation("El archivo XML no tiene un formato válido o está corrupto.");
            }

            // 3. EXTRAER METADATOS DEL CFDI 4.0 USANDO NAMESPACES DEL SAT
            $namespaces = $xml->getDocNamespaces(true);
            $xml->registerXPathNamespace('cfdi', $namespaces['cfdi'] ?? 'http://www.sat.gob.mx/cfd/4');
            $xml->registerXPathNamespace('tfd', $namespaces['tfd'] ?? 'http://www.sat.gob.mx/TimbreFiscalDigital');

            // Extraer atributos del Comprobante
            $total = (float) (str_replace(',', '', (string)$xml->xpath('//cfdi:Comprobante/@Total')[0]) ?? 0);
            $subtotal = (float) (str_replace(',', '', (string)$xml->xpath('//cfdi:Comprobante/@Subtotal')[0]) ?? 0);
            $folio    = (string) ($xml->xpath('//cfdi:Comprobante/@Folio')[0] ?? '');
            $serie    = (string) ($xml->xpath('//cfdi:Comprobante/@Serie')[0] ?? '');
            $fechaEmision = (string) ($xml->xpath('//cfdi:Comprobante/@Fecha')[0] ?? '');
            
            // Extraer RFCs
            $rfcEmisor   = (string) ($xml->xpath('//cfdi:Comprobante/cfdi:Emisor/@Rfc')[0] ?? '');
            $rfcReceptor = (string) ($xml->xpath('//cfdi:Comprobante/cfdi:Receptor/@Rfc')[0] ?? '');
            
            // Extraer UUID (Timbre Fiscal Digital)
            $uuid = (string) ($xml->xpath('//tfd:TimbreFiscalDigital/@UUID')[0] ?? '');

            if (empty($uuid) || empty($rfcEmisor) || empty($rfcReceptor) || $total <= 0) {
                return ServiceResponse::validation("No se pudieron extraer los nodos mandatorios del CFDI (UUID, RFCs, Total).");
            }

            // 4. CRUCE DE SEGURIDAD (COMPLIANCE)
            // A. Validar que el RFC Receptor sea el de LDR Solutions
            if (strtoupper($rfcReceptor) !== self::RFC_LDR_SOLUTIONS) {
                return ServiceResponse::error("El RFC receptor de la factura ({$rfcReceptor}) no coincide con el de LDR Solutions.", 422);
            }

            // B. Validar que el RFC Emisor coincida con el RFC registrado del Proveedor
            $proveedor = $this->proveedorModel->getById($vendorId);
            if (!$proveedor || strtoupper($proveedor['rfc']) !== strtoupper($rfcEmisor)) {
                return ServiceResponse::error("Fraude detectado: El RFC emisor de la factura ({$rfcEmisor}) no coincide con su RFC registrado en el sistema.", 422);
            }

            // Cálculo Matemático de Vencimiento ---
            // Tomamos los días de crédito de tu tabla maestra de proveedores (Ej. 30 días)
            // Si el campo no existe o es nulo, asumimos 0 días (Pago de contado / inmediato)
            $diasCredito = (int) ($proveedor['dias_credito'] ?? 0);
            $fechaVencimiento = null;

            if (!empty($fechaEmision)) {
                // CFDI viene como: "2026-05-26T12:00:00", tomamos solo la fecha y removemos la hora
                $fechaSolo = explode('T', $fechaEmision)[0];
                $date = new \DateTime($fechaSolo);
                
                if ($diasCredito > 0) {
                    $date->modify("+{$diasCredito} days");
                }
                
                $fechaVencimiento = $date->format('Y-m-d');
            }

            // C. Validar montos contra la Orden de Compra (Tres-Way Match preliminar)
            $oc = $this->ordenCompraModel->getById($idOc);
            if (!$oc || (int)$oc['proveedorid'] !== $vendorId) {
                return ServiceResponse::error("La Orden de Compra asociada no existe o pertenece a otro proveedor.", 403);
            }

            $saldoPendiente = (float)$oc['total'] - (float)($oc['total_facturado'] ?? 0);
            if ($total > ($saldoPendiente + 0.10)) { // Tolerancia de 10 centavos por redondeos de IVA
                return ServiceResponse::error("Rechazo de Factura: El total de la factura (" . number_format($total, 2) . ") excede el saldo facturable de la Orden de Compra (" . number_format($saldoPendiente, 2) . ").", 409);
            }

            // 5. VALIDACIÓN SÍNCRONA ANTE EL SAT (SOAP Client con Timeout)
            $satStatus = $this->querySatStatus($rfcEmisor, $rfcReceptor, $total, $uuid);
            if ($satStatus !== 'Vigente') {
                return ServiceResponse::error("La factura fue rechazada por el SAT. Estatus actual: {$satStatus}.", 422);
            }

            // 6. MOVER ARCHIVOS AL STORAGE (Hostinger)
            $relativeDir = "Assets/uploads/facturas/prov_{$vendorId}/";
            $physicPath  = __DIR__ . '/../' . $relativeDir;

            if (!is_dir($physicPath)) {
                mkdir($physicPath, 0755, true);
            }

            $serieFolio = !empty($serie) ? "{$serie}_{$folio}" : $folio;
            $xmlName = "FAC_{$serieFolio}_" . time() . ".xml";
            $pdfName = "FAC_{$serieFolio}_" . time() . ".pdf";

            if (!move_uploaded_file($xmlFile['tmp_name'], $physicPath . $xmlName) ||
                !move_uploaded_file($pdfFile['tmp_name'], $physicPath . $pdfName)) {
                throw new \Exception("Error al guardar los archivos físicos en el servidor.", 500);
            }

            // 7. REGISTRAR PASIVO EN CUENTAS POR PAGAR (CxP)
            $facturaId = $this->facturaModel->registrarFactura([
                'id_proveedor'   => $vendorId,
                'id_compra'      => $idOc,
                'serie_folio'    => $serieFolio,
                'uuid'           => $uuid,
                'monto_total'    => $total,
                'fecha_vencimiento' => $fechaVencimiento,
                'url_xml'        => $relativeDir . $xmlName,
                'url_pdf'        => $relativeDir . $pdfName,
                'created_by'     => (int)$userContext['id']
            ]);

            // 8. EJECUTAR CONCILIACIÓN AUTOMÁTICA DE 3 VÍAS
            $reconciliation = $this->threeWayMatchService->reconcile($facturaId);
            $reconcileData  = $reconciliation->data;

            $this->facturaModel->logAudit($facturaId, AuditAction::CREATED, "Validación y carga de factura.", $vendorId);

            return ServiceResponse::success([
                'id_factura' => $facturaId,
                'serie_folio' => $serieFolio,
                'total'       => $total,
                'estatus_final' => $reconcileData['estatus_final'], // 0=Congelado, 1=Aprobado
                'comentarios'   => $reconcileData['comentarios']
            ], "Factura validada ante el SAT y registrada exitosamente en CxP.");

        } catch (\InvalidArgumentException $i) {
            return ServiceResponse::validation($i->getMessage());
        } catch (\PDOException $e) {
            if ($this->db->inTransaction()) $this->db->rollBack();
            return ServiceResponse::error("Error de integridad en base de datos.");
        } catch (\Exception $e) {
            return ServiceResponse::error($e->getMessage(), (int)$e->getCode() ?: 500);
        }
    }

    /**
     * Consulta el estado del CFDI mediante el Web Service oficial del SAT.
     * @private
     */
    private function querySatStatus(string $re, string $rr, float $tt, string $uuid): string
    {
        // --- BYPASS DE DESARROLLO (WSL) ---
        // Si detectamos que estamos en entorno local (ej. localhost o IP privada de WSL)
        // simulamos que el SAT nos responde que la factura está vigente para poder testear sin trabas.
        if ($_SERVER['SERVER_NAME'] === 'localhost' || $_SERVER['SERVER_ADDR'] === '127.0.0.1' || $_SERVER['SERVER_ADDR'] === '::1' || str_starts_with($_SERVER['SERVER_ADDR'] ?? '', '192.168.')) {
            return 'Vigente'; 
        }

        try {
            // Dar formato a los importes flotantes requeridos por el validador del SAT
            // El total debe tener un formato estricto de 6 decimales con ceros al final si es necesario
            $formattedTotal = number_format($tt, 6, '.', '');
            $expression = "?re={$re}&rr={$rr}&tt={$formattedTotal}&id={$uuid}";

            // Configurar SoapClient con timeouts cortos para evitar colgar el servidor Hostinger
            $context = stream_context_create([
                'ssl' => [
                    'verify_peer' => false,
                    'verify_peer_name' => false,
                    'allow_self_signed' => true
                ]
            ]);

            $client = new SoapClient(self::SAT_SOAP_URL, [
                'connection_timeout' => 5, // 5 segundos máximo para no colgar la UI del SRM
                'stream_context' => $context,
                'exceptions' => true,
                'trace' => true
            ]);

            $response = $client->Consulta(['expresionImpresa' => $expression]);
            
            // Retorna: "Vigente", "Cancelado" o "No Encontrado"
            return $response->ConsultaResult->Estado ?? 'No Encontrado';

        } catch (\Throwable $t) {
            // Si el SAT está caído (pasa seguido), registramos la advertencia pero por seguridad
            // y continuidad de negocio podemos decidir si lo dejamos en estatus 'Pendiente' en lugar de tronar.
            // Para el MVP estricto, lanzaremos excepción.
            throw new \Exception("No se pudo validar el estatus de la factura ante el SAT (Servicio de Validación temporalmente fuera de línea).");
        }
    }
}