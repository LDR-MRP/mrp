<?php

class Com_compraService{
    protected $compraModel;

    protected $compraStoreRequest;

    protected $requisitionModel;

    protected $currencyModel;

    public function __construct()
    {
        $this->requisitionModel = new Com_requisicionModel;
        $this->compraModel = new Com_compraModel;
        $this->currencyModel = new Inv_monedaModel;
    }

    public function store(string $data): ServiceResponse
    {
        $db = $this->compraModel->getConexion();
        $db->beginTransaction();

        try {
            if(!($userId = $_SESSION['idUser'])) {
                throw new \Exception("No hay una sesión de usuario activa.");
            }

            $data = json_decode($data, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new Exception('El cuerpo de la petición no es un JSON válido.', 400);
            }

            // request
            $this->compraStoreRequest = new Com_compraStoreRequest($data);
            $this->compraStoreRequest->requisitionModel = $this->requisitionModel;
            $this->compraStoreRequest->currencyModel = $this->currencyModel;
            $this->compraStoreRequest->validate();
            $validated = $this->compraStoreRequest->all();

            // models
            $requisition = $this->requisitionModel->requisition($validated['requisicionid']);
            $currency = current($this->currencyModel->all(['idmoneda' => $validated['monedaid']]));

            // prepare data
            $code = "OC-" . date('Y') . "-" . str_pad($reqId = $requisition['idrequisicion'], 4, '0', STR_PAD_LEFT);
            $requisition['iva'] = $iva = $validated['iva'] ? .16 : 0;
            $requisition['total'] = $iva ? $requisition['monto_estimado'] + ($requisition['monto_estimado'] * $iva) : $requisition['monto_estimado'];

            // data insert
            $POId = $this->compraModel->create(
                array_merge($validated, $requisition, $currency),
                $userId
            );

            $this->requisitionModel->changeStatus($reqId, strtolower(Com_requisicionModel::ESTATUS_EN_COMPRA), $userId);
       
            $this->requisitionModel->logAudit($reqId, Com_requisicionModel::ESTATUS_EN_COMPRA, 'La PO se ha generado exitosamente', $userId);

            $db->commit();

            // response
            return ServiceResponse::success(
                [
                    'compraid' => $POId,
                    'requisicionid' => $POId,
                    'codigo' => $code,
                ],
                'Órden de compra generada existosamente.',
                201
            );

        } catch (InvalidArgumentException $e) {
            return ServiceResponse::validation($e->getMessage());
        } catch (Exception $e) {
            if (isset($db)) {
                $db->rollBack();
            }

            return ServiceResponse::error(
                message: $e->getMessage(),
                code: is_int($e->getCode()) ? $e->getCode() : 500
            );
        }
    }

    /**
     * Generates a Premium PDF for the Purchase Order
     * @param int $idPurchase
     * @param string $outputMode 'stream' (download) or 'string' (raw data for saving)
     * @return mixed
     * @throws Exception
     */
    public function generatePremiumOCPDF(int $idPurchase, string $outputMode = 'stream')
    {
        // 1.Increase memory limit for PDF generation (heavy process)
        ini_set('memory_limit', '256M');
        set_time_limit(60);

        // 2. Fetch all necessary data
        $data['oc'] = $this->compraModel->findByCriteria($idPurchase);
        $data['items'] = $this->requisitionModel->details($idPurchase);

        if (!$data['oc']) {
            throw new Exception("Purchase Order #$idPurchase not found.");
        }

        // 3. Get Corporate Data (Crucial for the logo)
        // You need a method in a general model to get company config
        // Make sure getCompanyLogoAsBase64() returns something like "data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAA..."
        //$data['empresa']['logo_base64'] = $this->generalModel->getCompanyLogoAsBase64() ?? null;

        // 4. Configure Dompdf for premium rendering
        $options = new \Dompdf\Options();
        $options->set('isHtml5ParserEnabled', true); // Better HTML compliance
        $options->set('isPhpEnabled', false); // Security best practice
        $options->set('defaultFont', 'Helvetica'); // Clean, professional font
        $options->set('dpi', 120); // Sharper images and text
        // isRemoteEnabled allows loading images from URLs, but Base64 is safer.
        // $options->set('isRemoteEnabled', true);

        $dompdf = new \Dompdf\Dompdf($options);

        // 5. Load View into Buffer
        ob_start();
        // Adjust the path to where you saved the premium template
        require_once(__DIR__."/../Views/Com_compras/purchase_order.php");
        $html = ob_get_clean();

        // 6. Render PDF
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        // 7. Output
        $filename = "OC_" . $data['oc']['codigo_oc'] . "_" . date('Ymd') . ".pdf";

        if ($outputMode === 'stream') {
            // Force download in the browser
            $dompdf->stream($filename, ["Attachment" => true]);
            exit; // Stop script execution after streaming
        } else {
            // Return raw PDF data (e.g., to save it to disk or email attachment)
            return $dompdf->output();
        }
    }
}