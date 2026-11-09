/** 2026-11-04 | Revisión comercial: módulo autónomo, cálculos por fila y descuentos MXN/%. */
(()=>{'use strict';
const cfg=window.GESTION_PEDIDO||{};if(!cfg.clave)return;
const base=(window.base_url||location.origin).replace(/\/$/,'');
const tasa=Number(window.PED_REVISION_TASA_IVA??0.16);
const dinero=n=>'$'+Number(n||0).toLocaleString('es-MX',{minimumFractionDigits:2,maximumFractionDigits:2});
const num=v=>{const n=Number(String(v??'').replace(/,/g,'').replace(/\$/g,'').trim());return Number.isFinite(n)?n:NaN};
const alerta=(title,text,icon='info')=>window.Swal?Swal.fire({title,text,icon}):Promise.reject(Error('SweetAlert2 no está disponible'));
const api=async(body)=>{if(window.Swal)Swal.fire({title:body.accion==='enviar'?'Enviando propuesta':body.accion==='guardar'?'Guardando revisión':'Preparando revisión',text:'Estamos registrando la operación. Espera un momento.',allowOutsideClick:false,allowEscapeKey:false,showConfirmButton:false,didOpen:()=>Swal.showLoading()});const r=await fetch(`${base}/ped_pedidos/revisionComercialApi`,{method:'POST',credentials:'same-origin',headers:{'Content-Type':'application/json','X-Ped-CSRF':window.PED_REVISION_CSRF||''},body:JSON.stringify({clave:cfg.clave,...body})});const j=await r.json();if(!r.ok||!j.status)throw Error(j.msg||'No se pudo completar la operación');return j};
const abrirRevision=()=>{sessionStorage.setItem('ped_tab_gestion','tabRevision');location.hash='tabRevision';location.reload()};
const filas=()=>[...document.querySelectorAll('.revision-linea')];
function calcularFila(tr){
 const qty=num(tr.querySelector('.rc-cantidad')?.value),precio=num(tr.querySelector('.rc-precio')?.value),valor=num(tr.querySelector('.rc-descuento')?.value),tipo=tr.querySelector('.rc-descuento-tipo')?.value||'MONTO';
 if(!Number.isInteger(qty)||qty<0||!Number.isFinite(precio)||precio<0||!Number.isFinite(valor)||valor<0||(tipo==='PORCENTAJE'&&valor>100)){
  tr.classList.add('table-danger');return null;
 }
 const subtotal=Math.round(qty*precio*100)/100;
 const descuento=Math.round((tipo==='PORCENTAJE'?subtotal*valor/100:valor)*100)/100;
 if(descuento>subtotal){tr.classList.add('table-danger');return null;}
 tr.classList.remove('table-danger');
 const baseImponible=Math.round((subtotal-descuento)*100)/100,iva=Math.round(baseImponible*tasa*100)/100,total=Math.round((baseImponible+iva)*100)/100;
 const set=(selector,txt)=>{const e=tr.querySelector(selector);if(e)e.textContent=txt};
 set('.rc-operacion',`${qty.toLocaleString('es-MX')} × ${dinero(precio)}`);
 set('.rc-subtotal',`Subtotal: ${dinero(subtotal)}`);
 set('.rc-descuento-aplicado',`Descuento aplicado: −${dinero(descuento)}`);
 set('.rc-base',`Base gravable: ${dinero(baseImponible)}`);
 set('.rc-iva',`IVA (${(tasa*100).toLocaleString('es-MX',{maximumFractionDigits:2})}%): ${dinero(iva)}`);
 set('.rc-total',dinero(total));
 return {idrevision_detalle:Number(tr.dataset.id),cantidad_propuesta:qty,idunidad_color:tr.querySelector('.rc-color')?.value||'',precio_unitario:precio,descuento_tipo:tipo,descuento_valor:valor,motivo_ajuste:tr.querySelector('.rc-motivo')?.value||'',subtotal,descuento,iva,total};
}
function recalcular(){let sub=0,des=0,iva=0,total=0,ok=true;filas().forEach(tr=>{const f=calcularFila(tr);if(!f){ok=false;return;}sub+=f.subtotal;des+=f.descuento;iva+=f.iva;total+=f.total});const set=(id,value)=>{const el=document.getElementById(id);if(el)el.textContent=value};set('rcSubtotal',dinero(sub));set('rcDescuento','−'+dinero(des));set('rcIva',dinero(iva));set('rcTotal',dinero(total));
 const pdf=document.getElementById('propuestaPdfData');if(pdf&&ok){try{const d=JSON.parse(pdf.textContent);d.subtotal=sub;d.descuento=des;d.iva=iva;d.total=total;d.lineas=filas().map(tr=>{const f=calcularFila(tr);return {modelo:tr.querySelector('td strong')?.textContent||'',color:tr.querySelector('.rc-color')?.selectedOptions?.[0]?.textContent||'',cantidad:f.cantidad_propuesta,precio:f.precio_unitario,subtotal:f.subtotal,descuento:f.descuento,iva:f.iva,total:f.total}});pdf.textContent=JSON.stringify(d)}catch(e){}}
 return ok;}
function recoger(){if(!recalcular())throw Error('Corrige los importes marcados en rojo. El descuento no puede superar el subtotal.');return filas().map(calcularFila).map(({subtotal,iva,total,descuento,...rest})=>rest)}
async function ejecutar(accion,extra={}){try{const r=await api({accion,...extra});await alerta('Operación realizada',r.msg||'Información actualizada','success');abrirRevision()}catch(e){await alerta('Revisa la información',e.message,'error')}}
const guardar=()=>{try{ejecutar('guardar',{lineas:recoger(),observaciones:document.getElementById('rcObservaciones')?.value||''})}catch(e){alerta('Datos inválidos',e.message,'warning')}};
document.getElementById('btnCrearRevisionComercial')?.addEventListener('click',()=>ejecutar('crear'));
document.getElementById('btnGuardarRevisionComercial')?.addEventListener('click',guardar);
document.getElementById('btnEnviarRevisionComercial')?.addEventListener('click',async()=>{
 const r=window.Swal?await Swal.fire({title:'¿Enviar propuesta al distribuidor?',text:'Incluso si no modificaste las condiciones, se solicitará la aceptación formal para auditoría.',icon:'question',showCancelButton:true,confirmButtonText:'Sí, enviar',cancelButtonText:'Cancelar'}):{isConfirmed:false};if(!r.isConfirmed)return;
 try{await api({accion:'guardar',lineas:recoger(),observaciones:document.getElementById('rcObservaciones')?.value||''});await api({accion:'enviar'});Swal.close();await alerta('Propuesta enviada','La versión quedó congelada y publicada en el portal.','success');abrirRevision()}catch(e){Swal.close();alerta('No se pudo enviar',e.message,'error')}
});
document.querySelectorAll('.rc-descuento-tipo').forEach(el=>{el.dataset.previous=el.value;el.addEventListener('change',()=>{const tr=el.closest('tr'),valor=tr.querySelector('.rc-descuento'),q=num(tr.querySelector('.rc-cantidad').value),p=num(tr.querySelector('.rc-precio').value),sub=q*p,old=num(valor.value);if(Number.isFinite(sub)&&Number.isFinite(old)&&sub>0){const convertido=el.value==='PORCENTAJE'?old/sub*100:sub*old/100;valor.value=Math.min(el.value==='PORCENTAJE'?100:sub,convertido).toFixed(2)}el.dataset.previous=el.value;recalcular()})});
document.querySelectorAll('.revision-linea input,.revision-linea select').forEach(el=>{el.addEventListener('input',recalcular);el.addEventListener('change',recalcular);if(el.classList.contains('rc-moneda')||el.classList.contains('rc-descuento')){el.addEventListener('focus',()=>{el.value=el.value.replace(/,/g,'')});el.addEventListener('blur',()=>{const n=num(el.value);if(Number.isFinite(n))el.value=n.toLocaleString('en-US',{minimumFractionDigits:2,maximumFractionDigits:2});recalcular()})}});
// Mantiene la pestaña activa al guardar/enviar. No afecta la navegación de otras pestañas.
if(location.hash==='#tabRevision'||sessionStorage.getItem('ped_tab_gestion')==='tabRevision'){sessionStorage.removeItem('ped_tab_gestion');const trigger=document.querySelector('[data-bs-target="#tabRevision"],a[href="#tabRevision"]');if(trigger&&window.bootstrap?.Tab){bootstrap.Tab.getOrCreateInstance(trigger).show()}}
recalcular();
})();
