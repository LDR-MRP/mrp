let divLoading = null;

document.addEventListener("DOMContentLoaded", function () {
  divLoading = document.querySelector("#divLoading");

  // ------------------------------------------------------------------
  //  DESCARGAR PLANTILLA
  // ------------------------------------------------------------------
  const btnPlantilla = document.querySelector("#btnDescargarPlantilla");
  if (btnPlantilla) {
    btnPlantilla.addEventListener("click", function () {
      window.open(base_url + "/Inv_cargamasiva/descargarPlantilla", "_blank");
    });
  }

  // ------------------------------------------------------------------
  //  ALTA MASIVA
  // ------------------------------------------------------------------
  const formAltas = document.querySelector("#formAltasMasivas");
  if (formAltas) {
    formAltas.addEventListener("submit", function (e) {
      e.preventDefault();
      subirArchivo({
        form: formAltas,
        url: base_url + "/Inv_cargamasiva/procesarAltas",
        boxResultado: document.querySelector("#resultadoAltas"),
        alertBox: document.querySelector("#alertResultadoAltas"),
        btnLog: document.querySelector("#btnLogAltas"),
        tipoLog: "altas",
        campoOk: "insertados",
      });
    });
  }

  // ------------------------------------------------------------------
  //  ACTUALIZACIÓN MASIVA
  // ------------------------------------------------------------------
  const formActualizacion = document.querySelector("#formActualizacionMasiva");
  if (formActualizacion) {
    formActualizacion.addEventListener("submit", function (e) {
      e.preventDefault();
      subirArchivo({
        form: formActualizacion,
        url: base_url + "/Inv_cargamasiva/procesarActualizacion",
        boxResultado: document.querySelector("#resultadoActualizacion"),
        alertBox: document.querySelector("#alertResultadoActualizacion"),
        btnLog: document.querySelector("#btnLogActualizacion"),
        tipoLog: "actualizacion",
        campoOk: "actualizados",
      });
    });
  }

  const btnLogAltas = document.querySelector("#btnLogAltas");
  if (btnLogAltas) {
    btnLogAltas.addEventListener("click", function () {
      window.open(base_url + "/Inv_cargamasiva/exportarLog/altas", "_blank");
    });
  }

  const btnLogActualizacion = document.querySelector("#btnLogActualizacion");
  if (btnLogActualizacion) {
    btnLogActualizacion.addEventListener("click", function () {
      window.open(base_url + "/Inv_cargamasiva/exportarLog/actualizacion", "_blank");
    });
  }
});

// ------------------------------------------------------------------------
//  SUBE EL ARCHIVO Y MUESTRA EL RESULTADO
// ------------------------------------------------------------------------
function subirArchivo(cfg) {
  const inputArchivo = cfg.form.querySelector('input[type="file"]');

  if (!inputArchivo || !inputArchivo.files || inputArchivo.files.length === 0) {
    Swal.fire("Atención", "Selecciona un archivo antes de continuar.", "warning");
    return;
  }

  if (divLoading) divLoading.style.display = "flex";

  const btnSubmit = cfg.form.querySelector('button[type="submit"]');
  if (btnSubmit) btnSubmit.disabled = true;

  const formData = new FormData(cfg.form);

  fetch(cfg.url, {
    method: "POST",
    body: formData,
  })
    .then((resp) => resp.json())
    .then((objData) => {
      if (divLoading) divLoading.style.display = "none";
      if (btnSubmit) btnSubmit.disabled = false;

      if (!objData.status) {
        Swal.fire("Atención", objData.msg || "No fue posible procesar el archivo.", "warning");
        return;
      }

      cfg.boxResultado.style.display = "block";

      const ok = objData[cfg.campoOk] || 0;
      const omitidos = objData.omitidos || 0;

      cfg.alertBox.className = "alert " + (omitidos > 0 ? "alert-warning" : "alert-success");
      cfg.alertBox.innerHTML = objData.msg;

      if (omitidos > 0) {
        cfg.btnLog.style.display = "inline-block";
      } else {
        cfg.btnLog.style.display = "none";
      }

      cfg.form.reset();

      Swal.fire({
        title: "Proceso finalizado",
        text: objData.msg,
        icon: omitidos > 0 ? "warning" : "success",
        confirmButtonText: "OK",
        confirmButtonColor: "#28a745",
      });
    })
    .catch(function (err) {
      if (divLoading) divLoading.style.display = "none";
      if (btnSubmit) btnSubmit.disabled = false;
      console.error(err);
      Swal.fire("Error", "Ocurrió un error en el servidor. Inténtalo de nuevo.", "error");
    });
}
