<?php headerAdmin($data); ?>

<div class="main-content">
    <div class="page-content">
        <div class="container-fluid">
            <style>
                [cite_start]/* Estilos de Estados: HU-09 [cite: 4] */
                .badge-draft { background: #f1f5f9; color: #475569; }
                .badge-review { background: #e0f2fe; color: #0369a1; }
                .badge-observed { background: #fffbeb; color: #b45309; }
                .badge-approved { background: #f0fdf4; color: #15803d; }
                .badge-rejected { background: #fef2f2; color: #b91c1c; }
                .badge-purchasing { background: #f5f3ff; color: #6d28d9; }
                .badge-closed { background: #f8fafc; color: #1e293b; border: 1px solid #e2e8f0; }
                
                :root { --primary: #0056b3; --success: #28a745; --warning: #ffc107; --danger: #dc3545; }
                body { background-color: #f4f7f6; font-family: 'Segoe UI', sans-serif; }
                .skeleton { background: #eee; background: linear-gradient(110deg, #ececec 8%, #f5f5f5 18%, #ececec 33%); border-radius: 4px; background-size: 200% 100%; animation: 1.5s shine linear infinite; }
                @keyframes shine { to { background-position-x: -200%; } }
                .skeleton-text { height: 1rem; margin-bottom: 0.5rem; width: 100%; }
                .skeleton-title { height: 2rem; width: 60%; margin-bottom: 1rem; }
                .card { border: none; border-radius: 10px; box-shadow: 0 4px 6px rgba(0,0,0,0.04); margin-bottom: 2rem; }
                .card-header { background: #fff; font-weight: bold; text-transform: uppercase; font-size: 0.8rem; letter-spacing: 1px; }
            </style>

            <section id="view-skeleton">
                <div class="row mb-4">
                    <div class="col-md-6">
                        <div class="skeleton skeleton-title"></div>
                        <div class="skeleton skeleton-text" style="width: 30%;"></div>
                    </div>
                </div>
                <div class="card p-4"><div class="row"><div class="col-3"><div class="skeleton skeleton-text"></div></div><div class="col-3"><div class="skeleton skeleton-text"></div></div><div class="col-3"><div class="skeleton skeleton-text"></div></div><div class="col-3"><div class="skeleton skeleton-text"></div></div></div></div>
            </section>

            <section id="view-detail" style="display: none;">
                <div class="row align-items-center mb-4">
                    <div class="col-md-6">
                        <h3 class="font-weight-bold" id="req-title">Detalle de Requisición <span id="req-id">---</span></h3>
                        <span id="req-status-badge" class="badge px-4 py-2">---</span>
                    </div>
                    <div class="col-md-6 d-flex justify-content-md-end justify-content-start">
                        <!-- <button id="btn-export-pdf" class="btn btn-outline-danger shadow-sm ml-2">
                            <i class="ri-file-pdf-line"></i> Exportar
                        </button> -->
                        <button type="button" class="btn btn-light border mr-2" data-redirect="com_requisicion">Cancelar</button>
                        <button id="btn-edit-draft" class="btn btn-warning shadow-sm px-4 ml-2">Editar Borrador</button>
                    </div>
                </div>

                <div class="card bg-white">
                    <div class="card-body row text-center">
                        <div class="col-md-3 border-right">
                            <label class="small text-muted d-block">Solicitante</label>
                            <strong id="req-requester-name">---</strong>
                        </div>
                        <div class="col-md-3 border-right">
                            <label class="small text-muted d-block">Fecha de Solicitud</label>
                            <strong id="req-date">---</strong>
                        </div>
                        <div class="col-md-3 border-right">
                            <label class="small text-muted d-block">Centro de Costo</label>
                            <strong id="req-cost-center">---</strong>
                        </div>
                        <div class="col-md-3">
                            <label class="small text-muted d-block">Total Estimado</label>
                            <strong id="req-total-amount" class="text-primary font-weight-bold">---</strong>
                        </div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header">Artículos Autorizados</div>
                    <div class="card-body p-0">
                        <table class="table mb-0" id="table-items">
                            <thead>
                                <tr>
                                    <th class="pl-4">#</th>
                                    <th>Descripción</th>
                                    <th class="text-center">Cant.</th>
                                    <th class="text-right">Precio Unit.</th>
                                    <th class="text-right pr-4">Subtotal</th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header"><i class="ri-history-line mr-2"></i> Bitácora de Seguimiento</div>
                    <div class="card-body p-0">
                        <table class="table table-sm table-striped mb-0 small text-muted" id="table-audit-log">
                            <tbody></tbody>
                        </table>
                    </div>
                </div>
            </section>
        </div>
    </div>
</div>

<?php footerAdmin($data); ?>