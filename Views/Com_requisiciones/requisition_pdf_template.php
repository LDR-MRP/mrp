<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <style>
        @page { margin: 0.8cm; }
        body { font-family: 'Helvetica', 'Arial', sans-serif; font-size: 8.5pt; line-height: 1.15; color: #333; }
        
        /* Watermark dinámico */
        .watermark {
            position: fixed; top: 30%; left: 0; width: 100%;
            transform: rotate(-45deg); opacity: 0.12;
            font-size: 65pt; font-weight: bold; text-align: center;
            color: <?= $watermark['color'] ?? '#000' ?>; z-index: -1000;
        }

        /* Layout */
        .header-table { width: 100%; border-collapse: collapse; margin-bottom: 15px; }
        .company-title { font-size: 14pt; font-weight: bold; color: #000; margin-bottom: 2px; }
        .doc-title { font-size: 11pt; font-weight: bold; background: #f0f0f0; padding: 5px; text-align: center; border: 1px solid #ccc; }
        
        .info-td { vertical-align: top; width: 50%; border: 1px solid #eee; padding: 8px; }
        .label { font-weight: bold; color: #555; text-transform: uppercase; font-size: 7.5pt; }
        
        /* Grid de Partidas */
        .items-table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        .items-table th { 
            background: #444; color: #fff; padding: 6px; font-size: 7.5pt; 
            text-transform: uppercase; border: 1px solid #444;
        }
        .items-table td { padding: 6px; border: 1px solid #ccc; vertical-align: middle; }
        .sku-cell { font-weight: bold; font-family: 'Courier', monospace; }

        /* Totales y Justificación */
        .footer-container { margin-top: 20px; }
        .justificacion-box { 
            width: 65%; float: left; border: 1px solid #ccc; padding: 8px; min-height: 100px;
        }
        .totals-box { width: 32%; float: right; }
        .totals-table { width: 100%; border-collapse: collapse; }
        .totals-table td { padding: 3px 5px; border-bottom: 1px solid #eee; }
        .total-row { font-weight: bold; font-size: 10pt; background: #f9f9f9; }

        /* Monto en Letras */
        .amount-letters {
            margin-top: 10px;
            padding: 8px;
            font-weight: bold;
            text-transform: uppercase;
            font-size: 8pt;
            background-color: #fcfcfc;
            border-top: 1px solid #eee;
        }

        /* Firmas */
        .signature-table { width: 100%; margin-top: 40px; text-align: center; }
        .signature-line { border-top: 1px solid #000; width: 80%; margin: 0 auto; padding-top: 5px; }

        /* Helpers */
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .bold { font-weight: bold; }
        .clearfix { clear: both; }
    </style>
</head>
<body>

    <?php if (isset($watermark)): ?>
        <div class="watermark"><?= $watermark['text'] ?></div>
    <?php endif; ?>

    <!-- Encabezado -->
    <table class="header-table">
        <tr>
            <td width="60%">
                <div class="company-title">LDR SOLUTIONS</div>
                <div style="font-size: 7pt; color: #666;">
                    CARRETERA LA ESCONDIDA #488<br>
                    COL. MUNICIPIO LIBRE<br>
                    LAGOS DE MORENO JALISCO C.P.  47472<br>
                    REF: <?= htmlspecialchars($data['titulo']) ?>
                </div>
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

    <!-- Tabla de Partidas -->
    <table class="items-table">
        <thead>
            <tr>
                <th width="8%">Cant.</th>
                <th width="8%">Unidad</th>
                <th width="13%">Producto</th>
                <th width="30%">Descripción</th>
                <th width="10%">% Desc.</th>
                <th width="12%" class="text-right">Costo Est.</th>
                <th width="12%" class="text-right">Importe</th>
            </tr>
        </thead>
        <tbody>
            <?php 
            $subtotal = 0;
            foreach ($data['items'] as $item): 
                $importe = (float)$item['cantidad'] * (float)$item['precio_unitario_estimado'];
                $subtotal += $importe;
            ?>
            <tr>
                <td class="text-center"><?= number_format($item['cantidad'], 2) ?></td>
                <td class="text-center"><?= $item['unidad_salida'] ?? 'PZA' ?></td>
                <td class="sku-cell"><?= $item['cve_articulo'] ?></td>
                <td>
                    <?= htmlspecialchars($item['descripcion']) ?>
                    <?php if(!empty($item['notas'])): ?>
                        <br><small style="color: #555; font-style: italic;">Obs: <?= htmlspecialchars($item['notas']) ?></small>
                    <?php endif; ?>
                </td>
                <td class="text-center">0.00</td>
                <td class="text-right"><?= number_format($item['precio_unitario_estimado'], 4) ?></td>
                <td class="text-right bold"><?= number_format($importe, 2) ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <!-- Resumen y Justificación -->
    <div class="footer-container">
        <div class="justificacion-box">
            <span class="label">Justificación del Gasto:</span><br>
            <div style="font-size: 8pt; margin-top: 5px;"><?= nl2br(htmlspecialchars($data['justificacion'])) ?></div>
        </div>

        <div class="totals-box">
            <table class="totals-table">
                <tr>
                    <td class="label">Subtotal:</td>
                    <td class="text-right">$ <?= number_format($subtotal, 2) ?></td>
                </tr>
                <tr>
                    <td class="label">Descuento:</td>
                    <td class="text-right">$ 0.00</td>
                </tr>
                <tr>
                    <td class="label">Desc. Final:</td>
                    <td class="text-right">$ 0.00</td>
                </tr>
                <tr>
                    <td class="label">I.E.P.S.:</td>
                    <td class="text-right">$ 0.00</td>
                </tr>
                <tr>
                    <td class="label">Ret. ISR:</td>
                    <td class="text-right">$ 0.00</td>
                </tr>
                <tr>
                    <td class="label">Ret. IVA:</td>
                    <td class="text-right">$ 0.00</td>
                </tr>
                <tr>
                    <td class="label">I.V.A. (16%):</td>
                    <td class="text-right">$ <?= number_format($subtotal * 0.16, 2) ?></td>
                </tr>
                <tr>
                    <td class="label">Indirectos:</td>
                    <td class="text-right">$ 0.00</td>
                </tr>
                <tr class="total-row">
                    <td class="label">Total Est.:</td>
                    <td class="text-right">$ <?= number_format($subtotal * 1.16, 2) ?></td>
                </tr>
            </table>
        </div>
        <div class="clearfix"></div>
    </div>

    <!-- MONTO EN LETRAS -->
    <div class="amount-letters">
        SON: <?= $data['monto_letras'] ?>
    </div>

    <!-- Firmas (Opcional, basado en el template anterior) -->
    <table class="signature-table">
        <tr>
            <td width="50%">
                <div class="signature-line"><?= htmlspecialchars($data['solicitante_nombre']) ?></div>
                <span class="label">Solicitante</span>
            </td>
            <td width="50%">
                <div class="signature-line">&nbsp;</div>
                <span class="label">Autorización</span>
            </td>
        </tr>
    </table>

    <div style="position: fixed; bottom: 0; width: 100%; font-size: 7pt; color: #999; text-align: center; border-top: 0.5px solid #eee; padding-top: 5px;">
        Documento generado por <?= $userContext['nombre'] ?> | Identificador Único: <?= md5($data['idrequisicion'] . $data['fecha']) ?> | Página 1 de 1
    </div>

</body>
</html>