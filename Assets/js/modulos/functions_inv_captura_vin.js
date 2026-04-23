document.addEventListener("DOMContentLoaded", function () {
  const preview = document.getElementById("vinPreview");

  cargarTabla();

  // =============================
  // 🔹 CARGAR CATÁLOGO DE AÑOS
  // =============================
  fetch(base_url + "/Inv_captura_vin/getAniosVin")
    .then((res) => res.json())
    .then((data) => {
      let select = document.getElementById("anio");
      select.innerHTML = '<option value="">Año</option>';

      data.forEach((a) => {
        let option = document.createElement("option");

        option.value = a.id_cat_anio_vin; // 👈 IMPORTANTE (FK)
        option.textContent = a.anio + " (" + a.codigo + ")";
        option.dataset.codigo = a.codigo;

        select.appendChild(option);
      });
    });

  // =============================
  // 🔹 VALIDACIÓN INPUTS
  // =============================
  document.querySelectorAll(".vin-input, #planta").forEach((input) => {
    input.addEventListener("input", function () {
      let val = this.value.toUpperCase();

      val = val.replace(/[^A-Z0-9]/g, "");
      val = val.replace(/[IOQÑ]/g, "");

      this.value = val;

      actualizarPreview();
    });
  });

  document.getElementById("anio").addEventListener("change", actualizarPreview);

  // =============================
  // 🔹 PREVIEW VIN BASE (SIN CONTROL)
  // =============================
  function actualizarPreview() {
    let vin = "";

    // 1–8
    document.querySelectorAll(".vin-input").forEach((el) => {
      vin += el.value || "-";
    });

    // 9 (sin calcular)
    vin += "-";

    // 10 (año)
    let anioSelect = document.getElementById("anio");
    let codigoAnio = anioSelect.selectedOptions[0]?.dataset.codigo || "-";
    vin += codigoAnio;

    // 11 (planta)
    let planta = document.getElementById("planta").value || "-";
    vin += planta;

    preview.textContent = vin;
  }

  // =============================
  // 🔹 SUBMIT
  // =============================
  document
    .getElementById("formVinModelo")
    .addEventListener("submit", function (e) {
      e.preventDefault();

      let formData = new FormData(this);

      fetch(base_url + "/Inv_captura_vin/store", {
        method: "POST",
        body: formData,
      })
        .then((res) => res.json())
        .then((res) => {
          console.log(res);

          if (res.success) {
            Swal.fire("Correcto", res.message, "success");
            this.reset();
            preview.textContent = "--------";
            cargarTabla(); //  recargar tabla
          } else {
            Swal.fire("Warning", res.message, "warning");
          }
        });
    });
});

//funcion para recargar la tabla al guardar y entrar a la pagina
function cargarTabla() {
  let tbody = document.getElementById("tablaVin");

  tbody.innerHTML = `
    <tr>
      <td colspan="6" class="text-center">Cargando...</td>
    </tr>
  `;

  fetch(base_url + "/Inv_captura_vin/getModelosVin")
    .then((res) => res.json())
    .then((res) => {
      tbody.innerHTML = "";

      if (!res.success || res.data.length === 0) {
        tbody.innerHTML = `
          <tr>
            <td colspan="6" class="text-center">Sin registros</td>
          </tr>`;
        return;
      }

      res.data.forEach((item) => {
        let vinBase =
          item.digt_pais +
          item.digit_fabricante +
          item.digit_vehiculo +
          item.digit_modelo +
          item.digit_cuerpo +
          item.digit_sujecion +
          item.digit_transmision +
          item.digit_motor +
          "-" +
          item.codigo +
          item.digit_fabricacion;

        let estado =
          item.estado == 2
            ? '<span class="badge bg-success">Activo</span>'
            : '<span class="badge bg-danger">Inactivo</span>';

        tbody.innerHTML += `
          <tr>
            <td>${item.modelo}</td>
            <td>${vinBase}</td>
            <td>${item.anio}</td>
            <td>${item.digit_fabricacion}</td>
            <td>${estado}</td>
            <td>
      <button class="btn btn-sm btn-primary"
        onclick='editarRegistro(${JSON.stringify(item)})'>
        Editar
      </button>
    </td>
          </tr>`;
      });
    });
}
//funcion para editar el registro
function editarRegistro(item) {
  // modelo
  document.querySelector("[name='modelo']").value = item.modelo;

  // inputs VIN (1–8)
  const campos = [
    "digt_pais",
    "digit_fabricante",
    "digit_vehiculo",
    "digit_modelo",
    "digit_cuerpo",
    "digit_sujecion",
    "digit_transmision",
    "digit_motor",
  ];

  campos.forEach((campo) => {
    document.querySelector(`[name='${campo}']`).value = item[campo];
  });

  // año (FK)
  document.getElementById("anio").value = item.id_cat_anio_vin;

  // planta
  document.getElementById("planta").value = item.digit_fabricacion;

  // estado
  document.querySelector("[name='estado']").value = item.estado;

  // 👇 guardar ID oculto
  if (!document.getElementById("id_modelo_vin")) {
    let input = document.createElement("input");
    input.type = "hidden";
    input.name = "id";
    input.id = "id_modelo_vin";
    document.getElementById("formVinModelo").appendChild(input);
  }

  document.getElementById("id_modelo_vin").value = item.id_cat_modelo_vin;

  // refrescar preview
  actualizarPreview();

  // UX
  window.scrollTo({ top: 0, behavior: "smooth" });
}
