import re

with open('/home/christianguarneros/proyectos/mrp/Assets/js/modulos/functions_lgs_envios_detalle.js', 'r') as f:
    js_content = f.read()

old_fetch = """            fetch(base_url + '/Lgs_envios/reabrirEnvio', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'idEnvio=' + idEnvio
            })
            .then(res => res.json())
            .then(data => {
                if(data.status) {
                    Swal.fire("Éxito", "El envío ha regresado a borrador.", "success").then(() => {
                        window.location.reload();
                    });
                } else {
                    Swal.fire("Error", data.msg, "error");
                }
            })
            .catch(err => {
                Swal.fire("Error", "Ocurrió un problema de red.", "error");
            });"""

new_xhr = """            let request = (window.XMLHttpRequest) ? new XMLHttpRequest() : new ActiveXObject('Microsoft.XMLHTTP');
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
            }"""

if old_fetch in js_content:
    js_content = js_content.replace(old_fetch, new_xhr)
    with open('/home/christianguarneros/proyectos/mrp/Assets/js/modulos/functions_lgs_envios_detalle.js', 'w') as f:
        f.write(js_content)
    print("Replaced fetch with XMLHttpRequest.")
else:
    print("Could not find the fetch block.")

