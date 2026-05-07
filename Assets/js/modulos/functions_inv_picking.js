let currentPicking = null;

document.addEventListener("DOMContentLoaded", function () {
  cargarPickings();

  const scanner = document.getElementById("scannerInput");

  scanner.addEventListener("keypress", function (e) {
    if (e.key === "Enter") {
      e.preventDefault();

      let code = scanner.value.trim();
      if (!code) return;

      procesarEscaneo(code);
      scanner.value = "";
    }
  });
});

function procesarEscaneo(code) {
  let rows = document.querySelectorAll("#detallePicking tr");
  let found = false;

  rows.forEach((row) => {
    let codigo = row.children[1].innerText.trim();

    if (codigo === code) {
      let input = row.querySelector(".recibido");
      let actual = parseFloat(input.value) || 0;
      let max = parseFloat(input.max) || 0;

      if (actual < max) {
        input.value = actual + 1;
        row.classList.add("table-success");
      }

      found = true;
    }
  });

  if (!found) {
    alert("Código no encontrado en esta recepción");
  }
}

// =========================
// Cargar órdenes de compra
// =========================
function cargarPickings() {
  fetch(base_url + "/Inv_picking/getOrdenesCompraPendientes")
    .then((res) => res.json())
    .then((data) => {
      console.log("OCS:", data);

      let html = "";

      data.forEach((p) => {
        html += `
          <li class="list-group-item" onclick="cargarDetalle(${p.idcompra})">
            ${p.folio} - ${p.proveedor}
          </li>`;
      });

      document.getElementById("listaPicking").innerHTML = html;
    })
    .catch((err) => console.error("Error cargando OCs:", err));
}

// =========================
// Cargar detalle OC
// =========================
function cargarDetalle(id) {
  currentPicking = id;
  cargarHeader(id);

  fetch(base_url + "/Inv_picking/getDetalleOC/" + id)
    .then((res) => res.json())
    .then((data) => {
      console.log("DETALLE RECEPCIÓN:", data);

      let html = "";

      data.forEach((item, i) => {
        html += `
          <tr>
            <td>${i + 1}</td>
            <td>${item.codigo}</td>
            <td>${item.descripcion}</td>

            <td>
              <input type="text"
                class="form-control form-control-sm lote"
                value="${item.lote ?? ""}">
            </td>

            <td>
              <span class="cantidad-solicitada" data-value="${item.cantidad_solicitada}">
                ${item.cantidad_solicitada}
              </span>
            </td>

            <td>
              <input type="number"
                class="form-control form-control-sm recibido"
                data-id="${item.iddetalle}"
                data-inv="${item.inventarioid}"
                data-ubi="${item.ubicacionid ?? ""}"
                min="0"
                max="${item.cantidad_pendiente}"
                value="${item.cantidad_recibida}">
            </td>

            <td>
              <span class="cantidad-pendiente">
                ${item.cantidad_pendiente}
              </span>
            </td>

            <td>${item.unidad ?? ""}</td>

            <td>
              <input type="text"
                class="form-control form-control-sm obs"
                value="${item.observaciones ?? ""}">
            </td>
          </tr>`;
      });

      document.getElementById("detallePicking").innerHTML = html;
    })
    .catch((err) => console.error("Error cargando detalle:", err));
}

// =========================
// Guardar picking
// =========================
function guardarPicking() {
  let rows = document.querySelectorAll("#detallePicking tr");
  let detalle = [];
  let error = false;

  rows.forEach((row) => {
    let input = row.querySelector(".recibido");
    let cantidad = parseFloat(input.value) || 0;
    let max = parseFloat(input.max);

    if (cantidad > max) {
      alert("No puedes recibir más de lo solicitado");
      error = true;
      return;
    }

    detalle.push({
      iddetalle: input.dataset.id,
      inventarioid: input.dataset.inv,
      ubicacionid: input.dataset.ubi || null,
      lote: row.querySelector(".lote")?.value || "",
      cantidad_solicitada:
        row.querySelector(".cantidad-solicitada")?.dataset.value || 0,
      cantidad_recibida: cantidad,
      observaciones: row.querySelector(".obs")?.value || "",
    });
  });

  if (error) return;

  fetch(base_url + "/Inv_picking/setPicking", {
    method: "POST",
    headers: {
      "Content-Type": "application/json",
    },
    body: JSON.stringify({
      compraid: currentPicking,
      observaciones: document.getElementById("observacionesPicking").value,
      detalle: detalle,
    }),
  })
    .then((res) => res.json())
    .then((res) => {
      alert(res.msg);

      if (res.status) {
        cargarDetalle(currentPicking);
        cargarPickings();
      }
    })
    .catch((err) => console.error("Error guardando recepción:", err));
}

function cargarHeader(id) {
  fetch(base_url + "/Inv_picking/getHeaderOC/" + id)
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
