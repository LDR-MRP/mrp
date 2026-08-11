let currentRecepcion = null;
let recepcionActual = null;
let recepcionCerrada = false;

/* =========================
   ALERTAS VISUALES
========================= */

function alertaModal(icon, title, text) {
  return Swal.fire({
    icon: icon,
    title: title,
    text: text,
    confirmButtonColor: "#3085d6",
    confirmButtonText: "Aceptar",
  });
}

function promptCantidad(codigo, descripcion, pendiente) {
  return Swal.fire({
    title: `Producto: ${codigo}`,
    html: `
      <div style="margin-bottom:12px;">
        <div style="
          font-size:15px;
          font-weight:600;
          color:#111827;
          line-height:1.4;
          margin-bottom:10px;
        ">Descripción:
          ${descripcion}
        </div>

        <div style="
          background:#f8fafc;
          border:1px solid #e5e7eb;
          border-radius:12px;
          padding:12px;
          text-align:center;
        ">
          <div style="font-size:12px; color:#6b7280;">Pendiente por recibir</div>
          <div style="font-size:24px; font-weight:700; color:#111827;">${pendiente}</div>
        </div>
      </div>
    `,
    input: "number",
    inputAttributes: {
      min: 1,
      step: "any",
      placeholder: "Cantidad a recibir",
    },
    inputValue: "",
    showCancelButton: true,
    confirmButtonText: "Agregar",
    cancelButtonText: "Cancelar",
    confirmButtonColor: "#198754",
    cancelButtonColor: "#6c757d",
    inputValidator: (value) => {
      if (!value || parseFloat(value) <= 0) {
        return "Debes capturar una cantidad válida";
      }
    },
  });
}

document.addEventListener("DOMContentLoaded", function () {
  cargarRecepcionesAbiertas();
  cargarRecepcionesParciales();
  cargarRecepcionesCerradas();

  const scanner = document.getElementById("scannerInput");

  scanner.addEventListener("keypress", function (e) {
    if (e.key === "Enter") {
      e.preventDefault();
      let code = scanner.value.trim();
      if (!code) return;
      procesarEscaneo(code);
      scanner.value = "";
      scanner.focus();
    }
  });

  document
    .getElementById("buscarRecepcion")
    .addEventListener("keyup", filtrarRecepciones);
});

function cargarDetalle(id) {
  currentRecepcion = id;

  fetch(base_url + "/Inv_recepcion/getRecepcionCompra/" + id)
  .then((r) => r.json())
  .then((r) => {

    if (r && r.idrecepcion) {

      recepcionActual = r.idrecepcion;
      recepcionCerrada = r.estatus === "cerrada";

      document.getElementById("observacionesRecepcion").value =
        r.observaciones || "";

    } else {

      recepcionActual = null;
      recepcionCerrada = false;

      document.getElementById("observacionesRecepcion").value = "";
    }

    aplicarBloqueoRecepcion();

  });

  cargarHeader(id);

  fetch(base_url + "/Inv_recepcion/getDetalleOC/" + id)
    .then((res) => res.json())
    .then((data) => {
      let html = "";

      data.forEach((item, i) => {
        html += `
          <tr>
            <td>${i + 1}</td>
            <td>${item.codigo}</td>
            <td>${item.descripcion}</td>
            <td><input type="text" class="form-control form-control-sm lote" value="${item.lote || ""}"></td>
            <td><span class="cantidad-solicitada" data-value="${item.cantidad_solicitada}">${item.cantidad_solicitada}</span></td>
            <td><input type="number" class="form-control form-control-sm recibido" data-id="${item.iddetalle}" data-inv="${item.inventarioid}" data-codigo="${item.codigo}" min="0" max="${item.cantidad_pendiente}" value="0"></td>
            <td><span class="cantidad-pendiente">${item.cantidad_pendiente}</span></td>
            <td>${item.unidad}</td>
            <td><input type="text" class="form-control form-control-sm obs" value="${item.observaciones || ""}"></td>
            <td>
    <div class="evidencias-container"
         data-id="${item.iddetalle}"
         data-inv="${item.inventarioid}">

        <div class="evidencia-item mb-1">
            <input type="file"
                   class="form-control form-control-sm evidencia"
                   accept="image/*">
        </div>

        <button type="button"
                class="btn btn-sm btn-outline-primary mt-1"
                onclick="agregarEvidenciaProducto(this)">
            <i class="ri-add-line"></i> Agregar foto
        </button>

        <small class="text-muted d-block">
            Máximo 5 fotografías
        </small>

    </div>
</td>
<td>
${
  parseInt(item.total_evidencias) > 0
    ? `
    <button
        class="btn btn-outline-primary btn-sm"
        onclick="verEvidencias(${item.inventarioid})">

        <i class="ri-image-line"></i>
        Evidencias
    </button>
    `
    : `<span class="text-muted">Sin evidencias</span>`
}
</td>
          </tr>`;
      });

      document.getElementById("detalleRecepcion").innerHTML = html;
      document.getElementById("scannerInput").focus();
      aplicarBloqueoRecepcion();
    });
}
function aplicarBloqueoRecepcion() {

  // observaciones generales
  document.getElementById("observacionesRecepcion").readOnly = recepcionCerrada;

  // documentos generales
  document.querySelectorAll(".documento").forEach((el) => {
    el.disabled = recepcionCerrada;
  });

  // campos tabla
  document
    .querySelectorAll(".lote, .recibido, .obs, .evidencia")
    .forEach((el) => {
      el.disabled = recepcionCerrada;
    });

  // botones agregar evidencia
  document
    .querySelectorAll('[onclick="agregarEvidenciaProducto(this)"]')
    .forEach((btn) => {
      btn.style.display = recepcionCerrada ? "none" : "";
    });

  // botón agregar documento
  document.querySelectorAll('[onclick="agregarDocumento()"]').forEach((btn) => {
    btn.style.display = recepcionCerrada ? "none" : "";
  });
}

async function procesarEscaneo(code) {
  let rows = document.querySelectorAll("#detalleRecepcion tr");
  let found = false;

  for (const row of rows) {
    let codigo = row.children[1].innerText.trim();
    let descripcion = row.children[2].innerText.trim();

    if (codigo === code) {
      found = true;

      let input = row.querySelector(".recibido");
      let actual = parseFloat(input.value) || 0;
      let max = parseFloat(input.max) || 0;

      const pendiente = parseFloat(input.max) || 0;
      const result = await promptCantidad(codigo, descripcion, pendiente);

      if (!result.isConfirmed) {
        document.getElementById("scannerInput").focus();
        return;
      }

      let cantidad = parseFloat(result.value) || 0;

      if (actual + cantidad > max) {
        alertaModal(
          "warning",
          "Cantidad excedida",
          "La cantidad excede lo pendiente por recibir.",
        );
        document.getElementById("scannerInput").focus();
        return;
      }

      input.value = actual + cantidad;
      row.classList.add("table-success");

      document.getElementById("scannerInput").focus();
      return;
    }
  }

  if (!found) {
    alertaModal(
      "error",
      "Código no encontrado",
      "El código escaneado no pertenece a esta recepción.",
    );
  }

  document.getElementById("scannerInput").focus();
}

function guardarRecepcion() {
  let rows = document.querySelectorAll("#detalleRecepcion tr");
  let detalle = [];
  let error = false;

  rows.forEach((row) => {
    let input = row.querySelector(".recibido");
    let cantidad = parseFloat(input.value) || 0;
    let max = parseFloat(input.max) || 0;

    if (cantidad > max) {
      row.classList.add("table-danger");
      error = true;
      return;
    }

    detalle.push({
      iddetalle: input.dataset.id,
      inventarioid: input.dataset.inv,
      codigo: input.dataset.codigo,
      lote: row.querySelector(".lote").value,
      cantidad_solicitada: row.querySelector(".cantidad-solicitada").dataset
        .value,
      cantidad_recibida: cantidad,
      observaciones: row.querySelector(".obs").value,
    });
  });

  if (error) {
    alertaModal("warning", "Error", "Cantidades inválidas");
    return;
  }

  let tieneCantidades = detalle.some(
    (item) => parseFloat(item.cantidad_recibida) > 0,
  );

  if (!tieneCantidades) {
    alertaModal(
      "warning",
      "Información requerida",
      "Debes capturar al menos una cantidad recibida.",
    );
    return;
  }

  let formData = new FormData();

  formData.append("compraid", currentRecepcion);
  formData.append(
    "observaciones",
    document.getElementById("observacionesRecepcion").value,
  );
  formData.append("detalle", JSON.stringify(detalle));

  // =========================
  // DOCUMENTOS GENERALES
  // =========================
  document.querySelectorAll(".documento").forEach((input) => {
    if (input.files.length > 0) {
      formData.append("documentos[]", input.files[0]);
    }
  });

  // =========================
  // EVIDENCIAS POR PRODUCTO (CORRECTO)
  // =========================
  document.querySelectorAll("#detalleRecepcion tr").forEach((row) => {
    let input = row.querySelector(".recibido");

    if (!input) return;

    let detalleid = input.dataset.id;
    let inventarioid = input.dataset.inv;

    row.querySelectorAll(".evidencia").forEach((fileInput) => {
      if (fileInput.files.length > 0) {
        formData.append(`evidencias[${detalleid}][]`, fileInput.files[0]);
      }
    });

    formData.append(
      `evidencias_meta[${detalleid}][inventarioid]`,
      inventarioid,
    );
  });

  fetch(base_url + "/Inv_recepcion/setRecepcion", {
    method: "POST",
    body: formData,
  })
    .then((res) => res.json())
    .then((res) => {
      alertaModal("success", "OK", res.msg).then(() => limpiarRecepcion());
    })
    .catch((err) => {
      console.error(err);
    });
}

function limpiarRecepcion() {
  currentRecepcion = null;

  document.getElementById("detalleRecepcion").innerHTML = "";
  document.getElementById("headerOrigen").innerHTML = "";
  document.getElementById("headerDestino").innerHTML = "";
  document.getElementById("observacionesRecepcion").value = "";
  document.getElementById("buscarRecepcion").value = "";
  // LIMPIAR INPUTS FILE (documentos generales)
  document.querySelectorAll(".documento").forEach((input) => {
    input.value = "";
  });

  // LIMPIAR EVIDENCIAS POR PRODUCTO
  document.querySelectorAll(".evidencia").forEach((input) => {
    input.value = "";
  });

  cargarRecepcionesAbiertas();
  cargarRecepcionesParciales();
  cargarRecepcionesCerradas();

  const scanner = document.getElementById("scannerInput");

  if (scanner) {
    scanner.value = "";
    scanner.focus();
  }
}

function cargarHeader(id) {
  fetch(base_url + "/Inv_recepcion/getHeaderOC/" + id)
    .then((res) => res.json())
    .then((data) => {
      document.getElementById("headerOrigen").innerHTML = `
        <strong>Orden:</strong> ${data.orden_origen}<br>
        <strong>Fecha:</strong> ${data.fecha}
      `;

      document.getElementById("headerDestino").innerHTML = `
        <strong>Orden:</strong> ${data.orden_destino}<br>
        <strong>Almacén:</strong> ${data.almacen_destino}
      `;
    })
    .catch((err) => console.error("Error cargando header:", err));
}
function cargarRecepcionesAbiertas() {
  fetch(base_url + "/Inv_recepcion/getOrdenesAbiertas")
    .then((res) => res.json())
    .then((data) => {
      let html = "";

      data.forEach((p) => {
        html += `
          <li class="list-group-item recepcion-item activa"
              data-search="${p.folio} ${p.proveedor}"
              onclick="cargarDetalle(${p.idcompra})">
            <div class="d-flex justify-content-between align-items-center">
              <div>
                <strong>${p.folio}</strong><br>
                <small>${p.proveedor}</small>
              </div>
              <span class="badge bg-info text-dark badge-estatus">Abierta</span>
            </div>
          </li>`;
      });

      document.getElementById("listaRecepcionAbierta").innerHTML = html;
    });
}

function cargarRecepcionesParciales() {
  fetch(base_url + "/Inv_recepcion/getOrdenesParciales")
    .then((res) => res.json())
    .then((data) => {
      let html = "";

      data.forEach((p) => {
        html += `
          <li class="list-group-item recepcion-item parcial"
              data-search="${p.folio} ${p.proveedor}"
              onclick="cargarDetalle(${p.idcompra})">
            <div class="d-flex justify-content-between align-items-center">
              <div>
                <strong>${p.folio}</strong><br>
                <small>${p.proveedor}</small>
              </div>
              <span class="badge bg-warning text-dark badge-estatus">Parcial</span>
            </div>
          </li>`;
      });

      document.getElementById("listaRecepcionParcial").innerHTML = html;
    });
}

function cargarRecepcionesCerradas() {
  fetch(base_url + "/Inv_recepcion/getOrdenesCerradas")
    .then((res) => res.json())
    .then((data) => {
      let html = "";

      data.forEach((p) => {
        html += `
          <li class="list-group-item recepcion-item cerrada"
              data-search="${p.folio} ${p.proveedor}"
              onclick="cargarDetalle(${p.idcompra})">
            <div class="d-flex justify-content-between align-items-center">
              <div>
                <strong>${p.folio}</strong><br>
                <small>${p.proveedor}</small>
              </div>
              <span class="badge bg-success badge-estatus">Cerrada</span>
            </div>
          </li>`;
      });

      document.getElementById("listaRecepcionCerrada").innerHTML = html;
    });
}

function filtrarRecepciones() {
  let filtro = document.getElementById("buscarRecepcion").value.toLowerCase();
  let items = document.querySelectorAll(".recepcion-item");

  items.forEach((item) => {
    let texto = (item.dataset.search || "").toLowerCase();
    item.style.display = texto.includes(filtro) ? "" : "none";
  });
}

function agregarEvidenciaProducto(btn) {
  const container = btn.closest(".evidencias-container");

  const total = container.querySelectorAll(".evidencia-item").length;

  if (total >= 5) {
    alertaModal(
      "warning",
      "Límite alcanzado",
      "Solo puedes adjuntar hasta 5 fotografías.",
    );
    return;
  }

  const div = document.createElement("div");

  div.className = "evidencia-item mb-1";

  div.innerHTML = `
        <div class="d-flex gap-1">
            <input type="file"
                   class="form-control form-control-sm evidencia"
                   accept="image/*">

            <button type="button"
                    class="btn btn-sm btn-danger"
                    onclick="this.parentElement.parentElement.remove()">
                <i class="ri-delete-bin-line"></i>
            </button>
        </div>
    `;

  btn.before(div);
}
function agregarDocumento() {
  const cont = document.getElementById("contenedorDocumentos");

  const div = document.createElement("div");

  div.className = "documento-item mb-2";

  div.innerHTML = `
        <div class="d-flex gap-1">

            <input type="file"
                   class="form-control documento">

            <button type="button"
                    class="btn btn-danger btn-sm"
                    onclick="this.parentElement.parentElement.remove()">

                <i class="ri-delete-bin-line"></i>

            </button>

        </div>
    `;

  cont.appendChild(div);
}

function verEvidencias(inventarioid) {
  cargarGaleria(inventarioid);

  const modal = new bootstrap.Modal(document.getElementById("modalEvidencias"));

  modal.show();
}

function cargarGaleria(inventarioid) {
  fetch(
    base_url +
      "/Inv_recepcion/getEvidenciasProducto" +
      "?recepcionid=" +
      recepcionActual +
      "&inventarioid=" +
      inventarioid,
  )
    .then((r) => r.json())
    .then((data) => {
      let html = "";

      data.forEach((foto) => {
        html += `
                <div class="col-md-3 mb-3">

                    <a href="${base_url}/Assets/uploads/recepciones/evidencias/${foto.ruta}"
                       target="_blank">

                        <img
                            src="${base_url}/Assets/uploads/recepciones/evidencias/${foto.ruta}"
                            class="img-fluid rounded shadow">

                    </a>

                </div>
            `;
      });

      document.getElementById("galeriaProducto").innerHTML = html;
    });

  fetch(
    base_url +
      "/Inv_recepcion/getDocumentosRecepcion" +
      "?recepcionid=" +
      recepcionActual,
  )
    .then((r) => r.json())
    .then((data) => {
      let html = "";

      data.forEach((doc) => {
        html += `
                <a
                    class="list-group-item list-group-item-action"
                    href="${base_url}/Assets/uploads/recepciones/documentos/${doc.ruta}"
                    target="_blank">

                    ${doc.nombre}

                </a>
            `;
      });

      document.getElementById("listaDocumentos").innerHTML = html;
    });
}
