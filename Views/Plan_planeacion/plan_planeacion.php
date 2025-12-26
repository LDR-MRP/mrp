<?php headerAdmin($data); ?>
<div id="contentAjax"></div>

<style>
  /* -----------------------------
     SIDE PANEL (UX PRO)
  ------------------------------*/
  .side-panel-fixed { min-height: 680px; }
  .side-hero {
    height: 220px; /* imagen grande fija */
    border: 1px dashed rgba(0,0,0,.10);
    border-radius: 14px;
    display:flex; align-items:center; justify-content:center;
    background: rgba(0,0,0,.015);
    overflow: hidden;
  }
  .side-hero img{
    height: 200px;
    width: 100%;
    object-fit: contain;
  }
  .side-divider {
    height: 1px;
    background: rgba(0,0,0,.08);
    margin: 14px 0;
  }
  .btn-nav.active{
    box-shadow: 0 0.35rem 1rem rgba(0,0,0,.08);
    border-color: rgba(0,0,0,.12) !important;
    transform: translateY(-1px);
  }

  /* -----------------------------
     TOP HEADER “estado” (listas)
  ------------------------------*/
  .view-header {
    border: 1px solid rgba(0,0,0,.08);
    border-radius: 14px;
    padding: 14px 16px;
    background: rgba(0,0,0,.015);
  }

  /* -----------------------------
     STICKY GUARDAR (Nueva)
  ------------------------------*/
  .sticky-actions {
    position: sticky;
    bottom: 0;
    z-index: 10;
    background: #fff;
    border-top: 1px solid rgba(0,0,0,.08);
    padding: 12px 12px;
    border-radius: 0 0 14px 14px;
  }
</style>

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
                <li class="breadcrumb-item"><a href="javascript:void(0);">MRP</a></li>
                <li class="breadcrumb-item active"><?= $data['page_tag'] ?></li>
              </ol>
            </div>
          </div>
        </div>
      </div>
      <!-- end page title -->

      <!-- =========================
           HOME (Panel + Bienvenida)
      ========================== -->
      <div class="row g-3" id="viewHome">

        <!-- SIDE PANEL -->
        <div class="col-12 col-xxl-3">
          <div class="card h-100">
            <div class="card-body d-flex flex-column p-3 side-panel-fixed">

              <!-- CTA principal -->
              <button type="button" class="btn btn-primary w-100" id="btnNuevaPlaneacion">
                <i class="ri-add-circle-line me-1"></i> Nueva Planeación
              </button>
              <div class="text-muted small mt-2">
                <i class="ri-information-line me-1"></i>
                Flujo: Producto → Ruta → Operadores → Guardar.
              </div>

              <div class="side-divider"></div>

              <!-- Listados -->
              <div class="small text-muted fw-semibold mb-2">
                <i class="ri-list-check-2 me-1"></i> Listados
              </div>

              <div class="d-grid gap-2">
                <button type="button" class="btn btn-outline-warning w-100 btn-nav" id="btnPendientes">
                  <i class="ri-time-line me-1"></i> Pendientes
                </button>
                <button type="button" class="btn btn-outline-success w-100 btn-nav" id="btnFinalizadas">
                  <i class="ri-checkbox-circle-line me-1"></i> Finalizadas
                </button>
                <button type="button" class="btn btn-outline-danger w-100 btn-nav" id="btnCanceladas">
                  <i class="ri-close-circle-line me-1"></i> Canceladas
                </button>
              </div>

              <!-- Imagen fija grande -->
              <div class="mt-auto pt-3">
                <div class="side-hero">
                  <img src="<?= media(); ?>/minimal/images/task.png" alt="Tarea">
                </div>
              </div>

            </div>
          </div>
        </div>

        <!-- Bienvenida -->
        <div class="col-12 col-xxl-9">
          <div class="card h-100">
            <div class="card-body text-center py-5">
              <i class="ri-layout-2-line fs-1 text-primary"></i>
              <h5 class="fw-bold mt-2 mb-1">Planeación de Producción</h5>
              <p class="text-muted mb-0">
                Presiona <b>Nueva Planeación</b> para capturar una orden o entra a un listado para administrarlas.
              </p>
            </div>
          </div>
        </div>

      </div><!-- /viewHome -->

      <!-- =========================
           VISTA: NUEVA PLANEACIÓN
           (Full + Form + Ruta + Sticky Guardar)
      ========================== -->
      <div class="row g-3 d-none" id="viewNueva">
        <div class="col-12">
          <div class="view-header d-flex flex-wrap align-items-center justify-content-between gap-2">
            <div>
              <div class="d-flex align-items-center gap-2">
                <span class="badge bg-primary-subtle text-primary border">
                  <i class="ri-add-circle-line me-1"></i> Nueva
                </span>
                <span class="text-muted small">Inicio → Planeación → Nueva</span>
              </div>
              <div class="fw-bold mt-1">Captura de Planeación</div>
              <div class="text-muted small">Selecciona un producto para cargar su ruta y asignar operadores.</div>
            </div>

            <div class="d-flex align-items-center gap-2">
              <button class="btn btn-outline-secondary btn-sm" type="button" id="btnVolverHome1">
                <i class="ri-arrow-left-line me-1"></i> Volver
              </button>
            </div>
          </div>
        </div>

        <!-- Formulario (NO mover contenido) -->
        <div class="col-12 col-xxl-6">
          <div class="card h-100">
            <div class="card-body">
              <div class="row g-3">

                <!-- Producto -->
                <div class="col-12">
                  <label class="form-label fw-semibold">Producto</label>
                  <select class="form-select" id="selectProducto" required>
                    <option value="">-- Selecciona producto --</option>
                  </select>
                  <div class="help mt-1">Al seleccionar, se cargan ruta (estaciones) y operadores disponibles.</div>
                </div>

                <!-- Supervisor -->
                <div class="col-12">
                  <label class="form-label fw-semibold">Supervisor</label>
                  <input class="form-control" id="inputSupervisor" list="datalistSupervisores"
                         placeholder="Escribe para buscar supervisor..." autocomplete="off">
                  <datalist id="datalistSupervisores">
                    <option value="SUP-001 - Sergio Ramírez"></option>
                    <option value="SUP-002 - Isaura Hernández"></option>
                    <option value="SUP-003 - Jorge Martínez"></option>
                    <option value="SUP-004 - Astrid Gómez"></option>
                  </datalist>
                  <div class="help mt-1">Ej: “SUP-002 - Isaura Hernández”.</div>
                </div>

                <!-- Prioridad -->
                <div class="col-12">
                  <label class="form-label fw-semibold">Prioridad</label>
                  <select class="form-select" id="selectPrioridad" required>
                    <option value="">-- Selecciona prioridad --</option>
                    <option value="CRITICA">Crítica (Paro de línea / Seguridad / Calidad)</option>
                    <option value="ALTA">Alta (Entrega comprometida / Cliente)</option>
                    <option value="MEDIA">Media (Plan estándar)</option>
                    <option value="BAJA">Baja (Reposición / Stock)</option>
                    <option value="PROTOTIPO">Prototipo / Prueba</option>
                  </select>
                  <div class="help mt-1">Sugerencia: usa “Crítica” para contingencias.</div>
                </div>

                <!-- Cantidad / Inicio / Requerida -->
                <div class="col-12">
                  <div class="row g-3">
                    <div class="col-12 col-md-4">
                      <label class="form-label fw-semibold">Cantidad</label>
                      <input type="number" class="form-control" id="txtCantidad" min="1" value="1" required>
                    </div>
                    <div class="col-12 col-md-4">
                      <label class="form-label fw-semibold">Inicio producción</label>
                      <input type="date" class="form-control" id="fechaInicio" required>
                    </div>
                    <div class="col-12 col-md-4">
                      <label class="form-label fw-semibold">Fecha requerida</label>
                      <input type="date" class="form-control" id="fechaRequerida" required>
                    </div>
                  </div>
                </div>

                <!-- Notas -->
                <div class="col-12">
                  <label class="form-label fw-semibold">Notas / Observaciones</label>
                  <textarea class="form-control" id="txtNotas" rows="3"
                    placeholder="Ej: lote especial, cambio de ingeniería, requerimiento de calidad, etc."></textarea>
                </div>

                <!-- Recomendación -->
                <div class="col-12">
                  <div class="alert alert-light border mb-0">
                    <div class="small text-muted mb-1">
                      <i class="ri-information-line me-1"></i> Recomendación automotriz
                    </div>
                    <div class="small">
                      Considera agregar después: <b>Turno</b>, <b>Cliente/Programa</b>, <b>Orden de Producción</b>,
                      <b>Lote</b> y <b>Revisión de ingeniería</b>.
                    </div>
                  </div>
                </div>

              </div><!-- /row -->
            </div>

            <!-- ✅ Sticky acciones (Guardar/Cancelar) -->
            <div class="sticky-actions d-flex justify-content-end gap-2">
              <button type="button" class="btn btn-light" id="btnCancelarNueva">
                <i class="ri-close-line me-1"></i> Cancelar
              </button>
              <button type="button" class="btn btn-success" id="btnGuardarPlaneacion">
                <i class="ri-save-3-line me-1"></i> Guardar Planeación
              </button>
            </div>
          </div>
        </div>

        <!-- Ruta / Estaciones (NO mover contenido) -->
        <div class="col-12 col-xxl-6">
          <div class="card h-100">
            <div class="card-body">

              <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
                <div>
                  <h6 class="fw-bold mb-0">
                    <i class="ri-git-merge-line me-1"></i> Ruta del producto (estaciones) y asignación de operadores
                  </h6>
                  <div class="text-muted small">Selecciona un producto para cargar su ruta.</div>
                </div>

                <div class="d-flex align-items-center gap-2">
                  <span class="badge text-bg-primary">
                    Estaciones: <span id="countEstaciones">0</span>
                  </span>
                  <!-- <button class="btn btn-outline-primary btn-sm" id="btnAutoAsignar" type="button">
                    <i class="ri-magic-line me-1"></i> Auto-asignar (demo)
                  </button> -->
                </div>
              </div>

              <div class="table-responsive">
                <table class="table table-sm table-striped align-middle mb-0" id="tablaPlaneacionEstaciones">
                  <thead class="table-light">
                    <tr>
                      <th style="width:70px;">Orden</th>
                      <th>Estación</th>
                      <th style="width:280px;">Operador(es) asignados</th>
                      <th style="width:150px;" class="text-end">Acciones</th>
                    </tr>
                  </thead>
                  <tbody id="tbodyEstaciones">
                    <tr>
                      <td colspan="4" class="text-center text-muted py-4">
                        Selecciona un producto para cargar su ruta.
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>

              <div class="help mt-2">
                Tip: puedes permitir 1 operador o varios por estación.
              </div>

            </div>
          </div>
        </div>

      </div><!-- /viewNueva -->

      <!-- =========================
           VISTA: LISTADOS (Full + Filtros + Acciones)
      ========================== -->
      <div class="row g-3 d-none" id="viewListado">
        <div class="col-12">
          <div class="view-header d-flex flex-wrap align-items-center justify-content-between gap-2">
            <div>
              <div class="d-flex align-items-center gap-2">
                <span class="badge bg-secondary-subtle text-secondary border" id="badgeListado">
                  <i class="ri-list-check-2 me-1"></i> Listado
                </span>
                <span class="text-muted small" id="breadcrumbListado">Inicio → Planeación → Listado</span>
              </div>
              <div class="fw-bold mt-1" id="listadoTitulo">Planeaciones</div>
              <div class="text-muted small" id="listadoSubtitulo">Administra registros.</div>
            </div>

            <div class="d-flex align-items-center gap-2">
              <button class="btn btn-outline-secondary btn-sm" type="button" id="btnVolverHome2">
                <i class="ri-arrow-left-line me-1"></i> Volver
              </button>
            </div>
          </div>
        </div>

        <!-- ✅ filtros rápidos (sin backend aún) -->
        <div class="col-12">
          <div class="card">
            <div class="card-body">
              <div class="row g-2 align-items-end">
                <div class="col-12 col-md-4 col-xxl-3">
                  <label class="form-label mb-1 small text-muted">Buscar</label>
                  <input type="text" class="form-control form-control-sm" id="filterSearch" placeholder="Folio / producto...">
                </div>
                <div class="col-12 col-md-4 col-xxl-3">
                  <label class="form-label mb-1 small text-muted">Desde</label>
                  <input type="date" class="form-control form-control-sm" id="filterDesde">
                </div>
                <div class="col-12 col-md-4 col-xxl-3">
                  <label class="form-label mb-1 small text-muted">Hasta</label>
                  <input type="date" class="form-control form-control-sm" id="filterHasta">
                </div>
                <div class="col-12 col-md-4 col-xxl-2">
                  <label class="form-label mb-1 small text-muted">Prioridad</label>
                  <select class="form-select form-select-sm" id="filterPrioridad">
                    <option value="">Todas</option>
                    <option value="CRITICA">Crítica</option>
                    <option value="ALTA">Alta</option>
                    <option value="MEDIA">Media</option>
                    <option value="BAJA">Baja</option>
                  </select>
                </div>
                <div class="col-12 col-md-8 col-xxl-1 d-grid">
                  <button class="btn btn-outline-secondary btn-sm" type="button" id="btnRefrescarListado">
                    <i class="ri-refresh-line"></i>
                  </button>
                </div>
              </div>
              <div class="text-muted small mt-2">
                <i class="ri-lightbulb-line me-1"></i> Estos filtros están listos; luego los conectas a tu AJAX/SQL.
              </div>
            </div>
          </div>
        </div>

        <!-- tabla -->
        <div class="col-12">
          <div class="card">
            <div class="card-body">

              <div class="table-responsive">
                <table class="table table-sm table-striped align-middle mb-0" id="tablaListados">
                  <thead class="table-light">
                    <tr>
                      <th style="width:120px;">Folio</th>
                      <th>Producto</th>
                      <th style="width:130px;">Prioridad</th>
                      <th style="width:120px;">Cantidad</th>
                      <th style="width:140px;">Inicio</th>
                      <th style="width:140px;">Requerida</th>
                      <th style="width:160px;">Estatus</th>
                      <th style="width:210px;" class="text-end">Acciones</th>
                    </tr>
                  </thead>
                  <tbody id="tbodyListados"></tbody>
                </table>
              </div>

            </div>
          </div>
        </div>

      </div><!-- /viewListado -->

    </div><!-- /container-fluid -->
  </div><!-- /page-content -->

  <footer class="footer">
    <div class="container-fluid">
      <div class="row">
        <div class="col-sm-6">
          <script>document.write(new Date().getFullYear())</script> © LDR.
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

