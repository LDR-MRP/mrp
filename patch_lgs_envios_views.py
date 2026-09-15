import re

with open('/home/christianguarneros/proyectos/mrp/Views/Lgs_envios/detalle.php', 'r') as f:
    view_content = f.read()

old_var = "$enPlaneacionAbierta = ($estadoEnvio === 2 && $estadoPlan < 3);"
new_var = "$enPlaneacionAbierta = ($estadoEnvio === 2 && $estadoPlan < 2);"

if old_var in view_content:
    view_content = view_content.replace(old_var, new_var)
    with open('/home/christianguarneros/proyectos/mrp/Views/Lgs_envios/detalle.php', 'w') as f:
        f.write(view_content)
    print("Replaced rule in detalle.php")
else:
    print("Rule not found in detalle.php")

