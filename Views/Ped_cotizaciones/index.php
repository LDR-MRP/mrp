<?php headerAdmin($data); ?>

<div id="contentAjax"></div>

<div class="main-content">

    <div class="page-content">

        <div class="container-fluid">

            <!-- =========================================================
                 ENCABEZADO
            ========================================================== -->

            <div class="row mb-3">

                <div class="col-12">

                    <div
                        class="d-flex justify-content-between align-items-center flex-wrap gap-3">

                        <div class="d-flex align-items-center">

                            <div
                                class="avatar-sm rounded-circle bg-primary-subtle text-primary d-flex align-items-center justify-content-center me-3">

                                <i class="ri-file-list-3-line fs-2"></i>

                            </div>

                            <div>

                                <h3 class="mb-0 fw-bold">
                                    Cotizaciones
                                </h3>

                                <small class="text-muted">

                                    Administra, consulta y da seguimiento
                                    a las cotizaciones comerciales.

                                </small>

                            </div>

                        </div>


                        <div>

                            <a
                                href="<?= base_url(); ?>/ped_cotizaciones/nueva"
                                class="btn btn-primary">

                                <i class="ri-add-line align-bottom me-1"></i>

                                Nueva cotización

                            </a>

                        </div>

                    </div>

                </div>

            </div>


            <!-- =========================================================
                 DASHBOARD
            ========================================================== -->

            <div class="row g-3 mb-3">


                <!-- TOTAL -->

                <div class="col-xl-3 col-md-6">

                    <div class="card card-animate h-100">

                        <div class="card-body">

                            <div class="d-flex align-items-center">

                                <div class="flex-grow-1">

                                    <p
                                        class="text-uppercase fw-medium text-muted mb-0">

                                        Cotizaciones

                                    </p>

                                </div>

                                <div class="flex-shrink-0">

                                    <div
                                        class="avatar-sm rounded-circle bg-primary-subtle d-flex align-items-center justify-content-center">

                                        <i
                                            class="ri-file-list-3-line fs-4 text-primary">
                                        </i>

                                    </div>

                                </div>

                            </div>


                            <div class="d-flex align-items-end justify-content-between mt-3">

                                <div>

                                    <h3
                                        class="fs-22 fw-semibold mb-0"
                                        id="indicadorTotal">

                                        0

                                    </h3>

                                    <span class="text-muted fs-12">
                                        Total registradas
                                    </span>

                                </div>

                            </div>

                        </div>

                    </div>

                </div>


                <!-- PENDIENTES -->

                <div class="col-xl-3 col-md-6">

                    <div class="card card-animate h-100">

                        <div class="card-body">

                            <div class="d-flex align-items-center">

                                <div class="flex-grow-1">

                                    <p
                                        class="text-uppercase fw-medium text-muted mb-0">

                                        Pendientes

                                    </p>

                                </div>

                                <div class="flex-shrink-0">

                                    <div
                                        class="avatar-sm rounded-circle bg-warning-subtle d-flex align-items-center justify-content-center">

                                        <i
                                            class="ri-time-line fs-4 text-warning">
                                        </i>

                                    </div>

                                </div>

                            </div>


                            <div class="mt-3">

                                <h3
                                    class="fs-22 fw-semibold mb-0"
                                    id="indicadorPendientes">

                                    0

                                </h3>

                                <span class="text-muted fs-12">

                                    Esperando respuesta

                                </span>

                            </div>

                        </div>

                    </div>

                </div>


                <!-- EN AJUSTE -->

                <div class="col-xl-3 col-md-6">

                    <div class="card card-animate h-100">

                        <div class="card-body">

                            <div class="d-flex align-items-center">

                                <div class="flex-grow-1">

                                    <p
                                        class="text-uppercase fw-medium text-muted mb-0">

                                        En ajuste

                                    </p>

                                </div>

                                <div class="flex-shrink-0">

                                    <div
                                        class="avatar-sm rounded-circle bg-info-subtle d-flex align-items-center justify-content-center">

                                        <i
                                            class="ri-edit-2-line fs-4 text-info">
                                        </i>

                                    </div>

                                </div>

                            </div>


                            <div class="mt-3">

                                <h3
                                    class="fs-22 fw-semibold mb-0"
                                    id="indicadorAjuste">

                                    0

                                </h3>

                                <span class="text-muted fs-12">

                                    Requieren atención

                                </span>

                            </div>

                        </div>

                    </div>

                </div>


                <!-- ACEPTADAS -->

                <div class="col-xl-3 col-md-6">

                    <div class="card card-animate h-100">

                        <div class="card-body">

                            <div class="d-flex align-items-center">

                                <div class="flex-grow-1">

                                    <p
                                        class="text-uppercase fw-medium text-muted mb-0">

                                        Aceptadas

                                    </p>

                                </div>

                                <div class="flex-shrink-0">

                                    <div
                                        class="avatar-sm rounded-circle bg-success-subtle d-flex align-items-center justify-content-center">

                                        <i
                                            class="ri-checkbox-circle-line fs-4 text-success">
                                        </i>

                                    </div>

                                </div>

                            </div>


                            <div class="mt-3">

                                <h3
                                    class="fs-22 fw-semibold mb-0"
                                    id="indicadorAceptadas">

                                    0

                                </h3>

                                <span class="text-muted fs-12">

                                    Cotizaciones aceptadas

                                </span>

                            </div>

                        </div>

                    </div>

                </div>

            </div>


            <!-- =========================================================
                 SEGUNDA FILA INDICADORES
            ========================================================== -->

            <div class="row g-3 mb-3">


                <div class="col-xl-4 col-md-6">

                    <div class="card h-100">

                        <div class="card-body">

                            <div class="d-flex justify-content-between">

                                <div>

                                    <p class="text-muted mb-1">
                                        Valor cotizado
                                    </p>

                                    <h4
                                        class="mb-0"
                                        id="indicadorValorCotizado">

                                        $0.00

                                    </h4>

                                </div>

                                <div
                                    class="avatar-sm rounded-circle bg-primary-subtle d-flex align-items-center justify-content-center">

                                    <i
                                        class="ri-money-dollar-circle-line fs-4 text-primary">
                                    </i>

                                </div>

                            </div>

                        </div>

                    </div>

                </div>


                <div class="col-xl-4 col-md-6">

                    <div class="card h-100">

                        <div class="card-body">

                            <div class="d-flex justify-content-between">

                                <div>

                                    <p class="text-muted mb-1">
                                        Valor aceptado
                                    </p>

                                    <h4
                                        class="mb-0"
                                        id="indicadorValorAceptado">

                                        $0.00

                                    </h4>

                                </div>

                                <div
                                    class="avatar-sm rounded-circle bg-success-subtle d-flex align-items-center justify-content-center">

                                    <i
                                        class="ri-hand-coin-line fs-4 text-success">
                                    </i>

                                </div>

                            </div>

                        </div>

                    </div>

                </div>


                <div class="col-xl-4 col-md-12">

                    <div class="card h-100">

                        <div class="card-body">

                            <div class="d-flex justify-content-between">

                                <div>

                                    <p class="text-muted mb-1">
                                        Rechazadas
                                    </p>

                                    <h4
                                        class="mb-0"
                                        id="indicadorRechazadas">

                                        0

                                    </h4>

                                </div>

                                <div
                                    class="avatar-sm rounded-circle bg-danger-subtle d-flex align-items-center justify-content-center">

                                    <i
                                        class="ri-close-circle-line fs-4 text-danger">
                                    </i>

                                </div>

                            </div>

                        </div>

                    </div>

                </div>

            </div>


            <!-- =========================================================
                 FILTROS
            ========================================================== -->

            <div class="row">

                <div class="col-12">

                    <div class="card">

                        <div class="card-header">

                            <div
                                class="d-flex align-items-center justify-content-between">

                                <h5 class="card-title mb-0">

                                    <i
                                        class="ri-filter-3-line me-1">
                                    </i>

                                    Filtros de búsqueda

                                </h5>

                                <button
                                    type="button"
                                    class="btn btn-sm btn-ghost-secondary"
                                    id="btnLimpiarFiltros">

                                    <i class="ri-filter-off-line me-1"></i>

                                    Limpiar

                                </button>

                            </div>

                        </div>


                        <div class="card-body">

                            <div class="row g-3 align-items-end">


                                <!-- BUSCAR -->

                                <div class="col-12 col-md-6 col-xl-3">

                                    <label
                                        class="form-label small text-muted">

                                        Buscar

                                    </label>

                                    <div class="input-group">

                                        <span class="input-group-text">

                                            <i class="ri-search-line"></i>

                                        </span>

                                        <input
                                            type="text"
                                            class="form-control"
                                            id="filterSearch"
                                            placeholder="Folio, distribuidor...">

                                    </div>

                                </div>


                                <!-- DESDE -->

                                <div class="col-6 col-md-3 col-xl-2">

                                    <label
                                        class="form-label small text-muted">

                                        Desde

                                    </label>

                                    <input
                                        type="date"
                                        class="form-control"
                                        id="filterDesde">

                                </div>


                                <!-- HASTA -->

                                <div class="col-6 col-md-3 col-xl-2">

                                    <label
                                        class="form-label small text-muted">

                                        Hasta

                                    </label>

                                    <input
                                        type="date"
                                        class="form-control"
                                        id="filterHasta">

                                </div>


                                <!-- TIPO -->

                                <div class="col-6 col-md-4 col-xl-2">

                                    <label
                                        class="form-label small text-muted">

                                        Tipo

                                    </label>

                                    <select
                                        class="form-select"
                                        id="filterTipo">

                                        <option value="">
                                            Todos
                                        </option>

                                        <option value="DIRECTA">
                                            Directa
                                        </option>

                                        <option value="PEDIDO">
                                            Sobre pedido
                                        </option>

                                    </select>

                                </div>


                                <!-- ESTATUS -->

                                <div class="col-6 col-md-4 col-xl-2">

                                    <label
                                        class="form-label small text-muted">

                                        Estatus

                                    </label>

                                    <select
                                        class="form-select"
                                        id="filterEstatus">

                                        <option value="">
                                            Todos
                                        </option>

                                        <option value="BORRADOR">
                                            Borrador
                                        </option>

                                        <option value="PENDIENTE_RESPUESTA">
                                            Pendiente respuesta
                                        </option>

                                        <option value="EN_AJUSTE">
                                            En ajuste
                                        </option>

                                        <option value="ACEPTADA">
                                            Aceptada
                                        </option>

                                        <option value="RECHAZADA">
                                            Rechazada
                                        </option>

                                        <option value="VENCIDA">
                                            Vencida
                                        </option>

                                        <option value="CANCELADA">
                                            Cancelada
                                        </option>

                                    </select>

                                </div>


                                <!-- REFRESCAR -->

                                <div class="col-12 col-md-4 col-xl-1">

                                    <button
                                        type="button"
                                        class="btn btn-outline-secondary w-100"
                                        id="btnRefrescarListado"
                                        title="Actualizar listado">

                                        <i class="ri-refresh-line"></i>

                                    </button>

                                </div>

                            </div>

                        </div>

                    </div>

                </div>

            </div>


            <!-- =========================================================
                 TABLA
            ========================================================== -->

            <div class="row">

                <div class="col-12">

                    <div class="card">

                        <div class="card-header">

                            <div
                                class="d-flex justify-content-between align-items-center">

                                <div>

                                    <h5 class="card-title mb-1">

                                        Cotizaciones registradas

                                    </h5>

                                    <small class="text-muted">

                                        Consulta y administra las propuestas
                                        comerciales registradas.

                                    </small>

                                </div>

                                <span
                                    class="badge bg-primary-subtle text-primary"
                                    id="contadorRegistros">

                                    0 registros

                                </span>

                            </div>

                        </div>


                        <div class="card-body">

                            <div class="table-responsive">

                                <table
                                    class="table table-hover align-middle table-nowrap mb-0"
                                    id="tablaCotizaciones">

                                    <thead class="table-light">

                                        <tr>

                                            <th>
                                                Folio
                                            </th>

                                            <th>
                                                Distribuidor
                                            </th>

                                            <th>
                                                Origen
                                            </th>

                                            <th class="text-center">
                                                Versión
                                            </th>

                                            <th>
                                                Fecha
                                            </th>

                                            <th>
                                                Vigencia
                                            </th>

                                            <th class="text-end">
                                                Total
                                            </th>

                                            <th class="text-center">
                                                Estatus
                                            </th>

                                            <th
                                                class="text-end"
                                                style="width: 170px;">

                                                Acciones

                                            </th>

                                        </tr>

                                    </thead>

                                    <tbody id="tbodyCotizaciones">

                                        <tr>

                                            <td
                                                colspan="9"
                                                class="text-center py-5 text-muted">

                                                <div
                                                    class="spinner-border spinner-border-sm me-2"
                                                    role="status">
                                                </div>

                                                Cargando cotizaciones...

                                            </td>

                                        </tr>

                                    </tbody>

                                </table>

                            </div>

                        </div>

                    </div>

                </div>

            </div>

        </div>

    </div>


    <footer class="footer">

        <div class="container-fluid">

            <div class="row">

                <div class="col-sm-6">

                    <script>
                        document.write(
                            new Date().getFullYear()
                        );
                    </script>

                    © LDR.

                </div>

                <div class="col-sm-6">

                    <div
                        class="text-sm-end d-none d-sm-block">

                        LDR Solutions · MRP

                    </div>

                </div>

            </div>

        </div>

    </footer>

</div>

<?php footerAdmin($data); ?>