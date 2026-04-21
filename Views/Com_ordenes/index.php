<?php headerAdmin($data); ?>
<div class="main-content">
    <div class="page-content">
        <div class="container-fluid">
            <!-- Header -->
            <div class="row mb-3">
                <div class="col-12 d-flex justify-content-between align-items-center">
                    <h4 class="mb-0 fw-bold text-dark">Bandeja de Órdenes de Compra</h4>
                    <button class="btn btn-outline-primary btn-sm" type="button" data-bs-toggle="collapse" data-bs-target="#collapseFilters">
                        <i class="ri-filter-2-line"></i> Filtros
                    </button>
                </div>
            </div>

            <!-- Filtros Avanzados -->
            <div class="collapse mb-3" id="collapseFilters">
                <div class="card border-0 shadow-sm">
                    <div class="card-body bg-light">
                        <form id="formFiltrosOC" class="row g-3">
                            <div class="col-md-3">
                                <label class="form-label small fw-bold">Proveedor</label>
                                <select name="proveedorid" class="form-select form-select-sm">
                                    <option value="">Todos los proveedores</option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label small fw-bold">Estatus</label>
                                <select name="estatus" class="form-select form-select-sm">
                                    <option value="">Todos</option>
                                    <option value="emitida">Emitida</option>
                                    <option value="en_transito">En Tránsito</option>
                                    <option value="cerrada">Cerrada</option>
                                    <option value="cancelada">Cancelada</option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label small fw-bold">Desde</label>
                                <input type="date" name="fecha_desde" class="form-control form-control-sm">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label small fw-bold">Hasta</label>
                                <input type="date" name="fecha_hasta" class="form-control form-control-sm">
                            </div>
                            <div class="col-md-3 d-flex align-items-end gap-2">
                                <button type="submit" class="btn btn-primary btn-sm w-100">Aplicar</button>
                                <button type="reset" class="btn btn-light btn-sm w-100">Limpiar</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Tabla Principal -->
            <div class="card border-0 shadow-lg">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table id="tblOrders" class="table table-nowrap align-middle mb-0 table-hover" style="width:100%">
                            <thead class="bg-light">
                                <tr>
                                    <th>Folio</th>
                                    <th>Fecha</th>
                                    <th>Proveedor</th>
                                    <th>Ref. Req</th>
                                    <th class="text-end">Total</th>
                                    <th class="text-center">Estatus</th>
                                    <th class="text-end">Acciones</th>
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
<?php footerAdmin($data); ?>