<?php

headerOrders($data);


$pedido = $data['pedido'] ?? null;

$detalles = $data['detalles'] ?? [];

/*
 * ============================================================
 * FUNCIONES AUXILIARES
 * ============================================================
 */
function formatoMonedaPedidoDetalle($cantidad)
{

    return '$'
        . number_format(
            floatval($cantidad),
            2,
            '.',
            ','
        )
        . ' MXN';
}

function formatoFechaPedidoDetalle($fecha, $hora = false)
{

    if (empty($fecha) || $fecha === '0000-00-00' || $fecha === '0000-00-00 00:00:00') {

        return 'No especificada';
    }

    $timestamp = strtotime($fecha);

    if (!$timestamp) {
        return $fecha;
    }

    return $hora
        ? date(
            'd/m/Y H:i',
            $timestamp
        )
        : date(
            'd/m/Y',
            $timestamp
        );
}

/*
 * ============================================================
 * ERROR
 * ============================================================
 */

if (!$pedido):
    ?>

    <section class="order-detail-page">

        <div class="order-detail-container">

            <div class="order-detail-empty">

                <i class="ri-error-warning-line"></i>

                <h2>
                    Pedido no encontrado
                </h2>

                <p>
                    El pedido solicitado no existe o no pertenece
                    a tu cuenta.
                </p>

                <a href="<?= base_url(); ?>/orders/micuenta" class="order-detail-back">
                    <i class="ri-arrow-left-line"></i>
                    Volver a mis pedidos
                </a>

            </div>

        </div>

    </section>

    <?php

    footerOrders($data);
    return;
endif;


/*
 * ============================================================
 * DATOS
 * ============================================================
 */

$estatus = strtoupper(trim($pedido['estatus'] ?? 'PENDIENTE'));
$prioridad = strtoupper(trim($pedido['prioridad'] ?? 'NORMAL'));
$nombreDistribuidor = trim($pedido['nombre_comercial'] ?? '');

if ($nombreDistribuidor === '') {

    $nombreDistribuidor = $pedido['razon_social'] ?? 'Distribuidor';
}

$nombreSolicitante =
    trim(
        (
            $pedido['nombre_usuario']
            ?? ''
        )
        . ' '
        . (
            $pedido['apellido_usuario']
            ?? ''
        )
    );


$totalModelos = count($detalles);


$totalUnidades = 0;

foreach ($detalles as $detalle) {

    $totalUnidades += intval($detalle['cantidad_solicitada'] ?? 0);
}

?>

<section class="order-detail-page">

    <div class="order-detail-container">

        <!-- ==================================================
             BREADCRUMB
        =================================================== -->

        <div class="order-detail-breadcrumb">

            <a href="<?= base_url(); ?>/orders/micuenta">
                Mis pedidos
            </a>

            <i class="ri-arrow-right-s-line"></i>

            <span>
                <?= htmlspecialchars($pedido['folio_pedido'], ENT_QUOTES, 'UTF-8'); ?>
            </span>

        </div>

        <!-- ==================================================
             HEADER
        =================================================== -->

        <div class="order-detail-header">

            <div>

                <div class="order-detail-eyebrow">
                    Detalle del pedido
                </div>

                <h1>

                    <?= htmlspecialchars($pedido['folio_pedido'], ENT_QUOTES, 'UTF-8'); ?>

                </h1>

                <p>
                    Consulta la información general y las unidades
                    registradas en esta solicitud.
                </p>

            </div>

            <div class="order-detail-header-actions">

                <a href="<?= base_url(); ?>/orders/micuenta" class="order-detail-btn order-detail-btn-light">

                    <i class="ri-arrow-left-line"></i>

                    Volver

                </a>

                <?php if ($estatus === 'PENDIENTE'): ?>

                    <a href="<?= base_url(); ?>/orders/editarpedido/<?= rawurlencode($pedido['clave']); ?>"
                        class="order-detail-btn order-detail-btn-edit">

                        <i class="ri-edit-line"></i>

                        Editar pedido

                    </a>

                <?php endif; ?>




                <button type="button" id="btnPrintOrderDetail" class="order-detail-btn order-detail-btn-print"
                    data-clave="<?= htmlspecialchars($pedido['clave'], ENT_QUOTES, 'UTF-8'); ?>">

                    <i class="ri-printer-line"></i>

                    Imprimir

                </button>

            </div>

        </div>


        <!-- ==================================================
             ESTATUS
        =================================================== -->

        <div class="order-detail-status-card">

            <div class="order-detail-status-item">

                <span>
                    Estatus
                </span>

                <strong class="order-status-badge order-status-<?= strtolower($estatus); ?>">

                    <?= htmlspecialchars(
                        $estatus,
                        ENT_QUOTES,
                        'UTF-8'
                    ); ?>

                </strong>

            </div>

            <div class="order-detail-status-item">

                <span>
                    Prioridad
                </span>

                <strong>

                    <?= htmlspecialchars(
                        $prioridad,
                        ENT_QUOTES,
                        'UTF-8'
                    ); ?>

                </strong>

            </div>

            <div class="order-detail-status-item">

                <span>
                    Modelos
                </span>

                <strong>
                    <?= $totalModelos; ?>
                </strong>

            </div>

            <div class="order-detail-status-item">

                <span>
                    Unidades
                </span>

                <strong>
                    <?= $totalUnidades; ?>
                </strong>

            </div>

        </div>

        <!-- ==================================================
             INFORMACIÓN GENERAL
        =================================================== -->

        <div class="order-detail-grid">

            <!-- PEDIDO -->

            <div class="order-detail-card">

                <div class="order-detail-card-header">

                    <div>

                        <i class="ri-file-list-3-line"></i>

                    </div>

                    <h3>
                        Información del pedido
                    </h3>

                </div>

                <div class="order-detail-information">

                    <div class="order-detail-info-row">

                        <span>
                            Folio
                        </span>

                        <strong>
                            <?= htmlspecialchars(
                                $pedido['folio_pedido'],
                                ENT_QUOTES,
                                'UTF-8'
                            ); ?>
                        </strong>

                    </div>

                    <div class="order-detail-info-row">

                        <span>
                            Fecha del pedido
                        </span>

                        <strong>
                            <?= formatoFechaPedidoDetalle(
                                $pedido['fecha_pedido'],
                                true
                            ); ?>
                        </strong>

                    </div>

                    <div class="order-detail-info-row">

                        <span>
                            Fecha requerida
                        </span>

                        <strong>
                            <?= formatoFechaPedidoDetalle(
                                $pedido['fecha_requerida']
                            ); ?>
                        </strong>

                    </div>

                    <div class="order-detail-info-row">

                        <span>
                            Mes de facturación
                        </span>

                        <strong>
                            <?= htmlspecialchars($pedido['mes_facturacion_deseado'] ?? 'No especificado', ENT_QUOTES, 'UTF-8'); ?>
                        </strong>

                    </div>

                    <?php if (
                        !empty(
                        $pedido['version']
                    )
                    ): ?>

                        <div class="order-detail-info-row">

                            <span>
                                Versión
                            </span>

                            <strong>
                                <?= intval(
                                    $pedido['version']
                                ); ?>
                            </strong>

                        </div>

                    <?php endif; ?>

                </div>

            </div>

            <!-- DISTRIBUIDOR -->

            <div class="order-detail-card">

                <div class="order-detail-card-header">

                    <div>

                        <i class="ri-building-line"></i>

                    </div>


                    <h3>
                        Distribuidor
                    </h3>

                </div>

                <div class="order-detail-information">


                    <div class="order-detail-info-row">

                        <span>
                            Nombre comercial
                        </span>

                        <strong>
                            <?= htmlspecialchars(
                                $nombreDistribuidor,
                                ENT_QUOTES,
                                'UTF-8'
                            ); ?>
                        </strong>

                    </div>


                    <?php if (!empty($pedido['razon_social'])): ?>

                        <div class="order-detail-info-row">

                            <span>
                                Razón social
                            </span>

                            <strong>
                                <?= htmlspecialchars(
                                    $pedido['razon_social'],
                                    ENT_QUOTES,
                                    'UTF-8'
                                ); ?>
                            </strong>

                        </div>

                    <?php endif; ?>

                    <?php if (!empty($pedido['clave_distribuidor'])): ?>

                        <div class="order-detail-info-row">

                            <span>
                                Clave
                            </span>

                            <strong>
                                <?= htmlspecialchars(
                                    $pedido['clave_distribuidor'],
                                    ENT_QUOTES,
                                    'UTF-8'
                                ); ?>
                            </strong>

                        </div>

                    <?php endif; ?>

                    <div class="order-detail-info-row">

                        <span>
                            Solicitante
                        </span>

                        <strong>
                            <?= htmlspecialchars(
                                $nombreSolicitante,
                                ENT_QUOTES,
                                'UTF-8'
                            ); ?>
                        </strong>

                    </div>

                </div>

            </div>

        </div>

        <!-- ==================================================
             UNIDADES
        =================================================== -->

        <div class="order-detail-section-header">

            <div>

                <span>
                    Unidades registradas
                </span>

                <h2>
                    Detalle de modelos solicitados
                </h2>

            </div>

            <div class="order-detail-section-count">

                <?= $totalUnidades; ?>

                <span>
                    unidades
                </span>

            </div>

        </div>

        <div class="order-detail-products">

            <?php if (empty($detalles)): ?>

                <div class="order-detail-empty-products">

                    <i class="ri-car-line"></i>

                    <p>
                        Este pedido no tiene unidades registradas.
                    </p>

                </div>

            <?php else: ?>

                <?php foreach ($detalles as $detalle): ?>

                    <?php

                    $cantidad = intval($detalle['cantidad_solicitada'] ?? 0);
                    $precioUnitario = floatval($detalle['precio_unitario'] ?? 0);
                    $importeEstimado = $precioUnitario * $cantidad;
                    $tipoEntrega = strtoupper(trim($detalle['tipo_entrega'] ?? ''));
                    $destino = 'No especificado';

                    if ($tipoEntrega === 'SUCURSAL') {

                        $destino = $detalle['nombre_sucursal'] ?? ('Sucursal ' . intval($detalle['idsucursal_entrega'] ?? 0));

                    } elseif (
                        $tipoEntrega === 'OTRA_DIRECCION'
                    ) {
                        $destino = $detalle['direccion_entrega'] ?? 'No especificada';
                    }

                    $imagen = trim($detalle['imagen_caratula'] ?? '');
                    $imagen = base_url() . '/' . $imagen;
                    if ($imagen === '') {
                        $imagen = base_url() . '/Assets/images/no-image.png';
                    }

                    ?>

                    <article class="order-detail-product">

                        <div class="order-detail-product-image">

                            <img src="<?= htmlspecialchars($imagen, ENT_QUOTES, 'UTF-8'); ?>"
                                alt="<?= htmlspecialchars($detalle['nombre'] ?? 'Unidad', ENT_QUOTES, 'UTF-8'); ?>">

                        </div>

                        <div class="order-detail-product-content">

                            <div class="order-detail-product-top">

                                <div>

                                    <span class="order-detail-product-brand">

                                        <?= htmlspecialchars($detalle['marca'] ?? '', ENT_QUOTES, 'UTF-8'); ?>

                                    </span>

                                    <h3>

                                        <?= htmlspecialchars($detalle['nombre'] ?? $detalle['modelo'] ?? 'Unidad', ENT_QUOTES, 'UTF-8'); ?>

                                    </h3>

                                    <p>

                                        <?= htmlspecialchars(
                                            trim(
                                                (
                                                    $detalle['version']
                                                    ?? ''
                                                )
                                                . (
                                                    !empty(
                                                    $detalle['anio']
                                                )
                                                    ? ' · '
                                                    . $detalle['anio']
                                                    : ''
                                                )
                                            ),
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ); ?>

                                    </p>

                                </div>

                                <div class="order-detail-qty">

                                    <span>
                                        Cantidad
                                    </span>

                                    <strong>
                                        <?= $cantidad; ?>
                                    </strong>

                                </div>

                            </div>


                            <div class="order-detail-product-data">

                                <div>

                                    <span>
                                        Clave modelo
                                    </span>

                                    <strong>
                                        <?= htmlspecialchars(
                                            $detalle[
                                                'clave_modelo'
                                            ]
                                            ?? 'N/A',
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ); ?>
                                    </strong>

                                </div>

                                <!-- <div>

                                    <span>
                                        Versión
                                    </span>

                                    <strong>
                                        <?= htmlspecialchars(
                                            $detalle[
                                                'motor'
                                            ]
                                            ?? 'N/A',
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ); ?>
                                    </strong>

                                </div> -->

                                <div>

                                    <span>
                                        Tipo de entrega
                                    </span>

                                    <strong>
                                        <?= htmlspecialchars(
                                            str_replace(
                                                '_',
                                                ' ',
                                                $tipoEntrega
                                            ),
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ); ?>
                                    </strong>

                                </div>


                                <div>

                                    <span>
                                        Destino
                                    </span>

                                    <strong>
                                        <?= htmlspecialchars(
                                            $destino,
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ); ?>
                                    </strong>

                                </div>

                            </div>


                            <div class="order-detail-product-financial">

                                <div>

                                    <span>
                                        Precio unitario
                                    </span>

                                    <strong>
                                        <?= formatoMonedaPedidoDetalle(
                                            $precioUnitario
                                        ); ?>
                                    </strong>

                                </div>


                                <div>

                                    <span>
                                        Importe estimado
                                    </span>

                                    <strong>
                                        <?= formatoMonedaPedidoDetalle(
                                            $importeEstimado
                                        ); ?>
                                    </strong>

                                </div>

                            </div>

                        </div>


                    </article>


                <?php endforeach; ?>


            <?php endif; ?>


        </div>


        <!-- ==================================================
             OBSERVACIONES + TOTALES
        =================================================== -->

        <div class="order-detail-bottom-grid">

            <div class="order-detail-card">

                <div class="order-detail-card-header">

                    <div>

                        <i class="ri-chat-3-line"></i>

                    </div>

                    <h3>
                        Observaciones
                    </h3>

                </div>

                <div class="order-detail-observations">

                    <?php if (!empty(trim($pedido['observaciones'] ?? ''))): ?>

                        <?= nl2br(
                            htmlspecialchars(
                                $pedido[
                                    'observaciones'
                                ],
                                ENT_QUOTES,
                                'UTF-8'
                            )
                        ); ?>

                    <?php else: ?>

                        <span>
                            No se registraron observaciones.
                        </span>

                    <?php endif; ?>

                </div>

            </div>

            <div class="order-detail-total-card">

                <div class="order-detail-total-row">

                    <span>
                        Subtotal
                    </span>

                    <strong>
                        <?= formatoMonedaPedidoDetalle(
                            $pedido['subtotal']
                        ); ?>
                    </strong>

                </div>

                <div class="order-detail-total-row">

                    <span>
                        Descuento
                    </span>

                    <strong>
                        <?= formatoMonedaPedidoDetalle(
                            $pedido['descuento']
                        ); ?>
                    </strong>

                </div>

                <div class="order-detail-total-row">

                    <span>
                        IVA
                    </span>

                    <strong>
                        <?= formatoMonedaPedidoDetalle(
                            $pedido['iva']
                        ); ?>
                    </strong>

                </div>

                <div class="order-detail-total-row order-detail-grand-total">

                    <span>
                        Total
                    </span>

                    <strong>
                        <?= formatoMonedaPedidoDetalle(
                            $pedido['total']
                        ); ?>
                    </strong>

                </div>

            </div>

        </div>

    </div>

</section>

<script>

    window.ORDER_DETAIL = <?= json_encode(
        [
            'clave' =>
                $pedido['clave'],

            'folio' =>
                $pedido['folio_pedido'],

            'estatus' =>
                $pedido['estatus']
        ],
        JSON_UNESCAPED_UNICODE
        | JSON_UNESCAPED_SLASHES
    ); ?>;

</script>

<?php

footerOrders($data);

?>