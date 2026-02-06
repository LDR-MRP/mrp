document.addEventListener("DOMContentLoaded", function () {
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

});
