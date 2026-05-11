let tableSeries;
let ordenesCache = [];
let productosCache = [];
let collapsedGroups = {};

document.addEventListener("DOMContentLoaded", function () {
  tableSeries = $("#tableSeries").DataTable({
    ajax: {
      url: base_url + "/Inv_series/getSeries",
      dataSrc: "",
    },
    columns: [
      { data: "producto" },
      { data: "almacen" },
      { data: "numero_serie" },
      { data: "referencia" },
      { data: "fecha" },
      { data: "estado" },
      {
        data: "numero_serie",
        render: function (data) {
          return `
          <a href="${base_url}/Inv_series/generarCodigoPDF/${data}" target="_blank" class="btn btn-sm btn-primary">
            Código
          </a>
          <a href="${base_url}/Inv_series/generarQRPDF/${data}" target="_blank" class="btn btn-sm btn-success">
            QR
          </a>
        `;
        },
      },
    ],
    responsive: true,
    bDestroy: true,
    paging: false,
    order: [[4, "desc"]],

    rowGroup: {
      dataSrc: "referencia",

      startRender: function (rows, group) {
        if (collapsedGroups[group] === undefined) {
          collapsedGroups[group] = true; // 👈 inicia colapsado
        }

        let collapsed = collapsedGroups[group];

        // 🔥 ocultar SOLO las filas de este grupo
        rows.nodes().each(function (r) {
          r.style.display = collapsed ? "none" : "";
        });

        return $(`
      <tr class="group-row" data-name="${group}" style="background:#e9ecef; cursor:pointer;">
        <td colspan="7">
          <strong>Referencia:</strong> ${group} 
          | <strong>Total:</strong> ${rows.count()}

          <button class="btn btn-sm btn-danger float-end btn-pdf-group" data-ref="${group}">
            Descargar PDF
          </button>
        </td>
      </tr>
    `);
      },
    },
  });

  $("#tableSeries tbody").on("click", "tr.group-row", function () {
    let group = $(this).data("name");

    // 🔥 alternar estado
    collapsedGroups[group] = !collapsedGroups[group];

    // 🔥 redibujar tabla SIN resetear paginación
    tableSeries.draw(false);
  });
  $("#tableSeries tbody").on("click", ".btn-pdf-group", function (e) {
    e.stopPropagation(); // 🔥 evita que colapse el grupo

    let ref = $(this).data("ref");

    window.open(base_url + "/Inv_series/generarOrdenPDF/" + ref, "_blank");
  });

  // ✅ AQUÍ VA (justo después de la tabla)
  cargarProductos("lote");

  // ================================
  // 🔹 CARGAR MODELOS VIN
  // ================================
  fetch(base_url + "/Inv_captura_vin/getModelosVin")
    .then((res) => res.json())
    .then((res) => {
      console.log(res.data);
      if (!res.success) return;

      const selectOrden = document.getElementById("modelo_vin");
      const selectLote = document.getElementById("modelo_vin_lote");

      res.data.forEach((m) => {
        let option1 = document.createElement("option");
        let option2 = document.createElement("option");

        // 🔥 NUEVA ESTRUCTURA
        let base = m.vin_base;

        // VALUE
        option1.value = option2.value = m.id_cat_modelo_vin;

        // DATASETS
        option1.dataset.base = option2.dataset.base = base;

        option1.dataset.anio = option2.dataset.anio = m.codigo_anio;

        option1.dataset.planta = option2.dataset.planta = m.caracter_planta;

        // TEXTO DEL SELECT
        option1.textContent = option2.textContent = `${m.modelo} (${m.anio})`;

        // AGREGAR AL SELECT
        selectOrden.appendChild(option1);
        selectLote.appendChild(option2);
      });
    });

  function cargarProductos(modo) {
    fetch(base_url + "/Inv_series/getProductos?modo=" + modo)
      .then((res) => res.json())
      .then((data) => {
        productosCache = data;
      });
  }

  document
    .getElementById("modelo_vin_lote")
    .addEventListener("change", function () {
      let opt = this.selectedOptions[0];
      if (!opt.value) return;

      let base = opt.dataset.base;
      let anio = opt.dataset.anio;
      let planta = opt.dataset.planta;

      let vinBase = base;

      document.getElementById("vinBasePreview_lote").value = vinBase;

      // 🔥 reutilizamos los mismos hidden
      document.getElementById("vin_parte_1_8").value = base;
      document.getElementById("vin_anio").value = anio;
      document.getElementById("vin_planta").value = planta;

      generarPreviewFinal();
    });

  // ================================
  // 🔹 CUANDO SELECCIONA MODELO VIN
  // ================================
  document.getElementById("modelo_vin").addEventListener("change", function () {
    let opt = this.selectedOptions[0];

    if (!opt.value) return;

    let base = opt.dataset.base;
    let anio = opt.dataset.anio;
    let planta = opt.dataset.planta;

    // VIN BASE (SIN DIGITO 9)
    let vinBase = base;

    document.getElementById("vinBasePreview").value = vinBase;

    // guardar ocultos
    document.getElementById("vin_parte_1_8").value = base;
    document.getElementById("vin_anio").value = anio;
    document.getElementById("vin_planta").value = planta;

    generarPreviewFinal();
  });

  // ================================
  // 🔹 FUNCIÓN CALCULAR DÍGITO VIN
  // ================================
  function calcularDigitoVIN(vin) {
    const transliteracion = {
      A: 1,
      B: 2,
      C: 3,
      D: 4,
      E: 5,
      F: 6,
      G: 7,
      H: 8,
      J: 1,
      K: 2,
      L: 3,
      M: 4,
      N: 5,
      P: 7,
      R: 9,
      S: 2,
      T: 3,
      U: 4,
      V: 5,
      W: 6,
      X: 7,
      Y: 8,
      Z: 9,
      0: 0,
      1: 1,
      2: 2,
      3: 3,
      4: 4,
      5: 5,
      6: 6,
      7: 7,
      8: 8,
      9: 9,
    };

    const pesos = [8, 7, 6, 5, 4, 3, 2, 10, 0, 9, 8, 7, 6, 5, 4, 3, 2];

    let suma = 0;

    for (let i = 0; i < 17; i++) {
      let char = vin[i];
      let valor = transliteracion[char] ?? 0;
      suma += valor * pesos[i];
    }

    let residuo = suma % 11;

    return residuo === 10 ? "X" : residuo.toString();
  }

  // ================================
  // 🔹 GENERAR PREVIEW FINAL
  // ================================
  function generarPreviewFinal() {
  let base = document.getElementById("vin_parte_1_8").value;

  if (!base) return;

  /*
    EJEMPLO BASE:
    3LD11C1L-VA
  */

  // agregar consecutivo temporal
  let vin = base + "000001";

  // asegurar longitud 17
  vin = vin.substring(0, 17);

  // calcular dígito usando el VIN con placeholder
  let digito = calcularDigitoVIN(vin);

  // reemplazar posición 9 (el guion)
  vin = vin.substring(0, 8) + digito + vin.substring(9);

  document.getElementById("vinPreviewFinal").textContent = vin;
}

  // ================================
  // 🔹 AUTOCOMPLETE ORDEN
  // ================================
  fetch(base_url + "/Inv_series/getOrdenesTrabajo")
    .then((res) => res.json())
    .then((data) => {
      ordenesCache = data;
    });

  document.addEventListener("input", function (e) {
    if (!e.target.classList.contains("ordenSearch")) return;

    let input = e.target;
    let val = input.value.toLowerCase();

    cerrarListaOrden();

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

          document.querySelector("#cantidadOrden").value = o.cantidad;
          document.querySelector("#cantidadPreview").value = o.cantidad;

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

  // ================================
  // 🔹 CARGAR ALMACENES
  // ================================
  fetch(base_url + "/Inv_series/getAlmacenes")
    .then((res) => res.json())
    .then((data) => {
      const selectOrden = document.querySelector("#almacenid");
      const selectLote = document.querySelector("#almacenid_lote");

      selectOrden.innerHTML = '<option value="">Seleccione almacén</option>';
      selectLote.innerHTML = '<option value="">Seleccione almacén</option>';

      data.forEach((a) => {
        let option1 = document.createElement("option");
        let option2 = document.createElement("option");

        option1.value = option2.value = a.idalmacen;
        option1.textContent =
          option2.textContent = `${a.cve_almacen} - ${a.descripcion}`;

        selectOrden.appendChild(option1);
        selectLote.appendChild(option2);
      });
    });

  // ================================
  // 🔹 PREVIEW SERIES
  // ================================
  document.addEventListener("click", function (e) {
    if (e.target && e.target.id === "btnPreview") {
      let base = document.getElementById("vin_parte_1_8").value;
      let anio = document.getElementById("vin_anio").value;
      let planta = document.getElementById("vin_planta").value;
      let modo = document.getElementById("modoGeneracion").value;

      let cantidad =
        modo === "orden"
          ? parseInt(document.getElementById("cantidadOrden").value)
          : parseInt(document.getElementById("cantidad_lote").value);

      if (!base || !anio || !planta) {
        Swal.fire("Error", "Seleccione un modelo VIN", "error");
        return;
      }

      if (!cantidad || cantidad <= 0) {
        Swal.fire("Error", "Cantidad inválida", "error");
        return;
      }

      let baseCompleta = base;

      // 🔥 CONSULTAR ÚLTIMO CONSECUTIVO REAL
      fetch(
        base_url + "/Inv_series/getUltimoConsecutivo?baseVin=" + baseCompleta,
      )
        .then((res) => res.json())
        .then((data) => {
          let ultimo = data.status ? data.ultimo : 0;

          let container = document.getElementById("previewContainer");
          container.innerHTML = "";

          for (let i = 0; i < cantidad; i++) {
            let consecutivo = String(ultimo + i + 1).padStart(6, "0");

            // base YA incluye VA
            // ejemplo: 3LD11C1LVA

            // base YA trae el placeholder "-" en posición 9
// ejemplo:
// 3LD11C1L-VA

let vinTemporal = base + consecutivo;

// asegurar 17 caracteres
vinTemporal = vinTemporal.substring(0, 17);

// calcular dígito usando placeholder
let digito = calcularDigitoVIN(vinTemporal);

// reemplazar el "-" por el dígito real
let vin =
  vinTemporal.substring(0, 8) +
  digito +
  vinTemporal.substring(9);

            let div = document.createElement("div");
            div.className = "col-md-3 mb-2";

            div.innerHTML = `
          <div class="border p-2 text-center bg-light">
            ${vin}
          </div>
        `;

            container.appendChild(div);
          }

          let modal = new bootstrap.Modal(
            document.getElementById("modalPreviewSeries"),
          );
          modal.show();
        })
        .catch(() => {
          Swal.fire("Error", "No se pudo obtener el consecutivo", "error");
        });
    }
  });

  document.addEventListener("click", function (e) {
    if (e.target && e.target.id === "btnConfirmSeries") {
      let modo = document.getElementById("modoGeneracion").value;

      let inventarioid =
        modo === "orden"
          ? document.getElementById("inventarioid").value
          : document.getElementById("inventarioid_lote").value;
      let almacenid =
        modo === "orden"
          ? document.getElementById("almacenid").value
          : document.getElementById("almacenid_lote").value;

      let referencia =
        modo === "orden"
          ? document.getElementById("referencia").value
          : document.getElementById("lote").value;

      let costo = 0;

      let lista = [];

      document.querySelectorAll("#previewContainer .col-md-3").forEach((el) => {
        lista.push(el.textContent.trim());
      });

      if (lista.length === 0) {
        Swal.fire("Error", "No hay VIN para guardar", "error");
        return;
      }

      fetch(base_url + "/Inv_series/setSeriesConfirmadas", {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
        },
        body: JSON.stringify({
          lista,
          inventarioid,
          almacenid,
          referencia,
          costo,
          modo,
        }),
      })
        .then((res) => res.json())
        .then((data) => {
          if (data.status) {
            Swal.fire("OK", data.msg, "success");

            // cerrar modal
            bootstrap.Modal.getInstance(
              document.getElementById("modalPreviewSeries"),
            ).hide();

            // limpiar preview
            document.getElementById("previewContainer").innerHTML = "";

            //  LIMPIAR FORMULARIO COMPLETO
            document.getElementById("formSeries").reset();

            //  LIMPIAR CAMPOS VISUALES
            document.getElementById("vinPreviewFinal").textContent =
              "-----------------";
            document.getElementById("vinBasePreview").value = "";
            document.getElementById("vinBasePreview_lote").value = "";
            document.getElementById("productoNombre").value = "";
            document.getElementById("ordenSearch").value = "";
            document.getElementById("productoSearchLote").value = "";

            tableSeries.ajax.reload(null, false);
          } else {
            Swal.fire("Error", data.msg, "error");
          }
        })
        .catch(() => {
          Swal.fire("Error", "Error en el servidor", "error");
        });
    }
  });

  document
    .getElementById("modoGeneracion")
    .addEventListener("change", function () {
      let modo = this.value;

      document.getElementById("vinPreviewFinal").textContent =
        "-----------------";

      document.getElementById("bloqueOrden").style.display =
        modo === "orden" ? "block" : "none";

      document.getElementById("bloqueLote").style.display =
        modo === "lote" ? "block" : "none";

      // 🔥 RECARGAR PRODUCTOS CORRECTOS
      cargarProductos(modo);
    });

  document.addEventListener("input", function (e) {
    if (!e.target.classList.contains("productoSearch")) return;

    let input = e.target;
    let val = input.value.toLowerCase();

    cerrarListaProductos();

    document.querySelector("#inventarioid_lote").value = "";

    if (!val) return;

    let lista = document.createElement("div");
    lista.className = "autocomplete-items list-group position-absolute w-100";
    input.parentNode.appendChild(lista);

    productosCache
      .filter(
        (p) =>
          p.descripcion.toLowerCase().includes(val) ||
          p.cve_articulo.toLowerCase().includes(val),
      )
      .slice(0, 10)
      .forEach((p) => {
        let item = document.createElement("div");
        item.className = "list-group-item list-group-item-action";

        item.innerHTML = `<strong>${p.cve_articulo}</strong> - ${p.descripcion}`;

        item.addEventListener("click", function () {
          document.querySelector("#productoSearchLote").value =
            `${p.cve_articulo} - ${p.descripcion}`;

          document.querySelector("#inventarioid_lote").value = p.idinventario;

          cerrarListaProductos();
        });

        lista.appendChild(item);
      });
  });
  function cerrarListaProductos() {
    document
      .querySelectorAll(".autocomplete-items")
      .forEach((el) => el.remove());
  }
  document.addEventListener("click", function (e) {
    if (!e.target.classList.contains("productoSearch")) {
      cerrarListaProductos();
    }
  });
});
