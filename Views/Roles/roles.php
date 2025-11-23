

<?php headerAdmin($data);
getModal('modalRoles',$data);
?>

  <div id="contentAjax"></div>
        <div class="main-content">

            <div class="page-content">
                <div class="container-fluid">

                    <!-- start page title -->
                    <div class="row">
                        <div class="col-12">
                            <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                                <h4 class="mb-sm-0">Roles</h4>

                                <div class="page-title-right">
                                    <ol class="breadcrumb m-0">
                                        <li class="breadcrumb-item"><a href="javascript: void(0);">MRP</a></li>
                                        <li class="breadcrumb-item active">roles</li>
                                    </ol>
                                </div>

                            </div>
                        </div>
                    </div>
                    <!-- end page title -->

                   <div class="row">
                        <div class="col-lg-12">
                            <div class="card">
                                <div class="card-header border-0">
                                    <div class="row align-items-center gy-3">
                                        <div class="col-sm">
                                            <!-- <h5 class="card-title mb-0">Roles</h5> -->
                                        </div>
                                        <div class="col-sm-auto">
                                            <div class="d-flex gap-1 flex-wrap">
                                                <button type="button" class="btn btn-primary add-btn" onclick="openModal();"><i class="ri-add-line align-bottom me-1" ></i> Crear Rol</button>
                                                <button type="button" class="btn btn-info"><i class="ri-file-download-line align-bottom me-1"></i> Exportar</button>
                                                <button class="btn btn-soft-danger" id="remove-actions" onClick="deleteMultiple()"><i class="ri-delete-bin-2-line"></i></button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="card-body">
                                    <table id="tableRoles" class="table table-bordered dt-responsive nowrap table-striped align-middle" style="width:100%">
                                        <thead>
                                            <tr>
                                                <th>ID</th>
                                                <th>NOMBRE</th>
                                                <th>DESCRIPCIÓN</th>
                                                <th>ESTATUS</th>
                                                <th>Action</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            
                                        </tbody>
                                    </table>
                                </div>
                            </div>
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
    