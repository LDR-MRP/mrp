<?php headerAdmin($data); ?>

<div id="contentAjax"></div>
<div class="main-content">
  <div class="page-content">
    <div class="container-fluid">

      <!-- start page title -->
      <div class="row">
        <div class="col-12">
          <div class="page-title-box d-sm-flex align-items-center justify-content-between">
            <h4 class="mb-sm-0"></h4>
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

      <div class="row mb-3">
  <div class="col-12">
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2">
      <div>
        <h5 class="mb-0">Reporte KPI · Planeación</h5>
        <small class="text-muted">Detalle por Sub-OT / Estación, estándar vs real, eficiencia, en-tiempo, calidad.</small>
      </div>

      <div class="d-flex flex-wrap gap-2">

        <button class="btn btn-soft-primary" id="btnVerConfigProducto" type="button">
          <i class="ri-settings-3-line me-1"></i> Ver configuración de producto
        </button>

        <button class="btn btn-soft-success" id="btnVerPlaneacionPdf" type="button">
          <i class="ri-file-pdf-2-line me-1"></i> Ver planeación terminada (PDF)
        </button>





      </div>
    </div>
  </div>
</div>


      <!-- Filters -->
      <div class="row g-3 align-items-end mb-3">
        <div class="col-12 col-md-4">
          <label class="form-label">Planeación ID</label>
          <select id="planeacionid" class="form-select">
            <option value="">-- Selecciona --</option>
          </select>
        </div>

        <div class="col-12 col-md-3">
          <label class="form-label">Fecha inicio</label>
          <input type="date" id="fecha_ini" class="form-control" />
        </div>

        <div class="col-12 col-md-3">
          <label class="form-label">Fecha fin</label>
          <input type="date" id="fecha_fin" class="form-control" />
        </div>

        <div class="col-12 col-md-2 d-grid">
          <button class="btn btn-primary" id="btnAplicar">
            <i class="ri-filter-3-line me-1"></i> Aplicar
          </button>
        </div>

        <div class="col-12 col-md-6">
          <label class="form-label">Buscar</label>
          <input type="text" id="q" class="form-control" placeholder="Sub-OT, estación, encargado..." />
        </div>

        <div class="col-12 col-md-6 d-grid d-md-flex justify-content-md-end gap-2">
          <button class="btn btn-soft-secondary" id="btnLimpiar">
            <i class="ri-eraser-line me-1"></i> Limpiar
          </button>
          <button class="btn btn-soft-success" id="btnExportar">
            <i class="ri-file-excel-2-line me-1"></i> Exportar (después)
          </button>
        </div>
      </div>

      <!-- KPI Cards -->
      <div class="row g-3">
        <div class="col-12 col-xl-3">
          <div class="card card-animate">
            <div class="card-body">
              <div class="d-flex align-items-start justify-content-between">
                <div>
                  <p class="text-muted mb-1">SUB-OT</p>
                  <h3 class="mb-0" id="kpi_subot">—</h3>
                  <p class="text-muted mb-0">Sub-órdenes detectadas (según filtros).</p>
                </div>
                <span class="badge bg-primary-subtle text-primary">Conteo</span>
              </div>
            </div>
          </div>
        </div>

        <div class="col-12 col-xl-3">
          <div class="card card-animate">
            <div class="card-body">
              <div class="d-flex align-items-start justify-content-between">
                <div>
                  <p class="text-muted mb-1">EFICIENCIA PROMEDIO</p>
                  <h3 class="mb-0"><span id="kpi_eficiencia">—</span>%</h3>
                  <p class="text-muted mb-0">Promedio solo de cerradas con real válido.</p>
                </div>
                <span class="badge bg-info-subtle text-info">Std/Real</span>
              </div>
            </div>
          </div>
        </div>

        <div class="col-12 col-xl-3">
          <div class="card card-animate">
            <div class="card-body">
              <div class="d-flex align-items-start justify-content-between">
                <div>
                  <p class="text-muted mb-1">% EN TIEMPO</p>
                  <h3 class="mb-0"><span id="kpi_ent">—</span>%</h3>
                  <p class="text-muted mb-0">Cumplimiento vs estándar en cerradas.</p>
                </div>
                <span class="badge bg-success-subtle text-success">Cumpl.</span>
              </div>
            </div>
          </div>
        </div>

        <div class="col-12 col-xl-3">
          <div class="card card-animate">
            <div class="card-body">
              <div class="d-flex align-items-start justify-content-between">
                <div>
                  <p class="text-muted mb-1">RECHAZOS</p>
                  <h3 class="mb-0" id="kpi_rech">—</h3>
                  <p class="text-muted mb-0">Calidad = Rechazado (4).</p>
                </div>
                <span class="badge bg-danger-subtle text-danger">Calidad</span>
              </div>
            </div>
          </div>
        </div>
      </div>



      <div class="row g-3 mt-1">
  <div class="col-12 col-xl-6">
    <div class="card">
      <div class="card-header align-items-center d-flex">
        <h4 class="card-title mb-0 flex-grow-1">
          <i class="ri-bar-chart-2-line me-1 text-primary"></i> Eficiencia por Sub-OT
        </h4>
        <span class="badge bg-primary-subtle text-primary">Bar</span>
      </div>
      <div class="card-body">
        <div style="height: 320px;">
          <canvas id="chartEficienciaSubOt"></canvas>
        </div>
      </div>
    </div>
  </div>

  <div class="col-12 col-xl-6">
    <div class="card">
      <div class="card-header align-items-center d-flex">
        <h4 class="card-title mb-0 flex-grow-1">
          <i class="ri-pie-chart-2-line me-1 text-primary"></i> Calidad (distribución)
        </h4>
        <span class="badge bg-info-subtle text-info">Doughnut</span>
      </div>
      <div class="card-body">
        <div style="height: 320px;">
          <canvas id="chartCalidad"></canvas>
        </div>
      </div>
    </div>
  </div>

  <div class="col-12 col-xl-6">
    <div class="card">
      <div class="card-header align-items-center d-flex">
        <h4 class="card-title mb-0 flex-grow-1">
          <i class="ri-time-line me-1 text-primary"></i> % En tiempo por Estación
        </h4>
        <span class="badge bg-success-subtle text-success">Bar</span>
      </div>
      <div class="card-body">
        <div style="height: 320px;">
          <canvas id="chartEnTiempoEstacion"></canvas>
        </div>
      </div>
    </div>
  </div>

  <div class="col-12 col-xl-6">
    <div class="card">
      <div class="card-header align-items-center d-flex">
        <h4 class="card-title mb-0 flex-grow-1">
          <i class="ri-scales-3-line me-1 text-primary"></i> Real vs Estándar (min)
        </h4>
        <span class="badge bg-warning-subtle text-warning">Bar</span>
      </div>
      <div class="card-body">
        <div style="height: 320px;">
          <canvas id="chartRealVsStd"></canvas>
        </div>
      </div>
    </div>
  </div>
</div>

















      <!-- Tabs + Tables -->
      <div class="row mt-3">
        <div class="col-12">
          <div class="card">
            <div class="card-body">

            <!-- Total costos planeación -->
<div class="card border border-success-subtle mb-3">
  <div class="card-body d-flex align-items-center justify-content-between">
    <div>
      <div class="text-muted">Costo total de la planeación</div>
      <h3 class="mb-0" id="kpi_costo_total">$—</h3>
      <small class="text-muted">Calculado con cantidad planeada × BOM por estación × último costo.</small>
    </div>
    <span class="badge bg-success-subtle text-success">
      <i class="ri-money-dollar-circle-line me-1"></i> Total
    </span>
  </div>
</div>


              <ul class="nav nav-pills nav-customs nav-danger mb-3" role="tablist">
                <li class="nav-item" role="presentation">
                  <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#tabDetalle" type="button" role="tab">
                    Detalle
                  </button>
                </li>
                <li class="nav-item" role="presentation">
                  <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tabResumen" type="button" role="tab">
                    Resumen Sub-OT
                  </button>
                </li>
                <li class="nav-item" role="presentation">
                  <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tabEncargados" type="button" role="tab">
                    Encargados
                  </button>
                </li>
                <li class="nav-item" role="presentation">
                  <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tabCalidad" type="button" role="tab">
                    Calidad por estación
                  </button>
                </li>

                <li class="nav-item" role="presentation">
  <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tabCostos" type="button" role="tab">
    Costos (por estación)
  </button>
</li>

              </ul>

              <div class="tab-content text-muted">

                <!-- Detalle -->
                <div class="tab-pane fade show active" id="tabDetalle" role="tabpanel">
                  <div class="d-flex justify-content-between align-items-center mb-2">
                    <div>
                      <h5 class="mb-0">Detalle por Sub-OT / Estación</h5>
                 
                    </div>
                    <small class="text-muted"><span id="lbl_registros">0</span> registros</small>
                  </div>

                  <div class="table-responsive">
                    <table id="tblDetalle" class="table table-sm table-striped align-middle mb-0" style="width:100%;">
                      <thead class="table-light">
                        <tr>
                          <th>Sub-OT</th>
                          <th>Estación</th>
                          <th>Encargado / Ayudante</th>
                          <th class="text-end">Std (min)</th>
                          <th class="text-end">Real (min)</th>
                          <th class="text-end">Eficiencia</th>
                          <th>Tiempo</th>
                          <th>Estatus</th>
                          <th>Calidad</th>
                        </tr>
                      </thead>
                      <tbody></tbody>
                    </table>
                  </div>
                </div>

                <!-- Resumen -->
                <div class="tab-pane fade" id="tabResumen" role="tabpanel">
                  <div class="mb-2">
                    <h5 class="mb-0">Resumen por Sub-OT</h5>
                    <small class="text-muted">Std total vs Real total, eficiencia global, % en tiempo, rechazos.</small>
                  </div>

                  <div class="table-responsive">
                    <table id="tblResumen" class="table table-sm table-striped align-middle mb-0" style="width:100%;">
                      <thead class="table-light">
                        <tr>
                          <th>Sub-OT</th>
                          <th class="text-end">Std total</th>
                          <th class="text-end">Real total</th>
                          <th class="text-end">Eficiencia</th>
                          <th class="text-end">% En tiempo</th>
                          <th class="text-end">Rechazos</th>
                          <th>Último estatus</th>
                          <th>Última calidad</th>
                        </tr>
                      </thead>
                      <tbody></tbody>
                    </table>
                  </div>
                </div>

                <!-- Encargados -->
                <div class="tab-pane fade" id="tabEncargados" role="tabpanel">
                  <div class="mb-2">
                    <h5 class="mb-0">Desempeño por Encargado</h5>
                    <small class="text-muted">Minutos reales, eficiencia promedio y rechazos asociados.</small>
                  </div>

                  <div class="table-responsive">
                    <table id="tblEncargados" class="table table-sm table-striped align-middle mb-0" style="width:100%;">
                      <thead class="table-light">
                        <tr>
                          <th>Encargado</th>
                          <th class="text-end">Registros</th>
                          <th class="text-end">Real total</th>
                          <th class="text-end">Eficiencia prom.</th>
                          <th class="text-end">% En tiempo</th>
                          <th class="text-end">Rechazos</th>
                        </tr>
                      </thead>
                      <tbody></tbody>
                    </table>
                  </div>
                </div>

                <!-- Calidad -->
                <div class="tab-pane fade" id="tabCalidad" role="tabpanel">
                  <div class="mb-2">
                    <h5 class="mb-0">Calidad por Estación</h5>
                    <small class="text-muted">Distribución por estación y conteos por estado de calidad (1-5).</small>
                  </div>

                  <div class="table-responsive">
                    <table id="tblCalidad" class="table table-sm table-striped align-middle mb-0" style="width:100%;">
                      <thead class="table-light">
                        <tr>
                          <th>Estación</th>
                          <th class="text-end">Pend. Insp</th>
                          <th class="text-end">En insp</th>
                          <th class="text-end">Obs</th>
                          <th class="text-end">Rech</th>
                          <th class="text-end">Lib</th>
                          <th class="text-end">Total</th>
                        </tr>
                      </thead>
                      <tbody></tbody>
                    </table>
                  </div>
                </div>

                <!-- Costos -->
<div class="tab-pane fade" id="tabCostos" role="tabpanel">
  <div class="mb-2">
    <h5 class="mb-0">Costos por estación y detalle por componente</h5>
    <small class="text-muted">Resumen por estación + desglose por artículo con último costo del inventario.</small>
  </div>

  <!-- Resumen costos por estación -->
  <div class="table-responsive mb-3">
    <table id="tblCostosEstacion" class="table table-sm table-striped align-middle mb-0" style="width:100%;">
      <thead class="table-light">
        <tr>
          <th>Estación</th>
          <th>Encargado / Ayudante</th>
          <th class="text-end">Costo estación</th>
          <th class="text-end">Pend</th>
          <th class="text-end">Insp</th>
          <th class="text-end">Obs</th>
          <th class="text-end">Rech</th>
          <th class="text-end">Lib</th>
          <th class="text-end">Total reg.</th>
        </tr>
      </thead>
      <tbody></tbody>
    </table>
  </div>

  <!-- Detalle costos -->
  <div class="table-responsive">
    <table id="tblCostosDetalle" class="table table-sm table-striped align-middle mb-0" style="width:100%;">
      <thead class="table-light">
        <tr>
          <th>Estación</th>
          <th>Artículo</th>
          <th class="text-end">Cant. planeada</th>
          <th class="text-end">Cant. x prod</th>
          <th class="text-end">Cant. total</th>
          <th class="text-end">Último costo</th>
          <th class="text-end">Costo total</th>
        </tr>
      </thead>
      <tbody></tbody>
    </table>
  </div>
</div>


              </div><!-- tab-content -->

            </div>
          </div>
        </div>
      </div>

    </div><!-- container-fluid -->
  </div><!-- End Page-content -->

  <footer class="footer">
    <div class="container-fluid">
      <div class="row">
        <div class="col-sm-6">
          <script>document.write(new Date().getFullYear())</script> © LDR.
        </div>
        <div class="col-sm-6">
          <div class="text-sm-end d-none d-sm-block">LDR Solutions · MRP</div>
        </div>
      </div>
    </div>
  </footer>
</div><!-- end main content-->

<?php footerAdmin($data); ?>
