let tableSeries;
let productosSerieCache = [];

document.addEventListener("DOMContentLoaded", function () {
  const hiddenInventario = document.querySelector("#inventarioid");

  // 🔹 DATATABLE
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
    { data: "costo" },
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

      <!--
      <a href="${base_url}/Inv_series/generarQrPDF/${data.numero_serie}" 
         target="_blank"
         class="btn btn-sm btn-primary">
         QR
      </a>
      -->
    `;
  }
}
  ],
  bDestroy: true,
  iDisplayLength: 10,
  order: [[5, "desc"]],
});

  

  // 🔹 CARGAR PRODUCTOS EN CACHE
  fetch(base_url + "/Inv_series/getProductos?term=")
    .then((res) => res.json())
    .then((data) => {
      productosSerieCache = data;
    });
    
    document.querySelector("#formSeries").addEventListener("submit", function(e){
    e.preventDefault();
});


  // 🔹 AUTOCOMPLETE ESTILO MOVIMIENTOS
  document.addEventListener("input", function (e) {
    if (!e.target.classList.contains("invSearchSerie")) return;

    const input = e.target;
    const hidden = document.querySelector("#inventarioid");

    let val = input.value.toLowerCase();
    cerrarListaSerie();
    hidden.value = ""; // 🔥 limpiar si escriben

    if (!val) return;

    let lista = document.createElement("div");
    lista.className = "autocomplete-items list-group position-absolute w-100";
    input.parentNode.appendChild(lista);

    productosSerieCache
      .filter(
        (p) =>
          (p.cve_articulo && p.cve_articulo.toLowerCase().includes(val)) ||
          (p.descripcion && p.descripcion.toLowerCase().includes(val)),
      )
      .slice(0, 10)
      .forEach((p) => {
        let item = document.createElement("div");
        item.className = "list-group-item list-group-item-action";
        item.innerHTML = `<strong>${p.cve_articulo}</strong> - ${p.descripcion}`;

        item.addEventListener("click", function () {
          if (p.serie === "N") {
            Swal.fire(
              "Producto sin control de serie",
              "Este artículo no permite números de serie. Debe editar el producto y activar control de serie.",
              "warning",
            );
            cerrarListaSerie();
            return;
          }

          input.value = `${p.cve_articulo} - ${p.descripcion}`;
          hidden.value = p.idinventario;
          cerrarListaSerie();
        });

        lista.appendChild(item);
      });
  });

  // 🔹 CERRAR LISTA
  function cerrarListaSerie() {
    document
      .querySelectorAll(".autocomplete-items")
      .forEach((el) => el.remove());
  }

  document.addEventListener("click", function (e) {
    if (!e.target.classList.contains("invSearchSerie")) {
      cerrarListaSerie();
    }
  });

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

  document.querySelector("#btnPreview").addEventListener("click", function () {
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

    if (baseVin.length > 17) {
      Swal.fire("Error", "El VIN no puede exceder 17 caracteres", "error");
      return;
    }

    let parteFija = baseVin;
    let contador = 1;
    let longitudNumerica;

    // 🔥 SOLO usar contador automático si ya tiene 17
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

          // 🔥 CASO 1: ninguno repetido
          if (repetidos.length === 0) {
            insertarSeries(disponibles);
          }
          // 🔥 CASO 2: todos repetidos
          else if (disponibles.length === 0) {
            Swal.fire({
              icon: "error",
              title: "Todos los VIN están ocupados",
              html: repetidos.join("<br>"),
            });
          }
          // 🔥 CASO 3: algunos repetidos
          else {
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
      referencia: document.querySelector("input[name='referencia']").value,
      costo: document.querySelector("input[name='costo']").value,
    }),
  })
    .then((res) => res.json())
    .then((obj) => {
      if (obj.status) {
        Swal.fire("Correcto", obj.msg, "success");
        tableSeries.ajax.reload();
        document.querySelector("#formSeries").reset();
        document.querySelector("#inventarioid").value = "";
        bootstrap.Modal.getInstance(
          document.getElementById("modalPreviewSeries"),
        ).hide();
      } else {
        Swal.fire("Error", obj.msg, "error");
      }
    });
}
