<?php headerAdmin($data); ?>
<div id="contentAjax"></div>
<div class="main-content">
    <div class="page-content">
        <div class="container-fluid">

            <!-- start page title -->
            <div class="row">
                <div class="col-12">
                    <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                        <h4 class="mb-sm-0"><?= $data['page_title']; ?></h4>

                        <div class="page-title-right">
                            <ol class="breadcrumb m-0">
                                <li class="breadcrumb-item"><a href="javascript: void(0);">Logística</a></li>
                                <li class="breadcrumb-item active"><?= $data['page_tag']; ?></li>
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
                            <a class="nav-link active" data-bs-toggle="tab" href="#listMadrinas" role="tab" id="tabList">
                                MADRINAS
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" data-bs-toggle="tab" href="#agregarMadrina" role="tab" id="tabForm" onclick="fntNewMadrina();">
                                NUEVA MADRINA
                            </a>
                        </li>
                    </ul>
                </div>

                <div class="card-body">
                    <div class="tab-content">
                        <!-- TAB LISTA -->
                        <div class="tab-pane active" id="listMadrinas" role="tabpanel">
                            <div class="table-responsive">
                                <table class="table table-bordered dt-responsive nowrap table-striped align-middle" id="tableMadrinas" style="width:100%">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Trasladista</th>
                                            <th>No. Económico</th>
                                            <th>Placas</th>
                                            <th>Marca / Modelo</th>
                                            <th>Capacidad</th>
                                            <th>Estatus</th>
                                            <th>Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- TAB FORMULARIO -->
                        <div class="tab-pane" id="agregarMadrina" role="tabpanel">
                            <form id="formMadrina" name="formMadrina" autocomplete="off">
                                <input type="hidden" id="id_madrina" name="id_madrina" value="">
                                
                                <div class="row">
                                    <div class="col-lg-6 col-md-12 mb-3">
                                        <label for="id_proveedor" class="form-label">Empresa Trasladista <span class="text-danger">*</span></label>
                                        <select class="form-select" id="id_proveedor" name="id_proveedor" required>
                                            <option value="">-- Seleccionar Trasladista --</option>
                                            <?php foreach ($data['trasladistas'] as $t) { ?>
                                                <option value="<?= $t['id_proveedor']; ?>"><?= $t['razon_social']; ?> (<?= $t['rfc']; ?>)</option>
                                            <?php } ?>
                                        </select>
                                    </div>

                                    <div class="col-lg-3 col-md-6 mb-3">
                                        <label for="numero_economico" class="form-label">Número Económico <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" id="numero_economico" name="numero_economico" required placeholder="ej. M-101">
                                    </div>

                                    <div class="col-lg-3 col-md-6 mb-3">
                                        <label for="placas" class="form-label">Placas Tracto <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control text-uppercase" id="placas" name="placas" required placeholder="ej. 64-AA-1B">
                                    </div>

                                    <div class="col-lg-3 col-md-6 mb-3">
                                        <label for="placa_caja" class="form-label">Placas Caja / Remolque</label>
                                        <input type="text" class="form-control text-uppercase" id="placa_caja" name="placa_caja" placeholder="ej. 98-TY-2C">
                                    </div>

                                    <div class="col-lg-3 col-md-6 mb-3">
                                        <label for="num_serie_vin" class="form-label">Número de Serie / VIN</label>
                                        <input type="text" class="form-control text-uppercase" id="num_serie_vin" name="num_serie_vin" placeholder="ej. 3AKJHGLD82910">
                                    </div>

                                    <div class="col-lg-3 col-md-6 mb-3">
                                        <label for="marca" class="form-label">Marca</label>
                                        <input type="text" class="form-control" id="marca" name="marca" placeholder="ej. Freightliner">
                                    </div>

                                    <div class="col-lg-3 col-md-6 mb-3">
                                        <label for="modelo" class="form-label">Modelo</label>
                                        <input type="text" class="form-control" id="modelo" name="modelo" placeholder="ej. Cascadia">
                                    </div>

                                    <div class="col-lg-2 col-md-4 mb-3">
                                        <label for="anio" class="form-label">Año</label>
                                        <input type="number" class="form-control" id="anio" name="anio" placeholder="2024" min="1990" max="2030">
                                    </div>

                                    <div class="col-lg-2 col-md-4 mb-3">
                                        <label for="color" class="form-label">Color</label>
                                        <input type="text" class="form-control" id="color" name="color" placeholder="ej. Blanco">
                                    </div>

                                    <div class="col-lg-2 col-md-4 mb-3">
                                        <label for="capacidad_vehiculos" class="form-label">Capacidad Unidades</label>
                                        <input type="number" class="form-control" id="capacidad_vehiculos" name="capacidad_vehiculos" value="8" min="1" max="20">
                                    </div>
                                </div>

                                <div class="mt-4 text-end">
                                    <button type="button" class="btn btn-light me-2" onclick="cancelForm();">Cancelar</button>
                                    <button type="submit" class="btn btn-primary" id="btnActionForm"><span id="btnText">Guardar</span></button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

<!-- Modal Historial y Asignación de Chofer -->
<div class="modal fade" id="modalHistorialMadrina" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-xl">
    <div class="modal-content">
      <div class="modal-header bg-light">
        <div>
          <h5 class="modal-title mb-0" id="titleModalHistorial">Historial de Operadores de la Madrina</h5>
          <small class="text-muted" id="subTitleMadrina">Unidad</small>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        
        <!-- Formulario Asignar Chofer -->
        <div class="card border mb-4">
          <div class="card-header bg-soft-primary">
            <h6 class="card-title mb-0"><i class="ri-user-add-line me-1"></i> Asignar / Cambiar Chofer Activo</h6>
          </div>
          <div class="card-body">
            <form id="formAsignarChofer">
              <input type="hidden" id="historial_id_madrina" name="id_madrina" value="">
              <input type="hidden" id="historial_id_proveedor" value="">
              
              <div class="row align-items-end">
                <div class="col-md-6 mb-3 mb-md-0">
                  <label for="selectChoferAsignar" class="form-label">Seleccionar Chofer <span class="text-danger">*</span></label>
                  <select class="form-select" id="selectChoferAsignar" name="id_chofer" required>
                    <option value="">-- Cargar Choferes --</option>
                  </select>
                </div>
                <div class="col-md-4 mb-3 mb-md-0">
                  <label for="observacionesAsignar" class="form-label">Observaciones / Motivo</label>
                  <input type="text" class="form-control" id="observacionesAsignar" name="observaciones" placeholder="ej. Asignación por turno">
                </div>
                <div class="col-md-2 text-end">
                  <button type="submit" class="btn btn-success w-100"><i class="ri-check-line me-1"></i> Asignar</button>
                </div>
              </div>
            </form>
          </div>
        </div>

        <!-- Tabla Historial -->
        <h6 class="mb-3"><i class="ri-history-line me-1"></i> Registro Histórico de Conductores</h6>
        <div class="table-responsive">
          <table class="table table-bordered table-striped align-middle" id="tableHistorialChoferes">
            <thead>
              <tr>
                <th>Chofer</th>
                <th>No. Licencia</th>
                <th>Teléfono</th>
                <th>Fecha Inicio</th>
                <th>Fecha Fin</th>
                <th>Estado</th>
                <th>Observaciones</th>
              </tr>
            </thead>
            <tbody id="tbodyHistorialChoferes">
            </tbody>
          </table>
        </div>

      </div>
    </div>
  </div>
</div>

<?php footerAdmin($data); ?>
