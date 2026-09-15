import re

with open('/home/christianguarneros/proyectos/mrp/Assets/js/modulos/functions_lgs_envios.js', 'r') as f:
    content = f.read()

# Add handleTipoTrasladoEnvio() call inside openModal, at the end
old_str = "fntSwitchView('form');\n}"
new_str = "handleTipoTrasladoEnvio();\n    " + old_str
content = content.replace(old_str, new_str)

with open('/home/christianguarneros/proyectos/mrp/Assets/js/modulos/functions_lgs_envios.js', 'w') as f:
    f.write(content)

print("Patched JS 3")
