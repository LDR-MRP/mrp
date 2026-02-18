<?php


$payload = $data ?? ($d ?? []);


$info = $payload['info'] ?? [];
$k    = $payload['kpis'] ?? [];
$logo = $payload['logo'] ?? null;


$resumen         = $payload['resumen'] ?? [];
$encargados      = $payload['encargados'] ?? [];
$calidad         = $payload['calidad'] ?? [];
$costos_estacion = $payload['costos_estacion'] ?? [];
$costos_detalle  = $payload['costos_detalle'] ?? [];
$detalle         = $payload['detalle'] ?? [];
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <style>
    @page { margin: 85px 18px 35px 18px; }

    body{
      font-family: DejaVu Sans, Arial, sans-serif;
      font-size: 9px;
      color:#111;
    }

    header{
      position: fixed;
      top: -70px; left: 0; right: 0;
      height: 70px;
      border-bottom: 1px solid #ddd;
      padding-bottom: 6px;
    }

    footer{
      position: fixed;
      bottom: -25px; left: 0; right: 0;
      height: 25px;
      border-top: 1px solid #eee;
      color:#666;
      font-size: 8px;
      padding-top: 6px;
    }

    .pagenum:before{ content: counter(page); }

    .title{ font-size: 13px; font-weight: 700; margin:0; }
    .muted{ color:#666; font-size: 8px; }
    .logo{ height: 28px; }

    .kpi{
      width:100%;
      border-collapse: collapse;
      margin: 8px 0 12px;
    }
    .kpi td{ border:1px solid #ddd; padding:6px; }
    .k{ color:#666; font-size:8px; }
    .v{ font-size:12px; font-weight:700; margin-top:2px; }

    .section{ margin: 10px 0 14px; }
    .section h3{ font-size:10px; margin: 0 0 6px; }

    table{ width:100%; border-collapse: collapse; margin:0; }
    th,td{ border:1px solid #ddd; padding:4px; vertical-align: top; }
    th{ background:#DE936F; font-weight:700; }

    .right{ text-align: right; }
    .wraptext{ white-space: normal; }

    thead { display: table-header-group; }
    tfoot { display: table-footer-group; }
    tr { page-break-inside: avoid; }
    table { page-break-inside: auto; }

    .page-break{ page-break-before: always; }
  </style>
</head>

<body>

<header>
  <table style="width:100%; border-collapse:collapse;">
    <tr>
      <td style="width:60%; vertical-align:top;">
        <div class="title">Reporte KPI · Planeación </div>
        <div class="muted">
          OT: <b><?= htmlspecialchars($info['num_orden'] ?? '—') ?></b> ·
          Producto: <b><?= htmlspecialchars($info['producto'] ?? '—') ?></b>
        </div>
        <div class="muted">
          Supervisor: <b><?= htmlspecialchars($info['supervisor'] ?? '—') ?></b> ·
          Generado: <b><?= date('Y-m-d H:i') ?></b>
        </div>
      </td>
      <td style="width:40%; text-align:right; vertical-align:top;">
        <?php if(!empty($logo)): ?>
          <img class="logo" src="<?= $logo ?>" alt="Logo">
        <?php endif; ?>
        <div class="muted">LDR Solutions · MRP</div>
      </td>
    </tr>
  </table>
</header>

<footer>
  <div style="width:100%; display:flex; justify-content:space-between;">
    <div>Reporte KPI · Planeación</div>
    <div>Página <span class="pagenum"></span></div>
  </div>
</footer>

<div class="wrap">

  <table class="kpi">
    <tr>
      <td><div class="k">Sub-OT</div><div class="v"><?= (int)($k['subot'] ?? 0) ?></div></td>
      <td><div class="k">Eficiencia promedio</div><div class="v"><?= number_format((float)($k['eficiencia_prom'] ?? 0),1) ?>%</div></td>
      <td><div class="k">% en tiempo</div><div class="v"><?= number_format((float)($k['pct_en_tiempo'] ?? 0),1) ?>%</div></td>
      <td><div class="k">Rechazos</div><div class="v"><?= (int)($k['rechazos'] ?? 0) ?></div></td>
      <td><div class="k">Costo total</div><div class="v">$<?= number_format((float)($k['costo_total'] ?? 0),2) ?></div></td>
    </tr>
  </table>


  <div class="section">
    <h3>Resumen por Sub-OT</h3>
    <table>
      <thead>
        <tr>
          <th>Sub-OT</th>
          <th class="right">Std</th>
          <th class="right">Real</th>
          <th class="right">Eficiencia</th>
          <th class="right">% en tiempo</th>
          <th class="right">Rech.</th>
          <th>Últ. estatus</th>
          <th>Últ. calidad</th>
        </tr>
      </thead>
      <tbody>
        <?php if(empty($resumen)): ?>
          <tr><td colspan="8" style="text-align:center;color:#666;">Sin registros</td></tr>
        <?php else: ?>
          <?php foreach($resumen as $r): ?>
            <tr>
              <td><?= htmlspecialchars($r['num_sub_orden'] ?? '') ?></td>
              <td class="right"><?= number_format((float)($r['std_total'] ?? 0),1) ?></td>
              <td class="right"><?= number_format((float)($r['real_total'] ?? 0),1) ?></td>
              <td class="right"><?= number_format((float)($r['eficiencia_cap'] ?? 0),1) ?>%</td>
              <td class="right"><?= number_format((float)($r['pct_en_tiempo_cap'] ?? 0),1) ?>%</td>
              <td class="right"><?= (int)($r['rechazos'] ?? 0) ?></td>
              <td><?= htmlspecialchars($r['ultimo_estatus_txt'] ?? '') ?></td>
              <td><?= htmlspecialchars($r['ultima_calidad_txt'] ?? '') ?></td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>

  <!-- Encargados -->
  <div class="section">
    <h3>Encargados</h3>
    <table>
      <thead>
        <tr>
          <th>Encargado</th>
          <th class="right">Registros</th>
          <th class="right">Real total</th>
          <th class="right">Eficiencia promedio</th>
          <th class="right">% en tiempo</th>
          <th class="right">Rech.</th>
        </tr>
      </thead>
      <tbody>
        <?php if(empty($encargados)): ?>
          <tr><td colspan="6" style="text-align:center;color:#666;">Sin registros</td></tr>
        <?php else: ?>
          <?php foreach($encargados as $r): ?>
            <tr>
              <td><?= htmlspecialchars($r['encargado_nombre'] ?? '—') ?></td>
              <td class="right"><?= (int)($r['registros'] ?? 0) ?></td>
              <td class="right"><?= number_format((float)($r['real_total'] ?? 0),1) ?></td>
              <td class="right"><?= number_format((float)min(100,(float)($r['eficiencia_prom'] ?? 0)),1) ?>%</td>
              <td class="right"><?= number_format((float)min(100,(float)($r['pct_en_tiempo'] ?? 0)),1) ?>%</td>
              <td class="right"><?= (int)($r['rechazos'] ?? 0) ?></td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>

  <!-- Calidad por estación -->
  <div class="section">
    <h3>Calidad por estación</h3>
    <table>
      <thead>
        <tr>
          <th>Estación</th>
          <th class="right">Pend.</th>
          <th class="right">En insp.</th>
          <th class="right">Obs.</th>
          <th class="right">Rech.</th>
          <th class="right">Lib.</th>
          <th class="right">Total</th>
        </tr>
      </thead>
      <tbody>
        <?php if(empty($calidad)): ?>
          <tr><td colspan="7" style="text-align:center;color:#666;">Sin registros</td></tr>
        <?php else: ?>
          <?php foreach($calidad as $r): ?>
            <tr>
              <td><?= htmlspecialchars(trim(($r['cve_estacion'] ?? '').' · '.($r['nombre_estacion'] ?? ''))) ?></td>
              <td class="right"><?= (int)($r['c1'] ?? 0) ?></td>
              <td class="right"><?= (int)($r['c2'] ?? 0) ?></td>
              <td class="right"><?= (int)($r['c3'] ?? 0) ?></td>
              <td class="right"><?= (int)($r['c4'] ?? 0) ?></td>
              <td class="right"><?= (int)($r['c5'] ?? 0) ?></td>
              <td class="right"><?= (int)($r['total'] ?? 0) ?></td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>

  <!-- Costos por estación -->
  <div class="section">
    <h3>Costos por estación</h3>
    <table>
      <thead>
        <tr>
          <th>Estación</th>
          <th>Encargado</th>
          <th>Ayudante</th>
          <th class="right">Costo</th>
          <th class="right">Total</th>
        </tr>
      </thead>
      <tbody>
        <?php if(empty($costos_estacion)): ?>
          <tr><td colspan="5" style="text-align:center;color:#666;">Sin registros</td></tr>
        <?php else: ?>
          <?php foreach($costos_estacion as $r): ?>
            <tr>
              <td><?= htmlspecialchars(trim(($r['cve_estacion'] ?? '').' · '.($r['nombre_estacion'] ?? ''))) ?></td>
              <td><?= htmlspecialchars($r['encargado_nombre'] ?? '—') ?></td>
              <td><?= htmlspecialchars($r['ayudante_nombre'] ?? '—') ?></td>
              <td class="right">$<?= number_format((float)($r['costo_total_estacion'] ?? 0),2) ?></td>
              <td class="right"><?= (int)($r['total_registros'] ?? 0) ?></td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>

  <div class="page-break"></div>

  <!-- Costos detalle -->
  <div class="section">
    <h3>Costos (detalle)</h3>
    <table>
      <thead>
        <tr>
          <th>Estación</th>
          <th>Artículo</th>
          <th>Descripción</th>
          <th class="right">Cant. plan</th>
          <th class="right">Cant. x prod</th>
          <th class="right">Cant. total</th>
          <th class="right">Últ. costo</th>
          <th class="right">Costo total</th>
        </tr>
      </thead>
      <tbody>
        <?php if(empty($costos_detalle)): ?>
          <tr><td colspan="8" style="text-align:center;color:#666;">Sin registros</td></tr>
        <?php else: ?>
          <?php foreach($costos_detalle as $r): ?>
            <tr>
              <td><?= htmlspecialchars(trim(($r['cve_estacion'] ?? '').' · '.($r['nombre_estacion'] ?? ''))) ?></td>
              <td><?= htmlspecialchars($r['cve_articulo'] ?? '') ?></td>
              <td class="wraptext"><?= htmlspecialchars($r['descripcion'] ?? '') ?></td>
              <td class="right"><?= number_format((float)($r['cantidad_planeada'] ?? 0),0) ?></td>
              <td class="right"><?= number_format((float)($r['cantidad_por_producto'] ?? 0),3) ?></td>
              <td class="right"><?= number_format((float)($r['cantidad_total_requerida'] ?? 0),3) ?></td>
              <td class="right">$<?= number_format((float)($r['ultimo_costo'] ?? 0),2) ?></td>
              <td class="right">$<?= number_format((float)($r['costo_total_articulo'] ?? 0),2) ?></td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>

  <div class="page-break"></div>

  <!-- Detalle por registro -->
  <div class="section">
    <h3>Detalle por registro</h3>
    <table>
      <thead>
        <tr>
          <th>Sub-OT</th>
          <th>Estación</th>
          <th>Encargado</th>
          <th class="right">Std</th>
          <th class="right">Real</th>
          <th class="right">Eficiencia</th>
          <th>En tiempo</th>
          <th>Estatus</th>
          <th>Calidad</th>
        </tr>
      </thead>
      <tbody>
        <?php if(empty($detalle)): ?>
          <tr><td colspan="9" style="text-align:center;color:#666;">Sin registros</td></tr>
        <?php else: ?>
          <?php foreach($detalle as $r): ?>
            <tr>
              <td><?= htmlspecialchars($r['num_sub_orden'] ?? '') ?></td>
              <td><?= htmlspecialchars(trim(($r['cve_estacion'] ?? '').' · '.($r['nombre_estacion'] ?? ''))) ?></td>
              <td><?= htmlspecialchars($r['encargado_nombre'] ?? '—') ?></td>
              <td class="right"><?= number_format((float)($r['estandar_min'] ?? 0),1) ?></td>
              <td class="right"><?= number_format((float)($r['duracion_real_min'] ?? 0),1) ?></td>
              <td class="right"><?= number_format((float)($r['eficiencia_cap'] ?? 0),1) ?>%</td>
              <td><?= ((int)($r['en_tiempo'] ?? 0) === 1 ? 'En tiempo' : 'Fuera de tiempo') ?></td>
              <td><?= htmlspecialchars($r['estatus_txt'] ?? '') ?></td>
              <td><?= htmlspecialchars($r['calidad_txt'] ?? '') ?></td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>

</div>
</body>
</html>
