let tableMotores;
let divLoading = null;
let formMotores = null;
let idMotorInput = null;
let tipoMotorSelect = null;
let activoSelect = null;
let fieldsetElectrico = null;
let primerTab = null;
let firstTab = null;
let tabNuevo = null;
let spanBtnText = null;

document.addEventListener("DOMContentLoaded", function () {
  divLoading = document.querySelector("#divLoading");
  formMotores = document.querySelector("#formMotores");
  spanBtnText = document.querySelector("#btnText");
  idMotorInput = document.querySelector("#id_motor");
  tipoMotorSelect = document.querySelector("#tipo-motor-select");
  activoSelect = document.querySelector("#activo-select");
  fieldsetElectrico = document.querySelector("#fieldsetElectrico");

  if (!formMotores) {
    console.warn("formMotores no encontrado. JS de motores no se inicializa en esta vista.");
    return;
  }

  // --------------------------------------------------------------
  // DATATABLE
  // --------------------------------------------------------------
  tableMotores = $("#tableMotores").DataTable({
    processing: true,
    serverSide: false,
    ajax: {
      url: base_url + "/Ing_motores/getMotores",
      dataSrc: "",
    },
    columns: [
      { data: "fabricante" },
      { data: "modelo_motor" },
      { data: "tipo_motor_label" },
      {
        data: null,
        render: function (row) {
          if (!row.potencia) return "";
          let valor = Math.round(parseFloat(row.potencia));
          let unidad = (row.unidad_potencia || "").toLowerCase();
          return valor + " " + unidad;
        },
      },
      { data: "tipo_combustible" },
      { data: "activo_label" },
      { data: "options" },
    ],
    responsive: true,
    destroy: true,
    pageLength: 10,
  });

  // --------------------------------------------------------------
  // TOGGLE COMBUSTIÓN / ELÉCTRICO
  // --------------------------------------------------------------
  function toggleTipoMotor() {
    if (!tipoMotorSelect || !fieldsetElectrico) return;
    fieldsetElectrico.style.display = tipoMotorSelect.value === "ELECTRICO" ? "flex" : "none";
  }
  if (tipoMotorSelect) {
    tipoMotorSelect.addEventListener("change", toggleTipoMotor);
    toggleTipoMotor();
  }

  // --------------------------------------------------------------
  // TABS
  // --------------------------------------------------------------
  const primerTabEl = document.querySelector('#nav-tab a[href="#listMotores"]');
  const firstTabEl = document.querySelector('#nav-tab a[href="#agregarMotor"]');

  if (primerTabEl && firstTabEl && spanBtnText) {
    primerTab = new bootstrap.Tab(primerTabEl);
    firstTab = new bootstrap.Tab(firstTabEl);
    tabNuevo = firstTabEl;

    tabNuevo.addEventListener("click", () => {
      resetForm();
    });
    primerTabEl.addEventListener("click", () => {
      resetForm();
    });
  }

  function resetForm() {
    if (tabNuevo) tabNuevo.textContent = "NUEVO";
    if (spanBtnText) spanBtnText.textContent = "REGISTRAR";
    if (idMotorInput) idMotorInput.value = "0";
    if (formMotores) formMotores.reset();
    toggleTipoMotor();
  }

  // --------------------------------------------------------------
  // SUBMIT
  // --------------------------------------------------------------
  formMotores.addEventListener("submit", function (e) {
    e.preventDefault();

    if (divLoading) divLoading.style.display = "flex";

    let request = new XMLHttpRequest();
    let ajaxUrl = base_url + "/Ing_motores/setMotor";
    let formData = new FormData(formMotores);

    request.open("POST", ajaxUrl, true);
    request.send(formData);

    request.onreadystatechange = function () {
      if (request.readyState !== 4) return;
      if (divLoading) divLoading.style.display = "none";

      if (request.status !== 200) {
        Swal.fire("Error", "Ocurrió un error en el servidor. Inténtalo de nuevo.", "error");
        return;
      }

      let objData = JSON.parse(request.responseText);

      if (objData.status) {
        Swal.fire({
          title: objData.msg,
          icon: "success",
          confirmButtonText: "OK",
          confirmButtonColor: "#28a745",
        }).then(() => {
          resetForm();
          if (tableMotores) tableMotores.ajax.reload();
          if (primerTab) primerTab.show();
        });
      } else {
        Swal.fire("Atención", objData.msg, "warning");
      }
    };
  });
});

// ------------------------------------------------------------------------
// EDITAR
// ------------------------------------------------------------------------
function fntEditInfo(idMotor) {
  if (tabNuevo) tabNuevo.textContent = "ACTUALIZAR";
  if (spanBtnText) spanBtnText.textContent = "ACTUALIZAR";

  let request = new XMLHttpRequest();
  let ajaxUrl = base_url + "/Ing_motores/getMotor/" + idMotor;

  request.open("GET", ajaxUrl, true);
  request.send();

  request.onreadystatechange = function () {
    if (request.readyState !== 4) return;
    if (request.status !== 200) {
      Swal.fire("Error", "Error al consultar el motor.", "error");
      return;
    }

    let objData = JSON.parse(request.responseText);
    if (!objData.status) {
      Swal.fire("Error", objData.msg, "error");
      return;
    }

    let d = objData.data;
    document.querySelector("#id_motor").value = d.id_motor;
    document.querySelector("#fabricante-input").value = d.fabricante || "";
    document.querySelector("#modelo-motor-input").value = d.modelo_motor || "";
    document.querySelector("#tipo-motor-select").value = d.tipo_motor || "COMBUSTION";
    document.querySelector("#cilindrada-input").value = d.cilindrada || "";
    document.querySelector("#cilindros-input").value = d.numero_cilindros || "";
    document.querySelector("#potencia-input").value = d.potencia || "";
    document.querySelector("#unidad-potencia-select").value = d.unidad_potencia || "HP";
    document.querySelector("#torque-input").value = d.torque || "";
    document.querySelector("#unidad-torque-select").value = d.unidad_torque || "LB-PIE";
    document.querySelector("#tipo-combustible-input").value = d.tipo_combustible || "";
    document.querySelector("#tipo-admision-input").value = d.tipo_admision || "";
    document.querySelector("#fabricante-bateria-input").value = d.fabricante_bateria || "";
    document.querySelector("#tipo-bateria-input").value = d.tipo_bateria || "";
    document.querySelector("#capacidad-bateria-input").value = d.capacidad_bateria || "";
    document.querySelector("#consumo-input").value = d.consumo || "";
    document.querySelector("#conector-input").value = d.conector || "";
    document.querySelector("#proteccion-ip-input").value = d.proteccion_ip || "";
    document.querySelector("#sistema-electrico-input").value = d.sistema_electrico || "";
    document.querySelector("#activo-select").value = d.activo;

    document.querySelector("#tipo-motor-select").dispatchEvent(new Event("change"));

    if (firstTab) firstTab.show();
  };
}

// ------------------------------------------------------------------------
// ELIMINAR
// ------------------------------------------------------------------------
function fntDelInfo(idMotor) {
  Swal.fire({
    title: "Confirmar eliminación",
    text: "¿Estás seguro de que deseas eliminar este motor? Esta acción no se puede deshacer.",
    icon: "warning",
    showCancelButton: true,
    confirmButtonText: "Sí, eliminar",
    cancelButtonText: "Cancelar",
    confirmButtonColor: "#dc3545",
  }).then((result) => {
    if (!result.isConfirmed) return;

    let request = new XMLHttpRequest();
    let ajaxUrl = base_url + "/Ing_motores/delMotor";
    let strData = "id_motor=" + idMotor;

    request.open("POST", ajaxUrl, true);
    request.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
    request.send(strData);

    request.onreadystatechange = function () {
      if (request.readyState === 4 && request.status === 200) {
        let objData = JSON.parse(request.responseText);
        if (objData.status) {
          Swal.fire("¡Operación exitosa!", objData.msg, "success");
          if (tableMotores) tableMotores.ajax.reload();
        } else {
          Swal.fire("Atención", objData.msg, "error");
        }
      }
    };
  });
}
