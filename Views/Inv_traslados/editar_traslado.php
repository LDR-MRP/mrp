<?php headerAdmin($data); ?>

<div id="contentAjax"></div>

<div class="main-content">

    <div class="page-content">

        <div class="container-fluid">

            <!-- ============================================= -->
            <!-- BREADCRUMB -->
            <!-- ============================================= -->

            <div class="row">
                <div class="col-12">

                    <div class="page-title-box d-sm-flex align-items-center justify-content-between">

                        <h4 class="mb-sm-0">
                            Editar Solicitud de Traslado
                        </h4>

                        <div class="page-title-right">
                            <ol class="breadcrumb m-0">

                                <li class="breadcrumb-item">
                                    <a href="<?= base_url(); ?>">
                                        Dashboard
                                    </a>
                                </li>

                                <li class="breadcrumb-item">
                                    <a href="<?= base_url(); ?>/inv_traslados">
                                        Traslados
                                    </a>
                                </li>

                                <li class="breadcrumb-item active">
                                    Editar Solicitud
                                </li>

                            </ol>
                        </div>

                    </div>

                </div>
            </div>

            <!-- ============================================= -->
            <!-- HEADER -->
            <!-- ============================================= -->

            <div class="row mb-4">

                <div class="col-lg-8">

                    <div class="d-flex align-items-center">

                        <div class="avatar-lg flex-shrink-0">

                            <div class="avatar-title rounded-circle bg-warning text-white fs-1">
                                <i class="ri-truck-line"></i>
                            </div>

                        </div>

                        <div class="ms-3">

                            <h1 class="mb-1">
                                Editar solicitud de traslado
                            </h1>

                            <p class="text-muted mb-0">
                                Folio: <strong id="lblFolio">—</strong>
                            </p>

                            <p class="text-muted mb-0 mt-1">
                                <span class="text-danger">*</span> Campos obligatorios
                            </p>

                        </div>

                    </div>

                </div>

            </div>

            <!-- ============================================= -->
            <!-- AVISO: SOLO EDITABLE MIENTRAS ESTÉ EN SOLICITUD -->
            <!-- ============================================= -->

            <div class="alert alert-info" role="alert">
                <i class="ri-information-line me-1"></i>
                Solo se puede editar un traslado mientras esté en estado
                <strong>Solicitud</strong> (antes de confirmar la salida).
                En cuanto se registra la salida, la información deja de ser editable.
            </div>

            <!-- ============================================= -->
            <!-- DATOS GENERALES -->
            <!-- ============================================= -->
            <form id="formTraslado" method="POST" enctype="multipart/form-data">

                <input
                    type="hidden"
                    id="idtraslado"
                    name="idtraslado"
                    value="<?= (int) ($data['idtraslado'] ?? 0); ?>">

                <div class="card">

                    <div class="card-header">
                        <h4 class="card-title mb-0">
                            Datos Generales
                        </h4>
                    </div>

                    <div class="card-body">

                        <div class="row">

                            <div class="col-md-6 mb-3">
                                <label class="form-label">
                                    Tipo de traslado <span class="text-danger">*</span>
                                </label>

                                <select
                                    id="tipo_traslado"
                                    name="tipo_traslado"
                                    class="form-select"
                                    required>

                                    <option value="">
                                        Seleccione...
                                    </option>

                                    <option value="madrina">
                                        Madrina
                                    </option>

                                    <option value="rodando">
                                        Rodando
                                    </option>

                                    <option value="grua">
                                        Grúa
                                    </option>

                                </select>
                            </div>

                            <div class="col-md-6 mb-3">

                                <label class="form-label">
                                    Fecha programada <span class="text-danger">*</span>
                                </label>

                                <input
                                    type="date"
                                    class="form-control"
                                    id="fecha_programada"
                                    name="fecha_programada"
                                    required>

                            </div>

                        </div>

                    </div>

                </div>



                <input
                    type="hidden"
                    id="unidades_json"
                    name="unidades_json">

                <div class="row">

                    <!-- ================================================= -->
                    <!-- IZQUIERDA -->
                    <!-- ================================================= -->

                    <div class="col-lg-8">

                        <!-- ============================================= -->
                        <!-- RUTA DE TRASLADO -->
                        <!-- ============================================= -->

                        <div class="card">

                            <div class="card-header">
                                <h4 class="card-title mb-0">
                                    Ruta del Traslado
                                </h4>
                            </div>

                            <div class="card-body">

                                <div class="row">

                                    <div class="col-md-6">

                                        <label class="form-label">
                                            Almacén Origen <span class="text-danger">*</span>
                                        </label>

                                        <select
                                            id="almacen_origenid"
                                            name="almacen_origenid"
                                            class="form-control"
                                            required>
                                        </select>

                                    </div>

                                    <div class="col-md-6">

                                        <label class="form-label">
                                            Almacén Destino <span class="text-danger">*</span>
                                        </label>

                                        <select
                                            id="almacen_destinoid"
                                            name="almacen_destinoid"
                                            class="form-control"
                                            required>
                                        </select>

                                    </div>

                                </div>

                            </div>

                        </div>

                        <!-- ============================================= -->
                        <!-- TRANSPORTISTA -->
                        <!-- ============================================= -->

                        <div class="card">

                            <div class="card-header">
                                <h4 class="card-title mb-0">
                                    Transportista
                                </h4>
                            </div>

                            <div class="card-body">

                                <div class="row">

                                    <div class="col-md-12">

                                        <label class="form-label">
                                            Proveedor
                                        </label>

                                        <select
                                            id="id_proveedor"
                                            name="id_proveedor"
                                            class="form-select">

                                        </select>

                                    </div>

                                </div>

                            </div>

                            <div class="card mt-3">
                                <div class="card-header">
                                    <i class="fa-solid fa-id-card me-2"></i>
                                    Datos del Trasladista
                                </div>

                                <div class="card-body">

                                    <div class="row">

                                        <div class="col-md-8 mb-3">
                                            <label class="form-label">
                                                Nombre completo <span class="text-danger">*</span>
                                            </label>

                                            <input
                                                type="text"
                                                class="form-control"
                                                id="nombre_trasladista"
                                                name="nombre_trasladista"
                                                required>
                                        </div>

                                        <div class="col-md-4 mb-3">
                                            <label class="form-label">
                                                Licencia
                                            </label>

                                            <input
                                                type="text"
                                                class="form-control"
                                                id="numero_licencia"
                                                name="numero_licencia">
                                        </div>

                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">
                                                Contacto
                                            </label>

                                            <input
                                                type="email"
                                                class="form-control"
                                                id="correo_trasladista"
                                                name="correo_trasladista"
                                                placeholder="Telefono o email">
                                        </div>

                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">
                                                Vigencia de licencia
                                            </label>

                                            <input
                                                type="date"
                                                class="form-control"
                                                id="vigencia_licencia"
                                                name="vigencia_licencia">
                                        </div>

                                        <div class="col-md-12 mb-3">
                                            <label class="form-label">
                                                Adjuntar licencia
                                            </label>

                                            <input
                                                type="file"
                                                class="form-control"
                                                name="archivo_licencia"
                                                accept=".pdf,.jpg,.jpeg,.png">

                                            <div class="form-text" id="lblArchivoActual"></div>
                                        </div>

                                    </div>

                                </div>
                            </div>

                        </div>
                        <!-- ============================================= -->
                        <!-- UNIDADES A TRASLADAR -->
                        <!-- ============================================= -->
                        <div class="card">

                            <div class="card-header d-flex justify-content-between align-items-center">

                                <h4 class="card-title mb-0">
                                    Unidades a Trasladar
                                </h4>

                                <button
                                    type="button"
                                    class="btn btn-success"
                                    id="btnAgregarUnidad">

                                    <i class="ri-add-line me-1"></i>
                                    Agregar Unidad

                                </button>

                            </div>

                            <div class="card-body">

                                <div class="table-responsive">

                                    <table class="table table-bordered align-middle" id="tableUnidades">

                                        <thead class="table-light">
                                            <tr>
                                                <th style="min-width:220px">VIN</th>
                                                <th>Unidad</th>
                                                <th>Almacén Actual</th>
                                                <th>¿Entrega llave?</th>
                                                <th>Tipo de llave</th>
                                                <th></th>
                                            </tr>
                                        </thead>

                                        <tbody>
                                            <tr id="rowSinUnidades"></tr>
                                        </tbody>

                                    </table>

                                </div>

                            </div>

                        </div>

                        <!-- ============================================= -->
                        <!-- OBSERVACIONES -->
                        <!-- ============================================= -->

                        <div class="card">

                            <div class="card-header">
                                <h4 class="card-title mb-0">
                                    Observaciones
                                </h4>
                            </div>

                            <div class="card-body">

                                <textarea
                                    class="form-control"
                                    rows="4"
                                    id="observaciones"
                                    name="observaciones"></textarea>

                            </div>

                        </div>
                    </div>

                    <!-- ================================================= -->
                    <!-- DERECHA -->
                    <!-- ================================================= -->

                    <div class="col-lg-4">

                        <div class="card">

                            <div class="card-header">
                                <h4 class="card-title mb-0">
                                    Acciones Disponibles
                                </h4>
                            </div>

                            <div class="card-body">

                                <button
                                    type="submit"
                                    id="btnGuardarCambios"
                                    class="btn btn-success w-100 mb-2">

                                    <i class="ri-save-line me-1"></i>

                                    Guardar cambios

                                </button>

                                <a
                                    href="<?= base_url(); ?>/inv_traslados"
                                    class="btn btn-soft-danger w-100">

                                    Cancelar y Volver

                                </a>

                            </div>

                        </div>

                    </div>

                </div>

            </form>

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
<?php footerAdmin($data); ?>
