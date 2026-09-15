import re

with open('/home/christianguarneros/proyectos/mrp/Models/Lgs_aprobacionesModel.php', 'r') as f:
    content = f.read()

# Replace p.obs_operador, with '' AS obs_operador,
content = content.replace("p.obs_operador,", "'' AS obs_operador,")

with open('/home/christianguarneros/proyectos/mrp/Models/Lgs_aprobacionesModel.php', 'w') as f:
    f.write(content)
print("Fixed query in Aprobaciones Model")
