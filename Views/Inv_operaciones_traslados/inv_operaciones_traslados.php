<?php headerAdmin($data); ?>

<div id="contentAjax"></div>

<div class="main-content">

    <div class="page-content">

        <div class="container-fluid">

            <!-- ============================================= -->
            <!-- HEADER -->
            <!-- ============================================= -->

            <div class="row mb-3">

                <div class="col-12">

                    <div class="card border-0 shadow-sm">

                        <div class="card-body text-center">

                            <div class="avatar-lg mx-auto mb-3">

                                <div class="avatar-title bg-primary rounded-circle fs-1">
                                    <i class="ri-qr-scan-2-line"></i>
                                </div>

                            </div>

                            <h3 class="mb-1">
                                Operación de Traslados
                            </h3>

                            <p class="text-muted mb-0">
                                Escanee el QR o capture el folio manualmente
                            </p>

                        </div>

                    </div>

                </div>

            </div>

            <!-- ============================================= -->
            <!-- BUSQUEDA -->
            <!-- ============================================= -->

            <div class="row">

                <div class="col-12">

                    <div class="card">

                        <div class="card-body">

                            <label class="form-label fw-semibold">
                                Folio del traslado
                            </label>

                            <div class="input-group">

                                <input
                                    type="text"
                                    id="folioBusqueda"
                                    class="form-control form-control-lg"
                                    placeholder="TRU-20260805001">

                                <button
                                    type="button"
                                    class="btn btn-primary"
                                    id="btnBuscarTraslado">

                                    <i class="ri-search-line"></i>

                                </button>

                            </div>

                            <div class="d-grid mt-3">

                                <button
                                    class="btn btn-success btn-lg"
                                    id="btnEscanearQR">

                                    <i class="ri-qr-scan-line me-1"></i>

                                    Escanear QR

                                </button>

                            </div>

                        </div>

                    </div>

                </div>

            </div>

            <!-- ============================================= -->
            <!-- QR SCANNER -->
            <!-- ============================================= -->

            <div
                class="card d-none"
                id="cardScanner">

                <div class="card-body">

                    <div id="reader"></div>

                    <div class="d-grid mt-2">
                        <button
                            type="button"
                            class="btn btn-outline-danger btn-sm"
                            id="btnCerrarScanner">
                            Cerrar escáner
                        </button>
                    </div>

                </div>

            </div>

            <!-- ============================================= -->
            <!-- DATOS DEL TRASLADO -->
            <!-- ============================================= -->

            <div
                id="cardTraslado"
                class="card d-none">

                <div class="card-header">

                    <h5 class="mb-0">
                        Información del Traslado
                    </h5>

                </div>

                <div class="card-body">

                    <div class="row g-3">

                        <div class="col-md-3">

                            <label class="text-muted">
                                Folio
                            </label>

                            <div
                                id="lblFolio"
                                class="fw-bold">
                            </div>

                        </div>

                        <div class="col-md-3">

                            <label class="text-muted">
                                Origen
                            </label>

                            <div
                                id="lblOrigen"
                                class="fw-bold">
                            </div>

                        </div>

                        <div class="col-md-3">

                            <label class="text-muted">
                                Destino
                            </label>

                            <div
                                id="lblDestino"
                                class="fw-bold">
                            </div>

                        </div>

                        <div class="col-md-3">

                            <label class="text-muted">
                                Estado
                            </label>

                            <div id="lblEstado"></div>

                        </div>

                    </div>

                </div>

            </div>

            <!-- ============================================= -->
            <!-- ESCANEO VIN -->
            <!-- ============================================= -->

            <div
                id="cardVin"
                class="card d-none">

                <div class="card-header">
                    <h5 class="mb-0">
                        Validación de Unidades
                    </h5>
                </div>

                <div class="card-body">

                    <div class="d-grid mb-3">
                        <button
                            type="button"
                            class="btn btn-secondary btn-lg"
                            id="btnEscanearVin">
                            <i class="ri-qr-scan-line me-1"></i>
                            Escanear VIN
                        </button>
                    </div>

                    <div class="row g-2">
                        <div class="col-md-9">
                            <input
                                type="text"
                                id="vinBusqueda"
                                class="form-control form-control-lg"
                                placeholder="Capture VIN">
                        </div>
                        <div class="col-md-3 d-grid">
                            <button
                                id="btnValidarVin"
                                class="btn btn-primary">
                                Validar
                            </button>
                        </div>
                    </div>

                </div>

            </div>

            <!-- Scanner exclusivo de VIN -->
            <div
                class="card d-none"
                id="cardScannerVin">

                <div class="card-body">

                    <div id="readerVin"></div>

                    <div class="d-grid mt-2">
                        <button
                            type="button"
                            class="btn btn-outline-danger btn-sm"
                            id="btnCerrarScannerVin">
                            Cerrar escáner
                        </button>
                    </div>

                </div>

            </div>

            <!-- ============================================= -->
            <!-- UNIDADES -->
            <!-- ============================================= -->

            <div
                id="cardUnidades"
                class="card d-none">

                <div class="card-header">

                    <h5 class="mb-0">

                        Unidades del Traslado

                    </h5>

                </div>

                <div class="card-body p-0">

                    <div id="contenedorUnidades">

                    </div>

                </div>

            </div>

            <!-- ============================================= -->
            <!-- UNIDADES CON ALERTA (RECEPCIÓN) -->
            <!-- ============================================= -->

            <div id="cardUnidadesExtra" class="card d-none border-warning">
                <div class="card-header bg-warning-subtle">
                    <h5 class="mb-0 text-warning-emphasis">
                        <i class="ri-alert-line me-1"></i>
                        Unidades con Alerta (no pertenecen a esta solicitud)
                    </h5>
                </div>
                <div class="card-body p-0">
                    <div id="contenedorUnidadesExtra"></div>
                </div>
            </div>

            <!-- ============================================= -->
            <!-- ACCIONES -->
            <!-- ============================================= -->

            <div
                id="cardAcciones"
                class="card d-none">

                <div class="card-body">

                    <div class="row g-2">

                        <div class="col-md-6 d-grid">

                            <button
                                class="btn btn-warning btn-lg"
                                id="btnRegistrarSalida">

                                <i class="ri-truck-line me-1"></i>

                                Registrar Salida

                            </button>

                        </div>

                        <div class="col-md-6 d-grid">

                            <button
                                class="btn btn-info btn-lg d-none"
                                id="btnRegistrarIngreso">

                                <i class="ri-door-open-line me-1"></i>

                                Registrar Ingreso (Seguridad)

                            </button>

                        </div>

                        <div class="col-md-6 d-grid">

                            <button
                                class="btn btn-success btn-lg d-none"
                                id="btnRegistrarRecepcion">

                                <i class="ri-checkbox-circle-line me-1"></i>

                                Registrar Recepción Interna

                            </button>

                        </div>

                    </div>

                    <div
                        id="avisoIngreso"
                        class="alert alert-info mt-3 d-none">

                        <i class="ri-information-line me-2"></i>

                        La unidad ya ingresó al patio (registrado por seguridad).
                        Falta que una persona interna confirme la recepción y,
                        en su caso, la entrega de la llave.

                    </div>

                </div>

            </div>

        </div>

    </div>

</div>

<script>
    var FOLIO_INICIAL = "<?= isset($data['folio_inicial']) ? addslashes($data['folio_inicial']) : ''; ?>";
</script>

<!-- ============================================= -->
<!-- OVERLAY DE FEEDBACK DE ESCANEO -->
<!-- ============================================= -->

<div id="overlayScan" class="overlay-scan">
    <div class="overlay-scan-content">
        <i id="overlayScanIcon" class="ri-checkbox-circle-fill"></i>
        <div id="overlayScanTexto" class="overlay-scan-texto"></div>
    </div>
</div>

<style>
    .overlay-scan {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        display: none;
        align-items: center;
        justify-content: center;
        z-index: 99999;
        opacity: 0;
        transition: opacity 0.15s ease-in;
        pointer-events: none;
    }

    .overlay-scan.show {
        display: flex;
        opacity: 1;
    }

    .overlay-scan.bg-ok {
        background: rgba(25, 135, 84, 0.92);
    }

    .overlay-scan.bg-error {
        background: rgba(220, 53, 69, 0.92);
    }

    .overlay-scan.bg-info {
        background: rgba(13, 110, 253, 0.92);
    }

    .overlay-scan-content {
        text-align: center;
        color: #fff;
    }

    .overlay-scan-content i {
        font-size: 90px;
        line-height: 1;
        display: block;
        margin-bottom: 10px;
    }

    .overlay-scan-texto {
        font-size: 24px;
        font-weight: 700;
        padding: 0 20px;
    }

    .overlay-scan.bg-alerta {
        background: rgba(255, 145, 0, 0.92);
    }
</style>

<script src="https://unpkg.com/html5-qrcode"></script>

<?php footerAdmin($data); ?>