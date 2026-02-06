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

  tableMovimientos = $("#tableMovimientos").DataTable({
    processing: true,
    ajax: {
      url: base_url + "/Inv_movimientosinventario/getMovimientos",
      dataSrc: "",
    },
    columns: [
      { data: "idmovinventario" },
      { data: "producto" },
      { data: "almacen" },
      { data: "concepto" },
      { data: "referencia" },
      { data: "cantidad" },
      { data: "existencia" },
      { data: "fecha_movimiento" },
    ],
    order: [[0, "desc"]],
    destroy: true,
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

    fetch(base_url + "/Inv_movimientosinventario/setMovimiento", {
      method: "POST",
      body: formData,
    })
      .then((res) => res.json())
      .then((obj) => {
        if (obj.status) {
          Swal.fire("Correcto", obj.msg, "success");
          formMovimiento.reset();
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

document.querySelector("#btnAddRow").addEventListener("click", () => {
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

document.querySelector("#concepmovid").addEventListener("change", function () {
  const id = this.value;
  const bloque = document.querySelector("#bloqueCPN");
  const input = document.querySelector("#cpnValor");

  if (!id) {
    bloque.style.display = "none";
    input.value = "";
    return;
  }

  fetch(base_url + "/Inv_movimientosinventario/getConceptoInfo/" + id)
    .then((r) => r.json())
    .then((data) => {
      if (!data) return;

      bloque.style.display = "block";

      if (data.cpn === "C") {
        input.value = "Cliente";
      } else if (data.cpn === "P") {
        input.value = "Proveedor";
      } else {
        input.value = "Ninguno";
      }
    });
});
