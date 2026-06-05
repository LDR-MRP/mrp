document.addEventListener("DOMContentLoaded", function () {
  const preview = document.getElementById("vinPreview");

  cargarCatalogos();
  cargarTabla();
  configurarDistancia();

  // ============================
  // EVENTOS
  // ============================

  document.querySelectorAll("select, input").forEach((el) => {
    el.addEventListener("change", actualizarPreview);
    el.addEventListener("input", actualizarPreview);
  });

  // ============================
  // CARGAR CATÁLOGOS
  // ============================

  async function cargarCatalogos() {
    // FABRICANTES
    fetch(base_url + "/Inv_captura_vin/getFabricantes")
      .then((res) => res.json())
      .then((data) => {
        let select = document.getElementById("id_fabricante");

        data.forEach((item) => {
          let option = document.createElement("option");

          option.value = item.id_fabricante;
          option.textContent = item.fabricante + " (" + item.wmi + ")";
          option.dataset.codigo = item.wmi;

          select.appendChild(option);
        });
      });

    // TIPOS VEHÍCULO
    fetch(base_url + "/Inv_captura_vin/getTiposVehiculo")
      .then((res) => res.json())
      .then((data) => {
        let select = document.getElementById("id_tipo_vehiculo");

        data.forEach((item) => {
          let option = document.createElement("option");

          option.value = item.id_tipo_vehiculo;
          option.textContent = item.descripcion;
          option.dataset.codigo = item.caracter;
          option.dataset.categoria = item.categoria;

          select.appendChild(option);
        });
      });

    // MOTOR
    fetch(base_url + "/Inv_captura_vin/getTiposMotor")
      .then((res) => res.json())
      .then((data) => {
        let select = document.getElementById("id_tipo_motor");

        data.forEach((item) => {
          let option = document.createElement("option");

          option.value = item.id_tipo_motor;
          option.textContent = item.descripcion;
          option.dataset.codigo = item.caracter;

          select.appendChild(option);
        });
      });

    // PLANTAS
    fetch(base_url + "/Inv_captura_vin/getPlantas")
      .then((res) => res.json())
      .then((data) => {
        let select = document.getElementById("id_planta");

        data.forEach((item) => {
          let option = document.createElement("option");

          option.value = item.id_planta;
          option.textContent = item.planta;
          option.dataset.codigo = item.caracter;

          select.appendChild(option);
        });
      });

    // AÑOS
    fetch(base_url + "/Inv_captura_vin/getAniosVin")
      .then((res) => res.json())
      .then((data) => {
        let select = document.getElementById("anio");

        data.forEach((a) => {
          let option = document.createElement("option");

          option.value = a.id_cat_anio_vin;
          option.textContent = a.anio + " (" + a.codigo + ")";
          option.dataset.codigo = a.codigo;

          select.appendChild(option);
        });
      });
  }

  function configurarDistancia() {
    let tipo = document.getElementById("tipo_distancia");

    tipo.addEventListener("change", function () {
      let valor = this.value;

      let input = document.getElementById("distancia_ejes");

      let label = document.getElementById("label_distancia");

      let help = document.getElementById("help_distancia");

      // ==========================
      // AUTOMÓVIL
      // ==========================

      if (valor === "AUTO") {
        label.innerHTML = "Distancia entre ejes (mm)";

        input.placeholder = "Ejemplo: 4500";

        input.step = "1";

        help.innerHTML = "Capturar en milímetros (mm)";
      }

      // ==========================
      // CAMIONES
      // ==========================
      else if (valor === "CAMION") {
        label.innerHTML = "Longitud total (m)";

        input.placeholder = "Ejemplo: 12.5";

        input.step = "0.01";

        help.innerHTML = "Capturar en metros (m)";
      }
    });
  }

  // ============================
  // PREVIEW VIN
  // ============================

  function actualizarPreview() {
    let vin = "";

    // 1-3 WMI
    let fabricante = document.getElementById("id_fabricante");
    vin += fabricante.selectedOptions[0]?.dataset.codigo || "---";

    // 4 Tipo vehículo
    let tipoVehiculo = document.getElementById("id_tipo_vehiculo");
    vin += tipoVehiculo.selectedOptions[0]?.dataset.codigo || "-";

    // 5 Peso
    let pesoInput = document.getElementById("peso_bruto_kg").value;

    if (pesoInput === "") {
      vin += "-";
    } else {
      let peso = parseFloat(pesoInput);

      if (peso < 3000) {
        vin += "1";
      } else if (peso < 10000) {
        vin += "2";
      } else if (peso < 20000) {
        vin += "3";
      } else {
        vin += "4";
      }
    }

    // 6 Motor
    let motor = document.getElementById("id_tipo_motor");
    vin += motor.selectedOptions[0]?.dataset.codigo || "-";

    // 7 Potencia
    let hpInput = document.getElementById("potencia_hp").value;

    if (hpInput === "") {
      vin += "-";
    } else {
      let hp = parseFloat(hpInput);

      if (hp <= 136) {
        vin += "1";
      } else if (hp <= 272) {
        vin += "2";
      } else if (hp <= 408) {
        vin += "3";
      } else {
        vin += "4";
      }
    }

    // 8 Distancia
    let distanciaInput = document.getElementById("distancia_ejes").value;

    if (distanciaInput === "") {
      vin += "-";
    } else {
      let distancia = parseFloat(distanciaInput);

      let categoria = document.getElementById("tipo_distancia").value;

      // ==========================
      // AUTOMÓVIL (MM)
      // ==========================

      if (categoria === "AUTO") {
        if (distancia <= 2000) {
          vin += "E";
        } else if (distancia <= 4500) {
          vin += "F";
        } else if (distancia <= 7000) {
          vin += "G";
        } else {
          vin += "H";
        }
      }

      // ==========================
      // CAMIONES / AUTOBÚS (M)
      // ==========================
      else if (categoria === "CAMION") {
        if (distancia <= 10) {
          vin += "J";
        } else if (distancia <= 15) {
          vin += "L";
        } else {
          vin += "K";
        }
      }
    }

    // 9 Verificador temporal
    vin += "-";

    // 10 Año
    let anio = document.getElementById("anio");
    vin += anio.selectedOptions[0]?.dataset.codigo || "-";

    // 11 Planta
    let planta = document.getElementById("id_planta");
    vin += planta.selectedOptions[0]?.dataset.codigo || "-";

    preview.textContent = vin;
    document.getElementById("vin_base").value = vin;
  }

  // ============================
  // GUARDAR
  // ============================

  document
    .getElementById("formVinModelo")
    .addEventListener("submit", function (e) {
      e.preventDefault();

      let form = document.getElementById("formVinModelo");

      let formData = new FormData(form);

      fetch(base_url + "/Inv_captura_vin/store", {
        method: "POST",
        body: formData,
      })
        .then((res) => res.json())

        .then((res) => {
          if (res.success) {
            Swal.fire({
              icon: "success",
              title: "Correcto",
              text: res.message,
            });

            form.reset();
            
            document.getElementById("id").value = "";

            let btn = document.getElementById("btnGuardar");

            btn.innerHTML = "Guardar Modelo VIN";

            btn.classList.remove("btn-warning");

            btn.classList.add("btn-success");

            document.getElementById("vinPreview").textContent = "--------";

            cargarTabla();
          } else {
            Swal.fire({
              icon: "error",
              title: "Error",
              text: res.message,
            });
          }
        })

        .catch((error) => {
          console.error(error);

          Swal.fire({
            icon: "error",
            title: "Error",
            text: "Ocurrió un error al guardar",
          });
        });
    });
});
function cargarTabla() {
  let tbody = document.getElementById("tablaVin");

  tbody.innerHTML = `
        <tr>
            <td colspan="6" class="text-center">
                Cargando...
            </td>
        </tr>
    `;

  fetch(base_url + "/Inv_captura_vin/getModelosVin")
    .then((res) => res.json())

    .then((res) => {
      tbody.innerHTML = "";

      if (!res.success || res.data.length === 0) {
        tbody.innerHTML = `
                    <tr>
                        <td colspan="6" class="text-center">
                            Sin registros
                        </td>
                    </tr>
                `;

        return;
      }

      res.data.forEach((item) => {
        // ==================================
        // GENERAR VIN BASE
        // ==================================

        let vin = "";

        // 1-3 WMI
        vin += item.wmi;

        // 4 tipo vehículo
        vin += item.caracter_vehiculo;

        // 5 peso
        let peso = parseFloat(item.peso_bruto_kg);

        if (peso < 3000) {
          vin += "1";
        } else if (peso < 10000) {
          vin += "2";
        } else if (peso < 20000) {
          vin += "3";
        } else {
          vin += "4";
        }

        // 6 motor
        vin += item.caracter_motor;

        // 7 potencia
        let hp = parseFloat(item.potencia_hp);

        if (hp <= 136) {
          vin += "1";
        } else if (hp <= 272) {
          vin += "2";
        } else if (hp <= 408) {
          vin += "3";
        } else {
          vin += "4";
        }

        // 8 distancia
        let distancia = parseFloat(item.distancia_ejes);

        if (item.categoria === "Automóvil") {
          if (distancia <= 2000) {
            vin += "E";
          } else if (distancia <= 4500) {
            vin += "F";
          } else if (distancia <= 7000) {
            vin += "G";
          } else {
            vin += "H";
          }
        } else {
          if (distancia <= 10) {
            vin += "J";
          } else if (distancia <= 15) {
            vin += "L";
          } else {
            vin += "K";
          }
        }

        // 9 verificador temporal
        vin += "-";

        // 10 año
        vin += item.codigo_anio;

        // 11 planta
        vin += item.caracter_planta;

        // ==================================
        // ESTADO
        // ==================================

        let estado =
          item.estado == 2
            ? `<span class="badge bg-success">
                        Activo
                      </span>`
            : `<span class="badge bg-danger">
                        Inactivo
                      </span>`;

        // ==================================
        // ROW
        // ==================================

        tbody.innerHTML += `

                    <tr>

                        <td>${item.modelo}</td>

                        <td>
                            <span class="fw-bold text-primary">
                                ${vin}
                            </span>
                        </td>

                        <td>${item.anio}</td>

                        <td>${item.planta}</td>

                        <td>${estado}</td>

                        <td>

                            <button
                                class="btn btn-sm btn-primary"
                                onclick='editarRegistro(${JSON.stringify(item)})'>

                                Editar

                            </button>

                        </td>

                    </tr>

                `;
      });
    });
}
function editarRegistro(item) {
  document.getElementById("btnGuardar").innerHTML = "Actualizar Modelo VIN";

  let btn = document.getElementById("btnGuardar");

  btn.innerHTML = "Actualizar Modelo VIN";

  btn.classList.remove("btn-success");

  btn.classList.add("btn-warning");

  // ID
  document.getElementById("id").value = item.id_cat_modelo_vin;

  // MODELO
  document.querySelector('[name="modelo"]').value = item.modelo;

  // ESTADO
  document.querySelector('[name="estado"]').value = item.estado;

  // FABRICANTE
  document.getElementById("id_fabricante").value = item.id_fabricante;

  // TIPO VEHÍCULO
  document.getElementById("id_tipo_vehiculo").value = item.id_tipo_vehiculo;

  // PESO
  document.getElementById("peso_bruto_kg").value = item.peso_bruto_kg;

  // MOTOR
  document.getElementById("id_tipo_motor").value = item.id_tipo_motor;

  // HP
  document.getElementById("potencia_hp").value = item.potencia_hp;

  // DISTANCIA
  document.getElementById("distancia_ejes").value = item.distancia_ejes;

  // AÑO
  document.getElementById("anio").value = item.id_cat_anio_vin;

  // PLANTA
  document.getElementById("id_planta").value = item.id_planta;

  // TIPO DISTANCIA
  if (item.categoria === "Automóvil") {
    document.getElementById("tipo_distancia").value = "AUTO";
  } else {
    document.getElementById("tipo_distancia").value = "CAMION";
  }

  // DISPARAR CONFIGURACIÓN
  document.getElementById("tipo_distancia").dispatchEvent(new Event("change"));

  // RECALCULAR VIN
  actualizarPreview();

  // SCROLL ARRIBA
  window.scrollTo({
    top: 0,
    behavior: "smooth",
  });
}
