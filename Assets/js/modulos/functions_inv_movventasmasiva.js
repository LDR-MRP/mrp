document.addEventListener("DOMContentLoaded", function () {
  const divLoading = document.querySelector("#divLoading");

  // ------------------------------------------------------------------
  //  DESCARGAR LAYOUT CON TODOS LOS VINs
  // ------------------------------------------------------------------
  const btnLayout = document.querySelector("#btnDescargarLayoutVentas");
  if (btnLayout) {
    btnLayout.addEventListener("click", function () {
      window.open(base_url + "/Inv_movventasmasiva/descargarLayout", "_blank");
    });
  }

  // ------------------------------------------------------------------
  //  SUBIR LAYOUT (solo actualización)
  // ------------------------------------------------------------------
  const formCarga = document.querySelector("#formCargaVentas");
  if (formCarga) {
    formCarga.addEventListener("submit", function (e) {
      e.preventDefault();

      const inputArchivo = formCarga.querySelector('input[type="file"]');
      if (!inputArchivo || !inputArchivo.files || inputArchivo.files.length === 0) {
        Swal.fire("Atención", "Selecciona un archivo antes de continuar.", "warning");
        return;
      }

      const boxResultado = document.querySelector("#resultadoCargaVentas");
      const alertBox = document.querySelector("#alertResultadoCargaVentas");
      const btnLog = document.querySelector("#btnLogCargaVentas");
      const btnSubmit = formCarga.querySelector('button[type="submit"]');

      if (divLoading) divLoading.style.display = "flex";
      if (btnSubmit) btnSubmit.disabled = true;

      fetch(base_url + "/Inv_movventasmasiva/procesarCarga", {
        method: "POST",
        body: new FormData(formCarga),
      })
        .then((resp) => resp.json())
        .then((obj) => {
          if (divLoading) divLoading.style.display = "none";
          if (btnSubmit) btnSubmit.disabled = false;

          if (!obj.status) {
            Swal.fire("Atención", obj.msg || "No fue posible procesar el archivo.", "warning");
            return;
          }

          const omitidos = obj.omitidos || 0;
          boxResultado.style.display = "block";
          alertBox.className = "alert " + (omitidos > 0 ? "alert-warning" : "alert-success");
          alertBox.textContent = obj.msg;
          btnLog.style.display = omitidos > 0 ? "inline-block" : "none";

          formCarga.reset();

          Swal.fire({
            title: "Proceso finalizado",
            text: obj.msg,
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
    });
  }

  // ------------------------------------------------------------------
  //  DEVOLUCIONES
  // ------------------------------------------------------------------
  const btnLayoutDev = document.querySelector("#btnDescargarLayoutDev");
  if (btnLayoutDev) {
    btnLayoutDev.addEventListener("click", function () {
      const p = new URLSearchParams();
      const d = document.querySelector("#devDesde").value;
      const h = document.querySelector("#devHasta").value;
      if (d) p.set("desde", d);
      if (h) p.set("hasta", h);
      window.open(base_url + "/Inv_movventasmasiva/descargarLayoutDevoluciones?" + p.toString(), "_blank");
    });
  }

  const formDev = document.querySelector("#formCargaDevoluciones");
  if (formDev) {
    formDev.addEventListener("submit", function (e) {
      e.preventDefault();
      const input = formDev.querySelector('input[type="file"]');
      if (!input || !input.files || input.files.length === 0) {
        Swal.fire("Atención", "Selecciona un archivo antes de continuar.", "warning");
        return;
      }
      const box = document.querySelector("#resultadoCargaDevoluciones");
      const alertBox = document.querySelector("#alertResultadoCargaDevoluciones");
      const btnLogDev = document.querySelector("#btnLogCargaDevoluciones");
      const btnSubmit = formDev.querySelector('button[type="submit"]');
      if (divLoading) divLoading.style.display = "flex";
      if (btnSubmit) btnSubmit.disabled = true;

      fetch(base_url + "/Inv_movventasmasiva/procesarDevoluciones", { method: "POST", body: new FormData(formDev) })
        .then((r) => r.json())
        .then((obj) => {
          if (divLoading) divLoading.style.display = "none";
          if (btnSubmit) btnSubmit.disabled = false;
          if (!obj.status) {
            Swal.fire("Atención", obj.msg || "No fue posible procesar el archivo.", "warning");
            return;
          }
          const omitidos = obj.omitidos || 0;
          box.style.display = "block";
          alertBox.className = "alert " + (omitidos > 0 ? "alert-warning" : "alert-success");
          alertBox.textContent = obj.msg;
          btnLogDev.style.display = omitidos > 0 ? "inline-block" : "none";
          formDev.reset();
          Swal.fire({ title: "Proceso finalizado", text: obj.msg, icon: omitidos > 0 ? "warning" : "success", confirmButtonColor: "#28a745" });
        })
        .catch(function (err) {
          if (divLoading) divLoading.style.display = "none";
          if (btnSubmit) btnSubmit.disabled = false;
          console.error(err);
          Swal.fire("Error", "Ocurrió un error en el servidor. Inténtalo de nuevo.", "error");
        });
    });
  }

  const btnLogDevoluciones = document.querySelector("#btnLogCargaDevoluciones");
  if (btnLogDevoluciones) {
    btnLogDevoluciones.addEventListener("click", function () {
      window.open(base_url + "/Inv_movventasmasiva/exportarLogDevoluciones", "_blank");
    });
  }

  const btnLog = document.querySelector("#btnLogCargaVentas");
  if (btnLog) {
    btnLog.addEventListener("click", function () {
      window.open(base_url + "/Inv_movventasmasiva/exportarLog", "_blank");
    });
  }
});
