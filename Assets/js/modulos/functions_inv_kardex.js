document.addEventListener("DOMContentLoaded", function () {
  cargarProductos();

  document.querySelector("#btnBuscar").addEventListener("click", function () {
    let inventarioid = document.querySelector("#inventarioid").value;
    if (inventarioid === "") {
      alert("Seleccione un producto");
      return;
    }
    cargarKardex(inventarioid);
  });
});

function cargarProductos() {
  fetch(base_url + "/Inv_kardex/getProductos")
    .then((res) => res.json())
    .then((data) => {
      let select = document.querySelector("#inventarioid");
      data.forEach((p) => {
        select.innerHTML += `
                    <option value="${p.idinventario}">
                        ${p.cve_articulo} - ${p.descripcion}
                    </option>`;
      });
    });
}

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
        r.costo_promedio ?? 0
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
        data.totales.total_compras ?? 0
      ).toFixed(2);
    });
}

document.querySelector("#btnBuscar").addEventListener("click", function () {
  let inventarioid = document.querySelector("#inventarioid").value;

  cargarInfoProducto(inventarioid);
  cargarKardex(inventarioid);
});

function cargarKardex(inventarioid) {
  fetch(base_url + "/Inv_kardex/getKardex/" + inventarioid)
    .then((res) => res.json())
    .then((data) => {
      let tbody = document.querySelector("#tableKardex tbody");
      tbody.innerHTML = "";

      data.forEach((row) => {
        let compras =
          row.signo == 1 ? parseFloat(row.costo_cantidad).toFixed(2) : "0.00";

        tbody.innerHTML += `
                    <tr>
                        <td>${row.numero_movimiento}</td>
                        <td>${row.concepto}</td>
                        <td>${row.cantidad}</td>
                        <td>${parseFloat(row.costo).toFixed(2)}</td>
                        <td>${row.existencia}</td>
                        <td>${compras}</td>
                        <td>${row.fecha_movimiento}</td>
                    </tr>
                `;
      });
    });
}
