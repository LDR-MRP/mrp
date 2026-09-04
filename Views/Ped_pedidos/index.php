<?php headerAdmin($data); ?>

<div id="contentAjax"></div>

<div class="main-content">

    <div class="page-content">

        <div class="container-fluid">

            <!-- ============================================================
                 ENCABEZADO
            ============================================================= -->
            <div class="row mb-3">

                <div class="col-12">

                    <div
                        class="d-flex justify-content-between align-items-center flex-wrap gap-3">

                        <div class="d-flex align-items-center">

                            <div
                                class="avatar-sm rounded-circle bg-primary-subtle text-primary
                                       d-flex align-items-center justify-content-center me-3">

                                <i class="ri-shopping-bag-3-line fs-2"></i>

                            </div>

                            <div>

                                <h3 class="mb-0 fw-bold">
                                    Gestión de Pedidos
                                </h3>

                                <small class="text-muted">
                                    Consulta, revisa y administra los pedidos
                                    generados por los distribuidores.
                                </small>

                            </div>

                        </div>


                        <div class="d-flex align-items-center gap-2">

                            <button
                                type="button"
                                class="btn btn-soft-primary"
                                id="btnRefrescarPedidos">

                                <i class="ri-refresh-line me-1"></i>

                                Actualizar

                            </button>

                        </div>

                    </div>

                </div>

            </div>


            <!-- ============================================================
                 INDICADORES
            ============================================================= -->
            <div class="row g-3 mb-3">

                <!-- TOTAL -->
                <div class="col-xl-3 col-md-6">

                    <div class="card card-animate h-100 mb-0">

                        <div class="card-body">

                            <div class="d-flex align-items-center">

                                <div class="flex-grow-1">

                                    <p
                                        class="text-uppercase fw-medium text-muted
                                               text-truncate mb-0">

                                        Total pedidos

                                    </p>

                                </div>

                            </div>


                            <div
                                class="d-flex align-items-end justify-content-between mt-3">

                                <div>

                                    <h4
                                        class="fs-22 fw-semibold ff-secondary mb-0"
                                        id="statTotalPedidos">

                                        0

                                    </h4>

                                    <span class="text-muted fs-12">
                                        Pedidos registrados
                                    </span>

                                </div>


                                <div
                                    class="avatar-sm flex-shrink-0">

                                    <span
                                        class="avatar-title bg-primary-subtle
                                               text-primary rounded fs-3">

                                        <i class="ri-file-list-3-line"></i>

                                    </span>

                                </div>

                            </div>

                        </div>

                    </div>

                </div>


                <!-- PENDIENTES -->
                <div class="col-xl-3 col-md-6">

                    <div class="card card-animate h-100 mb-0">

                        <div class="card-body">

                            <div class="d-flex align-items-center">

                                <div class="flex-grow-1">

                                    <p
                                        class="text-uppercase fw-medium text-muted
                                               text-truncate mb-0">

                                        Pendientes

                                    </p>

                                </div>

                            </div>


                            <div
                                class="d-flex align-items-end justify-content-between mt-3">

                                <div>

                                    <h4
                                        class="fs-22 fw-semibold ff-secondary mb-0"
                                        id="statPendientes">

                                        0

                                    </h4>

                                    <span class="text-muted fs-12">
                                        Esperando revisión
                                    </span>

                                </div>


                                <div class="avatar-sm flex-shrink-0">

                                    <span
                                        class="avatar-title bg-warning-subtle
                                               text-warning rounded fs-3">

                                        <i class="ri-time-line"></i>

                                    </span>

                                </div>

                            </div>

                        </div>

                    </div>

                </div>


                <!-- EN REVISIÓN -->
                <div class="col-xl-3 col-md-6">

                    <div class="card card-animate h-100 mb-0">

                        <div class="card-body">

                            <div class="d-flex align-items-center">

                                <div class="flex-grow-1">

                                    <p
                                        class="text-uppercase fw-medium text-muted
                                               text-truncate mb-0">

                                        En revisión

                                    </p>

                                </div>

                            </div>


                            <div
                                class="d-flex align-items-end justify-content-between mt-3">

                                <div>

                                    <h4
                                        class="fs-22 fw-semibold ff-secondary mb-0"
                                        id="statEnRevision">

                                        0

                                    </h4>

                                    <span class="text-muted fs-12">
                                        Siendo gestionados
                                    </span>

                                </div>


                                <div class="avatar-sm flex-shrink-0">

                                    <span
                                        class="avatar-title bg-info-subtle
                                               text-info rounded fs-3">

                                        <i class="ri-search-eye-line"></i>

                                    </span>

                                </div>

                            </div>

                        </div>

                    </div>

                </div>


                <!-- IMPORTE -->
                <div class="col-xl-3 col-md-6">

                    <div class="card card-animate h-100 mb-0">

                        <div class="card-body">

                            <div class="d-flex align-items-center">

                                <div class="flex-grow-1">

                                    <p
                                        class="text-uppercase fw-medium text-muted
                                               text-truncate mb-0">

                                        Importe solicitado

                                    </p>

                                </div>

                            </div>


                            <div
                                class="d-flex align-items-end justify-content-between mt-3">

                                <div>

                                    <h4
                                        class="fs-22 fw-semibold ff-secondary mb-0"
                                        id="statImportePedidos">

                                        $0.00

                                    </h4>

                                    <span class="text-muted fs-12">
                                        Total acumulado
                                    </span>

                                </div>


                                <div class="avatar-sm flex-shrink-0">

                                    <span
                                        class="avatar-title bg-success-subtle
                                               text-success rounded fs-3">

                                        <i class="ri-money-dollar-circle-line"></i>

                                    </span>

                                </div>

                            </div>

                        </div>

                    </div>

                </div>

            </div>


            <!-- ============================================================
                 FILTROS
            ============================================================= -->
            <div class="row">

                <div class="col-12">

                    <div class="card">

                        <div class="card-header">

                            <div
                                class="d-flex align-items-center justify-content-between">

                                <div>

                                    <h5 class="card-title mb-1">
                                        Filtros de búsqueda
                                    </h5>

                                    <p class="text-muted mb-0 fs-12">
                                        Utiliza los filtros para localizar pedidos
                                        específicos.
                                    </p>

                                </div>


                                <button
                                    type="button"
                                    class="btn btn-sm btn-ghost-secondary"
                                    id="btnLimpiarFiltros">

                                    <i class="ri-filter-off-line me-1"></i>

                                    Limpiar filtros

                                </button>

                            </div>

                        </div>


                        <div class="card-body">

                            <div class="row g-3">

                                <!-- BUSCADOR -->
                                <div class="col-12 col-lg-4 col-xxl-3">

                                    <label
                                        for="filterSearch"
                                        class="form-label">

                                        Buscar pedido

                                    </label>


                                    <div class="input-group">

                                        <span class="input-group-text">

                                            <i class="ri-search-line"></i>

                                        </span>

                                        <input
                                            type="text"
                                            class="form-control"
                                            id="filterSearch"
                                            autocomplete="off"
                                            placeholder="Folio, clave o distribuidor">

                                    </div>

                                </div>


                                <!-- ESTATUS -->
                                <div class="col-12 col-md-6 col-lg-4 col-xxl-2">

                                    <label
                                        for="filterEstatus"
                                        class="form-label">

                                        Estatus

                                    </label>

                                    <select
                                        class="form-select"
                                        id="filterEstatus">

                                        <option value="">
                                            Todos
                                        </option>

                                        <option value="PENDIENTE">
                                            Pendiente
                                        </option>

                                        <option value="EN_REVISION">
                                            En revisión
                                        </option>

                                        <option value="AUTORIZADO">
                                            Autorizado
                                        </option>

                                        <option value="RECHAZADO">
                                            Rechazado
                                        </option>

                                        <option value="CANCELADO">
                                            Cancelado
                                        </option>

                                        <option value="FINALIZADO">
                                            Finalizado
                                        </option>

                                    </select>

                                </div>


                                <!-- PRIORIDAD -->
                                <div class="col-12 col-md-6 col-lg-4 col-xxl-2">

                                    <label
                                        for="filterPrioridad"
                                        class="form-label">

                                        Prioridad

                                    </label>

                                    <select
                                        class="form-select"
                                        id="filterPrioridad">

                                        <option value="">
                                            Todas
                                        </option>

                                        <option value="BAJA">
                                            Baja
                                        </option>

                                        <option value="MEDIA">
                                            Media
                                        </option>

                                        <option value="ALTA">
                                            Alta
                                        </option>

                                        <option value="URGENTE">
                                            Urgente
                                        </option>

                                    </select>

                                </div>


                                <!-- DISTRIBUIDOR -->
                                <div class="col-12 col-lg-4 col-xxl-3">

                                    <label
                                        for="filterDistribuidor"
                                        class="form-label">

                                        Distribuidor

                                    </label>

                                    <select
                                        class="form-select"
                                        id="filterDistribuidor">

                                        <option value="">
                                            Todos los distribuidores
                                        </option>

                                    </select>

                                </div>


                                <!-- FECHA DESDE -->
                                <div class="col-12 col-md-6 col-lg-3 col-xxl-2">

                                    <label
                                        for="filterDesde"
                                        class="form-label">

                                        Pedido desde

                                    </label>

                                    <input
                                        type="date"
                                        class="form-control"
                                        id="filterDesde">

                                </div>


                                <!-- FECHA HASTA -->
                                <div class="col-12 col-md-6 col-lg-3 col-xxl-2">

                                    <label
                                        for="filterHasta"
                                        class="form-label">

                                        Pedido hasta

                                    </label>

                                    <input
                                        type="date"
                                        class="form-control"
                                        id="filterHasta">

                                </div>


                                <!-- FECHA REQUERIDA -->
                                <div class="col-12 col-md-6 col-lg-3 col-xxl-2">

                                    <label
                                        for="filterFechaRequerida"
                                        class="form-label">

                                        Fecha requerida

                                    </label>

                                    <input
                                        type="date"
                                        class="form-control"
                                        id="filterFechaRequerida">

                                </div>


                                <!-- MES FACTURACIÓN -->
                                <div class="col-12 col-md-6 col-lg-3 col-xxl-2">

                                    <label
                                        for="filterMesFacturacion"
                                        class="form-label">

                                        Mes facturación

                                    </label>

                                    <input
                                        type="month"
                                        class="form-control"
                                        id="filterMesFacturacion">

                                </div>


                                <!-- BOTÓN BUSCAR -->
                                <div
                                    class="col-12 col-md-6 col-lg-3 col-xxl-2
                                           d-flex align-items-end">

                                    <button
                                        type="button"
                                        class="btn btn-primary w-100"
                                        id="btnAplicarFiltros">

                                        <i class="ri-search-line me-1"></i>

                                        Buscar

                                    </button>

                                </div>

                            </div>

                        </div>

                    </div>

                </div>

            </div>


            <!-- ============================================================
                 LISTADO DE PEDIDOS
            ============================================================= -->
            <div class="row">

                <div class="col-12">

                    <div class="card">

                        <div
                            class="card-header border-0 d-flex
                                   align-items-center justify-content-between
                                   flex-wrap gap-2">

                            <div>

                                <h5 class="card-title mb-1">
                                    Pedidos registrados
                                </h5>

                                <p class="text-muted mb-0 fs-12">

                                    Selecciona un pedido para consultar su detalle
                                    y comenzar su gestión.

                                </p>

                            </div>


                            <div>

                                <span
                                    class="badge bg-primary-subtle text-primary fs-12"
                                    id="badgeTotalRegistros">

                                    0 registros

                                </span>

                            </div>

                        </div>


                        <div class="card-body pt-0">

                            <div class="table-responsive">

                                <table
                                    class="table table-hover align-middle table-nowrap mb-0"
                                    id="tablaPedidos">

                                    <thead class="table-light">

                                        <tr>

                                            <th>
                                                Folio
                                            </th>

                                            <th>
                                                Distribuidor
                                            </th>

                                            <th>
                                                Fecha pedido
                                            </th>

                                            <th>
                                                Fecha requerida
                                            </th>

                                            <th class="text-center">
                                                Unidades
                                            </th>

                                            <th class="text-end">
                                                Total
                                            </th>

                                            <th class="text-center">
                                                Prioridad
                                            </th>

                                            <th class="text-center">
                                                Estatus
                                            </th>

                                            <th>
                                                Última actualización
                                            </th>

                                            <th class="text-end">
                                                Acciones
                                            </th>

                                        </tr>

                                    </thead>


                                    <tbody id="tbodyPedidos">

                                        <!-- ======================================
                                             LO LLENAREMOS DESDE index.js
                                        ======================================= -->

                                    </tbody>

                                </table>

                            </div>


                            <!-- ================================================
                                 ESTADO CARGANDO
                            ================================================= -->
                            <div
                                class="text-center py-5"
                                id="pedidosLoading"
                                style="display:none;">

                                <div
                                    class="spinner-border text-primary"
                                    role="status">

                                    <span class="visually-hidden">
                                        Cargando...
                                    </span>

                                </div>

                                <p class="text-muted mt-2 mb-0">
                                    Cargando pedidos...
                                </p>

                            </div>


                            <!-- ================================================
                                 SIN RESULTADOS
                            ================================================= -->
                            <div
                                class="text-center py-5"
                                id="pedidosSinResultados"
                                style="display:none;">

                                <div
                                    class="avatar-md mx-auto mb-3">

                                    <div
                                        class="avatar-title bg-light text-muted
                                               rounded-circle fs-1">

                                        <i class="ri-inbox-archive-line"></i>

                                    </div>

                                </div>

                                <h5 class="mb-1">
                                    No se encontraron pedidos
                                </h5>

                                <p class="text-muted mb-0">
                                    Intenta modificar los filtros de búsqueda.
                                </p>

                            </div>

                        </div>


                        <!-- ====================================================
                             PAGINACIÓN
                        ===================================================== -->
                        <div
                            class="card-footer"
                            id="footerPaginacionPedidos">

                            <div
                                class="d-flex justify-content-between
                                       align-items-center flex-wrap gap-2">

                                <div
                                    class="text-muted fs-13"
                                    id="infoPaginacionPedidos">

                                    Mostrando 0 registros

                                </div>


                                <nav aria-label="Paginación pedidos">

                                    <ul
                                        class="pagination pagination-sm mb-0"
                                        id="paginationPedidos">

                                    </ul>

                                </nav>

                            </div>

                        </div>

                    </div>

                </div>

            </div>

        </div>

    </div>


    <!-- ================================================================
         FOOTER
    ================================================================= -->
    <footer class="footer">

        <div class="container-fluid">

            <div class="row">

                <div class="col-sm-6">

                    <script>
                        document.write(new Date().getFullYear())
                    </script>

                    © LDR.

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

<?php footerAdmin($data); ?>