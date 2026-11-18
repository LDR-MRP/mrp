<div class="tab-pane" id="tabAsignacion" role="tabpanel">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <div>
            <h5 class="mb-1">Asignación de Unidades (VINs PT)</h5>
            <p class="text-muted mb-0 fs-13">
                Asigna VINs reales desde almacenes de Producto Terminado (<code>pt = 1</code>) hacia cada partida del pedido para reflejarlos en Logística y Facturación.
            </p>
        </div>
        <button type="button" class="btn btn-soft-primary btn-sm" id="btnRecargarAsignacionesVin">
            <i class="ri-refresh-line me-1"></i> Actualizar
        </button>
    </div>

    <div id="resumenAsignacionesPedido" class="mb-3"></div>
    <div id="contenidoAsignacionesPedido">
        <div class="text-center py-4 text-muted">Cargando partidas y asignaciones de VIN...</div>
    </div>
</div>

<!-- Modal para seleccionar VIN PT disponible -->
<div class="modal fade" id="modalAsignarVinPedido" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header bg-light">
                <div>
                    <h5 class="modal-title mb-0"><i class="ri-truck-line me-1 text-primary"></i> Asignar VIN PT al Pedido</h5>
                    <small class="text-muted" id="lblSubtituloModalVinPedido"></small>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="asig_vin_idpedido_detalle" value="0">
                <div class="input-group input-group-sm mb-3">
                    <span class="input-group-text"><i class="ri-search-line"></i></span>
                    <input type="text" class="form-control" id="inpBuscarVinPedido" placeholder="Buscar por VIN, número de motor o almacén PT...">
                    <button type="button" class="btn btn-outline-secondary" id="btnFiltrarVinPedido">Buscar</button>
                </div>
                <div class="table-responsive" style="max-height: 360px;">
                    <table class="table table-sm table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>VIN / Motor</th>
                                <th>Almacén PT</th>
                                <th>Estatus / Factura</th>
                                <th class="text-end">Acción</th>
                            </tr>
                        </thead>
                        <tbody id="tbodyVinsDisponiblesPedido">
                            <tr><td colspan="4" class="text-center text-muted py-4">Buscando VINs disponibles...</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
