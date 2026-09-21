let divLoadingMovCarga = null;

document.addEventListener("DOMContentLoaded", function () {
  divLoadingMovCarga = document.querySelector("#divLoading");

  // ------------------------------------------------------------------
  //  DESCARGAR PLANTILLA
  // ------------------------------------------------------------------
  const btnPlantilla = document.querySelector("#btnDescargarPlantillaMov");
  if (btnPlantilla) {
    btnPlantilla.addEventListener("click", function () {
      window.open(base_url + "/Inv_movcargamasiva/descargarPlantilla", "_blank");
    });
  }

  // ------------------------------------------------------------------
  //  CARGA MASIVA DE MOVIMIENTOS
  // ------------------------------------------------------------------
  const formCarga = document.querySelector("#formCargaMovimientos");
  if (formCarga) {
    formCarga.addEventListener("submit", function (e) {
      e.preventDefault();

      const inputArchivo = formCarga.querySelector('input[type="file"]');
      if (!inputArchivo || !inputArchivo.files || inputArchivo.files.length === 0) {
        Swal.fire("Atención", "Selecciona un archivo antes de continuar.", "warning");
        return;
      }

      const boxResultado = document.querySelector("#resultadoCargaMovimientos");
      const alertBox = document.querySelector("#alertResultadoCargaMovimientos");
      const btnLog = document.querySelector("#btnLogCargaMovimientos");

      if (divLoadingMovCarga) divLoadingMovCarga.style.display = "flex";

      const btnSubmit = formCarga.querySelector('button[type="submit"]');
      if (btnSubmit) btnSubmit.disabled = true;

      const formData = new FormData(formCarga);

      fetch(base_url + "/Inv_movcargamasiva/procesarCarga", {
        method: "POST",
        body: formData,
      })
        .then((resp) => resp.json())
        .then((objData) => {
          if (divLoadingMovCarga) divLoadingMovCarga.style.display = "none";
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
          if (divLoadingMovCarga) divLoadingMovCarga.style.display = "none";
          if (btnSubmit) btnSubmit.disabled = false;
          console.error(err);
          Swal.fire("Error", "Ocurrió un error en el servidor. Inténtalo de nuevo.", "error");
        });
    });
  }

  const btnLogCargaMovimientos = document.querySelector("#btnLogCargaMovimientos");
  if (btnLogCargaMovimientos) {
    btnLogCargaMovimientos.addEventListener("click", function () {
      window.open(base_url + "/Inv_movcargamasiva/exportarLog", "_blank");
    });
  }
});
