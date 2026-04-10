let tableMovimientos;
let rowTable = "";
let divLoading = document.querySelector("#divLoading");
let productosMovCache = [];

// Inputs del formulario
const cve_precio = document.querySelector("#clave-precio-input");
const estado = document.querySelector("#estado-select");
const descripcion = document.querySelector("#descripcion-precio-textarea");
const impuesto = document.querySelector("#impuesto-select");

// Mis referencias globales
let primerTab;
let firstTab;
let tabNuevo;
let spanBtnText = null;
let formPrecios = null;

document.addEventListener("DOMContentLoaded", function () {
  let hoy = new Date().toISOString().split("T")[0];
  tableMovimientos = $("#tableMovimientos").DataTable({
    processing: true,
    responsive: true,
    destroy: true,

    ajax: {
      url: base_url + "/Inv_movimientosalmacenes/getTransferencias",
      type: "GET",
      data: function (d) {
        d.origen = document.querySelector("#filtroOrigen").value;
        d.destino = document.querySelector("#filtroDestino").value;
        d.fechaInicio = document.querySelector("#filtroFechaInicio").value;
        d.fechaFin = document.querySelector("#filtroFechaFin").value;
      },
      dataSrc: "",
    },

    //  ACTIVAR BOTONES
    dom: '<"row mb-2"<"col-md-6"B><"col-md-6 text-end"f>>rtip',
    buttons: [
      {
        extend: "csvHtml5",
        text: '<i class="fa fa-download me-1"></i> Exportar CSV',
        className: "btn btn-outline-success btn-sm shadow-sm rounded-pill px-3",
        title: "Movimientos_Inventario_" + hoy,

        exportOptions: {
          columns: ":visible:not(:last-child)",
        },
      },
      {
        extend: "pdfHtml5",
        text: '<i class="fa fa-file-pdf me-1"></i> Exportar PDF',
        className: "btn btn-outline-danger btn-sm shadow-sm rounded-pill px-3",
        title: "Movimientos de Inventario",
        orientation: "landscape",
        pageSize: "A4",
        exportOptions: {
          columns: ":visible:not(:last-child)",
        },
        customize: function (doc) {
          doc.defaultStyle.fontSize = 8;
          doc.styles.tableHeader.fontSize = 9;
          doc.styles.title = {
            fontSize: 14,
            bold: true,
            alignment: "center",
          };
        },
      },
    ],
    columns: [
      { data: "folio" },
      { data: "almacen_origen" },
      { data: "almacen_destino" },
      { data: "referencia" },
      { data: "fecha" },
      {
        data: "idmovimientoalmacen",
        render: function (data) {
          return `
      <button class="btn btn-info btn-sm" onclick="verDetalle(${data})">
        Ver
      </button>
    `;
        },
      },
    ],

    order: [[0, "desc"]],
  });

  const btnFiltrar = document.querySelector("#btnFiltrar");
  if (btnFiltrar) {
    btnFiltrar.addEventListener("click", function () {
      tableMovimientos.ajax.reload();
    });
  }

  const btnLimpiar = document.querySelector("#btnLimpiar");
  if (btnLimpiar) {
    btnLimpiar.addEventListener("click", function () {
      document.querySelector("#filtroOrigen").value = "";
      document.querySelector("#filtroDestino").value = "";
      document.querySelector("#filtroFechaInicio").value = "";
      document.querySelector("#filtroFechaFin").value = "";

      tableMovimientos.ajax.reload();
    });
  }

  // Cargar almacenes en filtro
  fetch(base_url + "/Inv_movimientosalmacenes/getSelectAlmacenes")
    .then((res) => res.text())
    .then((html) => {
      document.querySelector("#filtroOrigen").innerHTML = html;
      document.querySelector("#filtroDestino").innerHTML = html;
    });

  const primerTabEl = document.querySelector(
    '#nav-tab a[href="#listmovimiento"]',
  );
  const firstTabEl = document.querySelector(
    '#nav-tab a[href="#agregarMovimiento"]',
  );

  if (primerTabEl && firstTabEl && spanBtnText) {
    primerTab = new bootstrap.Tab(primerTabEl);
    firstTab = new bootstrap.Tab(firstTabEl);
    tabNuevo = firstTabEl;
  }

  const formMovimiento = document.querySelector("#formMovimiento");

  formMovimiento.addEventListener("submit", function (e) {
    e.preventDefault();

    const cantidades = document.querySelectorAll('[name="cantidad[]"]');
    const productos = document.querySelectorAll('[name="inventarioid[]"]');

    let ok = false;

    cantidades.forEach((c, i) => {
      if (c.value > 0 && productos[i].value) {
        ok = true;
      }
    });

    if (!ok) {
      Swal.fire("Agrega al menos una partida válida", "", "warning");
      return;
    }

    let formData = new FormData(this);

    fetch(base_url + "/Inv_movimientosalmacenes/setMovimiento", {
      method: "POST",
      body: formData,
    })
      .then((res) => res.json())
      .then((obj) => {
        if (obj.status) {
          Swal.fire({
            title: "Movimiento guardado",
            text: "La transferencia se realizó correctamente",
            icon: "success",
            confirmButtonText: "Aceptar",
          });

          formMovimiento.reset();
          $("#tableMovimientos").DataTable().ajax.reload();
        } else {
          Swal.fire("Error", obj.msg, "error");
        }
      });
  });

  // Cargar almacenes
  fetch(base_url + "/Inv_movimientosalmacenes/getSelectAlmacenes")
    .then((res) => res.text())
    .then((html) => {
      document.querySelector("#almacen_origenid").innerHTML = html;
      document.querySelector("#almacen_destinoid").innerHTML = html;
    });

  // Cargar productos
  fetch(base_url + "/Inv_movimientosalmacenes/getSelectConceptos")
    .then((res) => res.text())
    .then((html) => {
      document.querySelector("#concepmovid").innerHTML = html;
    });

  // Cargar productos en cache
  fetch(base_url + "/Inv_movimientosalmacenes/getSelectInventarioJson")
    .then((res) => res.json())
    .then((data) => {
      productosMovCache = data;
    });

  const inputMov = document.querySelector("#inventarioSearchMov");

  inputMov.addEventListener("input", function () {
    let val = this.value.toLowerCase();
    cerrarListaMov();
    if (!val) return;

    let lista = document.createElement("div");
    lista.className = "autocomplete-items list-group position-absolute w-100";
    this.parentNode.appendChild(lista);

    productosMovCache
      .filter(
        (p) =>
          (p.cve_articulo && p.cve_articulo.toLowerCase().includes(val)) ||
          (p.descripcion && p.descripcion.toLowerCase().includes(val)),
      )
      .slice(0, 10)
      .forEach((p) => {
        let item = document.createElement("div");
        item.className = "list-group-item list-group-item-action";
        item.innerHTML = `<strong>${p.cve_articulo}</strong> - ${p.descripcion}`;

        item.addEventListener("click", function () {
          document.querySelector("#inventarioSearchMov").value =
            p.cve_articulo + " - " + p.descripcion;
          document.querySelector("#inventarioid").value = p.idinventario;
          cerrarListaMov();
        });

        lista.appendChild(item);
      });
  });
});

function cerrarListaMov() {
  document.querySelectorAll(".autocomplete-items").forEach((e) => e.remove());
}

const tbody = document.querySelector("#tablaPartidas tbody");
const btnAddRow = document.querySelector("#btnAddRow");

if (tbody && btnAddRow) {
  btnAddRow.addEventListener("click", () => {
    const tr = document.createElement("tr");

    tr.innerHTML = `
      <td><input name="cantidad[]" type="number" class="form-control cantidad"></td>
      <td>
        <input type="text" class="form-control invSearch">
        <input type="hidden" name="inventarioid[]">
      </td>
      <td><input name="costo_cantidad[]" type="number" class="form-control costo"></td>
      <td><input name="total[]" type="number" class="form-control total" readonly></td>
      <td><button type="button" class="btn btn-danger btn-sm btnDel">X</button></td>
    `;

    tbody.appendChild(tr);
  });
}

document.addEventListener("click", (e) => {
  if (e.target.classList.contains("btnDel")) {
    e.target.closest("tr").remove();
    recalcularGranTotal();
  }
});

function recalcularGranTotal() {
  let total = 0;
  document.querySelectorAll(".total").forEach((t) => {
    total += parseFloat(t.value || 0);
  });
  document.querySelector("#granTotal").value = total.toFixed(2);
}

document.addEventListener("input", (e) => {
  if (
    e.target.classList.contains("cantidad") ||
    e.target.classList.contains("costo")
  ) {
    const tr = e.target.closest("tr");
    const cant = parseFloat(tr.querySelector(".cantidad").value || 0);
    const costo = parseFloat(tr.querySelector(".costo").value || 0);

    tr.querySelector(".total").value = (cant * costo).toFixed(2);
    recalcularGranTotal();
  }
});

document.addEventListener("input", function (e) {
  if (!e.target.classList.contains("invSearch")) return;

  const input = e.target;
  const tr = input.closest("tr");
  const hidden = tr.querySelector('input[name="inventarioid[]"]');

  let val = input.value.toLowerCase();
  cerrarListaMov();

  if (!val) return;

  let lista = document.createElement("div");
  lista.className = "autocomplete-items list-group position-absolute w-100";
  input.parentNode.appendChild(lista);

  productosMovCache
    .filter(
      (p) =>
        (p.cve_articulo && p.cve_articulo.toLowerCase().includes(val)) ||
        (p.descripcion && p.descripcion.toLowerCase().includes(val)),
    )
    .slice(0, 10)
    .forEach((p) => {
      let item = document.createElement("div");
      item.className = "list-group-item list-group-item-action";
      item.innerHTML = `<strong>${p.cve_articulo}</strong> - ${p.descripcion}`;

      item.addEventListener("click", function () {
        input.value = `${p.cve_articulo} - ${p.descripcion}`;
        hidden.value = p.idinventario;
        cerrarListaMov();
      });

      lista.appendChild(item);
    });
});
function cerrarListaMov() {
  document.querySelectorAll(".autocomplete-items").forEach((e) => e.remove());
}

document.addEventListener("click", function (e) {
  if (!e.target.classList.contains("invSearch")) cerrarListaMov();
});

document.addEventListener("click", function (e) {
  const btn = e.target.closest(".btnReporteMov");
  if (!btn) return;

  const numero = btn.dataset.numero;
  const almacen = btn.dataset.almacen;

  window.open(
    base_url + "/Inv_movimientosalmacenes/reporte/" + numero + "," + almacen,
    "_blank",
  );
});

function verDetalle(id) {
  let request = new XMLHttpRequest();
  let url = base_url + "/Inv_movimientosalmacenes/getDetalle/" + id;

  request.open("GET", url, true);
  request.send();

  request.onreadystatechange = function () {
    if (request.readyState == 4 && request.status == 200) {
      let data = JSON.parse(request.responseText);

      let tbody = document.getElementById("tbodyDetalle");
      tbody.innerHTML = "";

      data.forEach((item) => {
        let fila = `
          <tr>
            <td>${item.cve_articulo}</td>
            <td>${item.descripcion}</td>
            <td>${item.cantidad}</td>
          </tr>
        `;

        tbody.innerHTML += fila;
      });

      let modal = new bootstrap.Modal(document.getElementById("modalDetalle"));
      modal.show();
    }
  };
}
