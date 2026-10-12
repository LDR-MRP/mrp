"use strict";
(() => {
  const root = `${base_url}/ped_cotizaciones`;
  const clave = window.GESTION_PROPUESTA_CLAVE || "";
  const $ = id => document.getElementById(id);
  const esc = v => String(v ?? "").replace(/[&<>"']/g, c => ({"&":"&amp;","<":"&lt;",">":"&gt;",'"':"&quot;","'":"&#39;"}[c]));
  const dinero = v => new Intl.NumberFormat("es-MX", {style:"currency",currency:"MXN"}).format(Number(v)||0);
  const limpiarMoneda = v => String(v ?? "").replace(/[,\s$]/g, "").trim();
  const formatoMiles = v => {
    const valor = limpiarMoneda(v);
    if (!valor || !Number.isFinite(Number(valor))) return "";
    return new Intl.NumberFormat("es-MX", {minimumFractionDigits:2, maximumFractionDigits:2}).format(Number(valor));
  };
  const loading = texto => Swal.fire({title:texto, text:"Espere un momento, estamos procesando su solicitud.",allowOutsideClick:false,allowEscapeKey:false,showConfirmButton:false,didOpen:()=>Swal.showLoading()});
  let cotizaciones = [], seleccionada = null, propuestaActual = null, seguimiento = {}, puedeEnviar = false;
  let formularioInicial = null;
  let operacionActiva = false;
  const campo = (id,v) => {$(id).value = v ?? "";};
  const fecha = v => String(v||"").slice(0,10);
  function error(m) {$("alertaGestion").textContent=m;$("alertaGestion").classList.remove("d-none");}
  function limpiarError() {$("alertaGestion").classList.add("d-none");}
  async function json(url, options={}) {
    const r=await fetch(url,{credentials:"same-origin",headers:{Accept:"application/json",...(options.headers||{})},...options});
    const data=await r.json();
    if(!r.ok || !data.status) throw new Error(data.message || "Error al procesar la solicitud.");
    return data.data;
  }
  function calcular() {
    const cantidad=Number($("gpCantidad").value);
    const dealer=Number(seleccionada?.detalle?.precio_dealer);
    const tipo=$("gpTipoDescuento").value;
    const valor=Number($("gpDescuento").value);
    const valido=Number.isSafeInteger(cantidad)&&cantidad>=1&&cantidad<=100000&&
      Number.isFinite(dealer)&&dealer>=0&&Number.isFinite(valor)&&valor>=0&&
      (tipo!=="PORCENTAJE"||valor<=100);
    const descuentoUnitario=valido ? Math.round((tipo==="PORCENTAJE"?dealer*valor/100:valor)*100)/100 : 0;
    const correcto=valido&&descuentoUnitario<=dealer;
    const precio=correcto?Math.round((dealer-descuentoUnitario)*100)/100:0;
    const subtotal=correcto?Math.round(precio*cantidad*100)/100:0;
    const descuentoTotal=correcto?Math.round(descuentoUnitario*cantidad*100)/100:0;
    const iva=Math.round(subtotal*.16*100)/100;
    $("gpDescuentoLabel").textContent=tipo==="PORCENTAJE"?"Descuento porcentual (%) *":"Descuento en pesos por unidad *";
    $("gpDescuento").step=tipo==="PORCENTAJE"?"0.0001":"0.01";
    $("gpDescuento").max=tipo==="PORCENTAJE"?"100":String(dealer);
    campo("gpDescuentoUnitario",dinero(descuentoUnitario));
    campo("gpPrecio",dinero(precio));
    $("gpDescuentoTotal").textContent=dinero(descuentoTotal);
    $("gpSubtotal").textContent=dinero(subtotal);
    $("gpIva").textContent=dinero(iva);
    $("gpTotal").textContent=dinero(subtotal+iva);
    return correcto;
  }
  function listar() {
    $("gpListado").innerHTML=cotizaciones.length ? cotizaciones.map(c=>`
      <button type="button" class="btn w-100 text-start border rounded p-3 mb-2 ${seleccionada?.clave===c.clave?"border-primary bg-primary-subtle":""}" data-clave="${esc(c.clave)}">
        <span class="d-flex justify-content-between gap-2"><strong>${esc(c.modelo)}</strong><span class="badge bg-secondary-subtle text-secondary">${esc(c.estatus)}</span></span>
        <small class="text-muted d-block">${esc(c.version_modelo)} · ${esc(c.folio_cotizacion)}</small>
        <span class="d-block mt-1 fw-semibold">${dinero(c.total)}</span>
      </button>`).join("") : '<div class="text-muted">No hay cotizaciones registradas.</div>';
  }
  function seleccionar(c) {
    seleccionada=c;listar();limpiarError();
    $("gpFormulario").classList.remove("d-none");$("gpVacio").classList.add("d-none");
    $("gpCotEstado").textContent=c.estatus;
    campo("gpModelo",`${c.modelo} · ${c.version_modelo}`);
    campo("gpCotFolio",c.folio_cotizacion);campo("gpVersion",c.version_actual);
    campo("gpPublico",dinero(c.detalle?.precio_publico));campo("gpDealer",dinero(c.detalle?.precio_dealer));
    campo("gpCantidad",c.detalle?.cantidad);
    campo("gpClienteFinal",c.detalle?.nombre_cliente_final);
    campo("gpCompetidor",c.detalle?.nombre_competidor);
    campo("gpModeloCompetidor",c.detalle?.modelo_competidor);
    campo("gpPrecioCompetidor",c.detalle?.precio_competidor == null ? "" : formatoMiles(c.detalle.precio_competidor));
    campo("gpObservacionesDetalle",c.detalle?.observaciones);
    const tipo=c.detalle?.tipo_descuento||"MONTO";
    campo("gpTipoDescuento",tipo);
    campo("gpDescuento",tipo==="PORCENTAJE"?Number(c.detalle?.descuento_porcentaje||0):Number(c.detalle?.descuento_unitario||0));
    campo("gpVigencia",fecha(c.fecha_vigencia));campo("gpPago",c.condiciones_pago);
    campo("gpEntrega",c.condiciones_entrega);campo("gpTiempo",c.tiempo_entrega);
    campo("gpObservaciones",c.observaciones);
    const editable=c.estatus==="BORRADOR";
    $("gpFormulario").querySelectorAll("input:not([readonly]),textarea,select").forEach(e=>e.disabled=!editable);
    $("gpGuardar").classList.toggle("d-none",!editable);
    calcular();
    pintarRevision();
    formularioInicial = firmaFormulario();
    ["gpPdfModelo", "gpDescargarPdfModelo", "gpPdfDistribuidor", "gpDescargarPdfDistribuidor"].forEach(id => {if ($(id)) $(id).disabled = false;});
  }
  async function cargar(preferida) {
    const d=await json(`${root}/getGestionPropuesta/${encodeURIComponent(clave)}`);
    const p=d.propuesta;propuestaActual=p;cotizaciones=d.cotizaciones||[];seguimiento=d.seguimiento||{};puedeEnviar=!!d.puede_enviar;
    $("gpFolio").textContent=p.folio_propuesta;
    $("gpCliente").textContent=p.nombre_comercial||p.razon_social||"—";
    $("gpCorreo").textContent=p.correo_distribuidor||"";
    $("gpOrigen").textContent=p.tipo_propuesta;
    $("gpEstado").textContent=p.estatus;
    const buscada=cotizaciones.find(c=>c.clave===(preferida||seleccionada?.clave))||cotizaciones[0];
    if(buscada)seleccionar(buscada);else listar();
    pintarSeguimiento();
  }
  // Los mismos datos y validaciones se usan al guardar y al enviar a revisión.
  function firmaFormulario() {
    return JSON.stringify([...$("gpFormulario").querySelectorAll("input:not([readonly]), textarea, select")]
      .map(el => [el.id, el.value]));
  }
  function obtenerPayloadBorrador() {
    if (!seleccionada || seleccionada.estatus !== "BORRADOR") throw new Error("El modelo ya no está editable.");
    const cantidad = Number($("gpCantidad").value);
    const valor = Number($("gpDescuento").value);
    if (!calcular() || !$("gpFormulario").checkValidity()) {
      $("gpFormulario").reportValidity();
      throw new Error("Verifica cantidad, descuento y los campos obligatorios antes de enviar.");
    }
    const competidor = limpiarMoneda($("gpPrecioCompetidor").value);
    if (competidor !== "" && (!Number.isFinite(Number(competidor)) || Number(competidor) < 0 || Number(competidor) > 9999999999999.99)) {
      throw new Error("Revisa el precio del competidor.");
    }
    return {
      clave_propuesta:clave, clave_cotizacion:seleccionada.clave, cantidad,
      tipo_descuento:$("gpTipoDescuento").value, valor_descuento:valor,
      nombre_cliente_final:$("gpClienteFinal").value,
      nombre_competidor:$("gpCompetidor").value,
      modelo_competidor:$("gpModeloCompetidor").value,
      precio_competidor:competidor,
      observaciones_detalle:$("gpObservacionesDetalle").value,
      fecha_vigencia:$("gpVigencia").value, condiciones_pago:$("gpPago").value,
      condiciones_entrega:$("gpEntrega").value, tiempo_entrega:$("gpTiempo").value,
      observaciones:$("gpObservaciones").value
    };
  }
  async function persistirBorrador() {
    const payload = obtenerPayloadBorrador();
    const firma = firmaFormulario();
    await json(`${root}/guardarBorradorCotizacion`, {
      method:"POST", headers:{"Content-Type":"application/json","X-CSRF-Token":window.GESTION_PROPUESTA_CSRF||""},
      body:JSON.stringify(payload)
    });
    formularioInicial = firma;
  }
  async function guardar(e) {
    e.preventDefault();
    if (operacionActiva || !seleccionada || seleccionada.estatus !== "BORRADOR") return;
    operacionActiva = true;
    $("gpGuardar").disabled = true;
    loading("Guardando cotización...");
    try {
      const actual = seleccionada.clave;
      await persistirBorrador();
      await cargar(actual);
      Swal.close();
      await Swal.fire("Cambios guardados", "La cotización se actualizó en su versión actual.", "success");
    } catch(e) { Swal.close(); await Swal.fire("No fue posible guardar", e.message, "error"); }
    finally { operacionActiva = false; $("gpGuardar").disabled = false; }
  }
  // PERSONALIZACIÓN PDF: cambia esta ruta por la ubicación de tu logotipo.
  // Coloca tu imagen en: Assets/images/logo-cotizaciones.png (preferible PNG transparente).
  const PDF_LOGO_URL = `${base_url}/Assets/images/logo-cotizaciones.png`;
  const PDF_AZUL = "#1B2B86";
  const PDF_TEXTO = "#243047";
  const PDF_GRIS = "#64748B";
  const PDF_LINEA = "#E3E8F1";

  async function logoPdfBase64(url) {
    try {
      const respuesta = await fetch(url, {credentials:"same-origin"});
      if (!respuesta.ok) return null;
      const blob = await respuesta.blob();
      if (!/^image\/(png|jpeg)$/i.test(blob.type)) return null;
      return await new Promise((resolve,reject) => {
        const reader = new FileReader();
        reader.onload = () => resolve(reader.result);
        reader.onerror = reject;
        reader.readAsDataURL(blob);
      });
    } catch (_) { return null; } // Si falta el logo, el PDF se genera igualmente.
  }

  async function generarPdfModelo(descargar) {
    if (!seleccionada || !propuestaActual) return;
    if (!window.pdfMake || typeof window.pdfMake.createPdf !== "function") {
      await Swal.fire("No se pudo generar el PDF", "No se cargó pdfMake. Verifica la conexión o instala pdfMake localmente.", "error");
      return;
    }
    // Datos guardados en BD: los cambios pendientes no se incluyen en el PDF.
    const c=seleccionada, d=c.detalle||{}, p=propuestaActual;
    const texto=v=>v===null||v===undefined||String(v).trim()===""?"—":String(v);
    const fechaFmt=v=>{const f=String(v||"").slice(0,10);if(!/^\d{4}-\d{2}-\d{2}$/.test(f))return "Pendiente de envío";const [y,m,dia]=f.split("-");return `${dia}/${m}/${y}`;};
    const n=v=>Number(v)||0;
    const celda=(valor,op={})=>({text:texto(valor),fontSize:9,color:PDF_TEXTO,margin:[0,4,0,4],...op});
    const etiqueta=(valor)=>celda(valor,{bold:true,color:PDF_GRIS,fontSize:8});
    const bloque=(titulo,contenido)=>({stack:[
      {text:titulo.toUpperCase(),bold:true,fontSize:10,color:PDF_AZUL,margin:[0,16,0,8]},
      contenido
    ],unbreakable:true});
    const tablaDatos=filas=>({table:{widths:[150,"*"],body:filas.map(([k,v])=>[etiqueta(k),celda(v)])},
      layout:{hLineWidth:(i,node)=>i===0||i===node.table.body.length?0:0.5,hLineColor:()=>PDF_LINEA,vLineWidth:()=>0,
        paddingLeft:()=>10,paddingRight:()=>10,paddingTop:()=>3,paddingBottom:()=>3}});
    const filaImporte=(nombre,valor,op={})=>[
      celda(nombre,{bold:!!op.bold,color:op.color||PDF_TEXTO}),
      celda(valor,{alignment:"right",bold:!!op.bold,color:op.color||PDF_TEXTO})
    ];
    const cantidad=n(d.cantidad);
    const modelo=[c.modelo,c.version_modelo].filter(Boolean).join(" · ");
    const logo=await logoPdfBase64(PDF_LOGO_URL);
    const cabeceraIzquierda=logo?{image:logo,fit:[135,58],alignment:"left"}:
      {text:"LOGOTIPO",fontSize:10,bold:true,color:PDF_GRIS,margin:[0,16,0,0]};
    const dd={
      pageSize:"A4",pageMargins:[42,36,42,60],
      defaultStyle:{fontSize:9,color:PDF_TEXTO},
      footer:(pagina,total)=>({margin:[42,14,42,0],stack:[
        {canvas:[{type:"line",x1:0,y1:0,x2:511,y2:0,lineColor:PDF_LINEA,lineWidth:1}]},
        {columns:[{text:"DOCUMENTO INTERNO · VISTA PREVIA · NO ENVIAR AL DEALER",color:"#A53B43",fontSize:7,bold:true},
          {text:`Página ${pagina} de ${total}`,alignment:"right",fontSize:8,color:PDF_GRIS}],margin:[0,8,0,0]}
      ]}),
      content:[
        {columns:[
          {width:160,stack:[cabeceraIzquierda]},
          {width:"*",stack:[
            {text:"COTIZACIÓN COMERCIAL",alignment:"right",fontSize:18,bold:true,color:PDF_AZUL},
            {text:`FOLIO ${texto(c.folio_cotizacion)}`,alignment:"right",fontSize:10,bold:true,color:PDF_TEXTO,margin:[0,5,0,0]},
            {text:`Versión ${texto(c.version_actual)}  ·  ${texto(c.estatus)}`,alignment:"right",fontSize:8,color:PDF_GRIS,margin:[0,4,0,0]}
          ]}
        ],columnGap:15},
        {canvas:[{type:"rect",x:0,y:0,w:511,h:5,color:PDF_AZUL}],margin:[0,15,0,14]},
        {columns:[
          {width:"55%",stack:[{text:"PROPUESTA",fontSize:8,bold:true,color:PDF_GRIS,margin:[0,0,0,4]},
            {text:texto(p.folio_propuesta),fontSize:10,bold:true,color:PDF_TEXTO}]},
          {width:"45%",stack:[{text:"DISTRIBUIDOR",fontSize:8,bold:true,color:PDF_GRIS,margin:[0,0,0,4]},
            {text:texto(p.nombre_comercial||p.razon_social),fontSize:10,bold:true,color:PDF_TEXTO}]}
        ],margin:[0,0,0,14]},
        {table:{widths:["*"],body:[
          [{text:"MODELO COTIZADO",fillColor:PDF_AZUL,color:"#FFFFFF",bold:true,fontSize:9,margin:[8,7,8,7]}],
          [{text:modelo||"Modelo no especificado",fillColor:"#F0F3FB",color:PDF_AZUL,bold:true,fontSize:15,margin:[8,10,8,10]}]
        ]},layout:"noBorders"},
        bloque("Cliente y referencia de competencia",tablaDatos([
          ["Cliente final",d.nombre_cliente_final],
          ["Competidor",d.nombre_competidor],
          ["Modelo del competidor",d.modelo_competidor],
          ["Precio del competidor",d.precio_competidor==null?"—":dinero(d.precio_competidor)]
        ])),
        bloque("Detalle de cotización · MXN",{
          table:{headerRows:1,widths:["*",115],body:[
            [{text:"CONCEPTO",fillColor:PDF_AZUL,color:"#FFFFFF",bold:true,fontSize:9,margin:[4,6,4,6]},
              {text:"IMPORTE",alignment:"right",fillColor:PDF_AZUL,color:"#FFFFFF",bold:true,fontSize:9,margin:[4,6,4,6]}],
            filaImporte("Cantidad de unidades",cantidad),
            filaImporte("Precio público unitario",dinero(d.precio_publico)),
            filaImporte("Precio dealer unitario",dinero(d.precio_dealer)),
            filaImporte("Tipo de descuento",d.tipo_descuento==="PORCENTAJE"?"Porcentaje":"Monto por unidad"),
            filaImporte("Descuento porcentual",`${n(d.descuento_porcentaje).toFixed(4)} %`),
            filaImporte("Descuento por unidad",dinero(d.descuento_unitario)),
            filaImporte("Precio cotizado unitario",dinero(d.precio_cotizado),{bold:true,color:PDF_AZUL}),
            filaImporte("Descuento total",dinero(d.descuento)),
            filaImporte("Subtotal",dinero(d.subtotal)),
            filaImporte("IVA (16 %)",dinero(d.iva)),
            [{text:"TOTAL COTIZADO",fillColor:PDF_AZUL,color:"#FFFFFF",bold:true,fontSize:11,margin:[4,9,4,9]},
             {text:dinero(d.total),alignment:"right",fillColor:PDF_AZUL,color:"#FFFFFF",bold:true,fontSize:13,margin:[4,9,4,9]}]
          ]},
          layout:{hLineWidth:(i,node)=>i===0||i===node.table.body.length?0:0.5,hLineColor:()=>PDF_LINEA,vLineWidth:()=>0,
            paddingLeft:()=>9,paddingRight:()=>9,paddingTop:()=>3,paddingBottom:()=>3}
        }),
        bloque("Condiciones comerciales",tablaDatos([
          ["Vigencia en borrador",fechaFmt(c.fecha_vigencia)],
          ["Condiciones de pago",c.condiciones_pago],
          ["Condiciones de entrega",c.condiciones_entrega],
          ["Tiempo de entrega",c.tiempo_entrega],
          ["Observaciones de la unidad",d.observaciones],
          ["Observaciones comerciales",c.observaciones]
        ])),
        {text:"La vigencia definitiva será de 30 días naturales a partir del envío efectivo al dealer. Esta vista previa no inicia ese plazo.",
          fontSize:8,color:PDF_GRIS,italics:true,margin:[0,16,0,0]}
      ],
      info:{title:`Cotización ${texto(c.folio_cotizacion)} - Vista previa`,author:"Gestión Comercial",subject:"Cotización interna por modelo"}
    };
    const archivo=`cotizacion_${String(c.folio_cotizacion||"modelo").replace(/[^a-zA-Z0-9_-]/g,"_")}_v${n(c.version_actual)||1}_PREVIA.pdf`;
    try { const pdf=window.pdfMake.createPdf(dd); if(descargar)pdf.download(archivo);else pdf.open(); }
    catch(e){await Swal.fire("Error al generar el PDF",e.message||"No fue posible construir el documento.","error");}
  }
  // PDF EXTERNO: solo incluye información apta para compartir con el distribuidor.
  // Esta fase permite PREVISUALIZAR. No autoriza, publica ni envía cotizaciones.
  async function generarPdfDistribuidor(descargar) {
    if (!seleccionada || !propuestaActual) return;
    if (!window.pdfMake || typeof window.pdfMake.createPdf !== "function") {
      await Swal.fire("Biblioteca no disponible", "No se cargó pdfMake.", "error");
      return;
    }
    // Datos originales guardados, no los valores pendientes del formulario.
    const c = seleccionada, d = c.detalle || {}, p = propuestaActual;
    const tx = v => v === null || v === undefined || String(v).trim() === "" ? "—" : String(v);
    const num = v => Number(v) || 0;
    const modelo = [c.modelo, c.version_modelo].filter(Boolean).join(" · ");
    const fmtFecha = v => {
      const f = String(v || "").slice(0, 10);
      if (!/^\d{4}-\d{2}-\d{2}$/.test(f)) return "Por definir al enviar";
      const [y, m, dia] = f.split("-"); return `${dia}/${m}/${y}`;
    };
    const logo = await logoPdfBase64(PDF_LOGO_URL);
    const line = "#DCE3EF";
    const text = (v, extra={}) => ({text:tx(v), color:PDF_TEXTO, fontSize:9, ...extra});
    const celda = (v, extra={}) => text(v, {margin:[6,7,6,7], ...extra});
    const bloque = (titulo, contenido) => ({stack:[
      {text:titulo.toUpperCase(), color:PDF_AZUL, fontSize:10, bold:true, margin:[0,18,0,8]},
      contenido
    ]});
    const tablaInfo = filas => ({
      table:{widths:[145,"*"],body:filas.map(([a,b])=>[
        celda(a,{bold:true,color:PDF_GRIS,fillColor:"#F4F6FA"}),celda(b)
      ])},
      layout:{hLineWidth:(i,node)=>i===0||i===node.table.body.length?0:0.5,hLineColor:()=>line,vLineWidth:()=>0}
    });
    const detalle = [
      [celda("DESCRIPCIÓN",{bold:true,color:"#FFFFFF",fillColor:PDF_AZUL}),
       celda("CANT.",{bold:true,color:"#FFFFFF",fillColor:PDF_AZUL,alignment:"center"}),
       celda("P. UNITARIO",{bold:true,color:"#FFFFFF",fillColor:PDF_AZUL,alignment:"right"}),
       celda("IMPORTE",{bold:true,color:"#FFFFFF",fillColor:PDF_AZUL,alignment:"right"})],
      [celda(modelo || "Unidad cotizada",{bold:true}),
       celda(num(d.cantidad),{alignment:"center"}),
       celda(dinero(d.precio_cotizado),{alignment:"right"}),
       celda(dinero(d.subtotal),{alignment:"right"})]
    ];
    const totales = [
      [celda("Subtotal",{bold:true}),celda(dinero(d.subtotal),{alignment:"right"})],
      [celda("IVA (16 %)",{bold:true}),celda(dinero(d.iva),{alignment:"right"})],
      [celda("TOTAL",{bold:true,color:"#FFFFFF",fillColor:PDF_AZUL,fontSize:11}),
       celda(dinero(d.total),{bold:true,color:"#FFFFFF",fillColor:PDF_AZUL,alignment:"right",fontSize:12})]
    ];
    const dd = {
      pageSize:"A4",pageMargins:[42,40,42,68],
      defaultStyle:{fontSize:9,color:PDF_TEXTO},
      footer:(pagina,total)=>({margin:[42,12,42,0],stack:[
        {canvas:[{type:"line",x1:0,y1:0,x2:511,y2:0,lineColor:line,lineWidth:1}]},
        {columns:[{text:"VISTA PREVIA · DOCUMENTO SIN AUTORIZACIÓN · NO VÁLIDO PARA ENVÍO",color:"#A43D44",fontSize:7,bold:true},
          {text:`Página ${pagina} de ${total}`,alignment:"right",fontSize:8,color:PDF_GRIS}],margin:[0,8,0,0]}
      ]}),
      content:[
        {columns:[
          {width:155,stack:[logo?{image:logo,fit:[140,60]}:text("ESPACIO PARA LOGOTIPO",{bold:true,color:PDF_GRIS})]},
          {width:"*",stack:[
            text("COTIZACIÓN PARA DISTRIBUIDOR",{fontSize:17,bold:true,color:PDF_AZUL,alignment:"right"}),
            text(`Folio: ${tx(c.folio_cotizacion)}`,{fontSize:10,bold:true,alignment:"right",margin:[0,7,0,0]}),
            text(`Versión ${tx(c.version_actual)}`,{fontSize:8,color:PDF_GRIS,alignment:"right",margin:[0,4,0,0]})
          ]}
        ],columnGap:15},
        {canvas:[{type:"rect",x:0,y:0,w:511,h:5,color:PDF_AZUL}],margin:[0,16,0,15]},
        {columns:[
          {width:"55%",stack:[text("DISTRIBUIDOR",{bold:true,color:PDF_GRIS,fontSize:8}),text(p.nombre_comercial||p.razon_social,{bold:true,fontSize:11,margin:[0,5,0,0]})]},
          {width:"45%",stack:[text("REFERENCIA",{bold:true,color:PDF_GRIS,fontSize:8}),text(p.folio_propuesta,{bold:true,fontSize:11,margin:[0,5,0,0]})]}
        ],margin:[0,0,0,14]},
        bloque("Unidad cotizada",{table:{headerRows:1,widths:["*",52,105,105],body:detalle},
          layout:{hLineWidth:(i,node)=>i===0||i===node.table.body.length?0:0.5,hLineColor:()=>line,vLineWidth:()=>0}}),
        {columns:[{width:"*",text:""},{width:225,table:{widths:["*",95],body:totales},
          layout:{hLineWidth:(i,node)=>i===0||i===node.table.body.length?0:0.5,hLineColor:()=>line,vLineWidth:()=>0}}],margin:[0,14,0,0]},
        bloque("Condiciones comerciales",tablaInfo([
          ["Condiciones de pago",c.condiciones_pago],
          ["Condiciones de entrega",c.condiciones_entrega],
          ["Tiempo de entrega",c.tiempo_entrega],
          ["Vigencia", "30 días naturales a partir del envío efectivo al distribuidor"]
        ])),
        {text:"Los precios se expresan en pesos mexicanos (MXN). Documento sujeto a autorización y confirmación de disponibilidad.",
          fontSize:8,color:PDF_GRIS,italics:true,margin:[0,18,0,0]}
      ],
      info:{title:`Cotización distribuidor ${tx(c.folio_cotizacion)} - Vista previa`,author:"Gestión Comercial",subject:"Vista previa para distribuidor"}
    };
    // No hay todavía un flujo de autorización: jamás se produce aquí un PDF definitivo.
    const archivo=`cotizacion_distribuidor_${String(c.folio_cotizacion||"modelo").replace(/[^a-zA-Z0-9_-]/g,"_")}_PREVIA.pdf`;
    try {
      const pdf=window.pdfMake.createPdf(dd);
      if (descargar) pdf.download(archivo); else pdf.open();
    } catch (e) {
      await Swal.fire("Error al generar PDF",e.message||"No fue posible construir el documento.","error");
    }
  }

  const nombresNivel = {1:"Dirección General",2:"Dirección Comercial",3:"Dirección de Finanzas"};
  function pintarSeguimiento(){
    $("gpEnviarTodos").classList.toggle("d-none",!puedeEnviar || !cotizaciones.some(c=>c.estatus==="BORRADOR"));
    $("gpSeguimiento").innerHTML=cotizaciones.map(c=>{
      const s=seguimiento[c.clave]||{porcentaje:0,etapas:[]};
      const pendiente=s.etapas.find(x=>x.estatus==="PENDIENTE");
      const label=pendiente?`Pendiente: ${nombresNivel[pendiente.nivel]}`:c.estatus;
      return `<div class="col-lg-4"><div class="border rounded p-3 h-100">
        <div class="d-flex justify-content-between gap-2"><strong>${esc(c.modelo)}</strong><span class="fw-semibold">${s.porcentaje||0}%</span></div>
        <small class="text-muted">${esc(c.folio_cotizacion)} · Versión ${esc(c.version_actual)}</small>
        <div class="progress mt-2" style="height:9px"><div class="progress-bar" style="width:${s.porcentaje||0}%;background-color:#1B2B86" role="progressbar" aria-valuenow="${s.porcentaje||0}" aria-valuemin="0" aria-valuemax="100"></div></div>
        <div class="small mt-2">${esc(label)}</div><div class="small text-muted">DG · Comercial · Finanzas</div>
        </div></div>`;
    }).join("");
  }
  function pintarRevision(){
    if(!seleccionada)return;
    const c=seleccionada,s=seguimiento[c.clave]||{etapas:[],puede_autorizar:false};
    const pendiente=s.etapas.find(x=>x.estatus==="PENDIENTE");
    $("gpRevisionEstado").textContent=pendiente?`Pendiente de ${nombresNivel[pendiente.nivel]}`:`Estado: ${c.estatus}`;
    const mostrar=(id,si)=>$(id).classList.toggle("d-none",!si);
    mostrar("gpEnviarUno",puedeEnviar&&c.estatus==="BORRADOR");
    ["gpAprobar","gpDevolver","gpRechazar"].forEach(id=>mostrar(id,!!s.puede_autorizar&&c.estatus==="PENDIENTE_AUTORIZACION"));
    mostrar("gpPrepararAjuste",puedeEnviar&&c.estatus==="EN_AJUSTE_INTERNO");
    $("gpRevisionHistorial").innerHTML=s.etapas.length?`<div class="table-responsive"><table class="table table-sm table-bordered mb-0"><thead><tr><th>Etapa</th><th>Resultado</th><th>Fecha</th><th>Comentario</th></tr></thead><tbody>${s.etapas.map(a=>`<tr><td>${esc(nombresNivel[a.nivel])}</td><td>${esc(a.estatus)}</td><td>${esc(a.fecha_respuesta||a.fecha_solicitud||"")}</td><td>${esc(a.comentario||"—")}</td></tr>`).join("")}</tbody></table></div>`:'<span class="text-muted">Aún no hay autorizaciones registradas.</span>';
  }
  async function postRevision(endpoint,payload){
    loading("Procesando autorización y notificaciones...");
    try { return await json(`${root}/${endpoint}`,{method:"POST",headers:{"Content-Type":"application/json","X-CSRF-Token":window.GESTION_PROPUESTA_CSRF||""},body:JSON.stringify(payload)}); } finally { Swal.close(); }
  }
  async function enviarRevision(todos){
    if (operacionActiva || (!todos && !seleccionada)) return;
    // Antes de revisar, guardar SIEMPRE el formulario del modelo abierto si está en borrador.
    // Para envíos masivos, los demás modelos usan su última versión persistida.
    const editable = seleccionada && seleccionada.estatus === "BORRADOR";
    let payload = null;
    if (editable) {
      try { payload = obtenerPayloadBorrador(); }
      catch (e) { await Swal.fire("No se puede enviar", e.message, "warning"); return; }
    }
    const texto = todos
      ? "Primero se guardarán los cambios del modelo abierto y después se enviarán los borradores válidos a Dirección General."
      : `Se guardarán los datos actuales de ${seleccionada.modelo} antes de enviarlo a Dirección General.`;
    const conf = await Swal.fire({title:"¿Guardar y enviar a revisión?",text:texto,icon:"question",showCancelButton:true,confirmButtonText:"Guardar y enviar",cancelButtonText:"Cancelar"});
    if (!conf.isConfirmed) return;
    operacionActiva = true;
    ["gpEnviarUno","gpEnviarTodos","gpGuardar"].forEach(id => {if ($(id)) $(id).disabled = true;});
    try {
      if (payload) {
        loading("Guardando los datos antes de enviar...");
        await json(`${root}/guardarBorradorCotizacion`, {
          method:"POST",headers:{"Content-Type":"application/json","X-CSRF-Token":window.GESTION_PROPUESTA_CSRF||""},
          body:JSON.stringify(payload)
        });
        formularioInicial = firmaFormulario();
        Swal.close();
      }
      const datos = {clave_propuesta:clave};
      if (!todos) datos.clave_cotizacion = seleccionada.clave;
      const r = await postRevision("enviarRevisionCotizaciones",datos);
      await cargar(seleccionada?.clave);
      await Swal.fire("Enviado",`${r.enviadas} cotización(es) guardadas y enviadas a revisión.`,"success");
    } catch(e) {
      Swal.close();
      await Swal.fire("No fue posible enviar",`${e.message} No se envió a revisión si falló el guardado.`,"error");
    } finally {
      operacionActiva = false;
      ["gpEnviarUno","gpEnviarTodos","gpGuardar"].forEach(id => {if ($(id)) $(id).disabled = false;});
    }
  }
  async function resolverRevision(decision){
    if(!seleccionada)return;
    const nombres={APROBADA:"Aprobar",RECHAZADA:"Rechazar",DEVUELTA_AJUSTE:"Solicitar ajustes"};
    const dialogo=await Swal.fire({title:`${nombres[decision]} cotización`,input:"textarea",inputLabel:"Comentario",inputPlaceholder:"Motivo o comentario de la decisión",showCancelButton:true,confirmButtonText:"Confirmar",cancelButtonText:"Cancelar",inputValidator:v=>(decision!=="APROBADA"&&!v.trim())?"Es obligatorio indicar el motivo.":undefined});
    if(!dialogo.isConfirmed)return;
    try{
      await postRevision("resolverRevisionCotizacion",{clave_cotizacion:seleccionada.clave,decision,comentario:dialogo.value||""});
      await cargar(seleccionada.clave);await Swal.fire("Registrado","La decisión se guardó correctamente.","success");
    }catch(e){await Swal.fire("Error",e.message,"error");}
  }
  async function prepararAjuste(){
    if(!seleccionada)return;
    const conf=await Swal.fire({title:"Crear nueva versión",text:"Se conservará la versión devuelta y se creará una nueva versión editable.",icon:"question",showCancelButton:true,confirmButtonText:"Crear versión"});
    if(!conf.isConfirmed)return;
    try{await postRevision("prepararAjusteCotizacion",{clave_cotizacion:seleccionada.clave});await cargar(seleccionada.clave);await Swal.fire("Versión creada","Ya puedes ajustar y guardar el nuevo borrador.","success");}
    catch(e){await Swal.fire("Error",e.message,"error");}
  }

  // Consolidado informativo: incluye todos los modelos actuales, incluso los no autorizados.
  // Nunca usar este documento de vista previa como archivo oficial enviado al distribuidor.
  async function generarPdfConsolidado(descargar) {
    if (!propuestaActual || !cotizaciones.length) {
      return Swal.fire("Sin modelos", "La propuesta todavía no tiene modelos cotizados.", "info");
    }
    if (!window.pdfMake?.createPdf) {
      return Swal.fire("PDF no disponible", "No se cargó la biblioteca pdfMake.", "error");
    }
    const p = propuestaActual;
    const logo = await logoPdfBase64(PDF_LOGO_URL);
    const txt = v => String(v ?? "—");
    const aprobados = cotizaciones.filter(c => c.estatus === "AUTORIZADA");
    const total = cotizaciones.reduce((a,c)=>a+Number(c.total||0),0);
    const suma = key => cotizaciones.reduce((a,c)=>a+Number(c.detalle?.[key] ?? 0),0);
    const cell=(v,opt={})=>({text:txt(v),fontSize:8,color:PDF_TEXTO,margin:[4,6,4,6],...opt});
    const cab=(v)=>cell(v,{bold:true,color:"#FFFFFF",fillColor:PDF_AZUL});
    const filas = [[cab("MODELO"),cab("FOLIO / VERSIÓN"),cab("ESTADO"),cab("CANT."),cab("P. OFRECIDO"),cab("TOTAL")]];
    cotizaciones.forEach(c=>filas.push([
      cell(`${c.modelo||"Modelo"} ${c.version_modelo||""}`),
      cell(`${c.folio_cotizacion||"—"} / v${c.version_actual||1}`),
      cell(c.estatus),cell(c.detalle?.cantidad||0,{alignment:"center"}),
      cell(dinero(c.detalle?.precio_cotizado),{alignment:"right"}),
      cell(dinero(c.total),{alignment:"right"})
    ]));
    const dd={
      pageSize:"A4",pageMargins:[38,48,38,52],
      defaultStyle:{fontSize:9,color:PDF_TEXTO},
      footer:(page,pages)=>({columns:[{text:"DOCUMENTO INTERNO · VISTA PREVIA · NO ENVIAR AL DISTRIBUIDOR",fontSize:7,color:"#A53B43"},{text:`Página ${page} de ${pages}`,alignment:"right",fontSize:8,color:PDF_GRIS}],margin:[38,10,38,0]}),
      content:[
        {columns:[logo?{image:logo,fit:[135,55]}:{text:"LDR SOLUTIONS",bold:true,color:PDF_AZUL},
          {stack:[{text:"PROPUESTA COMERCIAL CONSOLIDADA",fontSize:16,bold:true,color:PDF_AZUL,alignment:"right"},{text:txt(p.folio_propuesta),alignment:"right",margin:[0,6,0,0]}]}]},
        {canvas:[{type:"rect",x:0,y:0,w:519,h:4,color:PDF_AZUL}],margin:[0,16,0,18]},
        {columns:[{stack:[{text:"DISTRIBUIDOR",fontSize:8,bold:true,color:PDF_GRIS},{text:txt(p.nombre_comercial||p.razon_social),bold:true,margin:[0,4,0,0]}]},
          {stack:[{text:"ESTATUS DE PROPUESTA",fontSize:8,bold:true,color:PDF_GRIS},{text:txt(p.estatus),bold:true,margin:[0,4,0,0]}]}]},
        {text:"Resumen de modelos",fontSize:12,bold:true,color:PDF_AZUL,margin:[0,22,0,10]},
        {table:{headerRows:1,widths:["*",86,86,28,68,75],body:filas},layout:{hLineColor:()=>PDF_LINEA,vLineColor:()=>PDF_LINEA}},
        {table:{widths:["*",130],body:[
          [cell("Subtotal de modelos",{bold:true}),cell(dinero(suma("subtotal")),{alignment:"right"})],
          [cell("IVA",{bold:true}),cell(dinero(suma("iva")),{alignment:"right"})],
          [cell("TOTAL CONSOLIDADO",{bold:true,color:PDF_AZUL}),cell(dinero(total),{bold:true,color:PDF_AZUL,alignment:"right"})]
        ]},layout:"lightHorizontalLines",margin:[0,15,0,0]},
        {text:`Modelos autorizados: ${aprobados.length} de ${cotizaciones.length}.`,bold:true,margin:[0,18,0,8]},
        {text:"Este resumen incluye todos los modelos de la propuesta y sus versiones actuales, incluso aquellos pendientes, rechazados o en ajuste. Los importes y estados son informativos. No constituye una oferta oficial autorizada para envío al distribuidor.",fontSize:8,color:PDF_GRIS,margin:[0,6,0,0]}
      ]
    };
    const archivo=`propuesta_consolidada_${txt(p.folio_propuesta).replace(/[^a-zA-Z0-9_-]/g,"_")}_PREVIA.pdf`;
    try {const pdf=window.pdfMake.createPdf(dd);if(descargar)pdf.download(archivo);else pdf.open();}
    catch(e){Swal.fire("Error al generar PDF",e.message||"Error inesperado","error");}
  }

  function iniciar() {
    $("gpListado").addEventListener("click",e=>{
      const btn=e.target.closest("[data-clave]");
      if(btn){const c=cotizaciones.find(x=>x.clave===btn.dataset.clave);if(c){
        if (seleccionada?.estatus === "BORRADOR" && seleccionada.clave !== c.clave && firmaFormulario() !== formularioInicial) {
          Swal.fire({title:"Cambios sin guardar",text:"Guarda el borrador antes de cambiar de modelo, o perderás lo capturado.",icon:"warning",showCancelButton:true,confirmButtonText:"Cambiar sin guardar",cancelButtonText:"Permanecer"}).then(r=>{if(r.isConfirmed)seleccionar(c);});
        } else seleccionar(c);
      }}
    });
    ["gpCantidad","gpDescuento"].forEach(id=>$(id).addEventListener("input",calcular));
    $("gpTipoDescuento").addEventListener("change",()=>{campo("gpDescuento",0);calcular();});
    $("gpFormulario").addEventListener("submit",guardar);
    $("gpPrecioCompetidor")?.addEventListener("focus",e=>{e.target.value=limpiarMoneda(e.target.value);});
    $("gpPrecioCompetidor")?.addEventListener("blur",e=>{e.target.value=formatoMiles(e.target.value);});
    $("gpPdfConsolidado")?.addEventListener("click",()=>generarPdfConsolidado(false));
    $("gpDescargarPdfConsolidado")?.addEventListener("click",()=>generarPdfConsolidado(true));
    $("gpPdfModelo")?.addEventListener("click", () => generarPdfModelo(false));
    $("gpDescargarPdfModelo")?.addEventListener("click", () => generarPdfModelo(true));
    $("gpPdfDistribuidor")?.addEventListener("click", () => generarPdfDistribuidor(false));
    $("gpDescargarPdfDistribuidor")?.addEventListener("click", () => generarPdfDistribuidor(true));
    $("gpEnviarTodos").addEventListener("click",()=>enviarRevision(true));
    $("gpEnviarUno").addEventListener("click",()=>enviarRevision(false));
    $("gpAprobar").addEventListener("click",()=>resolverRevision("APROBADA"));
    $("gpDevolver").addEventListener("click",()=>resolverRevision("DEVUELTA_AJUSTE"));
    $("gpRechazar").addEventListener("click",()=>resolverRevision("RECHAZADA"));
    $("gpPrepararAjuste").addEventListener("click",prepararAjuste);
    const destino=new URLSearchParams(location.search).get("cotizacion");
    cargar(destino).catch(e=>error(e.message));
  }
  if(document.readyState==="loading")document.addEventListener("DOMContentLoaded",iniciar);else iniciar();
})();
