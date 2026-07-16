<?php headerAdmin($data); ?>

<?php
function esc($value)
{
  return htmlspecialchars((string) ($value ?? ''), ENT_QUOTES, 'UTF-8');
}

function badgePrioridad($prioridad)
{
  $prioridad = strtoupper(trim((string) $prioridad));

  switch ($prioridad) {
    case 'ALTA':
      return '<span class="badge rounded-pill bg-danger-subtle text-danger border border-danger-subtle">Alta</span>';
    case 'MEDIA':
      return '<span class="badge rounded-pill bg-warning-subtle text-warning border border-warning-subtle">Media</span>';
    case 'BAJA':
      return '<span class="badge rounded-pill bg-success-subtle text-success border border-success-subtle">Baja</span>';
    default:
      return '<span class="badge rounded-pill bg-secondary-subtle text-body border">N/D</span>';
  }
}

function badgeEstadoOrden($estatus)
{
  $estatus = (int) $estatus;

  switch ($estatus) {
    case 1:
      return '<span class="badge rounded-pill bg-warning-subtle text-warning border border-warning-subtle">Pendiente</span>';
    case 2:
      return '<span class="badge rounded-pill bg-info-subtle text-info border border-info-subtle">En proceso</span>';
    case 3:
      return '<span class="badge rounded-pill bg-success-subtle text-success border border-success-subtle">Finalizada</span>';
    case 4:
      return '<span class="badge rounded-pill bg-danger-subtle text-danger border border-danger-subtle">Detenida</span>';
    default:
      return '<span class="badge rounded-pill bg-secondary-subtle text-body border">Sin estado</span>';
  }
}

$resp = $data['arrOrdenDetalle'] ?? [];
// dep($resp);
$ok = !empty($resp) && (int) ($resp['status'] ?? 0) === 1;
$ot = $ok ? ($resp['data'] ?? []) : [];
$estaciones = $ot['estaciones'] ?? [];

$estacionAsignada = $estaciones[0] ?? [];
$unidadActual = $estacionAsignada['ordenes_trabajo'][0] ?? [];
$encargadoAsignado = $estacionAsignada['encargados'][0]['nombre_completo'] ?? '-';
$ayudanteAsignado = $estacionAsignada['ayudantes'][0]['nombre_completo'] ?? '-';

$totalEstaciones = count($estaciones);
$totalSubensambles = 0;
$totalPendientes = 0;
$totalEnProceso = 0;
$totalFinalizadas = 0;


$tiemposParoDemo = [
  ['unidad' => 'OT260514-003-S01', 'motivo' => 'Paro manual', 'tiempo' => '00:12:40']
];



$unidadesFueraLineaDemo = [
  ['unidad' => 'OT260514-003-S03', 'motivo' => 'Falla crítica', 'estatus' => 'Fuera de línea'],
  ['unidad' => 'OT260514-003-S04', 'motivo' => 'Retrabajo', 'estatus' => 'Pendiente'],
];

foreach ($estaciones as $est) {
  $totalSubensambles += count($est['subensambles'] ?? []);

  foreach (($est['ordenes_trabajo'] ?? []) as $ord) {
    $estatus = (int) ($ord['estatus'] ?? 0);
    if ($estatus === 1)
      $totalPendientes++;
    if ($estatus === 2)
      $totalEnProceso++;
    if ($estatus === 3)
      $totalFinalizadas++;
  }

  foreach (($est['subensambles'] ?? []) as $sub) {
    foreach (($sub['ordenes_trabajo'] ?? []) as $ordSub) {
      $estatus = (int) ($ordSub['estado'] ?? 0);
      if ($estatus === 1)
        $totalPendientes++;
      if ($estatus === 2)
        $totalEnProceso++;
      if ($estatus === 3)
        $totalFinalizadas++;
    }
  }
}
?>

<style>
  .mrp-shell {
    --mrp-bg-1: #0b1220;
    --mrp-bg-2: #111a2e;
    --mrp-bg-3: #16233d;
    --mrp-border: rgba(148, 163, 184, .14);
    --mrp-text: #e5eefc;
    --mrp-text-soft: #9fb1cc;
    --mrp-title: #ffffff;
    --mrp-card-bg: linear-gradient(180deg, rgba(18, 28, 49, .92), rgba(15, 23, 42, .84));
    --mrp-card-bg-soft: linear-gradient(180deg, rgba(255, 255, 255, .04), rgba(255, 255, 255, .02));
    --mrp-card-border: 1px solid rgba(148, 163, 184, .14);
    --mrp-chip-bg: rgba(15, 23, 42, .78);
    --mrp-chip-text: #dbeafe;
    --mrp-info-bg: rgba(255, 255, 255, .03);
    --mrp-info-text: #d8e2f2;
    --mrp-order-bg: rgba(255, 255, 255, .025);
    --mrp-order-text: #b5c4da;
    --mrp-shadow: 0 10px 24px rgba(0, 0, 0, .22);
    --mrp-shadow-lg: 0 15px 34px rgba(0, 0, 0, .25);
  }

  [data-bs-theme="light"] .mrp-shell {
    --mrp-bg-1: #f4f6fb;
    --mrp-bg-2: #ffffff;
    --mrp-bg-3: #eef2f7;
    --mrp-border: rgba(15, 23, 42, .08);
    --mrp-text: #334155;
    --mrp-text-soft: #64748b;
    --mrp-title: #0f172a;
    --mrp-card-bg: #ffffff;
    --mrp-card-bg-soft: linear-gradient(180deg, #ffffff, #f8fafc);
    --mrp-card-border: 1px solid rgba(15, 23, 42, .08);
    --mrp-chip-bg: #f1f5f9;
    --mrp-chip-text: #334155;
    --mrp-info-bg: #f8fafc;
    --mrp-info-text: #334155;
    --mrp-order-bg: #f8fafc;
    --mrp-order-text: #475569;
    --mrp-shadow: 0 6px 16px rgba(15, 23, 42, .06);
    --mrp-shadow-lg: 0 10px 24px rgba(15, 23, 42, .06);
  }

  .mrp-shell {
    color: var(--mrp-text);
  }

  .mrp-dark-wrap {
    background:
      radial-gradient(circle at top left, rgba(59, 130, 246, .16), transparent 28%),
      radial-gradient(circle at top right, rgba(6, 182, 212, .10), transparent 22%),
      linear-gradient(180deg, var(--mrp-bg-1) 0%, var(--mrp-bg-2) 52%, var(--mrp-bg-3) 100%);
    border: var(--mrp-card-border);
    border-radius: 24px;
    padding: 18px;
    box-shadow: var(--mrp-shadow-lg);
    overflow: hidden;
    position: relative;
  }

  .mrp-dark-wrap::before {
    content: "";
    position: absolute;
    inset: 0;
    background: linear-gradient(135deg, rgba(255, 255, 255, .02), transparent 30%, transparent 70%, rgba(255, 255, 255, .02));
    pointer-events: none;
  }

  .mrp-grid {
    display: grid;
    grid-template-columns: 680px minmax(0, 1fr);
    gap: 24px;
    align-items: start;
  }

  .mrp-panel,
  .mrp-kpi,
  .mrp-station-card,
  .mrp-sub-card,
  .mrp-mini-card,
  .mrp-actions-card,
  .mrp-legend.compact-legend,
  .mrp-flow-card {
    background: var(--mrp-card-bg);
    border: var(--mrp-card-border);
    box-shadow: var(--mrp-shadow);
    backdrop-filter: blur(10px);
  }

  .mrp-panel {
    border-radius: 22px;
    padding: 20px;
  }

  .mrp-panel-header {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 12px;
    margin-bottom: 18px;
  }

  .mrp-panel-title,
  .mrp-flow-title,
  .mrp-station-title,
  .mrp-sub-title,
  .mrp-kpi .kpi-value,
  .mrp-vin,
  .mrp-order-id,
  .mrp-mini-card .value {
    color: var(--mrp-title);
  }

  .mrp-panel-title {
    font-size: 1.6rem;
    line-height: 1.1;
    font-weight: 800;
    letter-spacing: -.02em;
    margin: 0;
    word-break: break-word;
  }

  .mrp-panel-subtitle,
  .mrp-flow-subtitle,
  .mrp-station-text,
  .mrp-sub-text,
  .mrp-kpi .kpi-desc,
  .mrp-order-desc,
  .mrp-actions-card p,
  .mrp-legend-item {
    color: var(--mrp-text-soft);
  }

  .mrp-panel-subtitle {
    font-size: .92rem;
    line-height: 1.5;
    margin-top: 8px;
    word-break: break-word;
  }

  .mrp-pill-live {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    border-radius: 999px;
    padding: 8px 14px;
    font-size: .78rem;
    font-weight: 700;
    color: #d1fae5;
    background: rgba(34, 197, 94, .16);
    border: 1px solid rgba(34, 197, 94, .34);
    white-space: nowrap;
  }

  .mrp-pill-live::before {
    content: "";
    width: 8px;
    height: 8px;
    border-radius: 50%;
    background: #22c55e;
    box-shadow: 0 0 12px #22c55e;
  }

  .mrp-btn-grid-top {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 10px;
    margin-bottom: 16px;
  }

  .mrp-btn-main,
  .mrp-btn-secondary,
  .mrp-btn-dark,
  .mrp-chip-btn {
    border: 0;
    border-radius: 16px;
    font-weight: 700;
    transition: .2s ease;
  }

  .mrp-btn-main {
    padding: 14px 12px;
    color: #fff;
    background: linear-gradient(135deg, #ff8a1f, #ff5f1f);
    box-shadow: 0 10px 24px rgba(255, 106, 31, .22);
  }

  .mrp-btn-secondary {
    padding: 14px 12px;
    color: #d1fae5;
    background: linear-gradient(135deg, rgba(34, 197, 94, .28), rgba(22, 163, 74, .30));
    border: 1px solid rgba(34, 197, 94, .24);
  }

  [data-bs-theme="light"] .mrp-btn-secondary {
    color: #fff;
    background: linear-gradient(135deg, #22c55e, #16a34a);
  }

  .mrp-btn-dark {
    padding: 12px 10px;
    color: var(--mrp-chip-text);
    background: var(--mrp-chip-bg);
    border: 1px solid rgba(148, 163, 184, .18);
    font-size: .82rem;
    line-height: 1.15;
    text-align: center;
    white-space: normal;
    word-break: break-word;
  }

  .mrp-btn-main:hover,
  .mrp-btn-secondary:hover,
  .mrp-btn-dark:hover,
  .mrp-chip-btn:hover {
    transform: translateY(-1px);
    filter: brightness(1.04);
  }

  .mrp-panel-layout {
    display: grid;
    grid-template-columns: .95fr 1.05fr;
    gap: 16px;
    align-items: start;
  }

  .mrp-panel-left,
  .mrp-panel-right {
    min-width: 0;
  }

  .mrp-side-stack {
    display: grid;
    gap: 14px;
  }

  .mrp-current-unit,
  .mrp-actions-card,
  .mrp-legend.compact-legend {
    border-radius: 18px;
    padding: 16px;
  }

  .mrp-current-unit {
    background: var(--mrp-card-bg-soft);
    border: var(--mrp-card-border);
    margin-bottom: 14px;
  }

  .mrp-label {
    color: var(--mrp-text-soft);
    text-transform: uppercase;
    font-size: .72rem;
    font-weight: 800;
    letter-spacing: .08em;
  }

  .mrp-vin {
    font-size: 1.55rem;
    font-weight: 800;
    line-height: 1.15;
    margin: 8px 0 10px;
    word-break: break-word;
  }

  .mrp-status-soft {
    display: inline-flex;
    align-items: center;
    border-radius: 999px;
    padding: 7px 12px;
    font-size: .78rem;
    font-weight: 700;
    background: rgba(59, 130, 246, .14);
    color: #60a5fa;
    border: 1px solid rgba(59, 130, 246, .25);
  }

  [data-bs-theme="light"] .mrp-status-soft {
    color: #2563eb;
    background: rgba(59, 130, 246, .10);
    border-color: rgba(59, 130, 246, .16);
  }

  .mrp-current-unit #detalleResumenUnidad {
    color: var(--mrp-text);
    line-height: 1.65;
    word-break: break-word;
  }

  .mrp-panel-data-grid,
  .mrp-stat-grid,
  .mrp-btn-grid,
  .mrp-station-actions {
    display: grid;
    gap: 12px;
  }

  .mrp-panel-data-grid,
  .mrp-stat-grid {
    grid-template-columns: repeat(2, minmax(0, 1fr));
  }

  .mrp-btn-grid {
    grid-template-columns: repeat(2, minmax(0, 1fr));
    margin-top: 0;
  }

  .mrp-mini-card {
    border-radius: 16px;
    padding: 14px;
    min-height: 98px;
    overflow: hidden;
  }

  .mrp-mini-card .value {
    font-size: 1rem;
    font-weight: 800;
    line-height: 1.35;
    margin-top: 6px;
    word-break: break-word;
    overflow-wrap: anywhere;
  }

  .mrp-actions-card h6 {
    color: #ffb86b;
    font-size: .95rem;
    font-weight: 800;
    margin-bottom: 12px;
    text-transform: uppercase;
    letter-spacing: .04em;
  }

  .mrp-actions-card p {
    margin-bottom: 0;
    line-height: 1.7;
    word-break: break-word;
    overflow-wrap: anywhere;
  }

  .mrp-legend.compact-legend {
    margin-top: 0;
  }

  .mrp-legend {
    display: grid;
    gap: 8px;
  }

  .mrp-legend-item {
    display: flex;
    align-items: center;
    gap: 10px;
    font-size: .88rem;
  }

  .mrp-dot {
    width: 11px;
    height: 11px;
    border-radius: 50%;
    flex-shrink: 0;
  }

  .dot-station {
    background: #fb923c;
  }

  .dot-ready {
    background: #22c55e;
  }

  .dot-working {
    background: #38bdf8;
  }

  .dot-done {
    background: #16a34a;
  }

  .dot-blocked {
    background: #ef4444;
  }

  .dot-waiting {
    background: #f59e0b;
  }


  .mrp-content {
    min-width: 0;
    padding-left: 8px;
  }

  .mrp-top {
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: 14px;
    margin-bottom: 16px;
  }

  .mrp-kpi {
    border-radius: 18px;
    padding: 16px 12px;
    min-height: 110px;
    position: relative;
    overflow: hidden;
  }

  .mrp-kpi::after {
    content: "";
    position: absolute;
    width: 110px;
    height: 110px;
    right: -35px;
    top: -35px;
    border-radius: 50%;
    background: radial-gradient(circle, rgba(59, 130, 246, .18), transparent 60%);
  }

  [data-bs-theme="light"] .mrp-kpi::after {
    background: radial-gradient(circle, rgba(59, 130, 246, .08), transparent 60%);
  }

  .mrp-kpi .kpi-title {
    color: var(--mrp-text-soft);
    font-size: .78rem;
    text-transform: uppercase;
    letter-spacing: .08em;
    font-weight: 800;
    margin-bottom: 10px;
  }

  .mrp-kpi .kpi-value {
    font-size: 2rem;
    line-height: 1;
    font-weight: 900;
    margin-bottom: 6px;
    word-break: break-word;
  }

  .mrp-kpi .kpi-desc {
    font-size: .9rem;
    line-height: 1.5;
    word-break: break-word;
  }

  .mrp-flow-card {
    border-radius: 24px;
    padding: 18px;
  }

  .mrp-flow-header {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 12px;
    margin-bottom: 16px;
  }

  .mrp-flow-title {
    font-size: 1.05rem;
    font-weight: 800;
    margin: 0 0 4px;
  }

  .mrp-flow-subtitle {
    margin: 0;
    line-height: 1.6;
    word-break: break-word;
  }

  .mrp-collapse-btn {
    border: 1px solid rgba(148, 163, 184, .16);
    background: var(--mrp-chip-bg);
    color: var(--mrp-chip-text);
    border-radius: 14px;
    padding: 9px 14px;
    font-weight: 700;
  }

  [data-bs-theme="light"] .mrp-collapse-btn {
    border-color: #dbe2ea;
  }

  .mrp-scroll {
    overflow-x: auto;
    overflow-y: hidden;
    padding-bottom: 10px;
  }

  .mrp-scroll::-webkit-scrollbar {
    height: 10px;
  }

  .mrp-scroll::-webkit-scrollbar-thumb {
    background: rgba(148, 163, 184, .25);
    border-radius: 999px;
  }

  [data-bs-theme="light"] .mrp-scroll::-webkit-scrollbar-thumb {
    background: rgba(100, 116, 139, .28);
  }

  /* .mrp-flow-row {
    display: flex;
    align-items: flex-end;
    gap: 14px;
    min-width: max-content;
    padding-top: 4px;
  } */

  .mrp-sub-zone.is-collapsed {
    height: 0 !important;
    min-height: 0 !important;
    margin: 0 !important;
    overflow: hidden;
    visibility: hidden;
  }

  .mrp-sub-card,
  .mrp-station-card {
    border-radius: 22px;
    padding: 16px;
    overflow: hidden;
  }

  .mrp-sub-card {
    border: 1px solid rgba(139, 92, 246, .32);
    position: relative;
  }

  [data-bs-theme="light"] .mrp-sub-card {
    border-color: rgba(139, 92, 246, .20);
    background: #fcfaff;
  }

  .mrp-sub-card::after {
    content: "";
    position: absolute;
    bottom: -18px;
    left: 50%;
    width: 2px;
    height: 18px;
    transform: translateX(-50%);
    background: linear-gradient(180deg, rgba(139, 92, 246, .75), rgba(56, 189, 248, .75));
    border-radius: 999px;
  }



  .mrp-station-card.active-station,
  .mrp-sub-card.active-station {
    border: 1px solid rgba(251, 146, 60, .32);
    box-shadow:
      inset 0 1px 0 rgba(255, 255, 255, .03),
      0 0 0 1px rgba(251, 146, 60, .12),
      0 16px 34px rgba(0, 0, 0, .18);
  }

  .mrp-station-top,
  .mrp-sub-top {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 10px;
    margin-bottom: 10px;
  }

  .mrp-station-number {
    width: 34px;
    height: 34px;
    border-radius: 12px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    background: rgba(148, 163, 184, .16);
    color: var(--mrp-title);
    font-weight: 900;
    flex-shrink: 0;
  }

  [data-bs-theme="light"] .mrp-station-number {
    background: #e2e8f0;
  }

  .mrp-chip {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 6px 10px;
    border-radius: 999px;
    font-size: .73rem;
    font-weight: 800;
    letter-spacing: .02em;
    white-space: nowrap;
  }

  .chip-sub-id {
    color: #7c3aed;
    background: rgba(139, 92, 246, .14);
    border: 1px solid rgba(139, 92, 246, .22);
  }

  .chip-linked {
    color: #0891b2;
    background: rgba(6, 182, 212, .12);
    border: 1px solid rgba(6, 182, 212, .18);
  }

  .chip-wait {
    color: var(--mrp-chip-text);
    background: var(--mrp-chip-bg);
    border: 1px solid rgba(148, 163, 184, .18);
  }

  .mrp-badge-assigned {
    color: #fed7aa;
    background: rgba(251, 146, 60, .13);
    border: 1px solid rgba(251, 146, 60, .22);
    border-radius: 999px;
    font-size: .72rem;
    font-weight: 800;
    padding: 6px 10px;
  }

  .mrp-sub-title,
  .mrp-station-title {
    font-weight: 800;
    line-height: 1.25;
    font-size: 1.02rem;
    margin-bottom: 6px;
    word-break: break-word;
    overflow-wrap: anywhere;
  }

  .mrp-sub-text,
  .mrp-station-text {
    line-height: 1.6;
    min-height: 42px;
    font-size: .93rem;
    word-break: break-word;
    overflow-wrap: anywhere;
  }

  .mrp-info-stack {
    display: grid;
    gap: 8px;
    margin-top: 12px;
  }

  .mrp-info-pill {
    border-radius: 12px;
    padding: 10px 12px;
    background: var(--mrp-info-bg);
    border: 1px solid rgba(148, 163, 184, .12);
    color: var(--mrp-info-text);
    font-size: .88rem;
    line-height: 1.5;
    word-break: break-word;
    overflow-wrap: anywhere;
  }

  .mrp-order-box {
    margin-top: 14px;
    border-radius: 16px;
    padding: 14px;
    background: var(--mrp-order-bg);
    border: 1px dashed rgba(148, 163, 184, .18);
    min-height: 132px;
  }

  .mrp-order-box.has-unit {
    border-style: solid;
    border-color: rgba(56, 189, 248, .24);
    background: linear-gradient(180deg, rgba(14, 165, 233, .08), rgba(30, 41, 59, .22));
  }

  [data-bs-theme="light"] .mrp-order-box.has-unit {
    background: linear-gradient(180deg, rgba(224, 242, 254, 1), rgba(248, 250, 252, 1));
  }

  .mrp-order-id {
    font-size: 1rem;
    font-weight: 900;
    line-height: 1.2;
    margin-bottom: 10px;
    word-break: break-word;
  }

  .mrp-order-desc {
    line-height: 1.6;
    margin-bottom: 0;
    word-break: break-word;
    overflow-wrap: anywhere;
  }

  .mrp-arrow {
    width: 34px;
    flex: 0 0 34px;
    display: flex;
    justify-content: center;
    position: relative;
    margin-top: 620px;
  }

  .mrp-arrow::before {
    content: "";
    position: absolute;
    width: 100%;
    height: 2px;
    background: linear-gradient(90deg, rgba(56, 189, 248, .35), rgba(56, 189, 248, .95));
    border-radius: 999px;
  }

  .mrp-arrow::after {
    content: "";
    width: 10px;
    height: 10px;
    border-top: 2px solid rgba(125, 211, 252, .95);
    border-right: 2px solid rgba(125, 211, 252, .95);
    transform: rotate(45deg);
    position: absolute;
    right: 0;
    background: transparent;
  }

  [data-bs-theme="light"] .mrp-arrow::before {
    background: linear-gradient(90deg, #94a3b8, #64748b);
  }

  [data-bs-theme="light"] .mrp-arrow::after {
    border-top-color: #64748b;
    border-right-color: #64748b;
  }

  .mrp-soft-note {
    border-radius: 16px;
    padding: 12px 14px;
    background: rgba(56, 189, 248, .08);
    border: 1px solid rgba(56, 189, 248, .16);
    color: #cce7f6;
    font-size: .92rem;
    margin-top: 14px;
    line-height: 1.6;
  }

  [data-bs-theme="light"] .mrp-soft-note {
    background: #e0f2fe;
    border-color: #bae6fd;
    color: #0369a1;
  }

  @media (max-width: 1550px) {
    .mrp-grid {
      grid-template-columns: 1fr;
    }

    .mrp-content {
      padding-left: 0;
    }
  }

  @media (max-width: 1400px) {
    .mrp-top {
      grid-template-columns: repeat(2, minmax(0, 1fr));
    }

    .mrp-panel-layout {
      grid-template-columns: 1fr;
    }
  }

  @media (max-width: 768px) {

    .mrp-top,
    .mrp-panel-data-grid,
    .mrp-stat-grid,
    .mrp-btn-grid,
    .mrp-btn-grid-top {
      grid-template-columns: 1fr;
    }
  }



  .mrp-semaforo {
    display: flex;
    align-items: flex-start;
    gap: 10px;
    border-radius: 14px;
    padding: 12px;
    border: 1px solid rgba(148, 163, 184, .18);
    background: rgba(15, 23, 42, .45);
    color: var(--mrp-text);
  }

  .mrp-semaforo-dot {
    width: 12px;
    height: 12px;
    border-radius: 999px;
    margin-top: 4px;
    flex-shrink: 0;
  }

  .semaforo-listo {
    border-color: rgba(34, 197, 94, .35);
    background: rgba(34, 197, 94, .10);
  }

  .semaforo-listo .mrp-semaforo-dot {
    background: #22c55e;
    box-shadow: 0 0 14px rgba(34, 197, 94, .8);
  }

  .semaforo-proceso {
    border-color: rgba(56, 189, 248, .35);
    background: rgba(56, 189, 248, .10);
  }

  .semaforo-proceso .mrp-semaforo-dot {
    background: #38bdf8;
    box-shadow: 0 0 14px rgba(56, 189, 248, .8);
  }

  .semaforo-bloqueado {
    border-color: rgba(239, 68, 68, .35);
    background: rgba(239, 68, 68, .10);
  }

  .semaforo-bloqueado .mrp-semaforo-dot {
    background: #ef4444;
    box-shadow: 0 0 14px rgba(239, 68, 68, .8);
  }

  .semaforo-espera {
    border-color: rgba(245, 158, 11, .35);
    background: rgba(245, 158, 11, .10);
  }

  .semaforo-espera .mrp-semaforo-dot {
    background: #f59e0b;
    box-shadow: 0 0 14px rgba(245, 158, 11, .8);
  }

  [data-bs-theme="light"] .mrp-semaforo {
    background: #f8fafc;
    color: #334155;
  }



  .mrp-order-box.is-ready {
    border-color: rgba(34, 197, 94, .55) !important;
    background: linear-gradient(180deg, rgba(34, 197, 94, .15), rgba(20, 83, 45, .14)) !important;
    box-shadow: 0 0 0 1px rgba(34, 197, 94, .15);
  }

  .mrp-order-box.is-working {
    border-color: rgba(56, 189, 248, .65) !important;
    background: linear-gradient(180deg, rgba(14, 165, 233, .20), rgba(30, 41, 59, .24)) !important;
    box-shadow: 0 0 0 1px rgba(56, 189, 248, .18), 0 10px 22px rgba(14, 165, 233, .16);
  }

  .mrp-order-box.is-done {
    border-color: rgba(34, 197, 94, .60) !important;
    background: linear-gradient(180deg, rgba(34, 197, 94, .18), rgba(20, 83, 45, .16)) !important;
  }

  .mrp-order-box.is-blocked {
    border-color: rgba(239, 68, 68, .60) !important;
    background: linear-gradient(180deg, rgba(239, 68, 68, .16), rgba(127, 29, 29, .18)) !important;
  }

  .mrp-order-box.is-waiting {
    border-color: rgba(245, 158, 11, .55) !important;
    background: linear-gradient(180deg, rgba(245, 158, 11, .14), rgba(120, 53, 15, .14)) !important;
  }

  [data-bs-theme="light"] .mrp-order-box.is-ready {
    background: #dcfce7 !important;
  }

  [data-bs-theme="light"] .mrp-order-box.is-working {
    background: #e0f2fe !important;
  }

  [data-bs-theme="light"] .mrp-order-box.is-done {
    background: #dcfce7 !important;
  }

  [data-bs-theme="light"] .mrp-order-box.is-blocked {
    background: #fee2e2 !important;
  }

  [data-bs-theme="light"] .mrp-order-box.is-waiting {
    background: #fef3c7 !important;
  }

  .mrp-btn-main:disabled,
  .mrp-btn-secondary:disabled,
  .mrp-btn-dark:disabled,
  .btn-state-disabled,
  .btn-state-working {
    opacity: .45 !important;
    filter: grayscale(.35) brightness(.75) !important;
    cursor: not-allowed !important;
    transform: none !important;
    box-shadow: none !important;
  }

  .btn-state-working {
    background: linear-gradient(135deg, rgba(56, 189, 248, .35), rgba(14, 165, 233, .30)) !important;
    color: #dbeafe !important;
  }

  .btn-state-ready {
    opacity: 1 !important;
    filter: none !important;
  }

  .autocomplete-usuarios {
    position: absolute;
    top: calc(100% + 6px);
    left: 0;
    right: 0;
    background: var(--vz-card-bg);
    border: 1px solid var(--vz-border-color);
    border-radius: 10px;
    z-index: 1080;
    max-height: 260px;
    overflow-y: auto;
  }

  .autocomplete-usuario-item {
    padding: 10px 12px;
    cursor: pointer;
    border-bottom: 1px solid var(--vz-border-color);
    transition: all .15s ease;
  }

  .autocomplete-usuario-item:last-child {
    border-bottom: 0;
  }

  .autocomplete-usuario-item:hover {
    background: var(--vz-light);
  }

  [data-layout-mode="dark"] .autocomplete-usuario-item:hover,
  [data-bs-theme="dark"] .autocomplete-usuario-item:hover {
    background: rgba(255, 255, 255, .06);
  }

  .autocomplete-usuario-name {
    font-size: 13px;
    font-weight: 600;
    color: var(--vz-heading-color);
  }

  .autocomplete-usuario-meta {
    font-size: 12px;
    color: var(--vz-secondary-color);
  }

  /* EN ESTA PARTE SE AGREGAN LOS ESTILOS PARA OS NUEVOS CARDS  */
  .mrp-production-extra {
    display: grid;
    grid-template-columns: 1fr 1.1fr;
    gap: 14px;
    margin-bottom: 16px;
  }

  .mrp-production-left {
    display: grid;
    gap: 14px;
  }

  .mrp-extra-card {
    background: var(--mrp-card-bg);
    border: var(--mrp-card-border);
    box-shadow: var(--mrp-shadow);
    border-radius: 18px;
    padding: 16px;
    min-height: 150px;
  }

  .mrp-extra-card-lg {
    min-height: 314px;
  }

  .mrp-extra-title {
    color: var(--mrp-title);
    font-size: .95rem;
    font-weight: 900;
    text-transform: uppercase;
    letter-spacing: .04em;
    margin-bottom: 12px;
  }

  .mrp-extra-item {
    border-radius: 14px;
    padding: 10px 12px;
    background: var(--mrp-info-bg);
    border: 1px solid rgba(148, 163, 184, .12);
    color: var(--mrp-info-text);
    margin-bottom: 8px;
    font-size: .88rem;
    line-height: 1.5;
  }

  .mrp-extra-item:last-child {
    margin-bottom: 0;
  }

  .mrp-extra-main {
    color: var(--mrp-title);
    font-weight: 800;
  }

  .mrp-extra-muted {
    color: var(--mrp-text-soft);
    font-size: .82rem;
  }

  @media (max-width: 992px) {
    .mrp-production-extra {
      grid-template-columns: 1fr;
    }

    .mrp-extra-card-lg {
      min-height: auto;
    }
  }

  /* NUEVA DISTRIBUCIÓN HORIZONTAL */
  .mrp-dashboard-horizontal {
    display: grid;
    grid-template-columns: 42% 58%;
    gap: 14px;
    margin-bottom: 16px;
    align-items: stretch;
  }

  .mrp-production-extra {
    display: flex;
    flex-direction: column;
    gap: 14px;
    align-items: stretch;
    margin-bottom: 16px;
  }

  .mrp-extra-card {
    background: var(--mrp-card-bg);
    border: var(--mrp-card-border);
    box-shadow: var(--mrp-shadow);
    border-radius: 18px;
    padding: 16px;
    min-height: 180px;
  }

  .mrp-extra-card-full {
    grid-column: 1 / -1;
  }

  .mrp-flow-card {
    min-width: 0;
    height: 100%;
  }

  .mrp-scroll {
    overflow-x: auto !important;
    overflow-y: hidden !important;
    padding-bottom: 10px;
  }

  /* .mrp-flow-row {
    min-width: max-content;
  } */

  @media (max-width: 1400px) {
    .mrp-dashboard-horizontal {
      grid-template-columns: 1fr;
    }
  }

  /* =========================================
   PARO MOMENTÁNEO
========================================= */

  #contenedorParoMomentaneo {
    display: block;
    margin-bottom: 16px;
  }

  .mrp-paro-grid {
    display: grid;
    grid-template-columns: 1fr 320px;
    gap: 14px;
    align-items: stretch;
  }

  .mrp-card-paro {
    background: linear-gradient(180deg,
        rgba(127, 29, 29, .96),
        rgba(69, 10, 10, .92));
    border: 1px solid rgba(239, 68, 68, .28);
    box-shadow: 0 10px 30px rgba(0, 0, 0, .25);
    border-radius: 20px;
    padding: 22px;
    position: relative;
    overflow: hidden;
  }

  .mrp-card-paro::after {
    content: "";
    position: absolute;
    width: 180px;
    height: 180px;
    right: -60px;
    top: -60px;
    border-radius: 50%;
    background: radial-gradient(circle,
        rgba(239, 68, 68, .18),
        transparent 70%);
  }

  .mrp-paro-label {
    color: #fecaca;
    text-transform: uppercase;
    font-size: .78rem;
    font-weight: 800;
    letter-spacing: .08em;
    margin-bottom: 10px;
  }

  .mrp-paro-timer {
    font-size: 3.5rem;
    font-weight: 900;
    line-height: 1;
    color: #fff;
    margin-bottom: 14px;
    letter-spacing: .04em;
  }

  .mrp-paro-text {
    color: #fecaca;
    font-size: .95rem;
    line-height: 1.6;
    max-width: 90%;
  }

  /* LIGHT MODE / VELZON */
  [data-bs-theme="light"] .mrp-card-paro,
  [data-layout-mode="light"] .mrp-card-paro {
    background: linear-gradient(180deg,
        #ffffff,
        #f8fafc);
    border: 1px solid rgba(15, 23, 42, .08);
    box-shadow: 0 8px 22px rgba(15, 23, 42, .08);
  }

  [data-bs-theme="light"] .mrp-card-paro::after,
  [data-layout-mode="light"] .mrp-card-paro::after {
    background: radial-gradient(circle,
        rgba(239, 68, 68, .08),
        transparent 70%);
  }

  [data-bs-theme="light"] .mrp-paro-label,
  [data-layout-mode="light"] .mrp-paro-label {
    color: #b91c1c;
  }

  [data-bs-theme="light"] .mrp-paro-timer,
  [data-layout-mode="light"] .mrp-paro-timer {
    color: #0f172a;
  }

  [data-bs-theme="light"] .mrp-paro-text,
  [data-layout-mode="light"] .mrp-paro-text {
    color: #334155;
  }

  /* CARD REANUDAR */
  .mrp-card-reanudar {
    background: var(--mrp-card-bg);
    border: var(--mrp-card-border);
    box-shadow: var(--mrp-shadow);
    border-radius: 20px;
    padding: 22px;
    display: flex;
    flex-direction: column;
    justify-content: center;
  }

  .mrp-reanudar-title {
    color: var(--mrp-title);
    font-size: 1rem;
    font-weight: 800;
    margin-bottom: 10px;
  }

  .mrp-reanudar-text {
    color: var(--mrp-text-soft);
    font-size: .88rem;
    line-height: 1.6;
    margin-bottom: 18px;
  }

  .mrp-btn-reanudar {
    width: 100%;
    border: 0;
    border-radius: 16px;
    padding: 16px;
    font-size: .95rem;
    font-weight: 800;
    color: #fff;
    background: linear-gradient(135deg,
        #22c55e,
        #16a34a);
    box-shadow: 0 10px 24px rgba(34, 197, 94, .24);
    transition: .2s ease;
  }

  .mrp-btn-reanudar:hover {
    transform: translateY(-1px);
    filter: brightness(1.05);
  }

  @media (max-width: 1200px) {
    .mrp-paro-grid {
      grid-template-columns: 1fr;
    }
  }

  .mrp-order-box.is-ready {
    border: 1px solid #22c55e;
    background: rgba(34, 197, 94, .10);
  }

  .mrp-order-box.is-working {
    border: 1px solid #0ea5e9;
    background: rgba(14, 165, 233, .10);
  }

  .mrp-order-box.is-done {
    border: 1px solid #16a34a;
    background: rgba(22, 163, 74, .18);
  }

  .mrp-order-box.is-blocked {
    border: 1px solid #ef4444;
    background: rgba(239, 68, 68, .12);
  }

  .mrp-order-box.is-empty {
    border: 1px solid #a16207;
    background: rgba(161, 98, 7, .10);
  }

  .mrp-order-box.is-waiting {
    border: 1px solid #64748b;
    background: rgba(100, 116, 139, .10);
  }

  .mrp-context-card {
    display: flex;
    align-items: flex-start;
    gap: 12px;
    width: 100%;
    min-height: 74px;
    padding: 14px 16px;
    margin: 14px 0 18px 0;
    border-radius: 16px;
    border: 1px solid rgba(148, 163, 184, .20);
    background: rgba(15, 23, 42, .60);
    box-shadow: 0 10px 24px rgba(0, 0, 0, .16);
  }

  .mrp-context-icon {
    width: 34px;
    height: 34px;
    min-width: 34px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 18px;
    margin-top: 2px;
  }

  .mrp-context-body {
    flex: 1;
    min-width: 0;
  }

  .mrp-context-title {
    font-size: 11px;
    line-height: 1.2;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: .06em;
    margin-bottom: 6px;
  }

  .mrp-context-text {
    font-size: 13px;
    line-height: 1.45;
    font-weight: 600;
    word-break: normal;
  }

  [data-bs-theme="light"] .mrp-context-card {
    background: #ffffff;
    border-color: #e5e7eb;
    box-shadow: 0 8px 18px rgba(15, 23, 42, .08);
  }

  [data-bs-theme="light"] .mrp-context-text {
    color: #0f172a;
  }

  /* Verde */
  .mrp-context-card.context-ready {
    border-color: rgba(34, 197, 94, .45);
    background: linear-gradient(135deg, rgba(34, 197, 94, .16), rgba(15, 23, 42, .70));
  }

  .mrp-context-card.context-ready .mrp-context-icon {
    background: rgba(34, 197, 94, .20);
    color: #22c55e;
  }

  .mrp-context-card.context-ready .mrp-context-title,
  .mrp-context-card.context-ready .mrp-context-text {
    color: #d1fae5;
  }

  /* Azul */
  .mrp-context-card.context-working {
    border-color: rgba(14, 165, 233, .45);
    background: linear-gradient(135deg, rgba(14, 165, 233, .16), rgba(15, 23, 42, .70));
  }

  .mrp-context-card.context-working .mrp-context-icon {
    background: rgba(14, 165, 233, .20);
    color: #38bdf8;
  }

  .mrp-context-card.context-working .mrp-context-title,
  .mrp-context-card.context-working .mrp-context-text {
    color: #e0f2fe;
  }

  /* Rojo */
  .mrp-context-card.context-blocked {
    border-color: rgba(239, 68, 68, .45);
    background: linear-gradient(135deg, rgba(239, 68, 68, .16), rgba(15, 23, 42, .70));
  }

  .mrp-context-card.context-blocked .mrp-context-icon {
    background: rgba(239, 68, 68, .20);
    color: #f87171;
  }

  .mrp-context-card.context-blocked .mrp-context-title,
  .mrp-context-card.context-blocked .mrp-context-text {
    color: #fee2e2;
  }

  /* Amarillo / espera */
  .mrp-context-card.context-warning {
    border-color: rgba(245, 158, 11, .45);
    background: linear-gradient(135deg, rgba(245, 158, 11, .16), rgba(15, 23, 42, .70));
  }

  .mrp-context-card.context-warning .mrp-context-icon {
    background: rgba(245, 158, 11, .20);
    color: #fbbf24;
  }

  .mrp-context-card.context-warning .mrp-context-title,
  .mrp-context-card.context-warning .mrp-context-text {
    color: #fef3c7;
  }

  /* Completado */
  .mrp-context-card.context-done {
    border-color: rgba(16, 185, 129, .45);
    background: linear-gradient(135deg, rgba(16, 185, 129, .18), rgba(15, 23, 42, .70));
  }

  .mrp-context-card.context-done .mrp-context-icon {
    background: rgba(16, 185, 129, .22);
    color: #10b981;
  }

  .mrp-context-card.context-done .mrp-context-title,
  .mrp-context-card.context-done .mrp-context-text {
    color: #d1fae5;
  }


  .mrp-panel-data-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 12px;
    margin-top: 16px;
  }

  .mrp-mini-card {
    min-height: 86px;
    padding: 14px;
    border-radius: 16px;
  }


  .mrp-dot.dot-station {
    background: #fb923c;
    box-shadow: 0 0 12px rgba(251, 146, 60, .55);
  }

  .mrp-dot.dot-ready {
    background: #22c55e;
    box-shadow: 0 0 12px rgba(34, 197, 94, .55);
  }

  .mrp-dot.dot-working {
    background: #38bdf8;
    box-shadow: 0 0 12px rgba(56, 189, 248, .55);
  }

  .mrp-dot.dot-done {
    background: #16a34a;
    box-shadow: 0 0 12px rgba(22, 163, 74, .55);
  }

  .mrp-dot.dot-blocked {
    background: #ef4444;
    box-shadow: 0 0 12px rgba(239, 68, 68, .55);
  }

  .mrp-dot.dot-waiting {
    background: #a16207;
    box-shadow: 0 0 12px rgba(161, 98, 7, .55);
  }

  .mrp-dot.dot-alarm {
    background: #facc15;
    box-shadow: 0 0 12px rgba(250, 204, 21, .55);
  }


  /* ==============================
   SINCRONIZAR BORDE EXTERIOR
   CON ESTADO INTERIOR
============================== */

  /* LISTO / VERDE */
  .mrp-station-card:has(.mrp-order-box.is-ready),
  .mrp-sub-card:has(.mrp-order-box.is-ready) {
    border-color: rgba(34, 197, 94, .75) !important;
    box-shadow: 0 0 0 1px rgba(34, 197, 94, .25),
      0 0 24px rgba(34, 197, 94, .10);
  }

  /* TRABAJANDO / AZUL */
  .mrp-station-card:has(.mrp-order-box.is-working),
  .mrp-sub-card:has(.mrp-order-box.is-working) {
    border-color: rgba(56, 189, 248, .75) !important;
    box-shadow: 0 0 0 1px rgba(56, 189, 248, .25),
      0 0 24px rgba(56, 189, 248, .12);
  }

  /* FINALIZADA / VERDE FUERTE */
  .mrp-station-card:has(.mrp-order-box.is-done),
  .mrp-sub-card:has(.mrp-order-box.is-done) {
    border-color: rgba(22, 163, 74, .85) !important;
    box-shadow: 0 0 0 1px rgba(22, 163, 74, .30),
      0 0 26px rgba(22, 163, 74, .14);
  }

  /* BLOQUEADA / ROJO */
  .mrp-station-card:has(.mrp-order-box.is-blocked),
  .mrp-sub-card:has(.mrp-order-box.is-blocked) {
    border-color: rgba(239, 68, 68, .75) !important;
    box-shadow: 0 0 0 1px rgba(239, 68, 68, .25),
      0 0 24px rgba(239, 68, 68, .12);
  }

  /* SIN UNIDAD / CAFÉ */
  .mrp-station-card:has(.mrp-order-box.is-empty),
  .mrp-sub-card:has(.mrp-order-box.is-empty) {
    border-color: rgba(161, 98, 7, .75) !important;
    box-shadow: 0 0 0 1px rgba(161, 98, 7, .25),
      0 0 22px rgba(161, 98, 7, .10);
  }

  .mrp-order-box.is-alarm {
    border: 1px solid #facc15 !important;
    background: rgba(250, 204, 21, .14) !important;
  }

  .mrp-station-card:has(.mrp-order-box.is-alarm),
  .mrp-sub-card:has(.mrp-order-box.is-alarm) {
    border-color: rgba(250, 204, 21, .85) !important;
    box-shadow: 0 0 0 1px rgba(250, 204, 21, .30),
      0 0 26px rgba(250, 204, 21, .14);
  }

  .mrp-alarm-card {
    margin-top: 12px;
    padding: 14px;
    border-radius: 16px;
    border: 1px solid rgba(250, 204, 21, .45);
    background: linear-gradient(135deg, rgba(250, 204, 21, .14), rgba(15, 23, 42, .72));
    box-shadow: 0 10px 24px rgba(250, 204, 21, .08);
  }

  .mrp-alarm-icon {
    width: 34px;
    height: 34px;
    min-width: 34px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: rgba(250, 204, 21, .18);
    color: #facc15;
    font-size: 18px;
  }

  .mrp-alarm-title {
    font-size: 11px;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: .06em;
    color: #fef3c7;
    margin-bottom: 4px;
  }

  .mrp-alarm-text {
    font-size: 13px;
    line-height: 1.4;
    font-weight: 600;
    color: #fef9c3;
  }

  .mrp-alarm-meta {
    font-size: 11px;
    line-height: 1.5;
    color: #fde68a;
    background: rgba(250, 204, 21, .10);
    border-radius: 10px;
    padding: 8px 10px;
  }

  [data-bs-theme="light"] .mrp-alarm-card {
    background: #fffbeb;
    border-color: #facc15;
  }

  [data-bs-theme="light"] .mrp-alarm-title,
  [data-bs-theme="light"] .mrp-alarm-text,
  [data-bs-theme="light"] .mrp-alarm-meta {
    color: #78350f;
  }

  .btn-proceso-pendiente {
    border: 1px solid #ef4444 !important;
    box-shadow: 0 0 0 1px rgba(239, 68, 68, .22);
  }

  .btn-proceso-completado {
    border: 1px solid #22c55e !important;
    box-shadow: 0 0 0 1px rgba(34, 197, 94, .22);
  }

  .btn-proceso-pendiente:hover {
    border-color: #ef4444 !important;
  }

  .btn-proceso-completado:hover {
    border-color: #22c55e !important;
  }

  /* NUEVOS ESTILOS AGREGADOS */
  .mrp-flow-row {
    display: flex;
    align-items: flex-start;
    gap: 18px;
    min-width: max-content;
  }

  .mrp-station-wrap {
    width: 290px;
    min-width: 290px;
    display: grid;
    grid-template-rows: 430px auto;
    align-items: start;
  }

  /* Fila superior */
  .mrp-sub-zone {
    grid-row: 1;
    height: 430px;
    min-height: 430px;
    display: flex;
    flex-direction: column;
    gap: 14px;
    justify-content: flex-start;
    margin-bottom: 12px;
  }

  /* Fila inferior */
  .mrp-station-card {
    grid-row: 2;
    min-height: 395px;
  }


  .mrp-flow-row.has-sub-row .mrp-station-wrap:not(.has-visible-sub) .mrp-sub-zone {
    display: flex !important;
    visibility: hidden;
  }


  .mrp-flow-row.has-sub-row .mrp-station-wrap.has-visible-sub:not(.has-visible-station) .mrp-station-card {
    display: block !important;
    visibility: hidden;
    pointer-events: none;
  }

  .mrp-flow-row.only-stations-row {
    align-items: flex-start;
  }

  .mrp-flow-row.only-stations-row .mrp-station-wrap {
    display: block;
  }

  .mrp-flow-row.only-stations-row .mrp-sub-zone {
    display: none !important;
  }

  .mrp-sub-zone:empty {
    display: none;
  }

  .mrp-flow-row {
    display: flex;
    align-items: flex-end;
    gap: 18px;
    min-width: max-content;
  }

  /* ==========================================
   SIN SUBENSAMBLES
========================================== */

  .mrp-station-wrap:not(.has-visible-sub) .mrp-sub-zone {
    display: none !important;
  }

  /* ==========================================
   CONTRAER SUBENSAMBLES
========================================== */

  .mrp-station-wrap.subs-collapsed {
    grid-template-rows: 0 auto !important;
  }

  /* .mrp-sub-zone.is-collapsed + .mrp-station-card{
    margin-top:0 !important;
} */

  /* ==========================================
   FLECHAS
========================================== */

  /* .mrp-arrow{
    width:34px;
    flex:0 0 34px;

    display:flex;
    justify-content:center;

    position:relative;

    margin-top:335px;
} */

  .mrp-arrow::before {
    content: "";
    position: absolute;
    width: 100%;
    height: 2px;

    background: linear-gradient(90deg,
        rgba(56, 189, 248, .35),
        rgba(56, 189, 248, .95));

    border-radius: 999px;

    top: 50%;
    transform: translateY(-50%);
  }

  .mrp-arrow::after {
    content: "";

    width: 10px;
    height: 10px;

    border-top: 2px solid rgba(125, 211, 252, .95);
    border-right: 2px solid rgba(125, 211, 252, .95);

    transform: translateY(-50%) rotate(45deg);

    position: absolute;
    right: 0;
    top: 50%;
  }

  /* ==========================================
   MODO SOLO ESTACIONES
========================================== */

  .mrp-flow-row.only-stations-row .mrp-sub-zone {
    display: none !important;
  }

  .mrp-flow-row.only-stations-row .mrp-arrow {
    margin-top: 195px;
  }

  /* ==========================================
   UTILIDAD
========================================== */

  .mrp-hidden-by-user {
    display: none !important;
  }

  .historial-scroll {
    max-height: 340px;
    overflow-y: auto;
    padding-right: 4px;
  }

  .historial-scroll::-webkit-scrollbar {
    width: 8px;
  }

  .historial-scroll::-webkit-scrollbar-track {
    background: #f8fafc;
    border-radius: 20px;
  }

  .historial-scroll::-webkit-scrollbar-thumb {
    background: #cbd5e1;
    border-radius: 20px;
  }

  .historial-scroll::-webkit-scrollbar-thumb:hover {
    background: #94a3b8;
  }


  .card-historial-eventos {
    height: auto !important;
    min-height: auto !important;
  }


  #cardHistorialEventos {
    min-height: auto !important;
    height: auto !important;
    align-self: start;
  }


  #cardHistorialEventos,
  #cardUnidadesFueraLinea {
    width: 100%;
    height: auto !important;
    min-height: auto !important;
    align-self: stretch !important;
  }
</style>

<div id="contentAjax"></div>

<div class="main-content">
  <div class="page-content">
    <div class="container-fluider">

      <div class="row">
        <div class="col-12">
          <div class="page-title-box d-sm-flex align-items-center justify-content-between">
            <h4 class="mb-sm-0"><?= $data['page_title'] ?></h4>

            <div class="page-title-right">
              <ol class="breadcrumb m-0">
                <li class="breadcrumb-item"><a href="javascript:void(0);">MRP</a></li>
                <li class="breadcrumb-item active"><?= esc($data['page_tag']) ?></li>
              </ol>
            </div>
          </div>
        </div>
      </div>

      <?php if (!$ok): ?>
        <div class="alert alert-danger border-0 shadow-sm">
          <div class="fw-bold mb-1">No se pudo cargar el detalle de la orden</div>
          <div class="small"><?= esc($resp['msg'] ?? 'Error') ?></div>
        </div>
      <?php else: ?>

        <div class="mrp-shell">
          <div class="mrp-dark-wrap">
            <div class="mrp-grid">

              <aside class="mrp-panel">
                <div class="mrp-panel-header">
                  <div>
                    <h2 class="mrp-panel-title" id="detalleTituloNodo">
                      <?= esc($estacionAsignada['nombre_estacion'] ?? '--') ?>
                    </h2>
                  </div>
                  <div class="mrp-pill-live" id="detalleTipoNodo">Estación</div>
                </div>

                <div class="mrp-btn-grid-top">
                  <button type="button" class="mrp-btn-main" id="btnIniciarUnidad">Iniciar unidad</button>
                  <button type="button" class="mrp-btn-secondary" id="btnFinalizarUnidad">Finalizar proceso</button>
                  <button type="button" class="mrp-btn-dark" id="btnPausarUnidad">Pausar / Paro</button>
                  <button type="button" class="mrp-btn-dark" id="btnIniciarProduccion">Iniciar Producción</button>
                  <button type="button" class="mrp-btn-dark" id="btnFinalizarProduccion">Finalizar Producción</button>
                </div>

                <div class="mrp-panel-layout">
                  <div class="mrp-panel-left">
                    <div class="mrp-current-unit" id="detalleUnidadActualCard">
                      <div class="mrp-label">Unidad actual</div>
                      <div class="mrp-vin" id="detalleUnidadActual">
                        <?= esc($unidadActual['num_sub_orden'] ?? 'SIN-UNIDAD') ?>
                      </div>




                      <!-- <div class="d-flex align-items-center gap-2 flex-wrap mb-3">
                        <span class="mrp-status-soft" id="detalleEstadoUnidad">Esperando inicio</span>
                        <span id="detalleBadgePrioridad"><?= badgePrioridad($ot['prioridad'] ?? '') ?></span>
                      </div>

                      <div id="detalleSemaforo" class="mrp-semaforo semaforo-espera mt-3">
                        <div class="mrp-semaforo-dot"></div>
                        <div>
                          <div class="fw-bold" id="detalleSemaforoTitulo">Esperando validación</div>
                          <div class="small" id="detalleSemaforoTexto">Selecciona una estación o subensamble.</div>
                        </div>
                      </div>

                      <div id="detalleResumenUnidad">
                        <div><strong>Modelo:</strong> <?= esc($ot['descripcion'] ?? '-') ?></div>
                        <div><strong>Supervisor:</strong> <?= esc($ot['supervisor'] ?? '-') ?></div>
                        <div><strong>Pedido:</strong> <?= esc($ot['num_pedido'] ?? '-') ?></div>
                      </div> -->
                    </div>



                    <div class="mrp-context-card mt-3" id="detalleUnidadActualTextoCard">

                      <div class="mrp-context-icon">
                        <i class="ri-information-line"></i>
                      </div>

                      <div class="mrp-context-body">

                        <div class="mrp-context-title" id="detalleUnidadActualTextoTitulo">
                          Estado actual
                        </div>

                        <div class="mrp-context-text" id="detalleUnidadActualTexto">
                          Selecciona una estación o subensamble para validar el estado actual.
                        </div>

                      </div>

                    </div>

                    <div id="cardAlarmaUnidad" class="mrp-alarm-card d-none"></div>

                    <div class="mrp-panel-left mt-2">
                      <div class="mrp-current-unit">
                        <div id="detalleResumenUnidad">
                          <div><strong>Modelo:</strong> <?= esc($ot['descripcion'] ?? '-') ?></div>
                          <div><strong>Supervisor:</strong> <?= esc($ot['supervisor'] ?? '-') ?></div>
                          <div><strong>Pedido:</strong> <?= esc($ot['num_pedido'] ?? '-') ?></div>
                        </div>
                      </div>
                    </div>

                    <div class="mrp-panel-data-grid">

                      <div class="mrp-mini-card">
                        <div class="mrp-label">Tipo</div>
                        <div class="value" id="detalleTipoCorto">Estación</div>
                      </div>

                      <div class="mrp-mini-card">
                        <div class="mrp-label">Tiempo ajuste</div>
                        <div class="value" id="detalleTiempoAjuste"><?= esc($estacionAsignada['tiempo_ajuste'] ?? '-') ?>
                          min</div>
                      </div>

                      <div class="mrp-mini-card">
                        <div class="mrp-label">Encargado</div>
                        <div class="value" id="detalleEncargado"><?= esc($encargadoAsignado) ?></div>
                      </div>

                      <div class="mrp-mini-card">
                        <div class="mrp-label">Ayudante</div>
                        <div class="value" id="detalleAyudante"><?= esc($ayudanteAsignado) ?></div>
                      </div>

                      <div class="mrp-mini-card" id="cardInspectorCalidad" style="display:none;">
                        <div class="mrp-label">Inspector calidad</div>
                        <div class="value" id="detalleInspectorCalidad">-</div>
                      </div>

                      <div class="mrp-mini-card" id="cardInspectorCriticos" style="display:none;">
                        <div class="mrp-label">Inspector críticos</div>
                        <div class="value" id="detalleInspectorCriticos">-</div>
                      </div>

                    </div>
                  </div>

                  <div class="mrp-panel-right">
                    <div class="mrp-side-stack">
                      <div class="mrp-actions-card">
                        <h6>Proceso seleccionado</h6>
                        <p id="detalleDescripcionProceso">
                          Ejecutar el proceso <strong><?= esc($estacionAsignada['proceso'] ?? '-') ?></strong>
                          <!-- validar herramientas requeridas, revisar componentes, consultar ayudas visuales
                          y completar operaciones estándar. -->
                        </p>
                      </div>

                      <div class="mt-3" id="contenedorMensajesProceso"></div>

                      <div class="mrp-actions-card">
                        <h6>Recursos y ejecución</h6>
                        <div class="mrp-btn-grid">
                          <button type="button" class="mrp-btn-dark" id="btnDetalleHerramientas">Herramientas</button>
                          <button type="button" class="mrp-btn-dark" id="btnDetalleComponentes">Componentes</button>
                          <button type="button" class="mrp-btn-dark" id="btnDetalleOperaciones">Operaciones</button>
                          <button type="button" class="mrp-btn-dark" id="btnDetalleAyudas">Ayudas visuales</button>
                          <button type="button" class="mrp-btn-dark" id="btnCalidad">Calidad</button>
                          <button type="button" class="mrp-btn-dark" id="btnOperacionesCriticas">Operaciones
                            críticas</button>
                          <button type="button" class="mrp-btn-dark" id="btnEstamparVin">Estampar VIN</button>


                        </div>
                      </div>

                      <div class="mrp-legend compact-legend">
                        <div class="mrp-legend-item">
                          <span class="mrp-dot dot-station"></span>
                          <span>Tu estación asignada</span>
                        </div>

                        <div class="mrp-legend-item">
                          <span class="mrp-dot dot-ready"></span>
                          <span>Listo para trabajar</span>
                        </div>

                        <div class="mrp-legend-item">
                          <span class="mrp-dot dot-working"></span>
                          <span>Trabajando / En proceso</span>
                        </div>

                        <div class="mrp-legend-item">
                          <span class="mrp-dot dot-done"></span>
                          <span>Finalizada / Entregada</span>
                        </div>

                        <div class="mrp-legend-item">
                          <span class="mrp-dot dot-blocked"></span>
                          <span>Bloqueada</span>
                        </div>

                        <div class="mrp-legend-item">
                          <span class="mrp-dot dot-waiting"></span>
                          <span>En espera / Sin unidad</span>
                        </div>

                        <div class="mrp-legend-item">
                          <span class="mrp-dot dot-alarm"></span>
                          <span>Unidad alarmada</span>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </aside>

              <section class="mrp-content">
                <div class="mrp-top">
                  <div class="mrp-kpi">
                    <div class="kpi-title">Orden de trabajo</div>
                    <div class="kpi-value"><?= esc($ot['num_orden'] ?? '-') ?></div>
                    <div class="kpi-desc"><?= esc($ot['cve_producto'] ?? '-') ?> · <?= esc($ot['descripcion'] ?? '-') ?>
                    </div>
                  </div>

                  <div class="mrp-kpi">
                    <div class="kpi-title">Cantidad a producir</div>
                    <div class="kpi-value" id="kpiCantidadProducir"><?= esc($ot['cantidad'] ?? 0) ?></div>
                    <div class="kpi-desc">Cantidad de piezas a ensamblar</div>
                  </div>

                  <div class="mrp-kpi">
                    <div class="kpi-title">Finalizadas</div>
                    <div class="kpi-value" id="kpiFinalizadas"><?= (int) $totalFinalizadas ?></div>
                    <div class="kpi-desc">Unidades completadas y liberadas</div>
                  </div>




                  <div class="mrp-kpi">
                    <div class="kpi-title">Tiempo de la unidad</div>
                    <div class="kpi-value" id="tiempoUnidadGlobal">00:00</div>
                    <div class="kpi-desc">Seguimiento visual de la unidad seleccionada</div>
                  </div>
                </div>

                <div id="contenedorParoMomentaneo">

                  <div class="mrp-paro-grid">

                    <!-- CARD TIEMPO PARO -->
                    <div class="mrp-card-paro">

                      <div class="mrp-paro-label">
                        <i class="ri-alarm-warning-line me-1"></i>
                        Tiempo de paro momentáneo
                      </div>

                      <div class="mrp-paro-timer" id="timerParoMomentaneo">
                        00:00
                      </div>

                      <div class="mrp-paro-detalle" id="detalleParoMomentaneo">
                        La unidad se encuentra temporalmente detenida.
                      </div>

                    </div>

                    <!-- CARD REANUDAR -->
                    <div class="mrp-card-reanudar">

                      <div class="mrp-reanudar-title">
                        Reanudar producción
                      </div>

                      <div class="mrp-reanudar-text">
                        Restablece el flujo operativo de la unidad actual
                        y continúa el proceso desde el punto donde fue detenido.
                      </div>

                      <button type="button" class="mrp-btn-reanudar" id="btnReanudarParo">

                        <i class="ri-play-circle-fill me-1"></i>
                        Continuar proceso de unidad

                      </button>

                    </div>

                  </div>

                </div>




                <div class="mrp-dashboard-horizontal">

                  <div class="mrp-production-extra">

                    <div class="mrp-extra-card mrp-extra-card-full card-historial-eventos" id="cardHistorialEventos">
                      <div class="mrp-extra-title">
                        <i class="ri-history-line me-1"></i>
                        Historial de eventos
                      </div>

                      <div id="contenedorHistorialEventos">
                        <div class="text-muted small py-2">
                          Selecciona una estación para ver sus unidades finalizadas.
                        </div>
                      </div>
                    </div>

                    <div class="mrp-extra-card mrp-extra-card-full" id="cardUnidadesFueraLinea">
                      <div class="mrp-extra-title">
                        <i class="ri-logout-box-r-line me-1"></i>
                        Unidades retiradas de producción
                      </div>

                      <div id="contenedorUnidadesFueraLinea">
                        <div class="text-muted small py-2">
                          No existen unidades fuera de línea en esta estación.
                        </div>
                      </div>
                    </div>



                  </div>

                  <div class="mrp-flow-card">
                    <div class="mrp-flow-header">
                      <div>
                        <h5 class="mrp-flow-title">Trazabilidad visual completa de estaciones y sub-ensambles</h5>
                        <p class="mrp-flow-subtitle">
                          Da click sobre una estación o subensamble para cargar su detalle en el panel izquierdo.
                        </p>
                      </div>

                      <div class="d-flex gap-2 flex-wrap">
                        <button type="button" class="mrp-collapse-btn" id="btnExpandirTodo">Expandir todo</button>
                        <button type="button" class="mrp-collapse-btn" id="btnContraerTodo">Contraer subensambles</button>
                      </div>
                    </div>

                    <div class="mrp-scroll">
                      <div class="mrp-flow-row">
                        <?php foreach ($estaciones as $i => $est): ?>
                          <?php
                          $subensambles = $est['subensambles'] ?? [];
                          $ordenes = $est['ordenes_trabajo'] ?? [];
                          $unidad = $ordenes[0] ?? [];
                          $esAsignada = ((int) ($est['id_planeacion_estacion'] ?? 0) === (int) ($estacionAsignada['id_planeacion_estacion'] ?? 0));
                          ?>

                          <div class="mrp-station-wrap" data-est-index="<?= (int) $i ?>">
                            <div class="mrp-sub-zone" data-est-index="<?= (int) $i ?>">
                              <?php if (!empty($subensambles)): ?>
                                <?php foreach ($subensambles as $idxSub => $sub): ?>
                                  <?php $subOT = $sub['ordenes_trabajo'][0] ?? []; ?>
                                  <div
                                    class="mrp-sub-card bloque-subensamble js-selectable-node <?= $esAsignada ? 'active-station' : '' ?>"
                                    data-node-type="subensamble" data-est-index="<?= (int) $i ?>"
                                    data-sub-index="<?= (int) $idxSub ?>">

                                    <div class="mrp-sub-top">
                                      <span class="mrp-chip chip-sub-id">
                                        S<?= ($i + 1) ?>-<?= chr(65 + $idxSub) ?>
                                      </span>
                                      <span class="mrp-chip chip-linked">
                                        Ligado a E<?= str_pad((string) ($i + 1), 2, '0', STR_PAD_LEFT) ?>
                                      </span>

                                    </div>

                                    <!-- <div class="mrp-sub-title"><?= esc($sub['nombre_estacion'] ?? '-') ?></div> -->

                                    <div class="mrp-info-stack">
                                      <div class="mrp-info-pill"><strong>Proceso:</strong> <?= esc($sub['proceso'] ?? '-') ?>
                                      </div>
                                      <div class="mrp-info-pill"><strong>Encargado:</strong>
                                        <?= esc($sub['encargados'][0]['nombre_completo'] ?? '-') ?></div>
                                      <div class="mrp-info-pill"><strong>Ayudante:</strong>
                                        <?= esc($sub['ayudantes'][0]['nombre_completo'] ?? '-') ?></div>
                                      <div class="mrp-info-pill"><strong>Tiempo de ajuste:</strong>
                                        <?= esc($sub['tiempo_ajuste'] ?? '-') ?> min</div>
                                    </div>


                                    <div class="mrp-order-box has-unit" data-order-box-type="subensamble"
                                      data-est-index="<?= (int) $i ?>" data-sub-index="<?= (int) $idxSub ?>">

                                      <div class="mrp-order-desc mb-0">

                                        <?php
                                        $totalSub = count($subensamble['ordenes_trabajo'] ?? []);

                                        $entregadas = count(array_filter(
                                          $subensamble['ordenes_trabajo'] ?? [],
                                          fn($o) => (int) ($o['estado'] ?? 0) === 4
                                        ));

                                        $siguiente = $entregadas + 1;
                                        ?>

                                        <?php if ($entregadas >= $totalSub && $totalSub > 0): ?>

                                          <div class="text-success fw-semibold">
                                            <i class="ri-checkbox-circle-fill me-1"></i>
                                            Subensambles entregados correctamente
                                          </div>

                                          <div class="small text-muted mt-1">
                                            Se completó correctamente la entrega total de subensambles programados.
                                          </div>

                                        <?php else: ?>

                                          <div class="fw-semibold">
                                            Ya puedes trabajar el subensamble <?= $siguiente ?>
                                          </div>

                                          <div class="small text-muted mt-1">
                                            Este subensamble debe completarse antes de liberar la estación principal.
                                          </div>

                                        <?php endif; ?>

                                      </div>

                                    </div>


                                  </div>
                                <?php endforeach; ?>
                              <?php endif; ?>
                            </div>

                            <div class="mrp-station-card js-selectable-node <?= $esAsignada ? 'active-station' : '' ?>"
                              data-node-type="estacion" data-est-index="<?= (int) $i ?>">

                              <div class="mrp-station-top">
                                <div class="d-flex align-items-center gap-3">
                                  <span
                                    class="mrp-station-number"><?= str_pad((string) ($i + 1), 2, '0', STR_PAD_LEFT) ?></span>
                                  <div>
                                    <div class="mrp-station-title"><?= esc($est['nombre_estacion'] ?? '-') ?></div>
                                    <div class="mrp-station-text"><?= esc($est['proceso'] ?? '-') ?></div>
                                  </div>
                                </div>

                                <div class="d-flex flex-column align-items-end gap-2">
                                  <span class="mrp-chip chip-wait">En espera</span>

                                </div>
                              </div>

                              <div class="mrp-info-stack">
                                <div class="mrp-info-pill"><strong>Proceso:</strong> <?= esc($est['proceso'] ?? '-') ?>
                                </div>
                                <div class="mrp-info-pill"><strong>Encargado:</strong>
                                  <?= esc($est['encargados'][0]['nombre_completo'] ?? '-') ?></div>
                                <div class="mrp-info-pill"><strong>Ayudante:</strong>
                                  <?= esc($est['ayudantes'][0]['nombre_completo'] ?? '-') ?></div>
                                <div class="mrp-info-pill"><strong>Tiempo de ajuste:</strong>
                                  <?= esc($est['tiempo_ajuste'] ?? '-') ?> min</div>
                              </div>

                              <div class="mrp-order-box <?= !empty($unidad) ? 'has-unit' : '' ?>"
                                data-order-box-type="estacion" data-est-index="<?= (int) $i ?>">
                                <?php if (!empty($unidad)): ?>
                                  <div class="d-flex justify-content-between align-items-start gap-2 mb-2">
                                    <div class="mrp-order-id js-card-order-id"><?= esc($unidad['num_sub_orden'] ?? '-') ?>
                                    </div>
                                    <!-- <span class="js-card-status">
                                    <?= badgeEstadoOrden($unidad['estatus'] ?? 1) ?>
                                  </span> -->
                                  </div>
                                  <p class="mrp-order-desc">
                                    Esta unidad está ligada a la corrida de la estación y podrá avanzar una vez completado el
                                    proceso requerido y, si existe, su subensamble previo.
                                  </p>
                                <?php else: ?>
                                  <p class="mrp-order-desc mb-0">
                                    Sin unidad en esta estación. Esperando recepción del proceso anterior.
                                  </p>
                                <?php endif; ?>
                              </div>
                            </div>
                          </div>

                          <?php if ($i < count($estaciones) - 1): ?>
                            <div class="mrp-arrow"></div>
                          <?php endif; ?>
                        <?php endforeach; ?>
                      </div>
                    </div>

                    <div class="mrp-soft-note">
                      <strong>Regla visual del flujo:</strong>
                      los subensambles permanecen arriba y ligados a su estación. Las estaciones quedan siempre alineadas
                      en la línea principal aunque una columna no tenga subensamble.
                    </div>
                  </div>

                </div>
              </section>

            </div>
          </div>
        </div>

        <script>
          const MRP_DATA = <?= json_encode($ot, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
        </script>

      <?php endif; ?>

    </div>
  </div>

  <footer class="footer">
    <div class="container-fluider">
      <div class="row">
        <div class="col-sm-6">
          <script>document.write(new Date().getFullYear())</script> © LDR.
        </div>
        <div class="col-sm-6">
          <div class="text-sm-end d-none d-sm-block">
            LDR Solutions · MRP
          </div>
        </div>
      </div>
    </div>
  </footer>
</div>


<div id="modalHerramientas" class="modal fade bs-example-modal-xl" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-xl">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title"><i class="ri-alert-line me-2"></i> Lista de herramientas requeridas</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
      </div>

      <div class="modal-body">

        <!-- CARDS CONTEXTO -->
        <div class="row g-3 mb-3">

          <div class="col-md-6">
            <div class="card border shadow-sm">
              <div class="card-body d-flex align-items-center gap-3">
                <div
                  class="avatar-sm bg-soft-primary text-primary rounded-circle d-flex align-items-center justify-content-center">
                  <i class="ri-map-pin-line fs-18"></i>
                </div>
                <div>
                  <div class="text-muted small">Estación de trabajo</div>
                  <h5 class="mb-0 fw-semibold" id="titleEstacionH">—</h5>
                </div>
              </div>
            </div>
          </div>

          <div class="col-md-6">
            <div class="card border shadow-sm">
              <div class="card-body d-flex align-items-center gap-3">
                <div
                  class="avatar-sm bg-soft-warning text-warning rounded-circle d-flex align-items-center justify-content-center">
                  <i class="ri-settings-3-line fs-18"></i>
                </div>
                <div>
                  <div class="text-muted small">Proceso</div>
                  <h6 class="mb-0 fw-semibold" id="titleProcesoH">—</h6>
                </div>
              </div>
            </div>
          </div>

        </div>


        <div class="alert alert-warning d-flex align-items-start gap-2 mb-3" role="alert">
          <i class="ri-alert-line fs-18 mt-1"></i>
          <div>
            <div class="fw-semibold">Validación de herramientas previa a producción</div>
            <div class="text-muted">
              Verifique la disponibilidad de herramientas antes de iniciar la orden de producción.
            </div>
          </div>
        </div>


        <!-- TABLA -->
        <div class="card border shadow-sm">
          <div class="card-body p-0">
            <div class="table-responsive">
              <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                  <tr>
                    <th>Herramienta</th>
                    <th class="text-center">Requerido</th>
                    <!-- <th class="text-center">Consumo total</th> -->
                    <!-- <th>Fecha creación</th> -->
                  </tr>
                </thead>

                <tbody id="herrTableBody">
                  <tr>
                    <td colspan="3" class="text-center text-muted">Sin datos…</td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>

      </div>


      <div class="modal-footer">
        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cerrar</button>
      </div>
    </div>
  </div>
</div>


<div id="modalComponentes" class="modal fade bs-example-modal-xl" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-xl">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title"><i class="ri-alert-line me-2"></i> Lista de componentes requeridos (BOM)</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
      </div>

      <div class="modal-body">

        <!-- CARDS CONTEXTO -->
        <div class="row g-3 mb-3">

          <div class="col-md-6">
            <div class="card border shadow-sm">
              <div class="card-body d-flex align-items-center gap-3">
                <div
                  class="avatar-sm bg-soft-primary text-primary rounded-circle d-flex align-items-center justify-content-center">
                  <i class="ri-map-pin-line fs-18"></i>
                </div>
                <div>
                  <div class="text-muted small">Estación de trabajo</div>
                  <h5 class="mb-0 fw-semibold" id="titleEstacion">—</h5>
                </div>
              </div>
            </div>
          </div>

          <div class="col-md-6">
            <div class="card border shadow-sm">
              <div class="card-body d-flex align-items-center gap-3">
                <div
                  class="avatar-sm bg-soft-warning text-warning rounded-circle d-flex align-items-center justify-content-center">
                  <i class="ri-settings-3-line fs-18"></i>
                </div>
                <div>
                  <div class="text-muted small">Proceso</div>
                  <h6 class="mb-0 fw-semibold" id="titleProceso">—</h6>
                </div>
              </div>
            </div>
          </div>

        </div>


        <div class="alert alert-warning d-flex align-items-start gap-2 mb-3" role="alert">
          <i class="ri-alert-line fs-18 mt-1"></i>
          <div>
            <div class="fw-semibold">Validación de componentes previa a producción</div>
            <div class="text-muted">
              Verifique la disponibilidad y cantidades de componentes antes de iniciar la orden de producción.
            </div>
          </div>
        </div>


        <!-- TABLA -->
        <div class="card border shadow-sm">
          <div class="card-body p-0">
            <div class="table-responsive">
              <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                  <tr>
                    <th>Componente</th>
                    <th class="text-center">Consumo unitario</th>
                    <th class="text-center">Consumo total</th>
                    <!-- <th>Fecha creación</th> -->
                  </tr>
                </thead>

                <tbody id="compTableBody">
                  <tr>
                    <td colspan="3" class="text-center text-muted">Sin datos…</td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>

      </div>


      <div class="modal-footer">
        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cerrar</button>
      </div>
    </div>
  </div>
</div>



<div id="modalEspecificaciones" class="modal fade bs-example-modal-xl" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-xl">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title"><i class="ri-alert-line me-2"></i> Operaciones - Unidad actual: <span
            id="titleUnidad"></span></h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
      </div>

      <div class="modal-body">


        <!-- CARDS CONTEXTO -->
        <div class="row g-3 mb-3">

          <div class="col-md-6">
            <div class="card border shadow-sm">
              <div class="card-body d-flex align-items-center gap-3">
                <div
                  class="avatar-sm bg-soft-primary text-primary rounded-circle d-flex align-items-center justify-content-center">
                  <i class="ri-map-pin-line fs-18"></i>
                </div>
                <div>
                  <div class="text-muted small">Estación de trabajo</div>
                  <h5 class="mb-0 fw-semibold" id="titleEstacionEs">—</h5>
                </div>
              </div>
            </div>
          </div>

          <div class="col-md-6">
            <div class="card border shadow-sm">
              <div class="card-body d-flex align-items-center gap-3">
                <div
                  class="avatar-sm bg-soft-warning text-warning rounded-circle d-flex align-items-center justify-content-center">
                  <i class="ri-settings-3-line fs-18"></i>
                </div>
                <div>
                  <div class="text-muted small">Proceso</div>
                  <h6 class="mb-0 fw-semibold" id="titleProcesoEs">—</h6>
                </div>
              </div>
            </div>
          </div>

        </div>


        <div class="alert alert-warning d-flex align-items-start gap-2 mb-3" role="alert">
          <i class="ri-alert-line fs-18 mt-1"></i>
          <div>
            <div class="fw-semibold">¡Nota!</div>
            <div class="text-muted">
              Para poder liberar esta unidad, es necesario capturar quién realizó cada actividad. Puedes ingresar el
              nombre del operador o escanear el número de colaborador correspondiente..

            </div>
          </div>
        </div>

        <div class="table-responsive">
          <table class="table table-hover align-middle mb-0 table-nowrap">
            <thead class="table-light">
              <tr>
                <th style="width: 45%;">Operación</th>
                <th style="width: 35%;">Empleado que realiza</th>
                <th style="width: 20%;" class="text-center">Acción</th>
              </tr>
            </thead>
            <tbody id="specTableBody">
              <tr>
                <td colspan="3" class="text-center text-muted">Sin datos…</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <div class="modal-footer">
        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cerrar</button>
      </div>
    </div>
  </div>
</div>


<div id="modalEspecificacionesCriticas" class="modal fade bs-example-modal-xl" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-xl">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title"><i class="ri-alert-line me-2"></i> Especificaciones Críticas - Unidad actual: <span
            id="titleUnidadC"></span></h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
      </div>

      <div class="modal-body">


        <!-- CARDS CONTEXTO -->
        <div class="row g-3 mb-3">

          <div class="col-md-6">
            <div class="card border shadow-sm">
              <div class="card-body d-flex align-items-center gap-3">
                <div
                  class="avatar-sm bg-soft-primary text-primary rounded-circle d-flex align-items-center justify-content-center">
                  <i class="ri-map-pin-line fs-18"></i>
                </div>
                <div>
                  <div class="text-muted small">Estación de trabajo</div>
                  <h5 class="mb-0 fw-semibold" id="titleEstacionEsC">—</h5>
                </div>
              </div>
            </div>
          </div>

          <div class="col-md-6">
            <div class="card border shadow-sm">
              <div class="card-body d-flex align-items-center gap-3">
                <div
                  class="avatar-sm bg-soft-warning text-warning rounded-circle d-flex align-items-center justify-content-center">
                  <i class="ri-settings-3-line fs-18"></i>
                </div>
                <div>
                  <div class="text-muted small">Proceso</div>
                  <h6 class="mb-0 fw-semibold" id="titleProcesoEsC">—</h6>
                </div>
              </div>
            </div>
          </div>

        </div>


        <div class="alert alert-warning border-0 mb-4">

          <div class="d-flex align-items-start gap-2">

            <i class="ri-error-warning-line fs-18"></i>

            <div>

              <div class="fw-semibold">
                Validación de especificaciones críticas
              </div>

              <div class="small">
                Antes de liberar la unidad, es necesario validar cada especificación crítica.
                En caso de detectar alguna desviación, deberá registrarse como "No conforme"
                indicando el detalle correspondiente.
              </div>

            </div>

          </div>

        </div>

        <div class="table-responsive">
          <table class="table table-hover align-middle mb-0 table-nowrap">
            <thead class="table-light">
              <tr>
                <th style="width: 45%;">Operación</th>
                <th style="width: 35%;">Acción</th>
                <th style="width: 20%;" class="text-center">Detalle</th>
              </tr>
            </thead>
            <tbody id="specCriticasTableBody">
              <tr>
                <td colspan="3" class="text-center text-muted">Sin datos…</td>
              </tr>
            </tbody>
          </table>

          <div class="text-end mt-4">

            <button type="button" class="btn btn-danger" onclick="guardarEspecificacionesCriticas()">
              <i class="ri-shield-check-line me-1"></i>
              Guardar validación de calidad
            </button>

          </div>



        </div>
      </div>

      <div class="modal-footer">
        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cerrar</button>
      </div>
    </div>
  </div>
</div>



<div id="modalAyudas" class="modal fade bs-example-modal-xl" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-xl">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title"><i class="ri-alert-line me-2"></i> Lista de herramientas requeridas</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
      </div>

      <div class="modal-body">

        <!-- CARDS CONTEXTO -->
        <div class="row g-3 mb-3">

          <div class="col-md-6">
            <div class="card border shadow-sm">
              <div class="card-body d-flex align-items-center gap-3">
                <div
                  class="avatar-sm bg-soft-primary text-primary rounded-circle d-flex align-items-center justify-content-center">
                  <i class="ri-map-pin-line fs-18"></i>
                </div>
                <div>
                  <div class="text-muted small">Estación de trabajo</div>
                  <h5 class="mb-0 fw-semibold" id="titleEstacionAyuda">—</h5>
                </div>
              </div>
            </div>
          </div>

          <div class="col-md-6">
            <div class="card border shadow-sm">
              <div class="card-body d-flex align-items-center gap-3">
                <div
                  class="avatar-sm bg-soft-warning text-warning rounded-circle d-flex align-items-center justify-content-center">
                  <i class="ri-settings-3-line fs-18"></i>
                </div>
                <div>
                  <div class="text-muted small">Proceso</div>
                  <h6 class="mb-0 fw-semibold" id="titleProcesoAyuda">—</h6>
                </div>
              </div>
            </div>
          </div>

        </div>


        <div class="alert alert-warning d-flex align-items-start gap-2 mb-3" role="alert">
          <i class="ri-alert-line fs-18 mt-1"></i>
          <div>
            <div class="fw-semibold">Ayudas visuales para ensamblad</div>
            <div class="text-muted">
              Consulte las ayudas visuales disponibles; estas le servirán como apoyo y guía durante el proceso de
              ensamblado.
            </div>
          </div>
        </div>


        <!-- TABLA -->
        <div class="card border shadow-sm">
          <div class="card-body p-0">
            <div class="table-responsive">
              <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                  <tr>
                    <th>Título</th>
                    <th class="text-center">Tipo</th>
                    <th class="text-center">Archivo</th>
                    <!-- <th class="text-center">Consumo total</th> -->
                    <!-- <th>Fecha creación</th> -->
                  </tr>
                </thead>

                <tbody id="AyudasTableBody">
                  <tr>
                    <td colspan="4" class="text-center text-muted">Sin datos…</td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>

      </div>


      <div class="modal-footer">
        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cerrar</button>
      </div>
    </div>
  </div>
</div>



<div id="modalCalidad" class="modal fade bs-example-modal-xl" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-fullscreen">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title"><i class="ri-alert-line me-2"></i> Inspección de calidad Final</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
      </div>

      <div class="modal-body bg-light">

        <div class="row g-3 mb-3">
          <!-- tus cards actuales -->
        </div>

        <div class="alert alert-warning d-flex align-items-start gap-2 mb-3">
          <i class="ri-alert-line fs-18 mt-1"></i>
          <div>
            <div class="fw-semibold">Inspección de calidad</div>
            <div class="text-muted">
              Marca cada validación requerida. Si existe una observación, captura el detalle.
            </div>
          </div>
        </div>

        <div id="contenedorPuntosCalidad"></div>

      </div>


      <!-- <div class="modal-footer">
        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cerrar</button>
      </div> -->
    </div>
  </div>
</div>


<div class="modal fade" id="modalIdentificacion" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content border-0 shadow">

      <!-- Header -->
      <div class="modal-header bg-soft-warning">
        <div>
          <h5 class="modal-title mb-0">
            <i class="ri-barcode-line me-1"></i>
            Identificación de unidad: <span id="numSubOrdenIdenti">-</span>
          </h5>
          <div class="small text-muted mt-1">
            <span class="me-2"><i class="ri-map-pin-line me-1"></i><span id="titleEstacionIdenti">Estación</span></span>
            <span class="me-2"><i class="ri-settings-3-line me-1"></i><span
                id="titleProcesoIdenti">Proceso</span></span>
            <!-- <span class="badge rounded-pill bg-warning text-dark">
              <i class="ri-hashtag me-1"></i>UNIDAD ACTUAL : <span id="numSubOrdenIdenti">-</span>
            </span> -->
          </div>
        </div>

        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>

      <!-- Body -->
      <div class="modal-body">

        <!-- INFO EXTRA (solo lectura cuando es Ver VIN) -->
        <div id="boxInfoVinAsignado" class="alert alert-info d-none mb-3">
          <!-- Se llena por JS -->
        </div>

        <!-- FORM -->
        <form id="formIdentificacionUnidad" class="form-identificacion-unidad" autocomplete="off">

          <!-- idorden hidden -->
          <input type="hidden" id="ordenid" name="orden_trabajo_id">
           <input type="hidden" id="numunidad" name="numunidad">

          <div class="row g-3">

            <!-- SELECT VIN -->
            <div class="col-12 col-md-6">
              <label for="selectVinIdenti" class="form-label fw-semibold">
                <i class="ri-barcode-line me-1"></i> Selecciona el VIN
              </label>

              <div class="input-group">
                <span class="input-group-text bg-soft-primary text-primary">
                  <i class="ri-qr-scan-2-line"></i>
                </span>
                <select class="form-select" id="selectVinIdenti" name="vin" required>
                  <option value="" selected disabled>— Selecciona —</option>
                  <!-- Opciones dinámicas -->
                </select>
              </div>

              <div class="form-text">
                Asegúrate de que el VIN corresponda a la unidad y a la sub-OT seleccionada.
              </div>
            </div>

            <!-- INPUT MOTOR -->
            <div class="col-12 col-md-6">
              <label for="inputMotorIdenti" class="form-label fw-semibold">
                <i class="ri-engine-line me-1"></i> Ingresa el número de motor
              </label>

              <div class="input-group">
                <span class="input-group-text bg-soft-warning text-warning">
                  <i class="ri-settings-3-line"></i>
                </span>
                <input type="text" class="form-control" id="inputMotorIdenti" name="numero_motor"
                  placeholder="Ej: ENG-AX12-99831" maxlength="60" required>
              </div>

              <div class="form-text">
                Captura el número exactamente como aparece en la unidad.
              </div>
            </div>

            <!-- INPUT VIN ORIGEN -->
            <div class="col-12 col-md-6">
              <label for="inputVinOrigenIdenti" class="form-label fw-semibold">
                <i class="ri-barcode-box-line me-1"></i> VIN de origen
              </label>

              <div class="input-group">
                <span class="input-group-text bg-soft-info text-info">
                  <i class="ri-barcode-line"></i>
                </span>
                <input type="text" class="form-control" id="inputVinOrigenIdenti" name="vin_origen"
                  placeholder="Ej: 3N1AB8CV7PY123456" maxlength="80" required>
              </div>

              <div class="form-text">
                Captura el VIN original de la unidad.
              </div>
            </div>

            <!-- INPUT TRANSMISIÓN -->
            <div class="col-12 col-md-6">
              <label for="inputTransmisionIdenti" class="form-label fw-semibold">
                <i class="ri-settings-5-line me-1"></i> Número de transmisión
              </label>

              <div class="input-group">
                <span class="input-group-text bg-soft-secondary text-secondary">
                  <i class="ri-tools-line"></i>
                </span>
                <input type="text" class="form-control" id="inputTransmisionIdenti" name="numero_transmision"
                  placeholder="Ej: TRM-AX12-77452" maxlength="80" required>
              </div>

              <div class="form-text">
                Captura el número de transmisión correspondiente a la unidad.
              </div>
            </div>

          </div>

        </form>

      </div>

      <!-- Footer -->
      <div class="modal-footer d-flex justify-content-between">
        <div class="d-flex gap-2">
          <button type="button" class="btn btn-soft-secondary" data-bs-dismiss="modal">
            <i class="ri-close-line me-1"></i> Cerrar
          </button>

          <!-- Botón guardar (se oculta en modo Ver VIN con JS) -->
          <button type="button" class="btn btn-warning" id="btnGuardarIdenti">
            <i class="ri-save-3-line me-1"></i> Guardar asignación
          </button>
        </div>
      </div>

    </div>
  </div>
</div>




<div class="modal fade" id="modalAccionProduccion" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content border-0 shadow">

      <div class="modal-header bg-warning-subtle">
        <h5 class="modal-title">
          <i class="ri-alert-line me-2"></i>
          <span id="tituloModalAccionProduccion">Acción de producción</span>
        </h5>

        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>

      <div class="modal-body">

        <div class="alert alert-warning border-0">
          <div class="fw-semibold" id="subtituloModalAccionProduccion">
            Selecciona la acción que se aplicará.
          </div>
          <div class="small text-muted" id="descripcionModalAccionProduccion">
            Esta decisión quedará registrada para trazabilidad de producción.
          </div>
        </div>

        <div id="contenedorOpcionesAccionProduccion" class="d-grid gap-3"></div>

      </div>

      <div class="modal-footer">
        <button type="button" class="btn btn-light" data-bs-dismiss="modal">
          Cancelar
        </button>

        <button type="button" class="btn btn-warning" onclick="confirmarAccionProduccion()">
          <i class="ri-check-line me-1"></i>
          Confirmar acción
        </button>
      </div>

    </div>
  </div>
</div>

<?php footerAdmin($data); ?>