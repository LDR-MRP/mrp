let tableTraslados;

document.addEventListener("DOMContentLoaded", function () {
  cargarKpisTraslados();

  tableTraslados = $("#tableTraslados").DataTable({
    processing: true,
    destroy: true,

    responsive: false,
    scrollX: true,
    autoWidth: false,

    ajax: {
      url: base_url + "/Inv_traslados/getTraslados",
      type: "GET",
      dataSrc: "",
    },

    columns: [
      { data: "folio" },
      { data: "almacen_origen" },
      { data: "almacen_destino" },

      {
        data: "tipo_traslado",
        render: function (data) {
          return `
                    <span class="badge bg-info">
                        ${data}
                    </span>`;
        },
      },

      { data: "proveedor" },

      {
        data: "total_unidades",
        render: function (data) {
          return `
                    <span class="badge bg-secondary">
                        ${data} unidades
                    </span>`;
        },
      },

      { data: "fecha_programada" },

      {
        data: "estado",
        render: function (data) {
          let badge = {
            1: ["warning", "Solicitud"],
            2: ["primary", "Tránsito"],
            3: ["info", "En tránsito"],
            4: ["success", "Recibido"],
            5: ["danger", "Cancelado"],
          };

          let estado = badge[data] || ["secondary", "N/D"];

          return `
                    <span class="badge bg-${estado[0]}">
                        ${estado[1]}
                    </span>`;
        },
      },

      {
        data: null,
        render: function (row) {
          // Editar solo tiene sentido mientras el traslado sigue en
          // "Solicitud" (estado 1): una vez confirmada la salida ya no
          // se puede modificar, así que el botón ni siquiera se muestra.
          const btnEditar =
            parseInt(row.estado) === 1
              ? `
            <button
                class="btn btn-warning btn-sm"
                onclick="editarTraslado(${row.idtraslado})"
                title="Editar traslado">

                <i class="ri-edit-line"></i>

            </button>
          `
              : "";

          return `

        <div class="d-flex gap-1">

            <button
                class="btn btn-info btn-sm"
                onclick="verDetalle(${row.idtraslado})"
                title="Ver detalle">

                <i class="ri-eye-line"></i>

            </button>

            ${btnEditar}

            <button
    class="btn btn-success btn-sm btnPdfTraslado"
    data-traslado="${row.idtraslado}"
    title="Descargar PDF con QR">
    <i class="ri-printer-line"></i>
</button>

        </div>

        `;
        },
      },
    ],

    order: [[0, "desc"]],
  });

  // Cada vez que la tabla vuelve a traer datos (recarga automática o
  // manual), refrescamos también las tarjetas de KPIs para que no se
  // queden desactualizadas frente al listado.
  tableTraslados.on("xhr.dt", function () {
    cargarKpisTraslados();
  });
});

/* =====================================================
   KPIs (tarjetas informativas)
===================================================== */

function cargarKpisTraslados() {
  fetch(base_url + "/Inv_traslados/getKpis")
    .then((res) => res.json())
    .then((data) => {
      const pendientes = document.querySelector("#kpiPendientes");
      const transito = document.querySelector("#kpiTransito");
      const recibidas = document.querySelector("#kpiRecibidas");
      const canceladas = document.querySelector("#kpiCanceladas");

      if (pendientes) pendientes.innerHTML = data.pendientes || 0;
      if (transito) transito.innerHTML = data.transito || 0;
      if (recibidas) recibidas.innerHTML = data.recibidas || 0;
      if (canceladas) canceladas.innerHTML = data.canceladas || 0;
    })
    .catch(() => {
      // Si falla, dejamos los valores que ya estaban en pantalla
      // en lugar de romper el resto de la vista.
      console.error("No se pudieron cargar los KPIs de traslados");
    });
}

/* =====================================================
   DETALLE
===================================================== */

function verDetalle(id) {
  fetch(base_url + "/Inv_traslados/getDetalle/" + id)
    .then((res) => res.json())

    .then((data) => {
      let tbody = document.querySelector("#tbodyDetalle");

      tbody.innerHTML = "";

      data.forEach((row) => {
        tbody.innerHTML += `

<tr>

<td>${row.vin}</td>

<td>${row.modelo}</td>


</tr>

`;
      });

      let modal = new bootstrap.Modal(
        document.getElementById("modalDetalleTraslado"),
      );

      modal.show();
    });
}

/* =====================================================
   EDITAR
===================================================== */

function editarTraslado(id) {
  window.location.href = base_url + "/Inv_traslados/editar_traslado/" + id;
}

/* =====================================================
   IMPRIMIR
===================================================== */
function imprimirTraslado(idTraslado) {
  let ventana = window.open(
    base_url + "/Inv_traslados/imprimirTraslado/" + idTraslado,
    "_blank",
  );
}

/* =====================================================
   PDF DE TRASLADO CON QR
===================================================== */

document.addEventListener("click", async function (e) {
  const btn = e.target.closest(".btnPdfTraslado");

  if (!btn) return;

  e.preventDefault();

  const idTraslado = btn.dataset.traslado;

  if (!idTraslado) {
    alert("No se encontró el traslado.");
    return;
  }

  try {
    const response = await fetch(
      base_url + "/Inv_traslados/getTrasladoPdf/" + idTraslado,
      {
        method: "GET",
        headers: { Accept: "application/json" },
      },
    );

    const result = await response.json();

    if (!result.status) {
      alert(result.msg || "No se pudo generar el PDF.");
      return;
    }

    generarPdfTraslado(
      result.data.traslado,
      result.data.detalle,
      result.data.trasladista,
      result.url_qr,
    );
  } catch (error) {
    console.error(error);
    alert("Error al generar el PDF del traslado.");
  }
});

function generarPdfTraslado(traslado, detalle, trasladista, urlQr) {
  const folio = traslado.folio || "N/A";
  const origen = traslado.almacen_origen || "N/A";
  const destino = traslado.almacen_destino || "N/A";
  const tipoTraslado = (traslado.tipo_traslado || "N/A")
    .toString()
    .toUpperCase();
  const proveedor = traslado.proveedor || "Sin asignar";
  const fechaProgramada = traslado.fecha_programada || "N/A";

  const estatusMap = {
    1: "SOLICITADO",
    2: "SALIDA",
    3: "EN TRÁNSITO",
    4: "RECIBIDO",
    5: "CANCELADO",
  };

  const estadoTexto = estatusMap[parseInt(traslado.estado)] || "N/D";

  const nombreTrasladista = (trasladista && trasladista.nombre) || "N/A";
  const contactoTrasladista = (trasladista && trasladista.contacto) || "N/A";
  const licencia = (trasladista && trasladista.numero_licencia) || "N/A";
  const vigenciaLicencia =
    (trasladista && trasladista.vigencia_licencia) || "N/A";

  const fechaImpresion = new Date().toLocaleString("es-MX", {
    timeZone: "America/Mexico_City",
    year: "numeric",
    month: "2-digit",
    day: "2-digit",
    hour: "2-digit",
    minute: "2-digit",
    second: "2-digit",
  });

  // tabla de unidades (dinámica, compacta)
  const bodyUnidades = [
    [
      { text: "#", bold: true, fillColor: "#F3F3F3", fontSize: 8 },
      { text: "VIN", bold: true, fillColor: "#F3F3F3", fontSize: 8 },
      { text: "MODELO", bold: true, fillColor: "#F3F3F3", fontSize: 8 },
    ],
  ];

  (detalle || []).forEach((row, idx) => {
    bodyUnidades.push([
      { text: String(idx + 1), fontSize: 8, alignment: "center" },
      { text: row.vin || "", fontSize: 8 },
      { text: row.modelo || "", fontSize: 8 },
    ]);
  });

  if (!detalle || detalle.length === 0) {
    bodyUnidades.push([
      {
        text: "Sin unidades registradas",
        colSpan: 3,
        alignment: "center",
        italics: true,
        color: "#777777",
        fontSize: 8,
      },
      {},
      {},
    ]);
  }

  const docDefinition = {
    pageSize: "LETTER",
    pageMargins: [26, 16, 26, 16],

    content: [
      {
        canvas: [
          {
            type: "rect",
            x: 0,
            y: 0,
            w: 559,
            h: 763,
            r: 8,
            lineWidth: 1.2,
            lineColor: "#999999",
          },
        ],
        absolutePosition: { x: 18, y: 14 },
      },

      logoLdr(),

      {
        text: "REPORTE DE TRASLADO DE UNIDADES",
        alignment: "center",
        fontSize: 13,
        bold: true,
        color: "#222222",
        margin: [0, 2, 0, 2],
      },

      sectionTitle("INFORMACIÓN GENERAL DEL TRASLADO"),

      {
        table: {
          widths: ["40%", "60%"],
          body: [
            [label("FOLIO"), valueBold(folio)],
            [label("ALMACÉN ORIGEN"), valueBold(origen)],
            [label("ALMACÉN DESTINO"), valueBold(destino)],
            [label("TIPO DE TRASLADO"), valueBold(tipoTraslado)],
            [label("PROVEEDOR / TRANSPORTISTA"), value(proveedor)],
            [label("FECHA PROGRAMADA"), value(fechaProgramada)],
            [
              labelBold("ESTADO ACTUAL"),
              {
                text: estadoTexto,
                bold: true,
                fontSize: 10,
                color: "#111111",
                margin: [0, 2, 0, 2],
              },
            ],
          ],
        },
        layout: tableLayout(),
        margin: [30, 0, 30, 8],
      },

      sectionTitle("DATOS DEL TRASLADISTA"),

      {
        table: {
          widths: ["40%", "60%"],
          body: [
            [label("NOMBRE"), value(nombreTrasladista)],
            [label("CONTACTO"), value(contactoTrasladista)],
            [label("NÚMERO DE LICENCIA"), value(licencia)],
            [label("VIGENCIA DE LICENCIA"), value(vigenciaLicencia)],
          ],
        },
        layout: tableLayout(),
        margin: [30, 0, 30, 8],
      },

      sectionTitle("UNIDADES INCLUIDAS EN EL TRASLADO"),

      {
        table: {
          widths: ["10%", "45%", "45%"],
          body: bodyUnidades,
        },
        layout: tableLayout(),
        margin: [30, 0, 30, 8],
      },

      sectionTitle("QR DE TRAZABILIDAD"),

      {
        qr: urlQr,
        fit: 90,
        alignment: "center",
        margin: [0, 6, 0, 4],
      },

      {
        text: "Este documento fue generado automáticamente por el Sistema MRP y contiene la información registrada del traslado de unidades entre almacenes. Los datos aquí presentados corresponden al estado del traslado al momento de su emisión.",
        alignment: "center",
        fontSize: 7,
        italics: true,
        color: "#555555",
        margin: [40, 0, 40, 8],
      },

      {
        canvas: [
          {
            type: "line",
            x1: 20,
            y1: 0,
            x2: 525,
            y2: 0,
            lineWidth: 1,
            lineColor: "#AAAAAA",
          },
        ],
        margin: [0, 2, 0, 4],
      },

      {
        columns: [
          {
            width: "33%",
            text: "LDR Solutions México\nSistema MRP de Producción",
            fontSize: 7.5,
            color: "#444444",
          },
          {
            width: "33%",
            text: "Documento generado\nautomáticamente",
            fontSize: 7.5,
            color: "#444444",
            alignment: "center",
          },
          {
            width: "33%",
            text: "Fecha de impresión\n" + fechaImpresion + " hrs",
            fontSize: 7.5,
            color: "#444444",
            alignment: "right",
          },
        ],
        margin: [30, 0, 30, 0],
      },
    ],

    defaultStyle: {
      font: "Roboto",
    },
  };

  pdfMake.createPdf(docDefinition).download("Traslado_" + folio + ".pdf");
}


/* =====================================================
   HELPERS PARA PDF (logo, secciones, tablas) - COMPACTOS
===================================================== */

function logoLdr() {
  if (typeof LOGO_LDR_BASE64 !== "undefined" && LOGO_LDR_BASE64) {
    return {
      image: LOGO_LDR_BASE64,
      width: 65,
      alignment: "center",
      margin: [0, 3, 0, 4],
    };
  }

  return {
    stack: [
      {
        text: "LDR",
        alignment: "center",
        fontSize: 24,
        bold: true,
        color: "#555555",
        margin: [0, 3, 0, 0],
      },
      {
        text: "SOLUTIONS",
        alignment: "center",
        fontSize: 8,
        characterSpacing: 3,
        color: "#666666",
        margin: [0, -3, 0, 4],
      },
    ],
  };
}

function sectionTitle(text) {
  return {
    table: {
      widths: ["100%"],
      body: [
        [
          {
            text: text,
            bold: true,
            fontSize: 11,
            color: "#222222",
            fillColor: "#F3F3F3",
            margin: [10, 4, 10, 4],
            border: [true, true, true, true],
          },
        ],
      ],
    },
    layout: {
      hLineColor: () => "#AAAAAA",
      vLineColor: () => "#AAAAAA",
      hLineWidth: () => 0.8,
      vLineWidth: () => 0.8,
    },
    margin: [30, 0, 30, 0],
  };
}

function label(text) {
  return {
    text: text,
    fontSize: 10,
    color: "#333333",
    margin: [0, 1, 0, 1],
  };
}

function labelBold(text) {
  return {
    text: text,
    bold: true,
    fontSize: 9,
    color: "#222222",
    margin: [0, 1, 0, 1],
  };
}

function value(text) {
  return {
    text: text || "",
    fontSize: 9,
    color: "#111111",
    margin: [0, 1, 0, 1],
  };
}

function valueBold(text) {
  return {
    text: text || "",
    bold: true,
    fontSize: 9,
    color: "#111111",
    margin: [0, 1, 0, 1],
  };
}

function tableLayout() {
  return {
    hLineColor: () => "#CCCCCC",
    vLineColor: () => "#DDDDDD",
    hLineWidth: () => 0.5,
    vLineWidth: () => 0.5,
    paddingLeft: () => 8,
    paddingRight: () => 8,
    paddingTop: () => 1.5,
    paddingBottom: () => 1.5,
  };
}