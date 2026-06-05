<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <style>
        /* Mismos estilos compartidos para consistencia visual */
        @page { margin: 0.8cm; }
        body { font-family: 'Helvetica', 'Arial', sans-serif; font-size: 8.5pt; line-height: 1.15; color: #333; }
        .watermark { position: fixed; top: 30%; left: 0; width: 100%; transform: rotate(-45deg); opacity: 0.12; font-size: 65pt; font-weight: bold; text-align: center; color: <?= $watermark['color'] ?? '#000' ?>; z-index: -1000; }
        .header-table { width: 100%; border-collapse: collapse; margin-bottom: 15px; }
        .company-title { font-size: 14pt; font-weight: bold; color: #000; }
        .doc-title { font-size: 11pt; font-weight: bold; background: #f0f0f0; padding: 5px; text-align: center; border: 1px solid #ccc; }
        .info-td { vertical-align: top; width: 50%; border: 1px solid #eee; padding: 8px; }
        .label { font-weight: bold; color: #555; text-transform: uppercase; font-size: 7.5pt; }
        .items-table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        .items-table th { background: #444; color: #fff; padding: 6px; font-size: 7.5pt; text-transform: uppercase; }
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
    <?php if (isset($watermark)): ?><div class="watermark"><?= $watermark['text'] ?></div><?php endif; ?>

    <table class="header-table">
        <tr>
            <td width="60%">
                <div class="company-title">LDR SOLUTIONS</div>
                <div style="font-size: 7pt; color: #666;">CARRETERA LA ESCONDIDA #488, LAGOS DE MORENO JALISCO<br>REF: <?= htmlspecialchars($data['titulo']) ?></div>
            </td>
            <td width="40%">
                <div class="doc-title">REQUISICIÓN DE COMPRA</div>
                <div class="text-right" style="margin-top: 5px;">
                    <span class="label">Folio:</span> <span style="font-size: 12pt; color: red;" class="bold"><?= str_pad((string)$data['idrequisicion'], 8, '0', STR_PAD_LEFT) ?></span><br>
                    <span class="label">Fecha Emisión:</span> <?= date('d/m/Y H:i', strtotime($data['fecha'])) ?>
                </div>
            </td>
        </tr>
    </table>

    <!-- Bloque de Información General -->
    <table class="header-table">
        <tr>
            <td class="info-td">
                <span class="label">Solicitante:</span><br>
                <?= htmlspecialchars($data['solicitante_nombre']) ?><br>
                <span class="label">Departamento:</span><br>
                <?= htmlspecialchars($data['departamento_nombre']) ?>
            </td>
            <td class="info-td">
                <span class="label">Centro de Costos:</span><br>
                <?= $data['centro_costo'] ?? 'N/A' ?><br>
                <span class="label">Fecha Requerida:</span><br>
                <span class="bold"><?= date('d/m/Y', strtotime($data['fecha_requerida'])) ?></span> 
                &nbsp;&nbsp; | &nbsp;&nbsp; <span class="label">Prioridad:</span> <?= strtoupper($data['prioridad']) ?>
            </td>
        </tr>
    </table>

    <table class="items-table">
        <thead>
            <tr>
                <th width="8%">Cant.</th>
                <th width="8%">Unidad</th>
                <th width="15%">Producto</th>
                <th width="40%">Descripción</th>
                <th width="14%" class="text-right">Costo Est.</th>
                <th width="15%" class="text-right">Importe</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($data['items'] as $item): 
                $imp = (float)$item['cantidad'] * (float)$item['precio_unitario_estimado']; ?>
            <tr>
                <td align="center"><?= number_format($item['cantidad'], 2) ?></td>
                <td align="center"><?= $item['unidad_salida'] ?? 'PZA' ?></td>
                <td class="bold"><?= $item['cve_articulo'] ?></td>
                <td><?= htmlspecialchars($item['descripcion']) ?></td>
                <td align="right"><?= number_format($item['precio_unitario_estimado'], 4) ?></td>
                <td align="right" class="bold"><?= number_format($imp, 2) ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <div class="footer-container">
        <div class="justificacion-box">
            <span class="label">Justificación:</span><br><div style="font-size: 8pt;"><?= nl2br(htmlspecialchars($data['justificacion'])) ?></div>
        </div>
        <div class="totals-box">
            <table class="totals-table">
                <tr><td class="label">Subtotal:</td><td align="right">$ <?= number_format($data['monto_estimado'], 2) ?></td></tr>
                <tr><td class="label">I.V.A. (16%):</td><td align="right">$ <?= number_format($data['monto_estimado'] * 0.16, 2) ?></td></tr>
                <tr class="total-row"><td class="label">Total Est.:</td><td align="right">$ <?= number_format($data['monto_estimado'] * 1.16, 2) ?></td></tr>
            </table>
        </div>
        <div class="clearfix"></div>
    </div>
    <div class="amount-letters">SON: <?= $data['monto_letras'] ?></div>

    <table class="signature-table">
        <tr>
            <td width="50%"><div class="signature-line"><?= $data['solicitante_nombre'] ?></div><span class="label">Solicita</span></td>
            <td width="50%"><div class="signature-line">&nbsp;</div><span class="label">Autorización</span></td>
        </tr>
    </table>
</body>
</html>