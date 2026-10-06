<?php headerAdmin($data);
$puedeEditar = !empty($_SESSION['permisosMod']['u']);
?>
<div id="contentAjax"></div>
<div class="main-content">

    <div class="page-content">
        <div class="container-fluid">

            <!-- BREADCRUMB -->
            <div class="row align-items-center mb-4">
                <div class="col-12">
                    <div class="page-title-box d-sm-flex align-items-center justify-content-between shadow-sm rounded px-3 py-2 bg-transparent">
                        <div class="page-title-right">
                            <ol class="breadcrumb m-0 fs-13">
                                <li class="breadcrumb-item"><a href="javascript: void(0);">Ingeniería</a></li>
                                <li class="breadcrumb-item active text-primary"><?= $data['page_tag'] ?></li>
                            </ol>
                        </div>
                    </div>
                </div>
            </div>

            <!-- HEADER -->
            <div class="row align-items-center mb-4">
                <div class="col-12">
                    <div class="d-flex align-items-center">
                        <div class="avatar-md me-4">
                            <span class="avatar-title text-white rounded-circle fs-2 shadow-lg border border-light" style="background-color: #405189 !important;">
                                <i class="ri-award-fill"></i>
                            </span>
                        </div>
                        <div>
                            <h3 class="mb-1 fw-bold text-uppercase ls-1 text-body">Certificaciones - Jurídico</h3>
                            <p class="text-muted mb-0 fs-14">
                                Selecciona una configuración de vehículo para cargar sus certificaciones: archivo, número, fechas y estado.
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- TABLA -->
            <div class="row">
                <div class="col-12">
                    <div class="card border-0 shadow-sm rounded-3">
                        <div class="card-body">
                            <div class="table-responsive">
                                <table id="tableJuridico" class="table table-hover table-bordered nowrap table-striped align-middle" style="width:100%">
                                    <thead class="bg-light">
                                        <tr>
                                            <th class="text-uppercase text-muted fs-11 fw-bold ls-1 py-3">SEGMENTO</th>
                                            <th class="text-uppercase text-muted fs-11 fw-bold ls-1 py-3">MODELO</th>
                                            <th class="text-uppercase text-muted fs-11 fw-bold ls-1 py-3">NOMBRE UNIDAD</th>
                                            <th class="text-uppercase text-muted fs-11 fw-bold ls-1 py-3">CLAVE VEHICULAR</th>
                                            <th class="text-uppercase text-muted fs-11 fw-bold ls-1 py-3">ESTADO</th>
                                            <th class="text-uppercase text-muted fs-11 fw-bold ls-1 py-3">CERTIFICACIONES</th>
                                            <th class="text-uppercase text-muted fs-11 fw-bold ls-1 py-3">ACCIÓN</th>
                                        </tr>
                                    </thead>
                                    <tbody></tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

<style>
    /* ===== Modal de certificaciones (Ing_juridico) ===== */
    #modalCertificacionesJur .modal-body {
        background: linear-gradient(180deg, #c3ccdb 0%, #cfd7e3 100%) !important;
    }
    #modalCertificacionesJur .min-w-0 { min-width: 0; }

    /* Leyenda */
    #modalCertificacionesJur .cert-leyenda {
        display: flex; flex-wrap: wrap; gap: .5rem; align-items: center;
        margin-bottom: .9rem; font-size: .8rem; color: #495057;
    }
    #modalCertificacionesJur .cert-leyenda .chip {
        display: inline-flex; align-items: center; gap: .4rem;
        background: #fff; border: 1px solid #cfd7e4; border-radius: 50rem;
        padding: .25rem .75rem; font-weight: 600; box-shadow: 0 1px 2px rgba(33,45,70,.06);
    }
    #modalCertificacionesJur .cert-leyenda .dot { width: .6rem; height: .6rem; border-radius: 50%; }
    #modalCertificacionesJur .cert-leyenda .num {
        background: #f3f6f9; border-radius: 50rem; padding: 0 .45rem; font-size: .75rem;
    }

    /* Tarjeta */
    #modalCertificacionesJur .cert-card {
        --cert-color: #adb5bd; --cert-tint: #adb5bd1f;
        background: #fff;
        border: 1px solid #cfd7e4 !important;
        border-top: 4px solid var(--cert-color) !important;
        border-radius: .75rem;
        box-shadow: 0 3px 10px rgba(33, 45, 70, .10);
        overflow: hidden;
        transition: box-shadow .2s ease, transform .2s ease, border-color .2s ease;
    }
    #modalCertificacionesJur .cert-card:hover {
        box-shadow: 0 10px 24px rgba(33, 45, 70, .18);
        transform: translateY(-2px);
    }

    /* Encabezado */
    #modalCertificacionesJur .cert-header {
        display: flex; justify-content: space-between; align-items: flex-start; gap: .5rem;
        background: linear-gradient(135deg, var(--cert-tint) 0%, #ffffff 85%) !important;
        border-bottom: 1px solid #e3e8ef; padding: .8rem 1rem;
    }
    #modalCertificacionesJur .cert-header h6 { font-weight: 700; color: #1f2937; letter-spacing: .2px; }
    #modalCertificacionesJur .cert-sub { color: #6b7280; font-size: .75rem; }
    #modalCertificacionesJur .cert-icono {
        width: 2.4rem; height: 2.4rem; flex-shrink: 0;
        display: inline-flex; align-items: center; justify-content: center;
        border-radius: .6rem; font-size: 1.25rem; color: #fff;
        background: var(--cert-color); box-shadow: 0 3px 8px var(--cert-tint);
    }
    #modalCertificacionesJur .cert-pill-estado {
        color: #fff; font-size: .65rem; font-weight: 700; letter-spacing: .5px;
        text-transform: uppercase; border-radius: 50rem; padding: .2rem .6rem;
    }
    #modalCertificacionesJur .cert-switch .form-check-label { font-size: .75rem; font-weight: 600; color: #495057; }
    #modalCertificacionesJur .cert-switch .form-check-input:checked { background-color: #405189; border-color: #405189; }

    /* Cuerpo */
    #modalCertificacionesJur .cert-card .card-body { padding: .9rem 1rem; }
    #modalCertificacionesJur .cert-label {
        display: flex; align-items: center; gap: .3rem;
        font-size: .72rem; font-weight: 600; color: #6b7280;
        text-transform: uppercase; letter-spacing: .3px; margin-bottom: .25rem;
    }
    #modalCertificacionesJur .cert-label i { color: var(--cert-color); font-size: .85rem; }
    #modalCertificacionesJur .cert-card .form-control,
    #modalCertificacionesJur .cert-card .form-select { background-color: #f8fafc; border-color: #d5dce6; }
    #modalCertificacionesJur .cert-card .form-control:focus,
    #modalCertificacionesJur .cert-card .form-select:focus {
        background-color: #fff; border-color: #405189; box-shadow: 0 0 0 .15rem rgba(64,81,137,.18);
    }
    #modalCertificacionesJur .cert-vigencia {
        background: #f3f6fa; border: 1px solid #e3e8ef; border-radius: .5rem; padding: .5rem .65rem .65rem;
    }
    #modalCertificacionesJur .cert-vigencia-titulo {
        font-size: .7rem; font-weight: 700; color: #405189; text-transform: uppercase;
        letter-spacing: .5px; margin-bottom: .3rem; display: flex; align-items: center; gap: .3rem;
    }

    /* Pie */
    #modalCertificacionesJur .cert-footer {
        background: #f3f6f9 !important; border-top: 1px dashed #cfd7e4; padding: .6rem 1rem;
    }
    #modalCertificacionesJur .cert-clip {
        width: 1.9rem; height: 1.9rem; flex-shrink: 0; border-radius: 50%;
        display: inline-flex; align-items: center; justify-content: center;
        background: #fff; border: 1px solid #d5dce6; color: #6b7280;
    }
    #modalCertificacionesJur .cert-sin-archivo { background: #e9ecef; color: #6c757d; font-weight: 600; }

    /* Opcional (no obligatoria): más tenue */
    #modalCertificacionesJur .cert-card.cert-opcional { opacity: .82; }
    #modalCertificacionesJur .cert-card.cert-opcional:hover { opacity: 1; }

    /* Obligatoria completa */
    #modalCertificacionesJur .cert-card.cert-completa { box-shadow: 0 0 0 2px #0ab39c55, 0 3px 10px rgba(33,45,70,.10); }

    /* Obligatoria INCOMPLETA: roja */
    #modalCertificacionesJur .cert-card.cert-incompleta {
        border-color: #f06548 !important; background: #fff6f5;
        box-shadow: 0 0 0 2px #f0654866, 0 4px 14px rgba(240,101,72,.25);
        animation: certPulso 2.2s ease-in-out infinite;
    }
    #modalCertificacionesJur .cert-card.cert-incompleta .cert-header {
        background: linear-gradient(135deg, #fde3de 0%, #fff6f5 85%) !important; border-bottom-color: #f8c7bd;
    }
    #modalCertificacionesJur .cert-card.cert-incompleta .cert-footer { background: #fdebe8 !important; border-top-color: #f8c7bd; }
    #modalCertificacionesJur .cert-card.cert-incompleta .cert-vigencia { background: #fdeeeb; border-color: #f8c7bd; }
    #modalCertificacionesJur .cert-alerta {
        display: flex; align-items: center; gap: .45rem;
        background: #f06548; color: #fff; font-size: .78rem; padding: .4rem 1rem;
    }
    #modalCertificacionesJur .cert-alerta i { font-size: 1rem; }
    @keyframes certPulso {
        0%, 100% { box-shadow: 0 0 0 2px #f0654866, 0 4px 14px rgba(240,101,72,.20); }
        50%      { box-shadow: 0 0 0 4px #f0654833, 0 4px 18px rgba(240,101,72,.35); }
    }

    #modalCertificacionesJur .cert-vacio {
        text-align: center; color: #6b7280; background: #fff; border: 2px dashed #cfd7e4;
        border-radius: .75rem; padding: 2.5rem 1rem;
    }
    #modalCertificacionesJur .cert-vacio i { font-size: 2.5rem; color: #adb5bd; display: block; margin-bottom: .5rem; }
</style>

<!-- MODAL CERTIFICACIONES -->
<div class="modal fade" id="modalCertificacionesJur" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header bg-light">
                <div class="d-flex align-items-center">
                    <div class="avatar-sm me-3">
                        <span class="avatar-title rounded-circle fs-3 text-white" style="background-color: #405189 !important;">
                            <i class="ri-award-fill"></i>
                        </span>
                    </div>
                    <div>
                        <h5 class="modal-title mb-0" id="tituloModalJur">Certificaciones</h5>
                        <small class="text-muted" id="subtituloModalJur"></small>
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <form id="formCertificacionesJur" autocomplete="off" class="d-flex flex-column overflow-hidden">
                <div class="modal-body bg-body-tertiary">
                    <input type="hidden" id="idConfiguracionJur" value="">

                    <!-- Resumen de la configuración -->
                    <div class="card border-0 shadow-sm mb-3">
                        <div class="card-body py-3">
                            <div class="row g-3 align-items-center" id="resumenConfigJur"></div>
                        </div>
                    </div>

                    <!-- Leyenda de estados de captura -->
                    <div class="cert-leyenda">
                        <span class="chip"><span class="dot" style="background:#0ab39c"></span>Obligatoria completa <span class="num" id="cntCompletaJur">0</span></span>
                        <span class="chip"><span class="dot" style="background:#f06548"></span>Obligatoria incompleta <span class="num" id="cntIncompletaJur">0</span></span>
                        <span class="chip"><span class="dot" style="background:#adb5bd"></span>No obligatoria <span class="num" id="cntOpcionalJur">0</span></span>
                    </div>

                    <!-- Certificaciones (una tarjeta por certificación) -->
                    <div class="row row-cols-1 row-cols-lg-2 g-3" id="contenedorCertificacionesJur"></div>
                </div>
                <div class="modal-footer justify-content-between">
                    <small class="text-muted">
                        <i class="ri-information-line align-middle"></i>
                        Archivos permitidos: PDF, JPG o PNG (máx. 10 MB). Si no eliges un archivo nuevo se conserva el actual.
                    </small>
                    <div class="d-flex gap-2">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cerrar</button>
                        <?php if ($puedeEditar) { ?>
                            <button type="submit" class="btn btn-success btn-label right">
                                <i class="ri-save-line label-icon align-middle fs-16 ms-2"></i>GUARDAR CERTIFICACIONES
                            </button>
                        <?php } ?>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    const JUR_PUEDE_EDITAR = <?= $puedeEditar ? 'true' : 'false' ?>;
</script>

<?php footerAdmin($data); ?>
