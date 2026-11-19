let divLoadingTrasCarga = null;

document.addEventListener("DOMContentLoaded", function () {
  divLoadingTrasCarga = document.querySelector("#divLoading");

  // ------------------------------------------------------------------
  //  DESCARGAR PLANTILLA
  // ------------------------------------------------------------------
  const btnPlantilla = document.querySelector("#btnDescargarPlantillaTras");
  if (btnPlantilla) {
    btnPlantilla.addEventListener("click", function () {
      window.open(base_url + "/Inv_trascargamasiva/descargarPlantilla", "_blank");
    });
  }

  // ------------------------------------------------------------------
  //  CARGA MASIVA DE TRASLADOS
  // ------------------------------------------------------------------
  const formCarga = document.querySelector("#formCargaTraslados");
  if (formCarga) {
    formCarga.addEventListener("submit", function (e) {
      e.preventDefault();

      const inputArchivo = formCarga.querySelector('input[type="file"]');
      if (!inputArchivo || !inputArchivo.files || inputArchivo.files.length === 0) {
        Swal.fire("Atención", "Selecciona un archivo antes de continuar.", "warning");
        return;
      }

      const boxResultado = document.querySelector("#resultadoCargaTraslados");
      const alertBox = document.querySelector("#alertResultadoCargaTraslados");
      const btnLog = document.querySelector("#btnLogCargaTraslados");
      const btnBandeja = document.querySelector("#btnIrBandejaTraslados");

      if (divLoadingTrasCarga) divLoadingTrasCarga.style.display = "flex";

      const btnSubmit = formCarga.querySelector('button[type="submit"]');
      if (btnSubmit) btnSubmit.disabled = true;

      fetch(base_url + "/Inv_trascargamasiva/procesarCarga", {
        method: "POST",
        body: new FormData(formCarga),
      })
        .then((resp) => resp.json())
        .then((objData) => {
          if (divLoadingTrasCarga) divLoadingTrasCarga.style.display = "none";
          if (btnSubmit) btnSubmit.disabled = false;

          if (!objData.status) {
            Swal.fire("Atención", objData.msg || "No fue posible procesar el archivo.", "warning");
            return;
          }

          const omitidos = objData.omitidos || 0;
          const traslados = objData.traslados || 0;

          boxResultado.style.display = "block";
          alertBox.className = "alert " + (omitidos > 0 ? "alert-warning" : "alert-success");
          alertBox.textContent = objData.msg;
          btnLog.style.display = omitidos > 0 ? "inline-block" : "none";
          btnBandeja.style.display = traslados > 0 ? "inline-block" : "none";

          formCarga.reset();

          // Sin omitidos: igual que en Nueva Solicitud, regresa a la
          // bandeja de traslados. Con omitidos se queda aquí para que
          // puedan descargar el log.
          Swal.fire({
            title: "Proceso finalizado",
            text: objData.msg,
            icon: omitidos > 0 ? "warning" : "success",
            confirmButtonText: "OK",
            confirmButtonColor: "#28a745",
          }).then(() => {
            if (omitidos === 0 && traslados > 0) {
              window.location.href = base_url + "/inv_traslados";
            }
          });
        })
        .catch(function (err) {
          if (divLoadingTrasCarga) divLoadingTrasCarga.style.display = "none";
          if (btnSubmit) btnSubmit.disabled = false;
          console.error(err);
          Swal.fire("Error", "Ocurrió un error en el servidor. Inténtalo de nuevo.", "error");
        });
    });
  }

  const btnLog = document.querySelector("#btnLogCargaTraslados");
  if (btnLog) {
    btnLog.addEventListener("click", function () {
      window.open(base_url + "/Inv_trascargamasiva/exportarLog", "_blank");
    });
  }
});
