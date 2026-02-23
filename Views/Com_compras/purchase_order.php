<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Orden de Compra - LDR Solutions</title>
    <style>
        /** * PREMIUM CSS RESET & TYPOGRAPHY
         * Dompdf prefers built-in fonts like Helvetica.
         */
        @page { margin: 0px; }
        body { 
            font-family: 'Helvetica', Arial, sans-serif; 
            margin: 30px; /* Creates the white border around the page */
            color: #333; 
            line-height: 1.4;
        }
        
        /** COLORS PALETTE **/
        .text-primary { color: #f05e29; } /* LDR Corporate Deep Blue */
        .bg-primary { background-color: #f05e29; color: white; }
        .bg-light { background-color: #f5f7fa; }
        .border-light { border: 1px solid #e9ecef; }

        /** UTILITIES **/
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .text-uppercase { text-transform: uppercase; }
        .font-bold { font-weight: bold; }
        .no-padding { padding: 0; }
        .w-100 { width: 100%; }
        .mb-20 { margin-bottom: 20px; }
        .mb-40 { margin-bottom: 40px; }

        /** HEADER SECTION **/
        .header-table { width: 100%; border-collapse: collapse; margin-bottom: 30px; border-bottom: 2px solid #f05e29; padding-bottom: 20px; }
        .logo-img { max-height: 80px; max-width: 200px; } /* Adjust based on actual LDR logo aspect ratio */
        .doc-title { font-size: 24px; font-weight: bold; color: #f05e29; margin: 0; }
        .doc-subtitle { font-size: 14px; color: #666; }

        /** INFO BLOCKS SECTION (Vendor & Ship To) **/
        .info-table { width: 100%; border-spacing: 15px 0; border-collapse: separate; margin-bottom: 30px; }
        .info-box { 
            background-color: #f9fafb; 
            border: 1px solid #e2e8f0; 
            padding: 15px; 
            vertical-align: top; 
            width: 48%;
            border-radius: 4px; /* Dompdf supports basic border-radius */
        }
        .info-label { font-size: 10px; color: #f05e29; font-weight: bold; letter-spacing: 1px; margin-bottom: 10px; display: block; }
        .info-content { font-size: 12px; color: #444; }

        /** ITEMS TABLE SECTION **/
        .items-table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        .items-table th { 
            background-color: #f05e29; /* Corporate Blue Header */
            color: white; 
            padding: 12px 8px; 
            text-align: left; 
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .items-table td { 
            padding: 12px 8px; 
            border-bottom: 1px solid #e9ecef; 
            font-size: 12px;
            vertical-align: middle;
        }
        /* Zebra striping for professional look */
        .items-table tbody tr:nth-child(even) { background-color: #f8f9fa; }

        /** TOTALS SECTION **/
        .totals-container { width: 100%; margin-top: 20px; }
        .totals-table { width: 40%; margin-left: auto; border-collapse: collapse; }
        .totals-table td { padding: 8px; font-size: 12px; }
        .totals-label { text-align: right; color: #666; font-weight: bold; }
        .totals-value { text-align: right; font-family: 'Helvetica', monospace; }
        .grand-total-row { 
            background-color: #f05e29; 
            color: white; 
            font-size: 14px; 
            font-weight: bold;
            border-top: 2px solid #f05e29;
        }
        .grand-total-row .totals-label { color: white; }

        /** FOOTER SECTION **/
        .footer { 
            position: fixed; 
            bottom: 30px; 
            left: 30px; 
            right: 30px;
            text-align: center; 
            font-size: 10px; 
            color: #999; 
            border-top: 1px solid #eee; 
            padding-top: 15px; 
        }
    </style>
</head>
<body>
    <table class="header-table">
        <tr>
            <td style="vertical-align: middle;">
                <img src="<?= $data['empresa']['logo_base64'] ?? '' ?>" alt="LDR Solutions Logo" class="logo-img">
            </td>
            <td style="text-align: right; vertical-align: middle;">
                <h1 class="doc-title">ORDEN DE COMPRA</h1>
                <p class="doc-subtitle">Folio: <strong>#<?= $data['oc']['codigo_oc'] ?></strong></p>
            </td>
        </tr>
    </table>

    <table class="info-table">
        <tr>
            <td class="info-box">
                <span class="info-label text-uppercase">Proveedor Asignado</span>
                <div class="info-content">
                    <strong style="font-size: 14px;"><?= $data['oc']['proveedor_razon_social'] ?></strong><br>
                    RFC: <?= $data['oc']['rfc'] ?><br>
                    Contacto: <?= $data['oc']['contacto'] ?><br>
                    Tel: <?= $data['oc']['telefono'] ?><br>
                    Email: <?= $data['oc']['correo_electronico'] ?>
                </div>
            </td>
            <td class="info-box">
                <span class="info-label text-uppercase">Detalles de la Orden & Envío</span>
                <div class="info-content">
                    <strong><?= $data['oc']['cve_almacen'] ?> - <?= $data['oc']['descripcion'] ?></strong><br>
                    Dirección de Entrega: <?= $data['oc']['direccion'] ?>.<br>
                    <br>
                    <table class="w-100 no-padding">
                        <tr>
                            <td><strong>Fecha Emisión:</strong></td>
                            <td class="text-right"><?= date('d/m/Y', strtotime($data['oc']['fecha_requerida'])) ?></td>
                        </tr>
                        <tr>
                            <td><strong>Fecha Entrega:</strong></td>
                            <td class="text-right"><?= date('d/m/Y', strtotime($data['oc']['fecha_requerida'])) ?></td>
                        </tr>
                        <tr>
                            <td><strong>Condición Pago:</strong></td>
                            <td class="text-right"><?= $data['oc']['dias_credito'] ?> Días</td>
                        </tr>
                    </table>
                </div>
            </td>
        </tr>
    </table>

    <table class="items-table">
        <thead>
            <tr>
                <th style="width: 5%;">#</th>
                <th style="width: 45%;">Descripción / SKU</th>
                <th style="width: 15%; text-align: center;">Cantidad</th>
                <th style="width: 15%; text-align: right;">P. Unitario</th>
                <th style="width: 20%; text-align: right;">Importe</th>
            </tr>
        </thead>
        <tbody>
            <?php $counter = 1; foreach ($data['items'] as $item): ?>
            <tr>
                <td class="text-center" style="color: #999;"><?= str_pad($counter++, 2, '0', STR_PAD_LEFT) ?></td>
                <td>
                    <strong><?= $item['descripcion'] ?></strong><br>
                    <small style="color: #666;">SKU: <?= $item['sku'] ?? 'N/A' ?> | U.M.: <?= $item['unidad_medida'] ?></small>
                </td>
                <td class="text-center" style="font-weight: bold;"><?= number_format($item['cantidad'], 2) ?></td>
                <td class="text-right font-monospace">$<?= number_format($item['precio_unitario'], 2) ?></td>
                <td class="text-right font-bold font-monospace">$<?= number_format($item['subtotal'], 2) ?></td>
            </tr>
            <?php endforeach; ?>
             <?php if(count($data['items']) < 5): ?>
                <tr><td colspan="5" style="height: 30px;"></td></tr>
            <?php endif; ?>
        </tbody>
    </table>

    <div class="totals-container">
        <table class="totals-table">
            <tr>
                <td class="totals-label">Subtotal:</td>
                <td class="totals-value">$<?= number_format($data['oc']['subtotal'], 2) ?></td>
            </tr>
            <tr>
                <td class="totals-label">I.V.A. (16%):</td>
                <td class="totals-value">$<?= number_format($data['oc']['iva'], 2) ?></td>
            </tr>
            <tr class="grand-total-row">
                <td class="totals-label">TOTAL A PAGAR:</td>
                <td class="totals-value">$<?= number_format($data['oc']['total'], 2) ?></td>
            </tr>
        </table>
    </div>

    <?php if(!empty($data['oc']['notas'])): ?>
    <div style="margin-top: 40px; background-color: #fff3cd; padding: 15px; border-left: 4px solid #ffc107; font-size: 11px; color: #856404;">
        <strong>Instrucciones Especiales:</strong><br>
        <?= nl2br($data['oc']['observaciones']) ?>
    </div>
    <?php endif; ?>

    <div class="footer">
        LDR Solutions S.A. de C.V. | RFC: LDRX010101000 | Av. Tecnológico #500, México.<br>
        Este documento es una representación impresa de una Orden de Compra Digital.
        <div style="margin-top: 5px; color: #f05e29;">www.ldrsolutions.com</div>
    </div>
</body>
</html>