/* Backorders: filtros, indicadores, modal de detalle y reportes filtrados. */
(() => {
'use strict';
const base=window.PED_BACKORDERS_BASE||window.base_url||location.origin;
const el=id=>document.getElementById(id);
if(!el('boFilas'))return;
let pagina=1,paginas=1,items=[],rapido='';
const esc=v=>String(v??'').replace(/[&<>"']/g,c=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
const entero=v=>Number(v||0).toLocaleString('es-MX');
const dinero=v=>Number(v||0).toLocaleString('es-MX',{style:'currency',currency:'MXN'});
const fecha=v=>v?String(v).slice(0,10).split('-').reverse().join('/'):'—';
const mes=v=>v?new Intl.DateTimeFormat('es-MX',{month:'short',year:'numeric',timeZone:'UTC'}).format(new Date(`${String(v).slice(0,7)}-01T12:00:00Z`)):'—';
const fechaHoy=()=>new Intl.DateTimeFormat('sv-SE',{timeZone:'America/Mexico_City',year:'numeric',month:'2-digit',day:'2-digit'}).format(new Date());
const metric=(titulo,valor,icono)=>`<div class="col-sm-6 col-xl-3"><div class="card bo-kpi"><div class="card-body d-flex justify-content-between align-items-center"><div><div class="text-muted small mb-1">${titulo}</div><div class="bo-kpi-num">${entero(valor)}</div></div><div class="bo-kpi-icon"><i class="${icono}"></i></div></div></div></div>`;
function situacion(x){const t=Number(x.renglones),c=Number(x.cancelados),p=Number(x.pendientes),a=Number(x.asignadas)+Number(x.atendidas_iniciales);if(c===t)return ['Cancelado','cancelado'];if(p===0)return ['Asignado','asignado'];if(a>0)return ['Parcial','parcial'];return ['Pendiente','pendiente'];}
function filtros(){return {pedido:el('boPedido').value.trim(),modelo:el('boModelo').value.trim(),estado:el('boEstado').value,desde:el('boDesde').value,hasta:el('boHasta').value,entrega_desde:el('boEntregaDesde').value,entrega_hasta:el('boEntregaHasta').value,mes:el('boMes').value,rapido};}
function params(f){const p=new URLSearchParams();Object.entries(f).forEach(([k,v])=>{if(v)p.set(k,v);});return p;}
function comprobarRangos(){const f=filtros();if(f.desde&&f.hasta&&f.desde>f.hasta)throw Error('La fecha Desde del pedido no puede superar la fecha Hasta.');if(f.entrega_desde&&f.entrega_hasta&&f.entrega_desde>f.entrega_hasta)throw Error('La fecha inicial de entrega no puede superar la final.');}
async function consultar(url){const res=await fetch(url,{credentials:'same-origin',headers:{Accept:'application/json'},cache:'no-store'});const json=await res.json();if(!res.ok||!json.status)throw Error(json.message||'Error al consultar');return json.data;}
async function cargar(){
 try{comprobarRangos();}catch(e){if(typeof Swal!=='undefined')Swal.fire('Revisa los filtros',e.message,'warning');return;}
 const q=params({...filtros(),pagina:String(pagina)});
 el('boFilas').innerHTML='<tr><td colspan="9" class="text-center py-4"><span class="spinner-border spinner-border-sm me-2"></span>Consultando pedidos…</td></tr>';
 try{
  const d=await consultar(`${base}/ped_backorders/getBackorders?${q}`);items=d.items||[];paginas=d.paginas||1;
  el('boMetricas').innerHTML=metric('Pedidos',d.totales.pedidos,'ri-shopping-cart-2-line')+metric('Unidades pendientes',d.totales.pendientes,'ri-inbox-line')+metric('Por entregar en 7 días',d.totales.proximos,'ri-calendar-event-line')+metric('Sin fecha de entrega',d.totales.sin_fecha,'ri-calendar-close-line');
  el('boFilas').innerHTML=items.length?items.map((x,i)=>{const [estado,tipo]=situacion(x);return `<tr><td><div class="bo-folio">${esc(x.folio_pedido||x.clave)}</div><small class="text-muted">${esc(x.distribuidor)}</small></td><td>${entero(x.modelos)}</td><td>${entero(x.comprometidas)}</td><td>${entero(x.asignadas)}</td><td class="fw-semibold">${entero(x.pendientes)}</td><td>${esc(fecha(x.fecha_requerida))}</td><td>${esc(mes(x.mes_facturacion_deseado))}</td><td><span class="bo-badge bo-pedido-${tipo}">${estado}</span></td><td><button type="button" class="btn btn-sm btn-soft-primary bo-ver" data-index="${i}"><i class="ri-eye-line me-1"></i>Detalle</button></td></tr>`;}).join(''):'<tr><td colspan="9" class="text-center text-muted py-4">Sin resultados para los filtros elegidos.</td></tr>';
  el('boConteo').textContent=`${entero(d.total)} pedidos · Página ${pagina} de ${paginas}`;
  el('boAnterior').disabled=pagina<=1;el('boSiguiente').disabled=pagina>=paginas;
 }catch(e){el('boFilas').innerHTML=`<tr><td colspan="9" class="text-center text-danger py-4">${esc(e.message)}</td></tr>`;}
}
async function detalle(x){
 el('boModalTitulo').textContent=`Pedido ${x.folio_pedido||x.clave}`;el('boModalSubtitulo').textContent=x.distribuidor||'';
 el('boIrPedido').href=`${base}/ped_pedidos/gestionar/${encodeURIComponent(x.clave)}#tabBackorder`;
 el('boDetalleContenido').innerHTML='<div class="text-center py-5"><span class="spinner-border text-primary"></span><p class="mt-2">Consultando renglones…</p></div>';
 bootstrap.Modal.getOrCreateInstance(el('boModal')).show();
 try{
  const rows=await consultar(`${base}/ped_backorders/getDetalle?${new URLSearchParams({clave:x.clave})}`);
  const total=rows.reduce((n,r)=>n+Number(r.total||0),0);
  el('boDetalleContenido').innerHTML=`<div class="alert alert-light border small"><i class="ri-information-line me-1"></i>Fecha de generación: <strong>${esc(fecha(x.fecha_backorder))}</strong> · ${entero(rows.length)} renglones · La asignación no equivale a entrega física.</div><div class="table-responsive"><table class="table table-hover align-middle"><thead><tr><th>Unidad / modelo / SKU</th><th>Color</th><th>Zona / entrega</th><th>Origen</th><th>Comprometidas</th><th>Atendidas históricas</th><th>Asignadas</th><th>Pendientes</th><th>Entrega requerida</th><th>Mes a facturar</th><th>Estado</th><th>Importe</th></tr></thead><tbody>${rows.map(r=>`<tr><td><strong>${esc(r.unidad)}</strong><div class="small text-muted">${esc(r.modelo)}</div><div class="small text-primary">SKU: ${esc(r.sku||'—')}</div></td><td>${esc(r.color||'No especificado')}</td><td><div class="fw-medium">${esc(r.sucursal_entrega||(r.tipo_entrega==='SUCURSAL'?'Sucursal sin nombre':r.tipo_entrega||'Por confirmar'))}</div><small class="text-muted">${esc(r.direccion_entrega||'')}</small></td><td><small>${esc(r.origen)}</small></td><td>${entero(r.cantidad_comprometida)}</td><td>${entero(r.cantidad_atendida_inicial)}</td><td>${entero(r.cantidad_asignada)}</td><td class="fw-semibold">${entero(r.cantidad_pendiente)}</td><td>${esc(fecha(r.fecha_requerida))}</td><td>${esc(mes(r.mes_facturacion_deseado))}</td><td>${esc(r.estatus)}</td><td>${dinero(r.total)}</td></tr>`).join('')}</tbody><tfoot><tr class="table-light fw-bold"><td colspan="11" class="text-end">Total de Backorders:</td><td>${dinero(total)}</td></tr></tfoot></table></div>`;
 }catch(e){el('boDetalleContenido').innerHTML=`<div class="alert alert-danger">${esc(e.message)}</div>`;}
}
function fechaGeneracion(){return new Intl.DateTimeFormat('es-MX',{dateStyle:'short',timeStyle:'short',timeZone:'America/Mexico_City'}).format(new Date());}
function filtrosTexto(){return Object.entries(filtros()).filter(([,v])=>v).map(([k,v])=>`${k}: ${v}`).join(' · ')||'Sin filtros';}
async function obtenerReporte(){comprobarRangos();return consultar(`${base}/ped_backorders/getReporte?${params(filtros())}`);}
async function pdf(){
 if(typeof pdfMake==='undefined') {if(typeof Swal!=='undefined')Swal.fire('PDF no disponible','No se encontró pdfMake en la plantilla.','error');return;}
 const boton=el('boPdf');boton.disabled=true;
 try{
  const d=await obtenerReporte();if(!d.pedidos.length){Swal.fire('Sin resultados','No hay Backorders que coincidan con los filtros.','info');return;}
  const porPedido=new Map();d.detalles.forEach(r=>{if(!porPedido.has(String(r.idpedido)))porPedido.set(String(r.idpedido),[]);porPedido.get(String(r.idpedido)).push(r);});
  let logo=null;try{const r=await fetch(`${base}/Assets/images/logo-cotizaciones.png`);if(r.ok){const blob=await r.blob();logo=await new Promise(resolve=>{const fr=new FileReader();fr.onload=()=>resolve(fr.result);fr.onerror=()=>resolve(null);fr.readAsDataURL(blob);});}}catch(_e){}
  const header=txt=>({text:txt,bold:true,fillColor:'#0b61b3',color:'#fff',fontSize:8,margin:[3,5,3,5]});
  const resumen=[['Pedidos',d.totales.pedidos],['Comprometidas',d.totales.comprometidas],['Asignadas',Number(d.totales.asignadas)],['Pendientes',d.totales.pendientes]];
  const content=[{columns:[logo?{image:logo,width:125}:{text:'FOTON',fontSize:22,color:'#0b61b3',bold:true},{stack:[{text:'REPORTE DE BACKORDERS',bold:true,fontSize:19,color:'#0c2851'},{text:'Generado: '+fechaGeneracion(),fontSize:9},{text:'Filtros: '+filtrosTexto(),fontSize:8,color:'#64748b'}],alignment:'right'}],margin:[0,0,0,16]},
   {columns:resumen.map(([label,val])=>({stack:[{text:label,color:'#334155',fontSize:9},{text:entero(val),fontSize:16,bold:true,color:'#17365d'}],margin:[6,0,6,0]})),margin:[0,0,0,15]}];
  for(const p of d.pedidos){const rows=porPedido.get(String(p.idpedido))||[];
   content.push({table:{widths:['*'],body:[[{text:`${p.folio_pedido||p.clave}  |  ${p.distribuidor}  |  Backorder: ${fecha(p.fecha_backorder)}  |  ${situacion(p)[0]}`,bold:true,color:'#fff',fillColor:'#0c2851',fontSize:9,margin:[5,5,5,5]}]]},layout:'noBorders',margin:[0,8,0,0]});
   const body=[[...['Modelo / SKU','Color','Comprometidas','Asignadas','Pendientes','Entrega programada','Mes a facturar','Precio unitario','Importe'].map(header)]];
   rows.forEach(r=>body.push([{text:`${r.unidad||''}${r.modelo?' · '+r.modelo:''}\nSKU: ${r.sku||'—'}`,fontSize:8},r.color||'—',entero(r.cantidad_comprometida),entero(r.cantidad_asignada),entero(r.cantidad_pendiente),fecha(r.entrega_programada),mes(r.mes_facturar),dinero(r.precio_unitario),dinero(r.total)].map(v=>typeof v==='object'?v:{text:String(v),fontSize:8})));
   body.push([{text:'SUBTOTAL DEL PEDIDO',colSpan:2,bold:true,fillColor:'#e7f3ff'}, {},...['comprometidas','asignadas','pendientes'].map((key)=>({text:entero(key==='asignadas'?Number(p.asignadas):p[key]),bold:true,fillColor:'#e7f3ff',alignment:'center'})),{text:'',fillColor:'#e7f3ff'},{text:'',fillColor:'#e7f3ff'},{text:'',fillColor:'#e7f3ff'},{text:dinero(p.monto_comprometido),bold:true,fillColor:'#e7f3ff',alignment:'center'}]);
   content.push({table:{headerRows:1,dontBreakRows:true,widths:['*',50,58,52,52,79,64,75,83],body},layout:{hLineColor:()=> '#cbd5e1',vLineColor:()=> '#dbe4ee',paddingLeft:()=>5,paddingRight:()=>5,paddingTop:()=>5,paddingBottom:()=>5},margin:[0,0,0,8]});
  }
  content.push({text:`TOTAL GENERAL     Pedidos: ${entero(d.totales.pedidos)}     Comprometidas: ${entero(d.totales.comprometidas)}     Asignadas: ${entero(Number(d.totales.asignadas))}     Pendientes: ${entero(d.totales.pendientes)}     IMPORTE TOTAL: ${dinero(d.totales.monto_comprometido)}`,bold:true,color:'#fff',fillColor:'#0c2851',fontSize:10,alignment:'center',margin:[5,9,5,9]});
  pdfMake.createPdf({pageSize:'A4',pageOrientation:'landscape',pageMargins:[25,30,25,35],content,footer:(cur,total)=>({text:`Backorders · Página ${cur} de ${total}`,alignment:'right',fontSize:8,color:'#64748b',margin:[0,10,25,0]})}).download(`Reporte_Backorders_${fechaHoy()}.pdf`);
 }catch(e){console.error(e);if(typeof Swal!=='undefined')Swal.fire('No fue posible generar el PDF',e.message,'error');}
 finally{boton.disabled=false;}
}
async function excel(){
 const boton=el('boExcel');boton.disabled=true;
 try{
  comprobarRangos();const d=await consultar(`${base}/ped_backorders/getReporte?${params(filtros())}`);
  if(!d.pedidos.length){if(typeof Swal!=='undefined')Swal.fire('Sin resultados','No hay Backorders para exportar.','info');return;}
  // El XLSX se prepara en el servidor usando el mismo filtro validado y los mismos datos.
  window.location.href=`${base}/ped_backorders/exportarExcel?${params(filtros())}`;
 }catch(e){if(typeof Swal!=='undefined')Swal.fire('No fue posible exportar',e.message,'error');}
 finally{boton.disabled=false;}
}
el('boFilas').addEventListener('click',e=>{const btn=e.target.closest('.bo-ver');if(btn&&items[Number(btn.dataset.index)])detalle(items[Number(btn.dataset.index)]);});
el('boFiltros').addEventListener('submit',e=>{e.preventDefault();pagina=1;cargar();});
el('boLimpiar').addEventListener('click',()=>{['boPedido','boModelo','boEstado','boDesde','boHasta','boEntregaDesde','boEntregaHasta','boMes'].forEach(id=>el(id).value='');rapido='';document.querySelectorAll('.bo-quick').forEach(btn=>btn.classList.remove('active'));pagina=1;cargar();});
document.querySelectorAll('.bo-quick').forEach(btn=>btn.addEventListener('click',()=>{rapido=rapido===btn.dataset.rapido?'':btn.dataset.rapido;document.querySelectorAll('.bo-quick').forEach(b=>b.classList.toggle('active',b.dataset.rapido===rapido));pagina=1;cargar();}));
el('boAnterior').addEventListener('click',()=>{if(pagina>1){pagina--;cargar();}});el('boSiguiente').addEventListener('click',()=>{if(pagina<paginas){pagina++;cargar();}});
// Sugerencias de modelos/SKU sin alterar el filtro de texto existente.
consultar(`${base}/ped_backorders/getModelos`).then(rows=>{
 const opciones=new Set();
 (rows||[]).forEach(r=>{if(r.unidad)opciones.add(r.unidad);if(r.modelo)opciones.add(r.modelo);if(r.sku)opciones.add(r.sku);});
 el('boOpcionesModelos').innerHTML=[...opciones].map(v=>`<option value="${esc(v)}"></option>`).join('');
}).catch(e=>console.warn('Sugerencias de modelo no disponibles:',e));
el('boPdf').addEventListener('click',pdf);el('boExcel').addEventListener('click',excel);cargar();
})();
