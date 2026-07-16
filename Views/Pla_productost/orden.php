<?php
headerAdmin($data);

$resp = $data['arrOrdenDetalle'] ?? [];

$header = $resp['header'] ?? [];
$finalizadas = $resp['unidades_finalizadas'] ?? [];
$pendientes = $resp['unidades_pendientes'] ?? [];

function hOrden($value)
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function fechaOrden($fecha)
{
    if (empty($fecha) || $fecha === '0000-00-00 00:00:00') {
        return 'Sin fecha';
    }

    return date('d/m/Y H:i', strtotime($fecha));
}

function tiempoOrden($minutos)
{
    $minutos = (int) $minutos;

    if ($minutos <= 0) {
        return '0 min';
    }

    $horas = floor($minutos / 60);
    $mins = $minutos % 60;

    if ($horas > 0) {
        return $horas . ' h ' . $mins . ' min';
    }

    return $mins . ' min';
}
?>

<div class="container-fluid">

    <?php if (empty($header)) { ?>

        <div class="alert alert-warning">
            No se encontró información de la orden de trabajo.
        </div>

    <?php } else { ?>

        <div class="row mb-4">

            <div class="col-xl-3 col-md-6 mb-3">
                <div class="card shadow-sm border-0 h-100">
                    <div class="card-body">
                        <div class="text-muted small">Número de orden</div>
                        <h4 class="mb-0">
                            <?= hOrden($header['num_orden'] ?? '') ?>
                        </h4>
                    </div>
                </div>
            </div>

            <div class="col-xl-3 col-md-6 mb-3">
                <div class="card shadow-sm border-0 h-100">
                    <div class="card-body">
                        <div class="text-muted small">Supervisor</div>
                        <h5 class="mb-0">
                            <?= hOrden($header['supervisor_nombre'] ?? 'Sin supervisor') ?>
                        </h5>
                    </div>
                </div>
            </div>

            <div class="col-xl-2 col-md-6 mb-3">
                <div class="card shadow-sm border-0 h-100">
                    <div class="card-body">
                        <div class="text-muted small">Cantidad planeada</div>
                        <h4 class="mb-0">
                            <?= hOrden($header['cantidad'] ?? 0) ?>
                        </h4>
                    </div>
                </div>
            </div>

            <div class="col-xl-2 col-md-6 mb-3">
                <div class="card shadow-sm border-0 h-100">
                    <div class="card-body">
                        <div class="text-muted small">Finalizadas</div>
                        <h4 class="mb-0 text-success">
                            <?= hOrden($header['total_finalizadas'] ?? 0) ?>
                        </h4>
                    </div>
                </div>
            </div>

            <div class="col-xl-2 col-md-6 mb-3">
                <div class="card shadow-sm border-0 h-100">
                    <div class="card-body">
                        <div class="text-muted small">Pendientes</div>
                        <h4 class="mb-0 text-warning">
                            <?= hOrden($header['total_pendientes'] ?? 0) ?>
                        </h4>
                    </div>
                </div>
            </div>

        </div>
        <div class="card border-0 shadow-sm mb-4 overflow-hidden">

            <div class="card-body p-0">

                <div class="row g-0">

                    <div class="col-lg-8 p-4">

                        <div class="d-flex align-items-start justify-content-between flex-wrap gap-3 mb-4">



                            <div>



                                <div class="text-muted small text-uppercase fw-semibold mb-1">
                                    <button class="btn btn-soft-secondary btn-sm" type="button" id="btnVolverHome2">

                                        <i class="ri-arrow-left-line"></i>

                                    </button> Orden de trabajo
                                </div>

                                <h2 class="fw-bold mb-1">
                                    <?= hOrden($header['num_orden'] ?? '') ?>
                                </h2>

                                <div class="text-muted">
                                    Pedido:
                                    <strong class="text-dark">
                                        <?= hOrden($header['num_pedido'] ?? 'N/A') ?>
                                    </strong>
                                </div>
                            </div>

                            <div class="text-end">

                                <?php
                                $prioridad = strtoupper((string) ($header['prioridad'] ?? ''));
                                $classPrioridad = 'bg-secondary';

                                if ($prioridad === 'CRITICA' || $prioridad === 'CRÍTICA') {
                                    $classPrioridad = 'bg-danger';
                                } elseif ($prioridad === 'ALTA') {
                                    $classPrioridad = 'bg-warning text-dark';
                                } elseif ($prioridad === 'MEDIA') {
                                    $classPrioridad = 'bg-info text-dark';
                                } elseif ($prioridad === 'BAJA') {
                                    $classPrioridad = 'bg-secondary';
                                } elseif ($prioridad === 'PROTOTIPO') {
                                    $classPrioridad = 'bg-dark';
                                }
                                ?>

                                <span class="badge <?= $classPrioridad ?> px-3 py-2 fs-6">
                                    <?= hOrden($header['prioridad'] ?? 'Sin prioridad') ?>
                                </span>

                            </div>

                        </div>

                        <div class="row g-3">

                            <div class="col-md-6 col-xl-4">
                                <div class="border rounded-3 p-3 h-100 bg-light">
                                    <div class="text-muted small mb-1">
                                        Supervisor de orden
                                    </div>
                                    <div class="fw-bold">
                                        <i class="ri-user-star-line me-1"></i>
                                        <?= hOrden($header['supervisor_nombre'] ?? 'Sin supervisor') ?>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-6 col-xl-4">
                                <div class="border rounded-3 p-3 h-100 bg-light">
                                    <div class="text-muted small mb-1">
                                        Piezas a construir
                                    </div>
                                    <div class="fw-bold fs-5">
                                        <i class="ri-stack-line me-1"></i>
                                        <?= hOrden($header['cantidad'] ?? 0) ?> unidades
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-6 col-xl-4">
                                <div class="border rounded-3 p-3 h-100 bg-light">
                                    <div class="text-muted small mb-1">
                                        Fecha requerida
                                    </div>
                                    <div class="fw-bold">
                                        <i class="ri-calendar-check-line me-1"></i>
                                        <?= fechaOrden($header['fecha_requerida'] ?? '') ?>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-6 col-xl-4">
                                <div class="border rounded-3 p-3 h-100 bg-light">
                                    <div class="text-muted small mb-1">
                                        Inicio planeado
                                    </div>
                                    <div class="fw-bold">
                                        <i class="ri-calendar-event-line me-1"></i>
                                        <?= fechaOrden($header['fecha_inicio'] ?? '') ?>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-6 col-xl-4">
                                <div class="border rounded-3 p-3 h-100 bg-light">
                                    <div class="text-muted small mb-1">
                                        Fin planeado
                                    </div>
                                    <div class="fw-bold">
                                        <i class="ri-calendar-todo-line me-1"></i>
                                        <?= fechaOrden($header['fecha_fin'] ?? '') ?>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-6 col-xl-4">
                                <div class="border rounded-3 p-3 h-100 bg-light">
                                    <div class="text-muted small mb-1">
                                        Planta
                                    </div>

                                    <div class="fw-bold">
                                        <i class="ri-building-2-line me-1"></i>
                                        <?= hOrden($header['nombre_planta'] ?? 'Sin planta') ?>
                                    </div>

                                    <div class="text-muted small mt-1">
                                        <?= hOrden($header['cve_planta'] ?? '') ?>
                                        <?php if (!empty($header['direccion_planta'])) { ?>
                                            · <?= hOrden($header['direccion_planta']) ?>
                                        <?php } ?>
                                    </div>
                                </div>
                            </div>

                        </div>

                    </div>

                    <div class="col-lg-4 bg-light border-start p-4">

                        <div class="mb-4">
                            <div class="text-muted small text-uppercase fw-semibold mb-2">
                                Avance de unidades
                            </div>

                            <?php
                            $cantidad = (int) ($header['cantidad'] ?? 0);
                            $finalizadasCount = (int) ($header['total_finalizadas'] ?? 0);
                            $pendientesCount = (int) ($header['total_pendientes'] ?? 0);

                            $porcentaje = 0;
                            if ($cantidad > 0) {
                                $porcentaje = round(($finalizadasCount / $cantidad) * 100);
                            }
                            ?>

                            <div class="d-flex justify-content-between align-items-end mb-2">
                                <div>
                                    <div class="fs-2 fw-bold">
                                        <?= $porcentaje ?>%
                                    </div>
                                    <div class="text-muted small">
                                        avance general
                                    </div>
                                </div>

                                <div class="text-end">
                                    <div class="fw-bold text-success">
                                        <?= $finalizadasCount ?> finalizadas
                                    </div>
                                    <div class="fw-bold text-warning">
                                        <?= $pendientesCount ?> pendientes
                                    </div>
                                </div>
                            </div>

                            <div class="progress" style="height: 10px;">
                                <div class="progress-bar bg-success" role="progressbar" style="width: <?= $porcentaje ?>%;">
                                </div>
                            </div>
                        </div>

                        <div>
                            <div class="text-muted small text-uppercase fw-semibold mb-2">
                                Notas de planeación
                            </div>

                            <div class="border rounded-3 bg-body-secondary p-3" style="min-height: 130px;">
                                <?php if (!empty($header['notas'])) { ?>
                                    <div class="text-dark">
                                        <?= nl2br(hOrden($header['notas'])) ?>
                                    </div>
                                <?php } else { ?>
                                    <div class="text-muted">
                                        Sin notas registradas para esta orden.
                                    </div>
                                <?php } ?>
                            </div>
                        </div>

                    </div>

                </div>

            </div>

        </div>

        <div class="card shadow-sm border-0">

            <div class="card-header bg-body">
                <ul class="nav nav-tabs card-header-tabs" id="tabsOrdenTrabajo" role="tablist">

                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="tab-finalizadas" data-bs-toggle="tab"
                            data-bs-target="#content-finalizadas" type="button" role="tab">
                            Unidades finalizadas
                            <span class="badge bg-success ms-1">
                                <?= count($finalizadas) ?>
                            </span>
                        </button>
                    </li>

                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="tab-pendientes" data-bs-toggle="tab"
                            data-bs-target="#content-pendientes" type="button" role="tab">
                            Pendientes / En proceso
                            <span class="badge bg-warning text-dark ms-1">
                                <?= count($pendientes) ?>
                            </span>
                        </button>
                    </li>

                </ul>
            </div>

            <div class="card-body">

                <div class="tab-content">

                    <div class="tab-pane fade show active" id="content-finalizadas" role="tabpanel">

                        <?php if (empty($finalizadas)) { ?>

                            <div class="alert alert-info mb-0">
                                Todavía no hay unidades completamente finalizadas.
                            </div>

                        <?php } else { ?>

                            <div class="table-responsive">

                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <label class="form-label small text-muted mb-1">
                                            Buscar unidad, VIN, VIN origen, motor o transmisión
                                        </label>
                                        <input type="text" class="form-control" id="buscarUnidadesFinalizadas"
                                            placeholder="Ejemplo: OT260608-001-U03, VIN, motor...">
                                    </div>
                                </div>
                                <table class="table table-hover align-middle">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Unidad</th>  
                                            <th>VIN asignado</th>
                                            <th>Motor</th>
                                            <th>Transmisión</th>
                                            <th>VIN origen</th>
                                            <th>Inicio producción</th>
                                            <th>Fin producción</th>
                                            <th>Tiempo armado</th>
                                            <th>Usuario VIN</th>
                                            <th>Fecha asignación VIN</th>
                                            <th class="text-end">PDF</th>
                                        </tr>
                                    </thead>

                                    <tbody>
                                        <?php foreach ($finalizadas as $unidad) { ?>
                                            <tr class="row-unidad-finalizada" data-search="<?= hOrden(strtolower(
                                                                                                trim(
                                                                                                    ($unidad['num_sub_orden'] ?? '') . ' ' .
                                                                                                        ($unidad['numero_serie'] ?? '') . ' ' .
                                                                                                        ($unidad['vin_origen'] ?? '') . ' ' .
                                                                                                        ($unidad['numero_motor'] ?? '') . ' ' .
                                                                                                        ($unidad['numero_transmision'] ?? '')
                                                                                                )
                                                                                            )) ?>">
                                                <td>
                                                    <div class="d-flex align-items-center gap-2">
                                                        <div class="avatar-xs">
                                                            <div class="avatar-title rounded-circle bg-success-subtle text-success">
                                                                <i class="ri-checkbox-circle-line"></i>
                                                            </div>
                                                        </div>

                                                        <div>
                                                            <div class="fw-bold">
                                                                <?= hOrden($unidad['num_sub_orden'] ?? '') ?>
                                                            </div>
                                                            <div class="text-muted small">
                                                                Unidad finalizada
                                                            </div>
                                                        </div>
                                                    </div>
                                                </td>

                                                <td>
                                                    <?php if (!empty($unidad['numero_serie'])) { ?>
                                                        <div class="d-flex align-items-center gap-2">
                                                            <i class="ri-barcode-line text-primary fs-5"></i>
                                                            <span class="badge bg-primary">
                                                                <?= hOrden($unidad['numero_serie']) ?>
                                                            </span>
                                                        </div>
                                                    <?php } else { ?>
                                                        <span class="badge bg-secondary">
                                                            <i class="ri-error-warning-line me-1"></i>
                                                            Sin VIN
                                                        </span>
                                                    <?php } ?>
                                                </td>

                                                <td>
                                                    <i class="ri-settings-3-line text-muted me-1"></i>
                                                    <?= hOrden($unidad['numero_motor'] ?? 'N/A') ?>
                                                </td>

                                                <td>
                                                    <i class="ri-tools-line text-muted me-1"></i>
                                                    <?= hOrden($unidad['numero_transmision'] ?? 'N/A') ?>
                                                </td>

                                                <td>
                                                    <i class="ri-map-pin-2-line text-muted me-1"></i>
                                                    <?= hOrden($unidad['vin_origen'] ?? 'N/A') ?>
                                                </td>

                                                <td>
                                                    <i class="ri-play-circle-line text-success me-1"></i>
                                                    <?= fechaOrden($unidad['fecha_inicio_produccion'] ?? '') ?>
                                                </td>

                                                <td>
                                                    <i class="ri-flag-2-line text-danger me-1"></i>
                                                    <?= fechaOrden($unidad['fecha_fin_produccion'] ?? '') ?>
                                                </td>

                                                <td>
                                                    <span class="badge bg-info-subtle text-info border">
                                                        <i class="ri-time-line me-1"></i>
                                                        <?= tiempoOrden($unidad['minutos_armado'] ?? 0) ?>
                                                    </span>
                                                </td>


                                                <td>
                                                    <i class="ri-user-check-line text-muted me-1"></i>
                                                    <?= hOrden($unidad['usuario_vin'] ?? 'N/A') ?>
                                                </td>

                                                <td>
                                                    <?= fechaOrden($unidad['fecha_asignacion'] ?? '') ?>
                                                </td>

                                            <td class="text-end">
   <button type="button"
        class="btn btn-soft-danger btn-sm btnPdfUnidad"
        data-unidad="<?= htmlspecialchars($unidad['num_sub_orden'] ?? '') ?>">
    <i class="ri-file-pdf-2-line me-1"></i>
    PDF
</button>
</td>
                                            </tr>
                                        <?php } ?>
                                    </tbody>


                                    <div id="mensajeSinUnidadesFinalizadas" class="alert alert-warning mt-3"
                                        style="display:none;">
                                        No se encontraron unidades finalizadas con ese criterio de búsqueda.
                                    </div>
                                </table>
                            </div>

                        <?php } ?>

                    </div>

                    <div class="tab-pane fade" id="content-pendientes" role="tabpanel">

                        <?php if (empty($pendientes)) { ?>

                            <div class="alert alert-success mb-0">
                                Todas las unidades de esta orden ya fueron finalizadas.
                            </div>

                        <?php } else { ?>

                            <div class="table-responsive">
                                <table class="table table-hover align-middle">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Unidad</th>
                                            <th>Estado unidad</th>
                                            <th>Acción activa</th>
                                            <th>Total estaciones</th>
                                            <th>Finalizadas</th>
                                            <th>En proceso</th>
                                            <th>Pendientes</th>
                                            <th>Inicio producción</th>
                                            <th>Última fecha fin</th>
                                        </tr>
                                    </thead>

                                    <tbody>
                                        <?php foreach ($pendientes as $unidad) { ?>

                                            <?php
                                            $accionActiva = (int) ($unidad['accion_activa'] ?? 1);
                                            $accionProduccion = (int) ($unidad['accion_produccion_activa'] ?? 0);
                                            ?>

                                            <tr>
                                                <td>
                                                    <strong>
                                                        <?= hOrden($unidad['num_sub_orden'] ?? '') ?>
                                                    </strong>
                                                </td>

                                                <td>
                                                    <?php if (($unidad['estado_unidad'] ?? '') === 'EN PROCESO') { ?>
                                                        <span class="badge bg-info text-dark">
                                                            EN PROCESO
                                                        </span>
                                                    <?php } else { ?>
                                                        <span class="badge bg-warning text-dark">
                                                            PENDIENTE
                                                        </span>
                                                    <?php } ?>
                                                </td>

                                                <td>
                                                    <?php if ($accionActiva === 2 && $accionProduccion > 0) { ?>

                                                        <?php if ($accionProduccion === 1) { ?>
                                                            <span class="badge bg-danger-subtle text-danger border">
                                                                Paro momentáneo activo
                                                            </span>

                                                        <?php } elseif ($accionProduccion === 2) { ?>
                                                            <span class="badge bg-dark">
                                                                Retiro AGV activo
                                                            </span>

                                                        <?php } elseif ($accionProduccion === 3) { ?>
                                                            <span class="badge bg-warning text-dark">
                                                                Unidad alarmada
                                                            </span>

                                                        <?php } elseif ($accionProduccion === 4) { ?>
                                                            <span class="badge bg-info text-dark">
                                                                Solicitud asistencia
                                                            </span>

                                                        <?php } elseif ($accionProduccion === 5) { ?>
                                                            <span class="badge bg-secondary">
                                                                Falta materia
                                                            </span>

                                                        <?php } else { ?>
                                                            <span class="badge bg-secondary">
                                                                Acción activa
                                                            </span>
                                                        <?php } ?>

                                                    <?php } else { ?>

                                                        <span class="badge bg-success-subtle text-success border">
                                                            Sin acción activa
                                                        </span>

                                                    <?php } ?>
                                                </td>

                                                <td>
                                                    <?= hOrden($unidad['total_estaciones'] ?? 0) ?>
                                                </td>

                                                <td>
                                                    <span class="text-success fw-bold">
                                                        <?= hOrden($unidad['estaciones_finalizadas'] ?? 0) ?>
                                                    </span>
                                                </td>

                                                <td>
                                                    <span class="text-info fw-bold">
                                                        <?= hOrden($unidad['estaciones_en_proceso'] ?? 0) ?>
                                                    </span>
                                                </td>

                                                <td>
                                                    <span class="text-warning fw-bold">
                                                        <?= hOrden($unidad['estaciones_pendientes'] ?? 0) ?>
                                                    </span>
                                                </td>

                                                <td>
                                                    <?= fechaOrden($unidad['fecha_inicio_produccion'] ?? '') ?>
                                                </td>

                                                <td>
                                                    <?= fechaOrden($unidad['ultima_fecha_fin'] ?? '') ?>
                                                </td>
                                            </tr>
                                        <?php } ?>
                                    </tbody>
                                </table>
                            </div>

                        <?php } ?>

                    </div>

                </div>

            </div>
        </div>

    <?php } ?>

</div>

<?php footerAdmin($data); ?>