/** Exportación local CSV de bitácora; no contiene IDs internos en la URL. */
(()=>{'use strict';document.getElementById('btnReporteHistorial')?.addEventListener('click',()=>{
 const el=document.getElementById('pedHistorialJson');if(!el)return;
 let rows=[];try{rows=JSON.parse(el.textContent)}catch(e){return}
 const cols=['fecha','tipo','descripcion','responsable','anterior','nuevo'];
 const esc=x=>'"'+String(x??'').replace(/"/g,'""').replace(/^[=+@\-]/,'\'$&')+'"';
 const csv='\ufeff'+[cols.join(';'),...rows.map(r=>cols.map(k=>esc(r[k])).join(';'))].join('\r\n');
 const url=URL.createObjectURL(new Blob([csv],{type:'text/csv;charset=utf-8'}));
 const a=document.createElement('a');a.href=url;a.download=`Historial_${String(window.GESTION_PEDIDO?.folio||'pedido').replace(/[^a-zA-Z0-9_-]/g,'_')}.csv`;a.click();URL.revokeObjectURL(url);
});})();


/* PDF de auditoría: conserva CSV y exporta los eventos de la misma fuente JSON. */
(()=>{'use strict';
const btn=document.getElementById('btnReporteHistorialPdf');if(!btn)return;
const load=url=>new Promise((resolve,reject)=>{const s=document.createElement('script');s.src=url;s.onload=resolve;s.onerror=reject;document.head.appendChild(s)});
const clean=v=>String(v??'—');
btn.addEventListener('click',async()=>{
 const el=document.getElementById('pedHistorialJson');let rows=[];
 try{rows=JSON.parse(el?.textContent||'[]');}catch(e){return Swal.fire({icon:'error',title:'Historial no disponible'});}
 btn.disabled=true;
 Swal.fire({title:'Preparando reporte PDF',text:'Generando el historial de auditoría...',allowOutsideClick:false,allowEscapeKey:false,didOpen:()=>Swal.showLoading()});
 try{
  if(!window.pdfMake){await load('https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.20/pdfmake.min.js');await load('https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.20/vfs_fonts.js');}
  const base=(window.base_url||location.origin).replace(/\/$/,'');let logo=null;
  try{const r=await fetch(base+'/Assets/images/logo-cotizaciones.png');if(r.ok){const b=await r.blob();logo=await new Promise((ok,fail)=>{const fr=new FileReader();fr.onload=()=>ok(fr.result);fr.onerror=fail;fr.readAsDataURL(b)});}}catch(e){}
  const folio=clean(window.GESTION_PEDIDO?.folio||'Pedido');
  const body=[[{text:'Fecha',bold:true},{text:'Evento / descripción',bold:true},{text:'Responsable',bold:true},{text:'Cambio de estado',bold:true}].map(x=>({...x,fillColor:'#e8eef5',margin:5}))];
  rows.forEach(r=>body.push([clean(r.fecha),clean(r.tipo)+'\n'+clean(r.descripcion),clean(r.responsable),clean(r.anterior)+' → '+clean(r.nuevo)].map(t=>({text:t,fontSize:8,margin:5}))));
  if(!rows.length)body.push([{text:'Sin eventos registrados',colSpan:4,alignment:'center',margin:12},{},{},{}]);
  const doc={pageSize:'A4',pageOrientation:'landscape',pageMargins:[34,36,34,45],content:[{columns:[logo?{image:logo,fit:[145,52]}:{text:'FOTON',bold:true,fontSize:20},{text:'REPORTE DE TRAZABILIDAD\n'+folio,alignment:'right',fontSize:15,bold:true,color:'#26354b'}],margin:[0,0,0,16]},{text:'Auditoría de eventos · '+rows.length+' registros · Generado '+new Date().toLocaleString('es-MX'),fontSize:9,color:'#64748b',margin:[0,0,0,14]},{table:{headerRows:1,widths:[100,'*',110,135],body},layout:'lightHorizontalLines'}],footer:(n,total)=>({text:'Reporte de auditoría · Página '+n+' de '+total,alignment:'right',fontSize:8,margin:[0,0,35,0],color:'#64748b'}),defaultStyle:{fontSize:9}};
  Swal.close();window.pdfMake.createPdf(doc).download('Historial_'+folio.replace(/[^a-zA-Z0-9_-]/g,'_')+'.pdf');
 }catch(e){Swal.fire({icon:'error',title:'No se pudo generar el PDF',text:'Verifica que pdfMake esté disponible. '+e.message});}finally{btn.disabled=false;}
});
})();
