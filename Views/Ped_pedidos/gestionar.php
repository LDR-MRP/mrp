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

            $gestionIniciada = $estatusPedido !== 'PENDIENTE' && $estatusPedido !== 'RECHAZADO';

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


            <?php $estadoRevision=$data['revision_comercial']['estatus']??'NO_INICIADA';$porcentaje=match(true){$estatusPedido==='ENTREGADO'=>100,$estatusPedido==='FACTURADO'=>95,$estatusPedido==='AUTORIZADO'=>85,$estatusPedido==='BACK_ORDER'=>65,$estadoRevision==='ACEPTADA'=>65,$estadoRevision==='PENDIENTE_ACEPTACION'=>45,$estadoRevision==='AJUSTE_SOLICITADO'=>35,$estatusPedido==='EN_REVISION'=>25,default=>10}; ?>
            <div class="card border-0 shadow-sm"><div class="card-body"><div class="d-flex justify-content-between flex-wrap gap-2 mb-2"><strong><i class="ri-route-line text-primary me-1"></i>Trazabilidad del pedido</strong><span class="small text-muted"><?= $estatusPedido==='RECHAZADO'?'Pedido rechazado · proceso cerrado':$porcentaje.'% · avance por etapas' ?></span></div><div class="progress" style="height:9px" role="progressbar" aria-valuenow="<?= $estatusPedido==='RECHAZADO'?0:$porcentaje ?>" aria-valuemin="0" aria-valuemax="100"><div class="progress-bar <?= $estatusPedido==='RECHAZADO'?'bg-danger':'' ?>" style="width:<?= $estatusPedido==='RECHAZADO'?100:$porcentaje ?>%"></div></div><small class="text-muted d-block mt-2">Solicitud → Revisión → Aprobación → Backorder → Asignación → Entrega. Indicador orientativo por etapas, no por unidades asignadas.</small></div></div>

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
                                Revisión comercial

                            </a>

                        </li>


                                                <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#tabBackorder" role="tab"><i class="ri-stack-line me-1"></i>Backorders vinculados</a></li>
<li class="nav-item">

                            <a class="nav-link" data-bs-toggle="tab" href="#tabAsignacion" role="tab">

                                <i class="ri-car-line me-1"></i>
                                Asignación

                            </a>

                        </li>


                        <li class="nav-item">

                            <a class="nav-link" data-bs-toggle="tab" href="#tabOrdenesVenta" role="tab">

                                <i class="ri-file-paper-2-line me-1"></i>
                                Órdenes de Venta

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

                                                <?php include __DIR__ . '/gestion/resumen.php'; ?>
                        <?php include __DIR__ . '/gestion/revision.php'; ?>
                        <?php include __DIR__ . '/gestion/backorder.php'; ?>
                        <?php include __DIR__ . '/gestion/asignacion.php'; ?>
                        <?php include __DIR__ . '/gestion/ordenes_venta.php'; ?>
                        <?php include __DIR__ . '/gestion/historial.php'; ?>


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


<?php if($rev && $lineasRev): $pdfLineas=[]; foreach($lineasRev as $pl){$pdfLineas[]=['modelo'=>$pl['nombre']?:$pl['modelo'],'color'=>$pl['nombre_color']??'','cantidad'=>(int)$pl['cantidad_propuesta'],'precio'=>(float)$pl['precio_unitario'],'subtotal'=>(float)$pl['subtotal'],'descuento'=>(float)$pl['descuento'],'iva'=>(float)$pl['iva'],'total'=>(float)$pl['total']];} $pdfInfo=['folio'=>$pedido['folio_pedido']??'','version'=>(int)$rev['numero_revision'],'estado'=>$rev['estatus'],'subtotal'=>(float)$rev['subtotal'],'descuento'=>(float)$rev['descuento'],'iva'=>(float)$rev['iva'],'total'=>(float)$rev['total'],'lineas'=>$pdfLineas]; ?><script type="application/json" id="propuestaPdfData"><?= json_encode($pdfInfo,JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT|JSON_UNESCAPED_UNICODE) ?></script><?php endif; ?>
<script>window.PED_REVISION_TASA_IVA = <?= json_encode((float)(max(0,(float)($pedido['subtotal']??0)-(float)($pedido['descuento']??0))>0 ? (float)($pedido['iva']??0)/max(0.01,(float)($pedido['subtotal']??0)-(float)($pedido['descuento']??0)) : 0.16)) ?>;</script>
<script>window.PED_REVISION_CSRF = <?= json_encode($_SESSION['ped_revision_csrf'] ?? '') ?>;</script>
<link rel="stylesheet" href="<?= base_url() ?>/Assets/css/modulos/revision_comercial.css">
<script>
document.addEventListener('DOMContentLoaded', function () {
  function activarBackorder() {
    if (location.hash !== '#tabBackorder' || !window.bootstrap) return;
    const enlace = document.querySelector('a[data-bs-toggle="tab"][href="#tabBackorder"]');
    if (enlace) bootstrap.Tab.getOrCreateInstance(enlace).show();
  }
  activarBackorder();
  window.addEventListener('hashchange', activarBackorder);
});
</script>
<?php footerAdmin($data); ?>