<?php headerAdmin($data); ?>
<div class="main-content"><div class="page-content"><div class="container-fluid">
<div class="row mb-3"><div class="col-12 d-flex justify-content-between align-items-center flex-wrap gap-2">
<div><h3 class="mb-1"><i class="ri-file-list-3-line text-primary me-2"></i>Gestión de propuesta comercial</h3><p class="text-muted mb-0">Revisa y ajusta las cotizaciones antes de solicitar autorización.</p></div>
<a class="btn btn-soft-secondary" href="<?= base_url(); ?>/ped_cotizaciones"><i class="ri-arrow-left-line me-1"></i> Volver al listado</a>
</div></div>
<div id="alertaGestion" class="alert alert-danger d-none" role="alert"></div>
<div class="card"><div class="card-body"><div class="row g-3 align-items-center">
<div class="col-md-3"><small class="text-muted d-block">Folio</small><strong id="gpFolio">Cargando...</strong></div>
<div class="col-md-4"><small class="text-muted d-block">Distribuidor</small><strong id="gpCliente">—</strong><div class="small text-muted" id="gpCorreo"></div></div>
<div class="col-md-2"><small class="text-muted d-block">Origen</small><span id="gpOrigen">—</span></div>
<div class="col-md-3"><small class="text-muted d-block">Estado</small><span id="gpEstado" class="badge bg-secondary-subtle text-secondary">—</span></div>
</div></div></div>
<div class="card border-primary"><div class="card-header d-flex align-items-center justify-content-between flex-wrap gap-2">
<h5 class="card-title mb-0"><i class="ri-shield-check-line text-primary me-2"></i>Seguimiento de autorizaciones por modelo</h5>
<button id="gpEnviarTodos" type="button" class="btn btn-primary btn-sm d-none"><i class="ri-send-plane-line me-1"></i>Guardar y enviar borradores a revisión</button>
</div><div class="card-body"><div class="alert alert-info py-2"><i class="ri-information-line me-1"></i>Cada modelo se autoriza de forma independiente: Dirección General → Dirección Comercial → Dirección de Finanzas. El porcentaje indica etapas aprobadas.</div>
<div id="gpSeguimiento" class="row g-3"><div class="text-muted">Cargando seguimiento...</div></div></div></div>
<div class="row g-3">
<div class="col-xl-4"><div class="card h-100"><div class="card-header"><h5 class="card-title mb-0"><i class="ri-list-check-2 me-1 text-primary"></i>Cotizaciones de la propuesta</h5></div><div class="card-body" id="gpListado"><div class="text-muted">Consultando cotizaciones...</div></div></div></div>
<div class="col-xl-8"><div class="card"><div class="card-header d-flex justify-content-between align-items-center"><h5 class="card-title mb-0"><i class="ri-edit-box-line me-1 text-primary"></i>Detalle y ajustes comerciales</h5><span id="gpCotEstado" class="badge bg-secondary-subtle text-secondary">Selecciona una cotización</span></div>
<div class="card-body">
<form id="gpFormulario" class="d-none" novalidate>
<div class="alert alert-info border-0"><i class="ri-information-line me-1"></i> Solo las cotizaciones en <strong>BORRADOR</strong> pueden editarse. Los precios de catálogo se muestran como referencia.</div>
<div class="row g-3">
<div class="col-12"><h6 class="text-primary border-bottom pb-2"><i class="ri-car-line me-2"></i>Unidad y precios de referencia</h6></div>
<div class="col-md-8"><label class="form-label">Modelo</label><input id="gpModelo" class="form-control" readonly></div>
<div class="col-md-4"><label class="form-label">Folio de cotización</label><input id="gpCotFolio" class="form-control" readonly></div>
<div class="col-md-4"><label class="form-label">Precio público histórico</label><input id="gpPublico" class="form-control" readonly></div>
<div class="col-md-4"><label class="form-label">Precio dealer histórico</label><input id="gpDealer" class="form-control" readonly></div>
<div class="col-md-4"><label class="form-label">Versión</label><input id="gpVersion" class="form-control" readonly></div>
<div class="col-12 mt-4"><h6 class="text-primary border-bottom pb-2"><i class="ri-building-2-line me-2"></i>Cliente final y competencia</h6></div>
<div class="col-md-6"><label for="gpClienteFinal" class="form-label">Nombre del cliente final <i class="ri-question-line text-info ms-1" role="img" aria-label="Ayuda" data-bs-toggle="tooltip" data-bs-placement="top" title="Indica el nombre de la persona o empresa que adquirirá la unidad."></i></label><input id="gpClienteFinal" class="form-control" maxlength="200"><div class="form-text"><i class="ri-information-line me-1"></i>Indica el nombre de la persona o empresa que adquirirá la unidad.</div></div>
<div class="col-md-6"><label for="gpCompetidor" class="form-label">Nombre del competidor <i class="ri-question-line text-info ms-1" role="img" aria-label="Ayuda" data-bs-toggle="tooltip" data-bs-placement="top" title="Empresa o marca contra la que compite esta operación."></i></label><input id="gpCompetidor" class="form-control" maxlength="200"><div class="form-text"><i class="ri-information-line me-1"></i>Empresa o marca contra la que compite esta operación.</div></div>
<div class="col-md-6"><label for="gpModeloCompetidor" class="form-label">Modelo del competidor <i class="ri-question-line text-info ms-1" role="img" aria-label="Ayuda" data-bs-toggle="tooltip" data-bs-placement="top" title="Modelo específico de la competencia para comparar la oferta."></i></label><input id="gpModeloCompetidor" class="form-control" maxlength="200"><div class="form-text"><i class="ri-information-line me-1"></i>Modelo específico de la competencia para comparar la oferta.</div></div>
<div class="col-md-6"><label for="gpPrecioCompetidor" class="form-label">Precio del competidor (MXN) <i class="ri-question-line text-info ms-1" role="img" aria-label="Ayuda" data-bs-toggle="tooltip" data-bs-placement="top" title="Precio de referencia de la competencia en pesos mexicanos."></i></label><input id="gpPrecioCompetidor" type="text" inputmode="decimal" autocomplete="off" placeholder="0.00" maxlength="22" class="form-control"><div class="form-text"><i class="ri-information-line me-1"></i>Precio de referencia de la competencia en pesos mexicanos.</div></div>
<div class="col-12 mt-4"><h6 class="text-primary border-bottom pb-2"><i class="ri-percent-line me-2"></i>Ajuste comercial</h6></div>
<div class="col-md-4"><label for="gpCantidad" class="form-label">Cantidad *</label><input id="gpCantidad" type="number" min="1" max="100000" step="1" class="form-control" required></div>
<div class="col-md-4"><label for="gpTipoDescuento" class="form-label">Tipo de descuento * <i class="ri-question-line text-info ms-1" role="img" aria-label="Ayuda" data-bs-toggle="tooltip" data-bs-placement="top" title="Elige pesos por unidad o porcentaje sobre el precio dealer."></i></label><select id="gpTipoDescuento" class="form-select"><option value="MONTO">Monto en pesos por unidad</option><option value="PORCENTAJE">Porcentaje (%)</option></select><div class="form-text"><i class="ri-information-line me-1"></i>Elige pesos por unidad o porcentaje sobre el precio dealer.</div></div>
<div class="col-md-4"><label for="gpDescuento" id="gpDescuentoLabel" class="form-label">Descuento en pesos por unidad * <i class="ri-question-line text-info ms-1" role="img" aria-label="Ayuda" data-bs-toggle="tooltip" data-bs-placement="top" title="El descuento no puede superar el precio dealer."></i></label><input id="gpDescuento" type="number" min="0" step="0.01" class="form-control" required><div class="form-text"><i class="ri-information-line me-1"></i>El descuento no puede superar el precio dealer.</div></div>
<div class="col-md-4"><label class="form-label">Descuento unitario calculado</label><input id="gpDescuentoUnitario" class="form-control" readonly></div>
<div class="col-md-4"><label class="form-label">Precio final cotizado por unidad</label><input id="gpPrecio" class="form-control" readonly></div>
<div class="col-md-4"><label for="gpVigencia" class="form-label"><i class="ri-calendar-check-line me-1 text-primary"></i>Fecha de vigencia <i class="ri-question-line text-info" data-bs-toggle="tooltip" title="Fecha de referencia durante la preparación de la cotización."></i></label><input id="gpVigencia" type="date" class="form-control"><div class="form-text">La fecha capturada en borrador es informativa.</div></div>
<div class="col-12"><div class="alert alert-info d-flex align-items-start gap-2 mb-0" role="note"><i class="ri-calendar-event-line fs-5"></i><div><strong>Vigencia al enviar al dealer:</strong> una vez que la cotización se envíe al distribuidor, tendrá una vigencia de <strong>30 días naturales desde la fecha de envío</strong>. La fecha definitiva se establecerá en el proceso de envío (pendiente de implementar).</div></div></div><div class="col-12 mt-4"><h6 class="text-primary border-bottom pb-2"><i class="ri-file-text-line me-2"></i>Condiciones y observaciones</h6></div>
<div class="col-md-6"><label for="gpPago" class="form-label">Condiciones de pago</label><textarea id="gpPago" class="form-control" rows="2"></textarea></div>
<div class="col-md-6"><label for="gpEntrega" class="form-label">Condiciones de entrega</label><textarea id="gpEntrega" class="form-control" rows="2"></textarea></div>
<div class="col-md-6"><label for="gpTiempo" class="form-label">Tiempo de entrega</label><input id="gpTiempo" class="form-control"></div>
<div class="col-md-6"><label for="gpObservacionesDetalle" class="form-label">Observaciones de esta unidad <i class="ri-question-line text-info ms-1" role="img" aria-label="Ayuda" data-bs-toggle="tooltip" data-bs-placement="top" title="Información específica de esta unidad para la revisión comercial y el PDF."></i></label><textarea id="gpObservacionesDetalle" maxlength="500" class="form-control" rows="2"></textarea><div class="form-text"><i class="ri-information-line me-1"></i>Información específica de esta unidad para la revisión comercial y el PDF.</div></div>
<div class="col-12"><label for="gpObservaciones" class="form-label">Observaciones comerciales generales <i class="ri-question-line text-info ms-1" role="img" aria-label="Ayuda" data-bs-toggle="tooltip" data-bs-placement="top" title="Justifica los ajustes comerciales que revisarán las direcciones."></i></label><textarea id="gpObservaciones" class="form-control" rows="3"></textarea><div class="form-text"><i class="ri-information-line me-1"></i>Justifica los ajustes comerciales que revisarán las direcciones.</div></div>
</div>
<div class="border rounded bg-light-subtle p-3 mt-3"><div class="row text-end g-2"><div class="col-3"><small class="text-muted d-block">Subtotal neto</small><strong id="gpSubtotal">$0.00</strong></div><div class="col-3"><small class="text-muted d-block">Descuento total</small><strong id="gpDescuentoTotal">$0.00</strong></div><div class="col-3"><small class="text-muted d-block">IVA (16%)</small><strong id="gpIva">$0.00</strong></div><div class="col-3"><small class="text-muted d-block">Total</small><strong id="gpTotal" class="text-primary fs-5">$0.00</strong></div></div></div>
<div class="border rounded p-3 mt-3" id="gpRevisionPanel">
<div class="d-flex justify-content-between align-items-center flex-wrap gap-2"><div><strong><i class="ri-user-follow-line me-1 text-primary"></i>Revisión de esta cotización</strong><div id="gpRevisionEstado" class="small text-muted">Selecciona un modelo.</div></div>
<div class="d-flex flex-wrap gap-2">
<button type="button" id="gpEnviarUno" class="btn btn-primary btn-sm d-none"><i class="ri-send-plane-line me-1"></i>Guardar y enviar modelo a revisión</button>
<button type="button" id="gpAprobar" class="btn btn-success btn-sm d-none"><i class="ri-check-double-line me-1"></i>Aprobar</button>
<button type="button" id="gpDevolver" class="btn btn-warning btn-sm d-none"><i class="ri-arrow-go-back-line me-1"></i>Solicitar ajustes</button>
<button type="button" id="gpRechazar" class="btn btn-danger btn-sm d-none"><i class="ri-close-circle-line me-1"></i>Rechazar</button>
<button type="button" id="gpPrepararAjuste" class="btn btn-soft-primary btn-sm d-none"><i class="ri-file-add-line me-1"></i>Crear nueva versión para ajustes</button>
</div></div><div id="gpRevisionHistorial" class="small mt-2"></div></div>
<div class="alert alert-warning border mt-3 mb-0 small"><i class="ri-information-line me-1"></i><strong>Importante:</strong> el botón «Guardar y enviar modelo a revisión» guarda automáticamente los datos capturados antes de solicitar la autorización. «Guardar cambios en borrador» conserva la información sin enviarla.</div>
<div class="d-flex justify-content-end mt-3 gap-2"><button id="gpGuardar" class="btn btn-primary" type="submit"><i class="ri-save-line me-1"></i>Guardar cambios en borrador</button></div>
</form>
<div id="gpVacio" class="text-center text-muted py-5"><i class="ri-file-list-3-line fs-1 d-block mb-2"></i>Selecciona una cotización para consultar su detalle.</div>
</div></div>
<div class="card"><div class="card-header"><h5 class="card-title mb-0"><i class="ri-file-pdf-2-line text-danger me-2"></i>Documentos PDF de la propuesta</h5></div><div class="card-body"><p class="text-muted mb-3">La vista previa por modelo se genera con los datos guardados de la cotización seleccionada. El documento es interno y no está autorizado para envío al dealer.</p><div class="row g-3"><div class="col-md-6"><div class="border rounded p-3 h-100"><div class="d-flex align-items-center gap-2 mb-2"><i class="ri-file-paper-2-line fs-4 text-primary"></i><strong>Propuesta consolidada</strong></div><p class="text-muted small mb-2">Resumen general de todos los modelos y totales de la propuesta.</p><button type="button" id="gpPdfConsolidado" class="btn btn-soft-secondary btn-sm"><i class="ri-eye-line me-1"></i>Ver PDF consolidado</button> <button type="button" id="gpDescargarPdfConsolidado" class="btn btn-outline-secondary btn-sm"><i class="ri-download-line me-1"></i>Descargar PDF</button></div></div><div class="col-md-6"><div class="border rounded p-3 h-100"><div class="d-flex align-items-center gap-2 mb-2"><i class="ri-file-pdf-line fs-4 text-danger"></i><strong>Cotización por modelo</strong></div><p class="text-muted small mb-2">PDF individual con precio, descuento, competencia y condiciones de la cotización seleccionada.</p><button type="button" id="gpPdfModelo" class="btn btn-soft-danger btn-sm" disabled><i class="ri-eye-line me-1"></i>Ver PDF del modelo</button> <button type="button" id="gpDescargarPdfModelo" class="btn btn-outline-danger btn-sm" disabled><i class="ri-download-line me-1"></i>Descargar PDF</button></div></div></div></div></div><div class="card border-primary"><div class="card-header"><h5 class="card-title mb-0"><i class="ri-file-shield-2-line text-primary me-2"></i>Cotización para distribuidor · Vista previa</h5></div><div class="card-body"><div class="alert alert-info mb-3"><i class="ri-information-line me-1"></i>Este formato presenta únicamente modelo, cantidad, precio ofrecido, impuestos y condiciones comerciales. <strong>No incluye precio dealer, descuentos internos ni datos de la competencia.</strong></div><p class="text-muted small">Mientras no exista el flujo de autorizaciones, los documentos generados estarán identificados como <strong>vista previa no autorizada</strong>. No se envían automáticamente.</p><div class="d-flex flex-wrap gap-2"><button type="button" id="gpPdfDistribuidor" class="btn btn-primary btn-sm" disabled><i class="ri-eye-line me-1"></i>Ver vista previa para distribuidor</button><button type="button" id="gpDescargarPdfDistribuidor" class="btn btn-outline-primary btn-sm" disabled><i class="ri-download-line me-1"></i>Descargar vista previa</button></div></div></div>
<div class="alert alert-warning"><i class="ri-lock-line me-1"></i> La solicitud de autorización a Dirección General se integrará en la siguiente etapa. Esta pantalla todavía no envía correos ni cambia estados.</div>
</div></div>
</div></div>
<footer class="footer"><div class="container-fluid"><div class="row"><div class="col-sm-6">© LDR Solutions · MRP</div></div></div></footer></div>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.12/pdfmake.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.12/vfs_fonts.js"></script>
<script>
window.GESTION_PROPUESTA_CLAVE = <?= json_encode($data['clave_propuesta'], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;
window.GESTION_PROPUESTA_CSRF = <?= json_encode($_SESSION['csrf_token'] ?? '', JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;
</script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    if (window.bootstrap && typeof bootstrap.Tooltip === 'function') {
        document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(function (elemento) {
            new bootstrap.Tooltip(elemento, {container: 'body', trigger: 'hover focus'});
        });
    }
});
</script>
<?php footerAdmin($data); ?>
