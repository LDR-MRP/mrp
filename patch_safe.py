import re

with open('/home/christianguarneros/proyectos/mrp/Assets/js/modulos/functions_lgs_envios_detalle.js', 'r') as f:
    js_content = f.read()

# I will replace the whole regresarABorrador function.
# I'll use regex to match from "function regresarABorrador() {" up to the closing brace that is before "function initSortables()" or similar.
# The easier way is to find the exact block since I just wrote it.

old_block = """function regresarABorrador() {
    Swal.fire({
        title: "¿Regresar a Borrador?",
        text: "El envío volverá a estado de Borrador y dejará de estar confirmado. ¿Desea continuar?",
        icon: "warning",
        showCancelButton: true,
        confirmButtonText: "Sí, regresar",
        cancelButtonText: "Cancelar"
    }).then((result) => {
        if (result.isConfirmed) {
            const idEnvio = document.getElementById('id_envio').value;
            let request = (window.XMLHttpRequest) ? new XMLHttpRequest() : new ActiveXObject('Microsoft.XMLHTTP');
            let ajaxUrl = base_url + '/Lgs_envios/reabrirEnvio';
            let strData = "idEnvio=" + idEnvio;
            
            request.open("POST", ajaxUrl, true);
            request.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
            request.send(strData);
            
            request.onreadystatechange = function () {
                if (request.readyState == 4 && request.status == 200) {
                    let objData = JSON.parse(request.responseText);
                    if (objData.status) {
                        Swal.fire("Éxito", objData.msg, "success").then(() => {
                            window.location.reload();
                        });
                    } else {
                        Swal.fire("Error", objData.msg, "error");
                    }
                } else if (request.readyState == 4) {
                    Swal.fire("Error", "Ocurrió un problema de red (Código: " + request.status + ").", "error");
                }
            }
        }
    });
}"""

new_block = """function regresarABorrador() {
    Swal.fire({
        title: '¿Regresar a Borrador?',
        text: 'El envío volverá a estado de Borrador para permitir su edición y recalcular costos. ¿Desea continuar?',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Sí, regresar',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            Swal.fire({
                title: 'Reabriendo...',
                allowOutsideClick: false,
                didOpen: () => { Swal.showLoading(); }
            });

            const idEnvio = document.getElementById('id_envio').value;
            let request = new XMLHttpRequest();
            let ajaxUrl = base_url + '/Lgs_envios/reabrir';
            let formData = new FormData();
            formData.append('id_envio', idEnvio);

            request.open("POST", ajaxUrl, true);
            request.send(formData);

            request.onreadystatechange = function () {
                if (request.readyState == 4) {
                    if (request.status == 200) {
                        try {
                            let objData = JSON.parse(request.responseText);
                            if (objData.status) {
                                Swal.fire("Éxito", objData.msg, "success").then(() => {
                                    window.location.reload();
                                });
                            } else {
                                Swal.fire("Error", objData.msg || "Ocurrió un error.", "error");
                            }
                        } catch(e) {
                            Swal.fire("Error de Servidor", "La respuesta no es válida.", "error");
                            console.error("Respuesta no JSON:", request.responseText);
                        }
                    } else {
                        Swal.fire("Error", "Ocurrió un problema de red (Código: " + request.status + ").", "error");
                    }
                }
            }
        }
    });
}"""

if old_block in js_content:
    js_content = js_content.replace(old_block, new_block)
    with open('/home/christianguarneros/proyectos/mrp/Assets/js/modulos/functions_lgs_envios_detalle.js', 'w') as f:
        f.write(js_content)
    print("Replaced regresarABorrador with safe version.")
else:
    print("Could not find old regresarABorrador block.")

