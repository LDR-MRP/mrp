let tableLlaves;
let tableHistorialTraslados;

document.addEventListener("DOMContentLoaded", function () {
  cargarKpisLlaves();
  cargarUnidadesLlave();
  cargarResponsablesLlave();

  tableLlaves = $("#tableLlaves").DataTable({
    processing: true,
    destroy: true,

    responsive: false,
    scrollX: true,
    autoWidth: false,

    ajax: {
      url: base_url + "/Inv_llaves/getLlaves",
      type: "GET",
      dataSrc: "",
    },

    columns: [
      { data: "modelo" },
      { data: "vin" },
      {
        data: "tipo_llave",
        render: function (data) {
          const texto = data === "principal" ? "Principal" : "Duplicado";
          return `<span class="badge bg-secondary">${texto}</span>`;
        },
      },
      {
        data: "almacen",
        render: function (data) {
          return data || "—";
        },
      },
      { data: "responsable" },
      {
        data: "asigno",
        render: function (data) {
          return data && data.trim() ? data : "—";
        },
      },
      {
        data: "fecha_entrega",
        render: function (data) {
          return formatearFecha(data);
        },
      },
      {
        data: "fecha_prevista_devolucion",
        render: function (data) {
          return data ? formatearFechaSolo(data) : "—";
        },
      },
      {
        data: "fecha_devolucion",
        render: function (data) {
          return data ? formatearFecha(data) : "—";
        },
      },
      {
        data: "estatus",
        render: function (data) {
          const map = {
            prestada: ["primary", "Prestada"],
            vencida: ["danger", "Vencida"],
            devuelta: ["success", "Devuelta"],
          };
          const info = map[data] || ["secondary", "N/D"];
          return `<span class="badge bg-${info[0]}">${info[1]}</span>`;
        },
      },
      {
        data: null,
        render: function (row) {
          if (row.estatus === "devuelta") {
            return `<span class="text-muted small">Sin acciones</span>`;
          }

          return `
            <button
                class="btn btn-warning btn-sm btnRegistrarDevolucion"
                data-idmovimiento="${row.idmovimiento}"
                data-unidad="${(row.vin || "") + " - " + (row.modelo || "")}"
                data-responsable="${row.responsable || ""}"
                title="Registrar devolución">
                <i class="ri-arrow-go-back-line"></i>
                Devolución
            </button>
          `;
        },
      },
    ],

    order: [[0, "desc"]],
  });

  tableLlaves.on("xhr.dt", function (e, settings, json) {
    cargarKpisLlaves();

    const badge = document.querySelector("#badgeBitacora");
    if (badge) badge.textContent = (json || []).length;
  });

  tableHistorialTraslados = $("#tableHistorialTraslados").DataTable({
    processing: true,
    destroy: true,

    responsive: false,
    scrollX: true,
    autoWidth: false,

    ajax: {
      url: base_url + "/Inv_llaves/getHistorialTraslados",
      type: "GET",
      dataSrc: "",
    },

    columns: [
      { data: "vin" },
      { data: "modelo" },
      {
        data: "tipo_llave",
        render: function (data) {
          const texto = data === "principal" ? "Principal" : "Duplicado";
          return `<span class="badge bg-secondary">${texto}</span>`;
        },
      },
      {
        data: "almacen_origen",
        render: function (data) {
          return data || "—";
        },
      },
      {
        data: "almacen_destino",
        render: function (data) {
          return data || "—";
        },
      },
      {
        data: "fecha_salida",
        render: function (data) {
          return data ? formatearFecha(data) : "—";
        },
      },
      {
        data: "fecha_ultimo_movimiento",
        render: function (data) {
          return formatearFecha(data);
        },
      },
      {
        data: "responsable_ultimo",
        render: function (data) {
          return data && data.trim() !== "" ? data : "—";
        },
      },
      {
        data: "estatus",
        render: function (data) {
          const map = {
            en_transito: ["info", "En Tránsito"],
            recibida: ["success", "Recibida"],
            faltante: ["dark", "Faltante"],
          };
          const info = map[data] || ["secondary", "N/D"];
          return `<span class="badge bg-${info[0]}">${info[1]}</span>`;
        },
      },
    ],

    order: [[6, "desc"]],
  });

  tableHistorialTraslados.on("xhr.dt", function (e, settings, json) {
    const badge = document.querySelector("#badgeHistorial");
    if (badge) badge.textContent = (json || []).length;
  });

  // La pestaña de Historial arranca oculta (tab-pane no "active"), así
  // que su DataTable se inicializa con ancho 0. Al mostrarla por primera
  // vez hay que recalcular columnas para que no se vea desalineada.
  const tabHistorialBtn = document.querySelector("#tabHistorialBtn");
  if (tabHistorialBtn) {
    tabHistorialBtn.addEventListener("shown.bs.tab", function () {
      tableHistorialTraslados.columns.adjust().draw(false);
    });
  }
});

/* =====================================================
   KPIs
===================================================== */

function cargarKpisLlaves() {
  fetch(base_url + "/Inv_llaves/getKpisLlaves")
    .then((res) => res.json())
    .then((data) => {
      const total = document.querySelector("#lblTotalLlaves");
      const disponibles = document.querySelector("#lblDisponibles");
      const prestadas = document.querySelector("#lblPrestadas");
      const enTransito = document.querySelector("#lblEnTransito");
      const vencidas = document.querySelector("#lblVencidas");

      if (total) total.innerHTML = data.total || 0;
      if (disponibles) disponibles.innerHTML = data.disponibles || 0;
      if (prestadas) prestadas.innerHTML = data.prestadas || 0;
      if (enTransito) enTransito.innerHTML = data.en_transito_traslado || 0;
      if (vencidas) vencidas.innerHTML = data.vencidas || 0;
    })
    .catch(() => {
      console.error("No se pudieron cargar los KPIs de llaves");
    });
}

/* =====================================================
   SELECT DE UNIDADES (reutiliza el mismo catálogo que usa
   el módulo de traslados: unidades activas de inventario)
===================================================== */

let unidadesLlaveCache = [];

function cargarUnidadesLlave() {
  fetch(base_url + "/Inv_llaves/getUnidadesJson")
    .then((res) => res.json())
    .then((unidades) => {
      const select = document.querySelector("#id_unidad_llave");

      unidadesLlaveCache = unidades || [];

      if (!select) return;

      select.innerHTML = '<option value="">Seleccione una unidad...</option>';

      (unidades || []).forEach((u) => {
        const opt = document.createElement("option");
        opt.value = u.vinid;
        opt.textContent = `${u.vin} - ${u.unidad || ""} (${u.almacen || ""})`;
        opt.dataset.inventarioid = u.inventarioid;
        opt.dataset.almacenid = u.almacenid;
        opt.dataset.vin = u.vin || "";
        select.appendChild(opt);
      });
    })
    .catch(() => {
      console.error("No se pudieron cargar las unidades");
    });
}

/* =====================================================
   SELECT DE RESPONSABLES (colaboradores / usuarios activos)

   Se usa para los TRES selects de colaborador del módulo:
   - Responsable que RECIBE el préstamo (Nueva Entrega)
   - Quién PRESTA la llave (Nueva Entrega) - ya no se asume el
     usuario logeado, cualquier sesión puede estar capturando
   - Quién RECIBE la devolución (Devolución) - antes era un
     campo de texto libre, ahora también se elige de la lista
===================================================== */

let colaboradoresLlaveCache = [];

function llenarSelectColaborador(select, placeholder) {
  if (!select) return;

  select.innerHTML = `<option value="">${placeholder}</option>`;

  colaboradoresLlaveCache.forEach((u) => {
    const opt = document.createElement("option");
    opt.value = u.idusuario;
    opt.textContent = u.nombre_completo;
    select.appendChild(opt);
  });
}

function cargarResponsablesLlave() {
  fetch(base_url + "/Inv_llaves/getSelectResponsables")
    .then((res) => res.json())
    .then((usuarios) => {
      colaboradoresLlaveCache = usuarios || [];

      llenarSelectColaborador(
        document.querySelector("#id_responsable_llave"),
        "Seleccione responsable...",
      );

      llenarSelectColaborador(
        document.querySelector("#id_entrega_por_llave"),
        "Seleccione colaborador...",
      );

      llenarSelectColaborador(
        document.querySelector("#responsable_recibe"),
        "Seleccione colaborador...",
      );
    })
    .catch(() => {
      console.error("No se pudieron cargar los responsables");
    });
}

/* =====================================================
   ESCANEO DE GAFETE DEL COLABORADOR (lector de pistola)

   En su credencial traen su numcolaborador en código de barras.
   Al escanearlo en alguno de estos inputs se busca en el mismo
   catálogo de colaboradores (colaboradoresLlaveCache) y, si hay
   match, se selecciona automáticamente en el <select> correspon-
   diente - el select sigue siendo la fuente real del valor que se
   manda al guardar, así que seguir usándolo a mano sigue
   funcionando igual que antes.
===================================================== */

function buscarColaboradorPorCodigo(codigo) {
  const normalizado = (codigo || "").trim().toUpperCase();

  if (!normalizado) return null;

  return (
    colaboradoresLlaveCache.find(
      (u) => (u.numcolaborador || "").trim().toUpperCase() === normalizado,
    ) || null
  );
}

function marcarResultadoEscaneoGafete(input, encontrado) {
  input.classList.remove("is-valid", "is-invalid");
  input.classList.add(encontrado ? "is-valid" : "is-invalid");

  setTimeout(() => {
    input.classList.remove("is-valid", "is-invalid");
  }, 1500);
}

function configurarEscanerGafete(inputId, selectId, siguienteInputId) {
  const input = document.querySelector(`#${inputId}`);
  const select = document.querySelector(`#${selectId}`);

  if (!input || !select) return;

  input.addEventListener("keydown", function (e) {
    if (e.key !== "Enter" && e.key !== "Tab") return;

    e.preventDefault();

    const codigo = this.value;
    this.value = "";

    if (!codigo.trim()) return;

    const colaborador = buscarColaboradorPorCodigo(codigo);

    marcarResultadoEscaneoGafete(input, !!colaborador);

    if (!colaborador) {
      Swal.fire(
        "No encontrado",
        `Ningún colaborador activo tiene el código "${codigo.trim()}". Selecciónelo manualmente si es necesario.`,
        "warning",
      );
      return;
    }

    select.value = colaborador.idusuario;

    const siguiente = siguienteInputId
      ? document.querySelector(`#${siguienteInputId}`)
      : null;

    if (siguiente) siguiente.focus();
  });
}

configurarEscanerGafete(
  "scanResponsableLlave",
  "id_responsable_llave",
  "scanEntregaPorLlave",
);

configurarEscanerGafete("scanEntregaPorLlave", "id_entrega_por_llave", null);

// Al abrir Nueva Entrega -ya sea con el botón normal o encadenado desde
// el escaneo del VIN- se enfoca directo el campo de escaneo del
// colaborador, para poder seguir escaneando gafetes sin tocar el mouse.
// Si prefieren llenarlo a mano, los <select> siguen ahí sin cambios.
document
  .querySelector("#modalEntrega")
  ?.addEventListener("shown.bs.modal", function () {
    document.querySelector("#scanResponsableLlave")?.focus();
  });

// Por cada cierre de la modal (botón Cancelar, X, click fuera, Escape,
// o después de un envío exitoso) se limpia todo lo que haya quedado
// cargado -unidad seleccionada por VIN, colaboradores, fecha,
// observaciones y los estilos de validación de los campos de escaneo-
// para que la siguiente vez que se abra (manual o por otro escaneo de
// VIN) inicie completamente en blanco.
document
  .querySelector("#modalEntrega")
  ?.addEventListener("hidden.bs.modal", function () {
    document.querySelector("#formEntregaLlave")?.reset();
    document
      .querySelector("#scanResponsableLlave")
      ?.classList.remove("is-valid", "is-invalid");
    document
      .querySelector("#scanEntregaPorLlave")
      ?.classList.remove("is-valid", "is-invalid");
  });

/* =====================================================
   NUEVA ENTREGA (préstamo)
===================================================== */

let enviandoEntrega = false;

document
  .querySelector("#formEntregaLlave")
  .addEventListener("submit", function (e) {
    e.preventDefault();

    if (enviandoEntrega) return;

    const selectUnidad = document.querySelector("#id_unidad_llave");
    const selectResponsable = document.querySelector("#id_responsable_llave");
    const selectEntregaPor = document.querySelector("#id_entrega_por_llave");
    const tipoLlave = document.querySelector("#tipo_llave").value;
    const fechaDevolucion = document.querySelector("#fecha_devolucion").value;
    const observaciones = document.querySelector("#observaciones_llave").value;

    const vinid = selectUnidad.value;
    const opcionUnidad = selectUnidad.selectedOptions[0];

    const idResponsable = selectResponsable.value;
    const opcionResponsable = selectResponsable.selectedOptions[0];

    const idEntregaPor = selectEntregaPor.value;

    if (!vinid || !tipoLlave || !idResponsable || !idEntregaPor) {
      Swal.fire(
        "Datos incompletos",
        "Seleccione la unidad, el tipo de llave, el responsable y quién presta la llave",
        "warning",
      );
      return;
    }

    const payload = new URLSearchParams();
    payload.append("vinid", vinid);
    payload.append("inventarioid", opcionUnidad.dataset.inventarioid || "");
    payload.append("almacenid", opcionUnidad.dataset.almacenid || "");
    payload.append("tipo_llave", tipoLlave);
    payload.append("nombre_responsable", opcionResponsable.textContent.trim());
    payload.append("entregado_por", idEntregaPor);
    payload.append("fecha_devolucion", fechaDevolucion || "");
    payload.append("observaciones", observaciones || "");

    enviandoEntrega = true;
    const btnSubmit = document.querySelector(
      '#formEntregaLlave button[type="submit"]',
    );
    if (btnSubmit) btnSubmit.disabled = true;

    fetch(base_url + "/Inv_llaves/setPrestamoLlave", {
      method: "POST",
      headers: { "Content-Type": "application/x-www-form-urlencoded" },
      body: payload.toString(),
    })
      .then((res) => res.json())
      .then((resp) => {
        enviandoEntrega = false;
        if (btnSubmit) btnSubmit.disabled = false;

        if (!resp.status) {
          Swal.fire("Error", resp.msg, "error");
          return;
        }

        Swal.fire("Correcto", resp.msg, "success").then(() => {
          document.querySelector("#formEntregaLlave").reset();
          bootstrap.Modal.getInstance(
            document.querySelector("#modalEntrega"),
          )?.hide();
          tableLlaves.ajax.reload();
        });
      })
      .catch(() => {
        enviandoEntrega = false;
        if (btnSubmit) btnSubmit.disabled = false;
        Swal.fire("Error", "No se pudo registrar la entrega", "error");
      });
  });

/* =====================================================
   REGISTRAR DEVOLUCIÓN
===================================================== */

function abrirModalDevolucion({ idmovimiento, unidad, responsable }) {
  document.querySelector("#formDevolucionLlave").reset();

  document.querySelector("#idmovimiento_devolucion").value = idmovimiento;
  document.querySelector("#lblDevolucionUnidad").textContent = unidad || "";
  document.querySelector("#lblDevolucionResponsable").textContent =
    responsable || "";

  const modal = new bootstrap.Modal(
    document.querySelector("#modalDevolucion"),
  );
  modal.show();
}

document.addEventListener("click", function (e) {
  const btn = e.target.closest(".btnRegistrarDevolucion");

  if (!btn) return;

  abrirModalDevolucion({
    idmovimiento: btn.dataset.idmovimiento,
    unidad: btn.dataset.unidad,
    responsable: btn.dataset.responsable,
  });
});

let enviandoDevolucion = false;

document
  .querySelector("#formDevolucionLlave")
  .addEventListener("submit", function (e) {
    e.preventDefault();

    if (enviandoDevolucion) return;

    const idmovimiento = document.querySelector(
      "#idmovimiento_devolucion",
    ).value;
    const selectRecibe = document.querySelector("#responsable_recibe");
    const idResponsableRecibe = selectRecibe.value;
    const opcionResponsableRecibe = selectRecibe.selectedOptions[0];
    const observaciones = document.querySelector(
      "#observaciones_devolucion",
    ).value;

    if (!idmovimiento || !idResponsableRecibe) {
      Swal.fire(
        "Datos incompletos",
        "Indique quién recibe la llave",
        "warning",
      );
      return;
    }

    const payload = new URLSearchParams();
    payload.append("idmovimiento", idmovimiento);
    payload.append(
      "responsable_recibe",
      opcionResponsableRecibe.textContent.trim(),
    );
    payload.append("observaciones", observaciones || "");

    enviandoDevolucion = true;
    const btnSubmit = document.querySelector(
      '#formDevolucionLlave button[type="submit"]',
    );
    if (btnSubmit) btnSubmit.disabled = true;

    fetch(base_url + "/Inv_llaves/setDevolucionLlave", {
      method: "POST",
      headers: { "Content-Type": "application/x-www-form-urlencoded" },
      body: payload.toString(),
    })
      .then((res) => res.json())
      .then((resp) => {
        enviandoDevolucion = false;
        if (btnSubmit) btnSubmit.disabled = false;

        if (!resp.status) {
          Swal.fire("Error", resp.msg, "error");
          return;
        }

        Swal.fire("Correcto", resp.msg, "success").then(() => {
          document.querySelector("#formDevolucionLlave").reset();
          bootstrap.Modal.getInstance(
            document.querySelector("#modalDevolucion"),
          )?.hide();
          tableLlaves.ajax.reload();
        });
      })
      .catch(() => {
        enviandoDevolucion = false;
        if (btnSubmit) btnSubmit.disabled = false;
        Swal.fire("Error", "No se pudo registrar la devolución", "error");
      });
  });

/* =====================================================
   ESCÁNER DE LLAVE (lector de pistola / código de barras)

   El lector de pistola funciona como un teclado: al disparar
   sobre el código, "escribe" el texto donde esté el foco y
   termina con Enter. No hace falta cámara ni ninguna librería:
   solo un input enfocado esperando ese Enter.

   Un solo botón "Escanear Llave": según lo que se detecte para
   ese VIN, abre directo la Devolución (si tiene préstamo activo)
   o la Nueva Entrega con la unidad ya seleccionada (si está
   disponible). El código puede traer solo el VIN o una URL que
   termine en el VIN (ej. la que genera Inv_series/generarQRPDF).
===================================================== */

function extraerVinDeQR(textoEscaneado) {
  let texto = (textoEscaneado || "").trim();

  if (!texto) return "";

  // Si el código trae una URL (p.ej. ".../Inv_series/ver/ELVIN"), toma
  // el último segmento del path. Si trae solo el VIN, se usa tal cual.
  if (texto.includes("/")) {
    const partes = texto.split("/").filter(Boolean);
    texto = partes[partes.length - 1] || texto;
  }

  try {
    texto = decodeURIComponent(texto);
  } catch (e) {
    // No era un componente URI válido: se deja tal cual.
  }

  return texto.trim();
}

function abrirEscanerLlave() {
  const modalEl = document.querySelector("#modalScanner");
  const input = document.querySelector("#inputEscanerLlave");

  if (input) input.value = "";

  const modal = new bootstrap.Modal(modalEl);
  modal.show();

  modalEl.addEventListener(
    "shown.bs.modal",
    function () {
      input?.focus();
    },
    { once: true },
  );
}

// El lector envía el código y termina con Enter (a veces Tab, según
// cómo venga configurada la pistola). Se procesa en cualquiera de los
// dos casos.
document
  .querySelector("#inputEscanerLlave")
  ?.addEventListener("keydown", function (e) {
    if (e.key !== "Enter" && e.key !== "Tab") return;

    e.preventDefault();

    const valor = this.value;
    this.value = "";

    if (!valor.trim()) return;

    bootstrap.Modal.getInstance(document.querySelector("#modalScanner"))?.hide();

    procesarVinEscaneado(extraerVinDeQR(valor));
  });

// Si el campo pierde el foco mientras el modal sigue abierto (p.ej. un
// clic accidental), se regresa el foco para que la siguiente lectura
// no se pierda.
document
  .querySelector("#inputEscanerLlave")
  ?.addEventListener("blur", function () {
    const modalEl = document.querySelector("#modalScanner");
    const input = this;

    if (!modalEl || !modalEl.classList.contains("show")) return;

    setTimeout(function () {
      if (modalEl.classList.contains("show")) input.focus();
    }, 50);
  });

function procesarVinEscaneado(vinEscaneado) {
  if (!vinEscaneado) return;

  const vinNormalizado = vinEscaneado.trim().toUpperCase();

  const filas = tableLlaves ? tableLlaves.rows().data().toArray() : [];

  // 1) ¿Esta llave tiene un préstamo activo (prestada o vencida)? -> Devolución
  //    OJO: "origen" también puede ser "traslado" (en tránsito), y eso NO
  //    se gestiona aquí, por eso se filtra explícitamente por préstamo.
  const filaActiva = filas.find(
    (r) =>
      r.origen === "prestamo" &&
      (r.vin || "").trim().toUpperCase() === vinNormalizado &&
      r.estatus !== "devuelta",
  );

  if (filaActiva) {
    abrirModalDevolucion({
      idmovimiento: filaActiva.idmovimiento,
      unidad: (filaActiva.vin || "") + " - " + (filaActiva.modelo || ""),
      responsable: filaActiva.responsable || "",
    });
    return;
  }

  // 1.5) ¿Esta llave está en tránsito por un traslado? -> avisar, no se
  //      presta desde aquí (el sistema lo rechazaría de todos modos).
  const filaTraslado = filas.find(
    (r) =>
      r.origen === "traslado" &&
      (r.vin || "").trim().toUpperCase() === vinNormalizado,
  );

  if (filaTraslado) {
    Swal.fire(
      "Llave en tránsito",
      `Esta llave salió en un traslado y todavía no se recibe en destino. Consulte el traslado #${filaTraslado.referencia_traslado || "—"}.`,
      "info",
    );
    return;
  }

  // 2) ¿Es una unidad válida disponible? -> Nueva Entrega
  const unidad = unidadesLlaveCache.find(
    (u) => (u.vin || "").trim().toUpperCase() === vinNormalizado,
  );

  if (unidad) {
    const select = document.querySelector("#id_unidad_llave");
    if (select) select.value = unidad.vinid;

    const modalEntregaEl = document.querySelector("#modalEntrega");
    const modalEntrega = new bootstrap.Modal(modalEntregaEl);
    modalEntrega.show();

    // El foco al abrir vía escaneo de VIN se maneja con el listener
    // global de #modalEntrega (más abajo), que enfoca el campo de
    // escaneo del colaborador - así se puede seguir encadenando:
    // VIN -> colaborador -> quién presta.

    return;
  }

  // 3) No se encontró nada con ese VIN
  Swal.fire(
    "VIN no encontrado",
    `No se encontró ninguna unidad ni préstamo activo con el VIN "${vinEscaneado}".`,
    "warning",
  );
}

document
  .querySelector("#btnEscanearLlave")
  ?.addEventListener("click", abrirEscanerLlave);

/* =====================================================
   HELPERS DE FECHA
===================================================== */

function formatearFecha(data) {
  if (!data) return "—";
  const d = new Date(data.replace(" ", "T"));
  if (isNaN(d.getTime())) return data;
  return d.toLocaleString("es-MX", {
    timeZone: "America/Mexico_City",
    year: "numeric",
    month: "2-digit",
    day: "2-digit",
    hour: "2-digit",
    minute: "2-digit",
  });
}

function formatearFechaSolo(data) {
  if (!data) return "—";
  const d = new Date(data + "T00:00:00");
  if (isNaN(d.getTime())) return data;
  return d.toLocaleDateString("es-MX", {
    timeZone: "America/Mexico_City",
    year: "numeric",
    month: "2-digit",
    day: "2-digit",
  });
}
