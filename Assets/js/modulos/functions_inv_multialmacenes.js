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
  });

  // Cargar select
  fetch(base_url + "/Inv_multialmacenes/getSelectInventarios") // <-- CORREGIDO
    .then((res) => res.text())
    .then((html) => (selectInv.innerHTML = html));

  fetch(base_url + "/Inv_multialmacenes/getSelectAlmacenes")
    .then((res) => res.text())
    .then((html) => (selectAlm.innerHTML = html));

  // Form submit
  form.addEventListener("submit", function (e) {
    e.preventDefault();
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
});
