let productosCache = [];
let tableKardex;

document.addEventListener("DOMContentLoaded", function () {
  cargarProductos();

  tableKardex = $("#tableKardex").DataTable({
    deferRender: true,
    pageLength: 25,
    language: {
      url: "https://cdn.datatables.net/plug-ins/1.13.7/i18n/es-ES.json",
    },
    columns: [
      { data: "numero_movimiento" },
      { data: "concepto" },
      { data: "almacen" },
      { data: "cantidad" },
      {
        data: "costo_cantidad",
        render: (d) => parseFloat(d).toFixed(2),
      },
      { data: "existencia" },
      {
        data: "costo_cantidad",
        render: (d, t, r) => (r.signo == 1 ? parseFloat(d).toFixed(2) : "0.00"),
      },
      { data: "fecha_movimiento" },
    ],
  });

  document.querySelector("#btnBuscar").addEventListener("click", function () {
    let inventarioid = document.querySelector("#inventarioid").value;

    if (!inventarioid) {
      alert("Seleccione un producto");
      return;
    }

    cargarInfoProducto(inventarioid);
    cargarKardex(inventarioid);
  });
});

function cargarProductos() {
  fetch(base_url + "/Inv_kardex/getProductos")
    .then((res) => res.json())
    .then((data) => {
      productosCache = data;
    });
}

document
  .querySelector("#inventarioSearch")
  .addEventListener("input", function () {
    let val = this.value.toLowerCase();
    cerrarLista();
    if (!val) return;

    let lista = document.createElement("div");
    lista.className = "autocomplete-items list-group position-absolute w-100";
    this.parentNode.appendChild(lista);

    productosCache
      .filter(
        (p) =>
          p.cve_articulo.toLowerCase().includes(val) ||
          p.descripcion.toLowerCase().includes(val),
      )
      .slice(0, 10)
      .forEach((p) => {
        let item = document.createElement("div");
        item.className = "list-group-item list-group-item-action";
        item.innerHTML = `<strong>${p.cve_articulo}</strong> - ${p.descripcion}`;

        item.addEventListener("click", function () {
          document.querySelector("#inventarioSearch").value =
            p.cve_articulo + " - " + p.descripcion;
          document.querySelector("#inventarioid").value = p.idinventario;
          cerrarLista();
        });

        lista.appendChild(item);
      });
  });

function cerrarLista() {
  document.querySelectorAll(".autocomplete-items").forEach((e) => e.remove());
}

function cargarKardex() {
  let inventarioid = document.querySelector("#inventarioid").value;
  let almacen = document.querySelector("#filtroAlmacen").value;
  let concepto = document.querySelector("#filtroConcepto").value;
  let fechaInicio = document.querySelector("#filtroFechaInicio").value;
  let fechaFin = document.querySelector("#filtroFechaFin").value;

  fetch(
    base_url +
      "/Inv_kardex/getKardex?inventarioid=" +
      inventarioid +
      "&almacen=" +
      almacen +
      "&concepto=" +
      concepto +
      "&fechaInicio=" +
      fechaInicio +
      "&fechaFin=" +
      fechaFin,
  )
    .then((res) => res.json())
    .then((data) => {
      tableKardex.clear();
      tableKardex.rows.add(data);
      tableKardex.draw();
    });
}

document.querySelector("#btnFiltrar").addEventListener("click", function () {
  let inventarioid = document.querySelector("#inventarioid").value;

  if (!inventarioid) {
    alert("Primero selecciona un producto");
    return;
  }

  cargarKardex();
});

// cargar almacenes
fetch(base_url + "/Inv_kardex/getSelectAlmacenes")
  .then((res) => res.text())
  .then((html) => {
    document.querySelector("#filtroAlmacen").innerHTML = html;
  });

// cargar conceptos
fetch(base_url + "/Inv_kardex/getSelectConceptos")
  .then((res) => res.text())
  .then((html) => {
    document.querySelector("#filtroConcepto").innerHTML = html;
  });
  
function cargarInfoProducto(inventarioid) {
  fetch(base_url + "/Inv_kardex/getInfoProducto/" + inventarioid)
    .then((res) => res.json())
    .then((data) => {
      let p = data.producto;
      let r = data.resumen;

      document.querySelector("#articulo").value =
        p.cve_articulo + " - " + p.descripcion;

      document.querySelector("#unidad_salida").value = p.unidad_salida;
      document.querySelector("#unidad_entrada").value = p.unidad_entrada;
      document.querySelector("#ubicacion").value = p.control_almacen;

      document.querySelector("#fecha_ultima_compra").value =
        r.fecha_ultima_compra ?? "";

      document.querySelector("#costo_promedio").value = parseFloat(
        r.costo_promedio ?? 0,
      ).toFixed(2);

      document.querySelector("#existencia_actual").value = r.existencia ?? 0;

      // Totales
      document.querySelector("#total_existencia").value =
        data.totales.total_existencia ?? 0;

      document.querySelector("#total_entradas").value =
        data.totales.total_entradas ?? 0;

      document.querySelector("#total_salidas").value =
        data.totales.total_salidas ?? 0;

      document.querySelector("#total_compras").value = parseFloat(
        data.totales.total_compras ?? 0,
      ).toFixed(2);
    });
}
