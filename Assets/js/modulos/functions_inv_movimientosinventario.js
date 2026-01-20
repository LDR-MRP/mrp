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
  formPrecios = document.querySelector("#formPrecios");
  spanBtnText = document.querySelector("#btnText");

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

    tabNuevo.addEventListener("click", () => {
      spanBtnText.textContent = "REGISTRAR";
      formPrecios.reset();
      document.querySelector("#idprecio").value = 0;
    });
  }

  const formMovimiento = document.querySelector("#formMovimiento");

  formMovimiento.addEventListener("submit", function (e) {
    e.preventDefault();

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
