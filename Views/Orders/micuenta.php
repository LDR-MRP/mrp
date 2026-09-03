<?php

headerOrders($data);

/*
 * ============================================
 * DATA
 * ============================================
 */

$distribuidor = $data['distribuidor'] ?? [];

$metricas = $data['metricas'] ?? [
    'pedidos' => 0,
    'unidades' => 0,
    'modelos' => 0,
    'total' => 0
];

$pedidos = $data['pedidos'] ?? [];

// dep($pedidos);

$conteoEstatus = $data['conteo_estatus'] ?? [];

/*
 * ============================================
 * FUNCIONES VISUALES
 * ============================================
 */

if (!function_exists('moneyOrders')) {

    function moneyOrders(float $amount)
    {

        return '$'
            . number_format(
                $amount,
                2,
                '.',
                ','
            );
    }
}


if (!function_exists('statusPedidoTexto')) {

    function statusPedidoTexto(string $estatus)
    {

        $estatus = strtoupper(trim($estatus));

        $estatusMap = [

            'PENDIENTE' => 'Pendiente',

            'RECIBIDO' => 'Recibido',

            'EN_REVISION' => 'En revisión',

            'INVENTARIO_ASIGNADO' => 'Inventario asignado',

            'BACK_ORDER' => 'Back Order',

            'BO' => 'Back Order',

            'EN_TRANSITO' => 'En tránsito',

            'EN_ENTREGA' => 'En entrega',

            'ENTREGADO' => 'Entregado',

            'FACTURADO' => 'Facturado',

            'CANCELADO' => 'Cancelado'
        ];


        return $estatusMap[$estatus]
            ?? ucfirst(
                strtolower(
                    str_replace(
                        '_',
                        ' ',
                        $estatus
                    )
                )
            );
    }
}


if (!function_exists('statusPedidoNormalizado')) {

    function statusPedidoNormalizado(string $estatus)
    {

        $estatus = strtoupper(trim($estatus));

        if ($estatus === 'BO') {
            return 'BACK_ORDER';
        }

        return $estatus;
    }
}

?>


<main class="account-page">

    <div class="account-container">


        <!-- =====================================================
             SIDEBAR
        ====================================================== -->

        <aside class="account-sidebar">

            <!-- DISTRIBUIDOR -->

            <section class="account-card distributor-card">

                <div class="distributor-avatar">

                    <?= htmlspecialchars($distribuidor['iniciales'] ?? 'DR', ENT_QUOTES, 'UTF-8'); ?>

                </div>

                <h2>

                    <?= htmlspecialchars($distribuidor['nombre'] ?? '', ENT_QUOTES, 'UTF-8'); ?>

                </h2>

                <strong class="distributor-key">

                    Clave:

                    <?= htmlspecialchars($distribuidor['clave'] ?? '', ENT_QUOTES, 'UTF-8'); ?>

                </strong>

                <?php if (!empty($distribuidor['correo'])): ?>

                    <a href="mailto:<?= htmlspecialchars($distribuidor['correo'], ENT_QUOTES, 'UTF-8'); ?>"
                        class="distributor-email">
                        <?= htmlspecialchars($distribuidor['correo'], ENT_QUOTES, 'UTF-8'); ?>
                    </a>

                <?php endif; ?>

                <div class="distributor-status">
                    <span></span>
                    <?= htmlspecialchars($distribuidor['estado'] ?? '', ENT_QUOTES, 'UTF-8'); ?>
                </div>

            </section>


            <!-- MENÚ -->

            <nav class="account-card account-menu" aria-label="Menú de mi cuenta">

                <a href="<?= base_url(); ?>/orders/micuenta" class="account-menu-item active">
                    <i class="ri-home-5-line"></i>
                    <span> Resumen</span>
                </a>

                <a href="<?= base_url(); ?>/orders/sedes" class="account-menu-item">
                    <i class="ri-map-pin-line"></i>
                    <span> Sedes</span>
                </a>


                <a href="<?= base_url(); ?>/orders/micuenta" class="account-menu-item">
                    <i class="ri-file-list-3-line"></i>
                    <span>Mis pedidos</span>
                </a>


                <a href="<?= base_url(); ?>/orders/trazabilidad" class="account-menu-item">
                    <i class="ri-line-chart-line"></i>
                    <span>Trazabilidad</span>
                </a>


                <a href="<?= base_url(); ?>/orders/documentos" class="account-menu-item">
                    <i class="ri-folder-line"></i>
                    <span>Documentos</span>
                </a>


                <a href="<?= base_url(); ?>/orders/perfil" class="account-menu-item">
                    <i class="ri-user-settings-line"></i>
                    <span>Perfil y accesos</span>
                </a>

            </nav>


            <!-- SOPORTE -->

            <section class="account-card support-card">
                <div class="support-icon">
                    <i class="ri-customer-service-2-line"></i>
                </div>
                <div class="support-content">
                    <h3>
                        ¿Necesitas ayuda?
                    </h3>
                    <p>
                        Nuestro equipo está disponible para apoyarte.
                    </p>
                </div>

                <a href="mailto:soporte@ldrsolutions.com" class="support-button">

                    Contactar soporte

                    <i class="ri-arrow-right-s-line"></i>

                </a>

            </section>

        </aside>


        <!-- =====================================================
             CONTENIDO
        ====================================================== -->

        <section class="account-content">


            <!-- HERO -->

            <section class="account-hero">

                <div class="account-hero-overlay"></div>


                <div class="account-hero-content">

                    <div class="account-hero-icon">

                        <i class="ri-file-list-3-line"></i>

                    </div>

                    <div>

                        <h1>Resumen de pedidos</h1>

                        <p>
                            Consulta todas las solicitudes generadas
                            por tu distribuidora y da seguimiento
                            al estatus de cada pedido.
                        </p>

                    </div>

                </div>

            </section>


            <!-- =================================================
                 MÉTRICAS
            ================================================== -->

            <section class="account-metrics">

                <article class="metric-card">

                    <div class="metric-icon">

                        <i class="ri-file-list-3-line"></i>

                    </div>

                    <div class="metric-content">

                        <strong>

                            <?= number_format(intval($metricas['pedidos'])); ?>

                        </strong>

                        <h3>
                            Pedidos totales
                        </h3>

                        <span>
                            Todos los tiempos
                        </span>

                    </div>

                </article>


                <article class="metric-card">

                    <div class="metric-icon">

                        <i class="ri-box-3-line"></i>

                    </div>


                    <div class="metric-content">

                        <strong>

                            <?= number_format(intval($metricas['unidades'])); ?>

                        </strong>

                        <h3>
                            Unidades solicitadas
                        </h3>

                        <span>
                            Total acumulado
                        </span>

                    </div>

                </article>


                <article class="metric-card">

                    <div class="metric-icon">

                        <i class="ri-car-line"></i>

                    </div>

                    <div class="metric-content">

                        <strong>

                            <?= number_format(intval($metricas['modelos'])); ?>

                        </strong>

                        <h3>
                            Modelos diferentes
                        </h3>

                        <span>
                            Total solicitados
                        </span>

                    </div>

                </article>

                <article class="metric-card metric-total">

                    <div class="metric-icon">

                        <i class="ri-money-dollar-circle-line"></i>

                    </div>


                    <div class="metric-content">

                        <strong>

                            <?= moneyOrders(floatval($metricas['total'])); ?>

                        </strong>

                        <h3>
                            Valor total estimado
                        </h3>

                        <span>
                            Incluye IVA
                        </span>

                    </div>

                </article>

            </section>


            <!-- =================================================
                 FILTROS
            ================================================== -->

            <section class="account-filters">


                <div class="filter-group filter-search">

                    <label for="orderSearch">

                        Buscar pedido

                    </label>

                    <div class="input-icon-wrapper">

                        <input type="search" id="orderSearch" class="account-input" placeholder="Folio...">

                        <i class="ri-search-line"></i>

                    </div>

                </div>

                <div class="filter-group filter-date">

                    <label>Rango de fechas</label>

                    <div class="date-range">

                        <div class="input-icon-wrapper">

                            <input type="date" id="dateFrom" class="account-input">

                        </div>


                        <div class="input-icon-wrapper">

                            <input type="date" id="dateTo" class="account-input">

                        </div>

                    </div>

                </div>


                <div class="filter-group">

                    <label for="statusFilter">

                        Estatus

                    </label>


                    <select id="statusFilter" class="account-input">

                        <option value="">
                            Todos
                        </option>

                        <option value="PENDIENTE">
                            Pendiente
                        </option>

                        <option value="RECIBIDO">
                            Recibido
                        </option>

                        <option value="EN_REVISION">
                            En revisión
                        </option>

                        <option value="INVENTARIO_ASIGNADO">
                            Inventario asignado
                        </option>

                        <option value="BACK_ORDER">
                            Back Order
                        </option>

                        <option value="EN_TRANSITO">
                            En tránsito
                        </option>

                        <option value="EN_ENTREGA">
                            En entrega
                        </option>

                        <option value="ENTREGADO">
                            Entregado
                        </option>

                        <option value="FACTURADO">
                            Facturado
                        </option>

                        <option value="CANCELADO">
                            Cancelado
                        </option>

                    </select>

                </div>

                <div class="filter-actions">

                    <button type="button" class="button-clear-filters" id="btnClearOrderFilters">

                        <i class="ri-restart-line"></i>

                        Limpiar filtros

                    </button>

                </div>

            </section>

            <!-- =================================================
                 PEDIDOS
            ================================================== -->

            <section class="orders-panel">


                <!-- TABS -->

                <div class="orders-status-tabs">


                    <button type="button" class="status-tab active" data-status="">

                        Todos

                        <span><?= number_format(intval($metricas['pedidos'])); ?></span>

                    </button>


                    <button type="button" class="status-tab" data-status="PENDIENTE">

                        Pendiente

                        <span>

                            <?= number_format(intval($conteoEstatus['PENDIENTE'] ?? 0)); ?>

                        </span>

                    </button>

                    <button type="button" class="status-tab" data-status="EN_REVISION">

                        En revisión

                        <span>

                            <?= number_format(intval($conteoEstatus['EN_REVISION'] ?? 0)); ?>

                        </span>

                    </button>

                    <button type="button" class="status-tab" data-status="BACK_ORDER">

                        Back Order

                        <span>

                            <?= number_format(intval($conteoEstatus['BACK_ORDER'] ?? 0)); ?>

                        </span>

                    </button>

                    <button type="button" class="status-tab" data-status="FACTURADO">

                        Facturado

                        <span>

                            <?= number_format(intval($conteoEstatus['FACTURADO'] ?? 0)); ?>

                        </span>

                    </button>

                    <button type="button" class="status-tab" data-status="CANCELADO">

                        Cancelado

                        <span>

                            <?= number_format(intval($conteoEstatus['CANCELADO'] ?? 0)); ?>

                        </span>

                    </button>

                </div>


                <!-- TABLA -->

                <div class="orders-table-wrapper">

                    <table class="orders-table">

                        <thead>

                            <tr>

                                <th>Folio</th>
                                <!-- <th>Clave</th> -->
                                <th>Fecha</th>
                                <!-- <th>Registrado por</th> -->
                                <th>Unidades</th>
                                <th>Modelos</th>
                                <th>Subtotal</th>
                                <th>IVA</th>
                                <th>Total</th>
                                <th>Estatus</th>
                                <th style="text-align: center;">Acciones</th>
                            </tr>

                        </thead>


                        <tbody id="ordersTableBody">

                            <?php if (!empty($pedidos)): ?>

                                <?php foreach ($pedidos as $pedido): ?>

                                    <?php
                                    $estatus = statusPedidoNormalizado($pedido['estatus'] ?? 'PENDIENTE');
                                    $fechaPedido = '';

                                    if (!empty($pedido['fecha_pedido'])) {

                                        $timestamp = strtotime($pedido['fecha_pedido']);

                                        if ($timestamp !== false) {

                                            $fechaPedido = date('d/m/Y', $timestamp);
                                        }
                                    }

                                    ?>

                                    <tr class="order-row" data-status="<?= htmlspecialchars(
                                        $estatus,
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ); ?>" data-date="<?= htmlspecialchars(
                                         substr(
                                             $pedido[
                                                 'fecha_pedido'
                                             ] ?? '',
                                             0,
                                             10
                                         ),
                                         ENT_QUOTES,
                                         'UTF-8'
                                     ); ?>">


                                        <td data-label="Folio">

                                            <strong class="order-folio">

                                                <?= htmlspecialchars($pedido['folio_pedido'], ENT_QUOTES, 'UTF-8'); ?>

                                            </strong>

                                        </td>


                                        <!-- <td data-label="Clave">

                                            <span class="order-public-key">

                                                <?= htmlspecialchars($pedido['clave'], ENT_QUOTES, 'UTF-8'); ?>

                                            </span>

                                        </td> -->

                                        <td data-label="Fecha">

                                            <?= htmlspecialchars($fechaPedido, ENT_QUOTES, 'UTF-8'); ?>

                                        </td>

                                        <!-- <td data-label="Registrado por">

                                            <span class="registered-user">

                                                <i class="ri-account-circle-line"></i>

                                                <?= htmlspecialchars(trim($pedido['registrado_por'] ?? '') ?: ($pedido['nombre_usuario'] ?? 'Usuario del portal'), ENT_QUOTES, 'UTF-8'); ?>

                                            </span>

                                        </td> -->

                                        <td data-label="Unidades">

                                            <?= number_format(intval($pedido['total_unidades'])); ?>

                                        </td>

                                        <td data-label="Modelos">

                                            <?= number_format(intval($pedido['total_modelos'])); ?>

                                        </td>

                                        <td data-label="Subtotal">

                                            <?= moneyOrders(floatval($pedido['subtotal'])); ?>

                                        </td>

                                        <td data-label="IVA">

                                            <?= moneyOrders(floatval($pedido['iva'])); ?>

                                        </td>

                                        <td data-label="Total">

                                            <strong>

                                                <?= moneyOrders(floatval($pedido['total'])); ?>

                                            </strong>

                                        </td>

                                        <td data-label="Estatus">

                                            <span
                                                class="order-status status-<?= strtolower(str_replace('_', '-', $estatus)); ?>">

                                                <?= htmlspecialchars(statusPedidoTexto($estatus), ENT_QUOTES, 'UTF-8'); ?>

                                            </span>

                                        </td>

                                        <td data-label="Acciones">

                                            <?php
                                            /*
                                             * ==========================================================
                                             * CONFIGURACIÓN DE ACCIONES
                                             * ==========================================================
                                             */
                                            $estatusPedido = strtoupper(trim($pedido['estatus'] ?? ''));
                                            $clavePedido = $pedido['clave'] ?? '';
                                            /*
                                             * Solamete se puede editar/cancelar
                                             * mientras el pedido esté PENDIENTE.
                                             */
                                            $puedeEditar = $estatusPedido === 'PENDIENTE';
                                            $puedeCancelar = $estatusPedido === 'PENDIENTE';

                                            ?>
                                            <div class="order-actions">


                                                <a href="<?= base_url(); ?>/orders/detallepedido/<?= rawurlencode($clavePedido); ?>"
                                                    class="button-order-action button-order-view" title="Consultar pedido">
                                                    <i class="ri-eye-line"></i>
                                                    <span>
                                                        Ver detalle
                                                    </span>
                                                </a>

                                                

                                                <button type="button" class="button-order-action button-order-print"
                                                    data-action="print-order"
                                                    data-clave="<?= htmlspecialchars($clavePedido, ENT_QUOTES, 'UTF-8'); ?>"
                                                    data-folio="<?= htmlspecialchars($pedido['folio_pedido'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                                                    title="Imprimir hoja de pedido">
                                                    <i class="ri-printer-line"></i>
                                                    <span>
                                                        Imprimir
                                                    </span>

                                                </button>

                                            </div>

                                        </td>

                                    </tr>

                                <?php endforeach; ?>


                            <?php else: ?>


                                <tr id="emptyOrdersRow">

                                    <td colspan="11" class="orders-empty">

                                        <div class="orders-empty-content">

                                            <i class="ri-file-list-3-line"></i>

                                            <strong>
                                                Aún no tienes pedidos
                                            </strong>

                                            <span>
                                                Cuando generes una solicitud,
                                                aparecerá aquí.
                                            </span>

                                            <a href="<?= base_url(); ?>/orders/home#catalogo" class="btn btn-primary">

                                                Consultar catálogo

                                            </a>

                                        </div>

                                    </td>

                                </tr>


                            <?php endif; ?>


                        </tbody>

                    </table>

                </div>

                <!-- SIN RESULTADOS POR FILTRO -->

                <div id="ordersNoResults" class="orders-no-results" hidden>

                    <i class="ri-search-line"></i>

                    <strong>
                        No encontramos pedidos
                    </strong>

                    <span>
                        Modifica los filtros de búsqueda.
                    </span>

                </div>

                <!-- FOOTER -->

                <div class="orders-footer" id="ordersFooter">

                    <p id="ordersPaginationInfo">

                        Mostrando pedidos

                    </p>

                    <nav class="orders-pagination" id="ordersPagination" aria-label="Paginación de pedidos">
                    </nav>

                </div>

            </section>

        </section>

    </div>

</main>

<?php

footerOrders($data);

?>