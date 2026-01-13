<?php headerAdmin($data); ?>
<div id="contentAjax"></div>

<style>
  /* -----------------------------
     SIDE PANEL (UX PRO)
  ------------------------------*/
  .side-panel-fixed {
    min-height: 680px;
  }

  .side-hero {
    height: 220px;
    /* imagen grande fija */
    border: 1px dashed rgba(0, 0, 0, .10);
    border-radius: 14px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: rgba(0, 0, 0, .015);
    overflow: hidden;
  }

  .side-hero img {
    height: 200px;
    width: 100%;
    object-fit: contain;
  }

  .side-divider {
    height: 1px;
    background: rgba(0, 0, 0, .08);
    margin: 14px 0;
  }

  .btn-nav.active {
    box-shadow: 0 0.35rem 1rem rgba(0, 0, 0, .08);
    border-color: rgba(0, 0, 0, .12) !important;
    transform: translateY(-1px);
  }

  /* -----------------------------
     TOP HEADER “estado” (listas)
  ------------------------------*/
  .view-header {
    border: 1px solid rgba(0, 0, 0, .08);
    border-radius: 14px;
    padding: 14px 16px;
    background: rgba(0, 0, 0, .015);
  }

  /* -----------------------------
     STICKY GUARDAR (Nueva)
  ------------------------------*/
  .sticky-actions {
    position: sticky;
    bottom: 0;
    z-index: 10;
    background: #fff;
    border-top: 1px solid rgba(0, 0, 0, .08);
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
        <div class="col-12 col-xxl-4">
          <div class="card h-100">
            <div class="card-body">
              <div class="row g-3">

                <!-- Producto -->
                <div class="col-12">
                  <label class="form-label fw-semibold">Producto</label>
                  <select class="form-select" id="selectProducto" name="selectProducto" required>
                    <option value="">-- Selecciona producto --</option>
                  </select>
                  <div class="help mt-1">Al seleccionar, se cargan ruta (estaciones) y operadores disponibles.</div>
                </div>


                                <!-- Num pedido -->
                <div class="col-12">
                  <label class="form-label fw-semibold">Número de pedido</label>
                  <input class="form-control" id="numPedido" 
                    placeholder="Escribe el número de pedido..." autocomplete="off">
                  <div class="help mt-1">Ej: “PO-1000200002”.</div>
                </div>

                <!-- Supervisor -->
                <div class="col-12">
                  <label class="form-label fw-semibold">Supervisor</label>
                  <input class="form-control" id="inputSupervisor" list="datalistSupervisores"
                    placeholder="Escribe para buscar supervisor..." autocomplete="off">
                  <datalist id="datalistSupervisores">
                    <option value="Sergio Ramírez"></option>
                    <option value="Isaura Hernández"></option>
                    <option value="Jorge Martínez"></option>
                    <option value="Sofía Gómez"></option>
                  </datalist>
                  <div class="help mt-1">Ej: “Carlos Cruz Castañeda”.</div>
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
                      <input type="datetime-local" class="form-control" id="fechaInicio" required>
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
                <!-- <div class="col-12">
                  <div class="alert alert-light border mb-0">
                    <div class="small text-muted mb-1">
                      <i class="ri-information-line me-1"></i> Recomendación automotriz
                    </div>
                    <div class="small">
                      Considera agregar después: <b>Turno</b>, <b>Cliente/Programa</b>, <b>Orden de Producción</b>,
                      <b>Lote</b> y <b>Revisión de ingeniería</b>.
                    </div>
                  </div>
                </div> -->

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
        <div class="col-12 col-xxl-8">
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

              <!-- ALERTA DE BLOQUEO POR MANTENIMIENTO -->
              <div id="alertMantenimientoBloqueo" class="alert alert-danger d-none mb-3" role="alert">
                <div class="d-flex align-items-start gap-2">
                  <i class="ri-error-warning-line fs-4"></i>
                  <div>
                    <div class="fw-bold">No podrás guardar esta configuración</div>
                    <div>Porque al menos una estación se encuentra <b>En proceso</b> de mantenimiento.</div>
                  </div>
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

      </div>

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
                  <input type="text" class="form-control form-control-sm" id="filterSearch"
                    placeholder="Folio / producto...">
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



<div class="modal fade" id="modalAddOperador" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content border-0">
      <div class="modal-header bg-primary-subtle p-3">
     <h5 class="modal-title" id="titleModal">Agregar operadores</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close" id="close-modal"></button>
      </div>
<div class="modal-body">


  <div class="alert alert-primary-subtle border mb-3" role="alert">
    <div class="d-flex gap-2">
      <i class="ri-mail-send-line fs-4"></i>
      <div>
        <div class="fw-bold">Notificación a operadores</div>
        <div class="text-muted">
          Al guardar la planeación, los operadores asignados recibirán una notificación por correo con los detalles del pedido
          (producto, fechas y estación asignada). Verifica la información antes de aplicar la asignación.
        </div>
      </div>
    </div>
  </div>


  <div class="text-center mb-3">
    <div class="fw-bold fs-5" id="modalEstacionNombre">—</div>
    <div class="text-muted" id="modalEstacionProceso">—</div>
    <input type="text" id="modalEstacionId" value="">
  </div>

  <div class="row g-3">

    <div class="col-12 col-lg-7">
      <div class="mb-2">

        <label class="form-label fw-semibold mb-1">Encargado (Supervisor)</label>
        <select class="form-select" id="listOperadores" name="listOperadores">
          <option value="" selected>-- Selecciona encargado --</option>
        </select>
        <div class="form-text">El encargado siempre será uno.</div>
      </div>

      <div class="mt-3">
        <label class="form-label fw-semibold mb-1">Ayudantes (Operadores)</label>
        <select class="form-select" id="selectAyudantes" multiple size="8" name="selectAyudantes">
        </select>
        <div class="form-text">Puedes seleccionar varios (Ctrl o Shift).</div>
      </div>
    </div>


    <div class="col-12 col-lg-5">
      <div class="card h-100">
        <div class="card-body">
          <div class="fw-semibold mb-2">Sugerencias</div>
          <div class="text-muted mb-2">Buenas prácticas:</div>
          <ul class="mb-0">

            <li>Evitar sobre-asignación</li>
            <li>Considerar estaciones críticas</li>
          </ul>
        </div>
      </div>
    </div>
  </div>

</div>


<div class="modal-footer">
  <div class="hstack gap-2 justify-content-end w-100">
    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button>
    <button type="button" class="btn btn-primary" id="btnAplicarAsignacion">
      <i class="ri-check-line me-1"></i> Aplicar asignación
    </button>
  </div>
</div>


    </div>
  </div>
</div>




<div class="modal fade" id="modalFaltantesInventario" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content border-0">
      <div class="modal-header bg-danger-subtle p-3">
        <h5 class="modal-title" id="titleModalFaltantes">Faltantes de inventario</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>

      <div class="modal-body">
        <div class="text-muted small mb-2" id="subTitleFaltantes">Estación: —</div>

        <div class="table-responsive">
          <table class="table table-sm table-striped align-middle mb-0">
            <thead class="table-light">
              <tr>
                <th>Componente</th>
                <th class="text-end">Requerido</th>
                <th class="text-end">Existencia</th>
                <th class="text-end">Faltante</th>
                <th>Almacén</th>
              </tr>
            </thead>
            <tbody id="tbodyFaltantes">
              <tr>
                <td colspan="5" class="text-center text-muted py-3">Sin información</td>
              </tr>
            </tbody>
          </table>
        </div>


      </div>

      <div class="modal-footer">
        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cerrar</button>
      </div>
    </div>
  </div>
</div>



<div class="modal fade" id="modalFaltantesHerramientas" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content border-0">
      <div class="modal-header bg-danger-subtle p-3">
        <h5 class="modal-title" id="titleModalFaltantesHer">Faltantes de herramientas</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>

      <div class="modal-body">
        <div class="text-muted small mb-2" id="subTitleFaltantesHer">Estación: —</div>

        <div class="table-responsive">
          <table class="table table-sm table-striped align-middle mb-0">
            <thead class="table-light">
              <tr>
                <th>Herramienta</th>
                <th class="text-end">Requerido</th>
                <th class="text-end">Existencia</th>
                <th class="text-end">Faltante</th>
                <th>Almacén</th>
              </tr>
            </thead>
            <tbody id="tbodyFaltantesHer">
              <tr>
                <td colspan="5" class="text-center text-muted py-3">Sin información</td>
              </tr>
            </tbody>
          </table>
        </div>


      </div>

      <div class="modal-footer">
        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cerrar</button>
      </div>
    </div>
  </div>
</div>






<?php footerAdmin($data); ?>