let tableMovimientos;
let rowTable = "";
let divLoading = document.querySelector("#divLoading");

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

  const primerTabEl = document.querySelector('#nav-tab a[href="#listmovimiento"]');
  const firstTabEl = document.querySelector(
    '#nav-tab a[href="#agregarMovimiento"]'
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

  // Cargar inventarios
  fetch(base_url + "/Inv_movimientosinventario/getSelectInventario")
    .then((res) => res.text())
    .then((html) => {
      document.querySelector("#inventarioid").innerHTML = html;
    });

  // Cargar productos
  fetch(base_url + "/Inv_movimientosinventario/getSelectConceptos")
    .then((res) => res.text())
    .then((html) => {
      document.querySelector("#concepmovid").innerHTML = html;
    });
});
