<?php
headerOrders($data);

/*
 * Datos temporales.
 * Después se reemplazarán con información del controlador.
 */
$distribuidor = [
    'iniciales' => 'DN',
    'nombre' => 'Distribuidora Demo Norte',
    'clave' => 'DIST-00045',
    'correo' => 'compras@distribuidora.com',
    'estado' => 'Cuenta activa'
];

$metricas = [
    'pedidos' => 24,
    'unidades' => 352,
    'modelos' => 18,
    'total' => 24860800
];

$pedidosDemo = [
    [
        'folio' => 'PED-20260705-0001',
        'clave' => 'DIST-00045',
        'fecha' => '05 Jul 2026',
        'registrado_por' => 'Carlos Cruz',
        'unidades' => 11,
        'modelos' => 3,
        'subtotal' => 5534482.76,
        'iva' => 885517.24,
        'total' => 6420000,
        'estatus' => 'EN_REVISION',
        'estatus_texto' => 'En revisión'
    ],
    [
        'folio' => 'PED-20260630-0002',
        'clave' => 'DIST-00045',
        'fecha' => '30 Jun 2026',
        'registrado_por' => 'Ana Martínez',
        'unidades' => 6,
        'modelos' => 2,
        'subtotal' => 3612068.97,
        'iva' => 577931.03,
        'total' => 4190000,
        'estatus' => 'INVENTARIO_ASIGNADO',
        'estatus_texto' => 'Inventario asignado'
    ],
    [
        'folio' => 'PED-20260625-0003',
        'clave' => 'DIST-00045',
        'fecha' => '25 Jun 2026',
        'registrado_por' => 'Carlos Cruz',
        'unidades' => 14,
        'modelos' => 4,
        'subtotal' => 7254310.34,
        'iva' => 1160689.66,
        'total' => 8415000,
        'estatus' => 'EN_TRANSITO',
        'estatus_texto' => 'En tránsito'
    ],
    [
        'folio' => 'PED-20260618-0004',
        'clave' => 'DIST-00045',
        'fecha' => '18 Jun 2026',
        'registrado_por' => 'Luis Ramírez',
        'unidades' => 8,
        'modelos' => 3,
        'subtotal' => 4137931.03,
        'iva' => 662068.97,
        'total' => 4800000,
        'estatus' => 'PENDIENTE',
        'estatus_texto' => 'Pendiente'
    ],
    [
        'folio' => 'PED-20260612-0005',
        'clave' => 'DIST-00045',
        'fecha' => '12 Jun 2026',
        'registrado_por' => 'Ana Martínez',
        'unidades' => 10,
        'modelos' => 3,
        'subtotal' => 4827586.21,
        'iva' => 772413.79,
        'total' => 5600000,
        'estatus' => 'RECIBIDO',
        'estatus_texto' => 'Recibido'
    ]
];

function moneyOrders(float $amount): string
{
    return '$' . number_format(
        $amount,
        2,
        '.',
        ','
    );
}
?>

<main class="account-page">

    <div class="account-container">

        <!-- =====================================================
         * COLUMNA LATERAL
         * ===================================================== -->
        <aside class="account-sidebar">

            <!-- DISTRIBUIDOR -->
            <section class="account-card distributor-card">

                <div class="distributor-avatar">
                    <?= htmlspecialchars(
                        $distribuidor['iniciales']
                    ); ?>
                </div>

                <h2>
                    <?= htmlspecialchars(
                        $distribuidor['nombre']
                    ); ?>
                </h2>

                <strong class="distributor-key">
                    Clave:
                    <?= htmlspecialchars(
                        $distribuidor['clave']
                    ); ?>
                </strong>

                <a
                    href="mailto:<?= htmlspecialchars(
                        $distribuidor['correo']
                    ); ?>"
                    class="distributor-email"
                >
                    <?= htmlspecialchars(
                        $distribuidor['correo']
                    ); ?>
                </a>

                <div class="distributor-status">
                    <span></span>

                    <?= htmlspecialchars(
                        $distribuidor['estado']
                    ); ?>
                </div>

            </section>

            <!-- MENÚ LATERAL -->
            <nav
                class="account-card account-menu"
                aria-label="Menú de mi cuenta"
            >

                <a
                    href="<?= base_url(); ?>/orders/micuenta"
                    class="account-menu-item"
                >
                    <i class="ri-home-5-line"></i>
                    <span>Resumen</span>
                </a>

                <a
                    href="<?= base_url(); ?>/orders/sedes"
                    class="account-menu-item"
                >
                    <i class="ri-map-pin-line"></i>
                    <span>Sedes</span>
                </a>

                <a
                    href="<?= base_url(); ?>/orders/micuenta"
                    class="account-menu-item active"
                >
                    <i class="ri-file-list-3-line"></i>
                    <span>Mis pedidos</span>
                </a>

                <a
                    href="<?= base_url(); ?>/orders/trazabilidad"
                    class="account-menu-item"
                >
                    <i class="ri-line-chart-line"></i>
                    <span>Trazabilidad</span>
                </a>

                <a
                    href="<?= base_url(); ?>/orders/documentos"
                    class="account-menu-item"
                >
                    <i class="ri-folder-line"></i>
                    <span>Documentos</span>
                </a>

                <a
                    href="<?= base_url(); ?>/orders/perfil"
                    class="account-menu-item"
                >
                    <i class="ri-user-settings-line"></i>
                    <span>Perfil y accesos</span>
                </a>

                <a
                    href="<?= base_url(); ?>/orders/configuracion"
                    class="account-menu-item"
                >
                    <i class="ri-settings-3-line"></i>
                    <span>Configuración</span>
                </a>

            </nav>

            <!-- SOPORTE -->
            <section class="account-card support-card">

                <div class="support-icon">
                    <i class="ri-customer-service-2-line"></i>
                </div>

                <div class="support-content">
                    <h3>¿Necesitas ayuda?</h3>

                    <p>
                        Nuestro equipo está disponible para apoyarte.
                    </p>
                </div>

                <a
                    href="mailto:soporte@ldrsolutions.com"
                    class="support-button"
                >
                    Contactar soporte

                    <i class="ri-arrow-right-s-line"></i>
                </a>

            </section>

        </aside>

        <!-- =====================================================
         * CONTENIDO PRINCIPAL
         * ===================================================== -->
        <section class="account-content">

            <!-- BANNER -->
            <section class="account-hero">

                <div class="account-hero-overlay"></div>

                <div class="account-hero-content">

                    <div class="account-hero-icon">
                        <i class="ri-file-list-3-line"></i>
                    </div>

                    <div>
                        <h1>Mis pedidos</h1>

                        <p>
                            Consulta todos tus pedidos de unidades,
                            su estado actual y da seguimiento a cada
                            solicitud.
                        </p>
                    </div>

                </div>

            </section>

            <!-- MÉTRICAS -->
            <section class="account-metrics">

                <article class="metric-card">

                    <div class="metric-icon">
                        <i class="ri-file-list-3-line"></i>
                    </div>

                    <div class="metric-content">
                        <strong>
                            <?= number_format(
                                $metricas['pedidos']
                            ); ?>
                        </strong>

                        <h3>Pedidos totales</h3>

                        <span>Todos los tiempos</span>
                    </div>

                </article>

                <article class="metric-card">

                    <div class="metric-icon">
                        <i class="ri-box-3-line"></i>
                    </div>

                    <div class="metric-content">
                        <strong>
                            <?= number_format(
                                $metricas['unidades']
                            ); ?>
                        </strong>

                        <h3>Unidades solicitadas</h3>

                        <span>Total acumulado</span>
                    </div>

                </article>

                <article class="metric-card">

                    <div class="metric-icon">
                        <i class="ri-car-line"></i>
                    </div>

                    <div class="metric-content">
                        <strong>
                            <?= number_format(
                                $metricas['modelos']
                            ); ?>
                        </strong>

                        <h3>Modelos diferentes</h3>

                        <span>Total solicitados</span>
                    </div>

                </article>

                <article class="metric-card metric-total">

                    <div class="metric-icon">
                        <i class="ri-money-dollar-circle-line"></i>
                    </div>

                    <div class="metric-content">
                        <strong>
                            <?= moneyOrders(
                                $metricas['total']
                            ); ?>
                        </strong>

                        <h3>Valor total estimado</h3>

                        <span>Incluye IVA</span>
                    </div>

                </article>

            </section>

            <!-- FILTROS -->
            <section class="account-filters">

                <div class="filter-group filter-search">

                    <label for="orderSearch">
                        Buscar pedido
                    </label>

                    <div class="input-icon-wrapper">

                        <input
                            type="search"
                            id="orderSearch"
                            class="account-input"
                            placeholder="Buscar por folio, clave o registrado por..."
                        >

                        <i class="ri-search-line"></i>

                    </div>

                </div>

                <div class="filter-group filter-date">

                    <label>Rango de fechas</label>

                    <div class="date-range">

                        <div class="input-icon-wrapper">
                            <input
                                type="date"
                                id="dateFrom"
                                class="account-input"
                                aria-label="Fecha inicial"
                            >

                            <i class="ri-calendar-line"></i>
                        </div>

                        <div class="input-icon-wrapper">
                            <input
                                type="date"
                                id="dateTo"
                                class="account-input"
                                aria-label="Fecha final"
                            >

                            <i class="ri-calendar-line"></i>
                        </div>

                    </div>

                </div>

                <div class="filter-group">

                    <label for="statusFilter">
                        Estatus
                    </label>

                    <select
                        id="statusFilter"
                        class="account-input"
                    >
                        <option value="">
                            Todos los estatus
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

                        <option value="EN_TRANSITO">
                            En tránsito
                        </option>

                        <option value="EN_ENTREGA">
                            En entrega
                        </option>

                        <option value="ENTREGADO">
                            Entregado
                        </option>

                        <option value="CANCELADO">
                            Cancelado
                        </option>
                    </select>

                </div>

                <div class="filter-actions">

                    <button
                        type="button"
                        class="button-clear-filters"
                        id="btnClearOrderFilters"
                    >
                        <i class="ri-restart-line"></i>
                        Limpiar filtros
                    </button>

                </div>

            </section>

            <!-- LISTADO -->
            <section class="orders-panel">

                <!-- PESTAÑAS -->
                <div class="orders-status-tabs">

                    <button
                        type="button"
                        class="status-tab active"
                        data-status=""
                    >
                        Todos
                        <span>24</span>
                    </button>

                    <button
                        type="button"
                        class="status-tab"
                        data-status="PENDIENTE"
                    >
                        Pendiente
                        <span class="status-count pending">6</span>
                    </button>

                    <button
                        type="button"
                        class="status-tab"
                        data-status="RECIBIDO"
                    >
                        Recibido
                        <span class="status-count received">5</span>
                    </button>

                    <button
                        type="button"
                        class="status-tab"
                        data-status="EN_TRANSITO"
                    >
                        En tránsito
                        <span class="status-count transit">4</span>
                    </button>

                    <button
                        type="button"
                        class="status-tab"
                        data-status="EN_ENTREGA"
                    >
                        En entrega
                        <span class="status-count delivery">3</span>
                    </button>

                    <button
                        type="button"
                        class="status-tab"
                        data-status="ENTREGADO"
                    >
                        Entregado
                        <span class="status-count delivered">6</span>
                    </button>

                </div>

                <!-- TABLA -->
                <div class="orders-table-wrapper">

                    <table class="orders-table">

                        <thead>
                            <tr>
                                <th>Folio</th>
                                <th>Clave</th>
                                <th>Fecha del pedido</th>
                                <th>Registrado por</th>
                                <th>Unidades</th>
                                <th>Modelos</th>
                                <th>Subtotal</th>
                                <th>IVA</th>
                                <th>Total</th>
                                <th>Estatus</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>

                        <tbody id="ordersTableBody">

                            <?php foreach ($pedidosDemo as $pedido): ?>

                                <tr>

                                    <td data-label="Folio">
                                        <strong class="order-folio">
                                            <?= htmlspecialchars(
                                                $pedido['folio']
                                            ); ?>
                                        </strong>
                                    </td>

                                    <td data-label="Clave">
                                        <?= htmlspecialchars(
                                            $pedido['clave']
                                        ); ?>
                                    </td>

                                    <td data-label="Fecha del pedido">
                                        <?= htmlspecialchars(
                                            $pedido['fecha']
                                        ); ?>
                                    </td>

                                    <td data-label="Registrado por">

                                        <span class="registered-user">
                                            <i class="ri-account-circle-line"></i>

                                            <?= htmlspecialchars(
                                                $pedido['registrado_por']
                                            ); ?>
                                        </span>

                                    </td>

                                    <td data-label="Unidades">
                                        <?= number_format(
                                            $pedido['unidades']
                                        ); ?>
                                    </td>

                                    <td data-label="Modelos">
                                        <?= number_format(
                                            $pedido['modelos']
                                        ); ?>
                                    </td>

                                    <td data-label="Subtotal">
                                        <?= moneyOrders(
                                            $pedido['subtotal']
                                        ); ?>
                                    </td>

                                    <td data-label="IVA">
                                        <?= moneyOrders(
                                            $pedido['iva']
                                        ); ?>
                                    </td>

                                    <td data-label="Total">
                                        <strong>
                                            <?= moneyOrders(
                                                $pedido['total']
                                            ); ?>
                                        </strong>
                                    </td>

                                    <td data-label="Estatus">

                                        <span
                                            class="order-status status-<?= strtolower(
                                                $pedido['estatus']
                                            ); ?>"
                                        >
                                            <?= htmlspecialchars(
                                                $pedido['estatus_texto']
                                            ); ?>
                                        </span>

                                    </td>

                                    <td data-label="Acciones">

                                        <div class="order-actions">

                                            <a
                                                href="<?= base_url(); ?>/orders/detallepedido/<?= urlencode(
                                                    $pedido['folio']
                                                ); ?>"
                                                class="button-order-detail"
                                            >
                                                <i class="ri-eye-line"></i>
                                                Ver detalle
                                            </a>

                                            <button
                                                type="button"
                                                class="button-order-menu"
                                                aria-label="Más acciones"
                                            >
                                                <i class="ri-arrow-down-s-line"></i>
                                            </button>

                                        </div>

                                    </td>

                                </tr>

                            <?php endforeach; ?>

                        </tbody>

                    </table>

                </div>

                <!-- PIE Y PAGINACIÓN -->
                <div class="orders-footer">

                    <p>
                        Mostrando
                        <strong>1</strong>
                        a
                        <strong>5</strong>
                        de
                        <strong>24</strong>
                        pedidos
                    </p>

                    <nav
                        class="orders-pagination"
                        aria-label="Paginación de pedidos"
                    >

                        <button
                            type="button"
                            class="pagination-button"
                            disabled
                        >
                            <i class="ri-arrow-left-line"></i>
                        </button>

                        <button
                            type="button"
                            class="pagination-button active"
                        >
                            1
                        </button>

                        <button
                            type="button"
                            class="pagination-button"
                        >
                            2
                        </button>

                        <button
                            type="button"
                            class="pagination-button"
                        >
                            3
                        </button>

                        <span class="pagination-dots">
                            ...
                        </span>

                        <button
                            type="button"
                            class="pagination-button"
                        >
                            5
                        </button>

                        <button
                            type="button"
                            class="pagination-button"
                        >
                            <i class="ri-arrow-right-line"></i>
                        </button>

                    </nav>

                </div>

            </section>

        </section>

    </div>

</main>

<?php
footerOrders($data);
?>