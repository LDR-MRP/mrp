<?php
$logoPath = realpath(__DIR__ . '/../../Assets/images/ldr_negro.png');
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Hoja de Entrega de Unidad</title>
    <style>
        body {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 11px;
            color: #333;
            margin: 0;
            padding: 0;
        }
        .header {
            width: 100%;
            border-bottom: 2px solid #fd7e14;
            padding-bottom: 10px;
            margin-bottom: 20px;
        }
        .header table {
            width: 100%;
            border-collapse: collapse;
        }
        .logo {
            width: 150px;
        }
        .title-box {
            text-align: right;
        }
        .title-box h1 {
            color: #fd7e14;
            margin: 0;
            font-size: 18px;
            text-transform: uppercase;
        }
        .title-box p {
            margin: 2px 0;
            font-weight: bold;
            font-size: 12px;
        }
        .section-title {
            background-color: #f1f5f9;
            color: #0f172a;
            padding: 4px 8px;
            font-weight: bold;
            font-size: 11px;
            border-left: 4px solid #fd7e14;
            margin-top: 10px;
            margin-bottom: 6px;
            text-transform: uppercase;
        }
        .data-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
        }
        .data-table th {
            background-color: #f8fafc;
            border: 1px solid #e2e8f0;
            padding: 4px 6px;
            text-align: left;
            font-weight: bold;
            color: #475569;
            font-size: 10px;
        }
        .data-table td {
            border: 1px solid #e2e8f0;
            padding: 4px 6px;
            color: #0f172a;
            font-size: 10px;
        }
        .table-tramos th {
            background-color: #f1f5f9;
            color: #334155;
            font-size: 9.5px;
            border: 1px solid #cbd5e1;
            padding: 4px 5px;
        }
        .table-tramos td {
            font-size: 9.5px;
            border: 1px solid #e2e8f0;
            padding: 4px 5px;
        }
        .signatures {
            width: 100%;
            margin-top: 20px;
            text-align: center;
        }
        .signatures table {
            width: 100%;
            border-collapse: collapse;
        }
        .signatures td {
            width: 50%;
            padding: 20px;
            vertical-align: bottom;
        }
        .sign-line {
            border-top: 1px solid #000;
            width: 80%;
            margin: 0 auto;
            padding-top: 5px;
            font-weight: bold;
        }
        .legend {
            font-size: 9px;
            color: #64748b;
            text-align: justify;
            margin-top: 20px;
            border-top: 1px dashed #cbd5e1;
            padding-top: 10px;
        }
        .badge {
            background-color: #e0f2fe;
            color: #0284c7;
            padding: 3px 6px;
            border-radius: 4px;
            font-weight: bold;
        }
    </style>
</head>
<body>

<div class="header">
    <table>
        <tr>
            <td style="width: 50%;">
                <?php if (!empty($logoPath) && file_exists($logoPath)): ?>
                    <img src="<?= $logoPath ?>" style="width: 150px;" alt="LDR Solutions">
                <?php else: ?>
                    <h2 style="color: #fd7e14; margin: 0; font-size: 16px;">LDR SOLUTIONS</h2>
                <?php endif; ?>
            </td>
            <td class="title-box" style="width: 50%;">
                <h1>HOJA DE ENTREGA / TRASPASO</h1>
                <p>Folio Envío: <?= htmlspecialchars($detalle['folio'] ?? 'N/A') ?></p>
                <p style="color: #64748b; font-weight: normal;">Fecha de Expedición: <?= date('d/m/Y H:i') ?></p>
            </td>
        </tr>
    </table>
</div>

<div class="section-title">Información de la Unidad</div>
<table class="data-table">
    <tr>
        <th>VIN (Número de Serie)</th>
        <td style="font-weight: bold; color: #0369a1;"><?= htmlspecialchars($detalle['vin'] ?? 'N/A') ?></td>
        <th>Modelo / Color</th>
        <td><?= htmlspecialchars($detalle['modelo'] ?? 'N/A') ?> / <?= htmlspecialchars($detalle['color'] ?? 'N/A') ?></td>
    </tr>
    <tr>
        <th>Segmento</th>
        <td><span class="badge"><?= htmlspecialchars($detalle['segmento'] ?? 'General') ?></span></td>
        <th>Placas Madrina</th>
        <td><?= htmlspecialchars($detalle['madrina_placas'] ?? 'N/A') ?></td>
    </tr>
</table>

<div class="section-title">Detalle del Traslado (Real)</div>
<table class="data-table">
    <tr>
        <th>Motivo del Envío</th>
        <td colspan="3"><?= htmlspecialchars($detalle['motivo_nombre'] ?? 'Traslado Logístico') ?></td>
    </tr>
    <tr>
        <th>Origen (Punto de Salida)</th>
        <td><?= htmlspecialchars($detalle['subida_nombre'] ?? $detalle['origen_global'] ?? 'N/A') ?></td>
        <th>Destino (A quien se entregó)</th>
        <td><?= htmlspecialchars($detalle['bajada_nombre'] ?? $detalle['destino_global'] ?? 'N/A') ?></td>
    </tr>
    <tr>
        <th>Fecha Salida (Real)</th>
        <td><?= !empty($detalle['fecha_tentativa_envio']) ? date('d/m/Y H:i', strtotime($detalle['fecha_tentativa_envio'])) : 'N/A' ?></td>
        <th>Km Estimados / Reales</th>
        <td><?= htmlspecialchars($detalle['km_total'] ?? '0') ?> Km</td>
    </tr>
    <tr>
        <th>Transportista / Proveedor</th>
        <td><?= htmlspecialchars($detalle['proveedor_nombre'] ?? 'N/A') ?></td>
        <th>Operador / Chofer</th>
        <td><?= htmlspecialchars($detalle['chofer_nombre'] ?? 'N/A') ?></td>
    </tr>
</table>

<div class="section-title">Valores y Costos (Uso Interno)</div>
<table class="data-table">
    <tr>
        <th style="width: 25%;">Segmento Tarifario</th>
        <td style="width: 25%;"><span class="badge"><?= htmlspecialchars($detalle['segmento'] ?? 'N/A') ?></span></td>
        <th style="width: 25%;">Tarifa Base Referencia</th>
        <td style="width: 25%;">$<?= number_format((float)($detalle['costo_base_tramo'] ?? 0), 2) ?> MXN / Km</td>
    </tr>
    <tr>
        <th>Distancia Recorrida (Unidad)</th>
        <td><?= number_format((float)($kmRecorridosTotal ?? 0), 1) ?> Km</td>
        <th>Costo Total Asignado (Unidad)</th>
        <td style="font-weight: bold; color: #166534; font-size: 12px;">$<?= number_format((float)($detalle['costo_total_unitario'] ?? 0), 2) ?> MXN</td>
    </tr>
</table>

<div class="section-title">Desglose de Factoraje y Costos por Tramo Recorrido</div>
<p style="font-size: 9px; color: #64748b; margin: 0 0 6px 0;">
    El factoraje y costo se distribuyen dinámicamente según la cantidad de unidades que compartieron la madrina en cada tramo y el segmento vehicular:
</p>
<table class="data-table table-tramos" style="width: 100%; border-collapse: collapse; margin-bottom: 8px;">
    <thead>
        <tr>
            <th style="width: 5%; text-align: center;">#</th>
            <th style="width: 33%;">Tramo (Origen &rarr; Destino)</th>
            <th style="width: 11%; text-align: right;">Distancia</th>
            <th style="width: 13%; text-align: center;">Carga a Bordo</th>
            <th style="width: 11%; text-align: center;">Factoraje</th>
            <th style="width: 13%; text-align: right;">Costo Tramo</th>
            <th style="width: 14%; text-align: right;">Asignado VIN</th>
        </tr>
    </thead>
    <tbody>
        <?php if (!empty($tramosRecorridos)): ?>
            <?php foreach ($tramosRecorridos as $tr): ?>
                <tr>
                    <td style="text-align: center; color: #64748b;"><?= $tr['num'] ?></td>
                    <td><strong><?= htmlspecialchars($tr['origen']) ?></strong> &rarr; <?= htmlspecialchars($tr['destino']) ?></td>
                    <td style="text-align: right;"><?= number_format($tr['km'], 1) ?> km</td>
                    <td style="text-align: center; color: #0284c7; font-weight: bold;"><?= $tr['unidades_a_bordo'] ?> <?= $tr['unidades_a_bordo'] == 1 ? 'unidad' : 'unidades' ?></td>
                    <td style="text-align: center;">x<?= number_format($tr['factor'], 4) ?></td>
                    <td style="text-align: right;">$<?= number_format($tr['costo_tramo'], 2) ?></td>
                    <td style="text-align: right; font-weight: bold; color: #166534;">$<?= number_format($tr['cuota_unidad'], 2) ?></td>
                </tr>
            <?php endforeach; ?>
        <?php else: ?>
            <tr>
                <td colspan="7" style="text-align: center; color: #64748b;">No hay detalle de tramos registrado.</td>
            </tr>
        <?php endif; ?>
    </tbody>
    <tfoot>
        <tr style="background-color: #f8fafc; font-weight: bold;">
            <td colspan="2" style="text-align: right; color: #334155;">Totales de esta Unidad:</td>
            <td style="text-align: right; color: #334155;"><?= number_format((float)($kmRecorridosTotal ?? 0), 1) ?> km</td>
            <td colspan="3" style="text-align: right; color: #166534;">Costo Total Asignado:</td>
            <td style="text-align: right; color: #166534; font-size: 11px;">$<?= number_format((float)($detalle['costo_total_unitario'] ?? 0), 2) ?> MXN</td>
        </tr>
    </tfoot>
</table>

<div class="signatures">
    <table>
        <tr>
            <td>
                <div class="sign-line">Entregado Por (Operador / Madrina)</div>
                <p style="margin-top: 5px; color: #64748b;"><?= htmlspecialchars($detalle['chofer_nombre'] ?? '____________________') ?></p>
            </td>
            <td>
                <div class="sign-line">Recibido Por (Conformidad en Destino)</div>
                <p style="margin-top: 5px; color: #64748b;">Nombre, Firma y Sello</p>
            </td>
        </tr>
    </table>
</div>

<div class="legend">
    <strong>LEYENDA DE ENTREGA Y CONFORMIDAD:</strong> Por medio de la presente, el receptor declara haber recibido la unidad descrita en este documento a su entera satisfacción, reconociendo que las características (VIN, Modelo, Color) coinciden con lo estipulado. Se acepta que el vehículo fue transportado y entregado de acuerdo a los términos y condiciones de servicio establecidos por Logística del Río (LDR). Cualquier anomalía, faltante o daño deberá ser reportado inmediatamente y anexado como evidencia fotográfica y documental en el acta de recepción correspondiente. Al firmar este documento, se libera de responsabilidad de tránsito y custodia al transportista designado, asumiendo el receptor el resguardo físico y legal de la unidad a partir de la fecha y hora indicadas.
</div>

</body>
</html>
