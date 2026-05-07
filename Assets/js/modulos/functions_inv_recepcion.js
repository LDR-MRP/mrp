let currentRecepcion = null;

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

function cargarRecepciones() {
  fetch(base_url + "/Inv_recepcion/getOrdenesActivas")
    .then((res) => res.json())
    .then((data) => {
      let html = "";

      data.forEach((p) => {
        let clase = p.estatus === "parcial" ? "parcial" : "activa";
        let badge =
          p.estatus === "parcial"
            ? `<span class="badge bg-warning text-dark badge-estatus">Parcial</span>`
            : `<span class="badge bg-info text-dark badge-estatus">Abierta</span>`;

        html += `
          <li class="list-group-item recepcion-item ${clase}"
              data-search="${p.folio} ${p.proveedor}"
              onclick="cargarDetalle(${p.idcompra})">
            <div class="d-flex justify-content-between align-items-center">
              <div>
                <strong>${p.folio}</strong><br>
                <small>${p.proveedor}</small>
              </div>
              ${badge}
            </div>
          </li>`;
      });

      document.getElementById("listaRecepcion").innerHTML = html;
    });
}

function cargarDetalle(id) {
  currentRecepcion = id;
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
            <td><input type="text" class="form-control form-control-sm obs" value=""></td>
          </tr>`;
      });

      document.getElementById("detalleRecepcion").innerHTML = html;
      document.getElementById("scannerInput").focus();
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

    row.classList.remove("table-danger");

    // Validar que no exceda lo pendiente
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
    alertaModal(
      "warning",
      "Cantidad inválida",
      "No puedes recibir más cantidad de la solicitada en la orden de compra.",
    );
    return;
  }

  fetch(base_url + "/Inv_recepcion/setRecepcion", {
    method: "POST",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify({
      compraid: currentRecepcion,
      observaciones: document.getElementById("observacionesRecepcion").value,
      detalle: detalle,
    }),
  })
    .then((res) => res.json())
    .then((res) => {
      alertaModal("success", "Recepción guardada", res.msg);

      // Limpiar formulario visual
      currentRecepcion = null;
      document.getElementById("observacionesRecepcion").value = "";
      document.getElementById("detalleRecepcion").innerHTML = `
  <tr>
    <td colspan="9">
      <div class="empty-state">
        <i class="ri-inbox-archive-line"></i>
        <h6>Selecciona una recepción para comenzar</h6>
        <p>Aquí podrás escanear productos y registrar entradas.</p>
      </div>
    </td>
  </tr>
`;
      document.getElementById("headerOrigen").innerHTML = "";
      document.getElementById("headerDestino").innerHTML = "";

      // Recargar listas
      cargarRecepcionesAbiertas();
      cargarRecepcionesParciales();
      cargarRecepcionesCerradas();

      document.getElementById("scannerInput").value = "";
      document.getElementById("scannerInput").focus();
    })
    .catch((err) => {
      console.error("Error guardando recepción:", err);
      alertaModal(
        "error",
        "Error",
        "Ocurrió un problema al guardar la recepción.",
      );
    });
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

function cargarRecepciones() {
  fetch(base_url + "/Inv_recepcion/getOrdenesActivas")
    .then((res) => res.json())
    .then((data) => {
      let html = "";

      data.forEach((p) => {
        let clase =
          p.estatus === "parcial"
            ? "list-group-item-warning"
            : "list-group-item-info";

        html += `
          <li class="list-group-item ${clase} recepcion-item"
              onclick="cargarDetalle(${p.idcompra})">
            ${p.folio} - ${p.proveedor}
          </li>`;
      });

      document.getElementById("listaRecepcion").innerHTML = html;
    });
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
