import re

with open('/home/christianguarneros/proyectos/mrp/Assets/js/modulos/functions_lgs_planeaciones.js', 'r') as f:
    js_content = f.read()

# I want to add the clonar button logic
func_to_add = """
function clonarPlaneacion(idPlaneacion) {
    Swal.fire({
        title: '¿Clonar Planeación?',
        text: 'Se creará una copia en estado de Borrador con los mismos envíos, dejándolos disponibles para edición.',
        icon: 'info',
        showCancelButton: true,
        confirmButtonText: 'Sí, clonar',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            Swal.fire({
                title: 'Clonando...',
                allowOutsideClick: false,
                didOpen: () => { Swal.showLoading(); }
            });
            let request = new XMLHttpRequest();
            let ajaxUrl = base_url + '/Lgs_planeaciones/clonarPlaneacion';
            let formData = new FormData();
            formData.append('id_planeacion', idPlaneacion);

            request.open("POST", ajaxUrl, true);
            request.send(formData);

            request.onreadystatechange = function () {
                if (request.readyState == 4) {
                    if (request.status == 200) {
                        try {
                            let objData = JSON.parse(request.responseText);
                            if (objData.status) {
                                Swal.fire("Éxito", objData.msg, "success");
                                tablePlaneaciones.ajax.reload();
                                if (document.getElementById('view-detalle-planeaciones').style.display !== 'none') {
                                    fntSwitchView('grid');
                                }
                            } else {
                                Swal.fire("Error", objData.msg || "Error al clonar", "error");
                            }
                        } catch(e) {
                            Swal.fire("Error de Servidor", "La respuesta no es válida.", "error");
                        }
                    } else {
                        Swal.fire("Error", "Ocurrió un problema de red (Código: " + request.status + ").", "error");
                    }
                }
            }
        }
    });
}
"""

if "function clonarPlaneacion(" not in js_content:
    with open('/home/christianguarneros/proyectos/mrp/Assets/js/modulos/functions_lgs_planeaciones.js', 'a') as f:
        f.write(func_to_add)
    print("Added clonarPlaneacion JS function.")

