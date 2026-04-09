let table;
let collapsedSedes = {};
let collapsedZonas = {};

document.addEventListener("DOMContentLoaded", function () {
  loadSedes();

  table = $("#tableUbicaciones").DataTable({
    ajax: {
      url: base_url + "/Inv_ubicaciones/getUbicaciones",
      type: "GET",
      dataSrc: "data",
    },

    paging: false,
    info: false,
    lengthChange: false,
    ordering: false,

    columns: [
      { data: "cve_sede" },
      { data: "cve_zona" },
      { data: "pasillo" },
      { data: "seccion" },
      { data: "nivel" },
      { data: "lugar" },
      { data: "estado" },
      { data: "fecha_creacion" },
    ],

    order: [
      [0, "asc"],
      [1, "asc"],
    ],

    drawCallback: function () {
      let api = this.api();
      let rows = api.rows({ page: "current" }).nodes();
      let data = api.rows({ page: "current" }).data();

      let lastSede = null;
      let lastZona = null;

      api.rows({ page: "current" }).every(function (rowIdx) {
        let rowData = data[rowIdx];
        let sede = rowData.cve_sede;
        let zona = rowData.cve_zona;

        let rowNode = rows[rowIdx];

        /* ===== SEDE ===== */

        if (lastSede !== sede) {
          if (collapsedSedes[sede] === undefined) {
            collapsedSedes[sede] = true; // inicia colapsado
          }

          $(rowNode).before(`
            <tr class="group-sede" data-name="${sede}">
              <td colspan="6" class="fw-bold bg-light" style="cursor:pointer;">
                ${collapsedSedes[sede] ? "▶" : "▼"} SEDE: ${sede}
              </td>
            </tr>
          `);

          lastSede = sede;
          lastZona = null;
        }

        /* ===== ZONA ===== */

        /* ===== ZONA ===== */

        if (!collapsedSedes[sede]) {
          if (lastZona !== zona) {
            let keyZona = sede + "_" + zona;

            if (collapsedZonas[keyZona] === undefined) {
              collapsedZonas[keyZona] = true;
            }

            $(rowNode).before(`
      <tr class="group-zona" data-sede="${sede}" data-name="${zona}">
        <td colspan="6" class="ps-4 fw-semibold bg-white" style="cursor:pointer;">
          ${collapsedZonas[keyZona] ? "▶" : "▼"} ZONA: ${zona}
        </td>
      </tr>
    `);

            lastZona = zona;
          }
        }

        /* ===== VISIBILIDAD ===== */

        let hide = false;

        if (collapsedSedes[sede]) {
          hide = true;
        } else {
          let keyZona = sede + "_" + zona;

          if (collapsedZonas[keyZona]) {
            hide = true;
          }
        }

        rowNode.style.display = hide ? "none" : "";
      });
    },

    columnDefs: [
      { targets: 0, visible: false },
      { targets: 1, visible: false },
    ],

    destroy: true,
  });

  /* ===== EVENTO SEDE ===== */

  $("#tableUbicaciones tbody").on("click", "tr.group-sede", function () {
    let sede = $(this).data("name");
    let abrir = collapsedSedes[sede]; // saber si se va a abrir

    /* CERRAR TODAS LAS SEDES */
    Object.keys(collapsedSedes).forEach(function (key) {
      collapsedSedes[key] = true;
    });

    /* CERRAR TODAS LAS ZONAS */
    Object.keys(collapsedZonas).forEach(function (key) {
      collapsedZonas[key] = true;
    });

    /* SI ESTABA CERRADA, ABRIR SOLO ESTA */
    if (abrir) {
      collapsedSedes[sede] = false;
    }

    table.draw(false);
  });

  /* ===== EVENTO ZONA ===== */

  $("#tableUbicaciones tbody").on("click", "tr.group-zona", function () {
    let sede = $(this).data("sede");
    let zona = $(this).data("name");

    let key = sede + "_" + zona;

    collapsedZonas[key] = !collapsedZonas[key];

    table.draw(false);
  });
});
function loadSedes() {
  fetch(base_url + "/Inv_sedes/getSelectSedes")
    .then((res) => res.text())
    .then((html) => {
      document.getElementById("sedeid").innerHTML = html;
    });
}

document.getElementById("sedeid").addEventListener("change", function () {
  let idSede = this.value;

  if (idSede == "") {
    document.getElementById("zonaid").disabled = true;
    return;
  }

  loadZonas(idSede);
});

function loadZonas(idSede) {
  let selectZona = document.getElementById("zonaid");

  selectZona.innerHTML = '<option value="">Cargando...</option>';
  selectZona.disabled = true;

  fetch(base_url + "/Inv_zonas/getSelectZonas/" + idSede)
    .then((res) => res.text())
    .then((html) => {
      selectZona.innerHTML = html;
      selectZona.disabled = false;
    })
    .catch((error) => {
      console.error("Error cargando zonas:", error);
      selectZona.disabled = true;
    });
}

document
  .getElementById("formUbicacion")
  .addEventListener("submit", function (e) {
    e.preventDefault();

    let formData = new FormData(this);

    let codigoBase = document.querySelector("[name='codigo_base']").value;

    let regex = /^[A-Z]+[0-9]+$/;

    if (!regex.test(codigoBase)) {
      Swal.fire({
        icon: "warning",
        title: "Código inválido",
        text: "El código debe tener formato LETRAS + NÚMEROS. Ejemplo: B01",
      });

      return;
    }

    fetch(base_url + "/Inv_ubicaciones/store", {
      method: "POST",
      body: formData,
    })
      .then((res) => res.json())
      .then((data) => {
        console.log("Respuesta API:", data);

        if (data.status === "success") {
          let insertadas = data.data.insertadas || [];
          let duplicadas = data.data.duplicadas || [];

          let mensaje = "";

          if (insertadas.length > 0) {
            mensaje +=
              "<b>Ubicaciones insertadas:</b><br>" +
              insertadas.join(", ") +
              "<br><br>";
          }

          if (duplicadas.length > 0) {
            mensaje +=
              "<b>Duplicadas (no se insertaron):</b><br>" +
              duplicadas.join(", ");
          }

          Swal.fire({
            icon: duplicadas.length > 0 ? "warning" : "success",
            title: "Resultado del registro",
            html: mensaje,
            confirmButtonText: "Aceptar",
          });

          toggleForm();
          table.ajax.reload();
        } else {
          Swal.fire({
            icon: "error",
            title: "Error",
            text: data.message,
          });
        }
      })
      .catch((error) => {
        console.error(error);

        Swal.fire({
          icon: "error",
          title: "Error",
          text: "Error en la petición",
        });
      });
  });

function toggleForm() {
  const form = document.getElementById("formUbicacionContainer");

  if (form.style.display === "none") {
    form.style.display = "block";
  } else {
    form.style.display = "none";
    document.getElementById("formUbicacion").reset();
    document.getElementById("zonaid").disabled = true;
  }
}

/* =====================================
   CONVERTIR INPUTS A MAYÚSCULAS
===================================== */

document.addEventListener("input", function (e) {
  if (e.target.matches("input[type='text'], textarea")) {
    e.target.value = e.target.value.toUpperCase();
  }
});

/* =====================================
   BLOQUEAR NUMEROS NEGATIVOS
===================================== */

document.addEventListener("input", function (e) {
  if (e.target.type === "number") {
    if (e.target.value < 0) {
      e.target.value = 0;
    }
  }
});

/* =====================================
VALIDACION CODIGO BASE WMS
===================================== */

document.addEventListener("input", function (e) {
  if (e.target.name === "codigo_base") {
    let valor = e.target.value.toUpperCase();

    // eliminar caracteres no permitidos
    valor = valor.replace(/[^A-Z0-9]/g, "");

    // separar letras y numeros
    let letras = valor.match(/^[A-Z]+/)?.[0] || "";
    let numeros = valor.match(/[0-9]+$/)?.[0] || "";

    // limitar letras a 3 (opcional)
    letras = letras.substring(0, 3);

    // limitar numeros a 3
    numeros = numeros.substring(0, 3);

    e.target.value = letras + numeros;
  }
});
