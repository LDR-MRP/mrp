import re

with open('/home/christianguarneros/proyectos/mrp/Assets/js/modulos/functions_lgs_envios.js', 'r') as f:
    content = f.read()

# Add handleTipoTrasladoEnvio() call inside fntEditRuta, near the end of populating data
old_str = "if (document.querySelector('#id_tipo_traslado')) document.querySelector('#id_tipo_traslado').value = envio.id_tipo_traslado || '';"
new_str = old_str + "\n                    handleTipoTrasladoEnvio();"
content = content.replace(old_str, new_str)

with open('/home/christianguarneros/proyectos/mrp/Assets/js/modulos/functions_lgs_envios.js', 'w') as f:
    f.write(content)

print("Patched JS 2")
