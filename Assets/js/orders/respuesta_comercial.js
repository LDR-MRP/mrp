/** 2026-11-04 | Aceptación / ajustes desde la sesión autenticada del distribuidor. */
(()=>{'use strict';const cfg=window.PED_PORTAL_REVISION;if(!cfg)return;
 const send=async(respuesta)=>{
  const obs=document.getElementById('comentariosRespuesta')?.value.trim()||'';
  if(['SOLICITAR_AJUSTE','RECHAZAR'].includes(respuesta)&&obs.length<5){if(window.Swal)await Swal.fire({title:'Falta el motivo',text:'Describe el ajuste o rechazo (mínimo 5 caracteres).',icon:'warning'});return;}
  const pregunta=respuesta==='ACEPTAR'?'¿Aceptas todas las condiciones de esta propuesta? Se generará el backorder pendiente.':(respuesta==='RECHAZAR'?'¿Rechazar definitivamente esta propuesta?':'¿Enviar solicitud de ajustes a Comercial?');
  const result=window.Swal?await Swal.fire({title:pregunta,icon:'question',showCancelButton:true,confirmButtonText:'Confirmar',cancelButtonText:'Cancelar'}):{isConfirmed:false};
  if(!result.isConfirmed)return;
  const buttons=[document.getElementById('btnAceptarPropuesta'),document.getElementById('btnSolicitarAjustes'),document.getElementById('btnRechazarPropuesta')];buttons.forEach(b=>{if(b)b.disabled=true;});
  try{
   if(window.Swal)Swal.fire({title:respuesta==='ACEPTAR'?'Confirmando propuesta':respuesta==='RECHAZAR'?'Registrando rechazo':'Enviando solicitud de ajustes',text:'Guardando la respuesta y notificando a Comercial. No cierres esta ventana.',allowOutsideClick:false,allowEscapeKey:false,showConfirmButton:false,didOpen:()=>Swal.showLoading()});
   const response=await fetch(`${window.base_url||location.origin}/orders/responderPropuestaComercial`,{method:'POST',credentials:'same-origin',headers:{'Content-Type':'application/json','X-Ped-CSRF':cfg.csrf},body:JSON.stringify({clave:cfg.clave,respuesta,observaciones:obs})});
   const data=await response.json();if(!response.ok||!data.status)throw new Error(data.msg||'No se pudo registrar la respuesta.');
   if(window.Swal)await Swal.fire({title:'Respuesta registrada',text:data.msg,icon:'success'});
   location.href=`${window.base_url||location.origin}/orders/solicitudescomerciales`;
  }catch(e){if(window.Swal)Swal.fire({title:'Error',text:e.message,icon:'error'});buttons.forEach(b=>{if(b)b.disabled=false;});}
 };
 document.getElementById('btnAceptarPropuesta')?.addEventListener('click',()=>send('ACEPTAR'));
 document.getElementById('btnSolicitarAjustes')?.addEventListener('click',()=>send('SOLICITAR_AJUSTE'));
 document.getElementById('btnRechazarPropuesta')?.addEventListener('click',()=>send('RECHAZAR'));
})();
