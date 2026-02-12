document.addEventListener("DOMContentLoaded", function () {
  const modalExistenciasEl = document.getElementById("modalExistencias");

  if (modalExistenciasEl) {
    modalExistenciasEl.addEventListener("hidden.bs.modal", function () {
      document.getElementById("bodyExistencias").innerHTML = "";
      document.getElementById("totalExistencias").innerText = "0";
    });
  }

  const form = document.querySelector("#formMultialmacenes");
  const selectInv = document.querySelector("#listInventario");
  const selectAlm = document.querySelector("#listAlmacenes");
  const table = $("#tableMultialmacenes").DataTable({
    ajax: {
      url: base_url + "/Inv_multialmacenes/getmultialmacenes",
      dataSrc: "",
    },
    columns: [
      { data: "inventario" },
      { data: "almacen" },
      { data: "existencia" },
    ],
    iDisplayLength: 10,
    order: [[0, "asc"]],
  });

  // Cargar select
  fetch(base_url + "/Inv_multialmacenes/getSelectAlmacenes")
    .then((res) => res.text())
    .then((html) => (selectAlm.innerHTML = html));

  // Form submit
  form.addEventListener("submit", function (e) {
    e.preventDefault();
    if (!document.querySelector("#listInventario").value) {
      Swal.fire("Selecciona un inventario válido", "", "warning");
      return;
    }
    const fd = new FormData(this);
    fetch(base_url + "/Inv_multialmacenes/setMultialmacen", {
      method: "POST",
      body: fd,
    })
      .then((res) => res.json())
      .then((obj) => {
        Swal.fire(obj.msg, "", obj.status ? "success" : "error");
        if (obj.status) {
          table.ajax.reload();
          form.reset();
          document.querySelector("#idmultialmacen").value = 0;
        }
      });
  });

  let inventariosCache = [];

  // Cargar inventarios en cache
  fetch(base_url + "/Inv_multialmacenes/getSelectInventariosJson")
    .then((res) => res.json())
    .then((data) => {
      inventariosCache = data;
      console.log("Inventarios:", inventariosCache);
    });

  const inputInv = document.querySelector("#inventarioSearch");

  if (inputInv) {
    inputInv.addEventListener("input", function () {
      let val = this.value.toLowerCase();
      cerrarListaInv();

      // limpiar hidden al escribir
      document.querySelector("#listInventario").value = "";

      if (!val) return;

      let lista = document.createElement("div");
      lista.className = "autocomplete-items list-group position-absolute w-100";
      this.parentNode.appendChild(lista);

      inventariosCache
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
            document.querySelector("#inventarioSearch").value =
              p.cve_articulo + " - " + p.descripcion;
            document.querySelector("#listInventario").value = p.idinventario;
            cerrarListaInv();
          });

          lista.appendChild(item);
        });
    });

    inputInv.addEventListener("change", function () {
      if (!this.value) {
        document.querySelector("#listInventario").value = "";
      }
    });
  }

  function cerrarListaInv() {
    document.querySelectorAll(".autocomplete-items").forEach((e) => e.remove());
  }
  // Cerrar autocomplete al hacer click fuera
  document.addEventListener("click", function (e) {
    const input = document.querySelector("#inventarioSearch");
    const lista = document.querySelector(".autocomplete-items");

    if (!input) return;

    if (
      e.target !== input &&
      !input.contains(e.target) &&
      (!lista || !lista.contains(e.target))
    ) {
      cerrarListaInv();
    }
  });
  document.addEventListener("keydown", function (e) {
    if (e.key === "Escape") {
      cerrarListaInv();
    }
  });

  // BOTON EXISTENCIAS
  document
    .getElementById("btnExistencias")
    .addEventListener("click", function () {
      const modal = new bootstrap.Modal(
        document.getElementById("modalExistencias"),
      );
      modal.show();
      document.getElementById("bodyExistencias").innerHTML = "";
    });
  const modalExistencias = new bootstrap.Modal(
    document.getElementById("modalExistencias"),
  );
  const modalBuscar = new bootstrap.Modal(
    document.getElementById("modalBuscarArticulo"),
  );

  document.getElementById("btnAbrirBusqueda").addEventListener("click", () => {
    document.getElementById("inputBuscarArticulo").value = "";
    document.getElementById("bodyBuscarArticulo").innerHTML = "";
    modalBuscar.show();
  });
});

document
  .getElementById("inputBuscarArticulo")
  .addEventListener("keyup", function () {
    const q = this.value;

    if (q.length < 2) {
      document.getElementById("bodyBuscarArticulo").innerHTML = "";
      return;
    }

    fetch(base_url + "/Inv_multialmacenes/buscarArticulos?q=" + q)
      .then((r) => r.json())
      .then((data) => {
        let html = "";

        data.forEach((row) => {
          html += `
            <tr>
              <td>${row.cve_articulo}</td>
              <td>${row.descripcion}</td>
              <td>
                <button class="btn btn-sm btn-primary"
                  onclick="seleccionarArticulo('${row.cve_articulo}')">
                  Seleccionar
                </button>
              </td>
            </tr>
          `;
        });

        document.getElementById("bodyBuscarArticulo").innerHTML = html;
      });
  });

function seleccionarArticulo(clave) {
  const modalBuscarEl = document.getElementById("modalBuscarArticulo");
  const instance = bootstrap.Modal.getInstance(modalBuscarEl);

  if (instance) instance.hide();

  cargarExistencias(clave);
  document.getElementById("inputBuscarArticulo").value = "";
document.getElementById("bodyBuscarArticulo").innerHTML = "";

}

function cargarExistencias(clave) {
  fetch(base_url + "/Inv_multialmacenes/buscarExistencias?q=" + clave)
    .then((r) => r.json())
    .then((data) => {
      let html = "";
      let totalGeneral = 0;

      data.forEach((row) => {
        totalGeneral += parseFloat(row.existencia);

        html += `
          <tr>
            <td>${row.cve_articulo}</td>
            <td>${row.descripcion}</td>
            <td>${row.almacen}</td>
            <td>${row.existencia}</td>
            <td><input type="number" class="form-control form-control-sm" value="${row.stock_minimo}"></td>
            <td><input type="number" class="form-control form-control-sm" value="${row.stock_maximo}"></td>
            <td>
              <button class="btn btn-sm btn-success"
                onclick="guardarStock(${row.idmultialmacen}, this)">
                Guardar
              </button>
            </td>
          </tr>`;
      });

      document.getElementById("bodyExistencias").innerHTML = html;
      document.getElementById("totalExistencias").innerText =
        totalGeneral.toFixed(2);
    });
}

function guardarStock(id, btn) {
  let row = btn.closest("tr");
  let min = row.querySelectorAll("input")[0].value;
  let max = row.querySelectorAll("input")[1].value;

  let fd = new FormData();
  fd.append("id", id);
  fd.append("stock_min", min);
  fd.append("stock_max", max);

  fetch(base_url + "/Inv_multialmacenes/updateStock", {
    method: "POST",
    body: fd,
  })
    .then((r) => r.json())
    .then((resp) => {
      Swal.fire(resp.msg, "", resp.status ? "success" : "error");
    });
}
