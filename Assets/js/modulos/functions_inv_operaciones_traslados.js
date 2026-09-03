let trasladoActual = null;
let html5QrCode = null;
let modoOperacion = null; // "salida" | "ingreso" | "recepcion"

// Evita doble envío (doble clic, doble tap) mientras una acción
// de salida/ingreso/recepción ya está en curso.
let procesandoAccion = false;

function bloquearBotonesAccion() {
  procesandoAccion = true;
  document.querySelectorAll("#cardAcciones button").forEach((btn) => {
    btn.disabled = true;
  });
}

function desbloquearBotonesAccion() {
  procesandoAccion = false;
  document.querySelectorAll("#cardAcciones button").forEach((btn) => {
    btn.disabled = false;
  });
}

document.addEventListener("DOMContentLoaded", function () {
  const btnBuscar = document.querySelector("#btnBuscarTraslado");

  if (btnBuscar) {
    btnBuscar.addEventListener("click", buscarTraslado);
  }

  const btnValidarVin = document.querySelector("#btnValidarVin");

  if (btnValidarVin) {
    btnValidarVin.addEventListener("click", validarVin);
  }

  const btnQR = document.querySelector("#btnEscanearQR");

  if (btnQR) {
    btnQR.addEventListener("click", iniciarScannerQR);
  }

  const btnCerrarScanner = document.querySelector("#btnCerrarScanner");

  if (btnCerrarScanner) {
    btnCerrarScanner.addEventListener("click", detenerScanner);
  }

  const btnEscanearVin = document.querySelector("#btnEscanearVin");
  if (btnEscanearVin) {
    btnEscanearVin.addEventListener("click", iniciarScannerVin);
  }

  const btnCerrarScannerVin = document.querySelector("#btnCerrarScannerVin");
  if (btnCerrarScannerVin) {
    btnCerrarScannerVin.addEventListener("click", detenerScannerVin);
  }

  // ============================================
  // AUTOCARGA DESDE QR (viene del PDF de traslado)
  // ============================================
  if (typeof FOLIO_INICIAL !== "undefined" && FOLIO_INICIAL.trim() !== "") {
    document.querySelector("#folioBusqueda").value = FOLIO_INICIAL;
    buscarTraslado();
  }
});
function buscarTraslado() {
  let folio = document.querySelector("#folioBusqueda").value.trim();

  if (!folio) {
    Swal.fire("Atención", "Ingrese un folio", "warning");

    return;
  }

  fetch(
    base_url +
      "/Inv_operaciones_traslados/getTrasladoOperacion/" +
      encodeURIComponent(folio),
  )
    .then((res) => res.json())
    .then((resp) => {
      if (!resp.status) {
        Swal.fire("Error", resp.msg, "error");

        return;
      }

      cargarTraslado(resp.data);
    });
}

function cargarTraslado(data) {
  trasladoActual = data;

  desbloquearBotonesAccion();

  const btnSalida = document.querySelector("#btnRegistrarSalida");
  const btnIngreso = document.querySelector("#btnRegistrarIngreso");
  const btnRecepcion = document.querySelector("#btnRegistrarRecepcion");
  const avisoIngreso = document.querySelector("#avisoIngreso");

  // Ocultar todos por defecto
  btnSalida.classList.add("d-none");
  btnIngreso.classList.add("d-none");
  btnRecepcion.classList.add("d-none");
  avisoIngreso.classList.add("d-none");

  switch (parseInt(data.estado)) {
    // Solicitud pendiente de salida
    case 1:
      btnSalida.classList.remove("d-none");
      break;

    // Ya salió, pendiente de ingreso en patio (lo registra seguridad)
    case 2:
      btnIngreso.classList.remove("d-none");
      break;

    // Ingreso ya registrado por seguridad, pendiente de recepción interna
    case 3:
      btnRecepcion.classList.remove("d-none");
      avisoIngreso.classList.remove("d-none");
      break;

    // Recibido
    case 4:
      break;

    // Cancelado
    case 5:
      break;
  }

  if (parseInt(data.estado) === 1) {
    modoOperacion = "salida";
  } else if (parseInt(data.estado) === 2) {
    modoOperacion = "ingreso";
  } else if (parseInt(data.estado) === 3) {
    modoOperacion = "recepcion";
  } else {
    modoOperacion = null;
  }

  document.querySelector("#cardUnidadesExtra").classList.add("d-none");
  document.querySelector("#contenedorUnidadesExtra").innerHTML = "";

  document.querySelector("#cardTraslado").classList.remove("d-none");

  document.querySelector("#cardVin").classList.remove("d-none");

  document.querySelector("#cardUnidades").classList.remove("d-none");

  document.querySelector("#cardAcciones").classList.remove("d-none");

  document.querySelector("#lblFolio").innerHTML = data.folio;

  document.querySelector("#lblOrigen").innerHTML = data.origen;

  document.querySelector("#lblDestino").innerHTML = data.destino;

  document.querySelector("#lblEstado").innerHTML = obtenerEstado(data.estado);

  let html = "";

  data.unidades.forEach((u) => {
  const tieneLlave = parseInt(u.entrega_llave) === 1;

  html += `
    <div class="border-bottom p-3 unidad-item"
         data-vin="${u.vin}"
         data-iddetalle="${u.iddetalle}"
         data-tiene-llave="${tieneLlave ? 1 : 0}"
         data-tipo-llave="${u.tipo_llave || ''}"
         data-llave-recibida="0"
         data-observaciones-llave=""
         data-validado="0">

        <div class="d-flex justify-content-between">
            <div>
                <strong>${u.vin}</strong>
                <br>
                <small class="text-muted">${u.modelo}</small>
                ${tieneLlave ? `<br><span class="badge bg-info-subtle text-info"><i class="ri-key-2-line"></i> ${u.tipo_llave}</span>` : ""}
            </div>
            <span class="badge bg-secondary estado-vin">Pendiente</span>
        </div>
        <div class="llave-observacion-vin mt-1"></div>
    </div>
  `;
});

  document.querySelector("#contenedorUnidades").innerHTML = html;
}

function obtenerEstado(estado) {
  let estados = {
    1: '<span class="badge bg-warning">Solicitud</span>',
    2: '<span class="badge bg-primary">En tránsito (pendiente ingreso)</span>',
    3: '<span class="badge bg-info">En patio (pendiente recepción interna)</span>',
    4: '<span class="badge bg-success">Recibido</span>',
    5: '<span class="badge bg-danger">Cancelado</span>',
  };

  return estados[estado] || "";
}

function validarVin() {
  let vin = document.querySelector("#vinBusqueda").value.trim().toUpperCase();

  if (!vin) {
    Swal.fire("Atención", "Capture un VIN", "warning");

    return;
  }

  let unidad = document.querySelector(`.unidad-item[data-vin="${vin}"]`);

  if (!unidad) {
    Swal.fire("Error", "El VIN no pertenece a este traslado", "error");

    return;
  }

  document.querySelector("#vinBusqueda").value = "";

  // IMPORTANTE: confirmarUnidadValidada() puede abrir el diálogo
  // "¿Se recibió la llave?" (y su seguimiento de observación). Antes
  // aquí se disparaba un segundo Swal.fire("Correcto"...) justo
  // después, y SweetAlert2 no hace cola: el segundo reemplaza al
  // primero de inmediato, así que la pregunta de la llave nunca se
  // alcanzaba a ver ni a contestar. Por eso se encadena con .then()
  // y el aviso de "Unidad validada" espera a que esa pregunta (y su
  // observación, si aplica) ya se haya resuelto.
  Promise.resolve(confirmarUnidadValidada(unidad)).then(() => {
    Swal.fire("Correcto", "Unidad validada", "success");
  });
}

function validarTodasLasUnidades() {
  const pendientes = document.querySelectorAll(
    '.unidad-item[data-validado="0"]',
  );

  return pendientes.length === 0;
}

document
  .querySelector("#btnRegistrarSalida")
  .addEventListener("click", registrarSalida);

document
  .querySelector("#btnRegistrarIngreso")
  .addEventListener("click", registrarIngreso);

document
  .querySelector("#btnRegistrarRecepcion")
  .addEventListener("click", registrarRecepcion);

function registrarSalida() {
  if (procesandoAccion) return;

  if (!validarTodasLasUnidades()) {
    Swal.fire(
      "Unidades pendientes",
      "Debe validar todas las unidades antes de registrar la salida",
      "warning",
    );

    return;
  }

  Swal.fire({
    title: "¿Registrar salida?",
    text: "Se descontarán las unidades del almacén origen",
    icon: "warning",
    showCancelButton: true,
    confirmButtonText: "Sí, registrar",
  }).then((result) => {
    if (result.isConfirmed) {
      bloquearBotonesAccion();

      let folio = document.querySelector("#lblFolio").innerHTML.trim();

      fetch(base_url + "/Inv_operaciones_traslados/registrarSalida", {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
        },
        body: JSON.stringify({
          folio: folio,
        }),
      })
        .then(async (res) => {
          let texto = await res.text();

          console.log("RESPUESTA PHP:", texto);

          let resp = JSON.parse(texto);

          return resp;
        })
        .then((resp) => {
          if (!resp.status) {
            desbloquearBotonesAccion();
            Swal.fire("Error", resp.msg, "error");

            return;
          }

          Swal.fire(
            "Correcto",
            "Salida registrada correctamente",
            "success",
          ).then(() => {
            limpiarPantalla();
          });
        })
        .catch(() => {
          desbloquearBotonesAccion();
          Swal.fire("Error", "No se pudo registrar la salida", "error");
        });
    }
  });
}

function registrarIngreso() {
  if (procesandoAccion) return;

  if (!validarTodasLasUnidades()) {
    Swal.fire(
      "Unidades pendientes",
      "Debe validar todas las unidades antes de registrar el ingreso",
      "warning",
    );

    return;
  }

  Swal.fire({
    title: "¿Registrar ingreso de la unidad?",
    text: "Confirma que la unidad llegó físicamente al patio/destino. La recepción formal (con la llave) la debe hacer después una persona interna.",
    icon: "question",
    showCancelButton: true,
    confirmButtonText: "Sí, registrar ingreso",
  }).then((result) => {
    if (result.isConfirmed) {
      bloquearBotonesAccion();

      let folio = document.querySelector("#lblFolio").innerHTML.trim();

      fetch(base_url + "/Inv_operaciones_traslados/registrarIngreso", {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
        },
        body: JSON.stringify({
          folio: folio,
        }),
      })
        .then((res) => res.json())
        .then((resp) => {
          if (!resp.status) {
            desbloquearBotonesAccion();
            Swal.fire("Error", resp.msg, "error");

            return;
          }

          Swal.fire(
            "Correcto",
            resp.msg || "Ingreso registrado correctamente",
            "success",
          ).then(() => {
            limpiarPantalla();
          });
        })
        .catch(() => {
          desbloquearBotonesAccion();
          Swal.fire("Error", "No se pudo registrar el ingreso", "error");
        });
    }
  });
}

function registrarRecepcion() {
  if (procesandoAccion) return;

  let folio = document.querySelector("#lblFolio").innerHTML.trim();

  const items = document.querySelectorAll(".unidad-item");
  const validadas = [...items].filter((el) => el.getAttribute("data-validado") === "1");
  const faltantes = items.length - validadas.length;

  // Si no se validó ni una sola unidad, lo más probable es que sea un
  // clic accidental: no dejamos pasar a la confirmación genérica de
  // "faltantes", hay que validar al menos una unidad explícitamente.
  if (items.length > 0 && validadas.length === 0) {
    Swal.fire(
      "Nada validado todavía",
      "No ha validado ninguna unidad. Escanee o valide al menos una unidad antes de registrar la recepción.",
      "warning",
    );
    return;
  }

  const payload = {
    folio: folio,
    unidades: validadas.map((el) => ({
      vin: el.getAttribute("data-vin"),
      llave_recibida: el.getAttribute("data-llave-recibida") === "1",
      observaciones_llave: el.getAttribute("data-observaciones-llave") || "",
    })),
  };

  // Llaves que sí tenían que venir con la unidad (data-tiene-llave="1")
  // pero se marcaron/quedaron como NO recibidas: esto es justo lo que
  // antes desaparecía en silencio. Se avisa aquí, antes de mandar nada,
  // para que quede claro qué va a pasar con cada una.
  const llavesConProblema = validadas.filter(
    (el) =>
      el.getAttribute("data-tiene-llave") === "1" &&
      el.getAttribute("data-llave-recibida") !== "1",
  );

  let avisoLlaves = "";
  if (llavesConProblema.length > 0) {
    const vins = llavesConProblema
      .map((el) => el.getAttribute("data-vin"))
      .join(", ");
    avisoLlaves = ` ${llavesConProblema.length} llave(s) quedarán marcadas como FALTANTE (no recibidas): ${vins}.`;
  }

  const confirmar = () => {
    bloquearBotonesAccion();

    fetch(base_url + "/Inv_operaciones_traslados/registrarRecepcion", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify(payload),
    })
      .then((res) => res.json())
      .then((resp) => {
        if (!resp.status) {
          desbloquearBotonesAccion();
          Swal.fire("Error", resp.msg, "error");
          return;
        }
        Swal.fire("Correcto", resp.msg, "success").then(() => limpiarPantalla());
      })
      .catch(() => {
        desbloquearBotonesAccion();
        Swal.fire("Error", "No se pudo registrar la recepción", "error");
      });
  };

  if (faltantes > 0) {
    Swal.fire({
      title: `Hay ${faltantes} unidad(es) sin validar`,
      text:
        "Se marcarán como faltantes y el traslado se cerrará." +
        avisoLlaves +
        " ¿Continuar?",
      icon: "warning",
      showCancelButton: true,
      confirmButtonText: "Sí, cerrar así",
    }).then((r) => r.isConfirmed && confirmar());
  } else {
    Swal.fire({
      title: "¿Registrar recepción?",
      text: "Se agregarán las unidades al almacén destino." + avisoLlaves,
      icon: "warning",
      showCancelButton: true,
      confirmButtonText: "Sí, recibir",
    }).then((r) => r.isConfirmed && confirmar());
  }
}

function limpiarPantalla() {
  trasladoActual = null;

  document.querySelector("#folioBusqueda").value = "";
  document.querySelector("#vinBusqueda").value = "";

  document.querySelector("#lblFolio").innerHTML = "";
  document.querySelector("#lblOrigen").innerHTML = "";
  document.querySelector("#lblDestino").innerHTML = "";
  document.querySelector("#lblEstado").innerHTML = "";

  document.querySelector("#contenedorUnidades").innerHTML = "";

  document.querySelector("#btnRegistrarSalida").classList.add("d-none");
  document.querySelector("#btnRegistrarIngreso").classList.add("d-none");
  document.querySelector("#btnRegistrarRecepcion").classList.add("d-none");
  document.querySelector("#avisoIngreso").classList.add("d-none");

  document.querySelector("#cardTraslado").classList.add("d-none");
  document.querySelector("#cardVin").classList.add("d-none");
  document.querySelector("#cardUnidades").classList.add("d-none");
  document.querySelector("#cardAcciones").classList.add("d-none");

  document.querySelector("#cardUnidadesExtra").classList.add("d-none");
  document.querySelector("#contenedorUnidadesExtra").innerHTML = "";
  modoOperacion = null;

  desbloquearBotonesAccion();

  detenerScannerVin();
}

function iniciarScannerQR() {
  document.querySelector("#cardScanner").classList.remove("d-none");

  html5QrCode = new Html5Qrcode("reader");

  html5QrCode.start(
    {
      facingMode: "environment",
    },
    {
      fps: 10,
      qrbox: 250,
    },
    onScanSuccess,
  );
}

function onScanSuccess(decodedText) {
  const folio = extraerFolio(decodedText);

  document.querySelector("#folioBusqueda").value = folio;

  detenerScanner();

  buscarTraslado();
}

function extraerFolio(texto) {
  texto = (texto || "").trim();

  // Si no es una URL, se asume que ya es el folio plano
  if (!texto.startsWith("http://") && !texto.startsWith("https://")) {
    return texto;
  }

  try {
    const url = new URL(texto);

    const partes = url.pathname.split("/").filter(Boolean);

    const idx = partes.findIndex((p) => p.toLowerCase() === "escanear");

    if (idx !== -1 && partes[idx + 1]) {
      return decodeURIComponent(partes[idx + 1]);
    }

    // Fallback: si no encuentra "escanear", toma el último segmento de la URL
    return decodeURIComponent(partes[partes.length - 1] || "");
  } catch (e) {
    // Si algo falla al parsear, regresa el texto tal cual
    return texto;
  }
}

function detenerScanner() {
  if (html5QrCode) {
    html5QrCode.stop().then(() => {
      document.querySelector("#cardScanner").classList.add("d-none");
    });
  }
}

/* =====================================================
   SCANNER DE VIN (continuo)
===================================================== */

let html5QrCodeVin = null;
let ultimoVinEscaneado = "";
let ultimoVinTimestamp = 0;

function iniciarScannerVin() {
  const esContextoSeguro =
    window.isSecureContext ||
    location.hostname === "localhost" ||
    location.hostname === "127.0.0.1";

  if (!esContextoSeguro) {
    Swal.fire(
      "No disponible",
      "El acceso a la cámara requiere una conexión segura (HTTPS).",
      "warning",
    );
    return;
  }

  if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
    Swal.fire(
      "No disponible",
      "Este dispositivo o navegador no soporta acceso a la cámara.",
      "warning",
    );
    return;
  }

  document.querySelector("#cardScannerVin").classList.remove("d-none");

  html5QrCodeVin = new Html5Qrcode("readerVin");

  html5QrCodeVin
    .start(
      { facingMode: "environment" },
      { fps: 10, qrbox: 250 },
      onScanVinSuccess,
    )
    .catch((err) => {
      console.error("Error al iniciar cámara VIN:", err);
      document.querySelector("#cardScannerVin").classList.add("d-none");

      Swal.fire(
        "Error de cámara",
        "No se pudo acceder a la cámara para escanear el VIN.",
        "error",
      );
    });
}

function onScanVinSuccess(decodedText) {
  const vin = extraerVin(decodedText);
  const ahora = Date.now();

  if (vin === ultimoVinEscaneado && ahora - ultimoVinTimestamp < 2000) {
    return;
  }

  ultimoVinEscaneado = vin;
  ultimoVinTimestamp = ahora;

  procesarVinEscaneado(vin);
}

function extraerVin(texto) {
  texto = (texto || "").trim();

  if (!texto.startsWith("http://") && !texto.startsWith("https://")) {
    return texto.toUpperCase();
  }

  try {
    const url = new URL(texto);
    const partes = url.pathname.split("/").filter(Boolean);
    return decodeURIComponent(partes[partes.length - 1] || "").toUpperCase();
  } catch (e) {
    return texto.toUpperCase();
  }
}

function procesarVinEscaneado(vin) {
  const unidad = document.querySelector(`.unidad-item[data-vin="${vin}"]`);

  if (!unidad) {
    if (modoOperacion === "recepcion" || modoOperacion === "ingreso") {
      registrarVinAnomalo(vin);
    } else {
      reproducirSonido(false);
      if (navigator.vibrate) navigator.vibrate(300);
      mostrarFlashEscaneo("error", `VIN ${vin} no pertenece a este traslado`);
    }
    return;
  }

  if (unidad.getAttribute("data-validado") === "1") {
    mostrarFlashEscaneo("info", `VIN ${vin} ya estaba validado`);
    return;
  }

  reproducirSonido(true);
  if (navigator.vibrate) navigator.vibrate(100);

  mostrarFlashEscaneo("success", `VIN ${vin} validado correctamente`);

  // Igual que en validarVin(): se espera a que la pregunta de la
  // llave (si aplica) se resuelva antes de dar por cerrado el ciclo
  // de validación de esta unidad, para no cerrar/eclipsar el diálogo
  // ni apagar el escáner de cámara mientras sigue pendiente.
  Promise.resolve(confirmarUnidadValidada(unidad)).then(() => {
    if (validarTodasLasUnidades()) {
      mostrarFlashEscaneo("success", "Todas las unidades han sido validadas");
      setTimeout(detenerScannerVin, 800);
    }
  });
}

function confirmarUnidadValidada(unidad) {
  const badge = unidad.querySelector(".estado-vin");
  badge.classList.remove("bg-secondary");
  badge.classList.add("bg-success");
  badge.innerHTML = "Validado";
  unidad.setAttribute("data-validado", "1");

  // La pregunta de la llave solo aplica en la recepción interna
  // (estado 3). En el ingreso de seguridad (estado 2) solo se
  // confirma que la unidad llegó, sin decidir nada sobre la llave.
  if (modoOperacion === "recepcion" && unidad.getAttribute("data-tiene-llave") === "1") {
    const tipoLlave = unidad.getAttribute("data-tipo-llave");

    // Se devuelve la promesa (y no se deja "suelta") para que quien
    // llame a esta función pueda esperar a que la pregunta -y su
    // posible seguimiento de observación- ya se haya contestado
    // antes de mostrar cualquier otro aviso encima.
    return Swal.fire({
      title: "¿Se recibió la llave?",
      text: `Unidad ${unidad.getAttribute("data-vin")} - Llave ${tipoLlave}`,
      icon: "question",
      showCancelButton: true,
      confirmButtonText: "Sí, la recibí",
      cancelButtonText: "No",
      allowOutsideClick: false,
      allowEscapeKey: false,
    }).then((r) => {
      unidad.setAttribute("data-llave-recibida", r.isConfirmed ? "1" : "0");

      const obsDiv = unidad.querySelector(".llave-observacion-vin");

      if (r.isConfirmed) {
        unidad.setAttribute("data-observaciones-llave", "");
        if (obsDiv) obsDiv.innerHTML = "";
        return;
      }

      // No se recibió la llave: pedir el motivo (opcional) para
      // que quede registrado junto con el movimiento "faltante".
      return Swal.fire({
        title: "Llave no recibida",
        text: `¿Por qué no llegó la llave de la unidad ${unidad.getAttribute("data-vin")}? (opcional)`,
        input: "text",
        inputPlaceholder: "Ej. se quedó en la unidad de origen",
        icon: "warning",
        showCancelButton: true,
        confirmButtonText: "Guardar",
        cancelButtonText: "Omitir",
        allowOutsideClick: false,
      }).then((obsResult) => {
        const observacion = obsResult.isConfirmed && obsResult.value ? obsResult.value.trim() : "";
        unidad.setAttribute("data-observaciones-llave", observacion);

        if (obsDiv) {
          obsDiv.innerHTML = `<span class="badge bg-danger-subtle text-danger"><i class="ri-key-2-line"></i> Llave faltante${observacion ? `: ${observacion}` : ""}</span>`;
        }
      });
    });
  }

  return Promise.resolve();
}

let overlayScanTimeout = null;

function mostrarToast(icon, titulo) {
  mostrarFlashEscaneo(icon, titulo);
}

function mostrarFlashEscaneo(tipo, texto) {
  const overlay = document.querySelector("#overlayScan");
  const iconoEl = document.querySelector("#overlayScanIcon");
  const textoEl = document.querySelector("#overlayScanTexto");

  if (!overlay) return;

  overlay.classList.remove("bg-ok", "bg-error", "bg-info");

  let claseFondo = "bg-info";
  let claseIcono = "ri-information-fill";

  if (tipo === "success") {
    claseFondo = "bg-ok";
    claseIcono = "ri-checkbox-circle-fill";
  } else if (tipo === "error") {
    claseFondo = "bg-error";
    claseIcono = "ri-close-circle-fill";
  } else if (tipo === "info") {
    claseFondo = "bg-info";
    claseIcono = "ri-information-fill";
  } else if (tipo === "alerta") {
    claseFondo = "bg-alerta";
    claseIcono = "ri-alert-fill";
  }

  overlay.classList.add(claseFondo);
  iconoEl.className = claseIcono;
  textoEl.textContent = texto;

  overlay.classList.add("show");

  if (overlayScanTimeout) {
    clearTimeout(overlayScanTimeout);
  }

  const duracion = tipo === "error" ? 1200 : 900;

  overlayScanTimeout = setTimeout(() => {
    overlay.classList.remove("show");
  }, duracion);
}

function reproducirSonido(exito) {
  try {
    const ctx = new (window.AudioContext || window.webkitAudioContext)();
    const osc = ctx.createOscillator();
    const gain = ctx.createGain();

    osc.connect(gain);
    gain.connect(ctx.destination);

    osc.frequency.value = exito ? 880 : 220;
    gain.gain.setValueAtTime(0.2, ctx.currentTime);

    osc.start();
    osc.stop(ctx.currentTime + 0.15);
  } catch (e) {
    // Silencioso si el navegador bloquea audio sin interacción previa
  }
}

function detenerScannerVin() {
  if (html5QrCodeVin) {
    html5QrCodeVin
      .stop()
      .then(() => {
        html5QrCodeVin.clear();
        document.querySelector("#cardScannerVin").classList.add("d-none");
      })
      .catch(() => {
        document.querySelector("#cardScannerVin").classList.add("d-none");
      });
  }

  ultimoVinEscaneado = "";
  ultimoVinTimestamp = 0;
}
function registrarVinAnomalo(vin) {
  const folio = document.querySelector("#lblFolio").innerHTML.trim();

  fetch(base_url + "/Inv_operaciones_traslados/registrarUnidadAnomala", {
    method: "POST",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify({ folio: folio, vin: vin }),
  })
    .then((res) => res.json())
    .then((resp) => {
      if (!resp.status) {
        reproducirSonido(false);
        if (navigator.vibrate) navigator.vibrate(300);
        mostrarFlashEscaneo(
          "error",
          resp.msg || `No se pudo registrar VIN ${vin}`,
        );
        return;
      }

      if (resp.duplicado) {
        mostrarFlashEscaneo("info", `VIN ${vin} ya estaba marcado con alerta`);
        return;
      }

      if (navigator.vibrate) navigator.vibrate([100, 80, 100]);
      reproducirSonidoAlerta();

      mostrarFlashEscaneo(
        "alerta",
        `VIN ${vin} registrado con ALERTA (no pertenece)`,
      );

      agregarUnidadExtra(vin, resp.modelo || "");
    })
    .catch(() => {
      mostrarFlashEscaneo("error", "Error al registrar la unidad como alerta");
    });
}

function agregarUnidadExtra(vin, modelo) {
  const contenedor = document.querySelector("#contenedorUnidadesExtra");

  if (!contenedor) return;

  document.querySelector("#cardUnidadesExtra").classList.remove("d-none");

  const div = document.createElement("div");
  div.className = "border-bottom p-3";
  div.innerHTML = `
    <div class="d-flex justify-content-between">
      <div>
        <strong>${vin}</strong>
        <br>
        <small class="text-muted">${modelo}</small>
      </div>
      <span class="badge bg-warning text-dark">
        <i class="ri-alert-line me-1"></i>
        No pertenece
      </span>
    </div>
  `;

  contenedor.appendChild(div);
}

function reproducirSonidoAlerta() {
  try {
    const ctx = new (window.AudioContext || window.webkitAudioContext)();

    [660, 440].forEach((freq, i) => {
      const osc = ctx.createOscillator();
      const gain = ctx.createGain();

      osc.connect(gain);
      gain.connect(ctx.destination);

      osc.frequency.value = freq;
      gain.gain.setValueAtTime(0.2, ctx.currentTime + i * 0.15);

      osc.start(ctx.currentTime + i * 0.15);
      osc.stop(ctx.currentTime + i * 0.15 + 0.12);
    });
  } catch (e) {
    // Silencioso si el navegador bloquea audio
  }
}
