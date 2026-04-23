let table;

document.addEventListener("DOMContentLoaded", function () {
  loadMonedas();
   let hoy = new Date().toISOString().split("T")[0];
   let inputFecha = document.getElementById("fecha_creacion");
  inputFecha.value = hoy;

  document.getElementById("fecha_creacion").valueAsDate = new Date();

  table = $("#tableTipoCambio").DataTable({
    ajax: {
      url: base_url + "/Inv_tipo_cambio_moneda/getTipoCambio",
      type: "GET",
      data: function (d) {
        d.moneda = document.getElementById("filtro_moneda").value;
        d.desde = document.getElementById("fecha_desde").value;
        d.hasta = document.getElementById("fecha_hasta").value;
      },
      dataSrc: "data",
    },

    columns: [
      { data: "moneda" },
      { data: "tipo_cambio"},
      { data: "fecha_creacion" },
    ],

    order: [[2, "desc"]],
    destroy: true,
  });

  document.getElementById("btnBuscar").addEventListener("click", () => {
    table.ajax.reload();
  });
});

/* ================= MONEDAS ================= */
function loadMonedas() {
  fetch(base_url + "/Inv_tipo_cambio_moneda/getMonedas")
    .then((res) => res.json())
    .then((data) => {

      let optionsAlta = `<option value="">Seleccione</option>`;
      let optionsFiltro = `<option value="">Todas</option>`;

      data.forEach((m) => {
        let label = `${m.cve_moneda} - ${m.descripcion}`;

        optionsAlta += `<option value="${m.idmoneda}">${label}</option>`;
        optionsFiltro += `<option value="${m.idmoneda}">${label}</option>`;
      });

      document.getElementById("monedaid").innerHTML = optionsAlta;
      document.getElementById("filtro_moneda").innerHTML = optionsFiltro;
    });
}

/* ================= GUARDAR ================= */
document
  .getElementById("formTipoCambio")
  .addEventListener("submit", function (e) {
    e.preventDefault();

    let formData = new FormData(this);

    fetch(base_url + "/Inv_tipo_cambio_moneda/store", {
      method: "POST",
      body: formData,
    })
      .then((res) => res.json())
      .then((data) => {
        if (data.status === "success") {
          Swal.fire("Correcto", data.message, "success");

          toggleForm();
          table.ajax.reload();
        } else {
          Swal.fire("Error", data.message, "error");
        }
      });
  });

/* ================= TOGGLE ================= */
function toggleForm() {
  const form = document.getElementById("formContainer");

  if (form.style.display === "none") {
    form.style.display = "block";
  } else {
    form.style.display = "none";
    document.getElementById("formTipoCambio").reset();
  }
}

document.querySelector("[name='tipo_cambio']").addEventListener("input", function () {
  if (this.value <= 0) {
    this.classList.add("is-invalid");
  } else {
    this.classList.remove("is-invalid");
    this.classList.add("is-valid");
  }
});
