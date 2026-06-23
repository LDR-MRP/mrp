<?php

$resp = $data['unidad'] ?? [];

$unidad     = $resp['unidad'] ?? [];
$estaciones = $resp['estaciones'] ?? [];
$eventos    = $resp['eventos'] ?? [];

// dep($resp);

function h($value)
{
    return htmlspecialchars((string)($value ?? ''), ENT_QUOTES, 'UTF-8');
}

function hDefault($value, $default = 'N/A')
{
    return !empty($value) ? h($value) : $default;
}

function formatoFecha($fecha)
{
    if (empty($fecha)) {
        return 'N/A';
    }

    return date('d/m/Y H:i', strtotime($fecha));
}

function formatoFechaTexto($fecha)
{
    if (empty($fecha)) {
        return 'N/A';
    }

    $timestamp = strtotime($fecha);

    $meses = [
        1 => 'enero',
        2 => 'febrero',
        3 => 'marzo',
        4 => 'abril',
        5 => 'mayo',
        6 => 'junio',
        7 => 'julio',
        8 => 'agosto',
        9 => 'septiembre',
        10 => 'octubre',
        11 => 'noviembre',
        12 => 'diciembre'
    ];

    $dia  = date('d', $timestamp);
    $mes  = $meses[(int) date('m', $timestamp)];
    $anio = date('Y', $timestamp);
    $hora = date('H:i', $timestamp);

    return $dia . ' de ' . $mes . ' de ' . $anio . ' a las ' . $hora . ' hrs';
}

function estadoUnidadTexto($estado)
{
    return match ((int)$estado) {
        1 => 'Pendiente',
        2 => 'Finalizada',
        3 => 'Liberada',
        default => 'Sin estado',
    };
}

function prioridadTexto($prioridad)
{
    return match ((int)$prioridad) {
        1 => 'Crítica',
        2 => 'Alta',
        3 => 'Media',
        4 => 'Baja',
        5 => 'Prototipo',
        default => 'N/A',
    };
}

function badgeEstado($texto, $tipo = 'ok')
{
    return '<span class="pill '.$tipo.'">'.h($texto).'</span>';
}

$vinAsignado = $unidad['vin_asignado'] ?? 'N/A';
$estadoFinal = estadoUnidadTexto($unidad['estado_unidad'] ?? 0);

$fechaInicioProduccion = null;
$fechaFinProduccion = null;


if (!empty($estaciones)) {
    foreach ($estaciones as $estacion) {
        if (!empty($estacion['fecha_inicio'])) {
            if ($fechaInicioProduccion === null || strtotime($estacion['fecha_inicio']) < strtotime($fechaInicioProduccion)) {
                $fechaInicioProduccion = $estacion['fecha_inicio'];
            }
        }

        if (!empty($estacion['fecha_fin'])) {
            if ($fechaFinProduccion === null || strtotime($estacion['fecha_fin']) > strtotime($fechaFinProduccion)) {
                $fechaFinProduccion = $estacion['fecha_fin'];
            }
        }
    }
}

function calcularTiempoTotalProduccion($fechaInicio, $fechaFin)
{
    if (empty($fechaInicio) || empty($fechaFin)) {
        return 'N/A';
    }

    $inicio = strtotime($fechaInicio);
    $fin = strtotime($fechaFin);

    if ($fin < $inicio) {
        return 'N/A';
    }

    $minutosTotales = floor(($fin - $inicio) / 60);

    $dias = floor($minutosTotales / 1440);
    $horas = floor(($minutosTotales % 1440) / 60);
    $minutos = $minutosTotales % 60;

    $texto = '';

    if ($dias > 0) {
        $texto .= $dias . ' día' . ($dias > 1 ? 's ' : ' ');
    }

    if ($horas > 0) {
        $texto .= $horas . ' h ';
    }

    $texto .= $minutos . ' min';

    return trim($texto);
}

$tiempoTotalProduccion = calcularTiempoTotalProduccion($fechaInicioProduccion, $fechaFinProduccion);

?>

<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />

  <title>Detalle de Unidad Ensamblada</title>

  <link href="https://cdn.jsdelivr.net/npm/remixicon@3.5.0/fonts/remixicon.css" rel="stylesheet">

  <style>
    :root {
      --primary: #0f172a;
      --secondary: #1e293b;
      --accent: #2563eb;
      --success: #16a34a;
      --warning: #f59e0b;
      --danger: #dc2626;
      --bg: #f1f5f9;
      --card: #ffffff;
      --text: #334155;
      --muted: #64748b;
      --border: #e2e8f0;
      --soft-blue: #eff6ff;
      --soft-green: #ecfdf5;
      --soft-yellow: #fffbeb;
      --soft-red: #fef2f2;
    }

    * {
      box-sizing: border-box;
    }

    body {
      margin: 0;
      font-family: "Segoe UI", Arial, sans-serif;
      background: var(--bg);
      color: var(--text);
    }

    .page {
      max-width: 1450px;
      margin: 30px auto;
      padding: 0 20px;
    }

    .report-card {
      background: var(--card);
      border-radius: 24px;
      box-shadow: 0 18px 45px rgba(15, 23, 42, 0.12);
      overflow: hidden;
      border: 1px solid var(--border);
    }

    .header {
      background: #000001;
      color: #fff;
      padding: 30px 36px;
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 24px;
    }

    .header-left {
      display: flex;
      align-items: center;
      gap: 18px;
    }

    .logo-box {
      width: 78px;
      height: 78px;
      background: #fff;
      border-radius: 18px;
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 10px;
      flex: 0 0 auto;
    }

    .logo-box img {
      max-width: 100%;
      max-height: 100%;
      object-fit: contain;
    }

    .header-title h1 {
      margin: 0;
      font-size: 28px;
      font-weight: 800;
    }

    .header-title p {
      margin: 7px 0 0;
      font-size: 14px;
      color: #cbd5e1;
    }

    .header-badges {
      display: flex;
      flex-wrap: wrap;
      justify-content: flex-end;
      gap: 10px;
    }

    .status-badge {
      display: inline-flex;
      align-items: center;
      gap: 7px;
      background: rgba(22, 163, 74, 0.15);
      color: #bbf7d0;
      border: 1px solid rgba(34, 197, 94, 0.45);
      padding: 9px 14px;
      border-radius: 999px;
      font-size: 13px;
      font-weight: 700;
      white-space: nowrap;
    }

    .content {
      padding: 32px 36px 40px;
    }

    .main-summary {
      display: grid;
      grid-template-columns: 1.5fr 1fr;
      gap: 22px;
      margin-bottom: 26px;
    }

    .main-unit {
      background: linear-gradient(135deg, #eff6ff, #ffffff);
      border: 1px solid #bfdbfe;
      border-radius: 22px;
      padding: 28px;
    }

    .main-unit .label {
      color: var(--muted);
      font-size: 13px;
      font-weight: 700;
      text-transform: uppercase;
      letter-spacing: .7px;
    }

    .main-unit h2 {
      margin: 8px 0 10px;
      font-size: 34px;
      color: var(--primary);
    }

    .main-unit p {
      margin: 0;
      color: var(--muted);
      line-height: 1.7;
    }

    .summary-panel {
      border: 1px solid var(--border);
      border-radius: 22px;
      padding: 22px;
      background: #fff;
    }

    .summary-panel h3 {
      margin: 0 0 16px;
      color: var(--primary);
      font-size: 17px;
      display: flex;
      align-items: center;
      gap: 8px;
    }

    .summary-panel h3 i {
      color: var(--accent);
      font-size: 21px;
    }

    .summary-item {
      display: flex;
      justify-content: space-between;
      gap: 14px;
      padding: 10px 0;
      border-bottom: 1px dashed var(--border);
      font-size: 14px;
    }

    .summary-item:last-child {
      border-bottom: none;
    }

    .summary-item span {
      color: var(--muted);
      display: inline-flex;
      align-items: center;
      gap: 7px;
    }

    .summary-item span i {
      color: var(--accent);
      font-size: 16px;
    }

    .summary-item strong {
      color: var(--primary);
      text-align: right;
    }

    .section {
      margin-top: 30px;
    }

    .section-title {
      display: flex;
      align-items: center;
      gap: 9px;
      margin-bottom: 15px;
      color: var(--primary);
      font-size: 19px;
      font-weight: 800;
    }

    .section-title i {
      color: var(--accent);
      font-size: 23px;
    }

    .grid {
      display: grid;
      grid-template-columns: repeat(4, 1fr);
      gap: 16px;
    }

    .info-card {
      background: #fff;
      border: 1px solid var(--border);
      border-radius: 18px;
      padding: 18px;
      min-height: 112px;
    }

    .info-card .icon {
      width: 40px;
      height: 40px;
      border-radius: 13px;
      background: #eff6ff;
      color: var(--accent);
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 21px;
      margin-bottom: 12px;
    }

    .info-card .title {
      color: var(--muted);
      font-size: 13px;
      margin-bottom: 7px;
      display: flex;
      align-items: center;
      gap: 6px;
    }

    .info-card .value {
      color: var(--primary);
      font-size: 16px;
      font-weight: 800;
      word-break: break-word;
    }

    .info-card.success {
      background: var(--soft-green);
      border-color: #bbf7d0;
    }

    .info-card.warning {
      background: var(--soft-yellow);
      border-color: #fde68a;
    }

    .pill {
      display: inline-flex;
      align-items: center;
      gap: 6px;
      padding: 6px 11px;
      border-radius: 999px;
      font-size: 12px;
      font-weight: 800;
      white-space: nowrap;
    }

    .pill.ok {
      background: #dcfce7;
      color: #166534;
    }

    .pill.info {
      background: #dbeafe;
      color: #1d4ed8;
    }

    .pill.warn {
      background: #fef3c7;
      color: #92400e;
    }

    .pill.danger {
      background: #fee2e2;
      color: #991b1b;
    }

    .timeline-production {
      position: relative;
      padding-left: 28px;
    }

    .timeline-production::before {
      content: "";
      position: absolute;
      left: 9px;
      top: 8px;
      bottom: 8px;
      width: 2px;
      background: #cbd5e1;
    }

    .station-card {
      position: relative;
      background: #fff;
      border: 1px solid var(--border);
      border-radius: 20px;
      padding: 22px;
      margin-bottom: 18px;
      box-shadow: 0 8px 22px rgba(15, 23, 42, 0.05);
    }

    .station-card::before {
      content: "";
      position: absolute;
      left: -27px;
      top: 28px;
      width: 18px;
      height: 18px;
      border-radius: 50%;
      background: var(--success);
      border: 4px solid #dcfce7;
    }

    .station-header {
      display: flex;
      justify-content: space-between;
      gap: 18px;
      align-items: flex-start;
      margin-bottom: 16px;
    }

    .station-title h3 {
      margin: 0;
      color: var(--primary);
      font-size: 19px;
      display: flex;
      align-items: center;
      gap: 8px;
      flex-wrap: wrap;
    }

    .station-title h3 i {
      color: var(--accent);
    }

    .station-title p {
      margin: 7px 0 0;
      color: var(--muted);
      font-size: 14px;
      display: flex;
      align-items: center;
      gap: 7px;
    }

    .station-title p i {
      color: var(--accent);
    }

    .station-grid {
      display: grid;
      grid-template-columns: repeat(4, 1fr);
      gap: 12px;
      margin-top: 16px;
    }

    .station-data {
      background: #f8fafc;
      border: 1px solid var(--border);
      border-radius: 14px;
      padding: 13px;
    }

    .station-data span {
      display: flex;
      align-items: center;
      gap: 6px;
      color: var(--muted);
      font-size: 12px;
      margin-bottom: 5px;
    }

    .station-data span i {
      color: var(--accent);
      font-size: 15px;
    }

    .station-data strong {
      display: block;
      color: var(--primary);
      font-size: 14px;
    }

    .station-note {
      margin-top: 16px;
      background: #f8fafc;
      border-left: 4px solid var(--accent);
      border-radius: 12px;
      padding: 14px 16px;
      color: var(--text);
      font-size: 14px;
      line-height: 1.6;
    }

    .station-note i {
      color: var(--accent);
      margin-right: 5px;
    }

    .quality-box {
      margin-top: 14px;
      background: #ecfdf5;
      border: 1px solid #bbf7d0;
      border-radius: 16px;
      padding: 16px;
      line-height: 1.6;
    }

    .quality-box h4 {
      margin: 0 0 10px;
      color: #166534;
      font-size: 15px;
      display: flex;
      align-items: center;
      gap: 7px;
    }

    .vin-box {
      margin-top: 14px;
      background: #eff6ff;
      border: 1px solid #bfdbfe;
      border-radius: 16px;
      padding: 16px;
      line-height: 1.6;
    }

    .vin-box h4 {
      margin: 0 0 10px;
      color: #1d4ed8;
      font-size: 15px;
      display: flex;
      align-items: center;
      gap: 7px;
    }

    .table-card {
      border: 1px solid var(--border);
      border-radius: 18px;
      overflow-x: auto;
      background: #fff;
    }

    table {
      width: 100%;
      border-collapse: collapse;
    }

    .table-station {
      min-width: 1350px;
    }

    th {
      background: #f8fafc;
      color: var(--primary);
      font-size: 13px;
      text-align: left;
      padding: 14px 16px;
      border-bottom: 1px solid var(--border);
      white-space: nowrap;
    }

    th i {
      color: var(--accent);
      margin-right: 5px;
      font-size: 15px;
    }

    td {
      padding: 14px 16px;
      border-bottom: 1px solid var(--border);
      font-size: 14px;
      vertical-align: top;
    }

    tr:last-child td {
      border-bottom: none;
    }

    .station-code {
      display: inline-flex;
      align-items: center;
      gap: 7px;
      white-space: nowrap;
      background: #eff6ff;
      color: #1d4ed8;
      border: 1px solid #bfdbfe;
      padding: 7px 10px;
      border-radius: 999px;
      font-weight: 800;
      font-size: 13px;
    }

    .table-icon-text {
      display: flex;
      align-items: flex-start;
      gap: 8px;
      min-width: 160px;
    }

    .table-icon-text i {
      color: var(--accent);
      font-size: 18px;
      margin-top: 1px;
    }

    .table-icon-text strong {
      display: block;
      color: var(--primary);
      font-size: 14px;
    }

    .table-icon-text span {
      display: block;
      color: var(--muted);
      font-size: 12px;
      margin-top: 2px;
      line-height: 1.4;
    }

    .date-soft {
      display: inline-flex;
      align-items: flex-start;
      gap: 7px;
      color: var(--primary);
      font-size: 13px;
      line-height: 1.4;
      min-width: 190px;
    }

    .date-soft i {
      color: var(--accent);
      font-size: 17px;
      margin-top: 1px;
    }

    .event-list {
      display: grid;
      gap: 14px;
    }

    .event-card {
      background: #fff;
      border: 1px solid var(--border);
      border-radius: 18px;
      padding: 17px;
      display: grid;
      grid-template-columns: 42px 1fr auto;
      gap: 14px;
      align-items: flex-start;
    }

    .event-icon {
      width: 42px;
      height: 42px;
      border-radius: 14px;
      background: #eff6ff;
      color: var(--accent);
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 21px;
    }

    .event-card h4 {
      margin: 0 0 5px;
      color: var(--primary);
      font-size: 15px;
    }

    .event-card p {
      margin: 0;
      color: var(--muted);
      font-size: 14px;
      line-height: 1.5;
    }

    .event-card p i {
      color: var(--accent);
      margin-right: 5px;
    }

    .event-date {
      color: var(--muted);
      font-size: 13px;
      white-space: nowrap;
      display: inline-flex;
      align-items: center;
      gap: 6px;
    }

    .event-date i {
      color: var(--accent);
      font-size: 16px;
    }

    .footer {
      background: #f8fafc;
      border-top: 1px solid var(--border);
      padding: 18px 36px;
      display: flex;
      justify-content: space-between;
      gap: 20px;
      color: var(--muted);
      font-size: 13px;
    }

    .footer div {
      display: flex;
      align-items: center;
      gap: 7px;
    }

    .footer i {
      color: var(--accent);
      font-size: 17px;
    }

    @media (max-width: 1100px) {
      .main-summary {
        grid-template-columns: 1fr;
      }

      .grid,
      .station-grid {
        grid-template-columns: repeat(2, 1fr);
      }

      .header {
        flex-direction: column;
        align-items: flex-start;
      }

      .header-badges {
        justify-content: flex-start;
      }
    }

    @media (max-width: 650px) {
      .page {
        padding: 0 12px;
        margin: 16px auto;
      }

      .content {
        padding: 24px 18px;
      }

      .header {
        padding: 24px 18px;
      }

      .header-left {
        align-items: flex-start;
        flex-direction: column;
      }

      .main-unit h2 {
        font-size: 25px;
      }

      .grid,
      .station-grid {
        grid-template-columns: 1fr;
      }

      .station-header {
        flex-direction: column;
      }

      .event-card {
        grid-template-columns: 42px 1fr;
      }

      .event-date {
        grid-column: 2;
      }

      .footer {
        flex-direction: column;
        padding: 18px;
      }
    }
  </style>
</head>

<body>

<div class="page">
  <div class="report-card">

    <div class="header">
      <div class="header-left">
        <div class="logo-box">
          <img src="<?= media(); ?>/images/ldr-logo-dark.png" alt="Logo de la empresa">
        </div>

        <div class="header-title">
          <h1>Detalle de Unidad Ensamblada</h1>
          <p>Reporte de trazabilidad, producción, calidad, VIN y recorrido por estaciones</p>
        </div>
      </div>

      <div class="header-badges">
        <div class="status-badge">
          <i class="ri-checkbox-circle-line"></i>
          <?= h($estadoFinal) ?>
        </div>

        <div class="status-badge">
          <i class="ri-shield-check-line"></i>
          Calidad validada
        </div>

        <div class="status-badge">
          <i class="ri-fingerprint-line"></i>
          VIN <?= !empty($unidad['vin_asignado']) ? 'asignado' : 'pendiente' ?>
        </div>
      </div>
    </div>

    <div class="content">

      <div class="main-summary">
        <div class="main-unit">
          <div class="label">Unidad ensamblada</div>
          <h2><?= hDefault($unidad['num_unidad'] ?? '') ?></h2>
          <p>
            Esta vista concentra el historial completo de fabricación de la unidad,
            mostrando el recorrido por estaciones, responsables, tiempos estándar,
            tiempos reales, validaciones de calidad, asignación de VIN y eventos
            relevantes del proceso productivo.
          </p>
        </div>

        <div class="summary-panel">
          <h3>
            <i class="ri-dashboard-line"></i>
            Resumen rápido
          </h3>

          <div class="summary-item">
            <span><i class="ri-file-list-3-line"></i> Orden de trabajo</span>
            <strong><?= hDefault($unidad['num_orden'] ?? '') ?></strong>
          </div>

          <div class="summary-item">
            <span><i class="ri-box-3-line"></i> Producto</span>
            <strong><?= hDefault($unidad['producto'] ?? '') ?></strong>
          </div>

          <div class="summary-item">
            <span><i class="ri-barcode-line"></i> Clave producto</span>
            <strong><?= hDefault($unidad['cve_producto'] ?? '') ?></strong>
          </div>

          <div class="summary-item">
            <span><i class="ri-building-2-line"></i> Planta</span>
            <strong><?= hDefault($unidad['nombre_planta'] ?? '') ?></strong>
          </div>

          <div class="summary-item">
            <span><i class="ri-user-star-line"></i> Supervisor</span>
            <strong><?= hDefault($unidad['supervisor_nombre'] ?? '') ?></strong>
          </div>

          <div class="summary-item">
            <span><i class="ri-checkbox-circle-line"></i> Estado final</span>
            <strong><?= h($estadoFinal) ?></strong>
          </div>
        </div>
      </div>

      <div class="section">
        <div class="section-title">
          <i class="ri-car-line"></i>
          Datos principales de la unidad
        </div>

        <div class="grid">
          <div class="info-card">
            <div class="icon"><i class="ri-box-3-line"></i></div>
            <div class="title">Producto</div>
            <div class="value"><?= hDefault($unidad['producto'] ?? '') ?></div>
          </div>

          <div class="info-card">
            <div class="icon"><i class="ri-barcode-line"></i></div>
            <div class="title">Clave de producto</div>
            <div class="value"><?= hDefault($unidad['cve_producto'] ?? '') ?></div>
          </div>

          <div class="info-card">
            <div class="icon"><i class="ri-file-list-3-line"></i></div>
            <div class="title">Orden de trabajo</div>
            <div class="value"><?= hDefault($unidad['num_orden'] ?? '') ?></div>
          </div>

          <div class="info-card">
            <div class="icon"><i class="ri-hashtag"></i></div>
            <div class="title">Unidad</div>
            <div class="value"><?= hDefault($unidad['num_unidad'] ?? '') ?></div>
          </div>

          <div class="info-card">
            <div class="icon"><i class="ri-shopping-bag-line"></i></div>
            <div class="title">Pedido</div>
            <div class="value"><?= hDefault($unidad['num_pedido'] ?? '') ?></div>
          </div>

          <div class="info-card">
            <div class="icon"><i class="ri-building-2-line"></i></div>
            <div class="title">Planta</div>
            <div class="value"><?= hDefault($unidad['nombre_planta'] ?? '') ?></div>
          </div>

          <div class="info-card">
            <div class="icon"><i class="ri-calendar-check-line"></i></div>
            <div class="title">Fecha requerida</div>
            <div class="value"><?= formatoFechaTexto($unidad['fecha_requerida'] ?? '') ?></div>
          </div>

          <div class="info-card success">
            <div class="icon"><i class="ri-checkbox-circle-line"></i></div>
            <div class="title">Estatus</div>
            <div class="value"><?= h($estadoFinal) ?></div>
          </div>
        </div>
      </div>

      <div class="section">
        <div class="section-title">
          <i class="ri-fingerprint-line"></i>
          Identificación VIN
        </div>

        <div class="grid">
          <div class="info-card">
            <div class="icon"><i class="ri-hashtag"></i></div>
            <div class="title">VIN asignado</div>
            <div class="value"><?= hDefault($unidad['vin_asignado'] ?? '') ?></div>
          </div>

          <div class="info-card">
            <div class="icon"><i class="ri-user-settings-line"></i></div>
            <div class="title">Usuario que asignó VIN</div>
            <div class="value"><?= hDefault($unidad['asignacion_vin_usuario'] ?? '') ?></div>
          </div>

          <div class="info-card">
            <div class="icon"><i class="ri-time-line"></i></div>
            <div class="title">Fecha asignación VIN</div>
            <div class="value"><?= formatoFechaTexto($unidad['fecha_asignacion'] ?? '') ?></div>
          </div>

          <div class="info-card">
            <div class="icon"><i class="ri-map-pin-line"></i></div>
            <div class="title">Estación de estampado</div>
            <div class="value">
              <?= hDefault($unidad['cve_estacion_vin'] ?? '') ?>
              <?= !empty($unidad['estacion_estampado_vin']) ? ' - '.h($unidad['estacion_estampado_vin']) : '' ?>
            </div>
          </div>

          <div class="info-card">
            <div class="icon"><i class="ri-tools-line"></i></div>
            <div class="title">Número de motor</div>
            <div class="value"><?= hDefault($unidad['numero_motor'] ?? '') ?></div>
          </div>

          <div class="info-card">
            <div class="icon"><i class="ri-settings-3-line"></i></div>
            <div class="title">Transmisión</div>
            <div class="value"><?= hDefault($unidad['numero_transmision'] ?? '') ?></div>
          </div>

          <div class="info-card">
            <div class="icon"><i class="ri-database-2-line"></i></div>
            <div class="title">Origen VIN</div>
            <div class="value"><?= hDefault($unidad['vin_origen'] ?? '') ?></div>
          </div>

          <div class="info-card success">
            <div class="icon"><i class="ri-lock-line"></i></div>
            <div class="title">Validación VIN</div>
            <div class="value">
              <?= !empty($unidad['vin_asignado']) ? 'Asignado correctamente' : 'Pendiente de asignar' ?>
            </div>
          </div>
        </div>
      </div>

   <div class="section">
  <div class="section-title">
    <i class="ri-timer-line"></i>
    Tiempos generales de producción
  </div>

  <div class="grid">
    <div class="info-card">
      <div class="icon"><i class="ri-play-circle-line"></i></div>
      <div class="title">Inicio de producción</div>
      <div class="value"><?= formatoFechaTexto($fechaInicioProduccion ?? '') ?></div>
    </div>

    <div class="info-card success">
      <div class="icon"><i class="ri-checkbox-circle-line"></i></div>
      <div class="title">Fin de producción</div>
      <div class="value"><?= formatoFechaTexto($fechaFinProduccion ?? '') ?></div>
    </div>

    <div class="info-card">
      <div class="icon"><i class="ri-hourglass-line"></i></div>
      <div class="title">Tiempo total de producción</div>
      <div class="value"><?= hDefault($tiempoTotalProduccion ?? '') ?></div>
    </div>

    <div class="info-card">
      <div class="icon"><i class="ri-calendar-check-line"></i></div>
      <div class="title">Fecha requerida</div>
      <div class="value"><?= formatoFechaTexto($unidad['fecha_requerida'] ?? '') ?></div>
    </div>
  </div>
</div>

      <div class="section">
        <div class="section-title">
          <i class="ri-team-line"></i>
          Responsables del proyecto
        </div>

        <div class="grid">
          <div class="info-card">
            <div class="icon"><i class="ri-user-star-line"></i></div>
            <div class="title">Supervisor</div>
            <div class="value"><?= hDefault($unidad['supervisor_nombre'] ?? '') ?></div>
          </div>

          <div class="info-card">
            <div class="icon"><i class="ri-mail-line"></i></div>
            <div class="title">Correo del supervisor</div>
            <div class="value"><?= hDefault($unidad['supervisor_email'] ?? '') ?></div>
          </div>

          <div class="info-card">
            <div class="icon"><i class="ri-user-settings-line"></i></div>
            <div class="title">Usuario asignó VIN</div>
            <div class="value"><?= hDefault($unidad['asignacion_vin_usuario'] ?? '') ?></div>
          </div>

          <div class="info-card success">
            <div class="icon"><i class="ri-award-line"></i></div>
            <div class="title">Estado final</div>
            <div class="value"><?= h($estadoFinal) ?></div>
          </div>
        </div>
      </div>

      <div class="section">
        <div class="section-title">
          <i class="ri-route-line"></i>
          Recorrido detallado por estaciones
        </div>

        <div class="timeline-production">

          <?php if (!empty($estaciones)) { ?>
            <?php foreach ($estaciones as $estacion) { ?>

              <?php
                $esCalidad = ((int)($estacion['calidad'] ?? 0) === 2);
                $esVin = ((int)($estacion['estampado'] ?? 0) === 2);
                $esCritica = ((int)($estacion['especificaciones_criticas'] ?? 0) === 2);

                $badge = '<span class="pill ok"><i class="ri-check-line"></i> '.hDefault($estacion['estado_texto'] ?? 'Finalizada').'</span>';

                if ($esVin) {
                    $badge = '<span class="pill info"><i class="ri-fingerprint-line"></i> VIN</span>';
                } elseif ($esCalidad) {
                    $badge = '<span class="pill info"><i class="ri-shield-check-line"></i> Calidad</span>';
                } elseif ($esCritica) {
                    $badge = '<span class="pill warn"><i class="ri-alert-line"></i> Crítica</span>';
                }

                $encargado = $estacion['encargado'] ?? 'N/A';
                $ayudantes = $estacion['ayudantes'] ?? 'N/A';
              ?>

              <div class="station-card">
                <div class="station-header">
                  <div class="station-title">
                    <h3>
                      <i class="ri-map-pin-line"></i>
                      <?= hDefault($estacion['cve_estacion'] ?? '') ?>
                      •
                      <?= hDefault($estacion['nombre_estacion'] ?? '') ?>
                    </h3>

                    <p>
                      <i class="ri-tools-line"></i>
                      <?= hDefault($estacion['proceso'] ?? '') ?>
                    </p>
                  </div>

                  <?= $badge ?>
                </div>

                <div class="station-grid">
                  <div class="station-data">
                    <span><i class="ri-tools-line"></i> Proceso</span>
                    <strong><?= hDefault($estacion['proceso'] ?? '') ?></strong>
                  </div>

                  <div class="station-data">
                    <span><i class="ri-user-star-line"></i> Encargado</span>
                    <strong><?= hDefault($encargado) ?></strong>
                  </div>

                  <div class="station-data">
                    <span><i class="ri-group-line"></i> Ayudantes</span>
                    <strong><?= hDefault($ayudantes) ?></strong>
                  </div>

                  <div class="station-data">
                    <span><i class="ri-time-line"></i> Tiempo estándar</span>
                    <strong><?= hDefault($estacion['tiempo_estandar_formato'] ?? '') ?></strong>
                  </div>

                  <div class="station-data">
                    <span><i class="ri-hourglass-line"></i> Tiempo real</span>
                    <strong><?= hDefault($estacion['tiempo_real_formato'] ?? '') ?></strong>
                  </div>

                  <div class="station-data">
                    <span><i class="ri-play-circle-line"></i> Inicio</span>
                    <strong><?= formatoFechaTexto($estacion['fecha_inicio'] ?? '') ?></strong>
                  </div>

                  <div class="station-data">
                    <span><i class="ri-checkbox-circle-line"></i> Fin</span>
                    <strong><?= formatoFechaTexto($estacion['fecha_fin'] ?? '') ?></strong>
                  </div>

                  <div class="station-data">
                    <span><i class="ri-flag-line"></i> Estado</span>
                    <strong><?= hDefault($estacion['estado_texto'] ?? '') ?></strong>
                  </div>
                </div>

                <?php if ($esCalidad) { ?>
                  <div class="quality-box">
                    <h4><i class="ri-checkbox-circle-line"></i> Inspección de calidad</h4>
                    En esta estación se realizó una inspección de calidad.
                    Resultado:
                    <strong><?= hDefault($estacion['texto_calidad'] ?? '') ?></strong>
                  </div>
                <?php } ?>

                <?php if ($esCritica) { ?>
                  <div class="quality-box">
                    <h4><i class="ri-alert-line"></i> Especificaciones críticas</h4>
                    En esta estación se aplicaron operaciones o especificaciones críticas.
                    Resultado:
                    <strong><?= hDefault($estacion['texto_especificaciones_criticas'] ?? '') ?></strong>
                  </div>
                <?php } ?>

                <?php if ($esVin) { ?>
                  <div class="vin-box">
                    <h4><i class="ri-fingerprint-line"></i> Detalle de VIN</h4>

                    El VIN
                    <strong><?= hDefault($estacion['vin_asignado'] ?? '') ?></strong>
                    fue asignado en esta estación.

                    <br>

                    Motor:
                    <strong><?= hDefault($estacion['numero_motor'] ?? '') ?></strong>

                    <br>

                    Transmisión:
                    <strong><?= hDefault($estacion['numero_transmision'] ?? '') ?></strong>

                    <br>

                    Origen:
                    <strong><?= hDefault($estacion['vin_origen'] ?? '') ?></strong>

                    <br>

                    Fecha de asignación:
                    <strong><?= formatoFechaTexto($estacion['fecha_asignacion'] ?? '') ?></strong>
                  </div>
                <?php } ?>

                <div class="station-note">
                  <i class="ri-information-line"></i>
                  El usuario <strong><?= hDefault($encargado) ?></strong>
                  inició la unidad en la estación
                  <strong><?= hDefault($estacion['cve_estacion'] ?? '') ?> - <?= hDefault($estacion['nombre_estacion'] ?? '') ?></strong>
                  el día
                  <strong><?= formatoFechaTexto($estacion['fecha_inicio'] ?? '') ?></strong>
                  y finalizó el proceso el día
                  <strong><?= formatoFechaTexto($estacion['fecha_fin'] ?? '') ?></strong>.
                </div>
              </div>

            <?php } ?>
          <?php } else { ?>

            <div class="station-card">
              <div class="station-header">
                <div class="station-title">
                  <h3>
                    <i class="ri-information-line"></i>
                    Sin estaciones registradas
                  </h3>
                  <p>No se encontraron registros de producción para esta unidad.</p>
                </div>
              </div>
            </div>

          <?php } ?>

        </div>
      </div>

      <div class="section">
        <div class="section-title">
          <i class="ri-table-line"></i>
          Resumen de estaciones
        </div>

        <div class="table-card">
          <table class="table-station">
            <thead>
              <tr>
                <th><i class="ri-sort-number-asc"></i> Orden</th>
                <th><i class="ri-map-pin-line"></i> Estación</th>
                <th><i class="ri-tools-line"></i> Proceso</th>
                <th><i class="ri-team-line"></i> Responsables</th>
                <th><i class="ri-play-circle-line"></i> Inicio</th>
                <th><i class="ri-checkbox-circle-line"></i> Fin</th>
                <th><i class="ri-timer-line"></i> Tiempo</th>
                <th><i class="ri-shield-check-line"></i> Calidad</th>
                <th><i class="ri-fingerprint-line"></i> VIN</th>
                <th><i class="ri-alert-line"></i> Críticas</th>
                <th><i class="ri-flag-line"></i> Estado</th>
              </tr>
            </thead>

            <tbody>
              <?php if (!empty($estaciones)) { ?>
                <?php foreach ($estaciones as $estacion) { ?>
                  <tr>
                    <td>
                      <span class="station-code">
                        <i class="ri-route-line"></i>
                        <?= hDefault($estacion['orden'] ?? '') ?>
                      </span>
                    </td>

                    <td>
                      <span class="station-code">
                        <i class="ri-map-pin-line"></i>
                        <?= hDefault($estacion['cve_estacion'] ?? '') ?>
                      </span>
                    </td>

                    <td>
                      <div class="table-icon-text">
                        <i class="ri-tools-line"></i>
                        <div>
                          <strong><?= hDefault($estacion['nombre_estacion'] ?? '') ?></strong>
                          <span><?= hDefault($estacion['proceso'] ?? '') ?></span>
                        </div>
                      </div>
                    </td>

                    <td>
                      <div class="table-icon-text">
                        <i class="ri-user-star-line"></i>
                        <div>
                          <strong><?= hDefault($estacion['encargado'] ?? '') ?></strong>
                          <span>Ayudantes: <?= hDefault($estacion['ayudantes'] ?? '') ?></span>
                        </div>
                      </div>
                    </td>

                    <td>
                      <div class="date-soft">
                        <i class="ri-play-circle-line"></i>
                        <span><?= formatoFechaTexto($estacion['fecha_inicio'] ?? '') ?></span>
                      </div>
                    </td>

                    <td>
                      <div class="date-soft">
                        <i class="ri-checkbox-circle-line"></i>
                        <span><?= formatoFechaTexto($estacion['fecha_fin'] ?? '') ?></span>
                      </div>
                    </td>

                    <td>
                      <div class="table-icon-text">
                        <i class="ri-timer-line"></i>
                        <div>
                          <strong><?= hDefault($estacion['tiempo_real_formato'] ?? '') ?></strong>
                          <span>Estándar: <?= hDefault($estacion['tiempo_estandar_formato'] ?? '') ?></span>
                        </div>
                      </div>
                    </td>

                    <td>
                      <?php if ((int)($estacion['calidad'] ?? 0) === 2) { ?>
                        <?= badgeEstado('Inspección aplicada', 'info') ?>
                      <?php } else { ?>
                        <?= badgeEstado('No aplica', 'ok') ?>
                      <?php } ?>
                    </td>

                    <td>
                      <?php if ((int)($estacion['estampado'] ?? 0) === 2) { ?>
                        <?= badgeEstado('VIN asignado', 'info') ?>
                      <?php } else { ?>
                        <?= badgeEstado('No aplica', 'ok') ?>
                      <?php } ?>
                    </td>

                    <td>
                      <?php if ((int)($estacion['especificaciones_criticas'] ?? 0) === 2) { ?>
                        <?= badgeEstado('Aplicadas', 'warn') ?>
                      <?php } else { ?>
                        <?= badgeEstado('No aplica', 'ok') ?>
                      <?php } ?>
                    </td>

                    <td>
                      <?= badgeEstado($estacion['estado_texto'] ?? 'N/A', 'ok') ?>
                    </td>
                  </tr>
                <?php } ?>
              <?php } else { ?>
                <tr>
                  <td colspan="11">No se encontraron estaciones para esta unidad.</td>
                </tr>
              <?php } ?>
            </tbody>
          </table>
        </div>
      </div>

      <div class="section">
        <div class="section-title">
          <i class="ri-history-line"></i>
          Historial de eventos
        </div>

        <div class="event-list">
          <?php if (!empty($eventos)) { ?>
            <?php foreach ($eventos as $evento) { ?>

              <?php
                $icon = 'ri-information-line';

                if (($evento['tipo'] ?? '') === 'inicio_estacion') {
                    $icon = 'ri-play-circle-line';
                } elseif (($evento['tipo'] ?? '') === 'fin_estacion') {
                    $icon = 'ri-checkbox-circle-line';
                } elseif (($evento['tipo'] ?? '') === 'calidad') {
                    $icon = 'ri-shield-check-line';
                } elseif (($evento['tipo'] ?? '') === 'vin') {
                    $icon = 'ri-fingerprint-line';
                }
              ?>

              <div class="event-card">
                <div class="event-icon">
                  <i class="<?= h($icon) ?>"></i>
                </div>

                <div>
                  <h4><?= hDefault($evento['titulo'] ?? '') ?></h4>
                  <p>
                    <?= hDefault($evento['descripcion'] ?? '') ?>
                    <br>
                    <i class="ri-user-line"></i>
                    Usuario:
                    <strong><?= hDefault($evento['usuario'] ?? '') ?></strong>
                  </p>
                </div>

                <div class="event-date">
                  <i class="ri-time-line"></i>
                  <?= formatoFechaTexto($evento['fecha'] ?? '') ?>
                </div>
              </div>

            <?php } ?>
          <?php } else { ?>

            <div class="event-card">
              <div class="event-icon">
                <i class="ri-information-line"></i>
              </div>

              <div>
                <h4>Sin eventos</h4>
                <p>No se encontraron eventos registrados para esta unidad.</p>
              </div>

              <div class="event-date">
                <i class="ri-time-line"></i>
                N/A
              </div>
            </div>

          <?php } ?>
        </div>
      </div>

    </div>

    <div class="footer">
      <div>
        <i class="ri-car-line"></i>
        Sistema MRP · Manufacturing Execution System · Reporte de trazabilidad de producto terminado
      </div>

      <div>
        <i class="ri-calendar-check-line"></i>
        Fecha de generación: <?= formatoFechaTexto(date('Y-m-d H:i:s')) ?>
      </div>
    </div>

  </div>
</div>

</body>
</html>