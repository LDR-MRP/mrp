let tableConfiguraciones;
let divLoading = null;
let formConfiguraciones = null;
let formEspecificaciones = null;
let formCertificaciones = null;
let idConfiguracionInput = null;
let segmentoSelect = null;
let sublineaSelect = null;
let primerTab = null;
let firstTab = null;
let tabNuevo = null;
let tabEspecificacionesEl = null;
let tabCertificacionesEl = null;
let tabHistorialEl = null;
let tabFichaTecnicaEl = null;
let progresoWrapperEl = null;
let progresoEstadoBadgeEl = null;
let stepperConfiguracionEl = null;
let btnReevaluarTodas = null;
let spanBtnText = null;
// La categoría ya viene lista para mostrarse tal cual la definió el catálogo
// de Ingeniería > Especificaciones (Motor, Transmisión, Dimensiones, Pesos,
// Ejes, Suspensión, Llantas, Frenos, Seguridad, Electricidad, Combustible,
// Dirección, Equipamiento, Batería, Carrocería) — no requiere traducción.

document.addEventListener("DOMContentLoaded", function () {
  divLoading = document.querySelector("#divLoading");
  formConfiguraciones = document.querySelector("#formConfiguraciones");
  formEspecificaciones = document.querySelector("#formEspecificaciones");
  formCertificaciones = document.querySelector("#formCertificaciones");
  spanBtnText = document.querySelector("#btnText");
  idConfiguracionInput = document.querySelector("#id_configuracion");
  segmentoSelect = document.querySelector("#id-segmento-select");
  sublineaSelect = document.querySelector("#id-sublinea-select");
  tabEspecificacionesEl = document.querySelector("#tabEspecificaciones");
  tabCertificacionesEl = document.querySelector("#tabCertificaciones");
  tabHistorialEl = document.querySelector("#tabHistorial");
  tabFichaTecnicaEl = document.querySelector("#tabFichaTecnica");
  progresoWrapperEl = document.querySelector("#progresoConfiguracionWrapper");
  progresoEstadoBadgeEl = document.querySelector("#progresoEstadoBadge");
  stepperConfiguracionEl = document.querySelector("#stepperConfiguracion");
  btnReevaluarTodas = document.querySelector("#btnReevaluarTodas");

  if (!formConfiguraciones) {
    console.warn("formConfiguraciones no encontrado. JS de configuraciones no se inicializa en esta vista.");
    return;
  }

  // --------------------------------------------------------------
  // DATATABLE
  // --------------------------------------------------------------
  tableConfiguraciones = $("#tableConfiguraciones").DataTable({
    processing: true,
    serverSide: false,
    ajax: {
      url: base_url + "/Ing_configuraciones/getConfiguraciones",
      dataSrc: function (json) {
        let data = json || [];

        // Actualizar KPIs de Configuraciones de forma dinámica (mismo patrón
        // que functions_prv_madrinas.js): se calculan en el cliente a partir
        // de la misma respuesta que ya alimenta la tabla, sin endpoint extra.
        let total = data.length;
        let autorizadas = data.filter(function (d) { return d.estado === "AUTORIZADO"; }).length;
        let pendientes = data.filter(function (d) {
          return d.estado === "BORRADOR" || d.estado === "EN_REVISION" || d.estado === "EN_CORRECCION";
        }).length;
        let bloqueadas = data.filter(function (d) { return d.estado === "BLOQUEADO"; }).length;

        let kpiTotal = document.querySelector("#kpi-total-configuraciones");
        let kpiAutorizadas = document.querySelector("#kpi-autorizadas");
        let kpiPendientes = document.querySelector("#kpi-pendientes");
        let kpiBloqueadas = document.querySelector("#kpi-bloqueadas");

        if (kpiTotal) kpiTotal.textContent = total;
        if (kpiAutorizadas) kpiAutorizadas.textContent = autorizadas;
        if (kpiPendientes) kpiPendientes.textContent = pendientes;
        if (kpiBloqueadas) kpiBloqueadas.textContent = bloqueadas;

        return data;
      },
    },
    columns: [
      { data: "segmento" },
      { data: "modelo" },
      { data: "tipo_origen_label" },
      { data: "nombre_unidad" },
      { data: "nombre_comercial" },
      { data: "clave_vehicular" },
      { data: "motor_label" },
      { data: "transmision_label" },
      { data: "estado_label" },
      { data: "options" },
    ],
    responsive: false,
    scrollX: true,
    autoWidth: false,
    destroy: true,
    pageLength: 10,
  });

  // --------------------------------------------------------------
  // CATÁLOGOS PARA SELECTS (segmento, motor, transmisión)
  // --------------------------------------------------------------
  cargarSelectSegmentos();
  cargarSelectMotores();
  cargarSelectTransmisiones();

  if (segmentoSelect) {
    segmentoSelect.addEventListener("change", function () {
      cargarSelectModelos(this.value, null);
    });
  }

  // --------------------------------------------------------------
  // TABS
  // --------------------------------------------------------------
  const primerTabEl = document.querySelector('#nav-tab a[href="#listConfiguraciones"]');
  const firstTabEl = document.querySelector('#nav-tab a[href="#datosGenerales"]');

  if (primerTabEl && firstTabEl && spanBtnText) {
    primerTab = new bootstrap.Tab(primerTabEl);
    firstTab = new bootstrap.Tab(firstTabEl);
    tabNuevo = firstTabEl;

    // Nota: la pestaña DATOS GENERALES/ACTUALIZAR es solo navegación — NO
    // reinicia el formulario al hacer clic. Así, si estabas editando la
    // pestaña ESPECIFICACIONES/CERTIFICACIONES de una configuración y le
    // dabas clic a la pestaña ACTUALIZAR (para ver/editar los datos
    // generales de ESA MISMA configuración), no se pierde nada.
    //
    // La pestaña LISTADO SÍ reinicia el formulario al hacer clic: al volver
    // al listado se debe limpiar todo, regresar el botón/pestaña de alta a
    // "DATOS GENERALES" (en vez de "ACTUALIZAR") y volver a deshabilitar
    // ESPECIFICACIONES/CERTIFICACIONES/HISTORIAL/FICHA TÉCNICA, ya que esas
    // subpestañas solo aplican mientras se está dando de alta o editando una
    // configuración específica.
    primerTabEl.addEventListener("click", function () {
      resetForm();
    });
  }

  let btnNuevaConfiguracion = document.querySelector("#btnNuevaConfiguracion");
  if (btnNuevaConfiguracion) {
    btnNuevaConfiguracion.addEventListener("click", function () {
      resetForm();
      if (firstTab) firstTab.show();
    });
  }

  function resetForm() {
    if (tabNuevo) tabNuevo.textContent = "DATOS GENERALES";
    if (spanBtnText) spanBtnText.textContent = "REGISTRAR";
    if (idConfiguracionInput) idConfiguracionInput.value = "0";
    if (formConfiguraciones) formConfiguraciones.reset();
    if (sublineaSelect) {
      sublineaSelect.innerHTML = '<option value="">--Seleccione un segmento--</option>';
      sublineaSelect.disabled = true;
    }
    deshabilitarTabsHijas();
  }

  function deshabilitarTabsHijas() {
    if (tabEspecificacionesEl) tabEspecificacionesEl.classList.add("disabled");
    if (tabCertificacionesEl) tabCertificacionesEl.classList.add("disabled");
    if (tabHistorialEl) tabHistorialEl.classList.add("disabled");
    if (tabFichaTecnicaEl) tabFichaTecnicaEl.classList.add("disabled");
    document.querySelector("#avisoSinConfiguracionSpecs").style.display = "block";
    document.querySelector("#formEspecificaciones").style.display = "none";
    document.querySelector("#avisoSinConfiguracionCert").style.display = "block";
    document.querySelector("#formCertificaciones").style.display = "none";
    document.querySelector("#avisoSinConfiguracionHistorial").style.display = "block";
    document.querySelector("#contenedorHistorial").style.display = "none";
    document.querySelector("#avisoSinConfiguracionFicha").style.display = "block";
    document.querySelector("#contenedorFichaTecnica").style.display = "none";
    document.querySelector("#contenedorFichaTecnica").innerHTML = "";
    if (progresoWrapperEl) progresoWrapperEl.style.display = "none";
    if (stepperConfiguracionEl) stepperConfiguracionEl.innerHTML = "";
    if (progresoEstadoBadgeEl) progresoEstadoBadgeEl.innerHTML = "";
  }

  function habilitarTabsHijas(idConfiguracion) {
    if (tabEspecificacionesEl) tabEspecificacionesEl.classList.remove("disabled");
    if (tabCertificacionesEl) tabCertificacionesEl.classList.remove("disabled");
    if (tabHistorialEl) tabHistorialEl.classList.remove("disabled");
    if (tabFichaTecnicaEl) tabFichaTecnicaEl.classList.remove("disabled");
    cargarEspecificaciones(idConfiguracion);
    cargarCertificaciones(idConfiguracion);
    cargarHistorial(idConfiguracion);
    cargarFichaTecnica(idConfiguracion);
    cargarProgreso(idConfiguracion);
  }

  // --------------------------------------------------------------
  // SUBMIT DATOS GENERALES
  // --------------------------------------------------------------
  formConfiguraciones.addEventListener("submit", function (e) {
    e.preventDefault();

    if (divLoading) divLoading.style.display = "flex";

    let request = new XMLHttpRequest();
    let ajaxUrl = base_url + "/Ing_configuraciones/setConfiguracion";
    let formData = new FormData(formConfiguraciones);

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
        idConfiguracionInput.value = objData.id_configuracion;
        if (tabNuevo) tabNuevo.textContent = "ACTUALIZAR";
        if (spanBtnText) spanBtnText.textContent = "ACTUALIZAR";
        habilitarTabsHijas(objData.id_configuracion);
        Swal.fire({
          title: objData.msg,
          icon: "success",
          confirmButtonText: "OK",
          confirmButtonColor: "#28a745",
        }).then(() => {
          if (tableConfiguraciones) tableConfiguraciones.ajax.reload();
        });
      } else {
        Swal.fire("Atención", objData.msg, "warning");
      }
    };
  });

  // --------------------------------------------------------------
  // SUBMIT ESPECIFICACIONES
  // --------------------------------------------------------------
  if (formEspecificaciones) {
    formEspecificaciones.addEventListener("submit", function (e) {
      e.preventDefault();
      if (divLoading) divLoading.style.display = "flex";

      let request = new XMLHttpRequest();
      let ajaxUrl = base_url + "/Ing_configuraciones/setEspecificaciones";
      let formData = new FormData(formEspecificaciones);
      formData.append("id_configuracion", idConfiguracionInput.value);

      request.open("POST", ajaxUrl, true);
      request.send(formData);

      request.onreadystatechange = function () {
        if (request.readyState !== 4) return;
        if (divLoading) divLoading.style.display = "none";
        if (request.status !== 200) {
          Swal.fire("Error", "Ocurrió un error en el servidor.", "error");
          return;
        }
        let objData = JSON.parse(request.responseText);
        if (objData.status) {
          Swal.fire("¡Operación exitosa!", objData.msg, "success");
          if (idConfiguracionInput && idConfiguracionInput.value) {
            cargarHistorial(idConfiguracionInput.value);
            cargarFichaTecnica(idConfiguracionInput.value);
            cargarProgreso(idConfiguracionInput.value);
          }
          if (tableConfiguraciones) tableConfiguraciones.ajax.reload();
        } else {
          Swal.fire("Atención", objData.msg, "warning");
        }
      };
    });
  }

  // --------------------------------------------------------------
  // SUBMIT CERTIFICACIONES
  // --------------------------------------------------------------
  if (btnReevaluarTodas) {
    btnReevaluarTodas.addEventListener("click", function () {
      Swal.fire({
        title: "Reevaluar todas las configuraciones",
        text: "Se recalculará el estado (autorizado/bloqueado/en corrección) de todas las configuraciones en revisión, autorizadas o bloqueadas. ¿Continuar?",
        icon: "question",
        showCancelButton: true,
        confirmButtonText: "Sí, reevaluar",
        cancelButtonText: "Cancelar",
      }).then((result) => {
        if (!result.isConfirmed) return;

        if (divLoading) divLoading.style.display = "flex";
        let request = new XMLHttpRequest();
        request.open("POST", base_url + "/Ing_configuraciones/ejecutarReglasManual", true);
        request.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
        request.send("accion=reevaluar");

        request.onreadystatechange = function () {
          if (request.readyState !== 4) return;
          if (divLoading) divLoading.style.display = "none";
          if (request.status !== 200) {
            Swal.fire("Error", "Ocurrió un error en el servidor.", "error");
            return;
          }
          let objData = JSON.parse(request.responseText);
          if (objData.status) {
            Swal.fire("Reevaluación completada", objData.msg, "success").then(() => {
              if (tableConfiguraciones) tableConfiguraciones.ajax.reload();
            });
          } else {
            Swal.fire("Atención", objData.msg, "warning");
          }
        };
      });
    });
  }

  if (formCertificaciones) {
    formCertificaciones.addEventListener("submit", function (e) {
      e.preventDefault();
      if (divLoading) divLoading.style.display = "flex";

      let certificaciones = [];
      document.querySelectorAll("#bodyCertificacionesForm tr").forEach(function (tr) {
        certificaciones.push({
          id_certificacion: tr.dataset.idCertificacion,
          obligatoria: tr.querySelector(".chk-obligatoria").checked ? 1 : 0,
          estado: tr.querySelector(".sel-estado-cert").value,
          numero_certificado: tr.querySelector(".txt-numero-cert").value,
          fecha_emision: tr.querySelector(".fecha-emision-cert").value,
          fecha_inicio: tr.querySelector(".fecha-inicio-cert").value,
          fecha_vencimiento: tr.querySelector(".fecha-vencimiento-cert").value,
          observaciones: tr.querySelector(".txt-observaciones-cert").value,
        });
      });

      let request = new XMLHttpRequest();
      let ajaxUrl = base_url + "/Ing_configuraciones/setCertificaciones";
      let params = new URLSearchParams();
      params.append("id_configuracion", idConfiguracionInput.value);
      params.append("certificaciones", JSON.stringify(certificaciones));

      request.open("POST", ajaxUrl, true);
      request.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
      request.send(params.toString());

      request.onreadystatechange = function () {
        if (request.readyState !== 4) return;
        if (divLoading) divLoading.style.display = "none";
        if (request.status !== 200) {
          Swal.fire("Error", "Ocurrió un error en el servidor.", "error");
          return;
        }
        let objData = JSON.parse(request.responseText);
        if (objData.status) {
          Swal.fire("¡Operación exitosa!", objData.msg, "success");
          if (idConfiguracionInput && idConfiguracionInput.value) {
            cargarHistorial(idConfiguracionInput.value);
            cargarFichaTecnica(idConfiguracionInput.value);
            cargarProgreso(idConfiguracionInput.value);
          }
          if (tableConfiguraciones) tableConfiguraciones.ajax.reload();
        } else {
          Swal.fire("Atención", objData.msg, "warning");
        }
      };
    });
  }
});

// ------------------------------------------------------------------------
// CARGA DE SELECTS
// ------------------------------------------------------------------------
function cargarSelectSegmentos() {
  let request = new XMLHttpRequest();
  request.open("GET", base_url + "/Inv_lineasdproducto/getSelectLineasProductos", true);
  request.send();
  request.onreadystatechange = function () {
    if (request.readyState === 4 && request.status === 200 && segmentoSelect) {
      segmentoSelect.innerHTML = request.responseText;
    }
  };
}

function cargarSelectModelos(idLinea, idSublineaSeleccionada) {
  if (!sublineaSelect) return;
  if (!idLinea) {
    sublineaSelect.innerHTML = '<option value="">--Seleccione un segmento--</option>';
    sublineaSelect.disabled = true;
    return;
  }

  let request = new XMLHttpRequest();
  request.open("GET", base_url + "/Inv_lineasdproducto/getSublineas/" + idLinea, true);
  request.send();
  request.onreadystatechange = function () {
    if (request.readyState !== 4 || request.status !== 200) return;
    let arrData = JSON.parse(request.responseText);
    let html = '<option value="">--Seleccione--</option>';
    arrData.forEach(function (row) {
      if (row.estado == 2) {
        html += '<option value="' + row.idsublineaproducto + '">' + row.descripcion + "</option>";
      }
    });
    sublineaSelect.innerHTML = html;
    sublineaSelect.disabled = false;
    if (idSublineaSeleccionada) {
      sublineaSelect.value = idSublineaSeleccionada;
    }
  };
}

function cargarSelectMotores() {
  let sel = document.querySelector("#id-motor-select");
  if (!sel) return;
  let request = new XMLHttpRequest();
  request.open("GET", base_url + "/Ing_motores/getSelectMotores", true);
  request.send();
  request.onreadystatechange = function () {
    if (request.readyState === 4 && request.status === 200) {
      sel.innerHTML = request.responseText;
    }
  };
}

function cargarSelectTransmisiones() {
  let sel = document.querySelector("#id-transmision-select");
  if (!sel) return;
  let request = new XMLHttpRequest();
  request.open("GET", base_url + "/Ing_transmisiones/getSelectTransmisiones", true);
  request.send();
  request.onreadystatechange = function () {
    if (request.readyState === 4 && request.status === 200) {
      sel.innerHTML = request.responseText;
    }
  };
}

// ------------------------------------------------------------------------
// ESPECIFICACIONES (por configuración)
// ------------------------------------------------------------------------
function cargarEspecificaciones(idConfiguracion) {
  document.querySelector("#avisoSinConfiguracionSpecs").style.display = "none";
  document.querySelector("#formEspecificaciones").style.display = "block";

  let request = new XMLHttpRequest();
  request.open("GET", base_url + "/Ing_configuraciones/getEspecificaciones/" + idConfiguracion, true);
  request.send();
  request.onreadystatechange = function () {
    if (request.readyState !== 4 || request.status !== 200) return;
    let arrData = JSON.parse(request.responseText);
    renderEspecificaciones(arrData);
  };
}

function renderEspecificaciones(arrData) {
  let contenedor = document.querySelector("#contenedorEspecificaciones");
  if (!contenedor) return;

  let grupos = {};
  arrData.forEach(function (row) {
    if (!grupos[row.categoria]) grupos[row.categoria] = [];
    grupos[row.categoria].push(row);
  });

  let html = "";
  Object.keys(grupos).forEach(function (categoria) {
    let label = categoria;
    html += '<h6 class="text-muted mt-3">' + label + "</h6><div class=\"row\">";
    grupos[categoria].forEach(function (row) {
      let unidad = row.unidad ? " (" + row.unidad + ")" : "";
      html +=
        '<div class="col-lg-3 col-sm-6"><div class="mb-3">' +
        '<label class="form-label">' + row.clave + unidad + "</label>" +
        '<input type="text" class="form-control" name="valor[' + row.id_especificacion + ']" value="' +
        (row.valor ? row.valor.replace(/"/g, "&quot;") : "") +
        '"></div></div>';
    });
    html += "</div>";
  });

  contenedor.innerHTML = html;
}

// ------------------------------------------------------------------------
// CERTIFICACIONES (por configuración)
// ------------------------------------------------------------------------
function cargarCertificaciones(idConfiguracion) {
  document.querySelector("#avisoSinConfiguracionCert").style.display = "none";
  document.querySelector("#formCertificaciones").style.display = "block";

  let request = new XMLHttpRequest();
  request.open("GET", base_url + "/Ing_configuraciones/getCertificaciones/" + idConfiguracion, true);
  request.send();
  request.onreadystatechange = function () {
    if (request.readyState !== 4 || request.status !== 200) return;
    let arrData = JSON.parse(request.responseText);
    renderCertificaciones(arrData);
  };
}

function renderCertificaciones(arrData) {
  let tbody = document.querySelector("#bodyCertificacionesForm");
  if (!tbody) return;

  const ESTADOS = ["NO_APLICA", "PENDIENTE", "EN_PROCESO", "VIGENTE", "POR_VENCER", "VENCIDA", "POR_REVISAR", "NO_DISPONIBLE"];

  let html = "";
  arrData.forEach(function (row) {
    let opciones = "";
    ESTADOS.forEach(function (estado) {
      let sel = (row.estado || "PENDIENTE") === estado ? "selected" : "";
      opciones += '<option value="' + estado + '" ' + sel + ">" + estado.replace("_", " ") + "</option>";
    });

    html +=
      '<tr data-id-certificacion="' + row.id_certificacion + '">' +
      "<td>" + row.nombre + (row.codigo ? " (" + row.codigo + ")" : "") + "</td>" +
      '<td class="text-center"><input type="checkbox" class="form-check-input chk-obligatoria" ' +
      (row.obligatoria == 1 || row.obligatoria === null ? "checked" : "") + "></td>" +
      '<td><select class="form-select sel-estado-cert">' + opciones + "</select></td>" +
      '<td><input type="text" class="form-control txt-numero-cert" value="' + (row.numero_certificado || "") + '"></td>' +
      '<td><input type="date" class="form-control fecha-emision-cert" value="' + (row.fecha_emision || "") + '"></td>' +
      '<td><input type="date" class="form-control fecha-inicio-cert" value="' + (row.fecha_inicio || "") + '"></td>' +
      '<td><input type="date" class="form-control fecha-vencimiento-cert" value="' + (row.fecha_vencimiento || "") + '"></td>' +
      '<td><input type="text" class="form-control txt-observaciones-cert" value="' + (row.observaciones || "") + '"></td>' +
      "</tr>";
  });

  tbody.innerHTML = html;
}

// ------------------------------------------------------------------------
// HISTORIAL (bitácora del motor de reglas)
// ------------------------------------------------------------------------
function cargarHistorial(idConfiguracion) {
  document.querySelector("#avisoSinConfiguracionHistorial").style.display = "none";
  document.querySelector("#contenedorHistorial").style.display = "block";

  let request = new XMLHttpRequest();
  request.open("GET", base_url + "/Ing_configuraciones/getHistorial/" + idConfiguracion, true);
  request.send();
  request.onreadystatechange = function () {
    if (request.readyState !== 4 || request.status !== 200) return;
    let arrData = JSON.parse(request.responseText);
    renderHistorial(arrData);
  };
}

function renderHistorial(arrData) {
  let tbody = document.querySelector("#bodyHistorial");
  if (!tbody) return;

  if (!arrData.length) {
    tbody.innerHTML = '<tr><td colspan="4" class="text-center text-muted">Sin evaluaciones automáticas registradas todavía. El motor de reglas solo evalúa configuraciones en estado "En revisión", "Autorizado" o "Bloqueado" — mientras esté en Borrador, aquí no aparecerá nada.</td></tr>';
    return;
  }

  let html = "";
  arrData.forEach(function (row) {
    html +=
      "<tr>" +
      "<td>" + row.created_at + "</td>" +
      "<td>" + row.origen_label + "</td>" +
      "<td>" + row.estado_anterior + " &rarr; " + row.estado_nuevo + "</td>" +
      "<td>" + (row.motivos || "-") + "</td>" +
      "</tr>";
  });

  tbody.innerHTML = html;
}

// ------------------------------------------------------------------------
// EDITAR
// ------------------------------------------------------------------------
function setFieldValue(selector, value) {
  let el = document.querySelector(selector);
  if (!el) {
    console.warn("Elemento no encontrado en el DOM (¿página desactualizada? intenta refrescar con Ctrl+F5):", selector);
    return;
  }
  el.value = value;
}

function setDisplay(selector, display) {
  let el = document.querySelector(selector);
  if (!el) {
    console.warn("Elemento no encontrado en el DOM (¿página desactualizada? intenta refrescar con Ctrl+F5):", selector);
    return;
  }
  el.style.display = display;
}

function fntEditInfo(idConfiguracion) {
  if (tabNuevo) tabNuevo.textContent = "ACTUALIZAR";
  if (spanBtnText) spanBtnText.textContent = "ACTUALIZAR";

  let request = new XMLHttpRequest();
  let ajaxUrl = base_url + "/Ing_configuraciones/getConfiguracion/" + idConfiguracion;

  request.open("GET", ajaxUrl, true);
  request.send();

  request.onreadystatechange = function () {
    if (request.readyState !== 4) return;
    if (request.status !== 200) {
      Swal.fire("Error", "Error al consultar la configuración.", "error");
      return;
    }

    let objData = JSON.parse(request.responseText);
    if (!objData.status) {
      Swal.fire("Error", objData.msg, "error");
      return;
    }

    let d = objData.data;
    setFieldValue("#id_configuracion", d.id_configuracion);
    setFieldValue("#id-segmento-select", d.idlineaproducto);
    setFieldValue("#tipo-origen-select", d.tipo_origen);
    setFieldValue("#estado-select", d.estado);
    setFieldValue("#nombre-unidad-input", d.nombre_unidad || "");
    setFieldValue("#nombre-comercial-input", d.nombre_comercial || "");
    setFieldValue("#clave-vehicular-input", d.clave_vehicular || "");
    setFieldValue("#codigo-modelo-input", d.codigo_modelo || "");
    setFieldValue("#combustible-input", d.combustible || "");
    setFieldValue("#peso-bruto-input", d.peso_bruto || "");
    setFieldValue("#nivel-emisiones-input", d.nivel_emisiones || "");
    setFieldValue("#version-input", d.version || "");

    cargarSelectModelos(d.idlineaproducto, d.id_sublineaproducto);

    setTimeout(function () {
      setFieldValue("#id-motor-select", d.id_motor || "");
      setFieldValue("#id-transmision-select", d.id_transmision || "");
    }, 400);

    if (tabEspecificacionesEl) tabEspecificacionesEl.classList.remove("disabled");
    if (tabCertificacionesEl) tabCertificacionesEl.classList.remove("disabled");
    if (tabHistorialEl) tabHistorialEl.classList.remove("disabled");
    if (tabFichaTecnicaEl) tabFichaTecnicaEl.classList.remove("disabled");
    setDisplay("#avisoSinConfiguracionSpecs", "none");
    setDisplay("#formEspecificaciones", "block");
    setDisplay("#avisoSinConfiguracionCert", "none");
    setDisplay("#formCertificaciones", "block");
    setDisplay("#avisoSinConfiguracionHistorial", "none");
    setDisplay("#contenedorHistorial", "block");
    cargarEspecificaciones(d.id_configuracion);
    cargarCertificaciones(d.id_configuracion);
    cargarHistorial(d.id_configuracion);
    cargarFichaTecnica(d.id_configuracion);
    cargarProgreso(d.id_configuracion);

    if (firstTab) firstTab.show();
  };
}

// ------------------------------------------------------------------------
// ELIMINAR
// ------------------------------------------------------------------------
function fntDelInfo(idConfiguracion) {
  Swal.fire({
    title: "Confirmar eliminación",
    text: "¿Estás seguro de que deseas eliminar esta configuración? Esta acción no se puede deshacer.",
    icon: "warning",
    showCancelButton: true,
    confirmButtonText: "Sí, eliminar",
    cancelButtonText: "Cancelar",
    confirmButtonColor: "#dc3545",
  }).then((result) => {
    if (!result.isConfirmed) return;

    let request = new XMLHttpRequest();
    let ajaxUrl = base_url + "/Ing_configuraciones/delConfiguracion";
    let strData = "id_configuracion=" + idConfiguracion;

    request.open("POST", ajaxUrl, true);
    request.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
    request.send(strData);

    request.onreadystatechange = function () {
      if (request.readyState === 4 && request.status === 200) {
        let objData = JSON.parse(request.responseText);
        if (objData.status) {
          Swal.fire("¡Operación exitosa!", objData.msg, "success");
          if (tableConfiguraciones) tableConfiguraciones.ajax.reload();
        } else {
          Swal.fire("Atención", objData.msg, "error");
        }
      }
    };
  });
}

// ------------------------------------------------------------------------
// FICHA TÉCNICA (vista de solo lectura con todo lo capturado)
// ------------------------------------------------------------------------
function cargarFichaTecnica(idConfiguracion) {
  setDisplay("#avisoSinConfiguracionFicha", "none");
  setDisplay("#contenedorFichaTecnica", "block");

  let request = new XMLHttpRequest();
  request.open("GET", base_url + "/Ing_configuraciones/getFichaTecnica/" + idConfiguracion, true);
  request.send();
  request.onreadystatechange = function () {
    if (request.readyState !== 4 || request.status !== 200) return;
    let objData = JSON.parse(request.responseText);
    if (!objData.status) return;
    renderFichaTecnica(objData);
  };
}

function fichaFila(label, valor, unidad) {
  let texto = valor === null || valor === undefined || valor === "" ? "—" : valor;
  if (unidad && texto !== "—") texto = texto + " " + unidad;
  return (
    '<tr><th class="text-muted fw-normal" style="width:50%">' +
    label +
    "</th><td>" +
    texto +
    "</td></tr>"
  );
}

function renderFichaTecnica(objData) {
  let cont = document.querySelector("#contenedorFichaTecnica");
  if (!cont) return;

  let c = objData.configuracion;
  let html = "";

  html += '<div class="d-flex flex-wrap align-items-center gap-2 mb-3">';
  html += "<h4 class=\"mb-0\">" + (c.nombre_unidad || "(sin nombre)") + "</h4>";
  html += '<span class="badge bg-primary">' + c.tipo_origen_label + "</span>";
  html += c.estado_label;
  html += "</div>";

  if (c.estado === "AUTORIZADO") {
    html += '<div class="card border-0 shadow-sm mb-3">';
    html += '<div class="card-body d-flex flex-wrap align-items-center justify-content-between gap-3">';
    if (c.inv_cve_articulo) {
      html += '<div>';
      html += '<h6 class="mb-1"><i class="ri-checkbox-circle-fill text-success align-bottom me-1"></i>Dada de alta en inventario</h6>';
      html +=
        '<span class="text-muted">SKU: <strong>' +
        c.inv_cve_articulo +
        '</strong> — los VIN de las unidades físicas se generan desde el módulo VIN.</span>';
      html += '</div>';
    } else {
      html += '<div>';
      html += '<h6 class="mb-1">Alta en inventario</h6>';
      html +=
        '<span class="text-muted">Crea el artículo (SKU) en inventario para poder generar después los VIN de las unidades físicas.</span>';
      html += '</div>';
      html +=
        '<button type="button" class="btn btn-primary btn-label" onclick="darDeAltaInventario(' +
        c.id_configuracion +
        ')"><i class="ri-archive-2-line label-icon align-middle fs-16 me-2"></i>Dar de alta en inventario</button>';
    }
    html += "</div></div>";
  }

  html += '<div class="row g-3">';

  // DATOS GENERALES
  html +=
    '<div class="col-lg-6"><div class="card h-100 mb-0"><div class="card-header py-2"><strong>DATOS GENERALES</strong></div>' +
    '<div class="card-body p-0"><table class="table table-sm table-borderless mb-0">';
  html += fichaFila("Segmento", c.segmento);
  html += fichaFila("Modelo", c.modelo);
  html += fichaFila("Nombre comercial", c.nombre_comercial);
  html += fichaFila("Código de modelo", c.codigo_modelo);
  html += fichaFila("Clave vehicular", c.clave_vehicular);
  html += fichaFila("Combustible", c.combustible);
  html += fichaFila("Peso bruto vehicular", c.peso_bruto, "kg");
  html += fichaFila("Nivel de emisiones", c.nivel_emisiones);
  html += fichaFila("Versión", c.version);
  html += "</table></div></div></div>";

  // MOTOR
  html +=
    '<div class="col-lg-6"><div class="card h-100 mb-0"><div class="card-header py-2"><strong>MOTOR</strong></div>' +
    '<div class="card-body p-0"><table class="table table-sm table-borderless mb-0">';
  if (!c.motor_fabricante) {
    html += '<tr><td class="text-muted">No se ha asignado un motor a esta configuración.</td></tr>';
  } else {
    html += fichaFila("Fabricante / modelo", (c.motor_fabricante + " " + (c.modelo_motor || "")).trim());
    html += fichaFila("Tipo", c.tipo_motor);
    html += fichaFila("Cilindrada", c.cilindrada, "L");
    html += fichaFila("Cilindros", c.numero_cilindros);
    html += fichaFila("Potencia", c.potencia, c.unidad_potencia);
    html += fichaFila("Torque", c.torque, c.unidad_torque);
    html += fichaFila("Combustible (motor)", c.motor_tipo_combustible);
    html += fichaFila("Admisión", c.tipo_admision);
    if (c.fabricante_bateria || c.tipo_bateria || c.capacidad_bateria) {
      html += fichaFila(
        "Batería",
        [c.fabricante_bateria, c.tipo_bateria, c.capacidad_bateria].filter(Boolean).join(" / ")
      );
    }
  }
  html += "</table></div></div></div>";

  // TRANSMISIÓN
  html +=
    '<div class="col-lg-6"><div class="card h-100 mb-0"><div class="card-header py-2"><strong>TRANSMISIÓN</strong></div>' +
    '<div class="card-body p-0"><table class="table table-sm table-borderless mb-0">';
  if (!c.transmision_fabricante) {
    html += '<tr><td class="text-muted">No se ha asignado una transmisión a esta configuración.</td></tr>';
  } else {
    html += fichaFila(
      "Fabricante / modelo",
      (c.transmision_fabricante + " " + (c.transmision_modelo || "")).trim()
    );
    html += fichaFila("Tipo", c.transmision_tipo);
    html += fichaFila("Velocidades", c.numero_velocidades);
    html += fichaFila("Descripción", c.transmision_descripcion);
  }
  html += "</table></div></div></div>";

  // CERTIFICACIONES
  html +=
    '<div class="col-lg-6"><div class="card h-100 mb-0"><div class="card-header py-2"><strong>CERTIFICACIONES</strong></div>' +
    '<div class="card-body p-0"><div class="table-responsive"><table class="table table-sm align-middle mb-0">' +
    "<thead><tr><th>Certificación</th><th>Estado</th><th>No.</th><th>Vencimiento</th></tr></thead><tbody>";
  let certs = objData.certificaciones || [];
  if (!certs.length) {
    html += '<tr><td colspan="4" class="text-muted">Sin certificaciones en el catálogo.</td></tr>';
  } else {
    certs.forEach(function (cert) {
      html +=
        "<tr><td>" +
        cert.nombre +
        "</td><td>" +
        cert.estado_label +
        "</td><td>" +
        (cert.numero_certificado || "—") +
        "</td><td>" +
        (cert.fecha_vencimiento || "—") +
        "</td></tr>";
    });
  }
  
  html += "</tbody></table></div></div></div></div>";

  html += "</div>"; // cierra row principal

  // ESPECIFICACIONES AGRUPADAS POR CATEGORÍA
  html += '<h6 class="text-uppercase text-muted mt-4 mb-2">Especificaciones técnicas</h6>';
  let grupos = objData.especificaciones || {};
  let categorias = Object.keys(grupos);
  if (!categorias.length) {
    html += '<div class="text-muted">Sin especificaciones en el catálogo.</div>';
  } else {
    html += '<div class="row g-3">';
    categorias.forEach(function (categoria) {
      html += '<div class="col-lg-4 col-md-6">';
      html +=
        '<div class="card h-100 mb-0"><div class="card-header py-2"><strong>' +
        categoria +
        '</strong></div><div class="card-body p-0"><table class="table table-sm table-borderless mb-0">';
      grupos[categoria].forEach(function (esp) {
        let valor = esp.valor === null || esp.valor === undefined || esp.valor === "" ? "—" : esp.valor;
        if (esp.unidad && valor !== "—") valor = valor + " " + esp.unidad;
        html +=
          '<tr><th class="text-muted fw-normal" style="width:55%">' + esp.clave + "</th><td>" + valor + "</td></tr>";
      });
      html += "</table></div></div>";
      html += "</div>";
    });
    html += "</div>";
  }

  cont.innerHTML = html;
}

function darDeAltaInventario(idConfiguracion) {
  Swal.fire({
    title: "Dar de alta en inventario",
    text: "El SKU se genera automáticamente a partir de las especificaciones de esta configuración (segmento, modelo, nombre de unidad, origen, carrocería, versión y combustible). ¿Continuar?",
    icon: "question",
    showCancelButton: true,
    confirmButtonText: "Dar de alta",
    cancelButtonText: "Cancelar",
  }).then((result) => {
    if (!result.isConfirmed) return;

    if (divLoading) divLoading.style.display = "flex";
    let request = new XMLHttpRequest();
    request.open("POST", base_url + "/Ing_configuraciones/setAltaInventario", true);
    request.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
    request.send("id_configuracion=" + idConfiguracion);

    request.onreadystatechange = function () {
      if (request.readyState !== 4) return;
      if (divLoading) divLoading.style.display = "none";
      if (request.status !== 200) {
        Swal.fire("Error", "Ocurrió un error en el servidor.", "error");
        return;
      }
      let objData = JSON.parse(request.responseText);
      if (objData.status) {
        let msg = objData.msg + (objData.cve_articulo ? " SKU: " + objData.cve_articulo : "");
        Swal.fire("¡Operación exitosa!", msg, "success").then(() => {
          cargarFichaTecnica(idConfiguracion);
        });
      } else {
        Swal.fire("Atención", objData.msg, "warning");
      }
    };
  });
}

// ------------------------------------------------------------------------
// PROGRESO: línea de proceso (datos generales / especificaciones /
// certificaciones / autorizado), reutiliza el motor de reglas de la
// Épica 3 (solo lectura, no cambia el estado de la configuración).
// ------------------------------------------------------------------------
const ESTADO_STEPPER_MAP = {
  BORRADOR: { icon: "ri-draft-line", css: "secondary", label: "Borrador" },
  EN_REVISION: { icon: "ri-time-line", css: "info", label: "En revisión" },
  EN_CORRECCION: { icon: "ri-error-warning-line", css: "warning", label: "En corrección" },
  AUTORIZADO: { icon: "ri-shield-check-line", css: "success", label: "Autorizado" },
  BLOQUEADO: { icon: "ri-lock-line", css: "danger", label: "Bloqueado" },
  OBSOLETO: { icon: "ri-archive-line", css: "dark", label: "Obsoleto" },
};

function cargarProgreso(idConfiguracion) {
  if (progresoWrapperEl) progresoWrapperEl.style.display = "block";

  let request = new XMLHttpRequest();
  request.open("GET", base_url + "/Ing_configuraciones/getProgreso/" + idConfiguracion, true);
  request.send();
  request.onreadystatechange = function () {
    if (request.readyState !== 4 || request.status !== 200) return;
    let objData = JSON.parse(request.responseText);
    if (!objData.status) return;
    renderProgreso(objData);
  };
}

function stepperNodo(opts) {
  let css = opts.css || (opts.completo ? "success" : "secondary");
  let icon = opts.icon || (opts.completo ? "ri-check-line" : "ri-time-line");
  let target = opts.target ? ' data-target="' + opts.target + '"' : "";
  let title = opts.motivos && opts.motivos.length ? ' title="' + opts.motivos.join("; ").replace(/"/g, "'") + '"' : "";
  let html = '<div class="stepper-step"' + target + title + ">";
  html +=
    '<div class="avatar-xs mx-auto"><span class="avatar-title bg-' +
    css +
    "-subtle text-" +
    css +
    ' rounded-circle fs-16"><i class="' +
    icon +
    '"></i></span></div>';
  html += '<span class="stepper-label">' + opts.label + "</span>";
  if (opts.sublabel) html += '<span class="stepper-sublabel">' + opts.sublabel + "</span>";
  html += "</div>";
  return html;
}

function renderProgreso(objData) {
  if (!stepperConfiguracionEl) return;
  if (progresoEstadoBadgeEl) progresoEstadoBadgeEl.innerHTML = objData.estado_label || "";

  let datos = objData.datos_generales || {};
  let specs = objData.especificaciones || {};
  let certs = objData.certificaciones || {};
  let estado = objData.estado;
  let estadoInfo = ESTADO_STEPPER_MAP[estado] || ESTADO_STEPPER_MAP.BORRADOR;

  let html = '<div class="stepper-track"></div><div class="stepper-steps">';

  html += stepperNodo({
    completo: datos.completo,
    css: datos.completo ? "success" : "secondary",
    icon: datos.completo ? "ri-check-line" : "ri-file-edit-line",
    label: "Datos generales",
    sublabel: datos.completo ? "Completos" : (datos.motivos || []).length + " pendiente(s)",
    motivos: datos.motivos,
    target: "#datosGenerales",
  });

  html += stepperNodo({
    completo: specs.completo,
    css: specs.completo ? "success" : "secondary",
    icon: specs.completo ? "ri-check-line" : "ri-list-check-2",
    label: "Especificaciones",
    sublabel: (specs.capturadas || 0) + " de " + (specs.total || 0),
    motivos: specs.motivos,
    target: "#especificaciones",
  });

  html += stepperNodo({
    completo: certs.completo,
    css: certs.completo ? "success" : "secondary",
    icon: certs.completo ? "ri-check-line" : "ri-shield-check-line",
    label: "Certificaciones",
    sublabel: (certs.completas || 0) + " de " + (certs.total || 0),
    motivos: certs.motivos,
    target: "#certificaciones",
  });

  html += stepperNodo({
    completo: estado === "AUTORIZADO",
    css: estadoInfo.css,
    icon: estadoInfo.icon,
    label: "Autorizado",
    sublabel: estadoInfo.label,
    motivos: objData.otros_motivos,
    target: "#historial",
  });

  html += "</div>";
  stepperConfiguracionEl.innerHTML = html;

  stepperConfiguracionEl.querySelectorAll(".stepper-step[data-target]").forEach(function (el) {
    el.addEventListener("click", function () {
      let tabLink = document.querySelector('#nav-tab a[href="' + el.dataset.target + '"]');
      if (tabLink && !tabLink.classList.contains("disabled")) {
        new bootstrap.Tab(tabLink).show();
      }
    });
  });
}
