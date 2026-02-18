<?php
$info = $d['info'] ?? [];
$k    = $d['kpis'] ?? [];
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
  .spacer{ height: 10px; }
</style>

</head>
<body>

<header>
  <table style="width:100%; border-collapse:collapse;">
    <tr>
      <td style="width:60%; vertical-align:top;">
        <div class="title">Reporte KPI · Planeación (Completo)</div>
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
        <?php if(!empty($d['logo'])): ?>
          <img class="logo" src="<?= $d['logo'] ?>" alt="Logo">
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
      <td><div class="k">Eficiencia Prom</div><div class="v"><?= number_format((float)($k['eficiencia_prom'] ?? 0),1) ?>%</div></td>
      <td><div class="k">% En tiempo</div><div class="v"><?= number_format((float)($k['pct_en_tiempo'] ?? 0),1) ?>%</div></td>
      <td><div class="k">Rechazos</div><div class="v"><?= (int)($k['rechazos'] ?? 0) ?></div></td>
      <td><div class="k">Costo Total</div><div class="v">$<?= number_format((float)($k['costo_total'] ?? 0),2) ?></div></td>
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
        <th class="right">% En tiempo</th>
        <th class="right">Rech</th>
        <th>Últ. Estatus</th>
        <th>Últ. Calidad</th>
      </tr>
    </thead>
    <tbody>
    <?php foreach(($d['resumen'] ?? []) as $r): ?>
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
    </tbody>
  </table>

  </div>
<div class="section">
  <h3>Encargados</h3>
  <table>
    <thead>
      <tr>
        <th>Encargado</th>
        <th class="right">Registros</th>
        <th class="right">Real Total</th>
        <th class="right">Eficiencia Prom</th>
        <th class="right">% En tiempo</th>
        <th class="right">Rech</th>
      </tr>
    </thead>
    <tbody>
    <?php foreach(($d['encargados'] ?? []) as $r): ?>
      <tr>
        <td><?= htmlspecialchars($r['encargado_nombre'] ?? '—') ?></td>
        <td class="right"><?= (int)($r['registros'] ?? 0) ?></td>
        <td class="right"><?= number_format((float)($r['real_total'] ?? 0),1) ?></td>
        <td class="right"><?= number_format((float)min(100,(float)($r['eficiencia_prom'] ?? 0)),1) ?>%</td>
        <td class="right"><?= number_format((float)min(100,(float)($r['pct_en_tiempo'] ?? 0)),1) ?>%</td>
        <td class="right"><?= (int)($r['rechazos'] ?? 0) ?></td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
  </div>
<div class="section">
  <h3>Calidad por Estación</h3>
  <table>
    <thead>
      <tr>
        <th>Estación</th>
        <th class="right">Pend</th>
        <th class="right">En Insp</th>
        <th class="right">Obs</th>
        <th class="right">Rech</th>
        <th class="right">Lib</th>
        <th class="right">Total</th>
      </tr>
    </thead>
    <tbody>
    <?php foreach(($d['calidad'] ?? []) as $r): ?>
      <tr>
        <td><?= htmlspecialchars(($r['cve_estacion'] ?? '').' · '.($r['nombre_estacion'] ?? '')) ?></td>
        <td class="right"><?= (int)($r['c1'] ?? 0) ?></td>
        <td class="right"><?= (int)($r['c2'] ?? 0) ?></td>
        <td class="right"><?= (int)($r['c3'] ?? 0) ?></td>
        <td class="right"><?= (int)($r['c4'] ?? 0) ?></td>
        <td class="right"><?= (int)($r['c5'] ?? 0) ?></td>
        <td class="right"><?= (int)($r['total'] ?? 0) ?></td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
</div>

<div class="section">
  <h3>Costos por Estación</h3>
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
    <?php foreach(($d['costos_estacion'] ?? []) as $r): ?>
      <tr>
        <td><?= htmlspecialchars(($r['cve_estacion'] ?? '').' · '.($r['nombre_estacion'] ?? '')) ?></td>
        <td><?= htmlspecialchars($r['encargado_nombre'] ?? '—') ?></td>
        <td><?= htmlspecialchars($r['ayudante_nombre'] ?? '—') ?></td>
        <td class="right">$<?= number_format((float)($r['costo_total_estacion'] ?? 0),2) ?></td>

        <td class="right"><?= (int)($r['total_registros'] ?? 0) ?></td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
</div>
  <div class="page-break"></div>
<div class="section">
  <h3>Costos Detalle</h3>
  <table>
    <thead>
      <tr>
        <th>Estación</th>
        <th>Artículo</th>
        <th>Descripción</th>
        <th class="right">Cant Plan</th>
        <th class="right">Cant x Prod</th>
        <th class="right">Cant Total</th>
        <th class="right">Últ Costo</th>
        <th class="right">Costo Total</th>
      </tr>
    </thead>
    <tbody>
    <?php foreach(($d['costos_detalle'] ?? []) as $r): ?>
      <tr>
        <td><?= htmlspecialchars(($r['cve_estacion'] ?? '').' · '.($r['nombre_estacion'] ?? '')) ?></td>
        <td><?= htmlspecialchars($r['cve_articulo'] ?? '') ?></td>
        <td class="wraptext"><?= htmlspecialchars($r['descripcion'] ?? '') ?></td>
        <td class="right"><?= number_format((float)($r['cantidad_planeada'] ?? 0),0) ?></td>
        <td class="right"><?= number_format((float)($r['cantidad_por_producto'] ?? 0),3) ?></td>
        <td class="right"><?= number_format((float)($r['cantidad_total_requerida'] ?? 0),3) ?></td>
        <td class="right">$<?= number_format((float)($r['ultimo_costo'] ?? 0),2) ?></td>
        <td class="right">$<?= number_format((float)($r['costo_total_articulo'] ?? 0),2) ?></td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
</div>
  <div class="page-break"></div>
<div class="section">
  <h3>Detalle por Registro</h3>
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
    <?php foreach(($d['detalle'] ?? []) as $r): ?>
      <tr>
        <td><?= htmlspecialchars($r['num_sub_orden'] ?? '') ?></td>
        <td><?= htmlspecialchars(($r['cve_estacion'] ?? '').' · '.($r['nombre_estacion'] ?? '')) ?></td>
        <td><?= htmlspecialchars($r['encargado_nombre'] ?? '—') ?></td>
        <td class="right"><?= number_format((float)($r['estandar_min'] ?? 0),1) ?></td>
        <td class="right"><?= number_format((float)($r['duracion_real_min'] ?? 0),1) ?></td>
        <td class="right"><?= number_format((float)($r['eficiencia_cap'] ?? 0),1) ?>%</td>
        <td><?= ((int)($r['en_tiempo'] ?? 0)===1 ? 'En tiempo' : 'Fuera') ?></td>
        <td><?= htmlspecialchars($r['estatus_txt'] ?? '') ?></td>
        <td><?= htmlspecialchars($r['calidad_txt'] ?? '') ?></td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
</div>
</div>
</body>
</html>
