<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <style>
        /* MISMO CSS PARA UNIFICACIÓN */
        @page { margin: 0.8cm; }
        body { font-family: 'Helvetica', 'Arial', sans-serif; font-size: 8.5pt; line-height: 1.15; color: #333; }
        .watermark { position: fixed; top: 30%; left: 0; width: 100%; transform: rotate(-45deg); opacity: 0.12; font-size: 65pt; font-weight: bold; text-align: center; color: #0ab39c; z-index: -1000; }
        .header-table { width: 100%; border-collapse: collapse; margin-bottom: 15px; }
        .company-title { font-size: 14pt; font-weight: bold; color: #000; }
        .doc-title { font-size: 11pt; font-weight: bold; background: #405189; color: #fff; padding: 5px; text-align: center; }
        .info-td { vertical-align: top; width: 50%; border: 1px solid #eee; padding: 8px; }
        .label { font-weight: bold; color: #555; text-transform: uppercase; font-size: 7.5pt; }
        .items-table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        .items-table th { background: #405189; color: #fff; padding: 6px; font-size: 7.5pt; text-transform: uppercase; }
        .items-table td { padding: 6px; border: 1px solid #ccc; vertical-align: middle; }
        .footer-container { margin-top: 20px; }
        .justificacion-box { width: 65%; float: left; border: 1px solid #ccc; padding: 8px; min-height: 90px; }
        .totals-box { width: 32%; float: right; }
        .totals-table { width: 100%; border-collapse: collapse; }
        .totals-table td { padding: 3px 5px; border-bottom: 1px solid #eee; }
        .total-row { font-weight: bold; font-size: 10pt; background: #f9f9f9; }
        .amount-letters { margin-top: 10px; padding: 8px; font-weight: bold; text-transform: uppercase; font-size: 8pt; background-color: #fcfcfc; border-top: 1px solid #eee; }
        .signature-table { width: 100%; margin-top: 40px; text-align: center; }
        .signature-line { border-top: 1px solid #000; width: 80%; margin: 0 auto; padding-top: 5px; }
        .text-right { text-align: right; }
        .bold { font-weight: bold; }
        .clearfix { clear: both; }
    </style>
</head>
<body>
    <!-- Encabezado de OC -->
    <table class="header-table">
        <tr>
            <td width="60%">
                <div class="company-title">LDR SOLUTIONS</div>
                <div style="font-size: 7pt; color: #666;">DOMICILIO FISCAL: CARRETERA LA ESCONDIDA #488, LAGOS DE MORENO JALISCO<br>RFC: LDR123456ABC</div>
            </td>
            <td width="40%">
                <div class="doc-title">ORDEN DE COMPRA</div>
                <div class="text-right" style="margin-top: 5px;">
                    <span class="label">Folio OC:</span> <span style="font-size: 12pt; color: #405189;" class="bold"><?= str_pad((string)$data['idcompra'], 8, '0', STR_PAD_LEFT) ?></span><br>
                    <span class="label">Fecha OC:</span> <?= date('d/m/Y H:i', strtotime($data['created_at'])) ?>
                </div>
            </td>
        </tr>
    </table>

    <table class="header-table">
        <tr>
            <td class="info-td">
                <span class="label">Proveedor:</span><br><span class="bold"><?= htmlspecialchars($data['proveedor_nombre']) ?></span><br>
                <span class="label">RFC:</span> <?= $data['proveedor_rfc'] ?? '---' ?><br>
                <span class="label">Atención:</span> <?= $data['proveedor_contacto'] ?? 'Depto. Ventas' ?>
            </td>
            <td class="info-td">
                <span class="label">Enviar a:</span><br><span class="bold"><?= htmlspecialchars($data['almacen_nombre']) ?></span><br>
                <span class="label">Condiciones:</span> <?= $data['moneda'] ?> - <?= $data['observaciones'] ?><br>
                <span class="label">Req. Origen:</span> #<?= $data['requisicionid'] ?>
            </td>
        </tr>
    </table>

    <table class="items-table">
        <thead>
            <tr>
                <th width="8%">Cant.</th>
                <th width="8%">Unidad</th>
                <th width="15%">SKU</th>
                <th width="35%">Descripción</th>
                <th width="8%">% Desc.</th>
                <th width="12%" class="text-right">Precio Real</th>
                <th width="14%" class="text-right">Importe</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($data['items'] as $item): ?>
            <tr>
                <td align="center"><?= number_format($item['cantidad'], 2) ?></td>
                <td align="center"><?= $item['unidad_salida'] ?? 'PZA' ?></td>
                <td class="bold"><?= $item['cve_articulo'] ?></td>
                <td><?= htmlspecialchars($item['descripcion']) ?></td>
                <td align="center"><?= number_format($item['porcentaje_descuento'] ?? 0, 2) ?></td>
                <td align="right"><?= number_format($item['costo_unitario'], 4) ?></td>
                <td align="right" class="bold"><?= number_format($item['subtotal_partida'], 2) ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <div class="footer-container">
        <div class="justificacion-box">
            <span class="label">Observaciones de Entrega:</span><br>
            <div style="font-size: 8pt;"><?= nl2br(htmlspecialchars($data['observaciones'])) ?></div>
        </div>
        <div class="totals-box">
            <table class="totals-table">
                <tr>
                    <td class="label">Subtotal:</td>
                    <!-- Mostramos el subtotal antes de descuentos -->
                    <td align="right">$ <?= number_format($data['subtotal_bruto'], 2) ?></td>
                </tr>
                <tr>
                    <td class="label">Descuentos:</td>
                    <!-- Mostramos la suma de descuentos en rojo o con paréntesis -->
                    <td align="right" style="color: #d00;">$ -<?= number_format($data['total_descuento'], 2) ?></td>
                </tr>
                <tr>
                    <td class="label">I.V.A. (16%):</td>
                    <td align="right">$ <?= number_format($data['iva'], 2) ?></td>
                </tr>
                <tr class="total-row">
                    <td class="label">TOTAL OC:</td>
                    <td align="right">$ <?= number_format($data['total'], 2) ?></td>
                </tr>
            </table>
        </div>
        <div class="clearfix"></div>
    </div>
    <div class="amount-letters">SON: <?= $data['monto_letras'] ?></div>

    <table class="signature-table">
        <tr>
            <td width="50%"><div class="signature-line"><?= $userContext['nombre'] ?></div><span class="label">Comprador Autorizado</span></td>
            <td width="50%"><div class="signature-line">&nbsp;</div><span class="label">Firma Proveedor (Aceptación)</span></td>
        </tr>
    </table>
</body>
</html>