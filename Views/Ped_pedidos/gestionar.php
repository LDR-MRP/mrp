<?php headerAdmin($data); ?>

<div class="main-content">

    <div class="page-content">

        <div class="container-fluid">

            <?php

            $pedido = $data['pedido'] ?? [];
            $detalles = $data['detalles'] ?? [];
            $bitacora = $data['bitacora'] ?? [];
            $ordenesVenta = $data['ordenesVenta'] ?? [];



            $estatusPedido = strtoupper(
                trim(
                    $pedido['estatus'] ?? ''
                )
            );

            $gestionIniciada = $estatusPedido !== 'PENDIENTE';

            ?>

            <!-- =====================================================
                 CABECERA
            ====================================================== -->

            <div class="row mb-3">

                <div class="col-12">

                    <div class="d-flex align-items-center justify-content-between flex-wrap gap-3">

                        <div>

                            <div class="d-flex align-items-center gap-2 mb-1">

                                <a href="<?= base_url(); ?>/ped_pedidos" class="btn btn-sm btn-soft-secondary">

                                    <i class="ri-arrow-left-line"></i>

                                </a>

                                <h4 class="mb-0">
                                    <?= htmlspecialchars(
                                        $pedido['folio_pedido'] ?? 'Pedido'
                                    ); ?>
                                </h4>

                                <span class="badge bg-warning-subtle text-warning" id="badgeEstatusPedido">

                                    <?= htmlspecialchars(
                                        $pedido['estatus'] ?? ''
                                    ); ?>

                                </span>

                            </div>

                            <p class="text-muted mb-0">
                                Gestión administrativa del pedido.
                            </p>

                        </div>


                        <div class="d-flex gap-2">



                            <a href="<?= base_url(); ?>/ped_pedidos" class="btn btn-soft-secondary">

                                <i class="ri-arrow-left-line"> Volver</i>

                            </a>


                            <!-- <button
                                type="button"
                                class="btn btn-soft-info"
                                id="btnImprimirPedido">

                                <i class="ri-printer-line me-1"></i>
                                Imprimir

                            </button> -->

                        </div>

                    </div>

                </div>

            </div>



            <div class="alert alert-warning border-0 <?= $gestionIniciada ? 'd-none' : ''; ?>"
                id="alertaGestionPendiente">

                <div class="d-flex align-items-start">

                    <div class="flex-shrink-0 me-3">
                        <i class="ri-information-line fs-4"></i>
                    </div>

                    <div class="flex-grow-1">

                        <h6 class="alert-heading mb-1">
                            Gestión pendiente de iniciar
                        </h6>

                        <p class="mb-0">
                            El pedido se encuentra disponible únicamente para consulta.
                            Para realizar autorizaciones, asignaciones o cualquier otra
                            operación administrativa, primero debes iniciar formalmente
                            la gestión del pedido.
                        </p>

                    </div>

                </div>

            </div>


            <!-- =====================================================
                 RESUMEN SUPERIOR
            ====================================================== -->

            <div class="row">

                <div class="col-xl-3 col-md-6">

                    <div class="card">

                        <div class="card-body">

                            <p class="text-muted mb-1">
                                Distribuidor
                            </p>

                            <h6 class="mb-1">
                                <?= htmlspecialchars(
                                    $pedido['nombre_comercial']
                                    ?: $pedido['razon_social']
                                ); ?>
                            </h6>

                            <small class="text-muted">
                                <?= htmlspecialchars(
                                    $pedido['clave_distribuidor']
                                    ?? $pedido['codigo_cliente']
                                    ?? ''
                                ); ?>
                            </small>

                        </div>

                    </div>

                </div>


                <div class="col-xl-3 col-md-6">

                    <div class="card">

                        <div class="card-body">

                            <p class="text-muted mb-1">
                                Unidades solicitadas
                            </p>

                            <h4 class="mb-0">
                                <?= intval(
                                    $pedido['total_unidades'] ?? 0
                                ); ?>
                            </h4>

                        </div>

                    </div>

                </div>


                <div class="col-xl-3 col-md-6">

                    <div class="card">

                        <div class="card-body">

                            <p class="text-muted mb-1">
                                Unidades autorizadas
                            </p>

                            <h4 class="mb-0" id="resumenAutorizadas">

                                <?= intval(
                                    $pedido['total_autorizadas'] ?? 0
                                ); ?>

                            </h4>

                        </div>

                    </div>

                </div>


                <div class="col-xl-3 col-md-6">

                    <div class="card">

                        <div class="card-body">

                            <p class="text-muted mb-1">
                                Total solicitado
                            </p>

                            <h5 class="mb-0">

                                $<?= number_format(
                                    floatval(
                                        $pedido['total'] ?? 0
                                    ),
                                    2
                                ); ?>

                            </h5>

                        </div>

                    </div>

                </div>

            </div>


            <!-- =====================================================
                 PESTAÑAS
            ====================================================== -->

            <div class="card">

                <div class="card-header">

                    <ul class="nav nav-tabs-custom rounded card-header-tabs border-bottom-0" role="tablist">

                        <li class="nav-item">

                            <a class="nav-link active" data-bs-toggle="tab" href="#tabResumen" role="tab">

                                <i class="ri-file-list-3-line me-1"></i>
                                Resumen

                            </a>

                        </li>


                        <li class="nav-item">

                            <a class="nav-link" data-bs-toggle="tab" href="#tabRevision" role="tab">

                                <i class="ri-checkbox-multiple-line me-1"></i>
                                Revisión

                            </a>

                        </li>


                        <li class="nav-item">

                            <a class="nav-link" data-bs-toggle="tab" href="#tabDisponibilidad" role="tab">

                                <i class="ri-stack-line me-1"></i>
                                Disponibilidad / Backorder

                            </a>

                        </li>


                        <li class="nav-item">

                            <a class="nav-link" data-bs-toggle="tab" href="#tabOrdenesVenta" role="tab">

                                <i class="ri-file-paper-2-line me-1"></i>
                                Órdenes de Venta

                            </a>

                        </li>


                        <li class="nav-item">

                            <a class="nav-link" data-bs-toggle="tab" href="#tabAsignacion" role="tab">

                                <i class="ri-car-line me-1"></i>
                                Asignación

                            </a>

                        </li>


                        <li class="nav-item">

                            <a class="nav-link" data-bs-toggle="tab" href="#tabFacturacion" role="tab">

                                <i class="ri-bill-line me-1"></i>
                                Facturación / Entrega

                            </a>

                        </li>


                        <li class="nav-item">

                            <a class="nav-link" data-bs-toggle="tab" href="#tabHistorial" role="tab">

                                <i class="ri-history-line me-1"></i>
                                Historial

                            </a>

                        </li>

                    </ul>

                </div>


                <div class="card-body">

                    <div class="tab-content">


                        <!-- =================================================
                             RESUMEN
                        ================================================== -->

                        <div class="tab-pane active" id="tabResumen" role="tabpanel">

                            <div class="row g-4">

                                <div class="col-lg-6">

                                    <h5 class="mb-3">
                                        Información del pedido
                                    </h5>

                                    <div class="table-responsive">

                                        <table class="table table-borderless mb-0">

                                            <tbody>

                                                <tr>
                                                    <td class="text-muted">
                                                        Fecha pedido
                                                    </td>

                                                    <td class="fw-medium">
                                                        <?= htmlspecialchars(
                                                            $pedido['fecha_pedido']
                                                            ?? ''
                                                        ); ?>
                                                    </td>
                                                </tr>

                                                <tr>
                                                    <td class="text-muted">
                                                        Fecha requerida
                                                    </td>

                                                    <td class="fw-medium">
                                                        <?= htmlspecialchars(
                                                            $pedido['fecha_requerida']
                                                            ?? ''
                                                        ); ?>
                                                    </td>
                                                </tr>

                                                <tr>
                                                    <td class="text-muted">
                                                        Mes facturación
                                                    </td>

                                                    <td class="fw-medium">
                                                        <?= htmlspecialchars(
                                                            $pedido['mes_facturacion_deseado']
                                                            ?? ''
                                                        ); ?>
                                                    </td>
                                                </tr>

                                                <tr>
                                                    <td class="text-muted">
                                                        Prioridad
                                                    </td>

                                                    <td class="fw-medium">
                                                        <?= htmlspecialchars(
                                                            $pedido['prioridad']
                                                            ?? ''
                                                        ); ?>
                                                    </td>
                                                </tr>

                                            </tbody>

                                        </table>

                                    </div>

                                </div>


                                <div class="col-lg-6">

                                    <h5 class="mb-3">
                                        Solicitante
                                    </h5>

                                    <p class="mb-1 fw-medium">

                                        <?= htmlspecialchars(
                                            trim(
                                                ($pedido['nombre_usuario'] ?? '')
                                                . ' '
                                                . ($pedido['apellido_usuario'] ?? '')
                                            )
                                        ); ?>

                                    </p>

                                    <p class="text-muted mb-3">

                                        <?= htmlspecialchars(
                                            $pedido['correo_usuario']
                                            ?? ''
                                        ); ?>

                                    </p>


                                    <h6>
                                        Observaciones
                                    </h6>

                                    <p class="text-muted mb-0">

                                        <?= nl2br(
                                            htmlspecialchars(
                                                $pedido['observaciones']
                                                ?: 'Sin observaciones.'
                                            )
                                        ); ?>

                                    </p>

                                </div>

                            </div>

                        </div>


                        <!-- =================================================
                             REVISIÓN
                        ================================================== -->

                        <div class="tab-pane" id="tabRevision" role="tabpanel">

                            <div class="d-flex justify-content-between align-items-center mb-3">

                                <div>

                                    <h5 class="mb-1">
                                        Revisión y autorización
                                    </h5>

                                    <p class="text-muted mb-0">
                                        Define las cantidades que serán atendidas.
                                    </p>

                                </div>

                            </div>


                            <div class="table-responsive">

                                <table class="table table-hover align-middle">

                                    <thead class="table-light">

                                        <tr>

                                            <th>Modelo</th>
                                            <th class="text-center">Solicitadas</th>
                                            <th class="text-center">Autorizadas</th>
                                            <th class="text-end">Precio</th>
                                            <th class="text-end">Importe</th>

                                        </tr>

                                    </thead>

                                    <tbody>

                                        <?php foreach ($detalles as $detalle) { ?>

                                            <tr>

                                                <td>

                                                    <div class="fw-medium">

                                                        <?= htmlspecialchars(
                                                            $detalle['modelo']
                                                            ?? ''
                                                        ); ?>

                                                    </div>

                                                    <small class="text-muted">

                                                        <?= htmlspecialchars(
                                                            $detalle['version']
                                                            ?? ''
                                                        ); ?>

                                                    </small>

                                                </td>


                                                <td class="text-center fw-medium">

                                                    <?= intval(
                                                        $detalle['cantidad_solicitada']
                                                        ?? 0
                                                    ); ?>

                                                </td>


                                                <td style="width:150px;">

                                                    <input type="number"
                                                        class="form-control text-center cantidad-autorizada" min="0" max="<?= intval(
                                                            $detalle['cantidad_solicitada']
                                                            ?? 0
                                                        ); ?>" value="<?= intval(
                                                             $detalle['cantidad_autorizada']
                                                             ?? 0
                                                         ); ?>" data-detalle="<?= intval(
                                                              $detalle['idpedido_detalle']
                                                          ); ?>" <?= !$gestionIniciada ? 'disabled' : ''; ?>>

                                                </td>


                                                <td class="text-end">

                                                    $<?= number_format(
                                                        floatval(
                                                            $detalle['precio_unitario']
                                                            ?? 0
                                                        ),
                                                        2
                                                    ); ?>

                                                </td>


                                                <td class="text-end fw-medium">

                                                    $<?= number_format(
                                                        floatval(
                                                            $detalle['total']
                                                            ?? 0
                                                        ),
                                                        2
                                                    ); ?>

                                                </td>

                                            </tr>

                                        <?php } ?>

                                    </tbody>

                                </table>

                            </div>


                            <div class="text-end mt-3">

                                <button type="button"
                                    class="btn btn-primary accion-gestion-pedido <?= !$gestionIniciada ? 'd-none' : ''; ?>"
                                    id="btnGuardarRevision">

                                    <i class="ri-save-line me-1"></i>
                                    Guardar revisión

                                </button>

                            </div>

                        </div>


                        <!-- =================================================
                             DISPONIBILIDAD
                        ================================================== -->

                        <div class="tab-pane" id="tabDisponibilidad" role="tabpanel">

                            <h5>
                                Disponibilidad y Backorder
                            </h5>

                            <p class="text-muted">
                                Aquí mostraremos las unidades disponibles,
                                reservas y cantidades pendientes.
                            </p>

                            <div id="contenidoDisponibilidadPedido"></div>

                        </div>


                        <!-- =================================================
                             ÓRDENES DE VENTA
                        ================================================== -->

                        <div class="tab-pane" id="tabOrdenesVenta" role="tabpanel">

                            <div class="d-flex justify-content-between align-items-center mb-3">

                                <div>

                                    <h5 class="mb-1">
                                        Órdenes de Venta
                                    </h5>

                                    <p class="text-muted mb-0">
                                        Órdenes generadas a partir del pedido.
                                    </p>

                                </div>


                                <button type="button"
                                    class="btn btn-primary accion-gestion-pedido <?= !$gestionIniciada ? 'd-none' : ''; ?>"
                                    id="btnNuevaOrdenVenta">

                                    <i class="ri-add-line me-1"></i>
                                    Generar Orden de Venta

                                </button>

                            </div>


                            <div id="contenidoOrdenesVenta"></div>

                        </div>


                        <!-- =================================================
                             ASIGNACIÓN
                        ================================================== -->

                        <div class="tab-pane" id="tabAsignacion" role="tabpanel">

                            <h5>
                                Asignación de unidades
                            </h5>

                            <p class="text-muted">
                                Aquí se realizará la asignación definitiva
                                de las unidades y VIN.
                            </p>

                            <div id="contenidoAsignacionesPedido"></div>

                        </div>


                        <!-- =================================================
                             FACTURACIÓN
                        ================================================== -->

                        <div class="tab-pane" id="tabFacturacion" role="tabpanel">

                            <h5>
                                Facturación y entregas
                            </h5>

                            <p class="text-muted">
                                Seguimiento de unidades facturadas y entregadas.
                            </p>

                            <div id="contenidoFacturacionPedido"></div>

                        </div>


                        <!-- =================================================
                             HISTORIAL
                        ================================================== -->

                        <div class="tab-pane" id="tabHistorial" role="tabpanel">

                            <h5 class="mb-4">
                                Historial del pedido
                            </h5>

                            <?php if (empty($bitacora)) { ?>

                                <div class="text-center py-5 text-muted">

                                    <i class="ri-history-line fs-1"></i>

                                    <p class="mt-2 mb-0">
                                        No hay movimientos registrados.
                                    </p>

                                </div>

                            <?php } else { ?>

                                <div class="timeline">

                                    <?php foreach ($bitacora as $evento) { ?>

                                        <div class="mb-4">

                                            <div class="fw-medium">

                                                <?= htmlspecialchars(
                                                    $evento['descripcion']
                                                    ?? ''
                                                ); ?>

                                            </div>

                                            <small class="text-muted">

                                                <?= htmlspecialchars(
                                                    $evento['fecha_creacion']
                                                    ?? ''
                                                ); ?>

                                                ·

                                                <?= htmlspecialchars(
                                                    $evento['origen']
                                                    ?? ''
                                                ); ?>

                                            </small>

                                        </div>

                                    <?php } ?>

                                </div>

                            <?php } ?>

                        </div>


                    </div>

                </div>

            </div>

        </div>

    </div>

</div>


<script>

    window.GESTION_PEDIDO = <?= json_encode([
        'idpedido' => intval($pedido['idpedido'] ?? 0),
        'clave' => $pedido['clave'] ?? '',
        'folio' => $pedido['folio_pedido'] ?? '',
        'estatus' => $pedido['estatus'] ?? ''
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>;

</script>


<?php footerAdmin($data); ?>