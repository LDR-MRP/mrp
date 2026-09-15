import re

with open('/home/christianguarneros/proyectos/mrp/Controllers/Lgs_envios.php', 'r') as f:
    ctrl_content = f.read()

old_rule = "$puedeReabrir = ($estadoActual === 4 || $estadoActual === 8 || ($estadoActual === 2 && $estadoPlan < 3));"
new_rule = "$puedeReabrir = ($estadoActual === 4 || $estadoActual === 8 || ($estadoActual === 2 && $estadoPlan < 2));"

if old_rule in ctrl_content:
    ctrl_content = ctrl_content.replace(old_rule, new_rule)
    with open('/home/christianguarneros/proyectos/mrp/Controllers/Lgs_envios.php', 'w') as f:
        f.write(ctrl_content)
    print("Replaced rule in reabrirEnvio.")
else:
    print("Rule not found in reabrirEnvio.")

