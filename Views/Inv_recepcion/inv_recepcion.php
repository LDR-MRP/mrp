<?php headerAdmin($data); ?>

<style>
    .rc-module { --rc-radius: 12px; }
    .rc-module .card { border-radius: var(--rc-radius); }

    /* KPIs */
    .rc-kpi { cursor: pointer; border: 1px solid transparent; transition: all .15s ease; }
    .rc-kpi:hover { transform: translateY(-2px); box-shadow: 0 4px 14px rgba(0,0,0,.08); }
    .rc-kpi.active { border-color: var(--vz-primary, #405189); box-shadow: 0 0 0 2px rgba(64,81,137,.15); }
    .rc-kpi .rc-kpi-icon { width: 44px; height: 44px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 22px; }
    .rc-kpi .rc-kpi-value { font-size: 24px; font-weight: 700; line-height: 1; }

    /* Lista de órdenes */
    .rc-filtros .btn { flex: 1; font-size: 12.5px; }
    .rc-lista { max-height: calc(100vh - 360px); min-height: 300px; overflow-y: auto; overflow-x: hidden; padding-right: 4px; }
    .rc-oc-item { cursor: pointer; border: 1px solid #e9ebec; border-left: 4px solid #ced4da; border-radius: 10px; padding: 10px 12px; margin-bottom: 8px; background: #fff; transition: all .15s ease; }
    .rc-oc-item:hover { box-shadow: 0 2px 10px rgba(0,0,0,.07); transform: translateX(2px); }
    .rc-oc-item.g-pendiente { border-left-color: #f7b84b; }
    .rc-oc-item.g-parcial { border-left-color: #299cdb; }
    .rc-oc-item.g-cerrada { border-left-color: #0ab39c; }
    .rc-oc-item.active { background: #f3f6ff; border-color: #405189; border-left-width: 4px; }
    .rc-oc-item .rc-oc-folio { font-weight: 700; font-size: 14px; }
    .rc-oc-item .progress { height: 6px; }

    /* Panel derecho */
    .rc-empty { padding: 70px 20px; text-align: center; color: #878a99; }
    .rc-empty i { font-size: 54px; opacity: .45; display: block; margin-bottom: 10px; }
    .rc-dato small { display: block; color: #878a99; font-size: 11.5px; text-transform: uppercase; letter-spacing: .3px; }
    .rc-dato strong { font-size: 14px; }
    .rc-progress-lg { height: 10px; border-radius: 10px; }

    .rc-scanner { background: linear-gradient(135deg, #405189 0%, #4b65b3 100%); color: #fff; }
    .rc-scanner .form-control { height: 48px; font-size: 16px; border: 0; }
    .rc-scanner .input-group-text { background: #fff; border: 0; font-size: 20px; color: #405189; }
    .rc-scanner small { color: rgba(255,255,255,.8); }

    .rc-tabla thead th { background: #f3f6f9; font-size: 12px; text-transform: uppercase; color: #495057; white-space: nowrap; position: sticky; top: 0; z-index: 1; }
    .rc-tabla td { vertical-align: middle; }
    .rc-tabla .rc-input-cant { width: 90px; text-align: center; font-weight: 600; }
    .rc-tabla .rc-input-txt { min-width: 110px; }
    .rc-tabla tr.rc-capturado { background: #f0fbf8; }
    .rc-tabla tr.rc-completo { opacity: .6; }
    .rc-tabla tr.rc-flash { animation: rcFlash 1.2s ease; }
    @keyframes rcFlash { 0% { background: #c9f2e9; } 100% { background: transparent; } }
    .rc-wrap-tabla { max-height: 520px; overflow: auto; }

    .rc-historial-grupo { border-left: 3px solid #405189; padding-left: 12px; margin-bottom: 14px; }

    .rc-savebar { position: sticky; bottom: 0; z-index: 10; background: #fff; border: 1px solid #e9ebec; border-radius: var(--rc-radius); box-shadow: 0 -4px 16px rgba(0,0,0,.06); padding: 12px 18px; }
</style>

<div id="contentAjax"></div>
<div class="main-content">
    <div class="page-content">
        <div class="container-fluid rc-module">

            <!-- Título -->
            <div class="row">
                <div class="col-12">
                    <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                        <h5 class="mb-sm-0"><?= $data['page_title'] ?></h5>
                        <div class="page-title-right">
                            <ol class="breadcrumb m-0">
                                <li class="breadcrumb-item"><a href="javascript:void(0);">MRP</a></li>
                                <li class="breadcrumb-item active"><?= $data['page_tag'] ?></li>
                            </ol>
                        </div>
                    </div>
                </div>
            </div>

            <!-- KPIs -->
            <div class="row g-3 mb-3">
                <div class="col-md-4">
                    <div class="card rc-kpi mb-0 active" data-grupo="pendiente">
                        <div class="card-body d-flex align-items-center gap-3">
                            <div class="rc-kpi-icon bg-warning-subtle text-warning"><i class="ri-truck-line"></i></div>
                            <div>
                                <div class="text-muted small">Por recibir (en tránsito)</div>
                                <div class="rc-kpi-value" id="kpiPendiente">0</div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card rc-kpi mb-0" data-grupo="parcial">
                        <div class="card-body d-flex align-items-center gap-3">
                            <div class="rc-kpi-icon bg-info-subtle text-info"><i class="ri-loader-2-line"></i></div>
                            <div>
                                <div class="text-muted small">Recibidas parcialmente</div>
                                <div class="rc-kpi-value" id="kpiParcial">0</div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card rc-kpi mb-0" data-grupo="cerrada">
                        <div class="card-body d-flex align-items-center gap-3">
                            <div class="rc-kpi-icon bg-success-subtle text-success"><i class="ri-checkbox-circle-line"></i></div>
                            <div>
                                <div class="text-muted small">Recepciones cerradas</div>
                                <div class="rc-kpi-value" id="kpiCerrada">0</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row g-3">
                <!-- ============ COLUMNA IZQUIERDA: ÓRDENES ============ -->
                <div class="col-xl-4 col-lg-5">
                    <div class="card h-100 mb-0">
                        <div class="card-header d-flex align-items-center justify-content-between">
                            <h6 class="mb-0"><i class="ri-file-list-3-line me-1"></i> Órdenes de compra</h6>
                            <button class="btn btn-sm btn-soft-secondary" id="btnRefrescar" title="Actualizar">
                                <i class="ri-refresh-line"></i>
                            </button>
                        </div>
                        <div class="card-body">
                            <div class="search-box mb-2">
                                <input type="text" id="buscarRecepcion" class="form-control" placeholder="Buscar folio, proveedor o almacén...">
                            </div>

                            <div class="btn-group w-100 mb-3 rc-filtros" role="group">
                                <button type="button" class="btn btn-outline-warning active" data-grupo="pendiente">
                                    Por recibir <span class="badge bg-warning ms-1" id="tabPendiente">0</span>
                                </button>
                                <button type="button" class="btn btn-outline-info" data-grupo="parcial">
                                    Parciales <span class="badge bg-info ms-1" id="tabParcial">0</span>
                                </button>
                                <button type="button" class="btn btn-outline-success" data-grupo="cerrada">
                                    Cerradas <span class="badge bg-success ms-1" id="tabCerrada">0</span>
                                </button>
                            </div>

                            <div class="rc-lista" id="listaOrdenes">
                                <div class="rc-empty py-5"><span class="spinner-border spinner-border-sm"></span> Cargando...</div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ============ COLUMNA DERECHA: RECEPCIÓN ============ -->
                <div class="col-xl-8 col-lg-7">

                    <!-- Estado vacío -->
                    <div class="card mb-0" id="panelVacio">
                        <div class="rc-empty">
                            <i class="ri-inbox-archive-line"></i>
                            <h5 class="text-body">Selecciona una orden de compra</h5>
                            <p class="mb-0">Elige una OC de la lista para ver sus materiales y registrar la recepción.<br>
                                Las órdenes aparecen aquí en cuanto Compras las marca como <strong>En tránsito</strong>.</p>
                        </div>
                    </div>

                    <div id="panelRecepcion" class="d-none">

                        <!-- Encabezado OC -->
                        <div class="card mb-3">
                            <div class="card-body">
                                <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-3">
                                    <div>
                                        <h4 class="mb-1" id="hdrFolio">OC-</h4>
                                        <div class="text-muted" id="hdrProveedor"></div>
                                    </div>
                                    <div class="text-end">
                                        <div class="mb-1"><small class="text-muted me-1">Estatus OC</small><span id="hdrEstatusOC"></span></div>
                                        <div><small class="text-muted me-1">Recepción</small><span id="hdrEstatusRC"></span></div>
                                    </div>
                                </div>

                                <div class="row g-3 mb-3">
                                    <div class="col-sm-4 rc-dato"><small>Fecha OC</small><strong id="hdrFecha">-</strong></div>
                                    <div class="col-sm-4 rc-dato"><small>Almacén destino</small><strong id="hdrAlmacen">-</strong></div>
                                    <div class="col-sm-4 rc-dato"><small>Folio recepción</small><strong id="hdrFolioRC">-</strong></div>
                                </div>

                                <div class="d-flex justify-content-between small mb-1">
                                    <span class="text-muted">Avance de recepción</span>
                                    <span><strong id="hdrAvanceTxt">0 / 0</strong> <span class="text-muted" id="hdrAvancePct">(0%)</span></span>
                                </div>
                                <div class="progress rc-progress-lg">
                                    <div class="progress-bar bg-success" id="hdrAvanceBar" style="width:0%"></div>
                                </div>
                            </div>
                        </div>

                        <!-- Aviso de recepción cerrada -->
                        <div class="alert alert-success d-none align-items-center gap-2" id="avisoCerrada">
                            <i class="ri-lock-line fs-18"></i>
                            <div>Esta recepción está <strong>cerrada</strong>: todo el material fue recibido y la OC quedó cerrada. Solo es consulta.</div>
                        </div>

                        <!-- Escáner -->
                        <div class="card rc-scanner mb-3" id="cardScanner">
                            <div class="card-body">
                                <div class="row g-2 align-items-center">
                                    <div class="col-lg-7">
                                        <div class="input-group">
                                            <span class="input-group-text"><i class="ri-barcode-line"></i></span>
                                            <input type="text" id="scannerInput" class="form-control" placeholder="Escanea o escribe el código y presiona Enter" autocomplete="off">
                                        </div>
                                        <small>Cada escaneo suma a la columna <strong>"A recibir"</strong> del producto.</small>
                                    </div>
                                    <div class="col-lg-5 d-flex gap-2 justify-content-lg-end">
                                        <button type="button" class="btn btn-outline-light" id="btnLimpiarCaptura" title="Limpiar captura">
                                            <i class="ri-eraser-line"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Tabla de materiales -->
                        <div class="card mb-3">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h6 class="mb-0"><i class="ri-list-check-2 me-1"></i> Materiales de la orden</h6>
                                <small class="text-muted" id="txtPartidas"></small>
                            </div>
                            <div class="card-body p-0">
                                <div class="table-responsive rc-wrap-tabla">
                                    <table class="table table-sm table-hover mb-0 rc-tabla">
                                        <thead>
                                            <tr>
                                                <th>#</th>
                                                <th>Producto</th>
                                                <th class="text-center">Solicitado</th>
                                                <th class="text-center">Recibido</th>
                                                <th class="text-center">Pendiente</th>
                                                <th class="text-center">A recibir</th>
                                                <th>Lote</th>
                                                <th>Observaciones</th>
                                                <th class="text-center">Fotos</th>
                                                <th class="text-center">Estado</th>
                                            </tr>
                                        </thead>
                                        <tbody id="detalleRecepcion"></tbody>
                                    </table>
                                </div>
                            </div>
                        </div>

                        <!-- Observaciones + documentos -->
                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <div class="card h-100 mb-0">
                                    <div class="card-header"><h6 class="mb-0"><i class="ri-chat-3-line me-1"></i> Observaciones generales</h6></div>
                                    <div class="card-body">
                                        <textarea id="observacionesRecepcion" class="form-control" rows="4" placeholder="Comentarios de la recepción (remisión, estado del empaque, faltantes...)"></textarea>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="card h-100 mb-0">
                                    <div class="card-header"><h6 class="mb-0"><i class="ri-attachment-2 me-1"></i> Documentos (remisión, factura, etc.)</h6></div>
                                    <div class="card-body">
                                        <div id="contenedorDocumentos">
                                            <div class="rc-doc-item mb-2">
                                                <input type="file" class="form-control rc-doc"
                                                    accept=".pdf,.jpg,.jpeg,.png,.webp,.xml,.xls,.xlsx,.doc,.docx,.csv,.txt">
                                            </div>
                                        </div>
                                        <button type="button" class="btn btn-outline-primary btn-sm mb-2" id="btnAgregarDocumento">
                                            <i class="ri-add-line"></i> Agregar documento
                                        </button>
                                        <div id="listaDocumentosExistentes" class="list-group list-group-flush small"></div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Historial -->
                        <div class="card mb-3">
                            <div class="card-header d-flex justify-content-between align-items-center" role="button"
                                data-bs-toggle="collapse" data-bs-target="#collapseHistorial">
                                <h6 class="mb-0"><i class="ri-history-line me-1"></i> Historial de recepciones <span class="badge bg-secondary-subtle text-secondary ms-1" id="badgeHistorial">0</span></h6>
                                <i class="ri-arrow-down-s-line"></i>
                            </div>
                            <div class="collapse" id="collapseHistorial">
                                <div class="card-body" id="historialRecepcion"></div>
                            </div>
                        </div>

                        <!-- Barra de guardado -->
                        <div class="rc-savebar d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3" id="saveBar">
                            <div>
                                <div class="fw-semibold" id="resumenCaptura">Sin cantidades capturadas</div>
                                <small class="text-muted" id="resumenResultado">Captura cantidades en "A recibir" o escanea productos.</small>
                            </div>
                            <button class="btn btn-success btn-lg" id="btnGuardarRecepcion" disabled>
                                <i class="ri-check-double-line me-1"></i> Registrar recepción
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal evidencias -->
<div class="modal fade" id="modalEvidencias" tabindex="-1">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="ri-image-line me-1"></i> Fotografías del producto <span class="text-muted fs-14" id="modalEvidenciasProducto"></span></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="row" id="galeriaProducto"></div>
            </div>
        </div>
    </div>
</div>

<?php footerAdmin($data); ?>
