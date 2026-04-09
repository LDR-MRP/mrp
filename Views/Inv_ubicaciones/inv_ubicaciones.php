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
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5>Listado de ubicaciones</h5>
                    <button class="btn btn-primary" onclick="toggleForm()">
                        Nueva Ubicación
                    </button>
                </div>

                <!-- FORMULARIO DESPLEGABLE -->
                <div id="formUbicacionContainer" class="card-body border-top" style="display:none;">

                    <form id="formUbicacion">

                        <div class="row mb-3">

                            <div class="col-md-4">
                                <label class="form-label">Sede</label>
                                <select id="sedeid" name="sedeid" class="form-select" required>
                                    <option value="">Seleccione sede</option>
                                </select>
                            </div>

                            <div class="col-md-4">
                                <label class="form-label">Zona</label>
                                <select id="zonaid" name="zonaid" class="form-select" disabled required>
                                    <option value="">Seleccione zona</option>
                                </select>
                            </div>

                        </div>

                        <div class="row mb-3">

                            <div class="col-md-2">
                                <label class="form-label">Pasillo</label>
                                <input type="number" 
                                        class="form-control" 
                                        name="pasillo" 
                                        placeholder="Ej: 1"
                                        min="1"
                                        step="1"
                                        onkeydown="return event.key !== '-'"
                                        required>
                            </div>

                            <div class="col-md-2">
                                <label class="form-label">Sección</label>
                                <input type="text" name="seccion" class="form-control" required>
                            </div>

                            <div class="col-md-2">
                                <label class="form-label">Nivel</label>
                                <input type="number" 
                                        class="form-control" 
                                        name="nivel" 
                                        placeholder="Ej: 1"
                                        min="1"
                                        step="1"
                                        onkeydown="return event.key !== '-'"
                                        required>
                            </div>

                            <div class="row">

                                <div class="col-md-6">
                                    <label class="form-label">Código inicial</label>
                                    <input
                                        type="text"
                                        class="form-control"
                                        name="codigo_base"
                                        placeholder="Ej: B01"
                                        pattern="[A-Z]+[0-9]+"
                                        required>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label">Cantidad de ubicaciones</label>
                                    <input
                                        type="number"
                                        class="form-control"
                                        name="cantidad"
                                        placeholder="Ej: 5"
                                        min="1"
                                        step="1"
                                        onkeydown="return event.key !== '-'"
                                        required>
                                </div>

                            </div>

                        </div>

                        <div class="mb-3">
                            <label class="form-label">Descripción</label>
                            <textarea name="descripcion" class="form-control"></textarea>
                        </div>

                        <div class="text-end">
                            <button type="button" class="btn btn-secondary" onclick="toggleForm()">Cancelar</button>
                            <button type="submit" class="btn btn-success">Guardar Ubicación</button>
                        </div>

                    </form>

                </div>

                <!-- TABLA -->
                <div class="card-body">
                    <table class="table table-bordered" id="tableUbicaciones">
                        <thead>
                            <tr>
                                <th>Sede</th>
                                <th>Zona</th>
                                <th>Pasillo</th>
                                <th>Sección</th>
                                <th>Nivel</th>
                                <th>Lugar</th>
                                <th>Estado</th>
                                <th>fecha de creación</th>
                            </tr>
                        </thead>
                    </table>
                </div>

            </div>

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
<!-- end main content-->
<?php footerAdmin($data); ?>