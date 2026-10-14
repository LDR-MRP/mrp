<?php headerAdmin($data); ?>

<style>
    /* ===== Glosario NIV · estilos del módulo ===== */
    .niv-section-title {
        display: flex;
        align-items: center;
        gap: .5rem;
        font-size: .8rem;
        font-weight: 600;
        letter-spacing: .04em;
        text-transform: uppercase;
        color: var(--vz-secondary-color);
        margin-bottom: 1rem;
    }

    .niv-section-title .seg-dot {
        width: 10px;
        height: 10px;
        border-radius: 3px;
        flex: none;
    }

    .niv-section-title .seg-range {
        margin-left: auto;
        font-weight: 500;
        text-transform: none;
        letter-spacing: 0;
    }

    .niv-section+.niv-section {
        border-top: 1px dashed var(--vz-border-color);
        padding-top: 1.25rem;
        margin-top: .25rem;
    }

    /* chip de posición en cada etiqueta */
    .niv-field-label {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: .5rem;
        margin-bottom: .35rem;
        font-weight: 500;
    }

    .pos-chip {
        display: inline-flex;
        align-items: center;
        border-radius: 4px;
        overflow: hidden;
        font-size: .7rem;
        line-height: 1;
        border: 1px solid var(--vz-border-color);
        flex: none;
    }

    .pos-chip .p {
        padding: 3px 5px;
        color: var(--vz-secondary-color);
        background: var(--vz-tertiary-bg);
    }

    .pos-chip .v {
        padding: 3px 6px;
        min-width: 1.6em;
        text-align: center;
        font-family: var(--vz-font-monospace);
        font-weight: 700;
    }

    .pos-chip.is-empty .v {
        color: var(--vz-secondary-color);
    }

    /* colores por segmento */
    .seg-wmi { --seg-rgb: var(--vz-primary-rgb); }
    .seg-vds { --seg-rgb: var(--vz-success-rgb); }
    .seg-chk { --seg-rgb: var(--vz-warning-rgb); }
    .seg-vis { --seg-rgb: var(--vz-info-rgb); }
    .seg-sec { --seg-rgb: var(--vz-secondary-rgb); }

    .seg-dot { background: rgb(var(--seg-rgb)); }
    .pos-chip:not(.is-empty) .v { color: rgb(var(--seg-rgb)); }

    /* anatomía del NIV */
    .niv-anatomy {
        display: flex;
        gap: 3px;
        justify-content: center;
        flex-wrap: nowrap;
    }

    .niv-cell {
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 4px;
        flex: 1 1 0;
        max-width: 34px;
        min-width: 0;
    }

    .niv-cell .ch {
        width: 100%;
        aspect-ratio: 3 / 4;
        display: grid;
        place-items: center;
        border-radius: 5px;
        font-family: var(--vz-font-monospace);
        font-weight: 700;
        font-size: clamp(.8rem, 1.4vw, 1.05rem);
        background: rgba(var(--seg-rgb), .12);
        color: rgb(var(--seg-rgb));
        border: 1px solid rgba(var(--seg-rgb), .35);
        transition: background .15s, transform .15s;
    }

    .niv-cell.is-empty .ch {
        background: transparent;
        color: var(--vz-secondary-color);
        border-style: dashed;
        border-color: var(--vz-border-color);
    }

    .niv-cell.is-flash .ch {
        transform: translateY(-2px);
        background: rgba(var(--seg-rgb), .28);
    }

    .niv-cell .ix {
        font-size: .62rem;
        color: var(--vz-secondary-color);
        line-height: 1;
    }

    .niv-cell.is-tail .ch {
        background: transparent;
        border-style: dashed;
        border-color: var(--vz-border-color);
        color: var(--vz-secondary-color);
        font-weight: 400;
    }

    .niv-gap { width: 6px; flex: none; }

    .niv-legend {
        display: flex;
        flex-wrap: wrap;
        justify-content: center;
        gap: .35rem 1rem;
        font-size: .72rem;
        color: var(--vz-secondary-color);
    }

    .niv-legend span { display: inline-flex; align-items: center; gap: .35rem; }

    .niv-string {
        font-family: var(--vz-font-monospace);
        font-size: 1.35rem;
        font-weight: 700;
        letter-spacing: .12em;
        word-break: break-all;
    }

    .niv-checklist { list-style: none; padding: 0; margin: 0; }

    .niv-checklist li {
        display: flex;
        align-items: center;
        gap: .6rem;
        padding: .4rem 0;
        font-size: .82rem;
        border-bottom: 1px solid var(--vz-border-color);
    }

    .niv-checklist li:last-child { border-bottom: 0; }
    .niv-checklist .pos { width: 2.6rem; color: var(--vz-secondary-color); font-size: .72rem; }
    .niv-checklist .val { margin-left: auto; font-family: var(--vz-font-monospace); font-weight: 700; }
    .niv-checklist .ok { color: var(--vz-success); }
    .niv-checklist .pending { color: var(--vz-border-color); }

    @media (min-width: 1200px) {
        .niv-sticky { position: sticky; top: 90px; }
    }

    /* tabla */
    .niv-chip {
        display: inline-flex;
        font-family: var(--vz-font-monospace);
        font-weight: 700;
        letter-spacing: .06em;
        font-size: .85rem;
        border-radius: 4px;
        overflow: hidden;
        border: 1px solid var(--vz-border-color);
    }

    .niv-chip span { padding: 2px 5px; color: rgb(var(--seg-rgb)); background: rgba(var(--seg-rgb), .1); }
    #tablaVinWrap tr.is-editing > td { background: rgba(var(--vz-warning-rgb), .08); }

    /* historial del VIN base (línea de tiempo) */
    .hist-list { list-style: none; margin: 0; padding: 0 0 0 1.4rem; position: relative; }
    .hist-list::before { content: ""; position: absolute; left: .45rem; top: .3rem; bottom: .3rem; border-left: 2px solid var(--vz-border-color); }
    .hist-item { position: relative; padding: 0 0 1rem .6rem; }
    .hist-item::before {
        content: ""; position: absolute; left: -1.24rem; top: .3rem; width: 12px; height: 12px; border-radius: 50%;
        background: var(--vz-card-bg, #fff); border: 3px solid var(--vz-warning);
    }
    .hist-cambio { font-family: var(--vz-font-monospace); font-size: .82rem; }
    .hist-cambio del { color: var(--vz-danger); }
    .hist-cambio ins { color: var(--vz-success); text-decoration: none; font-weight: 600; }
</style>

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

            <form id="formVinModelo" novalidate>
                <input type="hidden" name="id" id="id">
                <input type="hidden" name="modelo" id="modelo">
                <input type="hidden" name="vin_base" id="vin_base">
                <!-- Se deriva de la categoría del tipo de vehículo (no se envía) -->
                <input type="hidden" id="tipo_distancia">

                <div class="row">

                    <!-- ================= FORMULARIO ================= -->
                    <div class="col-xl-8">
                        <div class="card">
                            <div class="card-header d-flex align-items-center gap-2">
                                <div class="avatar-xs flex-shrink-0">
                                    <span class="avatar-title rounded bg-primary-subtle text-primary fs-16">
                                        <i class="ri-barcode-box-line"></i>
                                    </span>
                                </div>
                                <div class="flex-grow-1">
                                    <h5 class="card-title mb-0">Configuración del modelo NIV</h5>
                                    <p class="text-muted mb-0 fs-12">Cada dato llena una posición del NIV base (posiciones 1 a 11)</p>
                                </div>
                                <span id="modoBadge" class="badge bg-success-subtle text-success fs-12">
                                    <i class="ri-add-line align-bottom"></i> Nuevo
                                </span>
                            </div>

                            <div class="card-body">

                                <!-- PRODUCTO -->
                                <div class="niv-section">
                                    <div class="niv-section-title">
                                        <i class="ri-car-line fs-16"></i> Producto
                                    </div>
                                    <div class="row g-3">
                                        <div class="col-md-8">
                                            <label class="form-label" for="id_inventario">Modelo (Ingeniería / Inventario)</label>
                                            <select class="form-select" name="id_inventario" id="id_inventario" required>
                                                <option value="">Seleccionar</option>
                                            </select>
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label" for="estado">Estado</label>
                                            <select class="form-select" name="estado" id="estado">
                                                <option value="2" selected>Activo</option>
                                                <option value="1">Inactivo</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div id="ingResumen" class="d-none"></div>
                                </div>

                                <!-- WMI -->
                                <div class="niv-section">
                                    <div class="niv-section-title seg-wmi">
                                        <span class="seg-dot"></span> Fabricante · WMI
                                        <span class="seg-range">Posiciones 1–3</span>
                                    </div>
                                    <div class="row g-3">
                                        <div class="col-md-8">
                                            <div class="niv-field-label seg-wmi">
                                                <label class="mb-0" for="id_fabricante">Fabricante</label>
                                                <span class="pos-chip is-empty" data-pos="wmi"><span class="p">1–3</span><span class="v">---</span></span>
                                            </div>
                                            <select class="form-select" name="id_fabricante" id="id_fabricante" required>
                                                <option value="">Seleccionar</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>

                                <!-- VDS -->
                                <div class="niv-section">
                                    <div class="niv-section-title seg-vds">
                                        <span class="seg-dot"></span> Descripción del vehículo · VDS
                                        <span class="seg-range">Posiciones 4–8</span>
                                    </div>
                                    <div class="row g-3">
                                        <div class="col-md-6">
                                            <div class="niv-field-label seg-vds">
                                                <label class="mb-0" for="id_tipo_vehiculo">Tipo de vehículo</label>
                                                <span class="pos-chip is-empty" data-pos="4"><span class="p">Pos. 4</span><span class="v">-</span></span>
                                            </div>
                                            <select class="form-select" name="id_tipo_vehiculo" id="id_tipo_vehiculo" required>
                                                <option value="">Seleccionar</option>
                                            </select>
                                        </div>

                                        <div class="col-md-6">
                                            <div class="niv-field-label seg-vds">
                                                <label class="mb-0" for="peso_bruto_kg">Peso bruto vehicular</label>
                                                <span class="pos-chip is-empty" data-pos="5"><span class="p">Pos. 5</span><span class="v">-</span></span>
                                            </div>
                                            <div class="input-group">
                                                <input type="number" class="form-control" name="peso_bruto_kg" id="peso_bruto_kg" min="1" placeholder="Ej. 2800" required>
                                                <span class="input-group-text">kg</span>
                                            </div>
                                        </div>

                                        <div class="col-md-6">
                                            <div class="niv-field-label seg-vds">
                                                <label class="mb-0" for="id_tipo_motor">Tipo de motor</label>
                                                <span class="pos-chip is-empty" data-pos="6"><span class="p">Pos. 6</span><span class="v">-</span></span>
                                            </div>
                                            <select class="form-select" name="id_tipo_motor" id="id_tipo_motor" required>
                                                <option value="">Seleccionar</option>
                                            </select>
                                        </div>

                                        <div class="col-md-6">
                                            <div class="niv-field-label seg-vds">
                                                <label class="mb-0" for="potencia_hp">Potencia</label>
                                                <span class="pos-chip is-empty" data-pos="7"><span class="p">Pos. 7</span><span class="v">-</span></span>
                                            </div>
                                            <div class="input-group">
                                                <input type="number" class="form-control" name="potencia_hp" id="potencia_hp" min="1" placeholder="Ej. 150" required>
                                                <span class="input-group-text">HP</span>
                                            </div>
                                        </div>

                                        <div class="col-md-6">
                                            <div class="niv-field-label seg-vds">
                                                <label class="mb-0" for="distancia_ejes" id="label_distancia">Distancia entre ejes</label>
                                                <span class="pos-chip is-empty" data-pos="8"><span class="p">Pos. 8</span><span class="v">-</span></span>
                                            </div>
                                            <div class="input-group">
                                                <input type="number" step="1" class="form-control" name="distancia_ejes" id="distancia_ejes" required disabled>
                                                <span class="input-group-text" id="unidad_distancia">—</span>
                                            </div>
                                            <small class="text-muted" id="help_distancia">
                                                <i class="ri-information-line align-bottom"></i> Seleccione primero el tipo de vehículo
                                            </small>
                                        </div>
                                    </div>
                                </div>

                                <!-- VIS -->
                                <div class="niv-section">
                                    <div class="niv-section-title seg-vis">
                                        <span class="seg-dot"></span> Identificación · VIS
                                        <span class="seg-range">Posiciones 10–11</span>
                                    </div>
                                    <div class="row g-3">
                                        <div class="col-md-6">
                                            <div class="niv-field-label seg-vis">
                                                <label class="mb-0" for="anio">Año modelo</label>
                                                <span class="pos-chip is-empty" data-pos="10"><span class="p">Pos. 10</span><span class="v">-</span></span>
                                            </div>
                                            <select class="form-select" name="anio" id="anio" required>
                                                <option value="">Seleccionar</option>
                                            </select>
                                        </div>

                                        <div class="col-md-6">
                                            <div class="niv-field-label seg-vis">
                                                <label class="mb-0" for="id_planta">Planta</label>
                                                <span class="pos-chip is-empty" data-pos="11"><span class="p">Pos. 11</span><span class="v">-</span></span>
                                            </div>
                                            <select class="form-select" name="id_planta" id="id_planta" required>
                                                <option value="">Seleccionar</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>

                            </div>
                        </div>
                    </div>

                    <!-- ================= VISTA PREVIA ================= -->
                    <div class="col-xl-4">
                        <div class="card niv-sticky">
                            <div class="card-header">
                                <h5 class="card-title mb-0">NIV base</h5>
                            </div>
                            <div class="card-body">

                                <div class="niv-anatomy mb-2" id="nivAnatomy"></div>

                                <div class="niv-legend mb-3">
                                    <span class="seg-wmi"><span class="seg-dot" style="width:8px;height:8px;border-radius:2px"></span>WMI</span>
                                    <span class="seg-vds"><span class="seg-dot" style="width:8px;height:8px;border-radius:2px"></span>VDS</span>
                                    <span class="seg-chk"><span class="seg-dot" style="width:8px;height:8px;border-radius:2px"></span>Verificador</span>
                                    <span class="seg-vis"><span class="seg-dot" style="width:8px;height:8px;border-radius:2px"></span>VIS</span>
                                    <span class="seg-sec"><span class="seg-dot" style="width:8px;height:8px;border-radius:2px"></span>Consecutivo</span>
                                </div>

                                <div class="text-center p-3 rounded bg-light mb-3">
                                    <div class="niv-string" id="vinPreview">-----------</div>
                                    <div class="text-muted fs-11 mt-1">
                                        Pos. 9 (verificador) y 12–17 (consecutivo) se calculan al generar los VIN
                                    </div>
                                </div>

                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <span class="fs-12 text-muted">Datos completos</span>
                                    <span class="fs-12 fw-semibold" id="nivProgresoTxt">0 de 9</span>
                                </div>
                                <div class="progress progress-sm mb-3">
                                    <div class="progress-bar bg-success" id="nivProgreso" role="progressbar" style="width:0%"></div>
                                </div>

                                <ul class="niv-checklist mb-4" id="nivChecklist"></ul>

                                <div class="d-grid gap-2">
                                    <button type="submit" id="btnGuardar" class="btn btn-success" disabled>
                                        <i class="ri-save-3-line align-bottom me-1"></i> Guardar modelo NIV
                                    </button>
                                    <button type="button" id="btnCancelar" class="btn btn-light d-none">
                                        <i class="ri-close-line align-bottom me-1"></i> Cancelar edición
                                    </button>
                                </div>

                            </div>
                        </div>
                    </div>

                </div>
            </form>

            <!-- ================= LISTADO ================= -->
            <div class="row">
                <div class="col-12">
                    <div class="card" id="tablaVinWrap">
                        <div class="card-header d-flex flex-wrap align-items-center gap-2">
                            <h5 class="card-title mb-0 flex-grow-1">
                                Modelos NIV registrados
                                <span class="badge bg-primary-subtle text-primary ms-1 align-middle" id="totalModelos">0</span>
                            </h5>
                            <div class="search-box" style="min-width:260px">
                                <input type="text" class="form-control form-control-sm" id="buscarModelo" placeholder="Buscar modelo, NIV, año o planta...">
                                <i class="ri-search-line search-icon"></i>
                            </div>
                        </div>

                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-hover align-middle table-nowrap mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th class="ps-3">Modelo</th>
                                            <th>NIV base</th>
                                            <th>Año</th>
                                            <th>Planta</th>
                                            <th>Estado</th>
                                            <th class="text-end pe-3">Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody id="tablaVin">
                                        <tr>
                                            <td colspan="6" class="text-center text-muted py-4">Cargando...</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div>
        <!-- container-fluid -->
    </div>
    <!-- End Page-content -->

    <!-- ================= MODAL HISTORIAL VIN BASE ================= -->
    <div class="modal fade" id="modalHistorialModelo" tabindex="-1">
        <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header border-bottom">
                    <div>
                        <h5 class="modal-title mb-0">Historial del VIN base</h5>
                        <p class="text-muted mb-0 fs-12" id="historialModeloTitulo"></p>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="historialModeloBody"></div>
            </div>
        </div>
    </div>

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
