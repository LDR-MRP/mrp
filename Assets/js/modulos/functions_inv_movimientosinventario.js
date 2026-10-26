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
  let hoy = new Date().toISOString().split('T')[0];
  tableMovimientos = $("#tableMovimientos").DataTable({
    processing: true,
    responsive: true,
    destroy: true,

    ajax: {
      url: base_url + "/Inv_movimientosinventario/getMovimientos",
      type: "GET",
      data: function (d) {
        d.almacen = document.querySelector("#filtroAlmacen").value;
        d.concepto = document.querySelector("#filtroConcepto").value;
        d.fechaInicio = document.querySelector("#filtroFechaInicio").value;
        d.fechaFin = document.querySelector("#filtroFechaFin").value;
      },
      dataSrc: "",
    },

    // 🔥 ACTIVAR BOTONES
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
      {
        data: "idmovinventario",
        visible: false,
        searchable: false,
      },
      { data: "clave" },
      { data: "producto" },
      { data: "almacen" },
      { data: "concepto" },
      { data: "referencia" },
      { data: "lote", defaultContent: "" },
      { data: "cantidad" },
      { data: "fecha_movimiento" },
      {
        data: null,
        className: "text-center",
        render: function (data) {
          return `
          <button 
            class="btn btn-sm btn-danger btnReporteMov"
            data-numero="${data.numero_movimiento}"
            data-almacen="${data.almacenid}">
            <i class="fa fa-file-pdf">Reporte</i>
          </button>
        `;
        },
        orderable: false,
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
      const almacen = document.querySelector("#filtroAlmacen");
      const concepto = document.querySelector("#filtroConcepto");
      const fechaInicio = document.querySelector("#filtroFechaInicio");
      const fechaFin = document.querySelector("#filtroFechaFin");

      if (almacen) almacen.value = "";
      if (concepto) concepto.value = "";
      if (fechaInicio) fechaInicio.value = "";
      if (fechaFin) fechaFin.value = "";

      tableMovimientos.ajax.reload();
    });
  }

  // Cargar almacenes en filtro
  fetch(base_url + "/Inv_movimientosinventario/getSelectAlmacenes")
    .then((res) => res.text())
    .then((html) => {
      document.querySelector("#filtroAlmacen").innerHTML = html;
    });

  // Cargar conceptos en filtro
  fetch(base_url + "/Inv_movimientosinventario/getSelectConceptos")
    .then((res) => res.text())
    .then((html) => {
      document.querySelector("#filtroConcepto").innerHTML = html;
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

    // Salidas de unidades: cada partida debe traer sus VINs
    let faltanVines = null;
    document.querySelectorAll("#tablaPartidas tbody tr").forEach((tr) => {
      if (faltanVines || !filaRequiereVin(tr)) return;
      const ids = JSON.parse(tr.querySelector(".seriesJson").value || "[]");
      if (ids.length === 0) faltanVines = tr.querySelector(".invSearch").value;
    });
    if (faltanVines) {
      Swal.fire(
        "Faltan VINs",
        "Selecciona los VINs que salen para: " + faltanVines,
        "warning",
      );
      return;
    }

    let formData = new FormData(this);

    fetch(base_url + "/Inv_movimientosinventario/setMovimiento", {
      method: "POST",
      body: formData,
    })
      .then((res) => res.json())
      .then((obj) => {
        if (obj.status) {
          Swal.fire({
            title: "Movimiento registrado",
            text: obj.msg,
            icon: "success",
            showCancelButton: true,
            confirmButtonText: "📄 Ver reporte",
            cancelButtonText: "Cerrar",
          }).then((result) => {
            if (result.isConfirmed) {
              window.open(
                base_url +
                  "/Inv_movimientosinventario/reporte/" +
                  obj.numero_movimiento +
                  "," +
                  obj.almacenid,
                "_blank",
              );
            }
          });

          formMovimiento.reset();

          const bloqueCPNReset = document.querySelector("#bloqueCPN");
          const cpnValorReset = document.querySelector("#cpnValor");
          const selectProveedorReset = document.querySelector("#id_proveedor");
          if (bloqueCPNReset) bloqueCPNReset.style.display = "none";
          if (cpnValorReset) {
            cpnValorReset.style.display = "";
            cpnValorReset.value = "";
          }
          if (selectProveedorReset) {
            selectProveedorReset.style.display = "none";
            selectProveedorReset.required = false;
            selectProveedorReset.disabled = true;
            selectProveedorReset.value = "";
          }
          const selectClienteReset = document.querySelector("#id_cliente");
          if (selectClienteReset) {
            selectClienteReset.style.display = "none";
            selectClienteReset.required = false;
            selectClienteReset.disabled = true;
            selectClienteReset.value = "";
          }

          conceptoSignoMov = 0;
          document.querySelectorAll("#tablaPartidas tbody tr").forEach((tr) => {
            tr.dataset.tipo = "";
            limpiarVinesFila(tr);
            evaluarFilaVin(tr);
          });

          $("#tableMovimientos").DataTable().ajax.reload();
        } else {
          Swal.fire("Error", obj.msg, "error");
        }
      });
  });

  // Cargar almacenes
  fetch(base_url + "/Inv_movimientosinventario/getSelectAlmacenes")
    .then((res) => res.text())
    .then((html) => {
      document.querySelector("#almacenid").innerHTML = html;
    });

  // Cargar productos
  fetch(base_url + "/Inv_movimientosinventario/getSelectConceptos")
    .then((res) => res.text())
    .then((html) => {
      document.querySelector("#concepmovid").innerHTML = html;
    });

  // Cargar productos en cache
  fetch(base_url + "/Inv_movimientosinventario/getSelectInventarioJson")
    .then((res) => res.json())
    .then((data) => {
      productosMovCache = data;
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
        <input type="hidden" name="series_json[]" class="seriesJson" value="[]">
        <div class="vinBox mt-1" style="display:none;">
          <button type="button" class="btn btn-sm btn-soft-primary btnVines">
            <i class="ri-barcode-line"></i> Seleccionar VINs
          </button>
          <span class="vinResumen small ms-2"></span>
        </div>
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
        tr.dataset.tipo = p.tipo_elemento || "";
        limpiarVinesFila(tr);
        evaluarFilaVin(tr);
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

let proveedoresMovLoaded = false;

function cargarSelectProveedoresMov() {
  if (proveedoresMovLoaded) return;

  fetch(base_url + "/Inv_movimientosinventario/getSelectProveedores")
    .then((res) => res.text())
    .then((html) => {
      document.querySelector("#id_proveedor").innerHTML = html;
      proveedoresMovLoaded = true;
    });
}

let clientesMovLoaded = false;

function cargarSelectClientesMov() {
  if (clientesMovLoaded) return;

  fetch(base_url + "/Inv_movimientosinventario/getSelectClientes")
    .then((res) => res.text())
    .then((html) => {
      document.querySelector("#id_cliente").innerHTML = html;
      clientesMovLoaded = true;
    });
}

function ocultarSelectClienteMov() {
  const selectCliente = document.querySelector("#id_cliente");
  if (!selectCliente) return;
  selectCliente.style.display = "none";
  selectCliente.required = false;
  selectCliente.disabled = true;
  selectCliente.value = "";
}

document.querySelector("#concepmovid").addEventListener("change", function () {
  const id = this.value;
  const bloque = document.querySelector("#bloqueCPN");
  const input = document.querySelector("#cpnValor");
  const selectProveedor = document.querySelector("#id_proveedor");
  const selectCliente = document.querySelector("#id_cliente");

  ocultarSelectClienteMov();
  conceptoSignoMov = 0;
  reevaluarFilasVin(true);

  if (!id) {
    bloque.style.display = "none";
    input.value = "";
    input.style.display = "";
    selectProveedor.style.display = "none";
    selectProveedor.required = false;
    selectProveedor.disabled = true;
    selectProveedor.value = "";
    return;
  }

  fetch(base_url + "/Inv_movimientosinventario/getConceptoInfo/" + id)
    .then((r) => r.json())
    .then((data) => {
      if (!data) return;

      conceptoSignoMov = parseInt(data.signo, 10) || 0;
      reevaluarFilasVin(false);

      bloque.style.display = "block";

      if (data.cpn === "P") {
        // Concepto tipo Proveedor: select conectado a prv_cat_proveedores
        cargarSelectProveedoresMov();

        input.style.display = "none";
        input.value = "";

        selectProveedor.style.display = "block";
        selectProveedor.disabled = false;
        selectProveedor.required = true;
      } else {
        selectProveedor.style.display = "none";
        selectProveedor.required = false;
        selectProveedor.disabled = true;
        selectProveedor.value = "";

        if (data.cpn === "C") {
          // Concepto tipo Cliente (ej. Ventas): select conectado a cli_clientes
          cargarSelectClientesMov();

          input.style.display = "none";
          input.value = "";

          selectCliente.style.display = "block";
          selectCliente.disabled = false;
          selectCliente.required = true;
        } else {
          input.style.display = "";
          input.value = "Ninguno";
        }
      }
    });
});

document.addEventListener("click", function (e) {
  const btn = e.target.closest(".btnReporteMov");
  if (!btn) return;

  const numero = btn.dataset.numero;
  const almacen = btn.dataset.almacen;

  window.open(
    base_url + "/Inv_movimientosinventario/reporte/" + numero + "," + almacen,
    "_blank",
  );
});


/* =====================================================
   VINs en salidas de unidades (productos tipo P)
   Cada partida de salida de una unidad guarda en series_json[]
   los id_numeros_serie seleccionados; la cantidad = # de VINs.
===================================================== */
let conceptoSignoMov = 0;
let filaVinActiva = null;
let vinesModalSel = new Set();
let vinesModalData = [];

function filaRequiereVin(tr) {
  return (
    conceptoSignoMov < 0 &&
    tr.dataset.tipo === "P" &&
    !!tr.querySelector('input[name="inventarioid[]"]').value
  );
}

function limpiarVinesFila(tr) {
  const hid = tr.querySelector(".seriesJson");
  if (hid) hid.value = "[]";
  tr.dataset.vines = "";
  const res = tr.querySelector(".vinResumen");
  if (res) res.innerHTML = "";
}

function evaluarFilaVin(tr) {
  const box = tr.querySelector(".vinBox");
  const cant = tr.querySelector(".cantidad");
  if (!box || !cant) return;

  if (filaRequiereVin(tr)) {
    box.style.display = "";
    cant.readOnly = true;
    const ids = JSON.parse(tr.querySelector(".seriesJson").value || "[]");
    cant.value = ids.length || "";
    const res = tr.querySelector(".vinResumen");
    res.innerHTML = ids.length
      ? `<span class="badge bg-primary-subtle text-primary">${ids.length} VIN(s)</span> ` +
        `<span class="text-muted">${tr.dataset.vines || ""}</span>`
      : '<span class="text-danger">Sin VINs seleccionados</span>';
  } else {
    box.style.display = "none";
    cant.readOnly = false;
  }
  cant.dispatchEvent(new Event("input", { bubbles: true }));
}

function reevaluarFilasVin(limpiar) {
  document.querySelectorAll("#tablaPartidas tbody tr").forEach((tr) => {
    if (limpiar) limpiarVinesFila(tr);
    evaluarFilaVin(tr);
  });
}

// Cambiar de almacén invalida los VINs elegidos (eran de otro almacén)
document.querySelector("#almacenid").addEventListener("change", function () {
  reevaluarFilasVin(true);
});

function pintarVinesModal() {
  const body = document.querySelector("#vinesMovBody");
  const filtro = document.querySelector("#vinesMovBuscar").value.trim().toUpperCase();
  const filas = vinesModalData.filter(
    (v) => !filtro || v.numero_serie.toUpperCase().includes(filtro),
  );

  body.innerHTML = filas.length
    ? filas
        .map(
          (v) => `
      <tr>
        <td><input type="checkbox" class="form-check-input chkVinMov" value="${v.id_numeros_serie}"
             ${vinesModalSel.has(String(v.id_numeros_serie)) ? "checked" : ""}></td>
        <td><span class="badge bg-primary-subtle text-primary">${v.numero_serie}</span></td>
        <td>${v.color || ""}</td>
        <td>${v.numero_motor || ""}</td>
      </tr>`,
        )
        .join("")
    : '<tr><td colspan="4" class="text-center text-muted py-3">No hay VINs disponibles</td></tr>';

  document.querySelector("#vinesMovContador").textContent =
    vinesModalSel.size + " seleccionados";
}

document.addEventListener("click", function (e) {
  const btn = e.target.closest(".btnVines");
  if (!btn) return;

  const tr = btn.closest("tr");
  const almacenid = document.querySelector("#almacenid").value;
  const inventarioid = tr.querySelector('input[name="inventarioid[]"]').value;

  if (!almacenid) {
    Swal.fire("Selecciona primero el almacén", "", "warning");
    return;
  }

  filaVinActiva = tr;
  vinesModalSel = new Set(
    JSON.parse(tr.querySelector(".seriesJson").value || "[]").map(String),
  );

  // VINs ya elegidos en otras partidas no se ofrecen otra vez
  const usadosOtras = new Set();
  document.querySelectorAll("#tablaPartidas tbody tr").forEach((o) => {
    if (o === tr) return;
    JSON.parse(o.querySelector(".seriesJson")?.value || "[]").forEach((id) =>
      usadosOtras.add(String(id)),
    );
  });

  document.querySelector("#vinesMovProducto").textContent =
    tr.querySelector(".invSearch").value;
  document.querySelector("#vinesMovBuscar").value = "";
  document.querySelector("#vinesMovInfo").textContent = "Cargando...";
  vinesModalData = [];
  pintarVinesModal();
  bootstrap.Modal.getOrCreateInstance(document.querySelector("#modalVinesMov")).show();

  fetch(
    base_url +
      "/Inv_movimientosinventario/getVinesDisponibles?inventarioid=" +
      encodeURIComponent(inventarioid) +
      "&almacenid=" +
      encodeURIComponent(almacenid),
  )
    .then((r) => r.json())
    .then((data) => {
      vinesModalData = (data || []).filter(
        (v) => !usadosOtras.has(String(v.id_numeros_serie)),
      );
      document.querySelector("#vinesMovInfo").textContent =
        vinesModalData.length + " unidad(es) en este almacén";
      pintarVinesModal();
      document.querySelector("#vinesMovBuscar").focus();
    });
});

document.addEventListener("change", function (e) {
  if (!e.target.classList.contains("chkVinMov")) return;
  if (e.target.checked) vinesModalSel.add(e.target.value);
  else vinesModalSel.delete(e.target.value);
  document.querySelector("#vinesMovContador").textContent =
    vinesModalSel.size + " seleccionados";
});

const inputVinesBuscar = document.querySelector("#vinesMovBuscar");
if (inputVinesBuscar) {
  inputVinesBuscar.addEventListener("input", pintarVinesModal);

  // Lector de pistola: escanea el VIN y Enter lo marca
  inputVinesBuscar.addEventListener("keydown", function (e) {
    if (e.key !== "Enter") return;
    e.preventDefault();
    const val = this.value.trim().toUpperCase();
    if (!val) return;
    const vin = vinesModalData.find((v) => v.numero_serie.toUpperCase() === val);
    if (vin) {
      vinesModalSel.add(String(vin.id_numeros_serie));
      this.value = "";
      pintarVinesModal();
    } else {
      Swal.fire("VIN no disponible", val + " no está en este almacén para este producto", "warning");
    }
  });
}

const btnVinesAceptar = document.querySelector("#btnVinesMovAceptar");
if (btnVinesAceptar) {
  btnVinesAceptar.addEventListener("click", function () {
    if (!filaVinActiva) return;
    const ids = Array.from(vinesModalSel).map(Number);
    filaVinActiva.querySelector(".seriesJson").value = JSON.stringify(ids);
    filaVinActiva.dataset.vines = vinesModalData
      .filter((v) => vinesModalSel.has(String(v.id_numeros_serie)))
      .map((v) => v.numero_serie)
      .join(", ");
    evaluarFilaVin(filaVinActiva);
    bootstrap.Modal.getInstance(document.querySelector("#modalVinesMov")).hide();
  });
}
