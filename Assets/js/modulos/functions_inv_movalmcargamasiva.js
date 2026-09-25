let divLoadingMovAlmCarga = null;

document.addEventListener("DOMContentLoaded", function () {
  divLoadingMovAlmCarga = document.querySelector("#divLoading");

  // ------------------------------------------------------------------
  //  DESCARGAR PLANTILLA
  // ------------------------------------------------------------------
  const btnPlantilla = document.querySelector("#btnDescargarPlantillaMovAlm");
  if (btnPlantilla) {
    btnPlantilla.addEventListener("click", function () {
      window.open(base_url + "/Inv_movalmcargamasiva/descargarPlantilla", "_blank");
    });
  }

  // ------------------------------------------------------------------
  //  CARGA MASIVA DE TRASPASOS
  // ------------------------------------------------------------------
  const formCarga = document.querySelector("#formCargaTraspasos");
  if (formCarga) {
    formCarga.addEventListener("submit", function (e) {
      e.preventDefault();

      const inputArchivo = formCarga.querySelector('input[type="file"]');
      if (!inputArchivo || !inputArchivo.files || inputArchivo.files.length === 0) {
        Swal.fire("Atención", "Selecciona un archivo antes de continuar.", "warning");
        return;
      }

      const boxResultado = document.querySelector("#resultadoCargaTraspasos");
      const alertBox = document.querySelector("#alertResultadoCargaTraspasos");
      const btnLog = document.querySelector("#btnLogCargaTraspasos");

      if (divLoadingMovAlmCarga) divLoadingMovAlmCarga.style.display = "flex";

      const btnSubmit = formCarga.querySelector('button[type="submit"]');
      if (btnSubmit) btnSubmit.disabled = true;

      const formData = new FormData(formCarga);

      fetch(base_url + "/Inv_movalmcargamasiva/procesarCarga", {
        method: "POST",
        body: formData,
      })
        .then((resp) => resp.json())
        .then((objData) => {
          if (divLoadingMovAlmCarga) divLoadingMovAlmCarga.style.display = "none";
          if (btnSubmit) btnSubmit.disabled = false;

          if (!objData.status) {
            Swal.fire("Atención", objData.msg || "No fue posible procesar el archivo.", "warning");
            return;
          }

          boxResultado.style.display = "block";

          const omitidos = objData.omitidos || 0;

          alertBox.className = "alert " + (omitidos > 0 ? "alert-warning" : "alert-success");
          alertBox.innerHTML = objData.msg;

          if (omitidos > 0) {
            btnLog.style.display = "inline-block";
          } else {
            btnLog.style.display = "none";
          }

          formCarga.reset();

          Swal.fire({
            title: "Proceso finalizado",
            text: objData.msg,
            icon: omitidos > 0 ? "warning" : "success",
            confirmButtonText: "OK",
            confirmButtonColor: "#28a745",
          });
        })
        .catch(function (err) {
          if (divLoadingMovAlmCarga) divLoadingMovAlmCarga.style.display = "none";
          if (btnSubmit) btnSubmit.disabled = false;
          console.error(err);
          Swal.fire("Error", "Ocurrió un error en el servidor. Inténtalo de nuevo.", "error");
        });
    });
  }

  const btnLogCargaTraspasos = document.querySelector("#btnLogCargaTraspasos");
  if (btnLogCargaTraspasos) {
    btnLogCargaTraspasos.addEventListener("click", function () {
      window.open(base_url + "/Inv_movalmcargamasiva/exportarLog", "_blank");
    });
  }
});
