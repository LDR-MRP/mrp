import re

with open('/home/christianguarneros/proyectos/mrp/Assets/js/modulos/functions_lgs_planeaciones.js', 'r') as f:
    js_content = f.read()

old_block = """                    } else if (parseInt(row.id_estado) === 3 || parseInt(row.id_estado) === 2) {
                        btnActionExtra = `<button class="btn btn-sm btn-soft-warning rounded-pill px-3 fw-semibold me-1" onClick="fntReabrirPlan(${data})" title="Reabrir y Desbloquear Planeación">
                                            <i class="ri-restart-line me-1"></i> Reabrir
                                          </button>`;
                    }"""

new_block = """                    } else if (parseInt(row.id_estado) === 3 || parseInt(row.id_estado) === 2) {
                        btnActionExtra = `<button class="btn btn-sm btn-soft-warning rounded-pill px-3 fw-semibold me-1" onClick="fntReabrirPlan(${data})" title="Reabrir y Desbloquear Planeación">
                                            <i class="ri-restart-line me-1"></i> Reabrir
                                          </button>`;
                    } else if (parseInt(row.id_estado) === 4) {
                        btnActionExtra = `<button class="btn btn-sm btn-soft-info rounded-pill px-3 fw-semibold me-1" onClick="clonarPlaneacion(${data})" title="Clonar (Reutilizar Histórico)">
                                            <i class="ri-file-copy-line me-1"></i> Clonar
                                          </button>`;
                    }"""

if old_block in js_content:
    js_content = js_content.replace(old_block, new_block)
    with open('/home/christianguarneros/proyectos/mrp/Assets/js/modulos/functions_lgs_planeaciones.js', 'w') as f:
        f.write(js_content)
    print("Replaced Datatable block.")
else:
    print("Datatable block not found.")

