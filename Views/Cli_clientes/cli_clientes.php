<?php headerAdmin($data);
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
                            <a class="nav-link active" data-bs-toggle="tab" href="#listplantas" role="tab">
                               PLANTAS
                            </a>
                        </li>
                          <?php if($_SESSION['permisosMod']['w']){ ?>
                        <li class="nav-item">
                            <a class="nav-link" data-bs-toggle="tab" href="#agregarplanta" role="tab">
                                NUEVO
                            </a>
                        </li>
                         <?php } ?>
                    </ul>
                </div>
                <!-- end card header -->
                <div class="card-body">
                    <div class="tab-content">
                        <div class="tab-pane active" id="listplantas" role="tabpanel">

                            <table id="tablePlantas"
                                class="table table-bordered dt-responsive nowrap table-striped align-middle"
                                style="width:100%">
                                <thead>
                                    <tr>
                                        <th>CLAVE</th>
                                        <th>NOMBRE</th>
                                        <th>FECHA</th>
                                        <th>ESTATUS</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>

                                </tbody>
                            </table>

                        </div>
                        <!-- end tab-pane -->




                        <div class="tab-pane" id="agregarplanta" role="tabpanel">
                            <form id="formPlantas" autocomplete="off"  class="form-steps was-validated" autocomplete="off">
                                <input type="hidden" id="idplanta" name="idplanta">
                                <div class="row">
                                    <!-- NOMBRE -->
                                    <div class="col-lg-6">
                                        <div class="mb-3">
                                            <label class="form-label" for="nombre-planta-input">NOMBRE</label>
                                            <div class="input-group mb-3">
                                                <span class="input-group-text" id="nombre-planta-addon">Nom</span>
                                                <input type="text" class="form-control"
                                                    placeholder="Ingresa el nombre de la planta" id="nombre-planta-input" name="nombre-planta-input"
                                                    aria-describedby="nombre-planta-addon" required>
                                                <div class="invalid-feedback">El campo de nombre es obligatorio</div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- ESTADO -->
                                    <div class="col-lg-6">
                                        <div class="mb-3">
                                                  <label class="form-label" for="estado-select">ESTADO</label>
                                            <div class="input-group has-validation mb-3">
                                                <span class="input-group-text" id="estado-addon">Est</span>
                                                <select class="form-select" id="estado-select" name="estado-select"
                                                    aria-describedby="estado-addon" required >
                                                    <option value="2" selected>Activo</option>
                                                    <option value="1">Inactivo</option>
                                                </select>
                                                <div class="invalid-feedback">El campo estado es obligatorio</div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <!-- end row -->

                                    <div class="mb-3">
                                    <label class="form-label" for="direccion-linea-textarea">Dirección</label>
                                    <div class="input-group mb-3">
                                        <span class="input-group-text" id="direccion-linea-addon">dir</span>
                                        <textarea class="form-control" id="direccion-linea-textarea" name="direccion-linea-textarea"
                                            placeholder="Ingresa la dirección" rows="3" 
                                            aria-describedby="direccion-linea-addon"></textarea>
                                    </div>
                                </div>

                                <div class="d-flex align-items-start gap-3 mt-4">
                                    <button type="submit" id="btnActionForm"
                                        class="btn btn-success btn-label right ms-auto nexttab nexttab"
                                        data-nexttab="steparrow-description-info-tab"><i
                                            class="ri-arrow-right-line label-icon align-middle fs-16 ms-2"></i><span id="btnText">REGISTRAR</span></button>
                                </div>


                            </form>
                        </div>

                        <!-- end tab pane -->
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



<div class="modal fade" id="modalViewPlanta" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
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
              <td>Clave:</td>
              <td id="celClave">654654654</td>
            </tr>
            <tr>
              <td>Nombres:</td>
              <td id="celNombre">Jacob</td>
            </tr>
            <tr>
              <td>Fecha Registro:</td>
              <td id="celFecha">Jacob</td>
            </tr>
            <tr>
              <td>Estado:</td>
              <td id="celEstado">Larry</td>
            </tr>
                        <tr>
              <td>Dirección:</td>
              <td id="celDireccion">Larry</td>
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







<!-- end main content-->
<?php footerAdmin($data); ?>