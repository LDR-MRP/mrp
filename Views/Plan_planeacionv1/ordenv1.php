<?php headerAdmin($data); ?>

<?php
function esc($value)
{
  return htmlspecialchars((string)($value ?? ''), ENT_QUOTES, 'UTF-8');
}

function formatFecha($fecha)
{
  if (empty($fecha) || $fecha === '0000-00-00 00:00:00' || $fecha === '0000-00-00') {
    return '-';
  }
  return esc($fecha);
}

function badgePrioridad($prioridad)
{
  $prioridad = strtoupper(trim((string)$prioridad));

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
  $estatus = (int)$estatus;

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
$ok = !empty($resp) && (int)($resp['status'] ?? 0) === 1;
$ot = $ok ? ($resp['data'] ?? []) : [];
$estaciones = $ot['estaciones'] ?? [];

$estacionAsignada = $estaciones[0] ?? [];
$subensamblesAsignados = $estacionAsignada['subensambles'] ?? [];

$totalEstaciones = count($estaciones);
$totalSubensambles = 0;
$totalOT = 0;
$totalPendientes = 0;
$totalEnProceso = 0;
$totalFinalizadas = 0;

foreach ($estaciones as $est) {
  $totalSubensambles += count($est['subensambles'] ?? []);
  foreach (($est['ordenes_trabajo'] ?? []) as $ord) {
    $totalOT++;
    $estatus = (int)($ord['estatus'] ?? 0);
    if ($estatus === 1) $totalPendientes++;
    if ($estatus === 2) $totalEnProceso++;
    if ($estatus === 3) $totalFinalizadas++;
  }

  foreach (($est['subensambles'] ?? []) as $sub) {
    foreach (($sub['ordenes_trabajo'] ?? []) as $ordSub) {
      $totalOT++;
      $estatus = (int)($ordSub['estado'] ?? 0);
      if ($estatus === 1) $totalPendientes++;
      if ($estatus === 2) $totalEnProceso++;
      if ($estatus === 3) $totalFinalizadas++;
    }
  }
}

$unidadActual = $estacionAsignada['ordenes_trabajo'][0] ?? [];
$encargadoAsignado = $estacionAsignada['encargados'][0]['nombre_completo'] ?? '-';
$ayudanteAsignado = $estacionAsignada['ayudantes'][0]['nombre_completo'] ?? '-';
?>

<style>
  .mrp-shell {
    --mrp-bg-1: #0b1220;
    --mrp-bg-2: #111a2e;
    --mrp-bg-3: #16233d;
    --mrp-card: rgba(15, 23, 42, .72);
    --mrp-card-2: rgba(17, 24, 39, .84);
    --mrp-border: rgba(148, 163, 184, .14);
    --mrp-text: #e5eefc;
    --mrp-text-soft: #9fb1cc;
    --mrp-accent: #3b82f6;
    --mrp-accent-2: #06b6d4;
    --mrp-green: #22c55e;
    --mrp-orange: #fb923c;
    --mrp-purple: #8b5cf6;
    --mrp-glow: 0 10px 30px rgba(0, 0, 0, .28);
  }

  .mrp-shell {
    color: var(--mrp-text);
  }

  .mrp-dark-wrap {
    background:
      radial-gradient(circle at top left, rgba(59, 130, 246, .16), transparent 28%),
      radial-gradient(circle at top right, rgba(6, 182, 212, .10), transparent 22%),
      linear-gradient(180deg, var(--mrp-bg-1) 0%, var(--mrp-bg-2) 52%, #0f172a 100%);
    border: 1px solid var(--mrp-border);
    border-radius: 24px;
    padding: 18px;
    box-shadow: var(--mrp-glow);
    overflow: hidden;
    position: relative;
  }

  .mrp-dark-wrap::before {
    content: "";
    position: absolute;
    inset: 0;
    background: linear-gradient(135deg, rgba(255,255,255,.02), transparent 30%, transparent 70%, rgba(255,255,255,.02));
    pointer-events: none;
  }

  .mrp-grid {
    display: grid;
    grid-template-columns: 340px minmax(0, 1fr);
    gap: 18px;
    align-items: start;
  }

  .mrp-panel,
  .mrp-kpi,
  .mrp-station-card,
  .mrp-sub-card,
  .mrp-mini-card,
  .mrp-actions-card {
    background: linear-gradient(180deg, rgba(18, 28, 49, .92), rgba(15, 23, 42, .84));
    border: 1px solid var(--mrp-border);
    box-shadow: inset 0 1px 0 rgba(255,255,255,.03), 0 10px 24px rgba(0,0,0,.22);
    backdrop-filter: blur(10px);
  }

  .mrp-panel {
    border-radius: 22px;
    padding: 20px;
    position: sticky;
    top: 95px;
  }

  .mrp-panel-header {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 12px;
    margin-bottom: 18px;
  }

  .mrp-panel-title {
    font-size: 1.6rem;
    line-height: 1.1;
    font-weight: 800;
    letter-spacing: -.02em;
    margin: 0;
    color: #fff;
  }

  .mrp-panel-subtitle {
    color: var(--mrp-text-soft);
    font-size: .92rem;
    line-height: 1.4;
    margin-top: 8px;
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

  .mrp-current-unit {
    border-radius: 18px;
    padding: 18px;
    background: linear-gradient(180deg, rgba(255,255,255,.04), rgba(255,255,255,.02));
    border: 1px solid rgba(255,255,255,.07);
    margin-bottom: 14px;
  }

  .mrp-label {
    color: #9fb1cc;
    text-transform: uppercase;
    font-size: .72rem;
    font-weight: 800;
    letter-spacing: .08em;
  }

  .mrp-vin {
    font-size: 1.65rem;
    font-weight: 800;
    line-height: 1.08;
    margin: 8px 0 10px;
    color: #fff;
  }

  .mrp-status-soft {
    display: inline-flex;
    align-items: center;
    border-radius: 999px;
    padding: 7px 12px;
    font-size: .78rem;
    font-weight: 700;
    background: rgba(59,130,246,.14);
    color: #93c5fd;
    border: 1px solid rgba(59,130,246,.28);
  }

  .mrp-stat-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0,1fr));
    gap: 12px;
    margin-bottom: 14px;
  }

  .mrp-mini-card {
    border-radius: 16px;
    padding: 14px;
    min-height: 92px;
  }

  .mrp-mini-card .value {
    font-size: 1.25rem;
    font-weight: 800;
    color: #fff;
    line-height: 1.1;
    margin-top: 6px;
  }

  .mrp-btn-grid-top {
    display: grid;
    grid-template-columns: repeat(2, minmax(0,1fr));
    gap: 10px;
    margin-bottom: 14px;
  }

  .mrp-actions-card {
    border-radius: 18px;
    padding: 16px;
    margin-bottom: 16px;
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
    color: #d7e1f1;
    margin-bottom: 0;
    line-height: 1.65;
  }

  .mrp-btn-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0,1fr));
    gap: 12px;
    margin-top: 16px;
  }

  .mrp-btn-main,
  .mrp-btn-secondary,
  .mrp-btn-dark,
  .mrp-chip-btn {
    border: 0;
    border-radius: 16px;
    font-weight: 700;
    transition: .22s ease;
  }

  .mrp-btn-main {
    padding: 14px 12px;
    color: #fff;
    background: linear-gradient(135deg, #ff8a1f, #ff5f1f);
    box-shadow: 0 10px 24px rgba(255, 106, 31, .22);
  }

  .mrp-btn-main:hover {
    transform: translateY(-1px);
    filter: brightness(1.06);
  }

  .mrp-btn-secondary {
    padding: 14px 12px;
    color: #d1fae5;
    background: linear-gradient(135deg, rgba(34,197,94,.28), rgba(22,163,74,.30));
    border: 1px solid rgba(34,197,94,.24);
  }

  .mrp-btn-dark {
    padding: 14px 12px;
    color: #dbeafe;
    background: linear-gradient(135deg, rgba(30,41,59,.9), rgba(17,24,39,.95));
    border: 1px solid rgba(148,163,184,.18);
  }

  .mrp-legend {
    margin-top: 16px;
    display: grid;
    gap: 8px;
  }

  .mrp-legend-item {
    display: flex;
    align-items: center;
    gap: 10px;
    color: #c6d3e7;
    font-size: .88rem;
  }

  .mrp-dot {
    width: 11px;
    height: 11px;
    border-radius: 50%;
    flex-shrink: 0;
  }

  .dot-station { background: #fb923c; }
  .dot-liberada { background: #22c55e; }
  .dot-proceso { background: #38bdf8; }
  .dot-espera { background: #94a3b8; }

  .mrp-content {
    min-width: 0;
  }

  .mrp-top {
    display: grid;
    grid-template-columns: repeat(4, minmax(0,1fr));
    gap: 14px;
    margin-bottom: 16px;
  }

  .mrp-kpi {
    border-radius: 18px;
    padding: 16px 18px;
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
    background: radial-gradient(circle, rgba(59,130,246,.18), transparent 60%);
  }

  .mrp-kpi .kpi-title {
    color: #9fb1cc;
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
    color: #fff;
    margin-bottom: 6px;
  }

  .mrp-kpi .kpi-desc {
    color: #c8d4e8;
    font-size: .9rem;
  }

  .mrp-flow-card {
    border-radius: 24px;
    padding: 18px;
    background: linear-gradient(180deg, rgba(10,18,35,.92), rgba(11,18,32,.92));
    border: 1px solid var(--mrp-border);
    box-shadow: inset 0 1px 0 rgba(255,255,255,.03), 0 15px 34px rgba(0,0,0,.25);
  }

  .mrp-flow-header {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 12px;
    margin-bottom: 16px;
  }

  .mrp-flow-title {
    color: #fff;
    font-size: 1.05rem;
    font-weight: 800;
    margin: 0 0 4px;
  }

  .mrp-flow-subtitle {
    color: var(--mrp-text-soft);
    margin: 0;
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
    background: rgba(148,163,184,.25);
    border-radius: 999px;
  }

  .mrp-flow-row {
    display: flex;
    align-items: flex-end;
    gap: 14px;
    min-width: max-content;
    padding-top: 4px;
  }

  .mrp-station-wrap {
    width: 290px;
    flex: 0 0 290px;
    display: flex;
    flex-direction: column;
  }

  .mrp-sub-zone {
    min-height: 290px;
    display: flex;
    flex-direction: column;
    justify-content: flex-start;
    gap: 12px;
    margin-bottom: 18px;
  }

  .mrp-sub-zone.empty {
    visibility: visible;
  }

  .mrp-sub-card {
    border-radius: 18px;
    padding: 14px;
    border: 1px solid rgba(139,92,246,.32);
    background:
      linear-gradient(180deg, rgba(55,48,163,.22), rgba(30,41,59,.86)),
      rgba(15,23,42,.9);
    position: relative;
  }

  .mrp-sub-card::after {
    content: "";
    position: absolute;
    bottom: -18px;
    left: 50%;
    width: 2px;
    height: 18px;
    transform: translateX(-50%);
    background: linear-gradient(180deg, rgba(139,92,246,.75), rgba(56,189,248,.75));
    border-radius: 999px;
  }

  .mrp-sub-top,
  .mrp-station-top {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 10px;
    margin-bottom: 10px;
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
  }

  .chip-sub-id {
    color: #e9ddff;
    background: rgba(139,92,246,.18);
    border: 1px solid rgba(139,92,246,.26);
  }

  .chip-linked {
    color: #a5f3fc;
    background: rgba(6,182,212,.14);
    border: 1px solid rgba(6,182,212,.24);
  }

  .chip-step {
    color: #fff;
    background: rgba(148,163,184,.14);
    border: 1px solid rgba(148,163,184,.18);
  }

  .chip-wait {
    color: #dbeafe;
    background: rgba(30,41,59,.78);
    border: 1px solid rgba(148,163,184,.18);
  }

  .mrp-sub-title,
  .mrp-station-title {
    color: #fff;
    font-weight: 800;
    line-height: 1.15;
    font-size: 1.08rem;
    margin-bottom: 6px;
  }

  .mrp-sub-text,
  .mrp-station-text {
    color: #b7c6dc;
    line-height: 1.5;
    min-height: 42px;
    font-size: .93rem;
  }

  .mrp-info-stack {
    display: grid;
    gap: 8px;
    margin-top: 12px;
  }

  .mrp-info-pill {
    border-radius: 12px;
    padding: 10px 12px;
    background: rgba(255,255,255,.03);
    border: 1px solid rgba(148,163,184,.12);
    color: #d8e2f2;
    font-size: .88rem;
    line-height: 1.35;
  }

  .mrp-station-card {
    border-radius: 22px;
    padding: 16px;
    min-height: 395px;
    background:
      linear-gradient(180deg, rgba(16,24,40,.96), rgba(12,20,36,.98));
    position: relative;
    overflow: hidden;
  }

  .mrp-station-card.active-station {
    border: 1px solid rgba(251,146,60,.32);
    box-shadow:
      inset 0 1px 0 rgba(255,255,255,.03),
      0 0 0 1px rgba(251,146,60,.12),
      0 16px 34px rgba(0,0,0,.28);
  }

  .mrp-station-card.active-station::before {
    content: "";
    position: absolute;
    inset: auto -40px -40px auto;
    width: 140px;
    height: 140px;
    border-radius: 50%;
    background: radial-gradient(circle, rgba(251,146,60,.12), transparent 65%);
  }

  .mrp-station-number {
    width: 34px;
    height: 34px;
    border-radius: 12px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    background: rgba(148,163,184,.16);
    color: #fff;
    font-weight: 900;
    flex-shrink: 0;
  }

  .mrp-station-actions {
    display: grid;
    grid-template-columns: repeat(3, minmax(0,1fr));
    gap: 6px;
    margin-top: 14px;
  }

  .mrp-chip-btn {
    padding: 8px 7px;
    font-size: .72rem;
    color: #dbeafe;
    background: rgba(15,23,42,.78);
    border: 1px solid rgba(148,163,184,.16);
    line-height: 1.2;
    border-radius: 12px;
  }

  .mrp-chip-btn:hover {
    background: rgba(30,41,59,.98);
    transform: translateY(-1px);
  }

  .mrp-order-box {
    margin-top: 14px;
    border-radius: 16px;
    padding: 14px;
    background: rgba(255,255,255,.025);
    border: 1px dashed rgba(148,163,184,.18);
    min-height: 132px;
  }

  .mrp-order-box.has-unit {
    border-style: solid;
    border-color: rgba(56,189,248,.24);
    background: linear-gradient(180deg, rgba(14,165,233,.08), rgba(30,41,59,.35));
  }

  .mrp-order-id {
    color: #fff;
    font-size: 1rem;
    font-weight: 900;
    line-height: 1.1;
    margin-bottom: 10px;
  }

  .mrp-order-desc {
    color: #b5c4da;
    line-height: 1.55;
    margin-bottom: 0;
  }

  .mrp-arrow {
    width: 34px;
    flex: 0 0 34px;
    display: flex;
    align-items: flex-end;
    justify-content: center;
    padding-bottom: 170px;
    position: relative;
  }

  .mrp-arrow::before {
    content: "";
    position: absolute;
    width: 100%;
    height: 2px;
    background: linear-gradient(90deg, rgba(56,189,248,.35), rgba(56,189,248,.95));
    border-radius: 999px;
  }

  .mrp-arrow::after {
    content: "";
    width: 10px;
    height: 10px;
    border-top: 2px solid rgba(125,211,252,.95);
    border-right: 2px solid rgba(125,211,252,.95);
    transform: rotate(45deg);
    position: absolute;
    right: 0;
    background: transparent;
  }

  .mrp-collapse-btn {
    border: 1px solid rgba(148,163,184,.16);
    background: rgba(15,23,42,.7);
    color: #dbeafe;
    border-radius: 14px;
    padding: 9px 14px;
    font-weight: 700;
  }

  .mrp-collapse-btn:hover {
    background: rgba(30,41,59,.95);
    color: #fff;
  }

  .mrp-soft-note {
    border-radius: 16px;
    padding: 12px 14px;
    background: rgba(56,189,248,.08);
    border: 1px solid rgba(56,189,248,.16);
    color: #cce7f6;
    font-size: .92rem;
    margin-top: 14px;
  }

  .mrp-badge-assigned {
    color: #fed7aa;
    background: rgba(251,146,60,.13);
    border: 1px solid rgba(251,146,60,.22);
    border-radius: 999px;
    font-size: .72rem;
    font-weight: 800;
    padding: 6px 10px;
  }

  @media (max-width: 1400px) {
    .mrp-top {
      grid-template-columns: repeat(2, minmax(0,1fr));
    }
  }

  @media (max-width: 1200px) {
    .mrp-grid {
      grid-template-columns: 1fr;
    }

    .mrp-panel {
      position: relative;
      top: 0;
    }
  }

  @media (max-width: 768px) {
    .mrp-top {
      grid-template-columns: 1fr;
    }

    .mrp-stat-grid,
    .mrp-btn-grid,
    .mrp-btn-grid-top,
    .mrp-station-actions {
      grid-template-columns: 1fr;
    }
  }

  .mrp-sub-zone.is-collapsed {
  min-height: 0 !important;
  height: 0;
  margin-bottom: 0;
  gap: 0;
  overflow: hidden;
}


/* ===========================
   MODO CLARO AUTOMÁTICO (VELZON)
   =========================== */

[data-bs-theme="light"] .mrp-shell {
  --mrp-bg-1: #f4f6fb;
  --mrp-bg-2: #ffffff;
  --mrp-bg-3: #eef2f7;

  --mrp-card: #ffffff;
  --mrp-card-2: #ffffff;

  --mrp-border: rgba(0,0,0,.08);

  --mrp-text: #1e293b;
  --mrp-text-soft: #64748b;

  --mrp-glow: 0 8px 20px rgba(0,0,0,.08);
}

/* Fondo principal */
[data-bs-theme="light"] .mrp-dark-wrap {
  background: linear-gradient(180deg, #f4f6fb 0%, #ffffff 100%);
}

/* Panel lateral */
[data-bs-theme="light"] .mrp-panel,
[data-bs-theme="light"] .mrp-kpi,
[data-bs-theme="light"] .mrp-station-card,
[data-bs-theme="light"] .mrp-sub-card,
[data-bs-theme="light"] .mrp-mini-card,
[data-bs-theme="light"] .mrp-actions-card {
  background: #ffffff;
  border: 1px solid rgba(0,0,0,.08);
  box-shadow: 0 6px 16px rgba(0,0,0,.06);
}

/* Texto */
[data-bs-theme="light"] .mrp-panel-title,
[data-bs-theme="light"] .mrp-station-title,
[data-bs-theme="light"] .mrp-sub-title,
[data-bs-theme="light"] .mrp-kpi .kpi-value {
  color: #1e293b;
}

[data-bs-theme="light"] .mrp-panel-subtitle,
[data-bs-theme="light"] .mrp-station-text,
[data-bs-theme="light"] .mrp-sub-text,
[data-bs-theme="light"] .mrp-kpi .kpi-desc {
  color: #64748b;
}

/* Pills */
[data-bs-theme="light"] .mrp-info-pill {
  background: #f1f5f9;
  border: 1px solid #e2e8f0;
  color: #334155;
}

/* Botones pequeños */
[data-bs-theme="light"] .mrp-chip-btn {
  background: #f1f5f9;
  color: #334155;
  border: 1px solid #e2e8f0;
}

[data-bs-theme="light"] .mrp-chip-btn:hover {
  background: #e2e8f0;
}

/* Subensambles */
[data-bs-theme="light"] .mrp-sub-card {
  background: #f8fafc;
  border: 1px solid #dbeafe;
}

/* Caja de orden */
[data-bs-theme="light"] .mrp-order-box {
  background: #f8fafc;
  border: 1px dashed #cbd5e1;
}

[data-bs-theme="light"] .mrp-order-box.has-unit {
  background: #e0f2fe;
  border: 1px solid #bae6fd;
}

/* Botones principales */
[data-bs-theme="light"] .mrp-btn-main {
  background: linear-gradient(135deg, #ff8a1f, #ff5f1f);
}

[data-bs-theme="light"] .mrp-btn-secondary {
  background: linear-gradient(135deg, #22c55e, #16a34a);
  color: #fff;
}

[data-bs-theme="light"] .mrp-btn-dark {
  background: #e2e8f0;
  color: #1e293b;
}

/* KPI */
[data-bs-theme="light"] .mrp-kpi::after {
  background: radial-gradient(circle, rgba(59,130,246,.08), transparent 60%);
}

/* Flechas */
[data-bs-theme="light"] .mrp-arrow::before {
  background: linear-gradient(90deg, #94a3b8, #64748b);
}

[data-bs-theme="light"] .mrp-arrow::after {
  border-top: 2px solid #64748b;
  border-right: 2px solid #64748b;
}

/* Nota */
[data-bs-theme="light"] .mrp-soft-note {
  background: #e0f2fe;
  border: 1px solid #bae6fd;
  color: #0369a1;
}
</style>

<div id="contentAjax"></div>

<div class="main-content">
  <div class="page-content">
    <div class="container-fluid">

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
                    <h2 class="mrp-panel-title">
                      Estación <?= esc($estacionAsignada['nombre_estacion'] ?? '--') ?>
                    </h2>
                    <div class="mrp-panel-subtitle">
                      Línea SUV-02 · Proceso estándar <?= esc($estacionAsignada['estandar'] ?? '-') ?><br>
                      Tiempo objetivo: <?= esc($estacionAsignada['tiempo_ajuste'] ?? '-') ?> min
                    </div>
                  </div>

                  <div class="mrp-pill-live">
                    Unidad lista para iniciar
                  </div>
                </div>

                <div class="mrp-btn-grid-top">
                  <button type="button" class="mrp-btn-main" id="btnIniciarUnidad">
                    Iniciar unidad
                  </button>
                  <button type="button" class="mrp-btn-secondary" id="btnFinalizarUnidad">
                    Finalizar proceso
                  </button>
                  <button type="button" class="mrp-btn-dark" id="btnPausarUnidad">
                    Pausar / Paro
                  </button>
                  <button type="button" class="mrp-btn-dark" id="btnReiniciarDemo">
                    Reiniciar simulación
                  </button>
                </div>

                <div class="mrp-current-unit">
                  <div class="mrp-label">Unidad actual en tu estación</div>
                  <div class="mrp-vin"><?= esc($unidadActual['num_sub_orden'] ?? 'SIN-UNIDAD') ?></div>

                  <div class="d-flex align-items-center gap-2 flex-wrap mb-3">
                    <span class="mrp-status-soft">Esperando inicio</span>
                    <?= badgePrioridad($ot['prioridad'] ?? '') ?>
                  </div>

                  <div class="text-light-emphasis" style="line-height:1.55;">
                    <div><strong>Modelo:</strong> <?= esc($ot['descripcion'] ?? '-') ?></div>
                    <div><strong>Supervisor:</strong> <?= esc($ot['supervisor'] ?? '-') ?></div>
                    <div><strong>Pedido:</strong> <?= esc($ot['num_pedido'] ?? '-') ?></div>
                  </div>
                </div>

                <div class="mrp-stat-grid">
                  <div class="mrp-mini-card">
                    <div class="mrp-label">Tiempo actual</div>
                    <div class="value" id="tiempoActualEstacion">00:00</div>
                  </div>

                  <div class="mrp-mini-card">
                    <div class="mrp-label">Capacidad</div>
                    <div class="value">1/1 unidad</div>
                  </div>

                  <div class="mrp-mini-card">
                    <div class="mrp-label">Siguiente destino</div>
                    <div class="value">
                      <?php
                      $idxAsignada = 0;
                      $siguiente = $estaciones[$idxAsignada + 1]['nombre_estacion'] ?? 'Fin';
                      echo esc($siguiente);
                      ?>
                    </div>
                  </div>

                  <div class="mrp-mini-card">
                    <div class="mrp-label">Total en estación</div>
                    <div class="value"><?= count($estacionAsignada['ordenes_trabajo'] ?? []) ?></div>
                  </div>
                </div>

                <div class="mrp-actions-card">
                  <h6>Proceso asignado al operador</h6>
                  <p>
                    Ejecutar el proceso <strong><?= esc($estacionAsignada['proceso'] ?? '-') ?></strong>,
                    validar herramientas requeridas, revisar componentes, completar operaciones estándar
                    y liberar la unidad al siguiente paso del flujo productivo.
                  </p>
                </div>

                <div class="mrp-legend">
                  <div class="mrp-legend-item">
                    <span class="mrp-dot dot-station"></span>
                    <span>Tu estación asignada</span>
                  </div>
                  <div class="mrp-legend-item">
                    <span class="mrp-dot dot-liberada"></span>
                    <span>Estación liberada</span>
                  </div>
                  <div class="mrp-legend-item">
                    <span class="mrp-dot dot-proceso"></span>
                    <span>Unidad en proceso</span>
                  </div>
                  <div class="mrp-legend-item">
                    <span class="mrp-dot dot-espera"></span>
                    <span>Esperando unidad</span>
                  </div>
                </div>
              </aside>

              <section class="mrp-content">
                <div class="mrp-top">
                  <div class="mrp-kpi">
                    <div class="kpi-title">Orden de trabajo</div>
                    <div class="kpi-value"><?= esc($ot['num_orden'] ?? '-') ?></div>
                    <div class="kpi-desc"><?= esc($ot['cve_producto'] ?? '-') ?> · <?= esc($ot['descripcion'] ?? '-') ?></div>
                  </div>

                  <div class="mrp-kpi">
                    <div class="kpi-title">Disponibles o en tránsito</div>
                    <div class="kpi-value"><?= $totalPendientes ?></div>
                    <div class="kpi-desc">Pendientes de ejecutar en estaciones o subensambles</div>
                  </div>

                  <div class="mrp-kpi">
                    <div class="kpi-title">En proceso</div>
                    <div class="kpi-value"><?= $totalEnProceso ?></div>
                    <div class="kpi-desc">Unidades activas actualmente en la corrida</div>
                  </div>

                  <div class="mrp-kpi">
                    <div class="kpi-title">Tiempo de la unidad</div>
                    <div class="kpi-value" id="tiempoUnidadGlobal">00:00</div>
                    <div class="kpi-desc">Seguimiento visual de la unidad en tu estación</div>
                  </div>
                </div>

                <div class="mrp-flow-card">
                  <div class="mrp-flow-header">
                    <div>
                      <h5 class="mrp-flow-title">Trazabilidad visual completa de estaciones y sub-ensambles</h5>
                      <p class="mrp-flow-subtitle">
                        Los sub-ensambles se muestran arriba de la estación a la que pertenecen. Las estaciones permanecen siempre alineadas en la misma línea base.
                      </p>
                    </div>

                    <div class="d-flex gap-2 flex-wrap">
                      <button type="button" class="mrp-collapse-btn" id="btnExpandirTodo">
                        Expandir todo
                      </button>
                      <button type="button" class="mrp-collapse-btn" id="btnContraerTodo">
                        Contraer subensambles
                      </button>
                    </div>
                  </div>

                  <div class="mrp-scroll">
                    <div class="mrp-flow-row">

                      <?php foreach ($estaciones as $i => $est): ?>
                        <?php
                        $subensambles = $est['subensambles'] ?? [];
                        $ordenes = $est['ordenes_trabajo'] ?? [];
                        $unidad = $ordenes[0] ?? [];
                        $esAsignada = ((int)($est['id_planeacion_estacion'] ?? 0) === (int)($estacionAsignada['id_planeacion_estacion'] ?? 0));
                        ?>

                        <div class="mrp-station-wrap">

                          <div class="mrp-sub-zone">
                            <?php if (!empty($subensambles)): ?>
                              <?php foreach ($subensambles as $idxSub => $sub): ?>
                                <?php $subOT = $sub['ordenes_trabajo'][0] ?? []; ?>
                                <div class="mrp-sub-card bloque-subensamble">
                                  <div class="mrp-sub-top">
                                    <span class="mrp-chip chip-sub-id">
                                      S<?= ($i + 1) ?>-<?= chr(65 + $idxSub) ?>
                                    </span>
                                    <span class="mrp-chip chip-linked">
                                      Ligado a E<?= str_pad((string)($i + 1), 2, '0', STR_PAD_LEFT) ?>
                                    </span>
                                  </div>

                                  <div class="mrp-sub-title">
                                    <?= esc($sub['nombre_estacion'] ?? '-') ?>
                                  </div>
                                  <div class="mrp-sub-text">
                                    <?= esc($sub['proceso'] ?? '-') ?>
                                  </div>

                                  <div class="mrp-info-stack">
                                    <div class="mrp-info-pill">
                                      <strong>Proceso:</strong> <?= esc($sub['proceso'] ?? '-') ?>
                                    </div>
                                    <div class="mrp-info-pill">
                                      <strong>Encargado:</strong> <?= esc($sub['encargados'][0]['nombre_completo'] ?? '-') ?>
                                    </div>
                                    <div class="mrp-info-pill">
                                      <strong>Ayudante:</strong> <?= esc($sub['ayudantes'][0]['nombre_completo'] ?? '-') ?>
                                    </div>
                                    <div class="mrp-info-pill">
                                      <strong>Tiempo de ajuste:</strong> <?= esc($sub['tiempo_ajuste'] ?? '-') ?> min
                                    </div>
                                  </div>

                                  <div class="mrp-station-actions">
                                    <button type="button" class="mrp-chip-btn btn-herramientas-sub"
                                      data-id="<?= esc($sub['idsubensamble'] ?? 0) ?>"
                                      data-nombre="<?= esc($sub['nombre_estacion'] ?? '') ?>">
                                      Herramientas
                                    </button>
                                    <button type="button" class="mrp-chip-btn btn-componentes-sub"
                                      data-id="<?= esc($sub['idsubensamble'] ?? 0) ?>"
                                      data-nombre="<?= esc($sub['nombre_estacion'] ?? '') ?>">
                                      Componentes
                                    </button>
                                    <button type="button" class="mrp-chip-btn btn-operaciones-sub"
                                      data-id="<?= esc($sub['idsubensamble'] ?? 0) ?>"
                                      data-nombre="<?= esc($sub['nombre_estacion'] ?? '') ?>">
                                      Operaciones
                                    </button>
                                  </div>

                                  <div class="mrp-order-box <?= !empty($subOT) ? 'has-unit' : '' ?>">
                                    <?php if (!empty($subOT)): ?>
                                      <div class="d-flex justify-content-between align-items-start gap-2 mb-2">
                                        <div class="mrp-order-id"><?= esc($subOT['num_sub_orden'] ?? '-') ?></div>
                                        <?= badgeEstadoOrden($subOT['estado'] ?? 1) ?>
                                      </div>
                                      <p class="mrp-order-desc">
                                        Código scan: <strong><?= esc($subOT['codigo_scan'] ?? '-') ?></strong><br>
                                        Subensamble listo para ejecutarse antes de la estación principal.
                                      </p>
                                    <?php else: ?>
                                      <p class="mrp-order-desc mb-0">
                                        Sin unidad en este sub-ensamble. Esperando recepción del proceso anterior.
                                      </p>
                                    <?php endif; ?>
                                  </div>
                                </div>
                              <?php endforeach; ?>
                            <?php endif; ?>
                          </div>

                          <div class="mrp-station-card <?= $esAsignada ? 'active-station' : '' ?>">
                            <div class="mrp-station-top">
                              <div class="d-flex align-items-center gap-3">
                                <span class="mrp-station-number"><?= str_pad((string)($i + 1), 2, '0', STR_PAD_LEFT) ?></span>
                                <div>
                                  <div class="mrp-station-title">
                                    <?= esc($est['nombre_estacion'] ?? '-') ?>
                                  </div>
                                  <div class="mrp-station-text">
                                    <?= esc($est['proceso'] ?? '-') ?>
                                  </div>
                                </div>
                              </div>

                              <div class="d-flex flex-column align-items-end gap-2">
                                <span class="mrp-chip chip-wait">En espera</span>
                                <?php if ($esAsignada): ?>
                                  <span class="mrp-badge-assigned">Tu estación</span>
                                <?php endif; ?>
                              </div>
                            </div>

                            <div class="mrp-info-stack">
                              <div class="mrp-info-pill">
                                <strong>Proceso:</strong> <?= esc($est['proceso'] ?? '-') ?>
                              </div>
                              <div class="mrp-info-pill">
                                <strong>Encargado:</strong> <?= esc($est['encargados'][0]['nombre_completo'] ?? '-') ?>
                              </div>
                              <div class="mrp-info-pill">
                                <strong>Ayudante:</strong> <?= esc($est['ayudantes'][0]['nombre_completo'] ?? '-') ?>
                              </div>
                              <div class="mrp-info-pill">
                                <strong>Tiempo de ajuste:</strong> <?= esc($est['tiempo_ajuste'] ?? '-') ?> min
                              </div>
                            </div>

                            <div class="mrp-station-actions">
                              <button type="button" class="mrp-chip-btn btn-herramientas-est"
                                data-id="<?= esc($est['idestacion'] ?? 0) ?>"
                                data-nombre="<?= esc($est['nombre_estacion'] ?? '') ?>">
                                Herramientas
                              </button>

                              <button type="button" class="mrp-chip-btn btn-componentes-est"
                                data-id="<?= esc($est['idestacion'] ?? 0) ?>"
                                data-nombre="<?= esc($est['nombre_estacion'] ?? '') ?>">
                                Componentes
                              </button>

                              <button type="button" class="mrp-chip-btn btn-operaciones-est"
                                data-id="<?= esc($est['idestacion'] ?? 0) ?>"
                                data-nombre="<?= esc($est['nombre_estacion'] ?? '') ?>">
                                Operaciones
                              </button>
                            </div>

                            <div class="mrp-order-box <?= !empty($unidad) ? 'has-unit' : '' ?>">
                              <?php if (!empty($unidad)): ?>
                                <div class="d-flex justify-content-between align-items-start gap-2 mb-2">
                                  <div class="mrp-order-id"><?= esc($unidad['num_sub_orden'] ?? '-') ?></div>
                                  <?= badgeEstadoOrden($unidad['estatus'] ?? 1) ?>
                                </div>
                                <p class="mrp-order-desc">
                                  Esta unidad está ligada a la corrida de la estación y podrá avanzar una vez completado el proceso requerido y, si existe, su sub-ensamble previo.
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
                    los sub-ensambles permanecen arriba y ligados a su estación. Las estaciones quedan siempre alineadas en la línea principal aunque una columna no tenga sub-ensamble.
                  </div>
                </div>
              </section>
            </div>
          </div>
        </div>

      <?php endif; ?>

    </div>
  </div>

  <footer class="footer">
    <div class="container-fluid">
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

<script>
  document.addEventListener('DOMContentLoaded', function () {
    let timer = null;
    let seconds = 0;

    const tiempoActualEstacion = document.getElementById('tiempoActualEstacion');
    const tiempoUnidadGlobal = document.getElementById('tiempoUnidadGlobal');

    const btnIniciarUnidad = document.getElementById('btnIniciarUnidad');
    const btnFinalizarUnidad = document.getElementById('btnFinalizarUnidad');
    const btnPausarUnidad = document.getElementById('btnPausarUnidad');
    const btnReiniciarDemo = document.getElementById('btnReiniciarDemo');
    const btnExpandirTodo = document.getElementById('btnExpandirTodo');
    const btnContraerTodo = document.getElementById('btnContraerTodo');

    function formatTime(totalSeconds) {
      const mins = Math.floor(totalSeconds / 60).toString().padStart(2, '0');
      const secs = (totalSeconds % 60).toString().padStart(2, '0');
      return `${mins}:${secs}`;
    }

    function renderTime() {
      const t = formatTime(seconds);
      if (tiempoActualEstacion) tiempoActualEstacion.textContent = t;
      if (tiempoUnidadGlobal) tiempoUnidadGlobal.textContent = t;
    }

    function iniciarTimer() {
      if (timer) return;
      timer = setInterval(() => {
        seconds++;
        renderTime();
      }, 1000);
    }

    function pausarTimer() {
      if (timer) {
        clearInterval(timer);
        timer = null;
      }
    }

    function reiniciarTimer() {
      pausarTimer();
      seconds = 0;
      renderTime();
    }

    if (btnIniciarUnidad) {
      btnIniciarUnidad.addEventListener('click', function () {
        iniciarTimer();

        if (window.Swal) {
          Swal.fire({
            icon: 'success',
            title: 'Unidad iniciada',
            text: 'La simulación visual de producción comenzó correctamente.',
            timer: 1600,
            showConfirmButton: false,
            background: '#0f172a',
            color: '#fff'
          });
        }
      });
    }

    if (btnPausarUnidad) {
      btnPausarUnidad.addEventListener('click', function () {
        pausarTimer();

        if (window.Swal) {
          Swal.fire({
            icon: 'warning',
            title: 'Producción en pausa',
            text: 'El proceso fue pausado temporalmente.',
            timer: 1600,
            showConfirmButton: false,
            background: '#0f172a',
            color: '#fff'
          });
        }
      });
    }

    if (btnFinalizarUnidad) {
      btnFinalizarUnidad.addEventListener('click', function () {
        pausarTimer();

        if (window.Swal) {
          Swal.fire({
            icon: 'success',
            title: 'Proceso finalizado',
            text: 'La unidad fue marcada visualmente como finalizada en esta estación.',
            confirmButtonText: 'Entendido',
            background: '#0f172a',
            color: '#fff'
          });
        }
      });
    }

    if (btnReiniciarDemo) {
      btnReiniciarDemo.addEventListener('click', function () {
        reiniciarTimer();

        if (window.Swal) {
          Swal.fire({
            icon: 'info',
            title: 'Simulación reiniciada',
            text: 'Se reinició el contador y el estado visual.',
            timer: 1500,
            showConfirmButton: false,
            background: '#0f172a',
            color: '#fff'
          });
        }
      });
    }

if (btnExpandirTodo) {
  btnExpandirTodo.addEventListener('click', function () {
    document.querySelectorAll('.bloque-subensamble').forEach(el => {
      el.style.display = '';
    });

    document.querySelectorAll('.mrp-sub-zone').forEach(zone => {
      zone.classList.remove('is-collapsed');
    });
  });
}

if (btnContraerTodo) {
  btnContraerTodo.addEventListener('click', function () {
    document.querySelectorAll('.bloque-subensamble').forEach(el => {
      el.style.display = 'none';
    });

    document.querySelectorAll('.mrp-sub-zone').forEach(zone => {
      zone.classList.add('is-collapsed');
    });
  });
}

    function modalDemo(title, text) {
      if (window.Swal) {
        Swal.fire({
          title: title,
          text: text,
          icon: 'info',
          confirmButtonText: 'Cerrar',
          background: '#0f172a',
          color: '#fff'
        });
      }
    }

    document.querySelectorAll('.btn-herramientas-est, .btn-herramientas-sub').forEach(btn => {
      btn.addEventListener('click', function () {
        modalDemo(
          'Herramientas necesarias',
          `Aquí puedes abrir el modal o consulta AJAX de herramientas para: ${this.dataset.nombre || ''}`
        );
      });
    });

    document.querySelectorAll('.btn-componentes-est, .btn-componentes-sub').forEach(btn => {
      btn.addEventListener('click', function () {
        modalDemo(
          'Componentes necesarios',
          `Aquí puedes abrir el modal o consulta AJAX de componentes para: ${this.dataset.nombre || ''}`
        );
      });
    });

    document.querySelectorAll('.btn-operaciones-est, .btn-operaciones-sub').forEach(btn => {
      btn.addEventListener('click', function () {
        modalDemo(
          'Operaciones a realizar',
          `Aquí puedes abrir el modal o consulta AJAX de operaciones para: ${this.dataset.nombre || ''}`
        );
      });
    });

    renderTime();
  });
</script>

<?php footerAdmin($data); ?>