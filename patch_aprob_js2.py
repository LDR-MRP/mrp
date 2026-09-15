import sys

file_path = '/home/christianguarneros/proyectos/mrp/Assets/js/modulos/functions_lgs_aprobaciones.js'
with open(file_path, 'r') as f:
    content = f.read()

target = """    document.getElementById('bodyDetalleRutas').innerHTML = '<tr><td colspan="6" class="text-center"><div class="spinner-border text-primary spinner-border-sm" role="status"></div> Cargando rutas...</td></tr>';"""

replacement = """    const contEnvios = document.getElementById('vdp_contenedor_envios');
    if (contEnvios) contEnvios.innerHTML = '<div class="text-center py-4"><div class="spinner-border text-primary spinner-border-sm" role="status"></div> <span class="ms-2">Cargando desglose de envíos...</span></div>';"""

if target in content:
    content = content.replace(target, replacement)
    with open(file_path, 'w') as f:
        f.write(content)
    print("Patched loading JS successfully")
else:
    print("Target not found in JS loading")
