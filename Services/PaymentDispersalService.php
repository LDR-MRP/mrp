<?php

declare(strict_types=1);

namespace Services;

use AccountsPayableInvoiceModel;
use AuditAction;
use Prv_proveedorModel;
use ServiceResponse;

readonly class PaymentDispersalService
{
    // Cuenta de retiro de LDR Solutions registrada en BBVA (10 dígitos obligatorios)
    private const CUENTA_RETIRO_BBVA = '0123456789'; // Cuenta de retiro de LDR en BBVA (10 dígitos)
    private const CUENTA_RETIRO_BANORTE = '9876543210'; // Cuenta de retiro de LDR en Banorte (10 dígitos)

    public function __construct(
        private AccountsPayableInvoiceModel $paymentModel,
        private Prv_proveedorModel $proveedorModel
    ) {}

    /**
     * Obtiene el listado de facturas autorizadas para pago.
     * 
     * @param array $userContext Datos del usuario del ERP (JWT)
     * @return ServiceResponse
     */
    public function getPending(array $userContext): ServiceResponse
    {
        try {
            // Protección de rol: Solo personal del ERP puede ver pagos pendientes
            if (($userContext['rol'] ?? '') === 'VENDOR') {
                return ServiceResponse::error("Acceso denegado. Rol insuficiente.", 403);
            }

            $payments = $this->paymentModel->getPendingPayments();

            // Casteo de tipos para PHP 8.3
            foreach ($payments as &$pay) {
                $pay['id_factura'] = (int)$pay['id_factura'];
                $pay['monto_total'] = (float)$pay['monto_total'];
            }

            return ServiceResponse::success($payments, "Listado de pagos pendientes recuperado.");

        } catch (\Exception $e) {
            return ServiceResponse::error("Error al recuperar pagos pendientes: " . $e->getMessage(), 500);
        }
    }

    /**
     * Genera el archivo Layout dinámico de acuerdo al banco de origen seleccionado por LDR.
     * 
     * @param array  $invoiceIds  Array de IDs de facturas
     * @param string $bankOrigin  'BBVA' o 'BANORTE'
     * @param array  $userContext Datos del usuario (JWT)
     */
    public function generateLayout(array $invoiceIds, string $bankOrigin, array $userContext): ServiceResponse
    {
        try {
            if (($userContext['role'] ?? '') === 'VENDOR') {
                return ServiceResponse::error("Acceso denegado.", 403);
            }

            $payments = $this->paymentModel->getPendingPayments();
            $invoicesToPay = [];

            foreach ($payments as $pay) {
                if (in_array((int)$pay['id_factura'], $invoiceIds, true)) {
                    $invoicesToPay[] = $pay;
                }
            }

            if (empty($invoicesToPay)) {
                return ServiceResponse::error("No se encontraron facturas válidas seleccionadas.", 422);
            }

            // =================================================================
            // FACTORY / STRATEGY: Seleccionar motor de generación según el banco
            // =================================================================
            $layoutContent = "";
            $filename = "";

            switch (strtoupper($bankOrigin)) {
                case 'BBVA':
                    $layoutContent = $this->buildBbvaLayout($invoicesToPay);
                    $filename = "LAYOUT_BBVA_PAGOS_" . date('Ymd_His') . ".txt";
                    break;
                    
                case 'BANORTE':
                    $layoutContent = $this->buildBanorteLayout($invoicesToPay);
                    $filename = "LAYOUT_BANORTE_PAGOS_" . date('Ymd_His') . ".txt";
                    break;

                default:
                    return ServiceResponse::error("El banco de origen seleccionado ('{$bankOrigin}') no cuenta con un motor de maquetación de layout activo.", 422);
            }

            // Marcar de forma masiva estas facturas como "PROGRAMADAS"
            $this->paymentModel->markAsProgrammed($invoiceIds, (int)$userContext['id']);

            // Registrar auditoría
            foreach ($invoicesToPay as $invoice) {
                $this->proveedorModel->logAudit(
                    (int)$invoice['id_proveedor'], 
                    AuditAction::GENERATE_PAYMENT_LAYOUT, 
                    "Layout {$bankOrigin} generado para dispersión. Factura ID: {$invoice['id_factura']}", 
                    (int)$userContext['id']
                );
            }

            return ServiceResponse::success([
                'content'  => $layoutContent,
                'filename' => $filename
            ], "Layout bancario de {$bankOrigin} generado exitosamente.");

        } catch (\Exception $e) {
            return ServiceResponse::error("Error al generar el layout: " . $e->getMessage(), 500);
        }
    }

    /**
     * Motor de Maquetación: BBVA Net Cash (Fijo 130 caracteres)
     * @private
     */
    private function buildBbvaLayout(array $invoices): string
    {
        $content = "";
        $consecutivo = 1;

        foreach ($invoices as $invoice) {
            $beneficiario = $this->sanitizeText($invoice['proveedor_nombre']);
            $beneficiario = substr(str_pad($beneficiario, 40, " ", STR_PAD_RIGHT), 0, 40);

            $montoCentavos = (int) round($invoice['monto_total'] * 100);
            $montoFormateado = str_pad((string)$montoCentavos, 15, "0", STR_PAD_LEFT);

            $clabe = str_pad(trim($invoice['cuenta_clabe']), 18, "0", STR_PAD_LEFT);
            $referenciaNum = str_pad((string)$consecutivo, 7, "0", STR_PAD_LEFT);

            $concepto = "PAGO FACTURA " . $invoice['serie_folio'];
            $concepto = substr(str_pad($this->sanitizeText($concepto), 40, " ", STR_PAD_RIGHT), 0, 40);

            // Cuenta Retiro (10) + CLABE (18) + Monto (15) + Beneficiario (40) + Referencia (7) + Concepto (40)
            $content .= self::CUENTA_RETIRO_BBVA . $clabe . $montoFormateado . $beneficiario . $referenciaNum . $concepto . "\r\n";
            $consecutivo++;
        }
        return $content;
    }

    /**
     * Motor de Maquetación: BANORTE Conexión Empresarial (Fijo 150 caracteres)
     * @private
     */
    private function buildBanorteLayout(array $invoices): string
    {
        $content = "";
        $consecutivo = 1;

        foreach ($invoices as $invoice) {
            // Banorte requiere: Operación (01=SPEI), Cuenta de Retiro (10), CLABE (18), Monto con decimal flotante (15), Nombre (40), Referencia (10)
            $clabe = str_pad(trim($invoice['cuenta_clabe']), 18, "0", STR_PAD_LEFT);
            
            // Banorte acepta el monto con punto decimal de forma directa: ej. 000000001250.50
            $montoFormateado = str_pad(number_format((int)$invoice['monto_total'], 2, '.', ''), 15, "0", STR_PAD_LEFT);
            
            $beneficiario = substr(str_pad($this->sanitizeText($invoice['proveedor_nombre']), 40, " ", STR_PAD_RIGHT), 0, 40);
            $concepto = substr(str_pad($this->sanitizeText("PAGO OC " . $invoice['serie_folio']), 30, " ", STR_PAD_RIGHT), 0, 30);
            $refNumerica = str_pad((string)$consecutivo, 10, "0", STR_PAD_LEFT);

            // SPEI (2) + Cuenta Retiro (10) + CLABE (18) + Monto (15) + Beneficiario (40) + Referencia (10) + Concepto (30) = 125 caracteres
            $content .= "01" . self::CUENTA_RETIRO_BANORTE . $clabe . $montoFormateado . $beneficiario . $refNumerica . $concepto . "\r\n";
            $consecutivo++;
        }
        return $content;
    }

    /**
     * Limpia cadenas de texto de acentos y caracteres especiales no válidos para portales bancarios.
     * @private
     */
    private function sanitizeText(string $text): string
    {
        $unwanted = [
            'Á'=>'A','É'=>'E','Í'=>'I','Ó'=>'O','Ú'=>'U','Ñ'=>'N','ü'=>'u','Ü'=>'U',
            'á'=>'a','é'=>'e','í'=>'i','ó'=>'o','ú'=>'u','ñ'=>'n','í'=>'i','ó'=>'o'
        ];
        $clean = strtr($text, $unwanted);
        $clean = preg_replace('/[^A-Za-z0-9 ]/', '', $clean);
        return strtoupper(trim($clean));
    }
}