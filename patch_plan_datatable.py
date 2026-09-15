import re

with open('/home/christianguarneros/proyectos/mrp/Assets/js/modulos/functions_lgs_planeaciones.js', 'r') as f:
    js_content = f.read()

old_btn = """                } else if (row.id_estado == 4) { // Rechazado -> Puede reabrirse (o clonarse)
                    btnEdit = `<button class="btn btn-sm btn-soft-warning rounded-circle shadow-none ms-1" onClick="fntReabrirPlan(${row.id_planeacion})" title="Reabrir Planeación">
                                 <i class="ri-refresh-line"></i>
                               </button>`;
                }"""

new_btn = """                } else if (row.id_estado == 4) { // Rechazado -> Se clona para no perder histórico
                    btnEdit = `<button class="btn btn-sm btn-soft-warning rounded-circle shadow-none ms-1" onClick="clonarPlaneacion(${row.id_planeacion})" title="Clonar como Nueva Planeación">
                                 <i class="ri-file-copy-line"></i>
                               </button>`;
                }"""

if old_btn in js_content:
    js_content = js_content.replace(old_btn, new_btn)
    with open('/home/christianguarneros/proyectos/mrp/Assets/js/modulos/functions_lgs_planeaciones.js', 'w') as f:
        f.write(js_content)
    print("Replaced Datatable button.")
else:
    print("Datatable button not found.")
