function verModuloQr() {
  window.location = base_url + "/Inv_reportes/qrProductos";
}

function generarEtiquetasMasivas() {
  let formData = new FormData();

  formData.append("desde", document.querySelector("#claveDesde").value);
  formData.append("hasta", document.querySelector("#claveHasta").value);
  formData.append("numEtiquetas", document.querySelector("#numEtiquetas").value);
  formData.append("linea", document.querySelector("#lineaProducto").value);

  fetch(base_url + "/Inv_reportes/generarEtiquetasMasivas", {
    method: "POST",
    body: formData,
  })
    .then(response => response.blob())
    .then(blob => {
      let url = window.URL.createObjectURL(blob);
      window.open(url);
    })
    .catch(error => {
      console.error("Error:", error);
    });
}


