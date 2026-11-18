/**
 * Gestión de asignación de VINs de Producto Terminado (pt = 1) en Ped_pedidos/gestionar.
 */
(function () {
    const PAGO_BADGE = {
        0: '<span class="badge bg-warning-subtle text-warning">Pago: Pendiente</span>',
        1: '<span class="badge bg-info-subtle text-info">Pago: Crédito</span>',
        2: '<span class="badge bg-primary-subtle text-primary">Pago: Parcial</span>',
        3: '<span class="badge bg-success-subtle text-success">Pago: Pagada</span>',
    };

    const EST_VIN = {
        '1': '<span class="badge bg-success-subtle text-success">Disponible</span>',
        '2': '<span class="badge bg-secondary-subtle text-secondary">Tatuado / No disp.</span>',
        '3': '<span class="badge bg-info-subtle text-info">Facturado / Salida</span>',
    };

    function esc(s) {
        return String(s ?? '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

    function getPedidoId() {
        return parseInt(window.GESTION_PEDIDO?.idpedido || 0, 10) || 0;
    }

    async function cargarAsignacionesPedido() {
        const idPedido = getPedidoId();
        const cont = document.getElementById('contenidoAsignacionesPedido');
        const resu = document.getElementById('resumenAsignacionesPedido');
        if (!idPedido || !cont) return;

        cont.innerHTML = '<div class="text-center py-4 text-muted">Cargando partidas y VINs asignados...</div>';
        try {
            const resp = await fetch(`${base_url}/ped_pedidos/getAsignacionesPedido/${idPedido}`);
            const json = await resp.json();
            if (!json.status) throw new Error(json.msg || 'No fue posible cargar las asignaciones.');

            const partidas = json.data?.partidas || [];
            const asignaciones = json.data?.asignaciones || [];
            const byDetalle = {};
            asignaciones.forEach(a => {
                const k = String(a.idpedido_detalle);
                if (!byDetalle[k]) byDetalle[k] = [];
                byDetalle[k].push(a);
            });

            const totalReq = partidas.reduce((acc, p) => acc + Math.max(parseInt(p.cantidad_solicitada || 0, 10), parseInt(p.cantidad_autorizada || 0, 10)), 0);
            const totalAsig = asignaciones.length;

            if (resu) {
                resu.innerHTML = `
                    <div class="row g-2">
                        <div class="col-md-4">
                            <div class="border rounded p-2 bg-light-subtle">
                                <div class="text-muted fs-12">Unidades del Pedido</div>
                                <div class="fw-bold fs-16">${totalReq}</div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="border rounded p-2 bg-light-subtle">
                                <div class="text-muted fs-12">VINs PT Asignados</div>
                                <div class="fw-bold fs-16 text-success">${totalAsig}</div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="border rounded p-2 bg-light-subtle">
                                <div class="text-muted fs-12">Pendientes de Asignar VIN</div>
                                <div class="fw-bold fs-16 text-warning">${Math.max(0, totalReq - totalAsig)}</div>
                            </div>
                        </div>
                    </div>`;
            }

            if (!partidas.length) {
                cont.innerHTML = '<div class="alert alert-light border text-muted">Este pedido no tiene partidas activas.</div>';
                return;
            }

            let html = '<div class="table-responsive"><table class="table table-bordered align-middle"><thead class="table-light"><tr>'
                + '<th>Unidad / Modelo</th>'
                + '<th>Entrega Solicitada</th>'
                + '<th class="text-center">Cupo / Asignados</th>'
                + '<th>VINs Asignados</th>'
                + '<th class="text-end">Acción</th>'
                + '</tr></thead><tbody>';

            partidas.forEach(p => {
                const req = Math.max(parseInt(p.cantidad_solicitada || 0, 10), parseInt(p.cantidad_autorizada || 0, 10));
                const list = byDetalle[String(p.idpedido_detalle)] || [];
                const completo = req > 0 && list.length >= req;

                const entregaHtml = p.sucursal_entrega_nombre
                    ? `<span class="badge bg-primary-subtle text-primary">${esc(p.sucursal_entrega_nombre)}</span>`
                    : (p.direccion_entrega ? `<span class="small text-muted">${esc(p.direccion_entrega)}</span>` : '<span class="text-muted small">Sin sede especificada</span>');

                const vinsHtml = list.length
                    ? list.map(a => {
                        const pago = a.estatus_pago !== null && a.estatus_pago !== undefined ? (PAGO_BADGE[parseInt(a.estatus_pago, 10)] || '') : '';
                        const fac = a.factura ? `<span class="badge bg-dark-subtle text-dark">Fac: ${esc(a.factura)}</span>` : '';
                        return `<div class="d-flex align-items-center justify-content-between gap-2 border rounded px-2 py-1 mb-1 bg-light-subtle">
                            <div>
                                <span class="font-monospace fw-semibold">${esc(a.numero_serie)}</span>
                                <div class="small text-muted">${esc(a.planta ? a.planta + ' · ' : '')}${esc(a.almacen)}</div>
                                <div class="d-flex flex-wrap gap-1 mt-1">${fac}${pago}</div>
                            </div>
                            <button type="button" class="btn btn-sm btn-soft-danger py-0 px-2" onclick="liberarVinDePedido(${a.id_asignacion}, '${esc(a.numero_serie)}')" title="Liberar VIN">
                                <i class="ri-close-line"></i> Liberar
                            </button>
                        </div>`;
                    }).join('')
                    : '<span class="text-muted fs-12">Sin VINs asignados aún</span>';

                html += `<tr>
                    <td>
                        <div class="fw-semibold">${esc(p.nombre || p.modelo)}</div>
                        <small class="text-muted">${esc(p.clave_modelo || '')} · inv #${esc(p.inventarioid || 0)}</small>
                    </td>
                    <td>${entregaHtml}</td>
                    <td class="text-center">
                        <span class="badge ${completo ? 'bg-success' : 'bg-warning-subtle text-warning'} fs-12">${list.length} / ${req}</span>
                    </td>
                    <td>${vinsHtml}</td>
                    <td class="text-end">
                        <button type="button" class="btn btn-sm btn-primary" ${completo ? 'disabled' : ''}
                            onclick="abrirModalAsignarVinPedido(${p.idpedido_detalle}, '${esc(p.nombre || p.modelo)}')">
                            <i class="ri-add-line me-1"></i> Asignar VIN
                        </button>
                    </td>
                </tr>`;
            });

            html += '</tbody></table></div>';
            cont.innerHTML = html;
        } catch (err) {
            cont.innerHTML = `<div class="alert alert-danger">${esc(err.message || 'Error cargando asignaciones.')}</div>`;
        }
    }

    async function cargarVinsDisponiblesModal(idDetalle, q = '') {
        const tbody = document.getElementById('tbodyVinsDisponiblesPedido');
        if (!tbody) return;
        tbody.innerHTML = '<tr><td colspan="4" class="text-center text-muted py-4">Consultando almacenes de Producto Terminado (pt=1)...</td></tr>';

        try {
            const url = `${base_url}/ped_pedidos/getVinsDisponiblesParaPedido?idpedido_detalle=${encodeURIComponent(idDetalle)}&q=${encodeURIComponent(q)}`;
            const resp = await fetch(url);
            const json = await resp.json();
            if (!json.status) throw new Error(json.msg || 'No fue posible consultar los VINs.');

            const vins = json.data || [];
            if (!vins.length) {
                tbody.innerHTML = '<tr><td colspan="4" class="text-center text-muted py-4">No hay VINs disponibles en almacenes PT (pt=1) para este modelo.</td></tr>';
                return;
            }

            tbody.innerHTML = vins.map(v => {
                const estBadge = EST_VIN[String(v.estado_vin)] || '';
                const fac = v.factura ? `<div class="small mt-1"><span class="badge bg-info-subtle text-info">Fac: ${esc(v.factura)}</span> <span class="text-muted">${esc(v.distribuidor)}</span></div>` : '';
                const pago = v.estatus_pago !== null && v.estatus_pago !== undefined ? (PAGO_BADGE[parseInt(v.estatus_pago, 10)] || '') : '';
                return `<tr>
                    <td>
                        <div class="font-monospace fw-semibold">${esc(v.numero_serie)}</div>
                        <small class="text-muted">${v.numero_motor ? 'Motor: ' + esc(v.numero_motor) + ' · ' : ''}${esc(v.cve_articulo)}</small>
                    </td>
                    <td>
                        <div>${esc(v.almacen)}</div>
                        <small class="text-muted">${esc(v.planta || v.cve_almacen)}</small>
                    </td>
                    <td>${estBadge} ${pago}${fac}</td>
                    <td class="text-end">
                        <button type="button" class="btn btn-sm btn-success" onclick="confirmarAsignarVinPedido(${idDetalle}, ${v.id_numeros_serie}, '${esc(v.numero_serie)}')">
                            <i class="ri-check-line me-1"></i> Asignar
                        </button>
                    </td>
                </tr>`;
            }).join('');
        } catch (err) {
            tbody.innerHTML = `<tr><td colspan="4" class="text-center text-danger py-3">${esc(err.message)}</td></tr>`;
        }
    }

    window.abrirModalAsignarVinPedido = function (idDetalle, nombreUnidad) {
        document.getElementById('asig_vin_idpedido_detalle').value = idDetalle;
        document.getElementById('inpBuscarVinPedido').value = '';
        const lbl = document.getElementById('lblSubtituloModalVinPedido');
        if (lbl) lbl.textContent = `Partida #${idDetalle} · ${nombreUnidad}`;
        const modalEl = document.getElementById('modalAsignarVinPedido');
        if (modalEl && window.bootstrap) {
            bootstrap.Modal.getOrCreateInstance(modalEl).show();
        }
        cargarVinsDisponiblesModal(idDetalle, '');
    };

    window.confirmarAsignarVinPedido = async function (idDetalle, idSerie, numeroSerie) {
        const idPedido = getPedidoId();
        try {
            const resp = await fetch(`${base_url}/ped_pedidos/asignarVinPedido`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    idpedido: idPedido,
                    idpedido_detalle: idDetalle,
                    id_numeros_serie: idSerie
                })
            });
            const json = await resp.json();
            if (!json.status) throw new Error(json.msg || 'No fue posible asignar el VIN.');

            const modalEl = document.getElementById('modalAsignarVinPedido');
            if (modalEl && window.bootstrap) {
                bootstrap.Modal.getOrCreateInstance(modalEl).hide();
            }
            Swal.fire({ icon: 'success', title: 'VIN Asignado', text: json.msg || `VIN ${numeroSerie} asignado.`, timer: 1600, showConfirmButton: false });
            cargarAsignacionesPedido();
        } catch (err) {
            Swal.fire('No se pudo asignar', err.message || 'Error al asignar VIN', 'error');
        }
    };

    window.liberarVinDePedido = async function (idAsignacion, numeroSerie) {
        const res = await Swal.fire({
            title: `¿Liberar VIN ${numeroSerie}?`,
            input: 'text',
            inputLabel: 'Motivo de liberación (opcional)',
            inputPlaceholder: 'Ej. Reasignación a otro distribuidor',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Sí, liberar',
            cancelButtonText: 'Cancelar'
        });
        if (!res.isConfirmed) return;

        try {
            const resp = await fetch(`${base_url}/ped_pedidos/liberarVinPedido`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    id_asignacion: idAsignacion,
                    motivo: res.value || ''
                })
            });
            const json = await resp.json();
            if (!json.status) throw new Error(json.msg || 'No fue posible liberar el VIN.');

            Swal.fire({ icon: 'success', title: 'VIN Liberado', text: json.msg, timer: 1500, showConfirmButton: false });
            cargarAsignacionesPedido();
        } catch (err) {
            Swal.fire('Error', err.message || 'Error al liberar el VIN', 'error');
        }
    };

    document.addEventListener('DOMContentLoaded', function () {
        const btnRec = document.getElementById('btnRecargarAsignacionesVin');
        if (btnRec) btnRec.addEventListener('click', cargarAsignacionesPedido);

        const btnFilt = document.getElementById('btnFiltrarVinPedido');
        const inpFilt = document.getElementById('inpBuscarVinPedido');
        if (btnFilt) {
            btnFilt.addEventListener('click', () => {
                cargarVinsDisponiblesModal(parseInt(document.getElementById('asig_vin_idpedido_detalle').value || '0', 10), inpFilt?.value || '');
            });
        }
        if (inpFilt) {
            inpFilt.addEventListener('keydown', (e) => {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    cargarVinsDisponiblesModal(parseInt(document.getElementById('asig_vin_idpedido_detalle').value || '0', 10), inpFilt.value || '');
                }
            });
        }

        const tabLink = document.querySelector('a[data-bs-toggle="tab"][href="#tabAsignacion"]');
        if (tabLink) {
            tabLink.addEventListener('shown.bs.tab', cargarAsignacionesPedido);
        }
        if (location.hash === '#tabAsignacion') {
            cargarAsignacionesPedido();
        }
    });
})();
