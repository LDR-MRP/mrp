<?php headerAdmin($data); ?>

<div id="contentAjax"></div>

<div class="main-content">

    <div class="page-content">

        <div class="container-fluid">

            <div class="row mb-4">

                <div class="col-lg-8">

                    <div class="d-flex align-items-center">

                        <div class="avatar-lg flex-shrink-0">

                            <div class="avatar-title rounded-circle bg-primary text-white fs-1">
                                <i class="ri-key-2-line"></i>
                            </div>

                        </div>

                        <div class="ms-3">

                            <h1 class="mb-1">
                                Control de Llaves
                            </h1>

                            <p class="text-muted mb-0">
                                Préstamo y devolución de llaves a colaboradores.
                            </p>

                        </div>

                    </div>

                </div>

            </div>

            <div class="row">

                <div class="col-md">

                    <div class="card">
                        <div class="card-body text-center">

                            <h6 class="text-muted">
                                Total Llaves
                            </h6>

                            <h2 id="lblTotalLlaves">0</h2>

                        </div>
                    </div>

                </div>

                <div class="col-md">

                    <div class="card">
                        <div class="card-body text-center">

                            <h6 class="text-muted">
                                Disponibles
                            </h6>

                            <h2 id="lblDisponibles">0</h2>

                        </div>
                    </div>

                </div>

                <div class="col-md">

                    <div class="card">
                        <div class="card-body text-center">

                            <h6 class="text-muted">
                                Prestadas
                            </h6>

                            <h2 id="lblPrestadas">0</h2>

                            <small class="text-muted">A colaborador</small>

                        </div>
                    </div>

                </div>

                <div class="col-md">

                    <div class="card">
                        <div class="card-body text-center">

                            <h6 class="text-muted">
                                En Tránsito
                            </h6>

                            <h2 id="lblEnTransito">0</h2>

                            <small class="text-muted">Traslado sin recibir</small>

                        </div>
                    </div>

                </div>

                <div class="col-md">

                    <div class="card">
                        <div class="card-body text-center">

                            <h6 class="text-muted">
                                Vencidas
                            </h6>

                            <h2 id="lblVencidas">0</h2>

                        </div>
                    </div>

                </div>

            </div>

            <div class="card">

                <div class="card-body">

                    <div class="d-flex gap-2">

                        <button
                            class="btn btn-success"
                            data-bs-toggle="modal"
                            data-bs-target="#modalEntrega">

                            <i class="ri-key-fill me-1"></i>
                            Nueva Entrega

                        </button>

                        <button
                            type="button"
                            class="btn btn-primary"
                            id="btnEscanearLlave">

                            <i class="ri-qr-scan-2-line me-1"></i>
                            Escanear Llave

                        </button>

                    </div>

                </div>

            </div>

            <div class="card">

                <div class="card-header">

                    <ul class="nav nav-tabs card-header-tabs" id="llavesTabs" role="tablist">

                        <li class="nav-item" role="presentation">
                            <button
                                class="nav-link active"
                                id="tabBitacoraBtn"
                                data-bs-toggle="tab"
                                data-bs-target="#tabBitacora"
                                type="button"
                                role="tab"
                                aria-controls="tabBitacora"
                                aria-selected="true">
                                Bitácora de Llaves
                            </button>
                        </li>

                        <li class="nav-item" role="presentation">
                            <button
                                class="nav-link"
                                id="tabHistorialBtn"
                                data-bs-toggle="tab"
                                data-bs-target="#tabHistorial"
                                type="button"
                                role="tab"
                                aria-controls="tabHistorial"
                                aria-selected="false">
                                Historial de Traslados
                            </button>
                        </li>

                    </ul>

                </div>

                <div class="card-body">

                    <div class="tab-content" id="llavesTabsContent">

                        <!-- ============================================= -->
                        <!-- BITÁCORA DE LLAVES -->
                        <!-- ============================================= -->

                        <div
                            class="tab-pane fade show active"
                            id="tabBitacora"
                            role="tabpanel"
                            aria-labelledby="tabBitacoraBtn">

                            <div class="alert alert-warning mb-4">

                                <i class="ri-information-line me-2"></i>

                                La entrega de llaves genera responsabilidad sobre los accesos asignados
                                hasta que sean devueltos y registrados en el sistema.

                            </div>

                            <div class="table-responsive">

                                <table
                                    class="table table-hover align-middle w-100"
                                    id="tableLlaves">

                                    <thead class="table-light">

                                        <tr>

                                            <th>Unidad</th>
                                            <th>VIN</th>
                                            <th>Tipo</th>
                                            <th>Almacén</th>
                                            <th>Responsable</th>
                                            <th>Asignó</th>
                                            <th>Entrega</th>
                                            <th>Devolución prevista</th>
                                            <th>Devolución real</th>
                                            <th>Estatus</th>
                                            <th>Acciones</th>

                                        </tr>

                                    </thead>

                                    <tbody></tbody>

                                </table>

                            </div>

                        </div>

                        <!-- ============================================= -->
                        <!-- HISTORIAL DE LLAVES EN TRASLADO -->
                        <!-- ============================================= -->

                        <div
                            class="tab-pane fade"
                            id="tabHistorial"
                            role="tabpanel"
                            aria-labelledby="tabHistorialBtn">

                            <div class="alert alert-info mb-4">

                                <i class="ri-information-line me-2"></i>

                                Aquí se ven TODAS las llaves que han salido en un traslado entre
                                almacenes, incluyendo las que ya se recibieron en destino. Sirve para saber a qué almacén se fue una llave y cuál
                                fue su origen.

                            </div>

                            <div class="table-responsive">

                                <table
                                    class="table table-hover align-middle w-100"
                                    id="tableHistorialTraslados">

                                    <thead class="table-light">

                                        <tr>

                                            <th>VIN</th>
                                            <th>Modelo</th>
                                            <th>Tipo</th>
                                            <th>Origen</th>
                                            <th>Destino</th>
                                            <th>Salida</th>
                                            <th>Último movimiento</th>
                                            <th>Asigno</th>
                                            <th>Estatus</th>

                                        </tr>

                                    </thead>

                                    <tbody></tbody>

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


<div
    class="modal fade"
    id="modalEntrega">

    <div class="modal-dialog modal-lg modal-dialog-centered">

        <div class="modal-content">

            <div class="modal-header">

                <h5 class="modal-title" id="modalEntregaLabel">

                    <i class="ri-key-2-line me-2"></i>
                    Nueva Entrega de Llave

                </h5>

                <button
                    type="button"
                    class="btn-close"
                    data-bs-dismiss="modal"
                    aria-label="Cerrar">
                </button>

            </div>

            <form id="formEntregaLlave">

                <div class="modal-body">

                    <div class="alert alert-info">

                        <i class="ri-information-line me-2"></i>

                        Registre el préstamo temporal de la llave de una unidad a un colaborador.
                        Si la llave ya está entregada en un traslado u otro préstamo activo, el
                        sistema no permitirá registrar una nueva entrega hasta que sea devuelta.

                    </div>

                    <div class="row">

                        <!-- UNIDAD -->
                        <div class="col-md-6 mb-3">

                            <label class="form-label">
                                Unidad
                                <span class="text-danger">*</span>
                            </label>

                            <select
                                class="form-select"
                                id="id_unidad_llave"
                                name="id_unidad"
                                required>

                                <option value="">
                                    Seleccione una unidad...
                                </option>

                            </select>

                        </div>

                        <!-- TIPO LLAVE -->
                        <div class="col-md-6 mb-3">

                            <label class="form-label">
                                Tipo de llave
                                <span class="text-danger">*</span>
                            </label>

                            <select
                                class="form-select"
                                id="tipo_llave"
                                name="tipo_llave"
                                required>

                                <option value="">
                                    Seleccione...
                                </option>

                                <option value="principal">
                                    Principal
                                </option>

                                <option value="duplicado">
                                    Duplicado
                                </option>

                            </select>

                        </div>

                        <!-- RESPONSABLE -->
                        <div class="col-md-6 mb-3">

                            <label class="form-label">
                                Responsable (colaborador)
                                <span class="text-danger">*</span>
                            </label>

                            <select
                                class="form-select"
                                id="id_responsable_llave"
                                name="id_responsable"
                                required>

                                <option value="">
                                    Seleccione responsable...
                                </option>

                            </select>

                            <input
                                type="text"
                                class="form-control form-control-sm mt-1"
                                id="scanResponsableLlave"
                                placeholder="O escanee el gafete del colaborador..."
                                autocomplete="off">

                        </div>

                        <!-- QUIÉN PRESTA LA LLAVE -->
                        <div class="col-md-6 mb-3">

                            <label class="form-label">
                                ¿Quién presta la llave?
                                <span class="text-danger">*</span>
                            </label>

                            <select
                                class="form-select"
                                id="id_entrega_por_llave"
                                name="entregado_por"
                                required>

                                <option value="">
                                    Seleccione colaborador...
                                </option>

                            </select>

                            <input
                                type="text"
                                class="form-control form-control-sm mt-1"
                                id="scanEntregaPorLlave"
                                placeholder="O escanee el gafete de quién presta..."
                                autocomplete="off">

                        </div>

                        <!-- FECHA DEVOLUCION -->
                        <div class="col-md-6 mb-3">

                            <label class="form-label">
                                Fecha prevista de devolución
                            </label>

                            <input
                                type="date"
                                class="form-control"
                                id="fecha_devolucion"
                                name="fecha_devolucion">

                        </div>

                        <!-- OBSERVACIONES -->
                        <div class="col-md-12 mb-3">

                            <label class="form-label">
                                Observaciones
                            </label>

                            <textarea
                                class="form-control"
                                id="observaciones_llave"
                                name="observaciones"
                                rows="3"
                                placeholder="Ingrese alguna observación..."></textarea>

                        </div>

                    </div>

                </div>

                <div class="modal-footer">

                    <button
                        type="button"
                        class="btn btn-light"
                        data-bs-dismiss="modal">

                        Cancelar

                    </button>

                    <button
                        type="submit"
                        class="btn btn-success">

                        <i class="ri-check-line me-1"></i>

                        Registrar Entrega

                    </button>

                </div>

            </form>

        </div>

    </div>

</div>

<div
    class="modal fade"
    id="modalDevolucion">

    <div class="modal-dialog modal-dialog-centered">

        <div class="modal-content">

            <div class="modal-header">

                <h5 class="modal-title">

                    <i class="ri-arrow-go-back-line me-2"></i>
                    Registrar Devolución

                </h5>

                <button
                    type="button"
                    class="btn-close"
                    data-bs-dismiss="modal"
                    aria-label="Cerrar">
                </button>

            </div>

            <form id="formDevolucionLlave">

                <div class="modal-body">

                    <p class="mb-3">
                        Unidad: <strong id="lblDevolucionUnidad"></strong><br>
                        Prestada a: <strong id="lblDevolucionResponsable"></strong>
                    </p>

                    <input type="hidden" id="idmovimiento_devolucion" name="idmovimiento">

                    <div class="mb-3">

                        <label class="form-label">
                            ¿Quién recibe la llave?
                            <span class="text-danger">*</span>
                        </label>

                        <select
                            class="form-select"
                            id="responsable_recibe"
                            name="responsable_recibe"
                            required>

                            <option value="">
                                Seleccione colaborador...
                            </option>

                        </select>

                    </div>

                    <div class="mb-3">

                        <label class="form-label">
                            Observaciones
                        </label>

                        <textarea
                            class="form-control"
                            id="observaciones_devolucion"
                            name="observaciones"
                            rows="3"
                            placeholder="Ingrese alguna observación..."></textarea>

                    </div>

                </div>

                <div class="modal-footer">

                    <button
                        type="button"
                        class="btn btn-light"
                        data-bs-dismiss="modal">

                        Cancelar

                    </button>

                    <button
                        type="submit"
                        class="btn btn-warning">

                        <i class="ri-check-line me-1"></i>

                        Registrar Devolución

                    </button>

                </div>

            </form>

        </div>

    </div>

</div>

<!-- =====================================================
     ESCÁNER DE LLAVE (lector de pistola / código de barras)

     El lector funciona como teclado: al disparar sobre el
     código, "escribe" el texto en el campo con foco y termina
     con Enter. Por eso aquí no se usa cámara, solo un input
     que se mantiene enfocado mientras el modal está abierto.

     Según lo que se detecte para ese VIN, abre directo la
     Devolución (si tiene préstamo activo) o la Nueva Entrega
     con la unidad ya seleccionada (si está disponible).
===================================================== -->
<div
    class="modal fade"
    id="modalScanner"
    data-bs-backdrop="static">

    <div class="modal-dialog modal-dialog-centered">

        <div class="modal-content">

            <div class="modal-header">

                <h5 class="modal-title">
                    <i class="ri-qr-scan-2-line me-2"></i>
                    Escanear Llave
                </h5>

                <button
                    type="button"
                    class="btn-close"
                    data-bs-dismiss="modal"
                    aria-label="Cerrar">
                </button>

            </div>

            <div class="modal-body">

                <p class="text-muted small mb-2">
                    Dispare el lector sobre el código de la llave. No haga
                    clic fuera de este campo mientras escanea.
                </p>

                <input
                    type="text"
                    class="form-control form-control-lg text-center"
                    id="inputEscanerLlave"
                    placeholder="Esperando escaneo..."
                    autocomplete="off">

            </div>

        </div>

    </div>

</div>

<?php footerAdmin($data); ?>
