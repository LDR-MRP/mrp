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
    const estado   = document.getElementById('filtroEstado')?.value   ?? '';
    const destino  = document.getElementById('filtroDestino')?.value  ?? '';
    const motivo   = document.getElementById('filtroMotivo')?.value   ?? '';
    const busqueda = document.getElementById('filtroBusqueda')?.value ?? '';

    tableBandeja = $('#tableBandeja').DataTable({
        aProcessing: true,
        aServerSide: false,
        ajax: {
            url: base_url + '/Lgs_bandeja/getBandeja'
                + '?estado=' + encodeURIComponent(estado)
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
            { data: 'vin' },
            { data: 'num_serie' },
            { data: 'modelo_unidad' },
            {
                data: 'motivo_envio',
                render: function (data) {
                    return data ? '<span class="badge bg-info">' + _esc(data) + '</span>' : '<span class="text-muted">—</span>';
                }
            },
            {
                data: 'tipo_destino',
                render: function (data) {
                    return data ? '<span class="badge bg-secondary">' + _esc(data) + '</span>' : '<span class="text-muted">—</span>';
                }
            },
            {
                data: 'destino_descripcion',
                render: function (data) {
                    return data ? _esc(data) : '<span class="text-muted">—</span>';
                }
            },
            {
                data: 'estado_proceso_texto',
                render: function (data, _, row) {
                    const clases = { 1: 'bg-warning text-dark', 2: 'bg-primary', 3: 'bg-success' };
                    const cls = clases[row.id_estado_proceso] || 'bg-secondary';
                    return '<span class="badge ' + cls + '">' + _esc(data) + '</span>';
                }
            },
            {
                data: 'fecha_salida',
                render: function (data) { return data ? _esc(data) : '<span class="text-muted">—</span>'; }
            },
            {
                data: 'fecha_llegada',
                render: function (data) { return data ? _esc(data) : '<span class="text-muted">—</span>'; }
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
            if (!res.success) throw new Error(res.message);
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
            if (!res.success) throw new Error(res.message);
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
            if (!res.success) throw new Error(res.message);
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
                if (!res.success) throw new Error(res.message);
                Sys_Core.UI.notify(res.message, 'success');
                recargarBandeja();
            })
            .catch(err => Sys_Core.UI.notify(err.message, 'error'));
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
