<?php
    headerAdmin($data);
    getModal("modalPartidas", $data);
?>
<div id="contentAjax"></div>
<div class="main-content">

    <div class="page-content">
        <div class="container-fluid">

            <!-- start page title -->
            <div class="row">
                <div class="col-12">
                    <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                        <h4 class="mb-sm-0"><?= $data['page_title'] ?></h4>

                        <div class="page-title-right">
                            <ol class="breadcrumb m-0">
                                <li class="breadcrumb-item"><a href="javascript: void(0);">MRP</a></li>
                                <li class="breadcrumb-item active"><?= $data['page_tag'] ?></li>
                            </ol>
                        </div>

                    </div>
                </div>
            </div>
            <!-- end page title -->
             
            <div class="card">
                <div class="card-header">
                    <ul class="nav nav-tabs-custom card-header-tabs border-bottom-0 " role="tablist" id="nav-tab">
                        <li class="nav-item">
                            <a class="nav-link active" data-bs-toggle="tab" href="#listcompras" role="tab">
                                COMPRAS
                            </a>
                        </li>
                        <?php if ($_SESSION['permisosMod']['w']) { ?>
                            <li class="nav-item">
                                <a class="nav-link" data-bs-toggle="tab" href="#agregarcompra" role="tab">
                                    NUEVO
                                </a>
                            </li>
                        <?php } ?>
                    </ul>
                </div>
                <!-- end card header -->
                <div class="card-body">
                    <div class="tab-content">
                        <div class="tab-pane active" id="listcompras" role="tabpanel">
                            <table id="tableCompras"
                                class="table table-bordered nowrap table-striped align-middle"
                                style="width:100%">
                                <thead>
                                    <tr>
                                        <th></th>
                                        <th>TIPO DOCUMENTO</th>
                                        <th>STATUS</th>
                                        <th>ENLAZADO</th>
                                        <th>CLAVE DOCUMENTO</th>
                                        <th>NOMBRE_COMERCIAL</th>
                                        <th>TOTAL</th>
                                        <th>FECHA DOCUMENTO</th>
                                        <th>FECHA ELABORACION</th>
                                    </tr>
                                </thead>
                                <tbody>

                                </tbody>
                            </table>
                        </div>

                        <div class="tab-pane" id="agregarcompra" role="tabpanel">
                            <form id="formCompras" autocomplete="off" class="form-steps was-validated">
                                <input type="hidden" id="idcompras" name="idcompras">

                                <div class="row">
                                    <div class="col-12 mb-3">
                                        <h5 class="text-primary border-bottom pb-2"><i class="ri-file-list-3-line"></i> Identificación del Documento</h5>
                                    </div>
                                    
                                    <div class="col-lg-3">
                                        <div class="mb-3">
                                            <label class="form-label">COMPRA</label>
                                            <div class="input-group has-validation">
                                                <span class="input-group-text"><i class="ri- honors-line"></i></span>
                                                <input type="text" class="form-control" id="enlazado" name="enlazado" placeholder="Documento enlazado">
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-lg-3">
                                        <div class="mb-3">
                                            <label class="form-label">SERIE / FOLIO</label>
                                            <div class="input-group">
                                                <span class="input-group-text">#</span>
                                                <input type="text" class="form-control" id="serieid" name="serieid" placeholder="Serie" required>
                                                <input type="number" class="form-control" id="folioid" name="folioid" placeholder="Folio" readonly>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-lg-3">
                                        <div class="mb-3">
                                        </div>
                                    </div>

                                    <div class="col-lg-3">
                                        <div class="mb-3">
                                            <label class="form-label">FECHA DOCUMENTO</label>
                                            <input type="date" class="form-control" name="fecha_documento" value="2026-01-15" required>
                                        </div>
                                    </div>
                                </div>

                                <div class="row mt-3">
                                    <div class="col-12 mb-3">
                                        <h5 class="text-primary border-bottom pb-2"><i class="ri-truck-line"></i> Logística y Proveedor</h5>
                                    </div>

                                    <div class="col-lg-6">
                                        <div class="mb-3">
                                            <label class="form-label">PROVEEDOR</label>
                                            <div class="input-group has-validation">
                                                <span class="input-group-text"><i class="ri-user-settings-line"></i></span>
                                                <select class="form-select" id="proveedor" name="proveedor" required>
                                                    <option value="">Seleccione Proveedor...</option>
                                                </select>
                                                <div class="invalid-feedback">Debe seleccionar un proveedor.</div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-lg-3">
                                        <div class="mb-3">
                                            <label class="form-label">ALMACÉN</label>
                                            <div class="input-group has-validation">
                                                <span class="input-group-text"><i class="ri-building-2-line"></i></span>
                                                <select class="form-select" id="almacen" name="almacen" required>
                                                    <option value="">Seleccione almacén...</option>
                                                </select>
                                                <div class="invalid-feedback">Debe seleccionar un almacén.</div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-lg-3">
                                        <div class="mb-3">
                                            <label class="form-label">MONEDA</label>
                                            <div class="input-group">
                                                <span class="input-group-text"><i class="ri-coin-line"></i></span>
                                                <select class="form-select" id="moneda" name="moneda" required>
                                                    <option value="">Seleccione moneda...</option>
                                                </select>
                                                <div class="invalid-feedback">Debe seleccionar una moneda.</div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="row mt-3">
                                    <div class="col-12 mb-3 d-flex justify-content-between align-items-center">
                                        <h5 class="text-primary border-bottom pb-2 mb-0 flex-grow-1">
                                            <i class="ri-shopping-cart-2-line"></i> Detalle de Artículos
                                        </h5>
                                        <button type="button" class="btn btn-outline-primary btn-sm ms-3" onclick="fntAddArticulo()">
                                            <i class="ri-add-line"></i> Agregar Artículo
                                        </button>
                                    </div>

                                    <div class="col-12">
                                        <div class="table-responsive">
                                            <table class="table table-nowrap align-middle" id="tableDetalle">
                                                <thead class="table-light">
                                                    <tr class="text-center">
                                                        <th style="width: 30%;">Clave Artículo / Descripción</th>
                                                        <th style="width: 15%;">Cantidad</th>
                                                        <th style="width: 15%;">Costo Unit.</th>
                                                        <th style="width: 15%;">Impuesto</th>
                                                        <th style="width: 15%;">Subtotal</th>
                                                        <th style="width: 10%;">Acción</th>
                                                    </tr>
                                                </thead>
                                                <tbody id="cuerpoDetalle">
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-lg-5 mt-5 ms-auto">
                                    <div class="row mb-2 align-items-center">
                                        <label for="descuento_financiero" class="col-sm-4 col-form-label text-end fw-bold">DESC. FIN.</label>
                                        <div class="col-sm-8">
                                            <div class="input-group">
                                                <span class="input-group-text bg-light">$</span>
                                                <input type="number" step="0.000001" class="form-control text-end" 
                                                    id="descuento_financiero" name="descuento_financiero" value="0.00">
                                            </div>
                                        </div>
                                    </div>

                                    <div class="row mb-2 align-items-center">
                                        <label for="total_indirecto" class="col-sm-4 col-form-label text-end fw-bold">GASTOS IND.</label>
                                        <div class="col-sm-8">
                                            <div class="input-group">
                                                <span class="input-group-text bg-light">$</span>
                                                <input type="number" step="0.000001" class="form-control text-end" 
                                                    id="total_indirecto" name="total_indirecto" value="0.00">
                                            </div>
                                        </div>
                                    </div>

                                    <div class="row mb-2 align-items-center">
                                        <label for="importe" class="col-sm-4 col-form-label text-end fw-bold">SUBTOTAL</label>
                                        <div class="col-sm-8">
                                            <div class="input-group">
                                                <span class="input-group-text bg-light">$</span>
                                                <input type="number" step="0.000001" class="form-control bg-light text-end" 
                                                    id="importe" name="importe" value="0.00" readonly>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="row mb-2 align-items-center">
                                        <label for="cantidad_total" class="col-sm-4 col-form-label text-end fw-bold">TOTAL COMPRA</label>
                                        <div class="col-sm-8">
                                            <div class="input-group">
                                                <span class="input-group-text text-success fw-bold bg-light">$</span>
                                                <input type="number" step="0.000001" class="form-control fw-bold text-end" 
                                                    id="cantidad_total" name="cantidad_total" value="0.00" readonly>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                

                                <div class="d-flex align-items-start gap-3 mt-4 border-top pt-3">
                                    <button type="reset" class="btn btn-light">LIMPIAR</button>
                                    <button type="submit" id="btnActionForm" class="btn btn-success btn-label right ms-auto">
                                        <i class="ri-save-line label-icon align-middle fs-16 ms-2"></i>
                                        <span id="btnText">GUARDAR COMPRA</span>
                                    </button>
                                </div>
                            </form>
                        </div>
                        <!-- end tab pane -->
                    </div>
                    <!-- end tab content -->
                </div>
                <!-- end card body -->
            </div>

        </div>
        <!-- container-fluid -->
    </div>
    <!-- End Page-content -->

    <footer class="footer">
        <div class="container-fluid">
            <div class="row">
                <div class="col-sm-6">
                    <script>
                        document.write(new Date().getFullYear())
                    </script> © LDR.
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