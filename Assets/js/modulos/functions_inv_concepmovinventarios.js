let tableConceptos;
let rowTable = "";
let divLoading = document.querySelector("#divLoading");

// Inputs del formulario
const cve_concep_mov = document.querySelector(
  "#clave-concepto-movimiento-input"
);
const asociado = document.querySelector("#asociado-select");
const estado = document.querySelector("#estado-select");
const descripcion = document.querySelector("#descripcion-concepto-textarea");

// Mis referencias globales
let primerTab;
let firstTab;
let tabNuevo;
let spanBtnText = null;
let formConceptos = null;

document.addEventListener("DOMContentLoaded", function () {
  formConceptos = document.querySelector("#formConceptos");
  spanBtnText = document.querySelector("#btnText");

  tableConceptos = $("#tableConceptos").dataTable({
    aProcessing: true,
    aServerSide: true,
    ajax: {
      url: base_url + "/Inv_concepmovinventarios/getConceptos",
      dataSrc: "",
    },
    columns: [
      { data: "cve_concep_mov" },
      { data: "descripcion" },
      { data: "cpn" },
      { data: "tipo_movimiento" },
      { data: "estado" },
      { data: "options" },
    ],
    dom: "lBfrtip",
    buttons: [],
    resonsieve: "true",
    bDestroy: true,
    iDisplayLength: 10,
    order: [[0, "desc"]],
  });

  const primerTabEl = document.querySelector(
    '#nav-tab a[href="#lisconceptos"]'
  );
  const firstTabEl = document.querySelector(
    '#nav-tab a[href="#agregarConcepto"]'
  );

  if (primerTabEl && firstTabEl && spanBtnText) {
    primerTab = new bootstrap.Tab(primerTabEl);
    firstTab = new bootstrap.Tab(firstTabEl);
    tabNuevo = firstTabEl;

    tabNuevo.addEventListener("click", () => {
      spanBtnText.textContent = "REGISTRAR";
      formConceptos.reset();
      document.querySelector("#idconcepmov").value = 0;
    });
  }

  formConceptos.addEventListener("submit", function (e) {
    e.preventDefault();

    if (!checkEntrada.checked && !checkSalida.checked) {
        Swal.fire("Atención", "Debes seleccionar Entrada o Salida", "warning");
        return;
    }

    let formData = new FormData(formConceptos);
    let url = base_url + "/Inv_concepmovinventarios/setConceptomovimiento";

    fetch(url, {
      method: "POST",
      body: formData,
    })
      .then((res) => res.json())
      .then((objData) => {
        if (objData.status) {
          $("#tableConceptos").DataTable().ajax.reload();
          primerTab.show();
          Swal.fire("Correcto", objData.msg, "success");
          formConceptos.reset();
        } else {
          Swal.fire("Error", objData.msg, "error");
        }
      });
  });

  // -------------------------------
// CONTROL CHECK ENTRADA / SALIDA
// -------------------------------
const checkEntrada = document.querySelector('#checkEntrada');
const checkSalida  = document.querySelector('#checkSalida');

function resetChecks() {
    checkEntrada.checked = false;
    checkSalida.checked  = false;
}

// Solo permitir uno activo
checkEntrada.addEventListener('change', function () {
    if (this.checked) {
        checkSalida.checked = false;
    }
});

checkSalida.addEventListener('change', function () {
    if (this.checked) {
        checkEntrada.checked = false;
    }
});

});

// ----------------------------------------------
// VER DETALLE
// ----------------------------------------------
function fntViewPrecio(id) {
  fetch(base_url + "/Inv_concepmovinventarios/getPrecio/" + id)
    .then((res) => res.json())
    .then((objData) => {
      if (objData.status) {
        document.querySelector("#celClave").innerHTML =
          objData.data.cve_concep_mov;
        document.querySelector("#celDescripcion").innerHTML =
          objData.data.descripcion;
        document.querySelector("#celAsociado").innerHTML =
          objData.data.impuesto == 2 ? "NO" : "SI";
        document.querySelector("#celFecha").innerHTML =
          objData.data.fecha_creacion;
        document.querySelector("#celEstado").innerHTML =
          objData.data.estado == 2 ? "Activo" : "Inactivo";

        $("#modalViewConcepto").modal("show");
      }
    });
}

// ----------------------------------------------
// EDITAR
// ----------------------------------------------
function fntEditPrecio(id) {
  fetch(base_url + "/Inv_concepmovinventarios/getPrecio/" + id)
    .then((res) => res.json())
    .then((objData) => {
      if (objData.status) {
        document.querySelector("#idconcepmov").value = objData.data.idconcepmov;
        cve_concep_mov.value = objData.data.cve_concep_mov;
        descripcion.value = objData.data.descripcion;
        estado.value = objData.data.estado;

        spanBtnText.textContent = "ACTUALIZAR";
        firstTab.show();
      }
    });
}

// ------------------------------------------------------------------------
//  ELIMINAR UN PRECIO
// ------------------------------------------------------------------------
function fntDelInfo(idconcepmov) {
  Swal.fire({
    html: `
        <div class="mt-3">
            <lord-icon 
                src="https://cdn.lordicon.com/gsqxdxog.json" 
                trigger="loop" 
                colors="primary:#f7b84b,secondary:#f06548" 
                style="width:100px;height:100px">
            </lord-icon>

            <div class="mt-4 pt-2 fs-15 mx-5">
                <h4>Confirmar eliminación</h4>
                <p class="text-muted mx-4 mb-0">
                    ¿Estás seguro de eliminar este registro?
                    Esta acción no se puede deshacer.
                </p>
            </div>
        </div>
        `,
    showCancelButton: true,
    confirmButtonText: "Sí, eliminar",
    cancelButtonText: "Cancelar",
    customClass: {
      confirmButton: "btn btn-primary w-xs me-2 mb-1",
      cancelButton: "btn btn-danger w-xs mb-1",
    },
    buttonsStyling: false,
    showCloseButton: true,
  }).then((result) => {
    if (!result.isConfirmed) return;

    let request = window.XMLHttpRequest
      ? new XMLHttpRequest()
      : new ActiveXObject("Microsoft.XMLHTTP");
    let ajaxUrl = base_url + "/Inv_concepmovinventarios/delPrecio";
    let strData = "idconcepmov=" + idconcepmov;

    request.open("POST", ajaxUrl, true);
    request.setRequestHeader(
      "Content-type",
      "application/x-www-form-urlencoded"
    );
    request.send(strData);

    request.onreadystatechange = function () {
      if (request.readyState === 4 && request.status === 200) {
        let objData = JSON.parse(request.responseText);

        if (objData.status) {
          Swal.fire("Correcto", objData.msg, "success");
          $("#tableConceptos").DataTable().ajax.reload();
        } else {
          Swal.fire("Error", objData.msg, "error");
        }
      }
    };
  });
}
