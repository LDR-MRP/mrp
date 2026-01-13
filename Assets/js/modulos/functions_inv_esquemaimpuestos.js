let tableImpuestos;
let rowTable = "";
let divLoading = document.querySelector("#divLoading");

// Inputs del formulario
const cve_impuesto = document.querySelector("#clave-impuesto-input");
const estado = document.querySelector("#estado-select");
const descripcion = document.querySelector("#descripcion-impuesto-textarea");

// Mis referencias globales
let primerTab;
let firstTab;
let tabNuevo;
let spanBtnText = null;
let formImpuestos = null;

document.addEventListener("DOMContentLoaded", function () {
  formImpuestos = document.querySelector("#formImpuestos");
  spanBtnText = document.querySelector("#btnText");

  tableImpuestos = $("#tableImpuestos").dataTable({
    aProcessing: true,
    aServerSide: true,
    ajax: {
      url: base_url + "/Inv_esquemaimpuestos/getImpuestos",
      dataSrc: "",
    },
    columns: [
      { data: "cve_impuesto" },
      { data: "descripcion" },
      { data: "impuesto1" },
      { data: "impuesto2" },
      { data: "impuesto3" },
      { data: "impuesto4" },
      { data: "impuesto5" },
      { data: "impuesto6" },
      { data: "impuesto7" },
      { data: "impuesto8" },
      { data: "fecha_creacion" },
      { data: "estado_texto" },
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
    '#nav-tab a[href="#listimpuestos"]'
  );
  const firstTabEl = document.querySelector(
    '#nav-tab a[href="#agregarImpuesto"]'
  );

  if (primerTabEl && firstTabEl && typeof bootstrap !== "undefined") {
    primerTab = new bootstrap.Tab(primerTabEl);
    firstTab = new bootstrap.Tab(firstTabEl);

    // Click en NUEVO
    firstTabEl.addEventListener("click", () => {
      spanBtnText.textContent = "REGISTRAR";
      formImpuestos.reset();
      document.querySelector("#idimpuesto").value = 0;
    });
  }

  formImpuestos.addEventListener("submit", function (e) {
    e.preventDefault();

    let formData = new FormData(formImpuestos);
    let url = base_url + "/Inv_esquemaimpuestos/setImpuesto";

    fetch(url, {
      method: "POST",
      body: formData,
    })
      .then((res) => res.json())
      .then((objData) => {
        if (objData.status) {
          Swal.fire("Correcto", objData.msg, "success").then(() => {
            $("#tableImpuestos").DataTable().ajax.reload(null, false);

            // 🔥 CAMBIO REAL DE TAB (FORMA SEGURA)
            primerTabEl.click();

            formImpuestos.reset();
            document.querySelector("#idimpuesto").value = 0;
            spanBtnText.textContent = "REGISTRAR";
          });
        } else {
          Swal.fire("Error", objData.msg, "error");
        }
      });
  });
});

// ----------------------------------------------
// VER DETALLE
// ----------------------------------------------
function fntViewImpuesto(id) {
  fetch(base_url + "/Inv_esquemaimpuestos/getImpuesto/" + id)
    .then((res) => res.json())
    .then((objData) => {
      if (objData.status) {
        document.querySelector("#celClave").innerHTML =
          objData.data.cve_impuesto;

        document.querySelector("#celDescripcion").innerHTML =
          objData.data.descripcion;

        document.querySelector("#celFecha").innerHTML =
          objData.data.fecha_creacion;

        document.querySelector("#celEstado").innerHTML =
          objData.data.estado == 2 ? "Activo" : "Inactivo";

        // 🔥 Construir detalle de impuestos
        let html = `<table class="table table-sm table-bordered mt-2">
          <thead>
            <tr>
              <th>Impuesto</th>
              <th>%</th>
              <th>Aplica a</th>
            </tr>
          </thead>
          <tbody>`;

        for (let i = 1; i <= 8; i++) {
          html += `
            <tr>
              <td>Impuesto ${i}</td>
              <td>${objData.data[`impuesto${i}`]} %</td>
              <td>${getAplicaTexto(i, objData.data[`imp${i}_aplica`])}</td>
            </tr>`;
        }

        html += `</tbody></table>`;

        document.querySelector("#detalleImpuestos").innerHTML = html;

        $("#modalViewPrecio").modal("show");
      }
    });
}
function getAplicaTexto(impuesto, valor) {
  const catalogos = {
    1: { 0: "Exento", 1: "Precio base" },
    2: { 0: "Exento", 1: "Precio base", 2: "Acumulado 1" },
    3: { 0: "Exento", 1: "Precio base", 2: "Acumulado 1", 3: "Acumulado 2" },
    4: { 0: "Exento", 1: "Precio base", 3: "Acumulado 2", 4: "Acumulado 3" },
    5: {
      0: "Precio base",
      1: "Acumulado 1",
      2: "Acumulado 2",
      3: "Acumulado 3",
      4: "Exento",
      6: "No aplica",
      7: "Acumulado 4",
    },
    6: {
      0: "Precio base",
      1: "Acumulado 1",
      2: "Acumulado 2",
      3: "Acumulado 3",
      4: "Exento",
      6: "No aplica",
      7: "Acumulado 4",
      8: "Acumulado 5",
    },
    7: {
      0: "Precio base",
      1: "Acumulado 1",
      2: "Acumulado 2",
      3: "Acumulado 3",
      4: "Exento",
      6: "No aplica",
      7: "Acumulado 4",
      8: "Acumulado 5",
      9: "Acumulado 6",
    },
    8: {
      0: "Precio base",
      1: "Acumulado 1",
      2: "Acumulado 2",
      3: "Acumulado 3",
      4: "Exento",
      6: "No aplica",
      7: "Acumulado 4",
      8: "Acumulado 5",
      9: "Acumulado 6",
      10: "Acumulado 7",
    },
  };

  return catalogos[impuesto][valor] ?? "—";
}

// ----------------------------------------------
// EDITAR
// ----------------------------------------------
function fntEditImpuesto(id) {
  fetch(base_url + "/Inv_esquemaimpuestos/getImpuesto/" + id)
    .then((res) => res.json())
    .then((objData) => {
      if (objData.status) {
        document.querySelector("#idimpuesto").value = objData.data.idimpuesto;
        cve_impuesto.value = objData.data.cve_impuesto;
        descripcion.value = objData.data.descripcion;

        for (let i = 1; i <= 8; i++) {
          document.querySelector(`[name="impuesto${i}"]`).value =
            objData.data[`impuesto${i}`];
          document.querySelector(`[name="imp${i}_aplica"]`).value =
            objData.data[`imp${i}_aplica`];
        }

        estado.value = objData.data.estado;

        spanBtnText.textContent = "ACTUALIZAR";
        firstTab.show(); // 🔥 AHORA SÍ FUNCIONA
      }
    });
}

// ------------------------------------------------------------------------
//  ELIMINAR UN esuqema de impuestos
// ------------------------------------------------------------------------
function fntDelInfo(idimpuesto) {
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
    let ajaxUrl = base_url + "/Inv_esquemaimpuestos/delImpuesto";
    let strData = "idimpuesto=" + idimpuesto;

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
          $("#tableImpuestos").DataTable().ajax.reload();
        } else {
          Swal.fire("Error", objData.msg, "error");
        }
      }
    };
  });
}
