import re

with open('/home/christianguarneros/proyectos/mrp/Views/Lgs_envios/index.php', 'r') as f:
    content = f.read()

# Add onchange to select
content = content.replace(
    'id="id_tipo_traslado" name="id_tipo_traslado" required>',
    'id="id_tipo_traslado" name="id_tipo_traslado" required onchange="handleTipoTrasladoEnvio()">'
)

with open('/home/christianguarneros/proyectos/mrp/Views/Lgs_envios/index.php', 'w') as f:
    f.write(content)

print("Patched index.php")
