let tableSeries;
let ordenesCache = [];
let collapsedGroups = {};

document.addEventListener("DOMContentLoaded", function () {
  // 🔹 FORZAR MAYÚSCULAS EN PREFIJO
  const vinInput = document.querySelector("input[name='prefijo']");
  const vinCounter = document.getElementById("vinCounter");

  vinInput.addEventListener("input", function () {
    // 🔹 Convertir a mayúsculas
    let value = this.value.toUpperCase();

    // 🔒 Eliminar letras prohibidas
    value = value.replace(/[IOQÑ]/g, "");

    // 🔒 Permitir solo alfanumérico
    value = value.replace(/[^A-Z0-9]/g, "");

    // 🔒 Limitar a 17 caracteres
    value = value.substring(0, 17);

    this.value = value;

    let length = value.length;

    vinCounter.textContent = `${length} / 17 caracteres`;

    vinCounter.classList.remove(
      "text-muted",
      "text-danger",
      "text-warning",
      "text-success",
    );

    vinInput.classList.remove("is-invalid", "is-valid");

    if (length < 11) {
      vinCounter.classList.add("text-danger");
      vinInput.classList.add("is-invalid");
    } else if (length < 17) {
      vinCounter.classList.add("text-warning");
    } else if (length === 17) {
      vinCounter.classList.add("text-success");
      vinInput.classList.add("is-valid");
    }
  });

  // 🔹 DATATABLE
  let collapsedGroups = {};

  tableSeries = $("#tableSeries").DataTable({
    ajax: {
      url: base_url + "/Inv_series/getSeries",
      dataSrc: "",
    },

    paging: false, //  QUITA PAGINACIÓN
    info: false, // opcional (quita "Showing 1 to X")
    lengthChange: false, // opcional (quita selector 10,25,50)

    columns: [
      { data: "producto" },
      { data: "almacen" },
      { data: "numero_serie" },
      { data: "referencia" },
      { data: "fecha" },
      { data: "estado" },
      {
        data: null,
        render: function (data) {
          return `
          <a href="${base_url}/Inv_series/generarCodigoPDF/${data.numero_serie}" 
             target="_blank"
             class="btn btn-sm btn-dark">
             Código
          </a>
        `;
        },
      },
    ],

    order: [[4, "desc"]],

    rowGroup: {
      dataSrc: "referencia",
      startRender: function (rows, group) {
        if (collapsedGroups[group] === undefined) {
          collapsedGroups[group] = true;
        }

        let collapsed = !!collapsedGroups[group];

        rows.nodes().each(function (r) {
          r.style.display = collapsed ? "none" : "";
        });

        return $("<tr/>")
          .addClass("group-header")
          .attr("data-name", group)
          .append(
            `
    <td colspan="7" class="fw-bold" style="cursor:pointer;">
            ${collapsed ? "▶" : "▼"} 
            ORDEN DE TRABAJO: ${group}
            <span class="badge bg-secondary ms-2">
              ${rows.count()} Series
            </span>
          </td>
        `,
          )
          .toggleClass("collapsed", collapsed);
      },
    },

    columnDefs: [{ targets: 3, visible: false }],

    bDestroy: true,
  });

  $("#tableSeries tbody").on("click", "tr.group-header", function () {
    let name = $(this).data("name");
    collapsedGroups[name] = !collapsedGroups[name];
    tableSeries.draw(false);
  });

  // 🔹 CARGAR ORDENES EN CACHE
  fetch(base_url + "/Inv_series/getOrdenesTrabajo")
    .then((res) => res.json())
    .then((data) => {
      ordenesCache = data;
    });

  // 🔹 PREVENIR SUBMIT NORMAL
  document
    .querySelector("#formSeries")
    .addEventListener("submit", function (e) {
      e.preventDefault();
    });

  // 🔹 AUTOCOMPLETE ORDEN DE TRABAJO
  document.addEventListener("input", function (e) {
    if (!e.target.classList.contains("ordenSearch")) return;

    let input = e.target;
    let val = input.value.toLowerCase();

    cerrarListaOrden();

    // limpiar campos ocultos si escriben
    document.querySelector("#inventarioid").value = "";
    document.querySelector("#referencia").value = "";
    document.querySelector("#productoNombre").value = "";

    if (!val) return;

    let lista = document.createElement("div");
    lista.className = "autocomplete-items list-group position-absolute w-100";
    input.parentNode.appendChild(lista);

    ordenesCache
      .filter((o) => o.num_orden.toLowerCase().includes(val))
      .slice(0, 10)
      .forEach((o) => {
        let item = document.createElement("div");
        item.className = "list-group-item list-group-item-action";
        item.innerHTML = `<strong>${o.num_orden}</strong> - ${o.producto}`;

        item.addEventListener("click", function () {
          document.querySelector("#ordenSearch").value = o.num_orden;
          document.querySelector("#referencia").value = o.num_orden;
          document.querySelector("#inventarioid").value = o.idinventario;
          document.querySelector("#productoNombre").value = o.producto;

          cerrarListaOrden();
        });

        lista.appendChild(item);
      });
  });

  function cerrarListaOrden() {
    document
      .querySelectorAll(".autocomplete-items")
      .forEach((el) => el.remove());
  }

  document.addEventListener("click", function (e) {
    if (!e.target.classList.contains("ordenSearch")) {
      cerrarListaOrden();
    }
  });

  // 🔹 CARGAR ALMACENES
  fetch(base_url + "/Inv_series/getAlmacenes")
    .then((res) => res.json())
    .then((data) => {
      const selectAlmacen = document.querySelector("#almacenid");

      selectAlmacen.innerHTML = '<option value="">Seleccione almacén</option>';

      data.forEach((a) => {
        let option = document.createElement("option");
        option.value = a.idalmacen;
        option.textContent = `${a.cve_almacen} - ${a.descripcion}`;
        selectAlmacen.appendChild(option);
      });
    });

  // 🔹 PREVIEW VIN
  document.querySelector("#btnPreview").addEventListener("click", function () {
    let inventarioid = document.querySelector("#inventarioid").value;

    if (!inventarioid) {
      Swal.fire(
        "Error",
        "Debe seleccionar una orden de trabajo válida",
        "error",
      );
      return;
    }

    let baseVin = document
      .querySelector("input[name='prefijo']")
      .value.trim()
      .toUpperCase();

    let cantidad = parseInt(
      document.querySelector("input[name='cantidad']").value,
    );

    if (!baseVin || !cantidad) {
      Swal.fire("Error", "Prefijo y cantidad son obligatorios", "error");
      return;
    }

    // mínimo 11 obligatorios
    if (baseVin.length < 11) {
      return;
    }

    // máximo 17
    if (baseVin.length > 17) {
      Swal.fire("Error", "El VIN no puede exceder 17 caracteres", "error");
      return;
    }

    //  caracteres inválidos
    if (/[IOQÑ]/.test(baseVin)) {
      Swal.fire(
        "Error",
        "El VIN no puede contener las letras I, O, Ñ o Q",
        "error",
      );
      return;
    }

    let parteFija = baseVin;
    let contador = 1;
    let longitudNumerica;

    if (baseVin.length === 17) {
      let match = baseVin.match(/(\d+)$/);

      if (match) {
        let numeroBase = match[1];
        longitudNumerica = numeroBase.length;
        parteFija = baseVin.slice(0, -longitudNumerica);
        contador = parseInt(numeroBase);
      } else {
        Swal.fire("Error", "El VIN completo debe terminar en números", "error");
        return;
      }
    } else {
      longitudNumerica = 17 - baseVin.length;
    }

    let container = document.querySelector("#previewContainer");
    container.innerHTML = "";

    for (let i = 0; i < cantidad; i++) {
      let nuevoNumero = String(contador + i).padStart(longitudNumerica, "0");

      let vinFinal = parteFija + nuevoNumero;

      if (vinFinal.length !== 17) {
        Swal.fire(
          "Error",
          "Error interno: el VIN generado no tiene 17 caracteres",
          "error",
        );
        return;
      }

      let div = document.createElement("div");
      div.className = "col-md-3 mb-2";
      div.innerHTML = `
        <div class="border p-2 text-center bg-light">
            ${vinFinal}
        </div>
      `;

      container.appendChild(div);
    }

    let modal = new bootstrap.Modal(
      document.getElementById("modalPreviewSeries"),
    );
    modal.show();
  });

  // 🔹 CONFIRMAR INSERT
  document.addEventListener("click", function (e) {
    if (e.target && e.target.id === "btnConfirmSeries") {
      let form = document.querySelector("#formSeries");
      let formData = new FormData(form);

      fetch(base_url + "/Inv_series/validarSeries", {
        method: "POST",
        body: formData,
      })
        .then((res) => res.json())
        .then((obj) => {
          if (!obj.status) {
            Swal.fire("Error", obj.msg, "error");
            return;
          }

          let repetidos = obj.repetidos;
          let disponibles = obj.disponibles;

          if (repetidos.length === 0) {
            insertarSeries(disponibles);
          } else if (disponibles.length === 0) {
            Swal.fire({
              icon: "error",
              title: "Todos los VIN están ocupados",
              html: repetidos.join("<br>"),
            });
          } else {
            Swal.fire({
              icon: "warning",
              title: "VIN repetidos detectados",
              html: `
                <b>Repetidos:</b><br>${repetidos.join("<br>")}
                <br><br>
                <b>Disponibles:</b> ${disponibles.length}
              `,
              showCancelButton: true,
              confirmButtonText: "Insertar disponibles",
              cancelButtonText: "Cancelar",
            }).then((result) => {
              if (result.isConfirmed) {
                insertarSeries(disponibles);
              }
            });
          }
        });
    }
  });
});

// 🔹 INSERTAR SERIES
function insertarSeries(lista) {
  fetch(base_url + "/Inv_series/setSeriesConfirmadas", {
    method: "POST",
    headers: {
      "Content-Type": "application/json",
    },
    body: JSON.stringify({
      lista: lista,
      inventarioid: document.querySelector("#inventarioid").value,
      almacenid: document.querySelector("#almacenid").value,
      referencia: document.querySelector("#referencia").value,
      costo: document.querySelector("input[name='costo']")
        ? document.querySelector("input[name='costo']").value
        : 0,
    }),
  })
    .then((res) => res.json())
    .then((obj) => {
      if (obj.status) {
        Swal.fire("Correcto", obj.msg, "success");
        tableSeries.ajax.reload();

        document.querySelector("#formSeries").reset();
        document.querySelector("#inventarioid").value = "";
        document.querySelector("#referencia").value = "";
        document.querySelector("#productoNombre").value = "";

        bootstrap.Modal.getInstance(
          document.getElementById("modalPreviewSeries"),
        ).hide();
      } else {
        Swal.fire("Error", obj.msg, "error");
      }
    });
}
