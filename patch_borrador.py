import re

# 1. Update detalle.php
with open('/home/christianguarneros/proyectos/mrp/Views/Lgs_envios/detalle.php', 'r') as f:
    content = f.read()

old_btn = """                        <button class="btn btn-warning rounded-pill px-4 shadow-sm" onclick="guardarAcomodo(false);">
                            <i class="ri-edit-line me-1"></i> <?= $enPlaneacionAbierta ? 'Editar (Regresa a Borrador)' : 'Editar (Regresa a Borrador)' ?>
                        </button>"""

new_btn = """                        <button class="btn btn-warning rounded-pill px-4 shadow-sm" onclick="regresarABorrador();">
                            <i class="ri-edit-line me-1"></i> <?= $enPlaneacionAbierta ? 'Editar (Regresa a Borrador)' : 'Editar (Regresa a Borrador)' ?>
                        </button>"""

if old_btn in content:
    content = content.replace(old_btn, new_btn)
    with open('/home/christianguarneros/proyectos/mrp/Views/Lgs_envios/detalle.php', 'w') as f:
        f.write(content)
    print("detalle.php updated!")
else:
    print("Could not find button block in detalle.php")


# 2. Update JS to add regresarABorrador()
with open('/home/christianguarneros/proyectos/mrp/Assets/js/modulos/functions_lgs_envios_detalle.js', 'r') as f:
    js_content = f.read()

func_to_add = """
function regresarABorrador() {
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
            fetch(base_url + '/Lgs_envios/reabrirEnvio', {
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
            });
        }
    });
}
"""

if "function regresarABorrador(" not in js_content:
    with open('/home/christianguarneros/proyectos/mrp/Assets/js/modulos/functions_lgs_envios_detalle.js', 'a') as f:
        f.write(func_to_add)
    print("functions_lgs_envios_detalle.js updated!")
else:
    print("regresarABorrador already exists.")
