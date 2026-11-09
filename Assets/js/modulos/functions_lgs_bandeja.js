/* =============================================================================
   functions_lgs_bandeja.js — Bandeja Principal de Logística
   Módulo: Épica 2 — Asignación Global de Destino y Motivo de Envío
   ============================================================================= */

let tableBandeja;

document.addEventListener('DOMContentLoaded', function () {
    initTableBandeja();
});

// ─── DataTable ────────────────────────────────────────────────────────────────

function initTableBandeja() {
    if (tableBandeja) {
        tableBandeja.destroy();
    }
    const estado     = document.getElementById('filtroEstado')?.value     ?? '';
    const activado   = document.getElementById('filtroActivacionComercial')?.value ?? '';
    const finanzas   = document.getElementById('filtroFinanzas')?.value   ?? '';
    const destConf   = document.getElementById('filtroDestinoConfirmado')?.value ?? '';
    const destino    = document.getElementById('filtroDestino')?.value    ?? '';
    const motivo     = document.getElementById('filtroMotivo')?.value     ?? '';
    const busqueda   = document.getElementById('filtroBusqueda')?.value   ?? '';

    tableBandeja = $('#tableBandeja').DataTable({
        aProcessing: true,
        aServerSide: false,
        ajax: {
            url: base_url + '/Lgs_bandeja/getBandeja'
                + '?estado=' + encodeURIComponent(estado)
                + '&activado_comercial=' + encodeURIComponent(activado)
                + '&estatus_finanzas=' + encodeURIComponent(finanzas)
                + '&destino_confirmado=' + encodeURIComponent(destConf)
                + '&destino=' + encodeURIComponent(destino)
                + '&motivo=' + encodeURIComponent(motivo)
                + '&busqueda=' + encodeURIComponent(busqueda),
            dataSrc: function (json) {
                const list = Array.isArray(json) ? json : (json.data || []);
                actualizarKpisBandeja(list);
                return list;
            }
        },
        columns: [
            { data: 'id_lgs_unidad' },
            { 
                data: 'vin',
                render: function(data, _, row) {
                    return '<strong class="text-primary fs-12">' + _esc(data) + '</strong><br><small class="text-muted">N/S: ' + _esc(row.num_serie || 'S/N') + '</small>';
                }
            },
            { 
                data: 'modelo_unidad',
                render: function(data) {
                    return '<span class="badge bg-soft-secondary text-dark">' + _esc(data || 'Unidad') + '</span>';
                }
            },
            { 
                data: 'folio_pedido',
                render: function(data) {
                    return (data && data !== '-') 
                        ? '<span class="badge bg-soft-primary text-primary fw-medium"><i class="ri-file-list-3-line me-1"></i>' + _esc(data) + '</span>'
                        : '<span class="text-muted fs-11">Sin Pedido</span>';
                }
            },
            {
                data: 'destino_pedido',
                render: function (data) {
                    return (data && data !== 'Por Definir') 
                        ? '<div class="fs-12"><i class="ri-map-pin-line text-muted me-1"></i><strong>' + _esc(data) + '</strong><br><span class="badge bg-light text-muted fs-10">Origen: Pedidos</span></div>' 
                        : '<span class="badge bg-soft-secondary text-muted">Sin Destino en Pedidos</span>';
                }
            },
            {
                data: 'destino_descripcion',
                render: function (data, _, row) {
                    if (parseInt(row.destino_confirmado) === 1) {
                        return '<div class="fs-12"><span class="badge bg-soft-success text-success border border-success-subtle px-2 py-1"><i class="ri-checkbox-circle-fill me-1"></i>' + _esc(data) + '</span></div>';
                    }
                    return '<div class="fs-12"><span class="badge bg-soft-warning text-dark border border-warning-subtle px-2 py-1"><i class="ri-time-line me-1"></i>Por Confirmar</span></div>';
                }
            },
            {
                data: 'estatus_finanzas',
                render: function (data) {
                    const st = (data || 'PENDIENTE').toUpperCase();
                    switch(st) {
                        case 'PAGADO':
                            return '<span class="badge bg-success px-2 py-1"><i class="ri-check-line me-1"></i>Pagado</span>';
                        case 'CREDITO_AUTORIZADO':
                            return '<span class="badge bg-info px-2 py-1"><i class="ri-shield-check-line me-1"></i>Crédito Aut.</span>';
                        case 'PARCIAL_AUTORIZADO':
                            return '<span class="badge bg-warning text-dark px-2 py-1">Parcial Aut.</span>';
                        default:
                            return '<span class="badge bg-soft-danger text-danger px-2 py-1">Pendiente Fin.</span>';
                    }
                }
            },
            {
                data: 'activado_comercial',
                render: function (data) {
                    if (parseInt(data) === 1) {
                        return '<span class="badge bg-primary px-2 py-1"><i class="ri-flashlight-fill me-1"></i>Listo Despacho</span>';
                    }
                    return '<span class="badge bg-soft-secondary text-muted px-2 py-1"><i class="ri-time-line me-1"></i>Preplaneación</span>';
                }
            },
            {
                data: 'estado_proceso_texto',
                render: function (data, _, row) {
                    const clases = { 1: 'bg-soft-warning text-warning border', 2: 'bg-soft-primary text-primary border', 3: 'bg-soft-success text-success border' };
                    const cls = clases[row.id_estado_proceso] || 'bg-light text-dark';
                    let html = '<span class="badge ' + cls + '">' + _esc(data) + '</span>';
                    if (row.envio_folio) {
                        html += '<br><a href="' + base_url + '/Lgs_envios/detalle/' + row.envio_id + '" class="badge bg-soft-info text-info border border-info mt-1 text-decoration-none" title="Ver Acomodo / Envío"><i class="ri-truck-line me-1"></i>' + _esc(row.envio_folio) + '</a>';
                    }
                    return html;
                }
            },
            { data: 'options', orderable: false }
        ],
        responsive: true,
        bDestroy: true,
        iDisplayLength: 25,
        order: [[0, 'desc']],
        language: { url: base_url + '/Assets/js/plugins/datatables-es.json' }
    });
}

function recargarBandeja() {
    initTableBandeja();
}

function actualizarKpisBandeja(list) {
    if (!Array.isArray(list)) return;
    const total = list.length;
    const pendientes = list.filter(r => parseInt(r.id_estado_proceso) === 1).length;
    const transito   = list.filter(r => parseInt(r.id_estado_proceso) === 2).length;
    const entregados = list.filter(r => parseInt(r.id_estado_proceso) === 3).length;

    const elTotal = document.getElementById('kpi-total-bandeja');
    const elPend  = document.getElementById('kpi-pendientes-bandeja');
    const elTran  = document.getElementById('kpi-transito-bandeja');
    const elEntr  = document.getElementById('kpi-entregados-bandeja');

    if (elTotal) elTotal.textContent = total;
    if (elPend)  elPend.textContent  = pendientes;
    if (elTran)  elTran.textContent  = transito;
    if (elEntr)  elEntr.textContent  = entregados;
}

// ─── Ver Detalle ─────────────────────────────────────────────────────────────

function fntVerUnidad(idLgsUnidad) {
    document.getElementById('detalleUnidadBody').innerHTML =
        '<div class="text-center py-4"><div class="spinner-border text-info" role="status"></div></div>';
    new bootstrap.Modal(document.getElementById('modalDetalleUnidad')).show();

    fetch(base_url + '/Lgs_bandeja/getUnidad/' + idLgsUnidad)
        .then(r => r.json())
        .then(res => {
            let isOk = (res.status === 'success' || res.status === true || res.success === true || res.code === 200);
            if (!isOk) throw new Error(res.message || 'Error al obtener datos');
            const d = res.data;
            document.getElementById('detalleUnidadBody').innerHTML = `
                <table class="table table-sm table-bordered">
                    <tr><th>VIN</th><td>${_esc(d.vin)}</td><th>N/S</th><td>${_esc(d.num_serie)}</td></tr>
                    <tr><th>Modelo</th><td>${_esc(d.modelo_unidad)}</td><th>Color</th><td>${_esc(d.color_unidad)}</td></tr>
                    <tr><th>Motivo</th><td>${_esc(d.motivo_envio || '—')}</td><th>Destino Tipo</th><td>${_esc(d.tipo_destino || '—')}</td></tr>
                    <tr><th>Destino</th><td colspan="3">${_esc(d.destino_descripcion || '—')}</td></tr>
                    <tr><th>Estado</th><td>${_esc(d.id_estado_proceso)}</td><th>Salida</th><td>${_esc(d.fecha_salida || '—')}</td></tr>
                    <tr><th>Llegada</th><td colspan="3">${_esc(d.fecha_llegada || '—')}</td></tr>
                </table>`;
        })
        .catch(err => {
            document.getElementById('detalleUnidadBody').innerHTML =
                '<div class="alert alert-danger">Error al cargar detalle: ' + _esc(err.message) + '</div>';
        });
}

// ─── Asignar Destino y Motivo ─────────────────────────────────────────────────

function fntAsignarDestino(idLgsUnidad) {
    document.getElementById('asig_id_lgs_unidad').value = idLgsUnidad;
    document.getElementById('asig_id_motivo').value     = '';
    document.getElementById('asig_id_destino').value    = '';
    document.getElementById('asig_destino_descripcion').value = '';
    document.getElementById('asig_vin_label').textContent = '(ID Unidad: ' + idLgsUnidad + ')';

    fetch(base_url + '/Lgs_bandeja/getUnidad/' + idLgsUnidad)
        .then(r => r.json())
        .then(res => {
            let isOk = (res.status === 'success' || res.status === true || res.success === true || res.code === 200);
            if (isOk && res.data) {
                const d = res.data;
                document.getElementById('asig_vin_label').textContent = '(VIN: ' + (d.vin || '') + ' - ' + (d.modelo_unidad || '') + ')';
                if (d.id_motivo) document.getElementById('asig_id_motivo').value = d.id_motivo;
                if (d.id_destino) document.getElementById('asig_id_destino').value = d.id_destino;
                if (d.destino_descripcion) document.getElementById('asig_destino_descripcion').value = d.destino_descripcion;
            }
        })
        .catch(() => {});

    new bootstrap.Modal(document.getElementById('modalAsignarDestino')).show();
}

function fntGuardarDestino() {
    const idLgsUnidad = document.getElementById('asig_id_lgs_unidad').value;
    const idMotivo    = document.getElementById('asig_id_motivo').value;
    const idDestino   = document.getElementById('asig_id_destino').value;
    const destDesc    = document.getElementById('asig_destino_descripcion').value.trim();

    if (!idMotivo || !idDestino) {
        Sys_Core.UI.notify('Seleccione motivo y destino.', 'warning');
        return;
    }

    const fd = new FormData();
    fd.append('id_lgs_unidad',        idLgsUnidad);
    fd.append('id_motivo',            idMotivo);
    fd.append('id_destino',           idDestino);
    fd.append('destino_descripcion',  destDesc);

    document.getElementById('btnGuardarDestino').disabled = true;
    fetch(base_url + '/Lgs_bandeja/asignarDestino', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(res => {
            let isOk = (res.status === 'success' || res.status === true || res.success === true || res.code === 200);
            if (!isOk) throw new Error(res.message || 'Error al guardar');
            bootstrap.Modal.getInstance(document.getElementById('modalAsignarDestino')).hide();
            Sys_Core.UI.notify(res.message || 'Guardado correctamente.', 'success');
            recargarBandeja();
        })
        .catch(err => Sys_Core.UI.notify(err.message, 'error'))
        .finally(() => { document.getElementById('btnGuardarDestino').disabled = false; });
}

// ─── Registrar Fechas ─────────────────────────────────────────────────────────

function fntRegistrarFechas(idLgsUnidad) {
    document.getElementById('fec_id_lgs_unidad').value  = idLgsUnidad;
    document.getElementById('fec_fecha_salida').value   = '';
    document.getElementById('fec_fecha_llegada').value  = '';
    new bootstrap.Modal(document.getElementById('modalFechas')).show();
}

function fntGuardarFechas() {
    const idLgsUnidad = document.getElementById('fec_id_lgs_unidad').value;
    const fechaSalida = document.getElementById('fec_fecha_salida').value  || '';
    const fechaLleg   = document.getElementById('fec_fecha_llegada').value || '';

    const fd = new FormData();
    fd.append('id_lgs_unidad', idLgsUnidad);
    fd.append('fecha_salida',  fechaSalida.replace('T', ' ') + (fechaSalida ? ':00' : ''));
    fd.append('fecha_llegada', fechaLleg.replace('T', ' ')   + (fechaLleg   ? ':00' : ''));

    fetch(base_url + '/Lgs_bandeja/registrarFechas', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(res => {
            let isOk = (res.status === 'success' || res.status === true || res.success === true || res.code === 200);
            if (!isOk) throw new Error(res.message || 'Error al registrar fechas');
            bootstrap.Modal.getInstance(document.getElementById('modalFechas')).hide();
            Sys_Core.UI.notify(res.message, 'success');
            recargarBandeja();
        })
        .catch(err => Sys_Core.UI.notify(err.message, 'error'));
}

// ─── Siguiente Área (Finalizar Traslado) ──────────────────────────────────────

function fntSiguienteArea(idLgsUnidad) {
    Swal.fire({
        title: '¿Confirmar Entrega?',
        text: 'La unidad será marcada como "Entregada". Esta acción no se puede revertir.',
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Sí, marcar entregada',
        cancelButtonText: 'Cancelar',
        confirmButtonColor: '#28a745'
    }).then(result => {
        if (!result.isConfirmed) return;
        const fd = new FormData();
        fd.append('id_lgs_unidad', idLgsUnidad);
        fetch(base_url + '/Lgs_bandeja/siguienteArea', { method: 'POST', body: fd })
            .then(r => r.json())
            .then(res => {
                let isOk = (res.status === 'success' || res.status === true || res.success === true || res.code === 200);
                if (!isOk) throw new Error(res.message || 'Error al actualizar área');
                Sys_Core.UI.notify(res.message, 'success');
                recargarBandeja();
            })
            .catch(err => Sys_Core.UI.notify(err.message, 'error'));
    });
}

// ─── Puntos 5, 8 y 9: Acciones Rápidas en Bandeja ───────────────────────────

/**
 * Confirmar el destino derivado de pedidos con un clic (Punto 5)
 */
function fntConfirmarDestinoPedido(idLgsUnidad, destinoPedido) {
    Swal.fire({
        title: 'Confirmar Destino de Pedidos',
        html: `¿Desea confirmar el destino originado en pedidos: <br><strong class="text-primary">${_esc(destinoPedido)}</strong> como ubicación logística de entrega?`,
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Sí, confirmar destino',
        cancelButtonText: 'Cancelar',
        confirmButtonColor: '#0ab39c'
    }).then(result => {
        if (!result.isConfirmed) return;
        const fd = new FormData();
        fd.append('id_lgs_unidad', idLgsUnidad);

        fetch(base_url + '/Lgs_bandeja/confirmarDestinoPedido', { method: 'POST', body: fd })
            .then(r => r.json())
            .then(res => {
                let isOk = (res.status === 'success' || res.status === true || res.success === true || res.code === 200);
                if (!isOk) throw new Error(res.message || 'Error al confirmar destino');
                if (typeof Sys_Core !== 'undefined' && Sys_Core.UI) {
                    Sys_Core.UI.notify(res.message || 'Destino confirmado', 'success');
                } else {
                    Swal.fire('Éxito', res.message || 'Destino confirmado', 'success');
                }
                recargarBandeja();
            })
            .catch(err => {
                if (typeof Sys_Core !== 'undefined' && Sys_Core.UI) {
                    Sys_Core.UI.notify(err.message, 'error');
                } else {
                    Swal.fire('Error', err.message, 'error');
                }
            });
    });
}

/**
 * Conmutar activación comercial (Punto 9)
 */
function fntToggleActivacion(idLgsUnidad, nextVal) {
    const titulo = (nextVal === 1) ? '¿Activar para Despacho en Firme?' : '¿Regresar a Solo Preplaneación?';
    const texto = (nextVal === 1) 
        ? 'Esta unidad quedará habilitada para agregarse a envíos ejecutables.'
        : 'La unidad quedará limitada a estimaciones de preplaneación sin despacho físico.';

    Swal.fire({
        title: titulo,
        text: texto,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: (nextVal === 1) ? 'Sí, activar para envío' : 'Sí, marcar preplaneación',
        cancelButtonText: 'Cancelar',
        confirmButtonColor: (nextVal === 1) ? '#0284c7' : '#64748b'
    }).then(result => {
        if (!result.isConfirmed) return;
        const fd = new FormData();
        fd.append('id_lgs_unidad', idLgsUnidad);
        fd.append('activado', nextVal);

        fetch(base_url + '/Lgs_bandeja/setActivacionComercial', { method: 'POST', body: fd })
            .then(r => r.json())
            .then(res => {
                let isOk = (res.status === 'success' || res.status === true || res.success === true || res.code === 200);
                if (!isOk) throw new Error(res.message || 'Error al actualizar estatus comercial');
                if (typeof Sys_Core !== 'undefined' && Sys_Core.UI) {
                    Sys_Core.UI.notify(res.message || 'Estatus comercial actualizado', 'success');
                } else {
                    Swal.fire('Éxito', res.message || 'Estatus comercial actualizado', 'success');
                }
                recargarBandeja();
            })
            .catch(err => {
                if (typeof Sys_Core !== 'undefined' && Sys_Core.UI) {
                    Sys_Core.UI.notify(err.message, 'error');
                } else {
                    Swal.fire('Error', err.message, 'error');
                }
            });
    });
}

// ─── Utilidad: escapar HTML ───────────────────────────────────────────────────

function _esc(str) {
    if (str === null || str === undefined) return '';
    return String(str)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}
