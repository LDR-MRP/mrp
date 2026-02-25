<?php headerAdmin($data);
?>
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
                    <ul class="nav nav-tabs-custom card-header-tabs border-bottom-0" id="nav-tab" role="tablist">
                        <li class="nav-item">
                            <a class="nav-link active" data-bs-toggle="tab" href="#listInventarios" role="tab">
                                INVENTARIO
                            </a>
                        </li>
                        <?php if ($_SESSION['permisosMod']['w']) { ?>
                            <li class="nav-item">
                                <a class="nav-link" data-bs-toggle="tab" href="#agregarProducto" role="tab">
                                    +ALTAS
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" data-bs-toggle="tab" href="#agregarServicio" role="tab">
                                    +SERVICIO
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" data-bs-toggle="tab" href="#agregarKit" role="tab">
                                    +KIT
                                </a>
                            </li>
                        <?php } ?>
                    </ul>
                </div>
                <!-- end card header -->
                <div class="card-body">
                    <div class="tab-content">
                        <div class="tab-pane active" id="listInventarios" role="tabpanel">

                            <table id="tableInventarios"
                                class="table table-bordered dt-responsive nowrap table-striped align-middle"
                                style="width:100%">
                                <thead>
                                    <tr>
                                        <th>CLAVE</th>
                                        <th>DESCRIPCIÓN</th>
                                        <th>TIPO</th>
                                        <th>LÍNEA</th>
                                        <th>ESTADO</th>
                                        <th>ACCIÓN</th>
                                    </tr>
                                </thead>
                                <tbody>

                                </tbody>
                            </table>

                        </div>
                        <!-- end tab-pane -->



                        <!--FORMULARIO ALTA DE PRODUCTOS-->
                        <div class="tab-pane" id="agregarProducto" role="tabpanel">
                            <?php include_once('forms/inv_form_producto.php'); ?>
                        </div>

                        <!--FORMULARIO ALTA DE SERVICIOS-->
                        <div class="tab-pane" id="agregarServicio" role="tabpanel">
                            <?php include_once('forms/inv_form_servicio.php'); ?>
                        </div>


                        <!--FORMULARIO ALTA DE KITS-->
                        <div class="tab-pane" id="agregarKit" role="tabpanel">
                            <?php include_once('forms/inv_form_kit.php'); ?>
                        </div>
                        <!-- end tab pane -->
                    </div>

                    <div id="kit_config_container" style="display:none;">
                        <?php include 'forms/inv_kit_config.php'; ?>
                    </div>
                    <!-- end tab content -->
                </div>
                <!-- end card body -->
            </div>
            <!-- end card -->


            <!--end row-->

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


<div class="modal fade" id="modalViewInventario" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0">
            <div class="modal-header bg-primary-subtle p-3">
                <h5 class="modal-title" id="titleModal">Datos del registro</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close" id="close-modal"></button>
            </div>
            <div class="modal-body">
                <table class="table table-bordered">
                    <tbody>
                        <tr>
                            <td>Clave artículo:</td>
                            <td id="celClave"></td>
                        </tr>
                        <tr>
                            <td>Clave alterna:</td>
                            <td id="celClaveAlterna"></td>
                        </tr>
                        <tr>
                            <td>Descripción:</td>
                            <td id="celDescripcion"></td>
                        </tr>
                        <tr>
                            <td>Tipo:</td>
                            <td id="celTipo"></td>
                        </tr>
                        <tr>
                            <td>Unidad entrada:</td>
                            <td id="celUnidadEntrada"></td>
                        </tr>
                        <tr>
                            <td>Unidad salida:</td>
                            <td id="celUnidadSalida"></td>
                        </tr>
                        <tr>
                            <td>Factor:</td>
                            <td id="celFactor"></td>
                        </tr>
                        <tr>
                            <td>Ubicación:</td>
                            <td id="celUbicacion"></td>
                        </tr>
                        <tr>
                            <td>Peso:</td>
                            <td id="celPeso"></td>
                        </tr>
                        <tr>
                            <td>Volumen:</td>
                            <td id="celVolumen"></td>
                        </tr>
                        <tr>
                            <td>Serie:</td>
                            <td id="celSerie"></td>
                        </tr>
                        <tr>
                            <td>Lote:</td>
                            <td id="celLote"></td>
                        </tr>
                        <tr>
                            <td>Pedimiento:</td>
                            <td id="celPedimiento"></td>
                        </tr>
                        <tr>
                        <tr>
                            <td>Estado:</td>
                            <td id="celEstado"></td>
                        </tr>
                        <tr>
                            <td>Fecha Registro:</td>
                            <td id="celFecha"></td>
                        </tr>

                    </tbody>

                </table>
            </div>

            <div class="modal-footer">
                <div class="hstack gap-2 justify-content-end">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cerrar</button>
                    <!-- <button type="submit" id="btnActionForm" class="btn btn-success">
        <span id="btnText">Guardar</span>
      </button> -->
                </div>

            </div>
        </div>
    </div>
</div>

<!--Modal de asignacion de configuracion del inventario-->

<div class="modal fade" id="modalConfigInventario" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-fullscreen modal-dialog-centered">
        <div class="modal-content border-0">

            <div class="modal-header bg-primary-subtle p-3">
                <h5 class="modal-title" id="titleModal">Configuración de productos</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close" id="close-modal"></button>
            </div>

            <div class="modal-body">

                <!-- INFO -->
                <div class="card mb-3 informacionmodalproducto">
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-3">
                                <strong>Clave:</strong>
                                <div id="cfg_cve"></div>
                            </div>
                            <div class="col-md-6">
                                <strong>Descripción:</strong>
                                <div id="cfg_desc"></div>
                            </div>
                            <div class="col-md-3">
                                <strong>Tipo:</strong>
                                <div id="cfg_tipo"></div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- TABS -->
                <ul class="nav nav-tabs" role="tablist">
                    <li class="nav-item">
                        <a class="nav-link active" data-bs-toggle="tab" href="#tabMoneda">Moneda</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" data-bs-toggle="tab" href="#tabPrecio">Precio</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" data-bs-toggle="tab" href="#tabFiscal">Fiscal</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" data-bs-toggle="tab" href="#tabImpuesto">Impuesto</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" data-bs-toggle="tab" href="#tabLtpd">
                            Lote / Pedimento
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" data-bs-toggle="tab" href="#tabLinea">Línea</a>
                    </li>
                </ul>

                <div class="tab-content mt-3">
                    <!--tab de la moneda-->
                    <div class="tab-pane fade show active" id="tabMoneda">
                        <!-- Contenedor dinámico solo para select y botón -->
                        <div id="contentMoneda"></div>
                    </div>

                    <!--tab de la precios-->
                    <div class="tab-pane fade" id="tabPrecio">
                        <!-- Contenedor dinámico solo para select y botón -->
                        <div id="contentPrecio"></div>
                    </div>


                    <!--tab fiscal-->
                    <div class="tab-pane fade" id="tabFiscal">
                        <div id="contentFiscal">
                            <div class="row g-3 bloqueFiscalForm" data-grupo="sat">
                                <!--CLAVE SAT-->
                                <div class="col-md-6 position-relative">
                                    <label class="form-label">Clave SAT (ProdServ)</label>
                                    <div class="input-group">
                                        <input type="text" class="form-control satSearch" readonly>
                                        <button class="btn btn-outline-success btnsatSearch" type="button">
                                            <i class="ri-search-line"></i>
                                        </button>
                                    </div>
                                    <input type="hidden" name="clave_sat">
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label">Descripción Producto o Servicio SAT</label>
                                    <input type="text" class="form-control" name="desc_sat" readonly>
                                </div>
                            </div>

                            <div class="row g-3 bloqueFiscalForm" data-grupo="unidad">
                                <!-- CLAVE UNIDAD SAT -->
                                <div class="col-md-6 position-relative">
                                    <label class="form-label">Clave Unidad SAT</label>
                                    <div class="input-group">
                                        <input type="text" class="form-control unidadSatInput" readonly>
                                        <button class="btn btn-outline-danger btnUnidadSat" type="button">
                                            <i class="ri-search-line"></i>
                                        </button>
                                    </div>
                                    <input type="hidden" name="clave_unidad_sat">
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label">Descripción clave Unidad SAT</label>
                                    <input type="text" class="form-control" name="desc_clave_unidad_sat" readonly>
                                </div>
                            </div>

                            <div class="row g-3 bloqueFiscalForm" data-grupo="fraccion">
                                <!--FRACCION ARANCELARIA-->
                                <div class="col-md-6 position-relative">
                                    <label class="form-label">Fracción Arancelaria</label>
                                    <div class="input-group">
                                        <input type="text" class="form-control fraccionInput" readonly>
                                        <button class="btn btn-outline-warning fraccionArancelariaSearch" type="button">
                                            <i class="ri-search-line"></i>
                                        </button>
                                    </div>
                                    <input type="hidden" name="clave_fraccion_sat">
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label">Descripción clave Fracción Arancelaria</label>
                                    <input type="text" class="form-control" name="desc_clave_fraccion_sat" readonly>
                                </div>
                            </div>

                            <div class="row g-3 bloqueFiscalForm" data-grupo="aduana">
                                <!--UNIDAD ADUANA-->
                                <div class="col-md-6 position-relative">
                                    <label class="form-label">Unidad Aduana</label>
                                    <div class="input-group">
                                        <input type="text" class="form-control aduanaInput" readonly>
                                        <button class="btn btn-outline-secondary aduanaSearch" type="button">
                                            <i class="ri-search-line"></i>
                                        </button>
                                    </div>
                                    <input type="hidden" name="clave_aduana_sat">
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label">Descripción clave Unidad Aduana</label>
                                    <input type="text" class="form-control" name="desc_clave_aduana_sat" readonly>
                                </div>
                            </div>

                            <div class="row mt-4">
                                <div class="col-12 text-end">
                                    <button type="button" class="btn btn-primary" id="btnGuardarFiscal">
                                        <i class="ri-save-line"></i> Guardar datos fiscales
                                    </button>
                                </div>
                            </div>

                            <hr>

                            <div id="bloqueFiscalTabla" class="mt-3 d-none">
                                <h6>Datos fiscales asignados</h6>

                                <table class="table table-sm table-bordered">
                                    <thead>
                                        <tr>
                                            <th>Tipo</th>
                                            <th>Clave</th>
                                            <th>Descripción</th>
                                            <th></th>
                                        </tr>
                                    </thead>
                                    <tbody id="tbodyFiscal"></tbody>
                                </table>
                            </div>

                        </div>
                    </div>

                    <!--tab impuesto-->
                    <div class="tab-pane fade" id="tabImpuesto">
                        <div id="contentImpuesto">

                            <form id="formImpuestoInventario">
                                <input type="hidden" id="imp_inventarioid" name="inventarioid">

                                <div class="row g-3">
                                    <div class="col-md-8">
                                        <label class="form-label">Impuesto</label>
                                        <select id="cfg_impuesto" name="idimpuesto" class="form-select">
                                            <option value="">Seleccione un impuesto</option>
                                        </select>
                                    </div>

                                    <div class="col-md-4 d-flex align-items-end">
                                        <button type="submit" class="btn btn-primary w-100">
                                            Registrar impuesto
                                        </button>
                                    </div>
                                </div>
                            </form>

                            <div class="mt-4">
                                <h6>Impuestos asignados</h6>
                                <table class="table table-bordered table-sm">
                                    <thead>
                                        <tr>
                                            <th>Impuesto</th>
                                            <th>Estado</th>
                                        </tr>
                                    </thead>
                                    <tbody id="tbodyImpuestosCfg"></tbody>
                                </table>
                            </div>

                        </div>
                    </div>

                    <!--tab del lote y pedimento-->
                    <div class="tab-pane fade" id="tabLtpd">

                        <div class="row mb-3">
                            <div class="col-md-4">
                                <label class="form-label">Tipo</label>
                                <select id="ltpd_tipo" class="form-select">
                                    <option value="">Seleccione</option>
                                    <option value="L">Lote</option>
                                    <option value="P">Pedimento</option>
                                </select>
                            </div>
                        </div>

                        <form id="formLtpd">

                            <div id="ltpdCampos"></div>

                            <div class="mt-3 text-end">
                                <button type="button" class="btn btn-success"
                                    onclick="guardarLtpd()">
                                    Guardar
                                </button>
                            </div>

                        </form>

                        <!-- ✅ TABLA -->
                        <div class="mt-4">
                            <h6>Lotes / Pedimentos asignados</h6>
                            <table class="table table-striped table-bordered">
                                <thead>
                                    <tr>
                                        <th>Tipo</th>
                                        <th>Almacén</th>
                                        <th>Clave</th>
                                        <th>Cantidad</th>
                                        <th>F. Prod</th>
                                        <th>F. Cad</th>
                                        <th>Estado</th>
                                    </tr>
                                </thead>
                                <tbody id="tbodyLtpd"></tbody>
                            </table>
                        </div>

                    </div>


                    <!--tab de la linea-->
                    <div class="tab-pane fade" id="tabLinea">
                        <div id="contentLinea">
                            <!-- FORMULARIO LÍNEA -->
                            <form id="formLineaInventario">
                                <input type="hidden" id="inventarioid" name="inventarioid" value="">

                                <div class="row g-3">
                                    <div class="col-md-8">
                                        <label for="idlineaproducto" class="form-label">Línea de producto</label>
                                        <select id="idlineaproducto" name="idlineaproducto" class="form-select" required>
                                            <option value="">Seleccione una línea</option>
                                            <!-- Opciones llenadas desde JS/PHP -->
                                        </select>
                                    </div>
                                    <div class="col-md-4 d-flex align-items-end">
                                        <button type="submit" class="btn btn-primary w-100">Registrar línea</button>
                                    </div>
                                </div>
                            </form>

                            <!-- TABLA DE LÍNEAS ASIGNADAS -->
                            <div class="mt-4">
                                <h5>Líneas asignadas</h5>
                                <table class="table table-striped table-bordered" id="tablaLineas">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Línea de producto</th>
                                            <th>Fecha asignación</th>
                                            <th>Estado</th>
                                            <th>Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody id="tbodyLineasAsignadas">
                                        <!-- Se llenará dinámicamente -->
                                    </tbody>
                                </table>
                            </div>

                        </div>
                    </div>

                </div>

            </div>

        </div>
    </div>
</div>

<!--Modal para ver la informacion del catalogo de los productos y servicios para datos fiscales-->
<div class="modal fade modalSAT" id="modalSAT" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Buscar Clave SAT</h5>
                <button class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body">
                <input type="text" id="satSearchInput" class="form-control mb-3" placeholder="Buscar por clave o descripción...">

                <div id="satResultados" style="max-height:400px;overflow:auto"></div>
            </div>
        </div>
    </div>
</div>

<!--Modal para ver la informacion del catalogo de las claves de unidades SAT para datos fiscales-->
<div class="modal fade modalUNIDSAT" id="modalUNIDSAT" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Buscar Clave de Unidad SAT</h5>
                <button class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body">
                <input type="text" id="unidadSatSearchInput"
                    class="form-control mb-3"
                    placeholder="Buscar por clave o descripción...">

                <div id="unidadSatResultados"
                    style="max-height:400px;overflow:auto"></div>
            </div>
        </div>
    </div>
</div>

<!--Modal para ver la informacion del catalogo las fracciones arancelarias para datos fiscales-->
<div class="modal fade modalFRACCIONSAT" id="modalFRACCIONSAT" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Buscar Clave de Fracción Arancelaria SAT</h5>
                <button class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body">
                <input type="text" id="satFraccionSearchInput" class="form-control mb-3" placeholder="Buscar por clave o descripción...">

                <div id="satFraccionResultados" style="max-height:400px;overflow:auto"></div>
            </div>
        </div>
    </div>
</div>

<!--Modal para ver la informacion del catalogo aduanas para datos fiscales-->
<div class="modal fade modalADUANASAT" id="modalADUANASAT" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Buscar Clave de Aduana SAT</h5>
                <button class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body">
                <input type="text" id="satAduanaSearchInput" class="form-control mb-3" placeholder="Buscar por clave o descripción...">

                <div id="satAduanaResultados" style="max-height:400px;overflow:auto"></div>
            </div>
        </div>
    </div>
</div>




<!-- end main content-->
<?php footerAdmin($data); ?>