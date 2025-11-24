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
                    <ul class="nav nav-tabs-custom card-header-tabs border-bottom-0" role="tablist" id="nav-tab">
                        <li class="nav-item">
                            <a class="nav-link active" data-bs-toggle="tab" href="#listEstaciones" role="tab">
                                LISTADO DE ESTACIONES
                            </a> 
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" data-bs-toggle="tab" href="#agregarEstacion" role="tab">
                                NUEVO
                            </a> 
                        </li>
                    </ul>
                </div>
                <!-- end card header -->
                <div class="card-body">
                    <div class="tab-content">
                        <div class="tab-pane active" id="listEstaciones" role="tabpanel">
 
                            <table id="tableEstaciones"
                                class="table table-bordered dt-responsive nowrap table-striped align-middle"
                                style="width:100%">
                                <thead>
                                    <tr> 
                                      <th>ID</th>
                                        <th>NOMBRE ESTACIÓN</th>
                                         <th>LÍNEA ASIGNADA</th>
                                        <th>FECHA REGISTRO</th>
                                        <th>ESTATUS</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>

                                </tbody>
                            </table>

                        </div>
                        <!-- end tab-pane -->




                        <div class="tab-pane" id="agregarEstacion" role="tabpanel">
                            <form id="formEstaciones" autocomplete="off"  class="form-steps was-validated" autocomplete="off">
                                <input type="hidden" id="idestacion" name="idestacion">
                                <div class="row">
                                    <!-- NOMBRE -->
                                    <div class="col-lg-6">
                                        <div class="mb-3">
                                            <label class="form-label" for="nombre-estacion-input">NOMBRE</label>
                                            <div class="input-group mb-3">
                                                <span class="input-group-text" id="nombre-linea-addon">Nom</span>
                                                <input type="text" class="form-control"
                                                    placeholder="Ingresa el nombre de la línea" id="nombre-estacion-input" name="nombre-estacion-input"
                                                    aria-describedby="nombre-linea-addon" required>
                                                <div class="invalid-feedback">El campo de nombre es obligatorio</div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- PROCESO -->
                                    <div class="col-lg-6">
                                        <div class="mb-3">
                                            <label class="form-label" for="proceso-estacion-input">PROCESO</label>
                                            <div class="input-group mb-3">
                                                <span class="input-group-text" id="proceso-linea-addon">Proc</span>
                                                <input type="text" class="form-control"
                                                    placeholder="Ingresa el proceso de la línea"
                                                    id="proceso-estacion-input" name="proceso-estacion-input" aria-describedby="proceso-linea-addon"
                                                    required>
                                                    <div class="invalid-feedback">El campo de proceso es obligatorio</div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <!-- end row -->

                                <div class="row">
                                    <!-- ESTÁNDAR -->
                                    <div class="col-lg-3 col-sm-6">
                                        <div class="mb-3">
                                            <label class="form-label" for="estandar-input">ESTÁNDAR</label>
                                            <div class="input-group mb-3">
                                                <span class="input-group-text" id="estandar-addon">Std</span>
                                                <input type="text" class="form-control" id="estandar-input" name="estandar-input"
                                                    placeholder="Ingresa el estándar" aria-describedby="estandar-addon"
                                                    required>
                                                    <div class="invalid-feedback">El campo estandar es obligatorio</div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- UNIDAD DE MEDIDA -->
                                    <div class="col-lg-3 col-sm-6">
                                        <div class="mb-3">
                                            <label class="form-label" for="unidad-medida-input">UNIDAD DE MEDIDA</label>
                                            <div class="input-group has-validation mb-3">
                                                <span class="input-group-text" id="unidad-medida-addon">UM</span>
                                                <!-- <input type="text" class="form-control" id="unidad-medida-input" name="unidad-medida-input"
                                                    placeholder="Ingresa la unidad de medida"
                                                    aria-label="Unidad de medida" aria-describedby="unidad-medida-addon"
                                                    required> -->

                                                     <select class="form-control" id="unidad-medida-select" name="unidad-medida-select" required="">
    <option value="">Seleccione una unidad</option>

    <!-- Básicas -->
    <option value="pieza">Pieza (pc)</option>
    <option value="kit">Kit</option>
    <option value="juego">Juego (jgo)</option>
    <option value="par">Par</option>
    <option value="conjunto">Conjunto</option>
    <option value="subconjunto">Subconjunto</option>
    <option value="rollo">Rollo</option>
    <option value="paquete">Paquete</option>
    <option value="caja">Caja</option>
    <option value="bolsa">Bolsa</option>

    <!-- Ingeniería -->
    <option value="unidad">Unidad</option>
    <option value="elemento">Elemento</option>
    <option value="componente">Componente</option>
    <option value="modulo">Módulo</option>
    <option value="armado">Armado</option>
    <option value="ensamble">Ensamble</option>

    <!-- Longitudes -->
    <option value="m">Metro (m)</option>
    <option value="cm">Centímetro (cm)</option>
    <option value="mm">Milímetro (mm)</option>
    <option value="in">Pulgada (in)</option>

    <!-- Eléctrico -->
    <option value="cable">Cable</option>
    <option value="metro_cable">Metro de cable</option>
    <option value="terminal">Terminal</option>
    <option value="conector">Conector</option>
    <option value="sensor">Sensor</option>
    <option value="actuador">Actuador</option>

    <!-- Consumibles -->
    <option value="litro">Litro (lt)</option>
    <option value="mililitro">Mililitro (ml)</option>
    <option value="galon">Galón (gl)</option>
    <option value="cartucho">Cartucho</option>
    <option value="tubo">Tubo</option>
    <option value="barra">Barra</option>
    <option value="hoja">Hoja</option>
    <option value="bote">Bote</option>
    <option value="spray">Spray</option>

    <!-- Logística -->
    <option value="tarima">Tarima</option>
    <option value="pallet">Pallet</option>
    <option value="contenedor">Contenedor</option>
    <option value="caja_master">Caja master</option>
    <option value="bobina">Bobina</option>
    <option value="saco">Saco</option>

    <!-- Tornillería -->
    <option value="tornillo">Tornillo</option>
    <option value="tuerca">Tuerca</option>
    <option value="arandela">Arandela</option>
    <option value="clip">Clip</option>
    <option value="remache">Remache</option>
    <option value="pin">Pin</option>
    <option value="bracket">Bracket</option>

    <!-- Automotriz -->
    <option value="vin">VIN</option>
    <option value="etiqueta">Etiqueta</option>
    <option value="set_armado">Set de Armado</option>
    <option value="pack">Pack Automotriz</option>
    <option value="ecu">ECU</option>
    <option value="arnes">Arnés</option>
    <option value="chicote">Chicote</option>

    <!-- Calidad -->
    <option value="formato">Formato</option>
    <option value="registro">Registro</option>
    <option value="inspeccion">Inspección</option>
    <option value="checklist">Checklist</option>
</select>

                                                     <div class="invalid-feedback">El campo unidad de medida es obligatorio</div>
                                            </div>
                                        </div>
                                    </div>


                                       <div class="col-lg-3 col-sm-6">
                                        <div class="mb-3">
                                            <label class="form-label" for="tiempo-ajuste-input">TIEMPO DE AJUSTE</label>
                                            <div class="input-group mb-3">
                                                <span class="input-group-text" id="tiempo-ajuste-addon">min</span>
                                                <input type="text" class="form-control" id="tiempo-ajuste-input" name="tiempo-ajuste-input"
                                                    placeholder="hh:mm:ss" step="60"
                                                    aria-describedby="tiempo-ajuste-addon" required>
                                                     <div class="invalid-feedback">El campo tiempo de ajuste es obligatorio</div>
                                            </div>
                                        </div>
                                    </div>

                                       <div class="col-lg-3 col-sm-6">
                                        <div class="mb-3">
                                            <label class="form-label" for="mx-input">MX</label>
                                            <div class="input-group mb-3">
                                                <span class="input-group-text" id="mx-addon" >MXN</span>
                                                <input type="text" class="form-control" id="mx-input" name="mx-input"
                                                    placeholder="Ingresa el valor"
                                                    aria-describedby="mx-addon" required>
                                                      <div class="invalid-feedback">El campo MXN es obligatorio</div>
                                            </div>
                                        </div>
                                    </div>


                                </div>
                                <!-- end row -->

                                <!-- end row -->

                                <div class="row">
                                    <!-- MX -->
                                    <div class="col-lg-6 col-sm-6">
                                        <div class="mb-3">
                                            <label class="form-label" for="listLineas">LÍNEA</label>
                                            <div class="input-group mb-3">
                                                <span class="input-group-text" >LIN</span>
                                               <select class="form-control"   name="listLineas" id="listLineas" required></select>
                                                      <div class="invalid-feedback">El campo Línea es obligatorio</div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- ESTADO -->
                                    <div class="col-lg-6 col-sm-6"> 
                                        <div class="mb-3">
                                            <label class="form-label" for="estado-select">ESTADO</label>
                                            <div class="input-group has-validation mb-3">
                                                <span class="input-group-text" id="estado-addon">Est</span>
                                                <select class="form-select" id="estado-select" name="estado-select"
                                                    aria-describedby="estado-addon" required>
                                                    <option value="2" selected>Activo</option>
                                                     <option value="1">inactivo</option>
                                                </select>
                                                <div class="invalid-feedback">El campo estado es obligatorio</div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <!-- end row -->

                                <!-- DESCRIPCIÓN -->
                                <div class="mb-3">
                                    <label class="form-label" for="descripcion-estacion-textarea">DESCRIPCIÓN</label>
                                    <div class="input-group mb-3">
                                        <span class="input-group-text" id="descripcion-estacion-addon">Desc</span>
                                        <textarea class="form-control" id="descripcion-estacion-textarea" name="descripcion-estacion-textarea"
                                            placeholder="Ingresa una descripción sobre esta línea" rows="3"
                                            aria-describedby="descripcion-estacion-addon"></textarea>
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




<div class="modal fade" id="modalViewEstacion" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
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
              <td>Nombre:</td>
              <td id="celNombre">Jacob</td>
            </tr>
            <tr>
              <td>Proceso:</td>
              <td id="celProceso">Jacob</td>
            </tr>

                        <tr>
              <td>Estandar:</td>
              <td id="celEstandar">Jacob</td>
            </tr>
                        <tr>
              <td>Unidad de Medida:</td>
              <td id="celUnidad">Jacob</td>
            </tr>
                        <tr>
              <td>Tiempo de ajuste:</td>
              <td id="celTiempo">Jacob</td>
            </tr>
                        <tr>
              <td>MX:</td>
              <td id="celProceso">Jacob</td>
            </tr>
                        <tr>
              <td>Proceso:</td>
              <td id="celMx">Jacob</td>
            </tr>

                        <tr>
              <td>Línea:</td>
              <td id="celLinea">Jacob</td>
            </tr>

            <tr>
              <td>Estado:</td>
              <td id="celEstado">Larry</td>
            </tr>
<tr>
              <td>Descripción:</td>
              <td id="celDescripcion">Larry</td>
            </tr>
            <tr>
              <td>Fecha:</td>
              <td id="celFecha">Larry</td>
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