let trasladoActual = null;
let html5QrCode = null;
let modoOperacion = null; // "salida" | "recepcion"

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

  const btnSalida = document.querySelector("#btnRegistrarSalida");
  const btnRecepcion = document.querySelector("#btnRegistrarRecepcion");

  // Ocultar ambos por defecto
  btnSalida.classList.add("d-none");
  btnRecepcion.classList.add("d-none");

  switch (parseInt(data.estado)) {
    // Solicitud pendiente de salida
    case 1:
      btnSalida.classList.remove("d-none");
      break;

    // Ya salió, pendiente de recepción
    case 2:
      btnRecepcion.classList.remove("d-none");
      break;

    // En tránsito (si lo utilizas después)
    case 3:
      btnRecepcion.classList.remove("d-none");
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
  } else if ([2, 3].includes(parseInt(data.estado))) {
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
    html += `

        <div
            class="border-bottom p-3 unidad-item"
            data-vin="${u.vin}"
            data-validado="0">

            <div class="d-flex justify-content-between">

                <div>

                    <strong>${u.vin}</strong>

                    <br>

                    <small class="text-muted">
                        ${u.modelo}
                    </small>

                </div>

                <span
                    class="badge bg-secondary estado-vin">

                    Pendiente

                </span>

            </div>

        </div>

        `;
  });

  document.querySelector("#contenedorUnidades").innerHTML = html;
}

function obtenerEstado(estado) {
  let estados = {
    1: '<span class="badge bg-warning">Solicitud</span>',
    2: '<span class="badge bg-primary">Salida</span>',
    3: '<span class="badge bg-info">En tránsito</span>',
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

  let badge = unidad.querySelector(".estado-vin");

  badge.classList.remove("bg-secondary");

  badge.classList.add("bg-success");

  badge.innerHTML = "Validado";

  unidad.setAttribute("data-validado", "1");

  document.querySelector("#vinBusqueda").value = "";

  Swal.fire("Correcto", "Unidad validada", "success");
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
  .querySelector("#btnRegistrarRecepcion")
  .addEventListener("click", registrarRecepcion);

function registrarSalida() {
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
        });
    }
  });
}

function registrarRecepcion() {
  let folio = document.querySelector("#lblFolio").innerHTML.trim();

  Swal.fire({
    title: "¿Registrar recepción?",

    text: "Se agregará la unidad al almacén destino",

    icon: "warning",

    showCancelButton: true,

    confirmButtonText: "Sí, recibir",
  }).then((result) => {
    if (result.isConfirmed) {
      fetch(base_url + "/Inv_operaciones_traslados/registrarRecepcion", {
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
            Swal.fire("Error", resp.msg, "error");

            return;
          }

          Swal.fire(
            "Correcto",
            "Recepción registrada correctamente",
            "success",
          ).then(() => {
            limpiarPantalla();
          });
        });
    }
  });
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
  document.querySelector("#btnRegistrarRecepcion").classList.add("d-none");

  document.querySelector("#cardTraslado").classList.add("d-none");
  document.querySelector("#cardVin").classList.add("d-none");
  document.querySelector("#cardUnidades").classList.add("d-none");
  document.querySelector("#cardAcciones").classList.add("d-none");

  document.querySelector("#cardUnidadesExtra").classList.add("d-none");
  document.querySelector("#contenedorUnidadesExtra").innerHTML = "";
  modoOperacion = null;

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
    if (modoOperacion === "recepcion") {
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

  const badge = unidad.querySelector(".estado-vin");
  badge.classList.remove("bg-secondary");
  badge.classList.add("bg-success");
  badge.innerHTML = "Validado";
  unidad.setAttribute("data-validado", "1");

  reproducirSonido(true);
  if (navigator.vibrate) navigator.vibrate(100);

  mostrarFlashEscaneo("success", `VIN ${vin} validado correctamente`);

  if (validarTodasLasUnidades()) {
    mostrarFlashEscaneo("success", "Todas las unidades han sido validadas");
    setTimeout(detenerScannerVin, 800);
  }
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
