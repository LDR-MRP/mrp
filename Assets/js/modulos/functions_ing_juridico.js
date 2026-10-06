// Ingeniería - Perfil Jurídico: carga de certificaciones por configuración.
let tableJuridico = null;
let modalJur = null;

const ESTADOS_CERT_JUR = ["NO_APLICA", "PENDIENTE", "EN_PROCESO", "VIGENTE", "POR_VENCER", "VENCIDA", "POR_REVISAR", "NO_DISPONIBLE"];

function escJur(v) {
  return String(v === null || v === undefined ? "" : v)
    .replace(/&/g, "&amp;")
    .replace(/</g, "&lt;")
    .replace(/>/g, "&gt;")
    .replace(/"/g, "&quot;");
}

document.addEventListener("DOMContentLoaded", function () {
  tableJuridico = $("#tableJuridico").DataTable({
    processing: true,
    serverSide: false,
    ajax: {
      url: base_url + "/Ing_juridico/getConfiguraciones",
      dataSrc: "",
    },
    columns: [
      { data: "segmento" },
      { data: "modelo" },
      { data: "unidad_label" },
      { data: "clave_vehicular" },
      { data: "estado_label" },
      { data: "avance_label" },
      { data: "options" },
    ],
    responsive: false,
    scrollX: true,
    autoWidth: false,
    destroy: true,
    pageLength: 10,
    order: [], // respeta el orden del servidor: última configuración primero
  });

  modalJur = new bootstrap.Modal(document.querySelector("#modalCertificacionesJur"));

  let form = document.querySelector("#formCertificacionesJur");
  form.addEventListener("submit", guardarCertificacionesJur);

  // Si se desmarca "Obligatoria", el estado pasa automáticamente a NO APLICA.
  // Cualquier cambio en la tarjeta re-evalúa su color / estado de captura.
  function onCambioCardJur(e) {
    let card = e.target.closest(".cert-card");
    if (!card) return;
    if (e.type === "change" && e.target.classList.contains("chk-obligatoria") && !e.target.checked) {
      let sel = card.querySelector(".sel-estado-cert");
      if (sel) sel.value = "NO_APLICA";
    }
    evaluarCardJur(card);
  }
  form.addEventListener("change", onCambioCardJur);
  form.addEventListener("input", onCambioCardJur);
});

function fntCertificaciones(idConfiguracion) {
  document.querySelector("#idConfiguracionJur").value = idConfiguracion;

  // Datos de la fila para el encabezado del modal.
  let fila = null;
  if (tableJuridico) {
    tableJuridico.rows().data().each(function (d) {
      if (parseInt(d.id_configuracion) === parseInt(idConfiguracion)) fila = d;
    });
  }
  renderResumenJur(fila);

  document.querySelector("#contenedorCertificacionesJur").innerHTML =
    '<div class="col-12 text-center text-muted py-5">Cargando certificaciones...</div>';
  modalJur.show();
  cargarCertificacionesJur(idConfiguracion);
}

function renderResumenJur(fila) {
  let subtitulo = document.querySelector("#subtituloModalJur");
  let resumen = document.querySelector("#resumenConfigJur");
  if (!fila) {
    subtitulo.textContent = "";
    resumen.innerHTML = "";
    return;
  }
  subtitulo.textContent = (fila.segmento || "") + " · " + (fila.modelo || "");

  function dato(etiqueta, valor) {
    return (
      '<div class="col-6 col-md-3"><div class="text-uppercase text-muted fs-11 fw-bold ls-1">' + etiqueta +
      '</div><div class="fw-semibold">' + valor + "</div></div>"
    );
  }
  resumen.innerHTML =
    dato("Unidad", escJur(fila.unidad_label) || "-") +
    dato("Clave vehicular", escJur(fila.clave_vehicular) || "-") +
    dato("Estado", fila.estado_label || "-") + // badge generado por el servidor
    '<div class="col-6 col-md-3"><div class="text-uppercase text-muted fs-11 fw-bold ls-1">Avance</div><div id="avanceJur">' +
    (fila.avance_label || "-") + "</div></div>";
}

function cargarCertificacionesJur(idConfiguracion) {
  let request = new XMLHttpRequest();
  request.open("GET", base_url + "/Ing_juridico/getCertificaciones/" + idConfiguracion, true);
  request.send();
  request.onreadystatechange = function () {
    if (request.readyState !== 4) return;
    if (request.status !== 200) {
      Swal.fire("Error", "No se pudieron cargar las certificaciones.", "error");
      return;
    }
    renderCertificacionesJur(JSON.parse(request.responseText));
  };
}

// Color del borde de cada tarjeta según el estado de la certificación.
const COLOR_ESTADO_JUR = {
  VIGENTE: "#0ab39c",
  POR_VENCER: "#f7b84b",
  VENCIDA: "#f06548",
  EN_PROCESO: "#299cdb",
  POR_REVISAR: "#f7b84b",
  NO_DISPONIBLE: "#495057",
  NO_APLICA: "#adb5bd",
  PENDIENTE: "#adb5bd",
};

function renderCertificacionesJur(arrData) {
  let cont = document.querySelector("#contenedorCertificacionesJur");
  let dis = JUR_PUEDE_EDITAR ? "" : "disabled";

  if (!arrData.length) {
    cont.innerHTML =
      '<div class="col-12"><div class="cert-vacio"><i class="ri-file-search-line"></i>' +
      "<div>No hay certificaciones activas en el catálogo.</div></div></div>";
    return;
  }

  function campo(col, icono, etiqueta, control) {
    return (
      '<div class="' + col + '"><label class="cert-label"><i class="' + icono + '"></i>' + etiqueta + "</label>" + control + "</div>"
    );
  }

  let html = "";
  arrData.forEach(function (row) {
    let estadoActual = row.estado || "PENDIENTE";
    let opciones = "";
    ESTADOS_CERT_JUR.forEach(function (estado) {
      opciones += '<option value="' + estado + '" ' + (estadoActual === estado ? "selected" : "") + ">" + estado.replace("_", " ") + "</option>";
    });

    let obligatoria = row.obligatoria === null || row.obligatoria == 1;
    let subtitulo = [row.codigo, row.autoridad].filter(Boolean).map(escJur).join(" · ");

    html +=
      '<div class="col"><div class="card h-100 cert-card" data-id-certificacion="' + escJur(row.id_certificacion) +
      '" data-archivo="' + (row.archivo ? "1" : "") + '">' +
      // Encabezado
      '<div class="card-header cert-header">' +
      '<div class="d-flex align-items-center gap-2 min-w-0">' +
      '<span class="cert-icono"><i class="ri-shield-check-fill"></i></span>' +
      '<div class="min-w-0"><h6 class="mb-0 text-truncate">' + escJur(row.nombre) + "</h6>" +
      (subtitulo ? '<small class="cert-sub">' + subtitulo + "</small>" : "") + "</div></div>" +
      '<div class="d-flex flex-column align-items-end gap-1 flex-shrink-0">' +
      '<span class="cert-pill-estado"></span>' +
      '<div class="form-check form-switch cert-switch mb-0">' +
      '<input class="form-check-input chk-obligatoria" type="checkbox" ' + dis + " " + (obligatoria ? "checked" : "") + ">" +
      '<label class="form-check-label">Obligatoria</label></div>' +
      "</div></div>" +
      // Aviso de faltantes (se llena en evaluarCardJur)
      '<div class="cert-alerta d-none"><i class="ri-error-warning-fill"></i><span class="cert-alerta-txt"></span></div>' +
      // Cuerpo
      '<div class="card-body"><div class="row g-2">' +
      campo("col-md-6", "ri-flag-2-line", "Estado",
        '<select class="form-select form-select-sm sel-estado-cert" ' + dis + ">" + opciones + "</select>") +
      campo("col-md-6", "ri-hashtag", "No. certificado",
        '<input type="text" class="form-control form-control-sm txt-numero-cert" value="' + escJur(row.numero_certificado) + '" ' + dis + ">") +
      '<div class="col-12"><div class="cert-vigencia"><div class="cert-vigencia-titulo"><i class="ri-calendar-2-line"></i>Vigencia</div><div class="row g-2">' +
      campo("col-md-4", "ri-file-mark-line", "Emisión",
        '<input type="date" class="form-control form-control-sm fecha-emision-cert" value="' + escJur(row.fecha_emision) + '" ' + dis + ">") +
      campo("col-md-4", "ri-play-circle-line", "Inicio",
        '<input type="date" class="form-control form-control-sm fecha-inicio-cert" value="' + escJur(row.fecha_inicio) + '" ' + dis + ">") +
      campo("col-md-4", "ri-time-line", "Vencimiento",
        '<input type="date" class="form-control form-control-sm fecha-vencimiento-cert" value="' + escJur(row.fecha_vencimiento) + '" ' + dis + ">") +
      "</div></div></div>" +
      campo("col-12", "ri-chat-3-line", "Observaciones",
        '<input type="text" class="form-control form-control-sm txt-observaciones-cert" value="' + escJur(row.observaciones) + '" ' + dis + ">") +
      "</div></div>" +
      // Pie: archivo
      '<div class="card-footer cert-footer d-flex align-items-center gap-2">' +
      '<span class="cert-clip"><i class="ri-attachment-2"></i></span>' +
      (row.archivo
        ? '<a href="' + base_url + "/Assets/uploads/ing_certificaciones/" + encodeURIComponent(row.archivo) + '" target="_blank" class="btn btn-sm btn-success flex-shrink-0" title="Ver archivo adjunto"><i class="ri-file-text-fill align-bottom"></i> Ver archivo</a>'
        : '<span class="badge cert-sin-archivo flex-shrink-0"><i class="ri-file-forbid-line align-bottom"></i> Sin archivo</span>') +
      (JUR_PUEDE_EDITAR
        ? '<input type="file" class="form-control form-control-sm inp-archivo-cert" accept=".pdf,.jpg,.jpeg,.png" title="' + (row.archivo ? "Reemplazar archivo" : "Adjuntar archivo") + '">'
        : "") +
      "</div>" +
      "</div></div>";
  });
  cont.innerHTML = html;
  cont.querySelectorAll(".cert-card").forEach(evaluarCardJur);
}

// Pinta la tarjeta según su estado y, si es obligatoria y le falta información,
// la marca en rojo con la lista de faltantes.
function evaluarCardJur(card) {
  let sel = card.querySelector(".sel-estado-cert");
  let estado = sel ? sel.value : "PENDIENTE";
  let obligatoria = card.querySelector(".chk-obligatoria").checked;
  let inpArchivo = card.querySelector(".inp-archivo-cert");
  let tieneArchivo = card.dataset.archivo === "1" || !!(inpArchivo && inpArchivo.files && inpArchivo.files.length);

  let faltantes = [];
  if (obligatoria) {
    if (estado === "PENDIENTE" || estado === "NO_APLICA") faltantes.push("Estado");
    if (!card.querySelector(".txt-numero-cert").value.trim()) faltantes.push("No. certificado");
    if (!card.querySelector(".fecha-vencimiento-cert").value) faltantes.push("Vencimiento");
    if (!tieneArchivo) faltantes.push("Archivo");
  }
  let incompleta = faltantes.length > 0;

  let color = incompleta ? "#f06548" : COLOR_ESTADO_JUR[estado] || "#adb5bd";
  card.style.setProperty("--cert-color", color);
  card.style.setProperty("--cert-tint", color + "1f");
  card.classList.toggle("cert-obligatoria", obligatoria);
  card.classList.toggle("cert-incompleta", incompleta);
  card.classList.toggle("cert-completa", obligatoria && !incompleta);
  card.classList.toggle("cert-opcional", !obligatoria);

  let pill = card.querySelector(".cert-pill-estado");
  if (pill) {
    pill.textContent = estado.replace("_", " ");
    pill.style.backgroundColor = (COLOR_ESTADO_JUR[estado] || "#adb5bd");
  }

  let alerta = card.querySelector(".cert-alerta");
  if (alerta) {
    alerta.classList.toggle("d-none", !incompleta);
    card.querySelector(".cert-alerta-txt").innerHTML = incompleta
      ? "<b>Obligatoria incompleta.</b> Falta: " + faltantes.join(", ")
      : "";
  }
  actualizarLeyendaJur();
}

function actualizarLeyendaJur() {
  let cards = document.querySelectorAll("#contenedorCertificacionesJur .cert-card");
  let n = { completa: 0, incompleta: 0, opcional: 0 };
  cards.forEach(function (c) {
    if (c.classList.contains("cert-incompleta")) n.incompleta++;
    else if (c.classList.contains("cert-completa")) n.completa++;
    else n.opcional++;
  });
  let set = function (id, v) {
    let el = document.getElementById(id);
    if (el) el.textContent = v;
  };
  set("cntCompletaJur", n.completa);
  set("cntIncompletaJur", n.incompleta);
  set("cntOpcionalJur", n.opcional);
}

function guardarCertificacionesJur(e) {
  e.preventDefault();
  if (!JUR_PUEDE_EDITAR) return;

  let divLoading = document.querySelector("#divLoading");
  if (divLoading) divLoading.style.display = "flex";

  let idConfiguracion = document.querySelector("#idConfiguracionJur").value;
  let certificaciones = [];
  let formData = new FormData();

  document.querySelectorAll("#contenedorCertificacionesJur .cert-card").forEach(function (tr) {
    let id = tr.dataset.idCertificacion;
    certificaciones.push({
      id_certificacion: id,
      obligatoria: tr.querySelector(".chk-obligatoria").checked ? 1 : 0,
      estado: tr.querySelector(".sel-estado-cert").value,
      numero_certificado: tr.querySelector(".txt-numero-cert").value,
      fecha_emision: tr.querySelector(".fecha-emision-cert").value,
      fecha_inicio: tr.querySelector(".fecha-inicio-cert").value,
      fecha_vencimiento: tr.querySelector(".fecha-vencimiento-cert").value,
      observaciones: tr.querySelector(".txt-observaciones-cert").value,
    });
    let inp = tr.querySelector(".inp-archivo-cert");
    if (inp && inp.files && inp.files[0]) {
      formData.append("archivo_" + id, inp.files[0]);
    }
  });

  formData.append("id_configuracion", idConfiguracion);
  formData.append("certificaciones", JSON.stringify(certificaciones));

  let request = new XMLHttpRequest();
  request.open("POST", base_url + "/Ing_juridico/setCertificaciones", true);
  request.send(formData);
  request.onreadystatechange = function () {
    if (request.readyState !== 4) return;
    if (divLoading) divLoading.style.display = "none";
    if (request.status !== 200) {
      Swal.fire("Error", "Ocurrió un error en el servidor.", "error");
      return;
    }
    let objData;
    try {
      objData = JSON.parse(request.responseText);
    } catch (err) {
      Swal.fire("Error", "Respuesta inesperada del servidor.", "error");
      return;
    }
    if (objData.status) {
      Swal.fire("¡Operación exitosa!", objData.msg, "success");
      cargarCertificacionesJur(idConfiguracion);
      if (tableJuridico) {
        // Al recargar la tabla se refresca también el resumen (avance/estado) del modal.
        tableJuridico.ajax.reload(function () {
          let fila = null;
          tableJuridico.rows().data().each(function (d) {
            if (parseInt(d.id_configuracion) === parseInt(idConfiguracion)) fila = d;
          });
          renderResumenJur(fila);
        }, false);
      }
    } else {
      Swal.fire("Atención", objData.msg, "warning");
    }
  };
}
