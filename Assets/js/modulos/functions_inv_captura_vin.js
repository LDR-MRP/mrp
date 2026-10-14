/* =====================================================================
 * Glosario NIV (Inv_captura_vin)
 * Captura del modelo NIV base: posiciones 1-8, 10 y 11.
 * La posición 9 (verificador) y 12-17 (consecutivo) se calculan en
 * Inv_series al generar los VIN. El vin_base se guarda con "-" en la
 * posición 9 (ej. 3LD12B2J-VA): NO cambiar ese formato.
 * ===================================================================== */

// Segmento de cada posición (para colores)
const NIV_SEG = {
  1: "wmi", 2: "wmi", 3: "wmi",
  4: "vds", 5: "vds", 6: "vds", 7: "vds", 8: "vds",
  9: "chk",
  10: "vis", 11: "vis",
};

let modelosNivCache = [];

/* ---------------------------------------------------------------------
 * Reglas de cálculo (única fuente para la vista previa y la tabla)
 * ------------------------------------------------------------------- */

// Categoría del tipo de vehículo → clasificación de distancia
function clasificacionDistancia(categoria) {
  if (!categoria) return "";
  return categoria === "Automóvil" ? "AUTO" : "CAMION";
}

function caracterPeso(peso) {
  if (peso === "" || peso === null || isNaN(peso)) return "";
  peso = parseFloat(peso);
  if (peso < 3000) return "1";
  if (peso < 10000) return "2";
  if (peso < 20000) return "3";
  return "4";
}

function caracterPotencia(hp) {
  if (hp === "" || hp === null || isNaN(hp)) return "";
  hp = parseFloat(hp);
  if (hp <= 136) return "1";
  if (hp <= 272) return "2";
  if (hp <= 408) return "3";
  return "4";
}

function caracterDistancia(distancia, clasificacion) {
  if (distancia === "" || distancia === null || isNaN(distancia) || !clasificacion) return "";
  distancia = parseFloat(distancia);

  if (clasificacion === "AUTO") {
    // milímetros
    if (distancia <= 2000) return "E";
    if (distancia <= 4500) return "F";
    if (distancia <= 7000) return "G";
    return "H";
  }
  // autobús / camión, metros
  if (distancia <= 10) return "J";
  if (distancia <= 15) return "L";
  return "K";
}

/**
 * d = { wmi, tipo, peso, motor, hp, clasificacion, distancia, anio, planta }
 * Regresa { wmi:"3LD", 4:"1", ... , 11:"A" } con "" donde falte el dato.
 */
function calcularPosiciones(d) {
  return {
    wmi: d.wmi || "",
    4: d.tipo || "",
    5: caracterPeso(d.peso),
    6: d.motor || "",
    7: caracterPotencia(d.hp),
    8: caracterDistancia(d.distancia, d.clasificacion),
    10: d.anio || "",
    11: d.planta || "",
  };
}

// Mismo formato que siempre: 8 chars + "-" + año + planta
function armarVinBase(p) {
  return (
    (p.wmi || "---") +
    (p[4] || "-") + (p[5] || "-") + (p[6] || "-") + (p[7] || "-") + (p[8] || "-") +
    "-" +
    (p[10] || "-") + (p[11] || "-")
  );
}

function escapeHtml(str) {
  return String(str ?? "").replace(/[&<>"']/g, (c) => ({
    "&": "&amp;", "<": "&lt;", ">": "&gt;", '"': "&quot;", "'": "&#39;",
  })[c]);
}

/* =====================================================================
 * Historial del VIN base (wms_vin_historial, modulo = VIN_BASE)
 * ===================================================================== */
function abrirHistorialModelo(id, modelo) {
  const body = document.getElementById("historialModeloBody");
  document.getElementById("historialModeloTitulo").textContent = modelo;
  body.innerHTML = '<div class="text-center text-muted py-4"><span class="spinner-border spinner-border-sm me-1"></span> Cargando...</div>';
  bootstrap.Modal.getOrCreateInstance(document.getElementById("modalHistorialModelo")).show();

  const CAMPOS = {
    vin_base: "VIN base", modelo: "Modelo", fabricante: "Fabricante", tipo_vehiculo: "Tipo de vehículo",
    peso_bruto_kg: "Peso bruto (kg)", tipo_motor: "Tipo de motor", potencia_hp: "Potencia (HP)",
    distancia_ejes: "Distancia entre ejes", anio: "Año", planta: "Planta", estado: "Estado",
  };
  const ACCIONES = { ALTA: "Alta", MODIFICACION: "Modificación" };

  fetch(base_url + "/Inv_captura_vin/getHistorial?id=" + encodeURIComponent(id))
    .then((res) => res.json())
    .then((res) => {
      const rows = res.success ? res.data : [];
      if (!rows.length) {
        body.innerHTML = '<div class="text-center text-muted py-4"><i class="ri-inbox-line fs-20 d-block mb-1"></i>Sin cambios registrados</div>';
        return;
      }
      body.innerHTML = `<ul class="hist-list">${rows
        .map((h) => {
          const ant = h.valor_anterior;
          const cambio = ant
            ? `<del>${escapeHtml(ant)}</del> <i class="ri-arrow-right-line align-middle text-muted"></i> <ins>${escapeHtml(h.valor_nuevo ?? "—")}</ins>`
            : `<ins>${escapeHtml(h.valor_nuevo ?? "")}</ins>`;
          return `<li class="hist-item">
              <div class="d-flex flex-wrap gap-2 mb-1">
                <span class="fw-semibold fs-13">${escapeHtml(ACCIONES[h.accion] || h.accion)} · ${escapeHtml(CAMPOS[h.campo] || h.campo || "")}</span>
                <span class="ms-auto text-muted fs-12">${escapeHtml(h.fecha)}</span>
              </div>
              <div class="hist-cambio">${cambio}</div>
              ${h.detalle ? `<div class="text-muted fs-12 mt-1">${escapeHtml(h.detalle)}</div>` : ""}
              <div class="text-muted fs-11 mt-1"><i class="ri-user-line align-bottom"></i> ${escapeHtml(h.usuario || "—")}</div>
            </li>`;
        })
        .join("")}</ul>`;
    })
    .catch(() => (body.innerHTML = '<div class="text-danger">No se pudo cargar el historial.</div>'));
}

/* ===================================================================== */

document.addEventListener("DOMContentLoaded", function () {
  const form = document.getElementById("formVinModelo");
  let vinAnterior = "";
  let ingDatos = null; // datos de Ingeniería del producto seleccionado

  // Campos que se pueden llenar desde Ingeniería
  const CAMPOS_ING = ["peso_bruto_kg", "potencia_hp"];

  cargarCatalogos();
  cargarTabla();
  actualizarPreview();

  // Modelo: reflejar su descripción en el campo oculto "modelo"
  document.getElementById("id_inventario").addEventListener("change", function (e) {
    const sel = this.selectedOptions[0];
    document.getElementById("modelo").value = sel ? sel.dataset.descripcion || "" : "";
    if (e.isTrusted) cargarDatosIngenieria(this.value);
  });

  // Si el usuario modifica un dato que vino de Ingeniería, deja de marcarse
  CAMPOS_ING.forEach((id) => {
    const el = document.getElementById(id);
    const quitar = (e) => { if (e.isTrusted) marcarIngenieria(id, false); };
    el.addEventListener("input", quitar);
    el.addEventListener("change", quitar);
  });

  // La clasificación de distancia sale del tipo de vehículo
  document.getElementById("id_tipo_vehiculo").addEventListener("change", aplicarClasificacionDistancia);

  form.querySelectorAll("select, input").forEach((el) => {
    el.addEventListener("change", actualizarPreview);
    el.addEventListener("input", actualizarPreview);
  });

  document.getElementById("btnCancelar").addEventListener("click", limpiarFormulario);

  document.getElementById("buscarModelo").addEventListener("input", function () {
    renderTabla(this.value);
  });

  // Editar desde la tabla (delegado)
  document.getElementById("tablaVin").addEventListener("click", function (e) {
    const btnHist = e.target.closest("[data-historial]");
    if (btnHist) {
      const item = modelosNivCache.find((m) => String(m.id_cat_modelo_vin) === btnHist.dataset.historial);
      abrirHistorialModelo(btnHist.dataset.historial, item ? item.modelo : "");
      return;
    }
    const btn = e.target.closest("[data-editar]");
    if (!btn) return;
    const item = modelosNivCache.find((m) => String(m.id_cat_modelo_vin) === btn.dataset.editar);
    if (item) editarRegistro(item);
  });

  /* ------------------------- catálogos ------------------------------ */

  function llenarSelect(id, url, mapear) {
    return fetch(base_url + url)
      .then((res) => res.json())
      .then((data) => {
        const select = document.getElementById(id);
        // Defensa contra doble carga: deja solo "Seleccionar"
        select.length = 1;
        data.forEach((item) => {
          const o = mapear(item);
          const option = document.createElement("option");
          option.value = o.value;
          option.textContent = o.text;
          Object.entries(o.data || {}).forEach(([k, v]) => (option.dataset[k] = v ?? ""));
          select.appendChild(option);
        });
      });
  }

  function cargarCatalogos() {
    llenarSelect("id_inventario", "/Inv_captura_vin/getModelosInventario", (i) => ({
      value: i.idinventario,
      text: `${i.descripcion} (${i.cve_articulo})`,
      data: { descripcion: i.descripcion },
    }));

    llenarSelect("id_fabricante", "/Inv_captura_vin/getFabricantes", (i) => ({
      value: i.id_fabricante,
      text: `${i.fabricante} (${i.wmi})`,
      data: { codigo: i.wmi, nombre: i.fabricante },
    }));

    llenarSelect("id_tipo_vehiculo", "/Inv_captura_vin/getTiposVehiculo", (i) => ({
      value: i.id_tipo_vehiculo,
      text: i.descripcion,
      data: { codigo: i.caracter, categoria: i.categoria },
    }));

    llenarSelect("id_tipo_motor", "/Inv_captura_vin/getTiposMotor", (i) => ({
      value: i.id_tipo_motor,
      text: i.descripcion,
      data: { codigo: i.caracter },
    }));

    llenarSelect("id_planta", "/Inv_captura_vin/getPlantas", (i) => ({
      value: i.id_planta,
      text: i.planta,
      data: { codigo: i.caracter },
    }));

    llenarSelect("anio", "/Inv_captura_vin/getAniosVin", (a) => ({
      value: a.id_cat_anio_vin,
      text: `${a.anio} (${a.codigo})`,
      data: { codigo: a.codigo },
    }));
  }

  /* -------------------- datos desde Ingeniería --------------------- */

  function marcarIngenieria(id, si) {
    const label = document.querySelector(`label[for="${id}"]`);
    const cont = label ? label.closest(".niv-field-label") : null;
    if (!cont) return;
    let tag = cont.querySelector(".ing-tag");
    if (si && !tag) {
      tag = document.createElement("span");
      tag.className = "ing-tag badge bg-primary-subtle text-primary ms-auto me-1";
      tag.title = "Dato tomado de Ingeniería";
      tag.innerHTML = '<i class="ri-flask-line align-bottom"></i> Ingeniería';
      label.after(tag);
    } else if (!si && tag) {
      tag.remove();
    }
  }

  function limpiarIngenieria() {
    ingDatos = null;
    CAMPOS_ING.forEach((id) => marcarIngenieria(id, false));
    const box = document.getElementById("ingResumen");
    box.className = "d-none";
    box.innerHTML = "";
  }

  function cargarDatosIngenieria(idInventario) {
    limpiarIngenieria();
    if (!idInventario) return;

    const box = document.getElementById("ingResumen");
    box.className = "alert alert-light border mt-3 mb-0 py-2 fs-13";
    box.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Consultando Ingeniería...';

    fetch(base_url + "/Inv_captura_vin/getDatosIngenieria?id_inventario=" + encodeURIComponent(idInventario))
      .then((res) => res.json())
      .then((res) => {
        const d = res.success ? res.data : null;

        if (!d || !d.encontrado) {
          box.className = "alert alert-warning mt-3 mb-0 py-2 fs-13";
          box.innerHTML = '<i class="ri-error-warning-line align-bottom me-1"></i> Este producto no tiene configuración en Ingeniería. Capture los datos manualmente.';
          return;
        }

        ingDatos = d;
        const llenados = [];
        const faltan = [];

        const poner = (id, valor, etiqueta) => {
          if (valor === null || valor === undefined || valor === "") {
            faltan.push(etiqueta);
            return;
          }
          document.getElementById(id).value = valor;
          marcarIngenieria(id, true);
          llenados.push(etiqueta);
        };

        poner("peso_bruto_kg", d.peso_bruto_kg, "peso bruto");
        poner("potencia_hp", d.potencia_hp, "potencia");
        // Tipo de motor: por ahora NO se llena; el combustible solo se muestra en el panel

        // Distancia / longitud: se captura manualmente (no se toma de Ingeniería)

        actualizarPreview();

        const estadoBadge = d.configuracion.estado === "AUTORIZADO"
          ? '<span class="badge bg-success-subtle text-success">AUTORIZADO</span>'
          : `<span class="badge bg-warning-subtle text-warning">${escapeHtml(d.configuracion.estado)}</span>`;

        const motorTxt = d.configuracion.motor ? ` · Motor ${escapeHtml(d.configuracion.motor)}` : "";
        const combTxt = d.combustible ? ` · Combustible <b>${escapeHtml(d.combustible)}</b>` : "";

        box.className = "alert alert-primary bg-primary-subtle border-0 mt-3 mb-0 py-2 fs-13";
        box.innerHTML = `
          <div class="d-flex flex-wrap align-items-center gap-2">
            <i class="ri-flask-line fs-16"></i>
            <span>Ingeniería: <b>${escapeHtml(d.configuracion.nombre)}</b>${motorTxt}${combTxt}</span>
            ${estadoBadge}
          </div>
          <div class="mt-1">
            ${llenados.length ? `<i class="ri-check-line text-success"></i> Se llenó: ${llenados.join(", ")}.` : ""}
            ${faltan.length ? ` <span class="text-muted">Sin dato en Ingeniería: ${faltan.join(", ")}.</span>` : ""}
          </div>`;
      })
      .catch(() => {
        box.className = "alert alert-warning mt-3 mb-0 py-2 fs-13";
        box.innerHTML = '<i class="ri-error-warning-line align-bottom me-1"></i> No se pudieron consultar los datos de Ingeniería.';
      });
  }

  /* --------------------- distancia según tipo ---------------------- */

  function aplicarClasificacionDistancia() {
    const sel = document.getElementById("id_tipo_vehiculo").selectedOptions[0];
    const clasif = clasificacionDistancia(sel && sel.value ? sel.dataset.categoria : "");

    const input = document.getElementById("distancia_ejes");
    const label = document.getElementById("label_distancia");
    const unidad = document.getElementById("unidad_distancia");
    const help = document.getElementById("help_distancia");

    document.getElementById("tipo_distancia").value = clasif;
    input.disabled = !clasif;

    if (clasif === "AUTO") {
      label.textContent = "Distancia entre ejes";
      unidad.textContent = "mm";
      input.placeholder = "Ej. 4500";
      input.step = "1";
      help.innerHTML = '<i class="ri-information-line align-bottom"></i> Automóvil: capturar en milímetros';
    } else if (clasif === "CAMION") {
      label.textContent = "Longitud total";
      unidad.textContent = "m";
      input.placeholder = "Ej. 12.5";
      input.step = "0.01";
      help.innerHTML = '<i class="ri-information-line align-bottom"></i> Autobús / camión: capturar en metros';
    } else {
      label.textContent = "Distancia entre ejes";
      unidad.textContent = "—";
      input.placeholder = "";
      help.innerHTML = '<i class="ri-information-line align-bottom"></i> Seleccione primero el tipo de vehículo';
    }

  }

  /* ------------------------- vista previa -------------------------- */

  function codigo(id) {
    const o = document.getElementById(id).selectedOptions[0];
    return o && o.value ? o.dataset.codigo || "" : "";
  }

  function leerFormulario() {
    return {
      wmi: codigo("id_fabricante"),
      tipo: codigo("id_tipo_vehiculo"),
      peso: document.getElementById("peso_bruto_kg").value,
      motor: codigo("id_tipo_motor"),
      hp: document.getElementById("potencia_hp").value,
      clasificacion: document.getElementById("tipo_distancia").value,
      distancia: document.getElementById("distancia_ejes").value,
      anio: codigo("anio"),
      planta: codigo("id_planta"),
    };
  }

  function actualizarPreview() {
    const p = calcularPosiciones(leerFormulario());
    const vin = armarVinBase(p);

    document.getElementById("vinPreview").textContent = vin;
    document.getElementById("vin_base").value = vin;

    renderAnatomia(vin, vinAnterior);
    vinAnterior = vin;

    // chips en las etiquetas
    document.querySelectorAll(".pos-chip[data-pos]").forEach((chip) => {
      const key = chip.dataset.pos;
      const val = p[key];
      chip.querySelector(".v").textContent = val || (key === "wmi" ? "---" : "-");
      chip.classList.toggle("is-empty", !val);
    });

    // checklist + progreso
    const productoOk = !!document.getElementById("id_inventario").value;
    const items = [
      { pos: "—", label: "Producto", ok: productoOk, val: "" },
      { pos: "1–3", label: "Fabricante", ok: !!p.wmi, val: p.wmi },
      { pos: "4", label: "Tipo de vehículo", ok: !!p[4], val: p[4] },
      { pos: "5", label: "Peso bruto", ok: !!p[5], val: p[5] },
      { pos: "6", label: "Tipo de motor", ok: !!p[6], val: p[6] },
      { pos: "7", label: "Potencia", ok: !!p[7], val: p[7] },
      { pos: "8", label: "Distancia / longitud", ok: !!p[8], val: p[8] },
      { pos: "10", label: "Año modelo", ok: !!p[10], val: p[10] },
      { pos: "11", label: "Planta", ok: !!p[11], val: p[11] },
    ];

    document.getElementById("nivChecklist").innerHTML = items
      .map(
        (i) => `<li>
          <i class="${i.ok ? "ri-checkbox-circle-fill ok" : "ri-checkbox-blank-circle-line pending"} fs-16"></i>
          <span class="pos">${i.pos}</span>
          <span>${i.label}</span>
          <span class="val">${escapeHtml(i.val || "")}</span>
        </li>`,
      )
      .join("");

    const completos = items.filter((i) => i.ok).length;
    document.getElementById("nivProgreso").style.width = (completos / items.length) * 100 + "%";
    document.getElementById("nivProgresoTxt").textContent = `${completos} de ${items.length}`;
    document.getElementById("btnGuardar").disabled = completos !== items.length;
  }

  function renderAnatomia(vin, anterior) {
    let html = "";
    for (let i = 1; i <= 17; i++) {
      if (i === 4 || i === 10 || i === 12) html += '<span class="niv-gap"></span>';

      if (i >= 12) {
        html += `<div class="niv-cell seg-sec is-tail"><span class="ch">#</span><span class="ix">${i}</span></div>`;
        continue;
      }

      const ch = vin[i - 1];
      const vacio = i === 9 || ch === "-";
      const cambio = anterior && anterior[i - 1] !== ch && !vacio;
      const mostrar = i === 9 ? "?" : vacio ? "" : ch;

      html += `<div class="niv-cell seg-${NIV_SEG[i]}${i !== 9 && vacio ? " is-empty" : ""}${cambio ? " is-flash" : ""}">
                 <span class="ch">${escapeHtml(mostrar)}</span><span class="ix">${i}</span>
               </div>`;
    }
    const cont = document.getElementById("nivAnatomy");
    cont.innerHTML = html;
    setTimeout(() => cont.querySelectorAll(".is-flash").forEach((c) => c.classList.remove("is-flash")), 250);
  }

  /* ---------------------------- guardar ---------------------------- */

  form.addEventListener("submit", function (e) {
    e.preventDefault();

    const btn = document.getElementById("btnGuardar");
    const textoBtn = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Guardando...';

    fetch(base_url + "/Inv_captura_vin/store", { method: "POST", body: new FormData(form) })
      .then((res) => res.json())
      .then((res) => {
        if (res.success) {
          Swal.fire({ icon: "success", title: "Correcto", text: res.message, timer: 1800, showConfirmButton: false });
          limpiarFormulario();
          cargarTabla();
        } else {
          Swal.fire({ icon: "error", title: "Error", text: res.message || res.msg });
          btn.innerHTML = textoBtn;
          actualizarPreview();
        }
      })
      .catch((error) => {
        console.error(error);
        Swal.fire({ icon: "error", title: "Error", text: "Ocurrió un error al guardar" });
        btn.innerHTML = textoBtn;
        actualizarPreview();
      });
  });

  /* ------------------------- modo edición -------------------------- */

  function setModo(editando) {
    const btn = document.getElementById("btnGuardar");
    const badge = document.getElementById("modoBadge");

    btn.classList.toggle("btn-success", !editando);
    btn.classList.toggle("btn-warning", editando);
    btn.innerHTML = editando
      ? '<i class="ri-refresh-line align-bottom me-1"></i> Actualizar modelo NIV'
      : '<i class="ri-save-3-line align-bottom me-1"></i> Guardar modelo NIV';

    badge.className = editando ? "badge bg-warning-subtle text-warning fs-12" : "badge bg-success-subtle text-success fs-12";
    badge.innerHTML = editando
      ? '<i class="ri-edit-line align-bottom"></i> Editando'
      : '<i class="ri-add-line align-bottom"></i> Nuevo';

    document.getElementById("btnCancelar").classList.toggle("d-none", !editando);
  }

  function limpiarFormulario() {
    form.reset();
    // los hidden no se limpian con reset()
    ["id", "modelo", "vin_base", "tipo_distancia"].forEach((id) => (document.getElementById(id).value = ""));
    limpiarIngenieria();
    aplicarClasificacionDistancia();
    setModo(false);
    marcarFilaEditando(null);
    actualizarPreview();
  }

  function marcarFilaEditando(id) {
    document.querySelectorAll("#tablaVin tr").forEach((tr) => {
      tr.classList.toggle("is-editing", id !== null && tr.dataset.id === String(id));
    });
  }

  function editarRegistro(item) {
    setModo(true);
    limpiarIngenieria();

    document.getElementById("id").value = item.id_cat_modelo_vin;
    document.getElementById("id_inventario").value = item.id_inventario ?? "";
    document.getElementById("modelo").value = item.modelo;
    document.getElementById("estado").value = item.estado;
    document.getElementById("id_fabricante").value = item.id_fabricante;
    document.getElementById("id_tipo_vehiculo").value = item.id_tipo_vehiculo;
    document.getElementById("peso_bruto_kg").value = item.peso_bruto_kg;
    document.getElementById("id_tipo_motor").value = item.id_tipo_motor;
    document.getElementById("potencia_hp").value = item.potencia_hp;
    document.getElementById("anio").value = item.id_cat_anio_vin;
    document.getElementById("id_planta").value = item.id_planta;

    aplicarClasificacionDistancia();
    document.getElementById("distancia_ejes").value = item.distancia_ejes;

    actualizarPreview();
    marcarFilaEditando(item.id_cat_modelo_vin);

    if (!item.id_inventario) {
      Swal.fire({
        icon: "info",
        title: "Asigne el producto",
        text: "Este modelo NIV no está ligado a un producto de inventario. Selecciónelo antes de actualizar.",
      });
    }

    window.scrollTo({ top: 0, behavior: "smooth" });
  }

  /* ----------------------------- tabla ----------------------------- */

  function cargarTabla() {
    const tbody = document.getElementById("tablaVin");
    tbody.innerHTML = `<tr><td colspan="6" class="text-center text-muted py-4">
        <span class="spinner-border spinner-border-sm me-1"></span> Cargando...</td></tr>`;

    fetch(base_url + "/Inv_captura_vin/getModelosVin")
      .then((res) => res.json())
      .then((res) => {
        modelosNivCache = res.success ? res.data : [];
        document.getElementById("totalModelos").textContent = modelosNivCache.length;
        renderTabla(document.getElementById("buscarModelo").value);
      });
  }

  function vinDeItem(item) {
    return armarVinBase(
      calcularPosiciones({
        wmi: item.wmi,
        tipo: item.caracter_vehiculo,
        peso: item.peso_bruto_kg,
        motor: item.caracter_motor,
        hp: item.potencia_hp,
        clasificacion: clasificacionDistancia(item.categoria),
        distancia: item.distancia_ejes,
        anio: item.codigo_anio,
        planta: item.caracter_planta,
      }),
    );
  }

  function chipVin(vin) {
    return `<span class="niv-chip">
        <span class="seg-wmi">${escapeHtml(vin.substring(0, 3))}</span><span class="seg-vds">${escapeHtml(vin.substring(3, 8))}</span><span class="seg-chk">${escapeHtml(vin.substring(8, 9))}</span><span class="seg-vis">${escapeHtml(vin.substring(9, 11))}</span>
      </span>`;
  }

  function renderTabla(filtro = "") {
    const tbody = document.getElementById("tablaVin");
    const f = filtro.trim().toLowerCase();

    const filas = modelosNivCache
      .map((item) => ({ item, vin: vinDeItem(item) }))
      .filter(({ item, vin }) =>
        !f || [item.modelo, vin, item.anio, item.planta].some((v) => String(v ?? "").toLowerCase().includes(f)),
      );

    if (filas.length === 0) {
      tbody.innerHTML = `<tr><td colspan="6" class="text-center text-muted py-4">
          <i class="ri-inbox-line fs-20 d-block mb-1"></i>${f ? "Sin coincidencias" : "Sin registros"}</td></tr>`;
      return;
    }

    const idEditando = document.getElementById("id").value;

    tbody.innerHTML = filas
      .map(({ item, vin }) => {
        const activo = item.estado == 2;
        const sinProducto = !item.id_inventario
          ? '<span class="badge bg-danger-subtle text-danger ms-1" title="Sin producto de inventario ligado">Sin producto</span>'
          : "";

        return `<tr data-id="${item.id_cat_modelo_vin}" class="${String(item.id_cat_modelo_vin) === idEditando ? "is-editing" : ""}">
            <td class="ps-3">
              <div class="fw-medium">${escapeHtml(item.modelo)}${sinProducto}</div>
              <div class="text-muted fs-12">${escapeHtml(item.fabricante)} · ${escapeHtml(item.tipo_vehiculo)} · ${escapeHtml(item.motor)}</div>
            </td>
            <td>${chipVin(vin)}</td>
            <td>${escapeHtml(item.anio)} <span class="text-muted fs-12">(${escapeHtml(item.codigo_anio)})</span></td>
            <td>${escapeHtml(item.planta)} <span class="text-muted fs-12">(${escapeHtml(item.caracter_planta)})</span></td>
            <td>${
              activo
                ? '<span class="badge bg-success-subtle text-success">Activo</span>'
                : '<span class="badge bg-danger-subtle text-danger">Inactivo</span>'
            }</td>
            <td class="text-end pe-3">
              <button type="button" class="btn btn-sm btn-soft-dark" data-historial="${item.id_cat_modelo_vin}" title="Historial de cambios">
                <i class="ri-history-line align-bottom"></i>
              </button>
              <button type="button" class="btn btn-sm btn-soft-primary" data-editar="${item.id_cat_modelo_vin}" title="Editar">
                <i class="ri-pencil-line align-bottom"></i> Editar
              </button>
            </td>
          </tr>`;
      })
      .join("");
  }
});
