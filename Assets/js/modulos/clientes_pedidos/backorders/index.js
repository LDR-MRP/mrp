/* Backorders agrupados por pedido: detalle por renglón bajo demanda. */
(() => {
'use strict';
const base=window.PED_BACKORDERS_BASE||window.base_url||location.origin;
const el=id=>document.getElementById(id);
if(!el('boFilas'))return;
let pagina=1,paginas=1,items=[];
const esc=v=>String(v??'').replace(/[&<>"']/g,c=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
const entero=v=>Number(v||0).toLocaleString('es-MX');
const dinero=v=>Number(v||0).toLocaleString('es-MX',{style:'currency',currency:'MXN'});
const fecha=v=>v?esc(String(v).replace('T',' ').slice(0,16)):'—';
const porcentaje=(a,b)=>Number(b)>0?Math.min(100,Math.max(0,Number(a)/Number(b)*100)):0;
const barra=p=>`<div class="progress" style="height:8px"><div class="progress-bar bg-primary" role="progressbar" style="width:${p}%" aria-valuenow="${p}" aria-valuemin="0" aria-valuemax="100"></div></div><small class="text-muted">${p.toFixed(1)} %</small>`;
const metric=(titulo,valor,icono)=>`<div class="col-sm-6 col-xl-3"><div class="card shadow-sm h-100"><div class="card-body"><div class="text-muted small"><i class="${icono} me-1"></i>${titulo}</div><div class="fs-3 fw-semibold">${entero(valor)}</div></div></div></div>`;
function situacion(x){const t=Number(x.renglones),c=Number(x.cancelados),p=Number(x.pendientes),a=Number(x.asignadas)+Number(x.atendidas_iniciales);if(c===t)return ['CANCELADO','danger'];if(p===0)return ['ASIGNADO','success'];if(a>0)return ['PARCIAL','info'];return ['PENDIENTE','warning'];}
async function cargar(){
 const params=new URLSearchParams({pedido:el('boPedido').value.trim(),estado:el('boEstado').value,pagina:String(pagina)});
 el('boFilas').innerHTML='<tr><td colspan="8" class="text-center py-4"><span class="spinner-border spinner-border-sm me-2"></span>Consultando pedidos…</td></tr>';
 try{
  const res=await fetch(`${base}/ped_backorders/getBackorders?${params}`,{credentials:'same-origin',headers:{Accept:'application/json'}});
  const json=await res.json();if(!res.ok||!json.status)throw Error(json.message||'Error al consultar');
  const d=json.data;items=d.items||[];paginas=d.paginas;
  el('boMetricas').innerHTML=metric('Pedidos',d.totales.pedidos,'ri-file-list-3-line')+metric('Renglones',d.totales.renglones,'ri-list-check')+metric('Unidades comprometidas',d.totales.comprometidas,'ri-car-line')+metric('Unidades pendientes',d.totales.pendientes,'ri-time-line');
  el('boFilas').innerHTML=items.length?items.map((x,i)=>{const p=porcentaje(Number(x.asignadas)+Number(x.atendidas_iniciales),x.comprometidas),[estado,clase]=situacion(x);return `<tr><td><div class="fw-semibold">${esc(x.folio_pedido||x.clave)}</div><div class="text-muted small">${esc(x.distribuidor)}</div></td><td>${fecha(x.fecha_backorder)}</td><td>${entero(x.comprometidas)}</td><td>${entero(Number(x.asignadas)+Number(x.atendidas_iniciales))}<div class="text-muted small">Históricas: ${entero(x.atendidas_iniciales)}</div></td><td class="fw-semibold">${entero(x.pendientes)}</td><td style="min-width:125px">${barra(p)}</td><td><span class="badge bg-${clase}-subtle text-${clase}">${estado}</span></td><td><button type="button" class="btn btn-sm btn-soft-primary bo-ver" data-index="${i}" title="Ver renglones"><i class="ri-eye-line me-1"></i>Detalle</button></td></tr>`;}).join(''):'<tr><td colspan="8" class="text-center text-muted py-4">Sin resultados.</td></tr>';
  el('boConteo').textContent=`${entero(d.total)} pedidos · Página ${pagina} de ${paginas}`;
  el('boAnterior').disabled=pagina<=1;el('boSiguiente').disabled=pagina>=paginas;
 }catch(e){el('boFilas').innerHTML=`<tr><td colspan="8" class="text-center text-danger py-4">${esc(e.message)}</td></tr>`;}
}
async function detalle(x){
 el('boModalTitulo').textContent=`Pedido ${x.folio_pedido||x.clave}`;
 el('boModalSubtitulo').textContent=x.distribuidor||'';
 el('boIrPedido').href=`${base}/ped_pedidos/gestionar/${encodeURIComponent(x.clave)}#tabBackorder`;
 el('boDetalleContenido').innerHTML='<div class="text-center py-5"><span class="spinner-border text-primary"></span><p class="mt-2">Consultando renglones…</p></div>';
 const modal=bootstrap.Modal.getOrCreateInstance(el('boModal'));modal.show();
 try{
  const res=await fetch(`${base}/ped_backorders/getDetalle?${new URLSearchParams({clave:x.clave})}`,{credentials:'same-origin'});
  const json=await res.json();if(!res.ok||!json.status)throw Error(json.message||'Error al consultar detalle');
  const rows=json.data||[];
  el('boDetalleContenido').innerHTML=`<div class="alert alert-light border small"><i class="ri-information-line me-1"></i>Fecha de generación: <strong>${fecha(x.fecha_backorder)}</strong> · ${entero(rows.length)} renglones · Avance de asignación, no de entrega.</div><div class="table-responsive"><table class="table table-hover align-middle"><thead><tr><th>Unidad / modelo</th><th>Color</th><th>Zona / lugar de entrega</th><th>Origen</th><th>Comprometidas</th><th>Atendidas históricas</th><th>Asignadas</th><th>Pendientes</th><th>Avance</th><th>Estado</th><th>Importe</th></tr></thead><tbody>${rows.map(r=>{const p=porcentaje(Number(r.cantidad_atendida_inicial)+Number(r.cantidad_asignada),r.cantidad_comprometida);return `<tr><td><strong>${esc(r.unidad)}</strong><div class="small text-muted">${esc(r.modelo)}</div></td><td>${esc(r.color||'No especificado')}</td><td><div class="fw-medium">${esc(r.sucursal_entrega || (r.tipo_entrega==='SUCURSAL'?'Sucursal sin nombre':r.tipo_entrega==='DIRECCION'?'Dirección indicada':r.tipo_entrega||'Por confirmar'))}</div><small class="text-muted">${esc(r.direccion_entrega||'')}</small></td><td><small>${esc(r.origen)}</small></td><td>${entero(r.cantidad_comprometida)}</td><td>${entero(r.cantidad_atendida_inicial)}</td><td>${entero(r.cantidad_asignada)}</td><td class="fw-semibold">${entero(r.cantidad_pendiente)}</td><td style="min-width:110px">${barra(p)}</td><td>${esc(r.estatus)}</td><td>${dinero(r.total)}</td></tr>`;}).join('')}</tbody></table></div>`;
 }catch(e){el('boDetalleContenido').innerHTML=`<div class="alert alert-danger">${esc(e.message)}</div>`;}
}
el('boFilas').addEventListener('click',e=>{const btn=e.target.closest('.bo-ver');if(btn&&items[Number(btn.dataset.index)])detalle(items[Number(btn.dataset.index)]);});
el('boFiltros').addEventListener('submit',e=>{e.preventDefault();pagina=1;cargar();});
el('boLimpiar').addEventListener('click',()=>{el('boPedido').value='';el('boEstado').value='';pagina=1;cargar();});
el('boAnterior').addEventListener('click',()=>{if(pagina>1){pagina--;cargar();}});
el('boSiguiente').addEventListener('click',()=>{if(pagina<paginas){pagina++;cargar();}});
cargar();
})();
